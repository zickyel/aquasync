<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$panel_url = $is_logged_in ? 'index.php' : 'login.php';
$panel_label = $is_logged_in ? 'Go to Dashboard' : 'Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaSync — Smart Automatic Water Refilling &amp; Monitoring System</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230284c7'><path d='M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'/></svg>">
    <link rel="stylesheet" href="bootstrap5/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --accent: #38bdf8;
            --bg-soft: #f0f9ff;
            --text-heading: #0f172a;
            --text-body: #475569;
            --border-subtle: #e2e8f0;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-body);
            background-color: #ffffff;
            margin: 0;
        }

        /* Navbar */
        .site-navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-subtle);
        }

        .brand-logo-box {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.25);
        }

        .brand-logo-box svg {
            width: 22px;
            height: 22px;
            fill: #ffffff;
        }

        .nav-link-clean {
            color: var(--text-body);
            font-weight: 600;
            font-size: 0.92rem;
            text-decoration: none;
            transition: color 0.2s;
        }

        .nav-link-clean:hover {
            color: var(--primary);
        }

        .btn-cta-primary {
            background: linear-gradient(135deg, #0ea5e9, #0284c7);
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.24);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-cta-primary:hover {
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(2, 132, 199, 0.32);
        }

        .btn-cta-outline {
            background: #ffffff;
            color: var(--text-heading);
            border: 1px solid var(--border-subtle);
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .btn-cta-outline:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--bg-soft);
        }

        /* Hero Section */
        .hero-section {
            background: radial-gradient(circle at top right, rgba(56, 189, 248, 0.16), transparent 45%),
                        linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            padding: 72px 0 80px;
            border-bottom: 1px solid var(--border-subtle);
        }

        .hero-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: #e0f2fe;
            color: var(--primary-dark);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .hero-pill .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0284c7;
        }

        .hero-heading {
            font-size: clamp(2.1rem, 4vw, 3.1rem);
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin-bottom: 18px;
        }

        .hero-heading span {
            color: var(--primary);
        }

        .hero-lead {
            font-size: 1.06rem;
            line-height: 1.7;
            color: var(--text-body);
            max-width: 540px;
            margin-bottom: 28px;
        }

        /* Mockup Telemetry Card */
        .telemetry-preview-card {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border-radius: 24px;
            padding: 28px;
            color: #ffffff;
            box-shadow: 0 24px 48px -12px rgba(2, 132, 199, 0.35);
            position: relative;
            overflow: hidden;
        }

        .preview-mini-card {
            background: rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 16px;
            padding: 16px;
        }

        .preview-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 700;
        }

        .preview-value {
            font-size: 1.55rem;
            font-weight: 800;
            color: #ffffff;
        }

        /* Section Headings */
        .section-kicker {
            color: var(--primary);
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .section-heading {
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }

        /* Feature Cards */
        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 18px;
            padding: 26px;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-4px);
            border-color: #bae6fd;
            box-shadow: 0 16px 32px -12px rgba(2, 132, 199, 0.18);
        }

        .feature-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: #e0f2fe;
            color: var(--primary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .feature-title {
            font-size: 1.08rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 8px;
        }

        /* How It Works */
        .workflow-section {
            background: #f8fafc;
            border-top: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
        }

        .step-card {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 18px;
            padding: 24px;
            height: 100%;
        }

        .step-number {
            display: inline-block;
            font-size: 0.78rem;
            font-weight: 800;
            color: var(--primary);
            background: #e0f2fe;
            padding: 4px 10px;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        /* Tech Stack Pills */
        .tech-badge {
            background: #f8fafc;
            border: 1px solid var(--border-subtle);
            border-radius: 14px;
            padding: 16px 20px;
            font-weight: 700;
            color: var(--text-heading);
            font-size: 0.92rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        /* Footer */
        .site-footer {
            background: #0f172a;
            color: #cbd5e1;
            padding: 48px 0 32px;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="site-navbar py-3">
        <div class="container d-flex align-items-center justify-content-between gap-3">
            <a href="landing.php" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="brand-logo-box">
                    <svg viewBox="0 0 24 24"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                </span>
                <div>
                    <div class="fw-bold text-dark lh-1" style="font-size: 1.15rem;">AquaSync</div>
                    <small class="text-muted" style="font-size: 0.72rem;">Smart Auto Refilling System</small>
                </div>
            </a>

            <div class="d-none d-md-flex align-items-center gap-4">
                <a href="#features" class="nav-link-clean">Features</a>
                <a href="#how-it-works" class="nav-link-clean">How It Works</a>
                <a href="#architecture" class="nav-link-clean">Architecture</a>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center justify-content-center">
                <div class="col-12 col-lg-9 mx-auto text-center">
                    <div class="hero-pill">
                        <span class="pulse-dot"></span>
                        ESP32 IoT Water Station Telemetry
                    </div>
                    <h1 class="hero-heading">
                        Automated Water <span>Refilling &amp; Monitoring</span> Made Effortless
                    </h1>
                    <p class="hero-lead mx-auto">
                        AquaSync continuously tracks reservoir water levels with an ESP32 water level sensor, automatically controls the refill pump using hysteresis thresholds, and delivers real-time analytics and audit logs.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="#how-it-works" class="btn-cta-outline px-4 py-3">
                            See How It Works
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FEATURES SECTION -->
    <section id="features" class="py-5 my-4">
        <div class="container">
            <div class="text-center mx-auto mb-5" style="max-width: 640px;">
                <div class="section-kicker">Core Capabilities</div>
                <h2 class="section-heading">Built for Reliable Water Station Automation</h2>
                <p class="text-muted mb-0">Every hardware reading, relay switch, and threshold update is synchronized between the ESP32 controller and web dashboard.</p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"></path>
                            </svg>
                        </div>
                        <h5 class="feature-title">Real-Time Level Sensing</h5>
                        <p class="text-muted small mb-0">Ultrasonic sensor readings are transmitted by the ESP32 every 3 seconds and reflected live on the dashboard via AJAX polling.</p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                            </svg>
                        </div>
                        <h5 class="feature-title">Automatic Hysteresis Pump</h5>
                        <p class="text-muted small mb-0">Prevents rapid relay chatter by turning the pump ON at the minimum threshold (10%–60%) and cutting OFF at the maximum limit (80%–90%).</p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="20" x2="18" y2="10"></line>
                                <line x1="12" y1="20" x2="12" y2="4"></line>
                                <line x1="6" y1="20" x2="6" y2="14"></line>
                            </svg>
                        </div>
                        <h5 class="feature-title">24-Hour Analytics &amp; Reports</h5>
                        <p class="text-muted small mb-0">Visualize 24-hour water level curves and hourly refill distribution, export CSV archives, or print clean summary reports.</p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                        </div>
                        <h5 class="feature-title">Pump Logs &amp; Audit Trail</h5>
                        <p class="text-muted small mb-0">Every pump ON/OFF event is recorded with timestamps, water percentage, and event descriptions for full operational traceability.</p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="4" y1="21" x2="4" y2="14"></line>
                                <line x1="4" y1="10" x2="4" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12" y2="3"></line>
                                <line x1="20" y1="21" x2="20" y2="16"></line>
                                <line x1="20" y1="12" x2="20" y2="3"></line>
                            </svg>
                        </div>
                        <h5 class="feature-title">Remote Threshold Tuning</h5>
                        <p class="text-muted small mb-0">Adjust minimum and maximum refill thresholds directly from the Settings tab; the ESP32 fetches updated limits automatically.</p>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <h5 class="feature-title">Role-Based Access Control</h5>
                        <p class="text-muted small mb-0">Protected administrator panel with Master and Admin roles for managing station credentials and system permissions safely.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section id="how-it-works" class="workflow-section py-5">
        <div class="container py-3">
            <div class="text-center mx-auto mb-5" style="max-width: 620px;">
                <div class="section-kicker">Automation Cycle</div>
                <h2 class="section-heading">How AquaSync Works</h2>
                <p class="text-muted mb-0">From ultrasonic sensing in the tank to real-time visualization on your browser.</p>
            </div>

            <div class="row g-4">
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="step-card">
                        <span class="step-number">STEP 01</span>
                        <h5 class="feature-title">Measure Tank Level</h5>
                        <p class="text-muted small mb-0">The ultrasonic sensor measures distance to the water surface and computes the exact tank percentage.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="step-card">
                        <span class="step-number">STEP 02</span>
                        <h5 class="feature-title">Sync Thresholds</h5>
                        <p class="text-muted small mb-0">ESP32 fetches the configured Min and Max thresholds from the database API over HTTPS.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="step-card">
                        <span class="step-number">STEP 03</span>
                        <h5 class="feature-title">Auto Relay Control</h5>
                        <p class="text-muted small mb-0">If level drops to Min, the relay switches the water pump ON; once it reaches Max, it turns OFF.</p>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="step-card">
                        <span class="step-number">STEP 04</span>
                        <h5 class="feature-title">Log &amp; Visualize</h5>
                        <p class="text-muted small mb-0">Readings and pump actions are stored in MySQL and streamed to the Dashboard, Pump Logs, and Reports.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- SYSTEM ARCHITECTURE -->
    <section id="architecture" class="py-5 my-3">
        <div class="container">
            <div class="text-center mx-auto mb-4" style="max-width: 600px;">
                <div class="section-kicker">System Stack</div>
                <h2 class="section-heading">Hardware &amp; Software Architecture</h2>
            </div>

            <div class="row g-3 justify-content-center">
                <div class="col-6 col-md-4 col-lg-2"><div class="tech-badge">ESP32 Wi-Fi</div></div>
                <div class="col-6 col-md-4 col-lg-2"><div class="tech-badge">WATER LEVEL SENSOR</div></div>
                <div class="col-6 col-md-4 col-lg-2"><div class="tech-badge">MOSFET &amp; PUMP</div></div>
                <div class="col-6 col-md-4 col-lg-2"><div class="tech-badge">PHP REST API</div></div>
                <div class="col-6 col-md-4 col-lg-2"><div class="tech-badge">MySQL Database</div></div>
                <div class="col-6 col-md-4 col-lg-2"><div class="tech-badge">AJAX + Chart.js</div></div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="site-footer">
        <div class="container">
            <div class="row align-items-center justify-content-between g-4 pb-4 border-bottom border-secondary border-opacity-25">
                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="brand-logo-box" style="width: 34px; height: 34px;">
                            <svg viewBox="0 0 24 24" style="width: 18px; height: 18px;"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                        </span>
                        <span class="fw-bold text-white fs-5">AquaSync</span>
                    </div>
                    <p class="small mb-0 text-secondary">Automated Water Refilling &amp; Monitoring System powered by ESP32 and real-time web telemetry.</p>
                </div>
            </div>
            <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 small text-secondary">
                <span>&copy; <?= date('Y') ?> AquaSync Water Station System.</span>
                <span>Automated Refill Controller</span>
            </div>
        </div>
    </footer>

    <script src="bootstrap5/js/bootstrap.bundle.min.js"></script>
</body>
</html>
