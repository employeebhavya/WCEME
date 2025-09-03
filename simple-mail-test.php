<?php
// Simple email test
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

try {
    $mail = new PHPMailer(true);

    // SMTP settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'riturajsingh3001@gmail.com';
    $mail->Password = 'wzggtgcoklhmthuj';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Email content
    $mail->setFrom('riturajsingh3001@gmail.com', 'WCEME 2025');
    $mail->addAddress('romansingh90633@gmail.com', 'Test User');
    $mail->isHTML(true);
    $mail->Subject = 'WCEME 2025 - Email Test ' . date('H:i:s');
    $mail->Body = '<h1>Test Email</h1><p>This is a test email sent at ' . date('Y-m-d H:i:s') . '</p>';

    $result = $mail->send();

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to send email: ' . $mail->ErrorInfo
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
