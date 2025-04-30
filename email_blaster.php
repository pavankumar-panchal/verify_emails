<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Database configuration
$db = new mysqli("localhost", "root", "", "email_id");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$db->set_charset("utf8mb4");

// Get campaign ID from command line
$campaign_id = isset($argv[1]) ? intval($argv[1]) : die("No campaign ID specified");

// Main processing loop
// Main processing loop
while (true) {
    try {
        // Check if campaign is still active
        $result = $db->query("SELECT status, total_emails, sent_emails FROM campaign_status WHERE campaign_id = $campaign_id");
        if ($result->num_rows === 0) {
            logMessage("Campaign not found. Exiting.");
            break;
        }

        $campaign_data = $result->fetch_assoc();
        $status = $campaign_data['status'];

        if ($status !== 'running') {
            logMessage("Campaign status changed to $status. Exiting.");
            break;
        }

        // Check if all emails are sent
        if ($campaign_data['sent_emails'] >= $campaign_data['total_emails']) {
            $db->query("UPDATE campaign_status 
                      SET status = 'completed', 
                          pending_emails = 0,
                          end_time = NOW() 
                      WHERE campaign_id = $campaign_id");
            logMessage("All emails sent. Campaign completed.");
            break;
        }

        // Process emails in batches
        processEmailBatch($db, $campaign_id);

        sleep(5); // Short delay between batches

    } catch (Exception $e) {
        logMessage("Error: " . $e->getMessage());
        sleep(60); // Wait longer on error
    }
}

function processEmailBatch($db, $campaign_id)
{
    // Get campaign details
    $result = $db->query("SELECT mail_subject, mail_body FROM campaign_master WHERE campaign_id = $campaign_id");
    $campaign = $result->fetch_assoc();

    // Get active SMTP server
    $smtp = getNextSmtpServer($db);
    if (!$smtp) {
        logMessage("No active SMTP servers available");
        return;
    }

    // Check sending limits
    $limits_ok = checkSendingLimits($db, $smtp['id']);
    if (!$limits_ok) {
        return;
    }

    // Get next batch of emails to send
    $emails = getNextEmailBatch($db, $campaign_id, 10); // Process 10 at a time

    if (empty($emails)) {
        // No more emails to send - mark as completed
        $db->query("UPDATE campaign_status 
                   SET status = 'completed', 
                       pending_emails = 0,
                       end_time = NOW() 
                   WHERE campaign_id = $campaign_id");
        logMessage("No more emails to send. Campaign completed.");
        return;
    }

    foreach ($emails as $email) {
        try {
            // Send email
            sendEmail($smtp, $email['raw_emailid'], $campaign['mail_subject'], $campaign['mail_body']);

            // Record successful delivery
            recordDelivery($db, $smtp['id'], $email['id'], $campaign_id, $email['raw_emailid'], 'success');

            // Update campaign status
            $db->query("UPDATE campaign_status 
                       SET sent_emails = sent_emails + 1, 
                           pending_emails = pending_emails - 1 
                       WHERE campaign_id = $campaign_id");

            logMessage("Sent to {$email['raw_emailid']}");

            usleep(500000); // 0.5 second delay between emails

        } catch (Exception $e) {
            // Record failed delivery
            recordDelivery($db, $smtp['id'], $email['id'], $campaign_id, $email['raw_emailid'], 'failed', $e->getMessage());

            // Update campaign status for failed email
            $db->query("UPDATE campaign_status 
                       SET failed_emails = failed_emails + 1, 
                           pending_emails = pending_emails - 1 
                       WHERE campaign_id = $campaign_id");

            logMessage("Failed to send to {$email['raw_emailid']}: " . $e->getMessage());
        }
    }
}

function getNextSmtpServer($db)
{
    static $currentSmtpId = null;

    $result = $db->query("SELECT * FROM smtp_servers WHERE is_active = 1 ORDER BY id");
    $servers = $result->fetch_all(MYSQLI_ASSOC);

    if (empty($servers))
        return null;

    // Round-robin selection
    if ($currentSmtpId === null) {
        $currentSmtpId = $servers[0]['id'];
        return $servers[0];
    }

    $nextIndex = null;
    foreach ($servers as $index => $server) {
        if ($server['id'] > $currentSmtpId) {
            $nextIndex = $index;
            break;
        }
    }

    if ($nextIndex === null)
        $nextIndex = 0;

    $currentSmtpId = $servers[$nextIndex]['id'];
    return $servers[$nextIndex];
}

function checkSendingLimits($db, $smtpId)
{
    // Check daily limit
    $result = $db->query("SELECT COUNT(*) as count FROM mail_blaster 
                         WHERE smtpid = $smtpId AND delivery_date = CURDATE()");
    $dailySent = $result->fetch_assoc()['count'];

    $result = $db->query("SELECT daily_limit FROM smtp_servers WHERE id = $smtpId");
    $dailyLimit = $result->fetch_assoc()['daily_limit'];

    if ($dailySent >= $dailyLimit) {
        logMessage("Daily limit reached for SMTP $smtpId ($dailySent/$dailyLimit)");
        return false;
    }

    // Check hourly limit
    $result = $db->query("SELECT COUNT(*) as count FROM mail_blaster 
                         WHERE smtpid = $smtpId 
                         AND delivery_date = CURDATE() 
                         AND HOUR(delivery_time) = HOUR(CURRENT_TIME())");
    $hourlySent = $result->fetch_assoc()['count'];

    $result = $db->query("SELECT hourly_limit FROM smtp_servers WHERE id = $smtpId");
    $hourlyLimit = $result->fetch_assoc()['hourly_limit'];

    if ($hourlySent >= $hourlyLimit) {
        logMessage("Hourly limit reached for SMTP $smtpId ($hourlySent/$hourlyLimit)");
        return false;
    }

    return true;
}

function getNextEmailBatch($db, $campaign_id, $limit)
{
    $result = $db->query("
        SELECT e.id, e.raw_emailid
        FROM emails e
        WHERE e.domain_status = 1
        AND NOT EXISTS (
            SELECT 1 FROM mail_blaster mb 
            WHERE mb.to_mail = e.raw_emailid 
            AND mb.campaign_id = $campaign_id
        )
        LIMIT $limit
    ");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function sendEmail($smtp, $to_email, $subject, $body)
{
    $mail = new PHPMailer(true);

    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host = $smtp['host'];
    $mail->Port = $smtp['port'];
    $mail->SMTPAuth = true;
    $mail->Username = $smtp['email'];
    $mail->Password = $smtp['password'];

    if ($smtp['encryption'] === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($smtp['encryption'] === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }

    // Email Content
    $mail->setFrom($smtp['email']);
    $mail->addAddress($to_email);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->isHTML(true);

    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo);
    }
}

function recordDelivery($db, $smtpId, $emailId, $campaignId, $to_email, $status, $error = null)
{
    // Record in mail_blaster table
    $db->query("
        INSERT INTO mail_blaster 
        (campaign_id, to_mail, smtpid, delivery_date, delivery_time, status, error_message)
        VALUES ($campaignId, '$to_email', $smtpId, CURDATE(), CURTIME(), '$status', " .
        ($error ? "'$error'" : "NULL") . ")
    ");

    // Record in sending_logs table
    $db->query("
        INSERT INTO sending_logs 
        (campaign_id, email_id, smtp_id, status, sent_at, error_message)
        VALUES ($campaignId, $emailId, $smtpId, '$status', NOW(), " .
        ($error ? "'$error'" : "NULL") . ")
    ");
}

function logMessage($message)
{
    $log = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    file_put_contents("logs/campaign_{$GLOBALS['campaign_id']}.log", $log, FILE_APPEND);
    echo $log;
}

$db->close();