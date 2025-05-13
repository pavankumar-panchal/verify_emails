<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

require 'db.php';

// Database configuration
$db = new mysqli("localhost", "root", "", "email_id");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$db->set_charset("utf8mb4");

date_default_timezone_set('Asia/Kolkata');

// Get campaign ID from command line
$campaign_id = isset($argv[1]) ? intval($argv[1]) : die("No campaign ID specified");

// Create PID file for process tracking
$pid_file = "/tmp/email_blaster_{$campaign_id}.pid";
file_put_contents($pid_file, getmypid());

// Register shutdown function to clean up PID file
register_shutdown_function(function () use ($pid_file) {
    if (file_exists($pid_file)) {
        unlink($pid_file);
    }
});

// Track SMTP usage windows
// $smtp_windows = [];

// Main processing loop
while (true) {
    try {
        // Check campaign status
        $result = $db->query("
            SELECT status, total_emails, sent_emails, pending_emails, failed_emails
            FROM campaign_status 
            WHERE campaign_id = $campaign_id
            FOR UPDATE");

        if ($result->num_rows === 0) {
            logMessage("Campaign not found. Exiting.");
            break;
        }

        $campaign_data = $result->fetch_assoc();
        $status = $campaign_data['status'];

        // Exit if campaign is paused, completed, or not running
        if ($status !== 'running') {
            logMessage("Campaign status is '$status'. Exiting process.");
            break;
        }

        // Check network connectivity
        if (!checkNetworkConnectivity()) {
            logMessage("Network connection unavailable. Waiting to retry...", 'WARNING');
            sleep(60);
            continue;
        }

        // Check remaining emails
        $remaining_result = $db->query("
            SELECT COUNT(*) as remaining FROM emails e
            WHERE e.domain_status = 1
            AND NOT EXISTS (
                SELECT 1 FROM mail_blaster mb 
                WHERE mb.to_mail = e.raw_emailid 
                AND mb.campaign_id = $campaign_id
                AND mb.status = 'success'
            )");
        $remaining_count = $remaining_result->fetch_assoc()['remaining'];

        if ($remaining_count == 0) {
            $db->query("UPDATE campaign_status SET status = 'completed', pending_emails = 0, end_time = NOW() 
                      WHERE campaign_id = $campaign_id");
            logMessage("All valid emails processed. Campaign completed.");
            break;
        }

        // Process emails
        $processed_count = processEmailBatch($db, $campaign_id);

        // Adjust sleep time based on processing results
        if ($processed_count > 0) {
            sleep(5); // Short sleep if we processed emails
        } else {
            // No emails processed, check if we should wait longer or exit
            $status_check = $db->query("SELECT status FROM campaign_status WHERE campaign_id = $campaign_id")->fetch_assoc();
            if ($status_check['status'] !== 'running') {
                break;
            }
            sleep(30); // Longer sleep if no emails processed
        }

    } catch (Exception $e) {
        logMessage("Error in main loop: " . $e->getMessage(), 'ERROR');
        sleep(60);
    }
}


function checkNetworkConnectivity()
{
    $connected = @fsockopen("8.8.8.8", 53, $errno, $errstr, 5);
    if ($connected) {
        fclose($connected);
        return true;
    }
    return false;
}

function processEmailBatch($db, $campaign_id, )
{
    $processed_count = 0;
    $max_retries = 3;

    try {
        $db->query("SET SESSION innodb_lock_wait_timeout = 10");
        $db->begin_transaction();

        // Get campaign details
        $campaign = $db->query("
            SELECT mail_subject, mail_body 
            FROM campaign_master 
            WHERE campaign_id = $campaign_id
        ")->fetch_assoc();

        if (!$campaign) {
            throw new Exception("Campaign not found");
        }

        // Get SMTP server
        $smtp = getNextSmtpServer($db);
        if (!$smtp) {
            logMessage("No available SMTP servers within limits");
            $db->commit();
            return 0;
        }

        // Get emails to process
        $emails = $db->query("
            SELECT e.id, e.raw_emailid
            FROM emails e
            LEFT JOIN mail_blaster mb ON mb.to_mail = e.raw_emailid AND mb.campaign_id = $campaign_id
            WHERE e.domain_status = 1
            AND (mb.id IS NULL OR (mb.status IN ('failed', 'pending') AND mb.attempt_count < $max_retries))
            AND NOT EXISTS (
                SELECT 1 FROM mail_blaster mb2 
                WHERE mb2.to_mail = e.raw_emailid 
                AND mb2.campaign_id = $campaign_id
                AND mb2.status = 'success'
            )
            ORDER BY mb.attempt_count ASC, mb.id ASC
            LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);

        if (empty($emails)) {
            $db->commit();
            return 0;
        }

        foreach ($emails as $email) {
            try {
                // Check campaign status
                $status = $db->query("
                    SELECT status FROM campaign_status 
                    WHERE campaign_id = $campaign_id
                ")->fetch_assoc()['status'];

                if ($status !== 'running') {
                    logMessage("Campaign paused or stopped during processing");
                    break;
                }

                // Check SMTP limits
                if (!checkSendingLimits($db, $smtp['id'])) {
                    logMessage("SMTP limits reached for server {$smtp['id']}");
                    break;
                }

                // Send email
                sendEmail($smtp, $email['raw_emailid'], $campaign['mail_subject'], $campaign['mail_body']);

                // Record delivery
                recordDelivery($db, $smtp['id'], $email['id'], $campaign_id, $email['raw_emailid'], 'success');

                // Update campaign status
                $db->query("
                    UPDATE campaign_status 
                    SET sent_emails = sent_emails + 1, 
                        pending_emails = GREATEST(0, pending_emails - 1) 
                    WHERE campaign_id = $campaign_id
                ");

                $processed_count++;
                usleep(300000); // Throttle emails (0.3 seconds)

            } catch (Exception $e) {
                recordDelivery($db, $smtp['id'], $email['id'], $campaign_id, $email['raw_emailid'], 'failed', $e->getMessage());

                $db->query("
                    UPDATE campaign_status 
                    SET failed_emails = failed_emails + 1, 
                        pending_emails = GREATEST(0, pending_emails - 1) 
                    WHERE campaign_id = $campaign_id
                ");

                logMessage("Failed to send to {$email['raw_emailid']}: " . $e->getMessage(), 'ERROR');

                // // If SMTP failed, mark it as potentially problematic
                // if (strpos($e->getMessage(), 'SMTP error') !== false) {
                //     $smtp_windows[$smtp['id']]['last_error'] = time();
                // }
            }
        }

        $db->commit();
        return $processed_count;

    } catch (Exception $e) {
        $db->rollback();
        logMessage("Error processing batch: " . $e->getMessage(), 'ERROR');
        return 0;
    }
}

function getNextSmtpServer($db, )
{
    $servers = $db->query("
        SELECT * FROM smtp_servers 
        WHERE is_active = 1
        ORDER BY RAND()
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($servers as $server) {
        // Check daily limit
        $daily_sent = $db->query("
            SELECT SUM(emails_sent) as total 
            FROM smtp_usage 
            WHERE smtp_id = {$server['id']} 
            AND date = CURDATE()
        ")->fetch_assoc()['total'] ?? 0;

        if ($daily_sent >= $server['daily_limit']) {
            logMessage("Daily limit reached for SMTP {$server['id']} ($daily_sent/{$server['daily_limit']})");
            continue;
        }

        // Check hourly limit
        $current_hour = date('G');
        $hourly_sent = $db->query("
            SELECT emails_sent as total 
            FROM smtp_usage 
            WHERE smtp_id = {$server['id']} 
            AND date = CURDATE() 
            AND hour = $current_hour
        ")->fetch_assoc()['total'] ?? 0;

        if ($hourly_sent < $server['hourly_limit']) {
            return $server;
        } else {
            logMessage("Hourly limit reached for SMTP {$server['id']} ($hourly_sent/{$server['hourly_limit']})");
        }
    }

    return null;
}


function checkSendingLimits($db, $smtpId)
{
    $server = $db->query("
        SELECT hourly_limit, daily_limit 
        FROM smtp_servers 
        WHERE id = $smtpId
    ")->fetch_assoc();

    if (!$server) {
        return false;
    }

    // Check daily limit
    $daily_sent = $db->query("
        SELECT SUM(emails_sent) as total 
        FROM smtp_usage 
        WHERE smtp_id = $smtpId 
        AND date = CURDATE()
    ")->fetch_assoc()['total'] ?? 0;

    if ($daily_sent >= $server['daily_limit']) {
        logMessage("Daily limit reached for SMTP $smtpId ($daily_sent/{$server['daily_limit']})");
        return false;
    }

    // Check hourly limit using the database (more reliable than in-memory tracking)
    $current_hour = date('G');
    $hourly_sent = $db->query("
        SELECT emails_sent as total 
        FROM smtp_usage 
        WHERE smtp_id = $smtpId 
        AND date = CURDATE() 
        AND hour = $current_hour
    ")->fetch_assoc()['total'] ?? 0;

    if ($hourly_sent >= $server['hourly_limit']) {
        logMessage("Hourly limit reached for SMTP $smtpId ($hourly_sent/{$server['hourly_limit']})");
        return false;
    }

    return true;
}
function sendEmail($smtp, $to_email, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->Port = $smtp['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['email'];
        $mail->Password = $smtp['password'];
        $mail->Timeout = 30;

        if ($smtp['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($smtp['email']);
        $mail->addAddress($to_email);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->isHTML(true);

        if (!$mail->send()) {
            throw new Exception($mail->ErrorInfo);
        }
    } catch (Exception $e) {
        throw new Exception("SMTP error: " . $e->getMessage());
    }
}



function recordDelivery($db, $smtpId, $emailId, $campaignId, $to_email, $status, $error = null)
{
    // Mail blaster record
    $db->query("
        INSERT INTO mail_blaster 
        (campaign_id, to_mail, smtpid, delivery_date, delivery_time, status, error_message, attempt_count)
        VALUES (
            $campaignId, 
            '" . $db->real_escape_string($to_email) . "', 
            $smtpId, 
            CURDATE(), 
            CURTIME(), 
            '" . $db->real_escape_string($status) . "', 
            " . ($error ? "'" . $db->real_escape_string($error) . "'" : "NULL") . ",
            1
        )
        ON DUPLICATE KEY UPDATE
            smtpid = VALUES(smtpid),
            delivery_date = VALUES(delivery_date),
            delivery_time = VALUES(delivery_time),
            status = IF(mail_blaster.status = 'success', 'success', VALUES(status)),
            error_message = VALUES(error_message),
            attempt_count = IF(mail_blaster.status = 'success', mail_blaster.attempt_count, mail_blaster.attempt_count + 1)
    ");

    // Sending log
    // $db->query("
    //     INSERT INTO sending_logs 
    //     (campaign_id, email_id, smtp_id, status, error_message)
    //     VALUES (
    //         $campaignId,
    //         $emailId,
    //         $smtpId,
    //         '" . $db->real_escape_string($status) . "',
    //         " . ($error ? "'" . $db->real_escape_string($error) . "'" : "NULL") . "
    //     )
    // ");

    // Update SMTP usage with exact timestamp (only for successful sends)
    if ($status === 'success') {
        $current_hour = date('G');
        $db->query("
            INSERT INTO smtp_usage 
            (smtp_id, date, hour, timestamp, emails_sent)
            VALUES (
                $smtpId,
                CURDATE(),
                $current_hour,
                NOW(),
                1
            )
            ON DUPLICATE KEY UPDATE
                emails_sent = emails_sent + 1,
                timestamp = VALUES(timestamp)
        ");
    }
}





function logMessage($message, $level = 'INFO')
{
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }

    $log = "[" . date('Y-m-d H:i:s') . "] [$level] " . $message . "\n";
    $campaign_id = $GLOBALS['campaign_id'] ?? 'unknown';
    file_put_contents("logs/campaign_{$campaign_id}.log", $log, FILE_APPEND);

    if (php_sapi_name() === 'cli') {
        echo $log;
    }
}

$db->close();