<?php
// Database connection to get SMTP servers
// Database connection - change to "email_id" to match your database
$conn = new mysqli("localhost", "root", "", "email_id");
if ($conn->connect_error) {
  die("Database connection failed: " . $conn->connect_error);
}

// Get active SMTP servers
$result = $conn->query("SELECT * FROM smtp_servers WHERE is_active = TRUE");
$smtpServers = [];
while ($row = $result->fetch_assoc()) {
  $smtpServers[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Bulk Emails</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Google Sans', Roboto, Arial, sans-serif;
      background: #f6f8fc;
    }

    .navbar {
      background-color: #ffffff;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      padding: 1rem 2rem;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 100;
    }

    .navbar-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
    }

    .navbar-brand {
      font-size: 1.25rem;
      font-weight: 600;
      color: #3b82f6;
      text-decoration: none;
    }

    .navbar-links {
      display: flex;
      gap: 1rem;
    }

    .nav-link {
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      font-weight: 500;
      transition: all 0.2s;
    }

    .nav-link:hover {
      background-color: #f3f4f6;
    }

    .nav-link.active {
      background-color: #3b82f6;
      color: white;
    }

    .nav-link.active:hover {
      background-color: #2563eb;
    }

    /* Progress overlay styles */
    .progress-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(211, 211, 211, 0.18);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      backdrop-filter: blur(5px);
    }

    .circle-loader {
      position: relative;
      width: 180px;
      height: 180px;
      margin-bottom: 1rem;
    }

    .circle-loader svg {
      transform: rotate(-90deg);
    }

    .circle-loader circle {
      fill: none;
      stroke-width: 10;
      stroke-linecap: round;
    }

    .circle-bg {
      stroke: #e6e6e6;
    }

    .circle-progress {
      stroke: #3b82f6;
      transition: stroke-dashoffset 0.5s ease;
    }

    .loader-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 1.5rem;
      font-weight: bold;
      color: #333;
    }

    .progress-label {
      font-size: 1.2rem;
      color: #555;
      font-weight: 500;
      margin-top: 1rem;
    }

    .hidden {
      display: none !important;
    }

    /* Adjust main content to account for fixed navbar */
    .main-content {
      margin-top: 80px;
    }

    .email-container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 1px 2px 0 rgba(60, 64, 67, 0.302), 0 2px 6px 2px rgba(60, 64, 67, 0.149);
      margin-top: 80px;
    }

    .progress-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 1000;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <nav class="navbar">
    <div class="navbar-container">
      <a href="#" class="navbar-brand">
        <i class="fas fa-envelope mr-2"></i>Email
      </a>
      <div class="navbar-links">
        <a href="index.php" class="nav-link ">
          <i class="fas fa-check-circle mr-2"></i>Verification
        </a>
        <a href="#" class="nav-link active">
          <i class="fas fa-paper-plane mr-2"></i>Send Emails
        </a>
      </div>
    </div>
  </nav>
  <!-- Progress Overlay -->
  <div id="progressOverlay" class="progress-overlay hidden">
    <div class="bg-white p-6 rounded-lg max-w-md w-full">
      <h3 class="text-lg font-medium mb-4" id="progressMessage">Sending emails...</h3>
      <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
      </div>
      <div class="mt-2 text-sm text-gray-600" id="progressText">0% (0/0)</div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="container mx-auto px-4 max-w-4xl">
    <div class="email-container p-6 mt-18">
      <h1 class="text-2xl font-normal text-gray-800 mb-6 flex items-center">
        <i class="fas fa-paper-plane mr-3 text-blue-500"></i>
        Send Bulk Emails
      </h1>

      <form id="emailForm" enctype="multipart/form-data" class="space-y-4">
        <!-- SMTP Server Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Server</label>

          <select name="selected_smtp_server" id="smtpServerSelect" class="border rounded p-2 w-full">
            <option value="">-- Select SMTP Server --</option>
            <?php foreach ($smtpServers as $server): ?>
              <option value="<?= $server['id'] ?>">
                <?= htmlspecialchars($server['name']) ?> (<?= htmlspecialchars($server['email']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Sender Name -->
        <div class="flex items-center border-b pb-2">
          <span class="text-sm text-gray-500 w-24">Sender Name:</span>
          <input type="text" name="sender_name" required class="flex-1 py-2 px-1 focus:outline-none"
            placeholder="Your Name" value="Bulk Email Sender">
        </div>

        <!-- Subject -->
        <div class="flex items-center border-b pb-2">
          <span class="text-sm text-gray-500 w-24">Subject:</span>
          <input type="text" name="subject" required class="flex-1 py-2 px-1 focus:outline-none"
            placeholder="Email Subject">
        </div>

        <!-- Email Body -->
        <div class="mt-4">
          <textarea name="body" rows="8" required
            class="w-full p-3 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="Compose your email here..."></textarea>
        </div>

        <!-- Attachments -->
        <div class="mt-4">
          <div id="attachment-container" class="flex flex-wrap gap-2 mb-2"></div>
          <input type="file" id="file-input" name="attachments[]" multiple style="display: none;">
          <button type="button" id="add-attachment" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
            <i class="fas fa-paperclip mr-1"></i> Add Attachment
          </button>
        </div>

        <!-- Send Button -->
        <div class="flex justify-end pt-4">
          <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
            Send Emails
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>

    function sendEmails() {
      var formData = new FormData(document.getElementById("emailForm"));

      var xhr = new XMLHttpRequest();
      xhr.open("POST", "send_email.php", true);

      // Progress event to show file upload progress
      xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
          var percent = (e.loaded / e.total) * 100;
          document.getElementById("progress").style.width = percent + "%";
        }
      });

      // Response event to show email sending status
      xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
          var response = JSON.parse(xhr.responseText);
          if (response.status === 'success') {
            alert("Sent: " + response.sent + ", Failed: " + response.failed);
          }
        }
      };

      xhr.send(formData);
    }






    // File attachments handling
    document.getElementById('add-attachment').addEventListener('click', () => {
      document.getElementById('file-input').click();
    });

    document.getElementById('file-input').addEventListener('change', function (e) {
      const container = document.getElementById('attachment-container');
      container.innerHTML = '';

      Array.from(e.target.files).forEach(file => {
        const chip = document.createElement('div');
        chip.className = 'bg-blue-50 text-blue-800 px-3 py-1 rounded-full text-sm flex items-center';
        chip.innerHTML = `
          <span>${file.name}</span>
          <button type="button" class="ml-2 text-blue-600" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
          </button>
        `;
        container.appendChild(chip);
      });
    });

    // Form submission
    document.getElementById('emailForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const selectedServer = document.getElementById('smtpServerSelect').value;

      if (!selectedServer) {
        alert('Please select an SMTP server');
        return;
      }

      // Show progress overlay
      const overlay = document.getElementById('progressOverlay');
      overlay.classList.remove('hidden');

      fetch('send_email.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.status === 'success') {
            updateProgress(data.sent, data.sent + data.failed);
            document.getElementById('progressMessage').textContent =
              `Sent ${data.sent} emails successfully! ${data.failed} failed.`;

            if (data.failed > 0) {
              setTimeout(() => {
                overlay.classList.add('hidden');
                alert(`${data.failed} emails failed to send. Check the log for details.`);
              }, 3000);
            } else {
              setTimeout(() => overlay.classList.add('hidden'), 2000);
            }
          } else {
            throw new Error(data.message || 'Error sending emails');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          document.getElementById('progressMessage').textContent = 'Error: ' + error.message;
          document.getElementById('progressMessage').className += ' text-red-600';
        });
    });

    function updateProgress(processed, total) {
      const percent = Math.round((processed / total) * 100);
      document.getElementById('progressBar').style.width = percent + '%';
      document.getElementById('progressText').textContent = `${percent}% (${processed}/${total})`;
    }
  </script>
</body>

</html>