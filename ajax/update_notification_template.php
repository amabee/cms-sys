<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!in_array($_SESSION['user_type'], ['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

require_once '../controllers/NotificationsController.php';
$controller = new NotificationsController();

try {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid template id']);
        exit;
    }

    $data = [
        'name' => $_POST['name'] ?? '',
        'code' => $_POST['code'] ?? '',
        'type' => $_POST['type'] ?? '',
        'method' => $_POST['method'] ?? '',
        'subject_template' => $_POST['subject_template'] ?? null,
        'message_template' => $_POST['message_template'] ?? '',
        'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1
    ];

    // Check code uniqueness excluding current id
    if ($controller->templateCodeExists($data['code'], $id)) {
        $conflictId = $controller->getTemplateIdByCode($data['code'], $id);
        echo json_encode(['success' => false, 'message' => 'Template code already exists', 'conflict_id' => $conflictId, 'received_id' => $id]);
        exit;
    }

    $result = $controller->updateTemplate($id, $data);
    // Ensure client knows which id was updated
    if ($result['success']) $result['received_id'] = $id;
    echo json_encode($result);
} catch (Exception $e) {
    error_log('Error in update_notification_template.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update template']);
}

?>
