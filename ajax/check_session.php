<?php
/**
 * Check Session AJAX Endpoint
 * Returns current session information
 */

header('Content-Type: application/json');
session_start();

$response = [
    'success' => false,
    'message' => '',
    'user_id' => null,
    'username' => null,
    'user_type' => null,
    'email' => null
];

try {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $response['success'] = true;
        $response['user_id'] = $_SESSION['user_id'];
        $response['username'] = $_SESSION['username'] ?? '';
        $response['user_type'] = $_SESSION['user_type'];
        $response['email'] = $_SESSION['email'] ?? '';
        $response['first_name'] = $_SESSION['first_name'] ?? '';
        $response['last_name'] = $_SESSION['last_name'] ?? '';
    } else {
        $response['message'] = 'Not authenticated';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
