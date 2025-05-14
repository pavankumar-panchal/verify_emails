<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Email Distribution</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6 mt-12">
<?php  include "navbar.php"; ?>


  <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl p-6">
    <h2 class="text-2xl font-bold mb-4">Email Distribution Settings</h2>
    
    <form method="POST" action="distribute_emails.php" class="space-y-4">
      <!-- SMTP list (dynamically rendered from DB in PHP) -->
      <div id="smtpContainer">
        <div class="flex items-center space-x-4">
          <label class="w-1/2">SMTP #1 ID: <input type="number" name="smtp_ids[]" class="border rounded px-2 py-1 w-full" value="1" required></label>
          <label class="w-1/2">Percentage: <input type="number" name="percentages[]" class="border rounded px-2 py-1 w-full" value="50" required></label>
        </div>
        <div class="flex items-center space-x-4 mt-2">
          <label class="w-1/2">SMTP #2 ID: <input type="number" name="smtp_ids[]" class="border rounded px-2 py-1 w-full" value="2" required></label>
          <label class="w-1/2">Percentage: <input type="number" name="percentages[]" class="border rounded px-2 py-1 w-full" value="50" required></label>
        </div>
      </div>

      <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Distribute Emails</button>
    </form>
  </div>
</body>
</html>
