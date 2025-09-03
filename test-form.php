<?php
// Diagnostic page for form testing
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed");
    }

    // Check if all required fields are present
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

    $missing_fields = [];
    $received_fields = [];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        } else {
            $received_fields[] = $field;
        }
    }

    // Check file upload
    $file_status = "No file uploaded";
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $file_status = "File uploaded: " . $_FILES['payment_proof']['name'] . " (" . $_FILES['payment_proof']['size'] . " bytes)";
    } elseif (isset($_FILES['payment_proof'])) {
        $file_status = "File upload error: " . $_FILES['payment_proof']['error'];
    }

    echo json_encode([
        'success' => true,
        'message' => 'Form data received successfully',
        'debug_info' => [
            'total_post_fields' => count($_POST),
            'total_files' => count($_FILES),
            'required_fields_count' => count($required_fields),
            'received_fields_count' => count($received_fields),
            'missing_fields' => $missing_fields,
            'file_status' => $file_status,
            'php_version' => PHP_VERSION,
            'max_file_size' => ini_get('upload_max_filesize'),
            'max_post_size' => ini_get('post_max_size')
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'error_type' => 'Exception',
            'php_version' => PHP_VERSION
        ]
    ]);
}
