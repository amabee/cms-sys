<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized
if (!isset($user_id) || !in_array($user_type, ['admin', 'doctor'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $db = getDBConnection();
    
    // DataTables parameters
    $draw = isset($_GET['draw']) ? intval($_GET['draw']) : 1;
    $start = isset($_GET['start']) ? intval($_GET['start']) : 0;
    $length = isset($_GET['length']) ? intval($_GET['length']) : 10;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    $orderColumnIndex = isset($_GET['order'][0]['column']) ? intval($_GET['order'][0]['column']) : 0;
    $orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'DESC';
    
    // Custom filters
    $specializationFilter = isset($_GET['specialization']) ? $_GET['specialization'] : '';
    $availabilityFilter = isset($_GET['is_available']) ? $_GET['is_available'] : '';
    $globalSearch = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Column mapping for ordering
    $columns = ['d.id', 'd.doctor_id', 'd.specialization', 'u.email', 'd.experience_years', 'd.is_available'];
    $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'd.id';
    
    // Base query
    $baseQuery = "FROM doctors d 
                  LEFT JOIN users u ON d.user_id = u.id";
    
    // WHERE conditions
    $whereConditions = [];
    $params = [];
    
    // Apply filters
    if (!empty($specializationFilter)) {
        $whereConditions[] = "d.specialization = :specialization";
        $params[':specialization'] = $specializationFilter;
    }
    
    if ($availabilityFilter !== '') {
        $whereConditions[] = "d.is_available = :is_available";
        $params[':is_available'] = $availabilityFilter;
    }
    
    // Global search
    if (!empty($globalSearch)) {
        $whereConditions[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE :search 
                              OR d.doctor_id LIKE :search 
                              OR d.specialization LIKE :search 
                              OR u.email LIKE :search 
                              OR u.phone LIKE :search)";
        $params[':search'] = "%$globalSearch%";
    }
    
    // DataTables search
    if (!empty($searchValue)) {
        $whereConditions[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE :dt_search 
                              OR d.doctor_id LIKE :dt_search 
                              OR d.specialization LIKE :dt_search)";
        $params[':dt_search'] = "%$searchValue%";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Count total records
    $totalQuery = "SELECT COUNT(*) as total FROM doctors d LEFT JOIN users u ON d.user_id = u.id";
    $totalStmt = $db->query($totalQuery);
    $totalRecords = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Count filtered records
    $filteredQuery = "SELECT COUNT(*) as total $baseQuery $whereClause";
    $filteredStmt = $db->prepare($filteredQuery);
    $filteredStmt->execute($params);
    $filteredRecords = $filteredStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Fetch data
    $dataQuery = "SELECT 
                    d.id,
                    d.doctor_id,
                    d.specialization,
                    d.experience_years,
                    d.is_available,
                    d.consultation_fee,
                    d.license_number,
                    CONCAT(u.first_name, ' ', u.last_name) as name,
                    u.email,
                    u.phone
                  $baseQuery
                  $whereClause
                  ORDER BY $orderColumn $orderDir
                  LIMIT :start, :length";
    
    $dataStmt = $db->prepare($dataQuery);
    
    // Bind parameters
    foreach ($params as $key => $value) {
        $dataStmt->bindValue($key, $value);
    }
    $dataStmt->bindValue(':start', $start, PDO::PARAM_INT);
    $dataStmt->bindValue(':length', $length, PDO::PARAM_INT);
    
    $dataStmt->execute();
    $data = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format response
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('[ajax/get_doctors_dt] ' . $e->getMessage());
    echo json_encode([
        'draw' => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Failed to fetch doctors data'
    ]);
}
