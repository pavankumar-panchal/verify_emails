<?php
require_once 'db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

function getCampaignsWithStats()
{
  global $conn;

  // Fetch campaigns
  $query = "SELECT 
                cm.campaign_id, 
                cm.description, 
                cm.mail_subject
              FROM campaign_master cm
              ORDER BY cm.campaign_id DESC";
  $result = $conn->query($query);
  $campaigns = $result->fetch_all(MYSQLI_ASSOC);

  // Fetch total valid emails once
  $email_result = $conn->query("SELECT COUNT(*) AS valid_emails FROM emails WHERE domain_status = 1");
  $email_data = $email_result->fetch_assoc();
  $valid_email_count = $email_data['valid_emails'];

  // Attach the same count to every campaign
  foreach ($campaigns as &$campaign) {
    $campaign['valid_emails'] = $valid_email_count;

    // Calculate total distributed percentage for each campaign
    $dist_stmt = $conn->prepare("SELECT SUM(percentage) AS total_percentage 
                                   FROM campaign_distribution 
                                   WHERE campaign_id = ?");
    $dist_stmt->bind_param("i", $campaign['campaign_id']);
    $dist_stmt->execute();
    $dist_result = $dist_stmt->get_result();
    $dist_data = $dist_result->fetch_assoc();
    $campaign['distributed_percentage'] = $dist_data['total_percentage'] ?? 0;
    $campaign['remaining_percentage'] = 100 - $campaign['distributed_percentage'];

    // Get current distributions with email counts
    $dist_stmt = $conn->prepare("SELECT cd.smtp_id, cd.percentage, ss.name, 
                                FLOOR(? * cd.percentage / 100) AS email_count
                                FROM campaign_distribution cd
                                JOIN smtp_servers ss ON cd.smtp_id = ss.id
                                WHERE cd.campaign_id = ?");
    $dist_stmt->bind_param("ii", $valid_email_count, $campaign['campaign_id']);
    $dist_stmt->execute();
    $dist_result = $dist_stmt->get_result();
    $campaign['current_distributions'] = $dist_result->fetch_all(MYSQLI_ASSOC);
  }

  return $campaigns;
}

function getSMTPServers()
{
  global $conn;
  $query = "SELECT id, name, host, email, daily_limit, hourly_limit FROM smtp_servers WHERE is_active = 1";
  $result = $conn->query($query);
  return $result->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['distribute'])) {
  $campaign_id = (int) $_POST['campaign_id'];
  $distributions = $_POST['distribution'] ?? [];

  if (empty($distributions)) {
    $error = "Please add at least one SMTP distribution";
  } else {
    $conn->begin_transaction();

    try {
      // Validate distributions array
      $valid_distributions = [];
      $total_percentage = 0;

      foreach ($distributions as $dist) {
        if (!isset($dist['smtp_id']) || !isset($dist['percentage'])) {
          throw new Exception("Invalid distribution data - missing fields");
        }

        $smtp_id = (int) $dist['smtp_id'];
        $percentage = (float) $dist['percentage'];

        if ($smtp_id <= 0) {
          throw new Exception("Invalid SMTP server ID");
        }

        if ($percentage <= 0 || $percentage > 100) {
          throw new Exception("Percentage must be between 1 and 100");
        }

        $total_percentage += $percentage;
        $valid_distributions[] = [
          'smtp_id' => $smtp_id,
          'percentage' => $percentage
        ];
      }

      if ($total_percentage > 100) {
        throw new Exception("Total distribution percentage cannot exceed 100% (Current: $total_percentage%)");
      }

      // Get total valid emails for this campaign
      $email_result = $conn->query("SELECT COUNT(*) AS valid_emails FROM emails WHERE domain_status = 1");
      $email_data = $email_result->fetch_assoc();
      $valid_email_count = $email_data['valid_emails'];

      // Delete existing distributions for this campaign
      $delete_stmt = $conn->prepare("DELETE FROM campaign_distribution WHERE campaign_id = ?");
      $delete_stmt->bind_param("i", $campaign_id);
      $delete_stmt->execute();

      // Insert new distributions
      $insert_stmt = $conn->prepare("INSERT INTO campaign_distribution (campaign_id, smtp_id, percentage) VALUES (?, ?, ?)");

      foreach ($valid_distributions as $dist) {
        $insert_stmt->bind_param("iid", $campaign_id, $dist['smtp_id'], $dist['percentage']);
        if (!$insert_stmt->execute()) {
          throw new Exception("Failed to insert distribution: " . $conn->error);
        }
      }

      $conn->commit();
      $success = "Email distribution updated successfully!";
    } catch (Exception $e) {
      $conn->rollback();
      $error = "Error updating distribution: " . $e->getMessage();
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
  <title>Email Campaign Distribution</title>
  <link rel="stylesheet" href="assets/style_tailwind.css">
  <link rel="stylesheet" href="assets/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .campaign-table {
      max-height: 70vh;
      overflow-y: auto;
    }

    .distribution-row {
      min-height: 42px;
    }

    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }

    .email-count {
      font-size: 0.75rem;
      color: #6b7280;
      margin-left: 0.5rem;
    }
  </style>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <?php require "navbar.php"; ?>
  <div class="p-6 rounded w-full max-w-6xl">
    <h1 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
      <i class="fas fa-envelope-open-text mr-2 text-blue-500"></i>
      Email Campaign Distribution
    </h1>

    <?php if (isset($error)): ?>
      <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-4 rounded text-sm">
        <i class="fas fa-exclamation-circle mr-1"></i>
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
      <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-3 mb-4 rounded text-sm">
        <i class="fas fa-check-circle mr-1"></i>
        <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 campaign-table mt-4 w-full max-w-6xl">
      <div class="grid grid-cols-12 bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
        <div class="col-span-4 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Campaign
        </div>
        <div class="col-span-2 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Total Emails
        </div>
        <div class="col-span-6 px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          SMTP Distribution
        </div>
      </div>

      <?php foreach ($campaigns as $campaign): ?>
        <div class="grid grid-cols-12 border-b border-gray-200 last:border-b-0 hover:bg-gray-50 py-2">
          <!-- Campaign Column -->
          <div class="col-span-4 px-4 py-1">
            <div class="font-medium text-sm text-gray-900 truncate">
              <?php echo htmlspecialchars($campaign['description']); ?>
            </div>
            <div class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($campaign['mail_subject']); ?></div>
          </div>

          <!-- Emails Column -->
          <div class="col-span-2 px-4 py-1 flex items-center">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
              <?php echo number_format($campaign['valid_emails']); ?>
            </span>
          </div>

          <!-- Distribution Column -->
          <div class="col-span-6 px-4 py-1 ">
            <form method="POST" class="space-y-1">
              <input type="hidden" name="campaign_id" value="<?php echo $campaign['campaign_id']; ?>">

              <div id="distribution-container-<?php echo $campaign['campaign_id']; ?>" class="space-y-1">
                <?php foreach ($campaign['current_distributions'] as $index => $dist): ?>
                  <div class="flex items-center space-x-2 distribution-row">
                    <select name="distribution[<?php echo $index; ?>][smtp_id]"
                      class="w-32 text-sm border border-gray-300 rounded px-2 py-1 focus:ring-blue-500 focus:border-blue-500">
                      <?php foreach ($smtp_servers as $server): ?>
                        <option value="<?php echo $server['id']; ?>" <?php echo $dist['smtp_id'] == $server['id'] ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($server['name']); ?>
                        </option>
                      <?php endforeach; ?>
                    </select>

                    <div class="relative w-20">
                      <input type="number" name="distribution[<?php echo $index; ?>][percentage]" min="1"
                        max="<?php echo $campaign['remaining_percentage'] + $dist['percentage']; ?>" step="1"
                        value="<?php echo $dist['percentage']; ?>"
                        class="text-sm border border-gray-300 rounded px-2 py-1 pr-6 w-full focus:ring-blue-500 focus:border-blue-500"
                        onchange="updateEmailCount(this, <?php echo $campaign['valid_emails']; ?>)">
                      <span class="absolute right-2 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">%</span>
                    </div>
                    <span class="email-count"
                      id="email-count-<?php echo $campaign['campaign_id']; ?>-<?php echo $dist['smtp_id']; ?>">
                      ~<?php echo number_format($dist['email_count']); ?> emails
                    </span>
                    <button type="button" class="remove-distribution text-red-500 hover:text-red-700 text-sm">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>

              <!-- Add SMTP button and remaining percentage display -->
              <div class="flex items-center justify-between pt-1">
                <div class="flex space-x-2">
                  <button type="button"
                    onclick="addDistribution(<?php echo $campaign['campaign_id']; ?>, <?php echo $campaign['remaining_percentage']; ?>, <?php echo $campaign['valid_emails']; ?>)"
                    class="text-xs inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm rounded text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-plus mr-1 text-xs"></i> Add SMTP
                  </button>

                  <button type="submit" name="distribute"
                    class="text-xs inline-flex items-center px-2 py-1 border border-transparent rounded shadow-sm text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-save mr-1"></i> Save Distribution
                  </button>
                </div>

                <?php if ($campaign['remaining_percentage'] > 0): ?>
                  <span class="text-xs text-gray-500">
                    Remaining: <?php echo $campaign['remaining_percentage']; ?>%
                  </span>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <script>
    let distributionCounter = <?php echo count($campaigns[0]['current_distributions'] ?? []) ?> || 0;

    function addDistribution(campaignId, maxPercentage, totalEmails) {
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
      newRow.className = 'flex items-center space-x-2 distribution-row';
      const newId = Date.now(); // Unique ID for this row
      distributionCounter++;

      newRow.innerHTML = `
    <select name="distribution[${distributionCounter}][smtp_id]" 
  class="w-28 text-sm border border-gray-300 rounded px-2 py-1 focus:ring-blue-500 focus:border-blue-500">
  <?php foreach ($smtp_servers as $server): ?>
    <option value="<?php echo $server['id']; ?>">
      <?php echo htmlspecialchars($server['name']); ?>
    </option>
  <?php endforeach; ?>
</select>

        <div class="relative w-20">
            <input type="number" name="distribution[${distributionCounter}][percentage]" min="1" max="${availablePercentage}" 
                   step="1" value="${Math.min(10, availablePercentage)}" 
                   class="text-sm border border-gray-300 rounded px-2 py-1 pr-6 w-full focus:ring-blue-500 focus:border-blue-500"
                   onchange="updateEmailCount(this, ${totalEmails})">
            <span class="absolute right-2 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">%</span>
        </div>
        <span class="email-count" id="email-count-${campaignId}-${newId}">
          ~${Math.floor(totalEmails * Math.min(10, availablePercentage) / 100)} emails
        </span>
        <button type="button" class="remove-distribution text-red-500 hover:text-red-700 text-sm">
            <i class="fas fa-times"></i>
        </button>
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
    }

    document.addEventListener('click', function (e) {
      if (e.target.classList.contains('remove-distribution') || e.target.closest('.remove-distribution')) {
        const row = e.target.closest('.distribution-row');
        if (row) {
          row.remove();
        }
      }
    });

    // Validate percentage inputs
    document.addEventListener('input', function (e) {
      if (e.target.name && e.target.name.includes('percentage') && e.target.value) {
        // Ensure numeric value
        e.target.value = e.target.value.replace(/[^0-9]/g, '');

        // Get max allowed value from input's max attribute
        const max = parseFloat(e.target.max) || 100;
        if (e.target.value > max) e.target.value = max;
        if (e.target.value < 1) e.target.value = 1;
      }
    });

    // Validate form submission
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', function (e) {
        if (e.submitter && e.submitter.name === 'distribute') {
          const percentageInputs = this.querySelectorAll('input[name^="distribution"][name$="[percentage]"]');
          let total = 0;

          percentageInputs.forEach(input => {
            total += parseFloat(input.value) || 0;
          });

          if (total > 100) {
            e.preventDefault();
            alert(`Total distribution percentage cannot exceed 100% (Current: ${total}%)`);
          }
        }
      });
    });
  </script>
</body>

</html>