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
    $max_size = 25 * 1024 * 1024; // 25MB

    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);

    if (!in_array($extension, $allowed_types)) {
        throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_types));
    }

    if ($file['size'] > $max_size) {
        throw new Exception("File size exceeds 25MB limit");
    }

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $unique_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $destination = $upload_dir . '/' . $unique_name;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return $unique_name;
    }

    throw new Exception("Failed to upload file");
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Only POST method allowed");
    }

    // Get form data
    $data = [];
    $required_fields = [
        'full_name',
        'email',
        'phone',
        'affiliation',
        'designation',
        'department',
        'abstract_title',
        'presentation_type',
        'category',
        'abstract_text',
        'keywords'
    ];

    $optional_fields = [
        'co_authors',
        'ethics_required',
        'ethics_info'
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

    // Handle file uploads
    $upload_dir = 'uploads/abstracts';
    $uploaded_files = [];

    // Abstract file (required)
    if (!isset($_FILES['abstract_file']) || $_FILES['abstract_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Abstract document is required. Please upload your abstract file.");
    }
    $uploaded_files['abstract_file'] = uploadFile($_FILES['abstract_file'], $upload_dir);

    // Support file (optional)
    if (isset($_FILES['support_file']) && $_FILES['support_file']['error'] === UPLOAD_ERR_OK) {
        $uploaded_files['support_file'] = uploadFile($_FILES['support_file'], $upload_dir);
    }

    $abstract_id = 'WCEME2025-ABS-' . strtoupper(uniqid());

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

    $mail_admin->SMTPDebug = 0;
    $mail_admin->Debugoutput = function ($str, $level) {
        error_log("SMTP Admin Debug: " . $str);
    };

    $mail_admin->setFrom($smtp_username, 'WCEME 2025 Abstract Submission System');
    $mail_admin->addAddress($admin_email);
    $mail_admin->addReplyTo($data['email'], $data['full_name']);

    $mail_admin->isHTML(true);
    $mail_admin->Subject = 'New Abstract Submission - WCEME 2025 - ' . $data['full_name'];

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
            <h2>New Abstract Submission - WCEME 2025</h2>
            <p>Abstract ID: {$abstract_id}</p>
        </div>
        <div class='content'>
            <div class='section'>
                <h3>Personal Information</h3>
                <div class='field'><span class='label'>Name:</span><span class='value'>{$data['full_name']}</span></div>
                <div class='field'><span class='label'>Email:</span><span class='value'>{$data['email']}</span></div>
                <div class='field'><span class='label'>Phone:</span><span class='value'>{$data['phone']}</span></div>
                <div class='field'><span class='label'>Institution:</span><span class='value'>{$data['affiliation']}</span></div>
                <div class='field'><span class='label'>Designation:</span><span class='value'>{$data['designation']}</span></div>
                <div class='field'><span class='label'>Department:</span><span class='value'>{$data['department']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Abstract Details</h3>
                <div class='field'><span class='label'>Title:</span><span class='value'>{$data['abstract_title']}</span></div>
                <div class='field'><span class='label'>Presentation Type:</span><span class='value'>{$data['presentation_type']}</span></div>
                <div class='field'><span class='label'>Category:</span><span class='value'>{$data['category']}</span></div>
                <div class='field'><span class='label'>Keywords:</span><span class='value'>{$data['keywords']}</span></div>
            </div>
            
            <div class='section'>
                <h3>Abstract Text</h3>
                <p>" . nl2br($data['abstract_text']) . "</p>
            </div>";

    if (!empty($data['co_authors'])) {
        $admin_email_body .= "
            <div class='section'>
                <h3>Co-Authors</h3>
                <p>" . nl2br($data['co_authors']) . "</p>
            </div>";
    }

    if (!empty($data['ethics_required']) && $data['ethics_required'] === 'Yes') {
        $admin_email_body .= "
            <div class='section'>
                <h3>Ethical Considerations</h3>
                <div class='field'><span class='label'>Ethics Required:</span><span class='value'>{$data['ethics_required']}</span></div>";
        if (!empty($data['ethics_info'])) {
            $admin_email_body .= "<div class='field'><span class='label'>Ethics Details:</span><span class='value'>" . nl2br($data['ethics_info']) . "</span></div>";
        }
        $admin_email_body .= "</div>";
    }

    $admin_email_body .= "
        </div>
    </body>
    </html>";

    $mail_admin->Body = $admin_email_body;

    // Attach files
    if (isset($uploaded_files['abstract_file']) && file_exists($upload_dir . '/' . $uploaded_files['abstract_file'])) {
        $mail_admin->addAttachment($upload_dir . '/' . $uploaded_files['abstract_file']);
    }
    if (isset($uploaded_files['support_file']) && file_exists($upload_dir . '/' . $uploaded_files['support_file'])) {
        $mail_admin->addAttachment($upload_dir . '/' . $uploaded_files['support_file']);
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
    $mail_user->Timeout = 15;

    $mail_user->SMTPDebug = 0;
    $mail_user->Debugoutput = function ($str, $level) {
        error_log("SMTP User Debug: " . $str);
    };

    $mail_user->setFrom($smtp_username, 'WCEME 2025');
    $mail_user->addAddress($data['email'], $data['full_name']);

    $mail_user->isHTML(true);
    $mail_user->Subject = 'Abstract Submission Confirmation - WCEME 2025 - ' . $abstract_id;

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
                <h1>Abstract Submission Confirmation</h1>
                <h2>WCEME 2025</h2>
                <p>World Congress of Emergency Medicine Educators</p>
            </div>
            
            <div class='content'>
                <p>Dear {$data['full_name']},</p>
                
                <p>Thank you for submitting your abstract to the World Congress of Emergency Medicine Educators (WCEME) 2025!</p>
                
                <div class='info-box'>
                    <h3>Submission Details:</h3>
                    <p><strong>Abstract ID:</strong> {$abstract_id}</p>
                    <p><strong>Title:</strong> {$data['abstract_title']}</p>
                    <p><strong>Presentation Type:</strong> {$data['presentation_type']}</p>
                    <p><strong>Category:</strong> {$data['category']}</p>
                    <p><strong>Submitted on:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                </div>
                
                <div class='info-box'>
                    <h3>What's Next:</h3>
                    <ul>
                        <li>Your abstract will be reviewed by our Scientific Committee</li>
                        <li>All abstracts will be peer-reviewed for quality and relevance</li>
                        <li>You will receive notification of acceptance by <strong>18th September 2025</strong></li>
                        <li>Accepted abstracts will be eligible for oral or poster presentation</li>
                    </ul>
                </div>
                
                <div class='info-box'>
                    <h3>Important Dates:</h3>
                    <p><strong>Review Notification:</strong> 18th September 2025</p>
                    <p><strong>Conference Dates:</strong> 25-28 September 2025</p>
                    <p><strong>Venue:</strong> Aarupadai Veedu Medical College, Puducherry</p>
                </div>
                
                <p>If you have any questions about your submission, please contact us at <a href='mailto:wceme2025@avmch.org'>wceme2025@avmch.org</a> and include your Abstract ID: <strong>{$abstract_id}</strong></p>
                
                <p>Thank you for your participation in WCEME 2025!</p>
                
                <p>Best regards,<br>
                WCEME 2025 Organizing Committee<br>
                Aarupadai Veedu Medical College</p>
            </div>
            
            <div class='footer'>
                <p>&copy; 2025 WCEME - World Congress of Emergency Medicine Educators</p>
                <p>Aarupadai Veedu Medical College, Puducherry</p>
            </div>
        </div>
    </body>
    </html>";

    $mail_user->Body = $user_email_body;

    // Send user email
    if (!$mail_user->send()) {
        error_log("Failed to send user email: " . $mail_user->ErrorInfo);
        // Don't throw exception here as the submission was successful
    }

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Abstract submitted successfully!',
        'abstract_id' => $abstract_id
    ]);
} catch (Exception $e) {
    error_log("Abstract submission error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
