<?php
session_start();
header('Content-Type: application/json');

// Check authentication and authorization
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if user has permission to save templates
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
    // DEBUG: Log all POST data
    error_log("DEBUG: Received POST data: " . json_encode($_POST));
    
    // Validate required fields
    $required_fields = ['name', 'code', 'type', 'method', 'message_template'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
            exit;
        }
    }

    $data = [
        'name' => $_POST['name'],
        'code' => $_POST['code'],
        'type' => $_POST['type'],
        'method' => $_POST['method'],
        'subject_template' => $_POST['subject_template'] ?? null,
        'message_template' => $_POST['message_template']
    ];

    // If id is provided, perform an update instead to avoid duplicate key on code
    if (!empty($_POST['id'])) {
        $id = intval($_POST['id']);
        error_log("DEBUG: Checking template code '{$data['code']}' excluding id {$id}");
        // Ensure code uniqueness excluding this id
        if ($controller->templateCodeExists($data['code'], $id)) {
            $conflictId = $controller->getTemplateIdByCode($data['code'], $id);
            error_log("DEBUG: Code conflict found! Conflict ID: {$conflictId}");
            echo json_encode(['success' => false, 'message' => 'Template code already exists', 'conflict_id' => $conflictId, 'received_id' => $id]);
            exit;
        }
        error_log("DEBUG: No code conflict, proceeding with update");
        $result = $controller->updateTemplate($id, $data);
        if ($result['success']) $result['received_id'] = $id;
    } else {
        // Before inserting, ensure template_code is unique
        if ($controller->templateCodeExists($data['code'])) {
            $conflictId = $controller->getTemplateIdByCode($data['code']);
            $conflictTemplate = null;
            if ($conflictId) {
                $tRes = $controller->getTemplateById($conflictId);
                if ($tRes['success']) $conflictTemplate = $tRes['template'];
            }

            echo json_encode(['success' => false, 'message' => 'Template code already exists', 'conflict_id' => $conflictId, 'conflict_template' => $conflictTemplate]);
            exit;
        }

        $result = $controller->saveTemplate($data);
        if ($result['success']) $result['received_id'] = $result['template_id'] ?? null;
    }
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Template saved successfully',
            'data' => ['template_id' => $result['template_id']]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
    
} catch (Exception $e) {
    error_log("Error in save_notification_template.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to save template']);
}
?>
