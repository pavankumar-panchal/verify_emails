<?php
require_once 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set higher limits for large email processing
ini_set('memory_limit', '2048M');
set_time_limit(0);

function getCampaignsWithStats() {
    global $conn;
    
    // Fetch campaigns with additional stats
    $query = "SELECT 
                cm.campaign_id, 
                cm.description, 
                cm.mail_subject,
                (SELECT COUNT(*) FROM emails WHERE domain_status = 1) AS valid_emails,
                (SELECT SUM(percentage) FROM campaign_distribution WHERE campaign_id = cm.campaign_id) AS distributed_percentage
              FROM campaign_master cm
              ORDER BY cm.campaign_id DESC";
    $result = $conn->query($query);
    $campaigns = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($campaigns as &$campaign) {
        $campaign['remaining_percentage'] = 100 - ($campaign['distributed_percentage'] ?? 0);
        
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['distribute'])) {
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
            $success = "Distribution saved successfully!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error: " . $e->getMessage();
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
            $success = "Optimal distribution calculated and saved!";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error saving optimal distribution: " . $e->getMessage();
        }
    } elseif (isset($_POST['start_campaign'])) {
        $campaign_id = (int)$_POST['campaign_id'];
        
        // In a real system, this would queue the campaign for processing
        $stmt = $conn->prepare("UPDATE campaign_master SET status = 'processing', start_time = NOW() WHERE campaign_id = ?");
        $stmt->bind_param("i", $campaign_id);
        if ($stmt->execute()) {
            $success = "Campaign queued for processing! Emails will be sent in batches.";
        } else {
            $error = "Error starting campaign: " . $conn->error;
        }
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
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .campaign-card {
            transition: all 0.3s ease;
        }
        .campaign-card:hover {
            transform: translateY(-2px);
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
    </style>
</head>


<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <?php require "navbar.php"; ?>
    
    <div class="container mx-auto px-12 py-6 w-full max-w-7xl ">
             <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded max-w-6xl"  id="success-message" >
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
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
                                </div>
                            </div>
                            <form method="POST" class="flex space-x-2">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['campaign_id']; ?>">
                                <button type="submit" name="auto_distribute" 
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-magic mr-1"></i> Auto-Distribute
                                </button>
                                <button type="submit" name="start_campaign" 
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-play mr-1"></i> Start Campaign
                                </button>
                            </form>
                        </div>

                        <div class="mt-6">
                            <form method="POST">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['campaign_id']; ?>">
                                
                                <h3 class="text-lg font-medium text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-server mr-2 text-blue-500"></i>
                                    SMTP Distribution Plan
                                </h3>
                                
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
                                                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                                                Fully allocated
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
                        max="${availablePercentage}" step="0.1" value="${Math.min(10, availablePercentage).toFixed(1)}" 
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
    </script>
</body>
</html>