<?php
require_once __DIR__ . '/../shared/session_handler.php';
header('Content-Type: application/json; charset=utf-8');
if (!isset($user_id)) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id<=0) { echo json_encode(['success'=>false,'message'=>'Invalid id']); exit(); }
require_once __DIR__ . '/../shared/config.php';
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT mra.file_path, mra.original_name, mr.patient_id FROM medical_record_attachments mra JOIN medical_records mr ON mra.medical_record_id = mr.id WHERE mra.id = :id LIMIT 1');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo json_encode(['success'=>false,'message'=>'Not found']); exit(); }
// permission: allow admin, doctor, or the patient owner
require_once __DIR__ . '/../shared/session_handler.php';
$user_type = $_SESSION['user_type'] ?? null; $uid = $_SESSION['user_id'] ?? null;
if (!in_array($user_type, ['admin','doctor']) && $row['patient_id'] != ($uid)) {
    echo json_encode(['success'=>false,'message'=>'Forbidden']); exit();
}
$path = __DIR__ . '/../' . $row['file_path'];
if (!is_file($path)) { echo json_encode(['success'=>false,'message'=>'File not found']); exit(); }
// stream file
$mime = mime_content_type($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($row['original_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit();
