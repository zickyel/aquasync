#include <WiFi.h>
#include <HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Preferences.h>

// ==========================================
// 1. HARDCODED WIFI CREDENTIALS (ESP32 Station)
// ==========================================
const char* WIFI_SSID = "cappuccino 2.4G";
const char* WIFI_PASS = "bituinngmindanao";

// Default Fallback Thresholds (Overridden by Database settings)
const int DEFAULT_MIN_THRESHOLD = 25;  // Fallback default: 25%
const int DEFAULT_MAX_THRESHOLD = 85;  // Fallback default: 85%

int minThreshold  = DEFAULT_MIN_THRESHOLD;  // Turn ON pump when <= minThreshold
int maxThreshold  = DEFAULT_MAX_THRESHOLD;  // Turn OFF pump ONLY when reaching maxThreshold

// ==========================================
// 2. SERVER ENDPOINT URLS
// ==========================================
const char* SENSOR_API_URL   = "https://bullring-retold-reformer.ngrok-free.dev/aquasync/api/sensor_to_database_api.php";
const char* SETTINGS_API_URL = "https://bullring-retold-reformer.ngrok-free.dev/aquasync/api/fetch_from_database_api.php?type=settings";

// ==========================================
// 3. HARDWARE PINS & MOSFET (IRLB8721)
// ==========================================
#define SENSOR_PIN  1   // ESP32 GPIO 1 (ADC1) -> Water Level Sensor
#define PUMP_PIN    4   // ESP32 GPIO 4        -> Gate of IRLB8721 MOSFET

// For MOSFET (IRLB8721):
// HIGH = Turn ON pump
// LOW  = Turn OFF pump
const bool RELAY_ACTIVE_LOW = false; 

// ========================================== 
// 4. SENSOR CALIBRATION (90% Submerged = 100% Reading)
// ==========================================
const int SENSOR_DRY_VAL = 90;    // Raw ADC when dry (0%)
const int SENSOR_WET_VAL = 1050;  // Raw ADC at 90% physical submersion = 100%

// ==========================================
// 5. TIMERS & RELIABILITY SETTINGS
// ==========================================
const unsigned long SYNC_INTERVAL_MS    = 3000;    // Database sync interval: every 3 seconds
const unsigned long MAX_PUMP_RUNTIME_MS = 600000;  // Failsafe: 10 minutes maximum continuous runtime

Preferences preferences;

// System State Variables
bool isPumpOn               = false;
bool failsafeTriggered      = false;
unsigned long pumpStartTime = 0;
unsigned long lastSyncTime  = 0;

// Helper to control pin polarity (Active LOW vs Active HIGH)
void writePumpPin(bool turnOn) {
  if (RELAY_ACTIVE_LOW) {
    digitalWrite(PUMP_PIN, turnOn ? LOW : HIGH);
  } else {
    digitalWrite(PUMP_PIN, turnOn ? HIGH : LOW);
  }
}

// ==========================================
// HELPER: Manual JSON Integer Parser
// ==========================================
int parseJsonInt(const String& json, const String& key, int defaultVal) {
  String searchKey = "\"" + key + "\":";
  int pos = json.indexOf(searchKey);
  if (pos == -1) return defaultVal;
  pos += searchKey.length();
  while (pos < json.length() && (json[pos] == ' ' || json[pos] == '\t')) pos++;
  int endPos = pos;
  while (endPos < json.length() && (isDigit(json[endPos]) || json[endPos] == '-')) endPos++;
  if (pos == endPos) return defaultVal;
  return json.substring(pos, endPos).toInt();
}

// ==========================================
// HELPER: Connect to Wi-Fi
// ==========================================
void setupWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.setAutoReconnect(true);
  WiFi.persistent(true);

  if (WiFi.status() == WL_CONNECTED) {
    return;
  }

  Serial.println();
  Serial.println("==============================");
  Serial.print("[WIFI] Connecting to: ");
  Serial.println(WIFI_SSID);
  Serial.println("==============================");

  WiFi.begin(WIFI_SSID, WIFI_PASS);

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(400);
    Serial.print(".");
    attempts++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println();
    Serial.println("[WIFI] Connected Successfully!");
    Serial.print("[WIFI] IP Address: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println();
    Serial.println("[WIFI] Auto-reconnect active in background.");
  }
}

// ==========================================
// HELPER: Stable Water Level Reading (0 - 100%)
// ==========================================
int readWaterLevelPercent() {
  const int numSamples = 20;
  int samples[numSamples];

  for (int i = 0; i < numSamples; i++) {
    samples[i] = analogRead(SENSOR_PIN);
    delay(2);
  }

  // Median filtering to eliminate noise and spikes
  for (int i = 0; i < numSamples - 1; i++) {
    for (int j = 0; j < numSamples - i - 1; j++) {
      if (samples[j] > samples[j + 1]) {
        int temp = samples[j];
        samples[j] = samples[j + 1];
        samples[j + 1] = temp;
      }
    }
  }

  long sum = 0;
  for (int i = 5; i < 15; i++) {
    sum += samples[i];
  }
  int cleanAdc = sum / 10;

  if (cleanAdc <= SENSOR_DRY_VAL) {
    return 0;
  }

  int percent = map(cleanAdc, SENSOR_DRY_VAL, SENSOR_WET_VAL, 0, 100);
  percent = constrain(percent, 0, 100);

  // Live Debug output in Serial Monitor every 1 second
  static unsigned long lastDebugPrint = 0;
  if (millis() - lastDebugPrint >= 1000) {
    lastDebugPrint = millis();
    Serial.println("------------------------------------------------------------------------------------------------");
    Serial.printf(">>> RAW ADC: %-4d | LEVEL: %-3d%% | CURRENT LIMITS: [Min: %d%% | Max: %d%%] | PUMP: %s <<<\n", 
                  cleanAdc, percent, minThreshold, maxThreshold, isPumpOn ? "ON" : "OFF");
    Serial.println("------------------------------------------------------------------------------------------------");
  }

  return percent;
}

// ==========================================
// HELPER: Control Water Pump Output (GPIO 4)
// ==========================================
void setPumpState(bool turnOn, const char* reason) {
  if (turnOn && !isPumpOn) {
    writePumpPin(true);
    isPumpOn = true;
    pumpStartTime = millis();
    failsafeTriggered = false;
    Serial.println("--------------------------------------------------");
    Serial.printf("[PUMP ACTION] >>> PUMP ACTIVATED (ON) <<< Reason: %s\n", reason);
    Serial.println("--------------------------------------------------");
  } 
  else if (!turnOn && isPumpOn) {
    writePumpPin(false);
    isPumpOn = false;
    Serial.println("--------------------------------------------------");
    Serial.printf("[PUMP ACTION] >>> PUMP DEACTIVATED (OFF) <<< Reason: %s\n", reason);
    Serial.println("--------------------------------------------------");
  }
  
  if (!turnOn) {
    writePumpPin(false);
    isPumpOn = false;
  }
}

// ==========================================
// HTTP GET: Fetch Thresholds from Database
// ==========================================
void fetchConfigFromPHP() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  WiFiClientSecure secureClient;
  WiFiClient regularClient;

  String url = String(SETTINGS_API_URL);
  if (url.startsWith("https://")) {
    secureClient.setInsecure();
    http.begin(secureClient, url);
  } else {
    http.begin(regularClient, url);
  }

  http.setTimeout(8000);
  http.addHeader("ngrok-skip-browser-warning", "true");
  http.addHeader("User-Agent", "ESP32-AquaSync");

  int httpCode = http.GET();

  if (httpCode == 200) {
    String payload = http.getString();

    // 1. Fetch Thresholds from Database payload
    if (payload.indexOf("\"min_threshold\"") != -1 && payload.indexOf("\"max_threshold\"") != -1) {
      int dbMin = parseJsonInt(payload, "min_threshold", minThreshold);
      int dbMax = parseJsonInt(payload, "max_threshold", maxThreshold);

      if (dbMin < dbMax && dbMin >= 5 && dbMax <= 100) {
        if (dbMin != minThreshold || dbMax != maxThreshold) {
          minThreshold = dbMin;
          maxThreshold = dbMax;
          preferences.putInt("minThresh", minThreshold);
          preferences.putInt("maxThresh", maxThreshold);
          Serial.printf("[DATABASE CONFIG] Updated Thresholds -> Min: %d%% | Max: %d%%\n", minThreshold, maxThreshold);
        }
      }
    }
  }
  http.end();
}

// ==========================================
// HTTP POST: Send Telemetry to Database (INSERT into water_levels)
// ==========================================
void sendTelemetryToPHP(int currentLevel) {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  WiFiClientSecure secureClient;
  WiFiClient regularClient;

  String url = String(SENSOR_API_URL);
  if (url.startsWith("https://")) {
    secureClient.setInsecure();
    http.begin(secureClient, url);
  } else {
    http.begin(regularClient, url);
  }

  http.setTimeout(8000);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("ngrok-skip-browser-warning", "true");
  http.addHeader("User-Agent", "ESP32-AquaSync");

  // Determine pump action and short concise description for water_levels table
  String action = isPumpOn ? "ON" : "OFF";
  String description = "";

  if (currentLevel >= maxThreshold) {
    description = "Target level achieved";
  } else if (currentLevel <= minThreshold) {
    description = "Water level dropped";
  } else if (isPumpOn) {
    description = "Water level raised";
  } else {
    description = "Normal water level";
  }

  // Ensure description fits within varchar(100) limit of the database
  if (description.length() > 98) {
    description = description.substring(0, 98);
  }

  // JSON payload including currently active min & max thresholds
  String jsonData = 
    "{"
    "\"water_level\":" + String(currentLevel) + ","
    "\"min_threshold\":" + String(minThreshold) + ","
    "\"max_threshold\":" + String(maxThreshold) + ","
    "\"action\":\"" + action + "\","
    "\"description\":\"" + description + "\""
    "}";

  int httpCode = http.POST(jsonData);
  if (httpCode > 0) {
    Serial.printf("[DATABASE SYNC] Level: %d%% | Following Thresholds: [Min: %d%% | Max: %d%%] | Action: %s | HTTP: %d\n", 
                  currentLevel, minThreshold, maxThreshold, action.c_str(), httpCode);

    // Extract updated thresholds from server response
    if (httpCode == 200) {
      String responsePayload = http.getString();
      if (responsePayload.indexOf("\"min_threshold\"") != -1 && responsePayload.indexOf("\"max_threshold\"") != -1) {
        int dbMin = parseJsonInt(responsePayload, "min_threshold", minThreshold);
        int dbMax = parseJsonInt(responsePayload, "max_threshold", maxThreshold);
        if (dbMin < dbMax && dbMin >= 5 && dbMax <= 100) {
          if (dbMin != minThreshold || dbMax != maxThreshold) {
            minThreshold = dbMin;
            maxThreshold = dbMax;
            preferences.putInt("minThresh", minThreshold);
            preferences.putInt("maxThresh", maxThreshold);
            Serial.printf("[THRESHOLD SYNCED] Min: %d%% | Max: %d%%\n", minThreshold, maxThreshold);
          }
        }
      }
    }
  } else {
    Serial.printf("[DATABASE SYNC ERROR] HTTP POST Failed: %s\n", http.errorToString(httpCode).c_str());
  }
  http.end();
}

// ==========================================
// SETUP
// ==========================================
void setup() {
  Serial.begin(115200);
  delay(1000);

  pinMode(PUMP_PIN, OUTPUT);
  writePumpPin(false);
  isPumpOn = false;

  preferences.begin("aquasync-cfg", false);
  minThreshold = preferences.getInt("minThresh", DEFAULT_MIN_THRESHOLD);
  maxThreshold = preferences.getInt("maxThresh", DEFAULT_MAX_THRESHOLD);

  Serial.println();
  Serial.println("==================================================");
  Serial.println("     AQUASYNC WATER MANAGEMENT CONTROLLER         ");
  Serial.printf ("     Initial Thresholds: Min: %d%% | Max: %d%%     \n", minThreshold, maxThreshold);
  Serial.printf ("     Relay Type: %s                              \n", RELAY_ACTIVE_LOW ? "ACTIVE LOW (Standard Module)" : "ACTIVE HIGH");
  Serial.println("==================================================");

  setupWiFi();

  if (WiFi.status() == WL_CONNECTED) {
    fetchConfigFromPHP();
  }
}

// ==========================================
// MAIN LOOP: HYSTERESIS AUTO-REFILL
// ==========================================
void loop() {
  int currentLevel = readWaterLevelPercent();

  // 1. Failsafe (10 minutes max continuous running)
  if (isPumpOn && (millis() - pumpStartTime >= MAX_PUMP_RUNTIME_MS)) {
    setPumpState(false, "MAX RUNTIME FAILSAFE REACHED");
    failsafeTriggered = true;
    Serial.println("[ALERT] Failsafe triggered: Pump stopped.");
  }

  // 2. Pump Control Logic (Auto Hysteresis)
  if (!failsafeTriggered) {
    // RULE 1: Stop pump when maximum threshold is reached
    if (currentLevel >= maxThreshold) {
      setPumpState(false, "MAXIMUM THRESHOLD REACHED (TANK FULL)");
    }
    // RULE 2: Start pump when water drops to or below minimum threshold
    else if (currentLevel <= minThreshold) {
      if (!isPumpOn) {
        setPumpState(true, "WATER DROPPED TO MINIMUM THRESHOLD");
      }
    }
  }

  // 3. Periodic Database Telemetry Sync
  if (millis() - lastSyncTime >= SYNC_INTERVAL_MS) {
    lastSyncTime = millis();

    if (WiFi.status() == WL_CONNECTED) {
      sendTelemetryToPHP(currentLevel);
    }
  }

  delay(100);
}
