<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Clean output buffer
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Email configuration
$admin_email = "romansingh90633@gmail.com";
$smtp_host = "smtp.gmail.com";
$smtp_username = "riturajsingh3001@gmail.com";
$smtp_password = "wzggtgcoklhmthuj";
$smtp_port = 587;

function sanitizeInput($data)
{
    return htmlspecialchars(stripslashes(trim($data)));
}

function uploadFile($file, $upload_dir)
{
    if (!isset($file['tmp_name']) || !$file['tmp_name']) {
        return false;
    }

    $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $max_size = 10 * 1024 * 1024; // 10MB

    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);

    if (!in_array($extension, $allowed_types)) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_types));
    }

    if ($file['size'] > $max_size) {
        throw new Exception("File size too large. Maximum size is 10MB.");
    }

    $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $upload_path = $upload_dir . '/' . $filename;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return $filename;
    }

    throw new Exception("Failed to upload file.");
}

try {
    error_log("Registration form processing started");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed");
    }

    // Sanitize and validate input data
    $data = [];
    $required_fields = [
        'full_name',
        'title',
        'email',
        'phone',
        'affiliation',
        'designation',
        'department',
        'nationality',
        'gender',
        'medical_council_name',
        'medical_council_number',
        'conference_fee',
        'amount_paid',
        'payment_mode',
        'transaction_id',
        'meal_preference'
    ];

    $optional_fields = [
        'day1_workshop',
        'day2_workshop',
        'masterclass'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Required field missing: " . ucfirst(str_replace('_', ' ', $field)));
        }
        $data[$field] = sanitizeInput($_POST[$field]);
    }

    // Handle optional fields
    foreach ($optional_fields as $field) {
        $data[$field] = isset($_POST[$field]) ? sanitizeInput($_POST[$field]) : '';
    }

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }

    // Handle file uploads (both files are required)
    $upload_dir = 'uploads';
    $uploaded_files = [];

    // Payment proof (required)
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Payment proof is required. Please upload your payment receipt.");
    }
    $uploaded_files['payment_proof'] = uploadFile($_FILES['payment_proof'], $upload_dir);

    // Bonafide certificate (required)
    if (!isset($_FILES['bonafide']) || $_FILES['bonafide']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Student Bonafide Certificate is required. Please upload your bonafide certificate.");
    }
    $uploaded_files['bonafide'] = uploadFile($_FILES['bonafide'], $upload_dir);

    // Calculate total amount based on form selections
    function calculateTotalAmount($data)
    {
        $total = 0;

        // Conference fee
        if (isset($data['conference_fee'])) {
            $fee = $data['conference_fee'];
            if (strpos($fee, '₹7000') !== false) $total += 7000;
            elseif (strpos($fee, '₹6000') !== false) $total += 6000;
            elseif (strpos($fee, '₹3000') !== false) $total += 3000;
            elseif (strpos($fee, '₹2000') !== false) $total += 2000;
        }

        // Day 1 workshop
        if (isset($data['day1_workshop']) && !empty($data['day1_workshop'])) {
            $workshop = $data['day1_workshop'];
            if (strpos($workshop, '₹6000') !== false) $total += 6000;
            elseif (strpos($workshop, '₹3000') !== false) $total += 3000;
        }

        // Day 2 workshop
        if (isset($data['day2_workshop']) && !empty($data['day2_workshop'])) {
            $workshop = $data['day2_workshop'];
            if (strpos($workshop, '₹6000') !== false) $total += 6000;
            elseif (strpos($workshop, '₹3000') !== false) $total += 3000;
            elseif (strpos($workshop, '₹800') !== false) $total += 800;
        }

        // Masterclass
        if (isset($data['masterclass']) && !empty($data['masterclass'])) {
            $masterclass = $data['masterclass'];
            if (strpos($masterclass, '₹5000') !== false) $total += 5000;
            elseif (strpos($masterclass, '₹800') !== false) $total += 800;
        }

        return $total;
    }

    $calculated_total = calculateTotalAmount($data);
    $registration_id = 'WCEME2025-' . strtoupper(uniqid());

    // Send email to admin
    $mail_admin = new PHPMailer(true);
    $mail_admin->isSMTP();
    $mail_admin->Host = $smtp_host;
    $mail_admin->SMTPAuth = true;
    $mail_admin->Username = $smtp_username;
    $mail_admin->Password = $smtp_password;
    $mail_admin->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail_admin->Port = $smtp_port;

    // Optimize for faster delivery
    $mail_admin->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    $mail_admin->Timeout = 30;

    // Add debugging
    $mail_admin->SMTPDebug = 0; // Reduced debug level for speed
    $mail_admin->Debugoutput = function ($str, $level) {
        error_log("SMTP Admin Debug: " . $str);
    };

    $mail_admin->setFrom($smtp_username, 'WCEME 2025 Registration System');
    $mail_admin->addAddress($admin_email);
    $mail_admin->addReplyTo($data['email'], $data['full_name']);

    $mail_admin->isHTML(true);
    $mail_admin->Subject = 'New WCEME 2025 Registration - ' . $data['full_name'];

    $admin_email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .header { background: #f67330; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .field { margin-bottom: 10px; }
            .label { font-weight: bold; color: #333; }
            .value { margin-left: 10px; }
            .section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #f67330; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h2>New WCEME 2025 Registration</h2>
            <p>Registration ID: {$registration_id}</p>
        </div>
        <div class='content'>
            <div class='section'>
                <h3>Personal Information</h3>
                <div class='field'><span class='label'>Name:</span><span class='value'>{$data['full_name']}</span></div>
                <div class='field'><span class='label'>Title:</span><span class='value'>{$data['title']}</span></div>
                <div class='field'><span class='label'>Email:</span><span class='value'>{$data['email']}</span></div>
                <div class='field'><span class='label'>Phone:</span><span class='value'>{$data['phone']}</span></div>
                <div class='field'><span class='label'>Gender:</span><span class='value'>{$data['gender']}</span></div>
                <div class='field'><span class='label'>Nationality:</span><span class='value'>{$data['nationality']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Professional Information</h3>
                <div class='field'><span class='label'>Affiliation:</span><span class='value'>{$data['affiliation']}</span></div>
                <div class='field'><span class='label'>Designation:</span><span class='value'>{$data['designation']}</span></div>
                <div class='field'><span class='label'>Department:</span><span class='value'>{$data['department']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Medical Council Information</h3>
                <div class='field'><span class='label'>Medical Council Name:</span><span class='value'>{$data['medical_council_name']}</span></div>
                <div class='field'><span class='label'>Medical Council Number:</span><span class='value'>{$data['medical_council_number']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Conference Selections</h3>
                <div class='field'><span class='label'>Conference Fee:</span><span class='value'>{$data['conference_fee']}</span></div>";

    if (!empty($data['day1_workshop'])) {
        $admin_email_body .= "<div class='field'><span class='label'>Day 1 Workshop:</span><span class='value'>{$data['day1_workshop']}</span></div>";
    }
    if (!empty($data['day2_workshop'])) {
        $admin_email_body .= "<div class='field'><span class='label'>Day 2 Workshop:</span><span class='value'>{$data['day2_workshop']}</span></div>";
    }
    if (!empty($data['masterclass'])) {
        $admin_email_body .= "<div class='field'><span class='label'>Masterclass:</span><span class='value'>{$data['masterclass']}</span></div>";
    }

    $admin_email_body .= "
            </div>
            
            <div class='section'>
                <h3>Payment Information</h3>
                <div class='field'><span class='label'>Conference Fee Type:</span><span class='value'>{$data['conference_fee']}</span></div>
                <div class='field'><span class='label'>Amount Paid:</span><span class='value'>₹{$data['amount_paid']}</span></div>
                <div class='field'><span class='label'>Calculated Total:</span><span class='value'>₹{$calculated_total}</span></div>
                <div class='field'><span class='label'>Payment Mode:</span><span class='value'>{$data['payment_mode']}</span></div>
                <div class='field'><span class='label'>Transaction ID:</span><span class='value'>{$data['transaction_id']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Additional Information</h3>
                <div class='field'><span class='label'>Meal Preference:</span><span class='value'>{$data['meal_preference']}</span></div>
            </div>
        </div>
    </body>
    </html>";

    $mail_admin->Body = $admin_email_body;

    // Attach files (both are required so they will exist)
    if (file_exists($upload_dir . '/' . $uploaded_files['payment_proof'])) {
        $mail_admin->addAttachment($upload_dir . '/' . $uploaded_files['payment_proof']);
    }
    if (file_exists($upload_dir . '/' . $uploaded_files['bonafide'])) {
        $mail_admin->addAttachment($upload_dir . '/' . $uploaded_files['bonafide']);
    }

    // Send admin email
    if (!$mail_admin->send()) {
        error_log("Failed to send admin email: " . $mail_admin->ErrorInfo);
        throw new Exception("Failed to send notification email to admin");
    }

    // Send confirmation email to user
    $mail_user = new PHPMailer(true);
    $mail_user->isSMTP();
    $mail_user->Host = $smtp_host;
    $mail_user->SMTPAuth = true;
    $mail_user->Username = $smtp_username;
    $mail_user->Password = $smtp_password;
    $mail_user->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail_user->Port = $smtp_port;

    // Optimize for faster delivery
    $mail_user->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
    $mail_user->Timeout = 15; // Shorter timeout for user confirmation

    // Add debugging (reduced for speed)
    $mail_user->SMTPDebug = 0;
    $mail_user->Debugoutput = function ($str, $level) {
        error_log("SMTP User Debug: " . $str);
    };

    $mail_user->setFrom($smtp_username, 'WCEME 2025');
    $mail_user->addAddress($data['email'], $data['full_name']);

    $mail_user->isHTML(true);
    $mail_user->Subject = 'WCEME 2025 Registration Confirmation - ' . $registration_id;

    $user_email_body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; }
            .header { background: #f67330; color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .info-box { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0; }
            .footer { background: #333; color: white; padding: 20px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Registration Confirmation</h1>
                <h2>WCEME 2025</h2>
                <p>World Congress of Emergency Medicine Educators</p>
            </div>
            
            <div class='content'>
                <p>Dear {$data['full_name']},</p>
                
                <p>Thank you for registering for the World Congress of Emergency Medicine Educators (WCEME) 2025!</p>
                
                <div class='info-box'>
                    <h3>Registration Details:</h3>
                    <p><strong>Registration ID:</strong> {$registration_id}</p>
                    <p><strong>Name:</strong> {$data['full_name']}</p>
                    <p><strong>Email:</strong> {$data['email']}</p>
                    <p><strong>Conference Fee:</strong> {$data['conference_fee']}</p>
                    <p><strong>Total Amount:</strong> ₹{$calculated_total}</p>
                    <p><strong>Amount Paid:</strong> ₹{$data['amount_paid']}</p>
                </div>
                
                <div class='info-box'>
                    <h3>Next Steps:</h3>
                    <ul>
                        <li>Your payment is being verified by our team</li>
                        <li>You will receive a confirmation email within 2-3 business days</li>
                        <li>Your delegate kit and certificates will be provided at the venue</li>
                        <li>Please bring a valid photo ID for verification</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <p>For any queries, please contact us:</p>
                    <p><strong>Email:</strong> info@avmch.edu.in<br>
                    <strong>Phone:</strong> +91-413-2615001</p>
                </div>
                
                <p>We look forward to welcoming you at WCEME 2025!</p>
                
                <p>Best regards,<br>
                <strong>WCEME 2025 Organizing Committee</strong><br>
                Aarupadai Veedu Medical College & Hospital</p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2025 Aarupadai Veedu Medical College & Hospital. All rights reserved.</p>
                <p>This is an automated email. Please do not reply to this email address.</p>
            </div>
        </div>
    </body>
    </html>";

    $mail_user->Body = $user_email_body;

    // Send user email
    if (!$mail_user->send()) {
        error_log("Failed to send user email: " . $mail_user->ErrorInfo);
        throw new Exception("Failed to send confirmation email to user");
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration submitted successfully! You will receive a confirmation email shortly.',
        'registration_id' => $registration_id,
        'total_amount' => $total_amount
    ]);
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("Registration fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again later.'
    ]);
}
