<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "root", "", "email_id");
if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed']));
}

$campaign_id = intval($_GET['id']);
$sql = "SELECT description, mail_subject, mail_body FROM campaign_master WHERE campaign_id = $campaign_id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(['error' => 'Campaign not found']);
}

$conn->close();
?>