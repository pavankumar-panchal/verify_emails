<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'email_id');

// Email sending limits
define('HOURLY_LIMIT', 100);
define('DAILY_LIMIT', 1000);

// Connect to database
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Set proper collation
$db->set_charset("utf8mb4");
$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

class EmailBlaster
{
    private $db;
    private $currentSmtpId = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function run()
    {
        while (true) {
            try {
                $this->processCampaigns();
                sleep(60); // Check every minute for new work
            } catch (Exception $e) {
                $this->log("Error in main loop: " . $e->getMessage());
                sleep(300); // Wait 5 minutes on error before retrying
            }
        }
    }

    private function processCampaigns()
    {
        // Get all active campaigns
        $campaigns = $this->getActiveCampaigns();

        foreach ($campaigns as $campaign) {
            $this->processCampaign($campaign);
        }
    }

    private function processCampaign($campaign)
    {
        // Get active SMTP server
        $smtpServer = $this->getNextActiveSmtpServer();
        if (!$smtpServer) {
            $this->log("No active SMTP servers available");
            return;
        }

        // Check daily limit
        $dailySent = $this->getDailySentCount($smtpServer['id']);
        if ($dailySent >= $smtpServer['daily_limit']) {
            $this->log("Daily limit reached for SMTP {$smtpServer['id']}");
            return;
        }

        // Check hourly limit
        $hourlySent = $this->getHourlySentCount($smtpServer['id']);
        if ($hourlySent >= $smtpServer['hourly_limit']) {
            $this->log("Hourly limit reached for SMTP {$smtpServer['id']}");
            return;
        }

        // Calculate available slots
        $available = min(
            $smtpServer['daily_limit'] - $dailySent,
            $smtpServer['hourly_limit'] - $hourlySent,
            HOURLY_LIMIT
        );

        if ($available <= 0) {
            return;
        }

        // Get pending emails for this campaign
        $emails = $this->getPendingEmails($campaign['campaign_id'], $available);

        foreach ($emails as $email) {
            try {
                $this->sendEmail($smtpServer, $email, $campaign);
                $this->recordDelivery($smtpServer['id'], $email['raw_emailid'], $campaign['campaign_id']);
                $this->log("Sent email for campaign {$campaign['campaign_id']} to {$email['raw_emailid']}");

                // Small delay between emails
                usleep(100000); // 0.1 second
            } catch (Exception $e) {
                $this->log("Failed to send email to {$email['raw_emailid']}: " . $e->getMessage());
            }
        }
    }

    private function getActiveCampaigns()
    {
        $query = "SELECT * FROM campaign_master ORDER BY campaign_id";
        $result = $this->db->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    private function getNextActiveSmtpServer()
    {
        // Get all active SMTP servers
        $query = "SELECT * FROM smtp_servers WHERE is_active = 1 ORDER BY id";
        $result = $this->db->query($query);
        $servers = $result->fetch_all(MYSQLI_ASSOC);

        if (empty($servers)) {
            return null;
        }

        // Round-robin selection
        if ($this->currentSmtpId === null) {
            $this->currentSmtpId = $servers[0]['id'];
            return $servers[0];
        }

        // Find next server
        $nextIndex = null;
        foreach ($servers as $index => $server) {
            if ($server['id'] > $this->currentSmtpId) {
                $nextIndex = $index;
                break;
            }
        }

        if ($nextIndex === null) {
            $nextIndex = 0;
        }

        $this->currentSmtpId = $servers[$nextIndex]['id'];
        return $servers[$nextIndex];
    }

    private function getPendingEmails($campaign_id, $limit)
    {
        $query = "SELECT e.id, e.raw_emailid 
                 FROM emails e
                 WHERE e.domain_status = 1
                 AND NOT EXISTS (
                     SELECT 1 FROM mail_blaster mb 
                     WHERE mb.to_mail COLLATE utf8mb4_unicode_ci = e.raw_emailid 
                     AND mb.campaign_id = ?
                 )
                 LIMIT ?";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $campaign_id, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function getDailySentCount($smtpId)
    {
        $query = "SELECT COUNT(*) as count FROM mail_blaster 
                 WHERE smtpid = ? AND delivery_date = CURDATE()";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $smtpId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    private function getHourlySentCount($smtpId)
    {
        $query = "SELECT COUNT(*) as count FROM mail_blaster 
                 WHERE smtpid = ? 
                 AND delivery_date = CURDATE() 
                 AND HOUR(delivery_time) = HOUR(CURRENT_TIME())";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $smtpId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['count'];
    }

    private function sendEmail($smtp, $email, $campaign)
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
        $mail->addAddress($email['raw_emailid']);
        $mail->Subject = $campaign['mail_subject'];
        $mail->Body = $campaign['mail_body'];
        $mail->isHTML(true);

        if (!$mail->send()) {
            throw new Exception($mail->ErrorInfo);
        }
    }

    private function recordDelivery($smtpId, $toMail, $campaignId)
    {
        $query = "INSERT INTO mail_blaster 
                 (campaign_id, to_mail, smtpid, delivery_date, delivery_time)
                 VALUES (?, ?, ?, CURDATE(), CURTIME())";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isi", $campaignId, $toMail, $smtpId);

        if (!$stmt->execute()) {
            throw new Exception("Failed to record delivery: " . $stmt->error);
        }
    }

    private function log($message)
    {
        $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;
        echo $logMessage;
        file_put_contents('email_blaster.log', $logMessage, FILE_APPEND);
    }
}

// Initialize and run
require 'vendor/autoload.php';
$emailBlaster = new EmailBlaster($db);
$emailBlaster->run();

$db->close();