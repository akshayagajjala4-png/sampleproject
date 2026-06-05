<?php

require_once __DIR__ . '/../config/mailer.php';

// Test send to yourself
$result = sendOtpEmail('ibabaprakurddin@gmail.com', 'Test User');

echo '<pre>';
if ($result) {
    echo "✅ SUCCESS! OTP sent: " . $result;
} else {
    echo "❌ FAILED — check config/mail_error.log for details\n\n";
    $log = @file_get_contents(__DIR__ . '/../config/mail_error.log');
    echo $log ?: "No log file found — cURL may not be running at all";
}
echo '</pre>';