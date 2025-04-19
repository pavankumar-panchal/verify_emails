<?php
// send_campaign.php
require_once 'vendor/autoload.php'; // Include PHPMailer if using composer

// Database connection
$conn = new mysqli("localhost", "root", "", "email_id");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get active SMTP servers
$smtpServers = $conn->query("SELECT * FROM smtp_servers WHERE is_active = 1 ORDER BY id");

// Get campaign details if specified
$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$campaign = $conn->query("SELECT * FROM campaign_master WHERE campaign_id = $campaign_id")->fetch_assoc();

if (!$campaign) {
    die("Invalid campaign specified");
}

// Get recipients (you'll need to implement your own recipient list logic)
$recipients = $conn->query("SELECT email FROM email_list WHERE is_active = 1"); // Example query

// Email sending function
function sendEmail($smtp, $recipient, $subject, $body) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['email'];
        $mail->Password   = $smtp['password'];
        $mail->SMTPSecure = $smtp['encryption'];
        $mail->Port       = $smtp['port'];
        
        // Recipients
        $mail->setFrom($smtp['email'], 'Your Company');
        $mail->addAddress($recipient);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Process sending
$sentCount = 0;
$failedCount = 0;
$currentServerIndex = 0;
$smtpServersArray = $smtpServers->fetch_all(MYSQLI_ASSOC);
$totalServers = count($smtpServersArray);

while ($recipient = $recipients->fetch_assoc()) {
    if ($totalServers == 0) break;
    
    // Get current SMTP server
    $currentServer = $smtpServersArray[$currentServerIndex];
    
    // Send email
    $success = sendEmail(
        $currentServer,
        $recipient['email'],
        $campaign['mail_subject'],
        $campaign['mail_body']
    );
    
    if ($success) {
        $sentCount++;
        // Rotate to next SMTP server
        $currentServerIndex = ($currentServerIndex + 1) % $totalServers;
    } else {
        $failedCount++;
    }
    
    // Respect server limits (optional)
    // sleep(1); // Add delay between emails if needed
}

// Display results
echo "<div class='p-4 mb-4 bg-green-100 text-green-800 rounded'>";
echo "Campaign sent successfully!<br>";
echo "Sent: $sentCount emails<br>";
echo "Failed: $failedCount emails";
echo "</div>";

// Close connection
$conn->close();
?>