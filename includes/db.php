<?php
$host = '127.0.0.1';        // Use IP instead of localhost to avoid socket issues
$username = 'root';         // Replace if your DB has a different user
$password = '';             // Add your MySQL password here
$database = 'email_id';     // Replace with your database name
$port = 3306;               // Default MySQL port

// Optional: Only use socket if necessary
$socket = '/opt/lampp/var/mysql/mysql.sock'; // Default socket path in XAMPP on Linux

$conn = new mysqli($host, $username, $password, $database, $port, $socket);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
