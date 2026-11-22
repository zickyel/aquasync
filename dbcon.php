<?php
if (!headers_sent()) {
    header('Access-Control-Allow-Origin: *');
}

// variables na mag hahandle ng mga data na need natin
$hostname = "localhost";
$username = "root";
$password = "";
$dbname   = "aquasync";

// variable na maghahandle ng database connection
$conn = new mysqli($hostname, $username, $password, $dbname);

// if condition to check if the connection is successfull
if ($conn && !$conn->connect_error) {
    $db_connected = true;
    // echo "Connected Successfully!";
} else {
    $db_connected = false;
    echo "Connection Error: " . ($conn ? $conn->connect_error : "Cannot connect to database");
}
?>
