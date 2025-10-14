<?php
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

header('Content-Type: application/json');

try {
    $db = getDBConnection();
    
    // Get DataTables parameters
    $draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
    $start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 25;
    $searchValue = isset($_GET['search']['value']) ? $_GET['search']['value'] : '';
    
    // Base query with all joins
    $baseQuery = "FROM prescriptions p
                  LEFT JOIN patients pt ON p.patient_id = pt.id
                  LEFT JOIN doctors d ON p.doctor_id = d.id
                  LEFT JOIN users u ON d.user_id = u.id
                  LEFT JOIN medications m ON p.medication_id = m.id";
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    // Add search filter
    if (!empty($searchValue)) {
        $whereClause .= " AND (
            p.prescription_id LIKE :search OR
            CONCAT(pt.first_name, ' ', pt.last_name) LIKE :search OR
            CONCAT(u.first_name, ' ', u.last_name) LIKE :search OR
            m.name LIKE :search OR
            p.dosage LIKE :search OR
            p.status LIKE :search
        )";
        $params[':search'] = "%{$searchValue}%";
    }
    
    // Get total records (without filter)
    $totalQuery = "SELECT COUNT(*) as total FROM prescriptions p WHERE 1=1";
    $stmt = $db->prepare($totalQuery);
    $stmt->execute();
    $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get filtered records count
    $countQuery = "SELECT COUNT(*) as total {$baseQuery} {$whereClause}";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $filteredRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get data
    $dataQuery = "SELECT 
                    p.id,
                    p.prescription_id,
                    p.patient_id,
                    p.doctor_id,
                    p.medication_id,
                    p.dosage,
                    p.frequency,
                    p.duration,
                    p.quantity,
                    p.instructions,
                    p.status,
                    p.created_at,
                    p.updated_at,
                    CONCAT(pt.first_name, ' ', pt.last_name) as patient_name,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    m.name as medication_name,
                    1 as item_count,
                    0.00 as total_cost
                  {$baseQuery}
                  {$whereClause}
                  ORDER BY p.created_at DESC
                  LIMIT :length OFFSET :start";
    
    $stmt = $db->prepare($dataQuery);
    
    // Bind search params
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind limit and offset
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    
    $stmt->execute();
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return DataTables format
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => (int)$totalRecords,
        'recordsFiltered' => (int)$filteredRecords,
        'data' => $prescriptions
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_prescriptions_dt.php: " . $e->getMessage());
    echo json_encode([
        'draw' => $draw ?? 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Error in get_prescriptions_dt.php: " . $e->getMessage());
    echo json_encode([
        'draw' => $draw ?? 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'An error occurred'
    ]);
}
?>
