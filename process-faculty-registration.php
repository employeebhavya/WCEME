<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user, but log them
ini_set('log_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Start output buffering to prevent any output before JSON
ob_start();

// Clean output buffer
if (ob_get_level() > 1) {
    ob_clean();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Include PHPMailer
    require_once 'PHPMailer/src/Exception.php';
    require_once 'PHPMailer/src/PHPMailer.php';
    require_once 'PHPMailer/src/SMTP.php';

    // Get form data
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $title = $_POST['title'] ?? '';
    $affiliation = $_POST['affiliation'] ?? '';
    $designation = $_POST['designation'] ?? '';
    $department = $_POST['department'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $accompanying_persons = $_POST['accompanying_persons'] ?? '0';
    $meal_preference = $_POST['meal_preference'] ?? '';
    $medical_council_name = $_POST['medical_council_name'] ?? '';
    $medical_council_number = $_POST['medical_council_number'] ?? '';
    $amount_paid = $_POST['amount_paid'] ?? '';
    $payment_mode = $_POST['payment_mode'] ?? '';
    $transaction_id = $_POST['transaction_id'] ?? '';

    // Calculate total amount for faculty
    $base_fee = 15000; // Faculty base fee
    $additional_persons = intval($accompanying_persons);
    $calculated_total = $base_fee + ($additional_persons * 5000);

    // Validate required fields
    $required_fields = [
        'email',
        'phone',
        'full_name',
        'title',
        'affiliation',
        'designation',
        'department',
        'nationality',
        'gender',
        'accompanying_persons',
        'meal_preference',
        'medical_council_name',
        'medical_council_number',
        'amount_paid',
        'payment_mode',
        'transaction_id'
    ];

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }

    // Handle file upload
    $uploadDir = 'uploads/faculty/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $paymentProofPath = '';

    // Check if payment proof file was uploaded
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Payment proof file is required');
    }

    // Validate file
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'image/jpeg', 'image/jpg', 'image/png'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB

    if (!in_array($_FILES['payment_proof']['type'], $allowedTypes)) {
        throw new Exception('Invalid file type for payment proof. Please upload PDF, DOC, DOCX, JPG, JPEG, or PNG files only.');
    }

    if ($_FILES['payment_proof']['size'] > $maxFileSize) {
        throw new Exception('Payment proof file size must be less than 10MB');
    }

    // Generate unique filename
    $fileExtension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
    $paymentProofFilename = 'payment_proof_' . date('YmdHis') . '_' . uniqid() . '.' . $fileExtension;
    $paymentProofPath = $uploadDir . $paymentProofFilename;

    if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $paymentProofPath)) {
        throw new Exception('Failed to upload payment proof file');
    }

    // Initialize PHPMailer
    $mail = new PHPMailer(true);

    // Configure SMTP with optimized settings for faster delivery
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'riturajsingh3001@gmail.com';
    $mail->Password = 'wzggtgcoklhmthuj';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // Optimize for speed
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    $mail->Timeout = 30;
    $mail->SMTPKeepAlive = true;

    // Set sender
    $mail->setFrom('riturajsingh3001@gmail.com', 'WCEME 2025 Registration');

    // Add recipients (send to admin only for faster processing)
    $mail->addAddress('romansingh90633@gmail.com', 'WCEME 2025 Admin');

    // Send confirmation to participant in a separate, faster email
    $confirmationMail = new PHPMailer(true);
    $confirmationMail->isSMTP();
    $confirmationMail->Host = 'smtp.gmail.com';
    $confirmationMail->SMTPAuth = true;
    $confirmationMail->Username = 'riturajsingh3001@gmail.com';
    $confirmationMail->Password = 'wzggtgcoklhmthuj';
    $confirmationMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $confirmationMail->Port = 587;
    $confirmationMail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    $confirmationMail->Timeout = 15;

    $confirmationMail->setFrom('riturajsingh3001@gmail.com', 'WCEME 2025 Registration');
    $confirmationMail->addAddress($email, $full_name);

    // Add attachment
    if (file_exists($paymentProofPath)) {
        $mail->addAttachment($paymentProofPath);
    }

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'WCEME 2025 Faculty Registration - ' . $full_name;

    $mail->Body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f67330; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .details { background-color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>WCEME 2025 Faculty Registration</h2>
                <p>World Congress of Emergency Medicine Educators</p>
            </div>
            <div class='content'>
                <div class='details'>
                    <h3>Registration Details</h3>
                    <p><span class='label'>Registration ID:</span> <span class='value'>FACULTY-" . date('Y') . "-" . strtoupper(substr(md5($email . time()), 0, 8)) . "</span></p>
                    <p><span class='label'>Name:</span> <span class='value'>" . htmlspecialchars($title . ' ' . $full_name) . "</span></p>
                    <p><span class='label'>Email:</span> <span class='value'>" . htmlspecialchars($email) . "</span></p>
                    <p><span class='label'>Phone:</span> <span class='value'>" . htmlspecialchars($phone) . "</span></p>
                    <p><span class='label'>Affiliation:</span> <span class='value'>" . htmlspecialchars($affiliation) . "</span></p>
                    <p><span class='label'>Designation:</span> <span class='value'>" . htmlspecialchars($designation) . "</span></p>
                    <p><span class='label'>Department:</span> <span class='value'>" . htmlspecialchars($department) . "</span></p>
                    <p><span class='label'>Nationality:</span> <span class='value'>" . htmlspecialchars($nationality) . "</span></p>
                    <p><span class='label'>Gender:</span> <span class='value'>" . htmlspecialchars($gender) . "</span></p>
                    <p><span class='label'>Base Faculty Fee:</span> <span class='value'>₹15,000</span></p>
                    <p><span class='label'>Accompanying Persons:</span> <span class='value'>" . htmlspecialchars($accompanying_persons) . " × ₹5,000 = ₹" . number_format($additional_persons * 5000) . "</span></p>
                    <p><span class='label'>Total Calculated Amount:</span> <span class='value'>₹" . number_format($calculated_total) . "</span></p>
                    <p><span class='label'>Meal Preference:</span> <span class='value'>" . htmlspecialchars($meal_preference) . "</span></p>
                    <p><span class='label'>Medical Council:</span> <span class='value'>" . htmlspecialchars($medical_council_name) . "</span></p>
                    <p><span class='label'>Registration Number:</span> <span class='value'>" . htmlspecialchars($medical_council_number) . "</span></p>
                    <p><span class='label'>Amount Paid:</span> <span class='value'>₹" . htmlspecialchars($amount_paid) . "</span></p>
                    <p><span class='label'>Payment Mode:</span> <span class='value'>" . htmlspecialchars($payment_mode) . "</span></p>
                    <p><span class='label'>Transaction ID:</span> <span class='value'>" . htmlspecialchars($transaction_id) . "</span></p>
                </div>
                <div class='details'>
                    <h3>Conference Information</h3>
                    <p><strong>Date:</strong> September 25-28, 2025</p>
                    <p><strong>Venue:</strong> Aarupadai Veedu Medical College, Puducherry</p>
                    <p><strong>Theme:</strong> Learning by Doing: Advancing Hands-On Training in Emergency Medicine</p>
                </div>
                <div class='details'>
                    <p><strong>Important:</strong> Please save this email for your records. You will need your Registration ID for further communications.</p>
                    <p>If you have any questions, please contact us at aemcfoundation@gmail.com</p>
                </div>
            </div>
        </div>
    </body>
    </html>";

    // Send admin email (with attachment)
    if (!$mail->send()) {
        throw new Exception('Failed to send admin notification: ' . $mail->ErrorInfo);
    }

    // Send participant confirmation (without attachment for speed)
    $confirmationMail->isHTML(true);
    $confirmationMail->Subject = 'WCEME 2025 Faculty Registration Confirmation - ' . $full_name;

    $registrationId = 'FACULTY-' . date('Y') . '-' . strtoupper(substr(md5($email . time()), 0, 8));

    $confirmationMail->Body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f67330; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .details { background-color: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>WCEME 2025 Faculty Registration Confirmed!</h2>
                <p>World Congress of Emergency Medicine Educators</p>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($title . ' ' . $full_name) . ",</p>
                <p>Thank you for registering for WCEME 2025. Your registration has been successfully submitted.</p>
                
                <div class='details'>
                    <h3>Your Registration Details</h3>
                    <p><span class='label'>Registration ID:</span> <span class='value'>" . $registrationId . "</span></p>
                    <p><span class='label'>Base Faculty Fee:</span> <span class='value'>₹15,000</span></p>
                    <p><span class='label'>Accompanying Persons:</span> <span class='value'>" . htmlspecialchars($accompanying_persons) . " × ₹5,000</span></p>
                    <p><span class='label'>Total Amount:</span> <span class='value'>₹" . number_format($calculated_total) . "</span></p>
                    <p><span class='label'>Amount Paid:</span> <span class='value'>₹" . htmlspecialchars($amount_paid) . "</span></p>
                    <p><span class='label'>Transaction ID:</span> <span class='value'>" . htmlspecialchars($transaction_id) . "</span></p>
                </div>
                
                <div class='details'>
                    <h3>Conference Information</h3>
                    <p><strong>Date:</strong> September 25-28, 2025</p>
                    <p><strong>Venue:</strong> Aarupadai Veedu Medical College, Puducherry</p>
                    <p><strong>Theme:</strong> Learning by Doing: Advancing Hands-On Training in Emergency Medicine</p>
                </div>
                
                <div class='details'>
                    <p><strong>Important:</strong> Please save this email and your Registration ID for future reference.</p>
                    <p>We will send you further details about the conference schedule and logistics soon.</p>
                    <p>For any queries, contact us at riturajsingh3001@gmail.com</p>
                </div>
            </div>
        </div>
    </body>
    </html>";

    if (!$confirmationMail->send()) {
        // Log the error but don't fail the registration
        error_log('Failed to send confirmation email to participant: ' . $confirmationMail->ErrorInfo);
    }

    // Clean output buffer and send JSON response
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Faculty registration submitted successfully!'
    ]);
} catch (Exception $e) {
    // Clean output buffer and send error response
    ob_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
