<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

try {
    // Log the request method
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed");
    }

    // Log POST data
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Check if required fields exist
    $required_fields = ['full_name', 'email', 'phone'];
    $missing_fields = [];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missing_fields));
    }

    // Check if payment proof file exists
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Payment proof file is required");
    }

    // If we reach here, basic validation passed
    echo json_encode([
        'success' => true,
        'message' => 'Debug: Form data received successfully',
        'data' => [
            'post_count' => count($_POST),
            'files_count' => count($_FILES),
            'name' => $_POST['full_name'] ?? 'N/A',
            'email' => $_POST['email'] ?? 'N/A'
        ]
    ]);
} catch (Exception $e) {
    error_log("Debug error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Debug: ' . $e->getMessage()
    ]);
} catch (Error $e) {
    error_log("Debug fatal error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Debug: Fatal error occurred'
    ]);
}
