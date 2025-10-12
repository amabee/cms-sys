<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/LabTestsController.php';

if (!isset($user_id)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$ctrl = new LabTestsController();
$res = $ctrl->delete($id, $user_id);
echo json_encode($res);
