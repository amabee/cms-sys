<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/PatientsController.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$db = null;
if (function_exists('getDBConnection')) {
    $db = @getDBConnection();
}


// DataTables server-side params (fall back to legacy params)
$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : null;
$start = isset($_GET['start']) ? max(0, (int)$_GET['start']) : null;
$length = isset($_GET['length']) ? max(1, min(1000, (int)$_GET['length'])) : null;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Legacy pagination
if ($start === null) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? max(1, min(200, (int)$_GET['per_page'])) : 20;
    $start = ($page - 1) * $per_page;
    $length = $per_page;
}

$result = ['success' => true];

if (!$db) {
    echo json_encode($result);
    exit();
}

try {
    $ctrl = new PatientsController();
    
    // Check for simple search parameter (used by doctor dashboard)
    if (isset($_GET['search']) && is_string($_GET['search'])) {
        // Convert simple search to DataTable format
        $searchRequest = [
            'draw' => 1,
            'start' => 0,
            'length' => 50, // Limit search results
            'search' => ['value' => $_GET['search']]
        ];
        $result = $ctrl->listForDataTable($searchRequest);
    } else {
        $result = $ctrl->listForDataTable($_GET);
    }
} catch (Exception $e) {
    $result = ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
}

echo json_encode($result);

?>
