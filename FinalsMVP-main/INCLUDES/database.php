

<?php
$HOST = "localhost";
$USERNAME = "root";
$PASSWORD = "";
$DBNAME = "mis_borrowing_system"; // Make sure your database name in phpMyAdmin is exactly "db"

// Create connection
$mysql = new mysqli($HOST, $USERNAME, $PASSWORD, $DBNAME);

// Check connection
if ($mysql->connect_error) {
    // If connection fails, stop everything and show the error
    die("Connection failed: " . $mysql->connect_error);
}

date_default_timezone_set('Asia/Manila');      // Fixes PHP's date() function
$mysql->query("SET time_zone = '+08:00'");
// Connection is successful if we passed the check above.
// No need to echo "connected" here, or it will mess up your HTML layout.
?>
