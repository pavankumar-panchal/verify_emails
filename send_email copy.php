<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';

// Database connection
$conn = new mysqli("localhost", "root", "", "email_id");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$senderName = $_POST['sender_name'];
$subject = $_POST['subject'];
$body = $_POST['body'];
$mailServer = $_POST['mail_server'];

// Define server configurations
$serverConfigs = [
    1 => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'name' => 'SMTP1',
        'email' => 'panchalpavan7090@gmail.com',
        'password' => 'fvof dhjk iekz lvny'
    ],
    2 => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'name' => 'SMTP2',
        'email' => 'panchalpavan7090@gmail.com',
        'password' => 'fvof dhjk iekz lvny'
    ],
    3 => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'name' => 'SMTP3',
        'email' => 'panchalpavan7090@gmail.com',
        'password' => 'fvof dhjk iekz lvny'
    ],
    4 => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'name' => 'SMTP4',
        'email' => 'panchalpavan7090@gmail.com',
        'password' => 'fvof dhjk iekz lvny'
    ],
    5 => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'name' => 'SMTP5',
        'email' => 'panchalpavan7090@gmail.com',
        'password' => 'fvof dhjk iekz lvny'
    ]
];

$selectedServer = $serverConfigs[$mailServer];
$senderEmail = $selectedServer['email'];
$senderPassword = $selectedServer['password'];

// Check email count sent today
$today = date('Y-m-d');
$checkSql = "SELECT COUNT(*) as count FROM email_logs WHERE sender_email = ? AND DATE(sent_at) = ?";
$stmt = $conn->prepare($checkSql);
$stmt->bind_param("ss", $senderEmail, $today);
$stmt->execute();
$resultCheck = $stmt->get_result()->fetch_assoc();
$sentToday = (int)$resultCheck['count'];

if ($sentToday >= 500) {
    die("<p style='color:red;'>Limit reached: This SMTP server has already sent 500 emails today.</p>");
}

// Handle file uploads
$attachments = [];
if (!empty($_FILES['attachments'])) {
    foreach ($_FILES['attachments']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
            $file_path = 'uploads/' . basename($_FILES['attachments']['name'][$key]);
            if (move_uploaded_file($tmp_name, $file_path)) {
                $attachments[] = $file_path;
            }
        }
    }
}

$sql = "SELECT raw_emailid FROM emails WHERE domain_status = 1 LIMIT ? OFFSET ?";
$remaining = 500 - $sentToday;
$offset = 0;
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $remaining, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Update smtp_servers table usage stats
// $updateSmtpQuery = "UPDATE smtp_servers SET usage_count = usage_count + ?, last_used = NOW() WHERE id = ?";
// $stmt = $conn->prepare($updateSmtpQuery);
// $stmt->bind_param("ii", $sentCount, $selectedServer['id']);
// $stmt->execute();


if ($result->num_rows > 0) {
    $logFile = 'email_log_' . date('Y-m-d_H-i-s') . '.txt';
    $logContent = "Bulk email sending started at " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "Using server: " . $selectedServer['name'] . " (" . $selectedServer['host'] . ")\n";
    $logContent .= "Sender email: " . $senderEmail . "\n\n";

    $sentCount = 0;
    $failedCount = 0;

    while ($row = $result->fetch_assoc()) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $selectedServer['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $senderEmail;
            $mail->Password = $senderPassword;
            $mail->SMTPSecure = $selectedServer['encryption'];
            $mail->Port = $selectedServer['port'];
            $mail->SMTPDebug = SMTP::DEBUG_OFF;

            $mail->setFrom($senderEmail, $senderName);
            $mail->addAddress($row['raw_emailid']);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;

            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment);
            }

            if ($mail->send()) {
                $logContent .= "SUCCESS: Sent to " . $row['raw_emailid'] . "\n";
                $sentCount++;

                // Log to DB
                $logStmt = $conn->prepare("INSERT INTO email_logs (sender_email, recipient_email, sent_at) VALUES (?, ?, NOW())");
                $logStmt->bind_param("ss", $senderEmail, $row['raw_emailid']);
                $logStmt->execute();
            } else {
                $logContent .= "FAILED: " . $row['raw_emailid'] . " - " . $mail->ErrorInfo . "\n";
                $failedCount++;
            }

            if ($sentCount % 30 === 0) {
                sleep(60);
            }
        } catch (Exception $e) {
            $logContent .= "ERROR: " . $row['raw_emailid'] . " - " . $e->getMessage() . "\n";
            $failedCount++;
        }
    }

    $logContent .= "\nSending completed at " . date('Y-m-d H:i:s') . "\n";
    $logContent .= "Total sent: $sentCount\n";
    $logContent .= "Total failed: $failedCount\n";

    if (!file_exists('logs')) {
        mkdir('logs', 0777, true);
    }

    file_put_contents('logs/' . $logFile, $logContent);

    foreach ($attachments as $attachment) {
        if (file_exists($attachment)) {
            unlink($attachment);
        }
    }

    echo "<div class='container mx-auto px-4 max-w-4xl mt-20'>";
    echo "<div class='email-container p-6'>";
    echo "<h2 class='text-xl font-medium mb-4'>Email Sending Results</h2>";
    echo "<p><strong>Server used:</strong> " . $selectedServer['name'] . "</p>";
    echo "<p><strong>Sender email:</strong> " . $senderEmail . "</p>";
    echo "<p><strong>Total emails sent successfully:</strong> $sentCount</p>";
    echo "<p><strong>Total emails failed:</strong> $failedCount</p>";
    echo "<p><strong>Detailed log saved to:</strong> logs/$logFile</p>";
    echo "<a href='send_form.php' class='text-blue-600 hover:text-blue-800 mt-4 inline-block'>Back to Send Form</a>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div class='container mx-auto px-4 max-w-4xl mt-20'>";
    echo "<div class='email-container p-6'>";
    echo "<p class='text-red-500'>No valid emails found in the database.</p>";
    echo "<a href='send_form.php' class='text-blue-600 hover:text-blue-800 mt-4 inline-block'>Back to Send Form</a>";
    echo "</div>";
    echo "</div>";
}

$conn->close();
?>
