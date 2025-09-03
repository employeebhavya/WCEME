<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to avoid breaking JSON
ini_set('log_errors', 1);

// Clean output buffer to prevent any unwanted output
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

// Database configuration (optional - uncomment if you want to save to database)
/*
$servername = "localhost";
$username = "your_db_username";
$password = "your_db_password";
$dbname = "your_database_name";
*/

// Email configuration
$admin_email = "romansingh90633@gmail.com"; // Replace with actual admin email
$smtp_host = "smtp.gmail.com"; // Replace with your SMTP host
$smtp_username = "riturajsingh3001@gmail.com"; // Replace with your email
$smtp_password = "wzggtgcoklhmthuj"; // Replace with your app password
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
    // Log the start of processing
    error_log("Registration form processing started");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed");
    }

    // Log POST data (without sensitive info)
    error_log("POST data received: " . count($_POST) . " fields");
    error_log("FILES data received: " . count($_FILES) . " files");

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
        'conference_fee',
        'amount_paid',
        'payment_mode',
        'transaction_id',
        'meal_preference'
    ];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Required field missing: " . ucfirst(str_replace('_', ' ', $field)));
        }
        $data[$field] = sanitizeInput($_POST[$field]);
    }

    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }

    // Handle optional fields
    $optional_fields = ['day1_workshop', 'day2_workshop', 'masterclass'];
    foreach ($optional_fields as $field) {
        $data[$field] = isset($_POST[$field]) ? sanitizeInput($_POST[$field]) : 'None';
    }

    // Handle file uploads
    $upload_dir = 'uploads/' . date('Y/m');
    $uploaded_files = [];

    // Handle payment proof (required)
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Payment proof is required");
    }
    $uploaded_files['payment_proof'] = uploadFile($_FILES['payment_proof'], $upload_dir);

    // Handle bonafide certificate (optional)
    if (isset($_FILES['bonafide']) && $_FILES['bonafide']['error'] === UPLOAD_ERR_OK) {
        $uploaded_files['bonafide'] = uploadFile($_FILES['bonafide'], $upload_dir);
    }

    // Calculate total amount
    $total_amount = 0;

    // Conference fee
    if (strpos($data['conference_fee'], '₹7000') !== false) $total_amount += 7000;
    elseif (strpos($data['conference_fee'], '₹6000') !== false) $total_amount += 6000;
    elseif (strpos($data['conference_fee'], '₹3000') !== false) $total_amount += 3000;
    elseif (strpos($data['conference_fee'], '₹2000') !== false) $total_amount += 2000;

    // Day 1 Workshop
    if (strpos($data['day1_workshop'], '₹12000') !== false) $total_amount += 12000;
    elseif (strpos($data['day1_workshop'], '₹3000') !== false) $total_amount += 3000;

    // Day 2 Workshop
    if (strpos($data['day2_workshop'], '₹12000') !== false) $total_amount += 12000;
    elseif (strpos($data['day2_workshop'], '₹3000') !== false) $total_amount += 3000;
    elseif (strpos($data['day2_workshop'], '₹800') !== false) $total_amount += 800;

    // Masterclass
    if (strpos($data['masterclass'], '₹5000') !== false) $total_amount += 5000;
    elseif (strpos($data['masterclass'], '₹800') !== false) $total_amount += 800;

    // Generate registration ID
    $registration_id = 'WCEME2025_' . date('Ymd') . '_' . sprintf('%04d', rand(1000, 9999));

    // Save to database (optional)
    /*
    try {
        $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = "INSERT INTO registrations (registration_id, full_name, title, email, phone, affiliation, designation, department, nationality, gender, conference_fee, day1_workshop, day2_workshop, masterclass, amount_paid, payment_mode, transaction_id, meal_preference, payment_proof, bonafide, total_amount, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $registration_id, $data['full_name'], $data['title'], $data['email'], $data['phone'],
            $data['affiliation'], $data['designation'], $data['department'], $data['nationality'],
            $data['gender'], $data['conference_fee'], $data['day1_workshop'], $data['day2_workshop'],
            $data['masterclass'], $data['amount_paid'], $data['payment_mode'], $data['transaction_id'],
            $data['meal_preference'], $uploaded_files['payment_proof'], 
            isset($uploaded_files['bonafide']) ? $uploaded_files['bonafide'] : null, $total_amount
        ]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
    }
    */

    // Send email to admin
    $mail_admin = new PHPMailer(true);

    $mail_admin->isSMTP();
    $mail_admin->Host = $smtp_host;
    $mail_admin->SMTPAuth = true;
    $mail_admin->Username = $smtp_username;
    $mail_admin->Password = $smtp_password;
    $mail_admin->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail_admin->Port = $smtp_port;
    $mail_admin->SMTPDebug = 2; // Set to 2 for verbose debugging
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
            <h2>WCEME 2025 - New Registration</h2>
            <p>Registration ID: {$registration_id}</p>
        </div>
        <div class='content'>
            <div class='section'>
                <h3>Personal Information</h3>
                <div class='field'><span class='label'>Full Name:</span><span class='value'>{$data['full_name']}</span></div>
                <div class='field'><span class='label'>Title:</span><span class='value'>{$data['title']}</span></div>
                <div class='field'><span class='label'>Email:</span><span class='value'>{$data['email']}</span></div>
                <div class='field'><span class='label'>Phone:</span><span class='value'>{$data['phone']}</span></div>
                <div class='field'><span class='label'>Affiliation:</span><span class='value'>{$data['affiliation']}</span></div>
                <div class='field'><span class='label'>Designation:</span><span class='value'>{$data['designation']}</span></div>
                <div class='field'><span class='label'>Department:</span><span class='value'>{$data['department']}</span></div>
                <div class='field'><span class='label'>Nationality:</span><span class='value'>{$data['nationality']}</span></div>
                <div class='field'><span class='label'>Gender:</span><span class='value'>{$data['gender']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Registration Details</h3>
                <div class='field'><span class='label'>Conference Fee:</span><span class='value'>{$data['conference_fee']}</span></div>
                <div class='field'><span class='label'>Day 1 Workshop:</span><span class='value'>{$data['day1_workshop']}</span></div>
                <div class='field'><span class='label'>Day 2 Workshop:</span><span class='value'>{$data['day2_workshop']}</span></div>
                <div class='field'><span class='label'>Masterclass:</span><span class='value'>{$data['masterclass']}</span></div>
                <div class='field'><span class='label'>Meal Preference:</span><span class='value'>{$data['meal_preference']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Payment Information</h3>
                <div class='field'><span class='label'>Amount Paid:</span><span class='value'>₹{$data['amount_paid']}</span></div>
                <div class='field'><span class='label'>Calculated Total:</span><span class='value'>₹{$total_amount}</span></div>
                <div class='field'><span class='label'>Payment Mode:</span><span class='value'>{$data['payment_mode']}</span></div>
                <div class='field'><span class='label'>Transaction ID:</span><span class='value'>{$data['transaction_id']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Uploaded Files</h3>
                <div class='field'><span class='label'>Payment Proof:</span><span class='value'>{$uploaded_files['payment_proof']}</span></div>
                " . (isset($uploaded_files['bonafide']) ? "<div class='field'><span class='label'>Bonafide Certificate:</span><span class='value'>{$uploaded_files['bonafide']}</span></div>" : "") . "
            </div>
        </div>
    </body>
    </html>";

    $mail_admin->Body = $admin_email_body;

    // Attach files
    if (file_exists($upload_dir . '/' . $uploaded_files['payment_proof'])) {
        $mail_admin->addAttachment($upload_dir . '/' . $uploaded_files['payment_proof']);
    }
    if (isset($uploaded_files['bonafide']) && file_exists($upload_dir . '/' . $uploaded_files['bonafide'])) {
        $mail_admin->addAttachment($upload_dir . '/' . $uploaded_files['bonafide']);
    }

    // Send admin email with error handling
    if (!$mail_admin->send()) {
        error_log("Failed to send admin email: " . $mail_admin->ErrorInfo);
        throw new Exception("Failed to send notification email to admin");
    }

    error_log("Admin email sent successfully to: " . $admin_email);

    // Send confirmation email to user
    $mail_user = new PHPMailer(true);

    $mail_user->isSMTP();
    $mail_user->Host = $smtp_host;
    $mail_user->SMTPAuth = true;
    $mail_user->Username = $smtp_username;
    $mail_user->Password = $smtp_password;
    $mail_user->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail_user->Port = $smtp_port;
    $mail_user->SMTPDebug = 2; // Set to 2 for verbose debugging
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
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #f67330, #e55d1f); color: white; padding: 30px; text-align: center; }
            .content { padding: 30px; }
            .section { margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid #f67330; }
            .field { margin: 8px 0; }
            .label { font-weight: bold; color: #333; }
            .value { color: #666; }
            .footer { background: #333; color: white; padding: 20px; text-align: center; }
            .btn { background: #f67330; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎉 Registration Confirmed!</h1>
                <h2>WCEME 2025</h2>
                <p>Aarupadai Veedu Medical College & Hospital</p>
                <div style='background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; margin-top: 20px;'>
                    <strong>Registration ID: {$registration_id}</strong>
                </div>
            </div>
            
            <div class='content'>
                <p>Dear <strong>{$data['title']} {$data['full_name']}</strong>,</p>
                
                <p>Thank you for registering for WCEME 2025! Your registration has been successfully received and is being processed.</p>
                
                <div class='section'>
                    <h3>📋 Your Registration Summary</h3>
                    <div class='field'><span class='label'>Conference Category:</span> <span class='value'>{$data['conference_fee']}</span></div>
                    <div class='field'><span class='label'>Day 1 Workshop:</span> <span class='value'>{$data['day1_workshop']}</span></div>
                    <div class='field'><span class='label'>Day 2 Workshop:</span> <span class='value'>{$data['day2_workshop']}</span></div>
                    <div class='field'><span class='label'>Masterclass:</span> <span class='value'>{$data['masterclass']}</span></div>
                    <div class='field'><span class='label'>Meal Preference:</span> <span class='value'>{$data['meal_preference']}</span></div>
                </div>
                
                <div class='section'>
                    <h3>💳 Payment Details</h3>
                    <div class='field'><span class='label'>Amount Paid:</span> <span class='value'>₹{$data['amount_paid']}</span></div>
                    <div class='field'><span class='label'>Transaction ID:</span> <span class='value'>{$data['transaction_id']}</span></div>
                    <div class='field'><span class='label'>Payment Mode:</span> <span class='value'>{$data['payment_mode']}</span></div>
                </div>
                
                <div class='section'>
                    <h3>📅 Important Dates</h3>
                    <div class='field'><span class='label'>Day 1 Workshops:</span> <span class='value'>25th September 2025</span></div>
                    <div class='field'><span class='label'>Day 2 Workshops:</span> <span class='value'>26th September 2025</span></div>
                    <div class='field'><span class='label'>Masterclasses & FDP:</span> <span class='value'>28th September 2025</span></div>
                </div>
                
                <div class='section'>
                    <h3>📍 Venue</h3>
                    <p><strong>Aarupadai Veedu Medical College & Hospital</strong><br>
                    Vinayaka Mission's Research Foundation<br>
                    Puducherry, India</p>
                </div>
                
                <div class='section'>
                    <h3>📌 Next Steps</h3>
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

    // Send user confirmation email with error handling
    if (!$mail_user->send()) {
        error_log("Failed to send user email: " . $mail_user->ErrorInfo);
        throw new Exception("Failed to send confirmation email to user");
    }

    error_log("User confirmation email sent successfully to: " . $data['email']);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Registration submitted successfully!',
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
    exit;
} catch (Error $e) {
    error_log("Registration fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A server error occurred. Please try again later.'
    ]);
    exit;
}

// If we reach here without catching an exception, something unexpected happened
http_response_code(500);
echo json_encode([
    'success' => false,
    'message' => 'Unexpected server error occurred.'
]);
