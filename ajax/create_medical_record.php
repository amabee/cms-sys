<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../controllers/MedicalRecordsController.php';

if (!isset($user_id)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }

$data = $_POST;
// handle attachment upload
if (isset($_FILES['attachment']) && is_uploaded_file($_FILES['attachment']['tmp_name'])) {
	$allowed = ['pdf','png','jpg','jpeg'];
	$tmp = $_FILES['attachment']['tmp_name'];
	$orig = basename($_FILES['attachment']['name']);
	$ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
	if (!in_array($ext, $allowed) || $_FILES['attachment']['size'] > 5*1024*1024) {
		echo json_encode(['success'=>false,'message'=>'Invalid attachment']); exit;
	}
	$destDir = __DIR__ . '/../uploads/medical_records/';
	if (!is_dir($destDir)) mkdir($destDir, 0755, true);
	$safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
	$filename = time() . '_' . bin2hex(random_bytes(6)) . '_' . $safe;
	$target = $destDir . $filename;
	if (move_uploaded_file($tmp, $target)) {
		$data['attachment'] = ['path' => 'uploads/medical_records/' . $filename, 'original_name' => $orig];
	}
}

$ctrl = new MedicalRecordsController();
$res = $ctrl->create($data, $user_id);
echo json_encode($res);
