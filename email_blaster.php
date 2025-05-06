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
        // Check campaign status with more comprehensive data
        $result = $db->query("
            SELECT 
                status, 
                total_emails, 
                sent_emails,
                pending_emails,
                failed_emails
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

        // Check network connectivity before proceeding
        if (!checkNetworkConnectivity()) {
            logMessage("Network connection unavailable. Waiting to retry...", 'WARNING');
            sleep(60); // Wait longer when network is down
            continue;
        }

        // Verify if there are actually emails left to send
        $remaining_result = $db->query("
            SELECT COUNT(*) as remaining
            FROM emails e
            WHERE e.domain_status = 1
            AND NOT EXISTS (
                SELECT 1 FROM mail_blaster mb 
                WHERE mb.to_mail = e.raw_emailid 
                AND mb.campaign_id = $campaign_id
                AND mb.status = 'success'
            )
        ");
        $remaining_count = $remaining_result->fetch_assoc()['remaining'];

        if ($remaining_count == 0) {
            // Mark as completed if no emails left to send
            $db->query("
                UPDATE campaign_status 
                SET status = 'completed', 
                    pending_emails = 0,
                    end_time = NOW() 
                WHERE campaign_id = $campaign_id
            ");
            logMessage("All valid emails processed. Campaign completed.");
            break;
        }

        // Process emails in batches
        $processed_count = processEmailBatch($db, $campaign_id);

        // If no emails were processed in this batch
        if ($processed_count === 0) {
            logMessage("No emails processed in this batch. Possible rate limiting.");
            sleep(30);
        } else {
            sleep(5);
        }

    } catch (Exception $e) {
        logMessage("Error in main loop: " . $e->getMessage(), 'ERROR');
        sleep(60); // Wait longer on errors
    }
}

function checkNetworkConnectivity()
{
    // Check if we can connect to a reliable host (like Google DNS)
    $connected = @fsockopen("8.8.8.8", 53, $errno, $errstr, 5);
    if ($connected) {
        fclose($connected);
        return true;
    }
    return false;
}






function processEmailBatch($db, $campaign_id)
{
    $processed_count = 0;
    $max_retries = 3;
    $retry_count = 0;
    $success = false;

    while ($retry_count < $max_retries && !$success) {
        try {
            // Set shorter lock timeout
            $db->query("SET SESSION innodb_lock_wait_timeout = 10");
            $db->begin_transaction();

            // Get campaign details
            $result = $db->query("SELECT mail_subject, mail_body FROM campaign_master WHERE campaign_id = $campaign_id");
            if ($result->num_rows === 0) {
                throw new Exception("Campaign not found");
            }
            $campaign = $result->fetch_assoc();

            // Get active SMTP server
            $smtp = getNextSmtpServer($db);
            if (!$smtp) {
                logMessage("No active SMTP servers available");
                $db->commit();
                return 0;
            }

            // Check sending limits
            if (!checkSendingLimits($db, $smtp['id'])) {
                $db->commit();
                return 0;
            }

            // Get next batch of pending emails
            $emails = getNextEmailBatch($db, $campaign_id, 10);

            if (empty($emails)) {
                $db->commit();
                return 0;
            }

            foreach ($emails as $email) {
                try {
                    // Check if this email was already successfully sent
                    $check = $db->query("
                        SELECT id, status, attempt_count 
                        FROM mail_blaster 
                        WHERE campaign_id = $campaign_id 
                        AND to_mail = '" . $db->real_escape_string($email['raw_emailid']) . "'
                        LIMIT 1
                    ");

                    $existing = $check->num_rows > 0 ? $check->fetch_assoc() : null;

                    if ($existing && $existing['status'] === 'success') {
                        continue;
                    }

                    if ($existing && $existing['attempt_count'] >= 3) {
                        continue;
                    }

                    // Check campaign status again
                    $status_check = $db->query("SELECT status FROM campaign_status WHERE campaign_id = $campaign_id LIMIT 1");
                    if ($status_check->num_rows === 0 || $status_check->fetch_assoc()['status'] !== 'running') {
                        logMessage("Campaign paused or stopped during processing");
                        break;
                    }

                    // Send email
                    sendEmail($smtp, $email['raw_emailid'], $campaign['mail_subject'], $campaign['mail_body']);

                    // Record successful delivery
                    recordDelivery($db, $smtp['id'], $email['id'], $campaign_id, $email['raw_emailid'], 'success');

                    // Update campaign status
                    $db->query("UPDATE campaign_status 
                               SET sent_emails = sent_emails + 1, 
                                   pending_emails = GREATEST(0, pending_emails - 1) 
                               WHERE campaign_id = $campaign_id");

                    logMessage("Sent to {$email['raw_emailid']}");
                    $processed_count++;

                    usleep(500000);

                } catch (Exception $e) {
                    recordDelivery($db, $smtp['id'], $email['id'], $campaign_id, $email['raw_emailid'], 'failed', $e->getMessage());

                    $db->query("UPDATE campaign_status 
                               SET failed_emails = failed_emails + 1, 
                                   pending_emails = GREATEST(0, pending_emails - 1) 
                               WHERE campaign_id = $campaign_id");

                    logMessage("Failed to send to {$email['raw_emailid']}: " . $e->getMessage(), 'ERROR');
                }
            }

            $db->commit();
            $success = true;

        } catch (mysqli_sql_exception $e) {
            $db->rollback();

            if (strpos($e->getMessage(), 'Lock wait timeout exceeded') !== false) {
                $retry_count++;
                logMessage("Lock timeout on batch processing attempt $retry_count for campaign $campaign_id. Retrying...", 'WARNING');
                sleep(1); // Wait before retrying

                if ($retry_count >= $max_retries) {
                    logMessage("Failed to process batch after $max_retries attempts due to lock timeout", 'ERROR');
                    return 0;
                }
            } else {
                logMessage("Database error processing batch: " . $e->getMessage(), 'ERROR');
                return 0;
            }
        }
    }

    // Reset to default timeout
    $db->query("SET SESSION innodb_lock_wait_timeout = 50");

    return $processed_count;
}


function getNextSmtpServer($db)
{
    $current_hour = date('H');
    $current_date = date('Y-m-d');

    $result = $db->query("
        SELECT s.*,
               IFNULL((
                   SELECT SUM(emails_sent) 
                   FROM smtp_usage 
                   WHERE smtp_id = s.id 
                   AND date = '$current_date' 
                   AND hour = $current_hour
               ), 0) as hourly_sent,
               IFNULL((
                   SELECT SUM(emails_sent) 
                   FROM smtp_usage 
                   WHERE smtp_id = s.id 
                   AND date = '$current_date'
               ), 0) as daily_sent
        FROM smtp_servers s
        WHERE s.is_active = 1
        HAVING hourly_sent < s.hourly_limit AND daily_sent < s.daily_limit
        ORDER BY hourly_sent ASC, daily_sent ASC
        LIMIT 1
    ");

    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

function checkSendingLimits($db, $smtpId)
{
    $current_hour = date('H');
    $current_date = date('Y-m-d');

    $server = $db->query("SELECT hourly_limit, daily_limit FROM smtp_servers WHERE id = $smtpId")->fetch_assoc();
    if (!$server) {
        return false;
    }

    $hourly_sent = $db->query("
        SELECT SUM(emails_sent) as total 
        FROM smtp_usage 
        WHERE smtp_id = $smtpId 
        AND date = '$current_date' 
        AND hour = $current_hour
    ")->fetch_assoc()['total'] ?? 0;

    if ($hourly_sent >= $server['hourly_limit']) {
        logMessage("Hourly limit reached for SMTP $smtpId ($hourly_sent/{$server['hourly_limit']})");
        return false;
    }

    $daily_sent = $db->query("
        SELECT SUM(emails_sent) as total 
        FROM smtp_usage 
        WHERE smtp_id = $smtpId 
        AND date = '$current_date'
    ")->fetch_assoc()['total'] ?? 0;

    if ($daily_sent >= $server['daily_limit']) {
        logMessage("Daily limit reached for SMTP $smtpId ($daily_sent/{$server['daily_limit']})");
        return false;
    }

    return true;
}



// function getNextEmailBatch($db, $campaign_id, $limit)
// {
//     $result = $db->query("
//         SELECT e.id, e.raw_emailid
//         FROM emails e
//         LEFT JOIN mail_blaster mb ON 
//             mb.to_mail = e.raw_emailid AND 
//             mb.campaign_id = $campaign_id
//         WHERE e.domain_status = 1
//         AND (
//             mb.id IS NULL OR 
//             (mb.status IN ('failed', 'pending') AND mb.attempt_count < 3)
//         )
//         AND NOT EXISTS (
//             SELECT 1 FROM mail_blaster mb2 
//             WHERE mb2.to_mail = e.raw_emailid 
//             AND mb2.campaign_id = $campaign_id
//             AND mb2.status = 'success'
//         )
//         ORDER BY mb.attempt_count ASC, mb.id ASC
//         LIMIT $limit
//     ");

//     if ($result === false) {
//         throw new Exception("Database error: " . $db->error);
//     }

//     return $result->fetch_all(MYSQLI_ASSOC);
// }

function getNextEmailBatch($db, $campaign_id, $limit)
{
    $result = $db->query("
        SELECT e.id, e.raw_emailid
        FROM emails e
        LEFT JOIN mail_blaster mb ON 
            mb.to_mail = e.raw_emailid AND 
            mb.campaign_id = $campaign_id
        WHERE e.domain_status = 1
        AND (
            mb.id IS NULL OR 
            (mb.status IN ('failed', 'pending') AND mb.attempt_count < 3)
        )
        AND NOT EXISTS (
            SELECT 1 FROM mail_blaster mb2 
            WHERE mb2.to_mail = e.raw_emailid 
            AND mb2.campaign_id = $campaign_id
            AND mb2.status = 'success'
        )
        ORDER BY mb.attempt_count ASC, mb.id ASC
        LIMIT $limit
    ");

    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row;
    }

    return $emails;
}



function sendEmail($smtp, $to_email, $subject, $body)
{
    $mail = new PHPMailer(true);

    try {
        // Configure SMTP with timeout settings
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->Port = $smtp['port'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['email'];
        $mail->Password = $smtp['password'];

        // Set timeouts (in seconds)
        $mail->Timeout = 30; // Socket connection timeout
        $mail->SMTPKeepAlive = true; // Keep connection alive for multiple emails

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
        // Check for network-related errors
        $errorMessage = $e->getMessage();
        if (
            strpos($errorMessage, 'Connection timed out') !== false ||
            strpos($errorMessage, 'Failed to connect to server') !== false ||
            strpos($errorMessage, 'SMTP Error: Could not connect to SMTP host') !== false
        ) {
            throw new Exception("Network error: " . $errorMessage);
        } else {
            throw new Exception("SMTP error: " . $errorMessage);
        }
    }
}


// function recordDelivery($db, $smtpId, $emailId, $campaignId, $to_email, $status, $error = null)
// {
//     $escaped_email = $db->real_escape_string($to_email);
//     $escaped_status = $db->real_escape_string($status);
//     $escaped_error = $error !== null ? "'" . $db->real_escape_string($error) . "'" : "NULL";

//     // Determine if this is a network failure
//     $is_network_failure = ($error !== null && strpos($error, 'Network error:') !== false);

//     // Insert or update mail_blaster table
//     $query = "
//         INSERT INTO mail_blaster 
//         (campaign_id, to_mail, smtpid, delivery_date, delivery_time, status, error_message, attempt_count)
//         VALUES (
//             $campaignId, 
//             '$escaped_email', 
//             $smtpId, 
//             CURDATE(), 
//             CURTIME(), 
//             '$escaped_status', 
//             $escaped_error,
//             1
//         )
//         ON DUPLICATE KEY UPDATE
//             smtpid = VALUES(smtpid),
//             delivery_date = VALUES(delivery_date),
//             delivery_time = VALUES(delivery_time),
//             status = IF(status = 'success', 'success', VALUES(status)),
//             error_message = VALUES(error_message),
//             attempt_count = IF(status = 'success', attempt_count, attempt_count + 1)
//     ";

//     if (!$db->query($query)) {
//         throw new Exception("Failed to record delivery: " . $db->error);
//     }

//     // Only count as failed if not a network error (we'll retry these)
//     if ($status === 'failed' && !$is_network_failure) {
//         $db->query("UPDATE campaign_status 
//                    SET failed_emails = failed_emails + 1, 
//                        pending_emails = GREATEST(0, pending_emails - 1) 
//                    WHERE campaign_id = $campaignId");
//     }

//     // Insert log into sending_logs table
//     $log_query = "
//         INSERT INTO sending_logs 
//         (campaign_id, email_id, smtp_id, status, sent_at, error_message)
//         VALUES (
//             $campaignId, 
//             $emailId, 
//             $smtpId, 
//             '$escaped_status', 
//             NOW(), 
//             $escaped_error
//         )
//     ";

//     if (!$db->query($log_query)) {
//         throw new Exception("Failed to record in sending_logs: " . $db->error);
//     }

//     // Only update SMTP usage if successful
//     if ($status === 'success') {
//         updateSmtpUsage($db, $smtpId);
//     }
// }

function recordDelivery($db, $smtpId, $emailId, $campaignId, $to_email, $status, $error = null)
{
    $is_network_failure = ($error !== null && strpos($error, 'Network error:') !== false);

    // Step 1: Insert or update mail_blaster
    $query = "
        INSERT INTO mail_blaster 
        (campaign_id, to_mail, smtpid, delivery_date, delivery_time, status, error_message, attempt_count)
        VALUES (?, ?, ?, CURDATE(), CURTIME(), ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            smtpid = VALUES(smtpid),
            delivery_date = VALUES(delivery_date),
            delivery_time = VALUES(delivery_time),
            status = IF(mail_blaster.status = 'success', 'success', VALUES(status)),
            error_message = VALUES(error_message),
            attempt_count = IF(mail_blaster.status = 'success', mail_blaster.attempt_count, mail_blaster.attempt_count + 1)
    ";
    $stmt = $db->prepare($query);
    $stmt->bind_param("isisss", $campaignId, $to_email, $smtpId, $status, $error);
    if (!$stmt->execute()) {
        throw new Exception("Failed to record delivery: " . $stmt->error);
    }
    $stmt->close();

    // Step 2: Update campaign_status (only if failed and not network error)
    if ($status === 'failed' && !$is_network_failure) {
        $status_update = "
            UPDATE campaign_status 
            SET failed_emails = failed_emails + 1, 
                pending_emails = GREATEST(0, pending_emails - 1) 
            WHERE campaign_id = ?
        ";
        $stmt2 = $db->prepare($status_update);
        $stmt2->bind_param("i", $campaignId);
        $stmt2->execute();
        $stmt2->close();
    }

    // Step 3: Insert into sending_logs (keep all attempts)
    $log_query = "
        INSERT INTO sending_logs 
        (campaign_id, email_id, smtp_id, status, sent_at, error_message)
        VALUES (?, ?, ?, ?, NOW(), ?)
    ";
    $stmt3 = $db->prepare($log_query);
    $stmt3->bind_param("iiiss", $campaignId, $emailId, $smtpId, $status, $error);
    if (!$stmt3->execute()) {
        throw new Exception("Failed to record in sending_logs: " . $stmt3->error);
    }
    $stmt3->close();

    // Step 4: Update SMTP usage
    if ($status === 'success') {
        updateSmtpUsage($db, $smtpId);
    }
}


function updateSmtpUsage($db, $smtpId)
{
    // Get Indian Standard Time (IST)
    $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    $current_hour = (int) $dt->format('H');
    $current_date = $dt->format('Y-m-d');

    $query = "
        INSERT INTO smtp_usage (smtp_id, date, hour, emails_sent)
        VALUES ($smtpId, '$current_date', $current_hour, 1)
        ON DUPLICATE KEY UPDATE emails_sent = emails_sent + 1
    ";

    if (!$db->query($query)) {
        throw new Exception("Failed to update SMTP usage: " . $db->error);
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