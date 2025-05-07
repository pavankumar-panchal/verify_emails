<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "email_id");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Initialize message variables
$message = '';
$message_type = ''; // 'success' or 'error'

// Handle campaign actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_campaign'])) {
        $campaign_id = intval($_POST['campaign_id']);
        startCampaign($conn, $campaign_id);

        // Trigger email processing in background
        exec("php email_blaster.php $campaign_id > /dev/null 2>&1 &");

    } elseif (isset($_POST['pause_campaign'])) {
        $campaign_id = intval($_POST['campaign_id']);
        pauseCampaign($conn, $campaign_id);
    } elseif (isset($_POST['retry_failed'])) {
        $campaign_id = intval($_POST['campaign_id']);
        retryFailedEmails($conn, $campaign_id);
    }
}

// Function to start a campaign

// In startCampaign() function

function startCampaign($conn, $campaign_id)
{
    global $message, $message_type;

    $max_retries = 3;
    $retry_count = 0;
    $success = false;

    while ($retry_count < $max_retries && !$success) {
        try {
            // Start transaction with shorter lock timeout
            $conn->query("SET SESSION innodb_lock_wait_timeout = 10");
            $conn->begin_transaction();

            // Check if campaign exists
            $check = $conn->query("SELECT 1 FROM campaign_master WHERE campaign_id = $campaign_id");
            if ($check->num_rows == 0) {
                $message = "Campaign #$campaign_id does not exist";
                $message_type = 'error';
                $conn->commit();
                return;
            }

            // Check if already completed
            $status_check = $conn->query("SELECT status FROM campaign_status WHERE campaign_id = $campaign_id");
            if ($status_check->num_rows > 0 && $status_check->fetch_assoc()['status'] === 'completed') {
                $message = "Campaign #$campaign_id is already completed";
                $message_type = 'info';
                $conn->commit();
                return;
            }

            // Calculate accurate counts of emails
            $counts = getEmailCounts($conn, $campaign_id);

            if ($status_check->num_rows > 0) {
                // Update existing campaign status to running
                $conn->query("UPDATE campaign_status SET 
                    status = 'running',
                    total_emails = {$counts['total_valid']},
                    pending_emails = {$counts['pending']},
                    sent_emails = {$counts['sent']},
                    failed_emails = {$counts['failed']},
                    start_time = IFNULL(start_time, NOW()),
                    end_time = NULL
                    WHERE campaign_id = $campaign_id");
            } else {
                // Create new campaign status
                $conn->query("INSERT INTO campaign_status 
                    (campaign_id, total_emails, pending_emails, sent_emails, failed_emails, status, start_time)
                    VALUES ($campaign_id, {$counts['total_valid']}, {$counts['pending']}, {$counts['sent']}, {$counts['failed']}, 'running', NOW())");
            }

            $conn->commit();
            $success = true;

            // Start the email blaster process
            startEmailBlasterProcess($campaign_id);

            $message = "Campaign #$campaign_id started successfully!";
            $message_type = 'success';

        } catch (mysqli_sql_exception $e) {
            $conn->rollback();

            if (strpos($e->getMessage(), 'Lock wait timeout exceeded') !== false) {
                $retry_count++;
                // logMessage("Lock timeout on start attempt $retry_count for campaign $campaign_id. Retrying...", 'WARNING');
                sleep(1); // Wait before retrying

                if ($retry_count >= $max_retries) {
                    $message = "Failed to start campaign #$campaign_id after $max_retries attempts due to lock timeout";
                    $message_type = 'error';
                    // logMessage($message, 'ERROR');
                }
            } else {
                // Other database errors
                $message = "Database error starting campaign #$campaign_id: " . $e->getMessage();
                $message_type = 'error';
                // logMessage($message, 'ERROR');
                break;
            }
        }
    }

    // Reset to default timeout
    $conn->query("SET SESSION innodb_lock_wait_timeout = 50");
}





function getEmailCounts($conn, $campaign_id)
{
    $result = $conn->query("
        SELECT 
            COUNT(*) as total_valid,
            SUM(CASE WHEN (mb.status IS NULL OR mb.status = 'failed' AND mb.attempt_count < 3) THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN mb.status = 'success' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN mb.status = 'failed' AND mb.attempt_count >= 3 THEN 1 ELSE 0 END) as failed
        FROM emails e
        LEFT JOIN mail_blaster mb ON mb.to_mail = e.raw_emailid AND mb.campaign_id = $campaign_id
        WHERE e.domain_status = 1
    ");
    return $result->fetch_assoc();
}

// function startEmailBlasterProcess($campaign_id) {
//     // Check if process is already running
//     $output = shell_exec("pgrep -f 'email_blaster.php $campaign_id'");
//     if (empty($output)) {
//         // Start new process in background
//         $command = "php email_blaster.php $campaign_id > /dev/null 2>&1 &";
//         exec($command);
//     }
// }



function startEmailBlasterProcess($campaign_id)
{
    $lock_file = "/tmp/email_blaster_{$campaign_id}.lock";

    // Check if process is already running
    if (file_exists($lock_file)) {
        $pid = file_get_contents($lock_file);
        if (posix_kill((int) $pid, 0)) {
            // Process is running
            return;
        } else {
            // Stale lock file
            unlink($lock_file);
        }
    }

    // Use absolute paths
    $php_path = '/opt/lampp/bin/php'; // Adjust this to your PHP path (run 'which php' to find it)
    $script_path = __DIR__ . '/email_blaster.php'; // Use absolute path to script

    // Start new background process
    $command = "nohup $php_path $script_path $campaign_id > /dev/null 2>&1 & echo $!";
    $pid = shell_exec($command);

    // Save PID to lock file
    if ($pid) {
        file_put_contents($lock_file, trim($pid));
    }
}




// function startEmailBlasterProcess($campaign_id)
// {
//     $lock_file = "/tmp/email_blaster_{$campaign_id}.lock";

//     // Check if process is already running
//     if (file_exists($lock_file)) {
//         $pid = file_get_contents($lock_file);
//         if (posix_kill((int) $pid, 0)) {
//             // Process is running
//             return;
//         } else {
//             // Stale lock file
//             unlink($lock_file);
//         }
//     }

//     // Use absolute paths
//     $php_path = '/opt/lampp/bin/php'; // Adjust this to your PHP path (run 'which php' to find it)
//     $script_path = __DIR__ . '/email_blaster.php'; // Use absolute path to script

//     // Start new background process
//     $command = "nohup $php_path $script_path $campaign_id > /dev/null 2>&1 & echo $!";
//     $pid = shell_exec($command);

//     // Save PID to lock file
//     if ($pid) {
//         file_put_contents($lock_file, trim($pid));
//     }
// }




// Function to pause a campaign
// function pauseCampaign($conn, $campaign_id)
// {
//     global $message, $message_type;

//     // Update status to paused
//     $result = $conn->query("UPDATE campaign_status SET status = 'paused' 
//               WHERE campaign_id = $campaign_id AND status = 'running'");

//     if ($conn->affected_rows > 0) {
//         // Kill the running email blaster process
//         stopEmailBlasterProcess($campaign_id);

//         $message = "Campaign #$campaign_id paused successfully!";
//         $message_type = 'success';
//     } else {
//         $message = "Campaign #$campaign_id is not running or doesn't exist";
//         $message_type = 'error';
//     }
// }



function pauseCampaign($conn, $campaign_id)
{
    global $message, $message_type;

    $max_retries = 3;
    $retry_count = 0;
    $success = false;

    while ($retry_count < $max_retries && !$success) {
        try {
            // Start transaction with shorter lock timeout
            $conn->query("SET SESSION innodb_lock_wait_timeout = 10");
            $conn->begin_transaction();

            // Update status to paused
            $result = $conn->query("UPDATE campaign_status SET status = 'paused' 
                  WHERE campaign_id = $campaign_id AND status = 'running'");

            if ($conn->affected_rows > 0) {
                // Kill the running email blaster process
                stopEmailBlasterProcess($campaign_id);

                $message = "Campaign #$campaign_id paused successfully!";
                $message_type = 'success';
                $success = true;
            } else {
                $message = "Campaign #$campaign_id is not running or doesn't exist";
                $message_type = 'error';
                $success = true; // Not an error we can retry
            }

            $conn->commit();

        } catch (mysqli_sql_exception $e) {
            $conn->rollback();

            if (strpos($e->getMessage(), 'Lock wait timeout exceeded') !== false) {
                $retry_count++;
                // logMessage("Lock timeout on pause attempt $retry_count for campaign $campaign_id. Retrying...", 'WARNING');
                sleep(1); // Wait before retrying

                if ($retry_count >= $max_retries) {
                    $message = "Failed to pause campaign #$campaign_id after $max_retries attempts due to lock timeout";
                    $message_type = 'error';
                    // logMessage($message, 'ERROR');
                }
            } else {
                // Other database errors
                $message = "Database error pausing campaign #$campaign_id: " . $e->getMessage();
                $message_type = 'error';
                // logMessage($message, 'ERROR');
                break;
            }
        }
    }

    // Reset to default timeout
    $conn->query("SET SESSION innodb_lock_wait_timeout = 50");
}


function stopEmailBlasterProcess($campaign_id)
{
    // Find and kill the process
    exec("pkill -f 'email_blaster.php $campaign_id'");
}






function retryFailedEmails($conn, $campaign_id)
{
    global $message, $message_type;

    // First get count of eligible failed emails (attempt_count < 3)
    $result = $conn->query("
        SELECT COUNT(*) as failed_count 
        FROM mail_blaster 
        WHERE campaign_id = $campaign_id 
        AND status = 'failed'
        AND attempt_count < 3
    ");
    $failed_count = $result->fetch_assoc()['failed_count'];

    if ($failed_count > 0) {
        // Mark failed emails for retry
        $conn->query("
            UPDATE mail_blaster 
            SET status = 'pending', 
                error_message = NULL,
                attempt_count = attempt_count + 1
            WHERE campaign_id = $campaign_id 
            AND status = 'failed'
            AND attempt_count < 3
        ");

        // Update campaign status
        $conn->query("
            UPDATE campaign_status 
            SET pending_emails = pending_emails + $failed_count,
                failed_emails = GREATEST(0, failed_emails - $failed_count),
                status = 'running'
            WHERE campaign_id = $campaign_id
        ");

        $message = "Retrying $failed_count failed emails for campaign #$campaign_id";
        $message_type = 'success';

        // Restart the email processing
        startEmailBlasterProcess($campaign_id);
    } else {
        $message = "No eligible failed emails to retry for campaign #$campaign_id";
        $message_type = 'info';
    }
}

// Replace the existing retryFailedEmails() function with this improved version:

// function retryFailedEmails($conn, $campaign_id)
// {
//     global $message, $message_type;

//     // First ensure the status column is large enough
//     $conn->query("ALTER TABLE mail_blaster MODIFY COLUMN status VARCHAR(20) NOT NULL");

//     // Get count of eligible failed emails (attempt_count < 3)
//     $result = $conn->query("
//         SELECT COUNT(*) as failed_count 
//         FROM mail_blaster 
//         WHERE campaign_id = $campaign_id 
//         AND (status = 'failed' OR status = 'pending')
//         AND attempt_count < 3
//     ");
//     $failed_count = $result->fetch_assoc()['failed_count'];

//     if ($failed_count > 0) {
//         // Mark failed emails for retry
//         $conn->query("
//             UPDATE mail_blaster 
//             SET status = 'pending', 
//                 error_message = NULL
//             WHERE campaign_id = $campaign_id 
//             AND (status = 'failed' OR status = 'pending')
//             AND attempt_count < 3
//         ");

//         // Update campaign status
//         $conn->query("
//             UPDATE campaign_status 
//             SET pending_emails = pending_emails + $failed_count,
//                 failed_emails = GREATEST(0, failed_emails - $failed_count),
//                 status = 'running'
//             WHERE campaign_id = $campaign_id
//         ");

//         $message = "Retrying $failed_count failed emails for campaign #$campaign_id";
//         $message_type = 'success';

//         // Restart the email processing
//         startEmailBlasterProcess($campaign_id);
//     } else {
//         $message = "No eligible failed emails to retry for campaign #$campaign_id";
//         $message_type = 'info';
//     }
// }




// Get all campaigns with their status
$campaigns = [];
$result = $conn->query("
    SELECT cm.*, 
           COALESCE(cs.status, 'pending') as campaign_status, 
           COALESCE(cs.total_emails, 0) as total_emails, 
           COALESCE(cs.pending_emails, 0) as pending_emails, 
           COALESCE(cs.sent_emails, 0) as sent_emails, 
           COALESCE(cs.failed_emails, 0) as failed_emails,
           cs.start_time, 
           cs.end_time
    FROM campaign_master cm
    LEFT JOIN campaign_status cs ON cm.campaign_id = cs.campaign_id
    ORDER BY cm.campaign_id DESC
");

while ($row = $result->fetch_assoc()) {
    // Calculate progress percentage
    // In your HTML/PHP code where you calculate progress:
    $total = max($row['total_emails'], 1); // Ensure we never divide by zero
    $sent = min($row['sent_emails'], $total); // Ensure sent never exceeds total
    $row['progress'] = round(($sent / $total) * 100);
    $campaigns[] = $row;
}

// Get detailed stats for selected campaign if specified
$campaign_details = null;
if (isset($_GET['view'])) {
    $campaign_id = intval($_GET['view']);
    $result = $conn->query("
        SELECT cm.*, cs.*
        FROM campaign_master cm
        LEFT JOIN campaign_status cs ON cm.campaign_id = cs.campaign_id
        WHERE cm.campaign_id = $campaign_id
    ");
    $campaign_details = $result->fetch_assoc();

    // Get recent logs for this campaign
    $logs = [];
    $result = $conn->query("
        SELECT mb.*, e.raw_emailid, s.name as smtp_name
        FROM mail_blaster mb
        JOIN emails e ON mb.to_mail = e.raw_emailid
        LEFT JOIN smtp_servers s ON mb.smtpid = s.id
        WHERE mb.campaign_id = $campaign_id
        ORDER BY mb.delivery_date DESC, mb.delivery_time DESC
        LIMIT 100
    ");
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    $campaign_details['logs'] = $logs;
}

$conn->close();
?>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Monitor</title>
    <link rel="stylesheet" href="assets/style_tailwind.css">
    <link rel="stylesheet" href="assets/main.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .progress-bar {
            height: 20px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #3b82f6;
            transition: width 0.3s ease;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-pending {
            background-color: #f59e0b;
            color: white;
        }

        .status-running {
            background-color: #3b82f6;
            color: white;
        }

        .status-paused {
            background-color: #6b7280;
            color: white;
        }

        .status-completed {
            background-color: #10b981;
            color: white;
        }

        .status-failed {
            background-color: #ef4444;
            color: white;
        }

        .log-success {
            color: #10b981;
        }

        .log-failed {
            color: #ef4444;
        }
    </style>
</head>

<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-8 max-w-6xl">
        <!-- Status Message -->
        <?php if ($message): ?>
            <div class="alert-<?= $message_type ?> p-4 mb-6 rounded-md shadow-sm flex items-start">
                <div class="ml-3">
                    <p class="text-sm font-medium">
                        <?= htmlspecialchars($message) ?>
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-chart-line mr-2 text-blue-600"></i>
                Campaign Monitor
            </h1>
        </div>

        <!-- Campaigns Overview -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Description</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Emails</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($campaigns as $campaign): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= $campaign['campaign_id'] ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <a href="?view=<?= $campaign['campaign_id'] ?>"
                                            class="text-blue-600 hover:text-blue-800">
                                            <?= htmlspecialchars($campaign['description']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="status-badge status-<?= strtolower($campaign['campaign_status'] ?? 'pending') ?>">
                                        <?= $campaign['campaign_status'] ?? 'Not started' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?= $campaign['progress'] ?>%"></div>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?= $campaign['progress'] ?>%
                                        (<?= $campaign['sent_emails'] ?: 0 ?>/<?= $campaign['total_emails'] ?: 0 ?>)
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>Total: <?= $campaign['total_emails'] ?: 0 ?></div>
                                    <div>Pending: <?= $campaign['pending_emails'] ?: 0 ?></div>
                                    <div>Sent: <?= $campaign['sent_emails'] ?: 0 ?></div>
                                    <div>Failed: <?= $campaign['failed_emails'] ?: 0 ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">

                                    <form method="POST" class="inline">
                                        <input type="hidden" name="campaign_id" value="<?= $campaign['campaign_id'] ?>">

                                        <?php if (($campaign['campaign_status'] ?? '') === 'running'): ?>
                                            <button type="submit" name="pause_campaign"
                                                class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                <i class="fas fa-pause mr-1"></i> Pause
                                            </button>
                                        <?php elseif (($campaign['campaign_status'] ?? '') === 'completed'): ?>
                                            <span class="text-gray-400 mr-3">Completed</span>
                                        <?php else: ?>
                                            <button type="submit" name="start_campaign"
                                                class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-play mr-1"></i> Start
                                            </button>
                                        <?php endif; ?>

                                        <?php if (($campaign['failed_emails'] ?? 0) > 0 && ($campaign['campaign_status'] ?? '') !== 'completed'): ?>
                                            <button type="submit" name="retry_failed" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-redo mr-1"></i> Retry Failed
                                            </button>
                                        <?php endif; ?>
                                    </form>


                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Campaign Details -->
        <?php if ($campaign_details): ?>
            <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        Campaign #<?= $campaign_details['campaign_id'] ?>:
                        <?= htmlspecialchars($campaign_details['description']) ?>
                    </h2>
                    <div class="flex items-center mt-2">
                        <span
                            class="status-badge status-<?= strtolower($campaign_details['campaign_status'] ?? 'pending') ?> mr-3">
                            <?= $campaign_details['campaign_status'] ?? 'Not started' ?>
                        </span>
                        <span class="text-sm text-gray-500">
                            <?php if ($campaign_details['start_time']): ?>
                                Started: <?= date('M j, Y g:i a', strtotime($campaign_details['start_time'])) ?>
                            <?php endif; ?>
                            <?php if ($campaign_details['end_time']): ?>
                                | Ended: <?= date('M j, Y g:i a', strtotime($campaign_details['end_time'])) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="px-6 py-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-blue-800">Total Emails</div>
                        <div class="text-2xl font-bold text-blue-600"><?= $campaign_details['total_emails'] ?: 0 ?></div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-yellow-800">Pending</div>
                        <div class="text-2xl font-bold text-yellow-600"><?= $campaign_details['pending_emails'] ?: 0 ?>
                        </div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-green-800">Sent</div>
                        <div class="text-2xl font-bold text-green-600"><?= $campaign_details['sent_emails'] ?: 0 ?></div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-red-800">Failed</div>
                        <div class="text-2xl font-bold text-red-600"><?= $campaign_details['failed_emails'] ?: 0 ?></div>
                    </div>
                </div>

                <div class="px-6 py-4">
                    <h3 class="text-md font-medium text-gray-900 mb-3">Recent Activity</h3>
                    <div class="overflow-y-auto max-h-96">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Time</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        SMTP Server</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($campaign_details['logs'] as $log): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= date('M j, H:i:s', strtotime($log['delivery_date'] . ' ' . $log['delivery_time'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($log['raw_emailid']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?= htmlspecialchars($log['smtp_name'] ?? 'N/A') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <span
                                                class="<?= ($log['status'] ?? '') === 'success' ? 'log-success' : 'log-failed' ?>">
                                                <?= ucfirst($log['status'] ?? 'unknown') ?>
                                                <?php if (!empty($log['error_message'])): ?>
                                                    <span
                                                        class="text-xs text-gray-500 block"><?= htmlspecialchars(substr($log['error_message'], 0, 50)) ?>...</span>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($campaign_details['logs'])): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                            No activity recorded yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh campaign status every 10 seconds
        setInterval(function () {
            if (window.location.href.indexOf('view=') === -1) {
                // Only refresh if we're not viewing a specific campaign
                fetch(window.location.href)
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const newDoc = parser.parseFromString(html, 'text/html');
                        const newTable = newDoc.querySelector('table');
                        if (newTable) {
                            document.querySelector('table').outerHTML = newTable.outerHTML;
                        }
                    });
            }
        }, 5000);
    </script>

</body>

</html>