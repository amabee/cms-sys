<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/PatientsController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    exit();
}

try {
    $ctrl = new PatientsController();
    $res = $ctrl->update($id, $_POST);
    echo json_encode($res);
} catch (Exception $e) {
    error_log('[ajax/update_patient] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
