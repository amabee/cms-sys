<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/CompanyController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Only admins can update company info
if (!isset($user_id) || !hasRole('admin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$post = $_POST;
$file = isset($_FILES['logo']) ? $_FILES['logo'] : null;

$res = CompanyController::updateCompanyInfo($post, $file);

// Ensure company data included in response is safe
if (is_array($res) && isset($res['company']) && is_array($res['company'])) {
    if (!empty($res['company']['logo'])) {
        $res['company']['logo'] = basename($res['company']['logo']);
    } else {
        $res['company']['logo'] = '';
    }
}

echo json_encode($res);

?>
