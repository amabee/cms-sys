<?php
session_start();
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permissions (admin only)
if (!in_array($_SESSION['user_type'], ['admin'])) {
    echo json_encode(['success' => false, 'message' => 'Insufficient permissions']);
    exit;
}

require_once '../controllers/UsersController.php';
$controller = new UsersController();

try {
    // Get filters from request
    $filters = [
        'role' => $_GET['role'] ?? '',
        'is_active' => $_GET['is_active'] ?? '',
        'search' => $_GET['search'] ?? '',
        'limit' => (int)($_GET['length'] ?? 50), // DataTables parameter
        'offset' => (int)($_GET['start'] ?? 0)   // DataTables parameter
    ];
    
    $result = $controller->getUsers($filters);
    
    if ($result['success']) {
        // Format response for DataTables
        echo json_encode([
            'draw' => (int)($_GET['draw'] ?? 1),
            'recordsTotal' => $result['total_count'],
            'recordsFiltered' => $result['total_count'],
            'data' => $result['users']
        ]);
    } else {
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log('Error in get_users.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve users']);
}
?>
