<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/LabTestsController.php';

if (!isset($user_id)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ctrl = new LabTestsController();
$row = $ctrl->getById($id);
if ($row) echo json_encode(['success'=>true,'data'=>$row]); else echo json_encode(['success'=>false,'message'=>'Not found']);
