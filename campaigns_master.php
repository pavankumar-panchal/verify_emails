<?php
require_once 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set higher limits for large email processing
ini_set('memory_limit', '2048M');
set_time_limit(0);

// Initialize message variables
$message = '';
$message_type = '';

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
    } elseif (isset($_POST['distribute'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        $distributions = $_POST['distribution'] ?? [];
        
        $conn->begin_transaction();
        try {
            // Delete existing distributions
            $delete_stmt = $conn->prepare("DELETE FROM campaign_distribution WHERE campaign_id = ?");
            $delete_stmt->bind_param("i", $campaign_id);
            $delete_stmt->execute();
            
            // Insert new distributions
            $insert_stmt = $conn->prepare("INSERT INTO campaign_distribution (campaign_id, smtp_id, percentage) VALUES (?, ?, ?)");
            
            $total_percentage = 0;
            foreach ($distributions as $dist) {
                if (!isset($dist['smtp_id']) || !isset($dist['percentage'])) {
                    throw new Exception("Invalid distribution data");
                }
                
                $smtp_id = (int)$dist['smtp_id'];
                $percentage = (float)$dist['percentage'];
                $total_percentage += $percentage;
                
                $insert_stmt->bind_param("iid", $campaign_id, $smtp_id, $percentage);
                $insert_stmt->execute();
            }
            
            if ($total_percentage > 100) {
                throw new Exception("Total distribution cannot exceed 100%");
            }
            
            $conn->commit();
            $message = "Distribution saved successfully!";
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error: " . $e->getMessage();
            $message_type = 'error';
        }
    } elseif (isset($_POST['auto_distribute'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        $email_result = $conn->query("SELECT COUNT(*) AS total FROM emails WHERE domain_status = 1");
        $email_data = $email_result->fetch_assoc();
        $total_emails = $email_data['total'];
        
        $smtp_servers = getSMTPServers();
        $optimal_distribution = calculateOptimalDistribution($total_emails, $smtp_servers);
        
        // Save the optimal distribution
        $conn->begin_transaction();
        try {
            $delete_stmt = $conn->prepare("DELETE FROM campaign_distribution WHERE campaign_id = ?");
            $delete_stmt->bind_param("i", $campaign_id);
            $delete_stmt->execute();
            
            $insert_stmt = $conn->prepare("INSERT INTO campaign_distribution (campaign_id, smtp_id, percentage) VALUES (?, ?, ?)");
            
            foreach ($optimal_distribution as $dist) {
                $insert_stmt->bind_param("iid", $campaign_id, $dist['smtp_id'], $dist['percentage']);
                $insert_stmt->execute();
            }
            
            $conn->commit();
            $message = "Optimal distribution calculated and saved!";
            $message_type = 'success';
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error saving optimal distribution: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}

function getCampaignsWithStats() {
    global $conn;
    
    // Fetch campaigns with additional stats
    $query = "SELECT 
                cm.campaign_id, 
                cm.description, 
                cm.mail_subject,
                (SELECT COUNT(*) FROM emails WHERE domain_status = 1) AS valid_emails,
                (SELECT SUM(percentage) FROM campaign_distribution WHERE campaign_id = cm.campaign_id) AS distributed_percentage,
                cs.status as campaign_status,
                COALESCE(cs.total_emails, 0) as total_emails,
                COALESCE(cs.pending_emails, 0) as pending_emails,
                COALESCE(cs.sent_emails, 0) as sent_emails,
                COALESCE(cs.failed_emails, 0) as failed_emails,
                cs.start_time,
                cs.end_time
              FROM campaign_master cm
              LEFT JOIN campaign_status cs ON cm.campaign_id = cs.campaign_id
              ORDER BY cm.campaign_id DESC";
    $result = $conn->query($query);
    $campaigns = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($campaigns as &$campaign) {
        $campaign['remaining_percentage'] = 100 - ($campaign['distributed_percentage'] ?? 0);
        
        // Calculate progress percentage
        $total = max($campaign['total_emails'], 1);
        $sent = min($campaign['sent_emails'], $total);
        $campaign['progress'] = round(($sent / $total) * 100);
        
        // Get current distributions with email counts
        $dist_stmt = $conn->prepare("SELECT 
                                    cd.smtp_id, 
                                    cd.percentage, 
                                    ss.name,
                                    ss.daily_limit,
                                    ss.hourly_limit,
                                    FLOOR(? * cd.percentage / 100) AS email_count
                                FROM campaign_distribution cd
                                JOIN smtp_servers ss ON cd.smtp_id = ss.id
                                WHERE cd.campaign_id = ?");
        $dist_stmt->bind_param("ii", $campaign['valid_emails'], $campaign['campaign_id']);
        $dist_stmt->execute();
        $dist_result = $dist_stmt->get_result();
        $campaign['current_distributions'] = $dist_result->fetch_all(MYSQLI_ASSOC);
    }

    return $campaigns;
}

function getSMTPServers() {
    global $conn;
    $query = "SELECT id, name, host, email, daily_limit, hourly_limit FROM smtp_servers WHERE is_active = 1";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function calculateOptimalDistribution($total_emails, $smtp_servers) {
    $distribution = [];
    $total_capacity = 0;
    
    // Calculate total available capacity
    foreach ($smtp_servers as $server) {
        $daily_capacity = min($server['daily_limit'], $server['hourly_limit'] * 24);
        $total_capacity += $daily_capacity;
    }
    
    // Distribute emails proportionally to each SMTP's capacity
    if ($total_capacity > 0) {
        foreach ($smtp_servers as $server) {
            $daily_capacity = min($server['daily_limit'], $server['hourly_limit'] * 24);
            $percentage = ($daily_capacity / $total_capacity) * 100;
            $distribution[] = [
                'smtp_id' => $server['id'],
                'percentage' => round($percentage, 2),
                'email_count' => floor($total_emails * $percentage / 100)
            ];
        }
    }
    
    return $distribution;
}

function startCampaign($conn, $campaign_id) {
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
                sleep(1); // Wait before retrying

                if ($retry_count >= $max_retries) {
                    $message = "Failed to start campaign #$campaign_id after $max_retries attempts due to lock timeout";
                    $message_type = 'error';
                }
            } else {
                // Other database errors
                $message = "Database error starting campaign #$campaign_id: " . $e->getMessage();
                $message_type = 'error';
                break;
            }
        }
    }

    // Reset to default timeout
    $conn->query("SET SESSION innodb_lock_wait_timeout = 50");
}

function getEmailCounts($conn, $campaign_id) {
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

function startEmailBlasterProcess($campaign_id) {
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
    $php_path = '/opt/lampp/bin/php';
    $script_path = __DIR__ . '/email_blaster.php';

    // Start new background process
    $command = "nohup $php_path $script_path $campaign_id > /dev/null 2>&1 & echo $!";
    $pid = shell_exec($command);

    // Save PID to lock file
    if ($pid) {
        file_put_contents($lock_file, trim($pid));
    }
}

function pauseCampaign($conn, $campaign_id) {
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
                sleep(1); // Wait before retrying

                if ($retry_count >= $max_retries) {
                    $message = "Failed to pause campaign #$campaign_id after $max_retries attempts due to lock timeout";
                    $message_type = 'error';
                }
            } else {
                // Other database errors
                $message = "Database error pausing campaign #$campaign_id: " . $e->getMessage();
                $message_type = 'error';
                break;
            }
        }
    }

    // Reset to default timeout
    $conn->query("SET SESSION innodb_lock_wait_timeout = 50");
}

function stopEmailBlasterProcess($campaign_id) {
    // Find and kill the process
    exec("pkill -f 'email_blaster.php $campaign_id'");
}

function retryFailedEmails($conn, $campaign_id) {
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

$campaigns = getCampaignsWithStats();
$smtp_servers = getSMTPServers();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Email Campaign Manager</title>
    <link rel="stylesheet" href="assets/style_tailwind.css">
    <link rel="stylesheet" href="assets/main.css">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .campaign-card {
            transition: all 0.3s ease;
        }
        .campaign-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .smtp-progress {
            height: 8px;
            border-radius: 4px;
        }
        .distribution-row {
            transition: background-color 0.2s;
        }
        .distribution-row:hover {
            background-color: #f8fafc;
        }
        .email-count-badge {
            font-size: 0.7rem;
        }
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
        .alert-success {
            background-color: #d1fae5;
            border-color: #10b981;
            color: #065f46;
        }
        .alert-error {
            background-color: #fee2e2;
            border-color: #ef4444;
            color: #991b1b;
        }
        .alert-info {
            background-color: #dbeafe;
            border-color: #3b82f6;
            color: #1e40af;
        }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <?php require "navbar.php"; ?>
    
    <div class="container mx-auto px-12 py-6 w-full max-w-7xl">
    <?php if ($message): ?>
        <div class="alert-<?= $message_type ?> p-4 mb-6 rounded-md shadow-sm flex items-start border-l-4">
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

    <div class="grid grid-cols-1 gap-6 max-w-6xl">
        <?php foreach ($campaigns as $campaign): ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden campaign-card">
                <div class="p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-1">
                                <?php echo htmlspecialchars($campaign['description']); ?>
                            </h2>
                            <p class="text-sm text-gray-600 mb-2">
                                <?php echo htmlspecialchars($campaign['mail_subject']); ?>
                            </p>
                            <div class="flex items-center space-x-4">
                                <span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-800 text-sm font-medium">
                                    <i class="fas fa-envelope mr-1"></i>
                                    <?php echo number_format($campaign['valid_emails']); ?> Emails
                                </span>
                                <?php if ($campaign['remaining_percentage'] > 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-yellow-100 text-yellow-800 text-sm font-medium">
                                        <i class="fas fa-clock mr-1"></i>
                                        <?php echo $campaign['remaining_percentage']; ?>% Remaining
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Fully Allocated
                                    </span>
                                <?php endif; ?>
                                <span class="status-badge status-<?= strtolower($campaign['campaign_status'] ?? 'pending') ?>">
                                    <?= $campaign['campaign_status'] ?? 'Not started' ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex space-x-2 items-center">
                            <button onclick="toggleCampaignDetails(<?php echo $campaign['campaign_id']; ?>)" 
                                class="text-gray-500 hover:text-gray-700 px-2 py-1 rounded-lg">
                                <i class="fas fa-chevron-down text-sm" id="toggle-icon-<?php echo $campaign['campaign_id']; ?>"></i>
                            </button>
                            <form method="POST" class="flex space-x-2">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['campaign_id']; ?>">
                                <button type="submit" name="auto_distribute" 
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-magic mr-1"></i> Auto-Distribute
                                </button>
                                <?php if (($campaign['campaign_status'] ?? '') === 'running'): ?>
                                    <button type="submit" name="pause_campaign"
                                        class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-pause mr-1"></i> Pause
                                    </button>
                                <?php elseif (($campaign['campaign_status'] ?? '') === 'completed'): ?>
                                    <span class="px-4 py-2 bg-gray-200 text-gray-600 rounded-lg text-sm font-medium">
                                        <i class="fas fa-check-circle mr-1"></i> Completed
                                    </span>
                                <?php else: ?>
                                    <button type="submit" name="start_campaign"
                                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-play mr-1"></i> Start
                                    </button>
                                <?php endif; ?>
                                <?php if (($campaign['failed_emails'] ?? 0) > 0 && ($campaign['campaign_status'] ?? '') !== 'completed'): ?>
                                    <button type="submit" name="retry_failed" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-redo mr-1"></i> Retry Failed
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <div id="campaign-details-<?php echo $campaign['campaign_id']; ?>" class="mt-6 hidden">
                        <form method="POST">
                            <input type="hidden" name="campaign_id" value="<?php echo $campaign['campaign_id']; ?>">
                            
                            <div id="distribution-container-<?php echo $campaign['campaign_id']; ?>" class="space-y-3 mb-4">
                                <?php foreach ($campaign['current_distributions'] as $index => $dist): ?>
                                    <div class="distribution-row flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                                        <select name="distribution[<?php echo $index; ?>][smtp_id]" 
                                            class="flex-1 min-w-0 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                                            <?php foreach ($smtp_servers as $server): ?>
                                                <option value="<?php echo $server['id']; ?>" 
                                                    <?php echo $dist['smtp_id'] == $server['id'] ? 'selected' : ''; ?>
                                                    data-daily-limit="<?php echo $server['daily_limit']; ?>"
                                                    data-hourly-limit="<?php echo $server['hourly_limit']; ?>">
                                                    <?php echo htmlspecialchars($server['name']); ?>
                                                    (<?php echo number_format($server['daily_limit']); ?>/day)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        
                                        <div class="relative w-32">
                                            <input type="number" name="distribution[<?php echo $index; ?>][percentage]" min="1"
                                                max="<?php echo $campaign['remaining_percentage'] + $dist['percentage']; ?>" step="0.1"
                                                value="<?php echo $dist['percentage']; ?>"
                                                class="text-sm border border-gray-300 rounded-lg px-3 py-2 pr-8 w-full focus:ring-blue-500 focus:border-blue-500"
                                                onchange="updateEmailCount(this, <?php echo $campaign['valid_emails']; ?>)">
                                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">%</span>
                                        </div>
                                        
                                        <div class="flex items-center space-x-2">
                                            <span class="email-count bg-gray-200 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                ~<?php echo number_format($dist['email_count']); ?> emails
                                            </span>
                                            <button type="button" class="remove-distribution text-red-500 hover:text-red-700">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <button type="button"
                                    onclick="addDistribution(<?php echo $campaign['campaign_id']; ?>, <?php echo $campaign['remaining_percentage']; ?>, <?php echo $campaign['valid_emails']; ?>)"
                                    class="px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-plus mr-1"></i> Add SMTP Server
                                </button>
                                
                                <div class="flex space-x-3">
                                    <span class="text-sm text-gray-600">
                                        <?php if ($campaign['remaining_percentage'] > 0): ?>
                                            <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                                            <?php echo $campaign['remaining_percentage']; ?>% remaining to allocate
                                        <?php else: ?>
                                            <!-- <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                            Fully allocated -->
                                        <?php endif; ?>
                                    </span>
                                    <button type="submit" name="distribute"
                                        class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                                        <i class="fas fa-save mr-1"></i> Save Distribution
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

    <script>
        let distributionCounters = {};
        
        function addDistribution(campaignId, maxPercentage, totalEmails) {
            if (!distributionCounters[campaignId]) {
                distributionCounters[campaignId] = 0;
            }
            
            const container = document.getElementById(`distribution-container-${campaignId}`);
            const remainingPercentage = maxPercentage || 100;

            // Calculate current total percentage
            let currentTotal = 0;
            container.querySelectorAll('input[name^="distribution"][name$="[percentage]"]').forEach(input => {
                currentTotal += parseFloat(input.value) || 0;
            });

            const availablePercentage = 100 - currentTotal;
            if (availablePercentage <= 0) {
                alert('You have already allocated 100% of emails');
                return;
            }

            const newRow = document.createElement('div');
            newRow.className = 'distribution-row flex items-center space-x-4 p-3 bg-gray-50 rounded-lg';
            distributionCounters[campaignId]++;
            const newIndex = distributionCounters[campaignId];

            newRow.innerHTML = `
                <select name="distribution[${newIndex}][smtp_id]" 
                    class="flex-1 min-w-0 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php foreach ($smtp_servers as $server): ?>
                        <option value="<?php echo $server['id']; ?>"
                            data-daily-limit="<?php echo $server['daily_limit']; ?>"
                            data-hourly-limit="<?php echo $server['hourly_limit']; ?>">
                            <?php echo htmlspecialchars($server['name']); ?>
                            (<?php echo number_format($server['daily_limit']); ?>/day)
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <div class="relative w-32">
                    <input type="number" name="distribution[${newIndex}][percentage]" min="1" 
                        max="${availablePercentage}" step="0.1" value="${Math.min(100, availablePercentage).toFixed(1)}" 
                        class="text-sm border border-gray-300 rounded-lg px-3 py-2 pr-8 w-full focus:ring-blue-500 focus:border-blue-500"
                        onchange="updateEmailCount(this, ${totalEmails})">
                    <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">%</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <span class="email-count bg-gray-200 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                        ~${Math.floor(totalEmails * Math.min(10, availablePercentage) / 100).toLocaleString()} emails
                    </span>
                    <button type="button" class="remove-distribution text-red-500 hover:text-red-700">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
            
            container.appendChild(newRow);
            
            // Focus the new percentage input
            const newInput = newRow.querySelector('input[type="number"]');
            if (newInput) {
                newInput.focus();
                newInput.select();
            }
        }

        function updateEmailCount(inputElement, totalEmails) {
            const percentage = parseFloat(inputElement.value) || 0;
            const row = inputElement.closest('.distribution-row');
            const emailCountSpan = row.querySelector('.email-count');
            const emailCount = Math.floor(totalEmails * percentage / 100);
            emailCountSpan.textContent = `~${emailCount.toLocaleString()} emails`;
            
            // Validate against SMTP limits
            const select = row.querySelector('select');
            const dailyLimit = parseFloat(select.selectedOptions[0].dataset.dailyLimit) || 0;
            const hourlyLimit = parseFloat(select.selectedOptions[0].dataset.hourlyLimit) || 0;
            
            if (emailCount > dailyLimit) {
                emailCountSpan.classList.add('bg-red-100', 'text-red-800');
                emailCountSpan.classList.remove('bg-gray-200', 'text-gray-800');
                emailCountSpan.innerHTML += ' <i class="fas fa-exclamation-triangle"></i> Exceeds daily limit';
            } else if (emailCount > hourlyLimit * 24) {
                emailCountSpan.classList.add('bg-yellow-100', 'text-yellow-800');
                emailCountSpan.classList.remove('bg-gray-200', 'text-gray-800');
                emailCountSpan.innerHTML += ' <i class="fas fa-exclamation-circle"></i> Review hourly limit';
            } else {
                emailCountSpan.classList.add('bg-gray-200', 'text-gray-800');
                emailCountSpan.classList.remove('bg-red-100', 'text-red-800', 'bg-yellow-100', 'text-yellow-800');
                emailCountSpan.textContent = `~${emailCount.toLocaleString()} emails`;
            }
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-distribution') || e.target.closest('.remove-distribution')) {
                const row = e.target.closest('.distribution-row');
                if (row) {
                    row.remove();
                }
            }
        });

        // Validate percentage inputs
        document.addEventListener('input', function(e) {
            if (e.target.name && e.target.name.includes('percentage') && e.target.value) {
                // Ensure numeric value
                e.target.value = e.target.value.replace(/[^0-9.]/g, '');
                
                // Get max allowed value from input's max attribute
                const max = parseFloat(e.target.max) || 100;
                if (e.target.value > max) e.target.value = max;
                if (e.target.value < 1) e.target.value = 1;
            }
        });

        // Validate form submission
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                if (e.submitter && e.submitter.name === 'distribute') {
                    const percentageInputs = this.querySelectorAll('input[name^="distribution"][name$="[percentage]"]');
                    let total = 0;
                    
                    percentageInputs.forEach(input => {
                        total += parseFloat(input.value) || 0;
                    });
                    
                    if (total > 100) {
                        e.preventDefault();
                        alert(`Total distribution percentage cannot exceed 100% (Current: ${total.toFixed(1)}%)`);
                        return false;
                    }
                    
                    // Check SMTP limits
                    let limitExceeded = false;
                    const rows = this.querySelectorAll('.distribution-row');
                    
                    rows.forEach(row => {
                        const select = row.querySelector('select');
                        const percentageInput = row.querySelector('input[name$="[percentage]"]');
                        const emailCountSpan = row.querySelector('.email-count');
                        
                        const dailyLimit = parseFloat(select.selectedOptions[0].dataset.dailyLimit) || 0;
                        const percentage = parseFloat(percentageInput.value) || 0;
                        const emailCount = Math.floor(<?php echo $email_data['total'] ?? 0; ?> * percentage / 100);
                        
                        if (emailCount > dailyLimit) {
                            emailCountSpan.classList.add('bg-red-100', 'text-red-800');
                            emailCountSpan.classList.remove('bg-gray-200', 'text-gray-800');
                            emailCountSpan.innerHTML = `~${emailCount.toLocaleString()} emails <i class="fas fa-exclamation-triangle"></i> Exceeds daily limit`;
                            limitExceeded = true;
                        }
                    });
                    
                    if (limitExceeded) {
                        e.preventDefault();
                        alert('One or more SMTP distributions exceed daily limits. Please adjust percentages.');
                        return false;
                    }
                }
                return true;
            });
        });

        setTimeout(function() {
            const msg = document.getElementById('success-message');
            if (msg) {
                msg.classList.add('opacity-0');
                setTimeout(() => msg.remove(), 500); // Remove after fade-out
            }
        }, 3000);


        function toggleCampaignDetails(campaignId) {
    const detailsDiv = document.getElementById(`campaign-details-${campaignId}`);
    const toggleIcon = document.getElementById(`toggle-icon-${campaignId}`);
    
    if (detailsDiv.classList.contains('hidden')) {
        detailsDiv.classList.remove('hidden');
        toggleIcon.classList.remove('fa-chevron-down');
        toggleIcon.classList.add('fa-chevron-up');
    } else {
        detailsDiv.classList.add('hidden');
        toggleIcon.classList.remove('fa-chevron-up');
        toggleIcon.classList.add('fa-chevron-down');
    }
}
    </script>

    </body>
    </html>
