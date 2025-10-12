<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/LabTestsController.php';

if (!isset($user_id)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$data = $_POST;
// handle report file upload if present
if (!empty($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
	$f = $_FILES['report_file'];
	$allowed = ['pdf','png','jpg','jpeg'];
	$maxBytes = 5 * 1024 * 1024; // 5MB
	if ($f['size'] > $maxBytes) {
		echo json_encode(['success'=>false,'message'=>'File too large (max 5MB)']); exit();
	}
	$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
	if (!in_array($ext, $allowed)) { echo json_encode(['success'=>false,'message'=>'Invalid file type']); exit(); }
	$safe = preg_replace('/[^a-z0-9._-]/i', '_', basename($f['name']));
	$targetDir = __DIR__ . '/../uploads/lab_reports/';
	if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
	$targetName = time() . '_' . bin2hex(random_bytes(6)) . '_' . $safe;
	$targetPath = $targetDir . $targetName;
	if (move_uploaded_file($f['tmp_name'], $targetPath)) {
		// store relative path
		$data['report_file'] = 'uploads/lab_reports/' . $targetName;
	} else {
		echo json_encode(['success'=>false,'message'=>'Failed to store uploaded file']); exit();
	}
}

$ctrl = new LabTestsController();
$res = $ctrl->update($id, $data, $user_id);
echo json_encode($res);
