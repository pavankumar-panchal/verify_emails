// trigger_domain_verification.php
<?php
// Start the process in the background
exec("nohup php /opt/lampp/htdocs/email/includes/verify_domain.php > /dev/null 2>&1 &");

// Return success response
echo json_encode(['status' => 'success']);
?>
