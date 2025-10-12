<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/AppointmentsController.php';

if (!isset($user_id)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }
$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id'])?(int)$_POST['id']:0);
if ($id<=0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }
try {
    $ctrl = new AppointmentsController();
    $row = $ctrl->getById($id);
    if ($row) echo json_encode(['success'=>true,'data'=>$row]); else echo json_encode(['success'=>false,'message'=>'Not found']);
} catch (Exception $e) {
    error_log('[ajax/get_appointment] '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Error']);
}
