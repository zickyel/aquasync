<?php
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
}

$hostname = "localhost";
$username = "root";
$password = "";
$dbname   = "aquasync";


$conn = new mysqli($hostname, $username, $password, $dbname);

if ($conn && !$conn->connect_error) {
    $db_connected = true;
    // echo "Connected Successfully!";
} else {
    $db_connected = false;
    echo "Connection Error: " . ($conn ? $conn->connect_error : "Cannot connect to database");
}
?>
