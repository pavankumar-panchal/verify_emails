<?php

$servername = "127.0.0.1";  // Change from "localhost" to "127.0.0.1"
$username = "root";
$password = "";
$dbname = "email_id";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully";
}

?>
