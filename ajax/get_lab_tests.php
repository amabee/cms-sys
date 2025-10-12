<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/LabTestsController.php';

if (!isset($user_id)) { echo json_encode(['draw'=>0,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[]]); exit(); }

$ctrl = new LabTestsController();
$res = $ctrl->listForDataTable($_GET);
echo json_encode($res);
