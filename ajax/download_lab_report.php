<?php
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$user_type = $_SESSION['user_type'] ?? '';
$user_id = $_SESSION['user_id'];

// Only allow admin, doctor, or the patient who owns the test to download
$allowed_roles = ['admin', 'doctor'];

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Missing lab result ID');
}

$lab_result_id = (int)$_GET['id'];

try {
    $db = getDBConnection();
    
    // Get lab result with lab test and patient info
    $stmt = $db->prepare("
        SELECT lr.report_file, lr.lab_test_id,
               lt.patient_id,
               p.patient_id as patient_code
        FROM lab_results lr
        JOIN lab_tests lt ON lr.lab_test_id = lt.id
        JOIN patients p ON lt.patient_id = p.id
        WHERE lr.id = :id AND lr.report_file IS NOT NULL
        LIMIT 1
    ");
    $stmt->bindValue(':id', $lab_result_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        http_response_code(404);
        exit('Lab report not found');
    }
    
    // Permission check: admin/doctor can access all, patients can only access their own
    if (!in_array($user_type, $allowed_roles)) {
        // For patient portal access (if implemented), check if this user owns this test
        // For now, restrict to admin/doctor only
        http_response_code(403);
        exit('Access denied');
    }
    
    $file_path = __DIR__ . '/../uploads/lab_reports/' . $result['report_file'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('File not found on server');
    }
    
    // Get file info
    $file_info = pathinfo($file_path);
    $file_extension = strtolower($file_info['extension']);
    
    // Set appropriate content type
    $content_types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
    
    $content_type = $content_types[$file_extension] ?? 'application/octet-stream';
    
    // Set headers for inline display (view in browser)
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . filesize($file_path));
    header('Content-Disposition: inline; filename="' . basename($result['report_file']) . '"');
    header('Cache-Control: private, max-age=3600');
    
    // Output file
    readfile($file_path);
    
} catch (Exception $e) {
    error_log('[download_lab_report] Exception: ' . $e->getMessage());
    http_response_code(500);
    exit('Server error');
}
?>
