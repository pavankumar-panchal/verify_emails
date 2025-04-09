<?php
// includes/progress.php
header('Content-Type: application/json');
session_start();

echo json_encode([
    'total' => $_SESSION['total_emails'] ?? 0,
    'completed' => $_SESSION['emails_verified'] ?? 0
]);
