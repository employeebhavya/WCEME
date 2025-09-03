<?php
// Test PHPMailer configuration
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

header('Content-Type: application/json');

try {
    $mail = new PHPMailer(true);

    // Enable SMTP debugging (set to 2 for detailed output)
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function ($str, $level) {
        error_log("SMTP Debug: " . $str);
    };

    // Test SMTP settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'riturajsingh3001@gmail.com';
    $mail->Password = 'wzggtgcoklhmthuj';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Add timeout settings
    $mail->Timeout = 60;
    $mail->SMTPKeepAlive = true;

    // Test email
    $mail->setFrom('riturajsingh3001@gmail.com', 'WCEME 2025 Test');
    $mail->addAddress('romansingh90633@gmail.com', 'Test Recipient');
    $mail->Subject = 'WCEME 2025 - Mail Configuration Test ' . date('Y-m-d H:i:s');
    $mail->Body = 'This is a test email to verify PHPMailer configuration is working correctly. Timestamp: ' . date('Y-m-d H:i:s');

    if ($mail->send()) {
        echo json_encode([
            'success' => true,
            'message' => 'Test email sent successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send test email: ' . $mail->ErrorInfo
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Mail test failed: ' . $e->getMessage()
    ]);
}
