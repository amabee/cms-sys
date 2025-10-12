<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/PatientsController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = null;
if (function_exists('getDBConnection')) {
    $db = @getDBConnection();
}

try {
    $ctrl = new PatientsController();
    $res = $ctrl->create($_POST);
    echo json_encode($res);
} catch (Exception $e) {
    error_log('[ajax/create_patient] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Insert failed']);
}

?>
