<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/AppointmentsController.php';

if (!isset($user_id)) { echo json_encode(['error'=>1,'message'=>'Unauthorized']); exit(); }

try {
    $ctrl = new AppointmentsController();
    $res = $ctrl->listForDataTable($_GET);
    echo json_encode($res);
} catch (Exception $e) {
    error_log('[ajax/get_appointments] '.$e->getMessage());
    echo json_encode(['draw'=>0,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[]]);
}
