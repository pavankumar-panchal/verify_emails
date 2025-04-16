<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

// Clean JSON output
ob_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Database connection
$conn = new mysqli("localhost", "root", "", "email_id");
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Validate and sanitize input
$senderName = filter_var($_POST['sender_name'] ?? 'Bulk Email Sender', FILTER_SANITIZE_STRING);
$subject = filter_var($_POST['subject'] ?? 'No Subject', FILTER_SANITIZE_STRING);
$body = $_POST['body'] ?? ''; // Allow HTML
$mailServer = filter_var($_POST['mail_server'] ?? 1, FILTER_VALIDATE_INT);

if (!$mailServer) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid SMTP server selection']);
    exit;
}

// Get SMTP server configuration
$stmt = $conn->prepare("SELECT * FROM smtp_servers WHERE id = ? AND is_active = TRUE");
$stmt->bind_param("i", $mailServer);
$stmt->execute();
$result = $stmt->get_result();
$selectedServer = $result->fetch_assoc();

if (!$selectedServer) {
    echo json_encode(['status' => 'error', 'message' => 'Selected SMTP server not found or inactive']);
    exit;
}

// Check daily limit
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT usage_count FROM smtp_usage WHERE smtp_server_id = ? AND usage_date = ?");
$stmt->bind_param("is", $selectedServer['id'], $today);
$stmt->execute();
$result = $stmt->get_result();
$usage = $result->fetch_assoc();
$sentToday = $usage['usage_count'] ?? 0;

if ($sentToday >= $selectedServer['daily_limit']) {
    echo json_encode(['status' => 'error', 'message' => 'This SMTP server has reached its daily limit']);
    exit;
}

// Handle file uploads
$attachments = [];
if (!empty($_FILES['attachments'])) {
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
            $file_name = basename($_FILES['attachments']['name'][$key]);
            $file_path = 'uploads/' . uniqid() . '_' . $file_name;
            if (move_uploaded_file($tmp_name, $file_path)) {
                $attachments[] = $file_path;
            }
        }
    }
}

// Get recipients
$remainingQuota = $selectedServer['daily_limit'] - $sentToday;
$stmt = $conn->prepare("SELECT raw_emailid FROM emails WHERE domain_status = 1 LIMIT ?");
$stmt->bind_param("i", $remainingQuota);
$stmt->execute();
$result = $stmt->get_result();

// Prepare log
$logFile = 'email_log_' . date('Y-m-d_H-i-s') . '.txt';
$logContent = "Bulk email sending started at " . date('Y-m-d H:i:s') . "\n";

// Email sending process
$sentCount = 0;
$failedCount = 0;
$failedRecipients = [];
$successRecipients = [];

while ($row = $result->fetch_assoc()) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $selectedServer['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $selectedServer['email'];
        $mail->Password = $selectedServer['password'];
        $mail->SMTPSecure = $selectedServer['encryption'];
        $mail->Port = $selectedServer['port'];
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->Timeout = 30;

        $mail->setFrom($selectedServer['email'], $senderName);
        $mail->addAddress($row['raw_emailid']);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        foreach ($attachments as $attachment) {
            $mail->addAttachment($attachment);
        }

        if ($mail->send()) {
            $sentCount++;
            $successRecipients[] = $row['raw_emailid'];

            // Log to database
            $logStmt = $conn->prepare("INSERT INTO email_logs (smtp_server_id, sender_email, recipient_email, subject, status) VALUES (?, ?, ?, ?, 'sent')");
            $logStmt->bind_param("isss", $selectedServer['id'], $selectedServer['email'], $row['raw_emailid'], $subject);
            $logStmt->execute();

            // Update usage count
            if ($sentToday == 0 && $sentCount == 1) {
                $updateStmt = $conn->prepare("INSERT INTO smtp_usage (smtp_server_id, usage_date, usage_count) VALUES (?, ?, 1)");
            } else {
                $updateStmt = $conn->prepare("UPDATE smtp_usage SET usage_count = usage_count + 1 WHERE smtp_server_id = ? AND usage_date = ?");
            }
            $updateStmt->bind_param("is", $selectedServer['id'], $today);
            $updateStmt->execute();
        } else {
            $failedCount++;
            $failedRecipients[] = $row['raw_emailid'];
        }
    } catch (Exception $e) {
        $failedCount++;
        $failedRecipients[] = $row['raw_emailid'];
    }
}

// Finalize log
$logContent .= "Sending completed at " . date('Y-m-d H:i:s') . "\n";
$logContent .= "Total sent: $sentCount\n";
$logContent .= "Total failed: $failedCount\n";
file_put_contents('logs/' . $logFile, $logContent);

// Clean up attachments
foreach ($attachments as $attachment) {
    if (file_exists($attachment)) {
        unlink($attachment);
    }
}

// Final response
echo json_encode([
    'status' => 'success',
    'sent' => $sentCount,
    'failed' => $failedCount,
    'success_recipients' => $successRecipients,
    'failed_recipients' => $failedRecipients,
    'log_file' => $logFile
]);

$conn->close();
ob_end_flush();
?>
