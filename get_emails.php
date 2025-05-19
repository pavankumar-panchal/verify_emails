<?php
// get_emails.php
// require_once 'db.php'; // Your DB connection file
$db = new mysqli("localhost", "root", "", "email_id");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$account_id = intval($_GET['account_id'] ?? 0);

// Get SMTP account details
$stmt = $db->prepare("SELECT * FROM smtp_servers WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $account_id);
$stmt->execute();
$smtp = $stmt->get_result()->fetch_assoc();

if (!$smtp) {
    header('Content-Type: application/json');
    die(json_encode(['error' => 'Invalid account']));
}

// Fetch replies (use the fetchReplies function from your original code)
$replies = fetchReplies($smtp);

header('Content-Type: application/json');
echo json_encode($replies);
?>