<?php
require_once __DIR__ . '/../shared/config.php';

class LabTestsController {
    protected $db;

    public function __construct() {
        if (function_exists('getDBConnection')) $this->db = getDBConnection(); else $this->db = null;
    }

    public function listForDataTable($request) {
        $draw = isset($request['draw']) ? (int)$request['draw'] : null;
        $start = isset($request['start']) ? (int)$request['start'] : 0;
        $length = isset($request['length']) ? (int)$request['length'] : 10;

        $searchValue = '';
        if (isset($request['search']) && is_array($request['search']) && isset($request['search']['value'])) $searchValue = trim($request['search']['value']);

        $where = '';
        $params = [];
        if ($searchValue !== '') {
            $where = "WHERE (test_id LIKE :q OR test_name LIKE :q OR test_category LIKE :q)";
            $params[':q'] = "%$searchValue%";
        }

        if (!$this->db) return ['draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[]];

        $total = (int)$this->db->query('SELECT COUNT(*) FROM lab_tests')->fetchColumn();

        if ($where !== '') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM lab_tests $where");
            foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $recordsFiltered = (int)$stmt->fetchColumn();
        } else $recordsFiltered = $total;

        $sql = "SELECT lt.id, lt.test_id, lt.patient_id, CONCAT(p.first_name,' ',p.last_name) AS patient_name, 
                       lt.test_name, lt.test_category, lt.test_date, lt.status,
                       CONCAT(u.first_name,' ',u.last_name) AS doctor_name,
                       CASE WHEN lr.id IS NOT NULL THEN 1 ELSE 0 END AS has_results,
                       lr.report_file AS latest_report_file
                FROM lab_tests lt 
                LEFT JOIN patients p ON lt.patient_id = p.id 
                LEFT JOIN doctors d ON lt.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                LEFT JOIN (
                    SELECT lab_test_id, id, report_file, 
                           ROW_NUMBER() OVER (PARTITION BY lab_test_id ORDER BY recorded_at DESC) as rn
                    FROM lab_results
                ) lr ON lt.id = lr.lab_test_id AND lr.rn = 1
                $where ORDER BY lt.test_date DESC LIMIT :start, :length";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'id'=>$r['id'],
                'test_id'=>$r['test_id'],
                'patient_id'=>$r['patient_id'],
                'patient_name'=>$r['patient_name'],
                'test_name'=>$r['test_name'],
                'test_category'=>$r['test_category'],
                'test_date'=>$r['test_date'],
                'status'=>$r['status'],
                'doctor_name'=>$r['doctor_name'] ?? null,
                'has_results'=>(int)$r['has_results'],
                'latest_report_file'=>$r['latest_report_file']
            ];
        }

        return ['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$recordsFiltered,'data'=>$data];
    }

    public function getById($id) {
        if (!$this->db) return null;
        $id = (int)$id;
        
        // Get lab test with patient and doctor info
        $stmt = $this->db->prepare('SELECT lt.*, CONCAT(p.first_name, " ", p.last_name) AS patient_name, p.patient_id AS patient_code, CONCAT(u.first_name, " ", u.last_name) AS doctor_name FROM lab_tests lt LEFT JOIN patients p ON lt.patient_id = p.id LEFT JOIN doctors d ON lt.doctor_id = d.id LEFT JOIN users u ON d.user_id = u.id WHERE lt.id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) return null;
        
        // Get latest lab results for this test
        $resultStmt = $this->db->prepare('SELECT lr.*, CONCAT(ru.first_name, " ", ru.last_name) AS recorded_by_name FROM lab_results lr LEFT JOIN users ru ON lr.recorded_by = ru.id WHERE lr.lab_test_id = :id ORDER BY lr.recorded_at DESC LIMIT 1');
        $resultStmt->bindValue(':id', $id, PDO::PARAM_INT);
        $resultStmt->execute();
        $latestResult = $resultStmt->fetch(PDO::FETCH_ASSOC);
        
        // Add lab results to the main row
        if ($latestResult) {
            $row['latest_result'] = $latestResult;
            $row['results'] = $latestResult['results'];
            $row['normal_range'] = $latestResult['normal_range'];
            $row['report_file'] = $latestResult['report_file'];
            $row['notes'] = $latestResult['notes'];
            $row['lab_technician'] = $latestResult['lab_technician'];
            $row['recorded_at'] = $latestResult['recorded_at'];
            $row['recorded_by_name'] = $latestResult['recorded_by_name'];
        } else {
            $row['latest_result'] = null;
            $row['results'] = null;
            $row['normal_range'] = null;
            $row['report_file'] = null;
            $row['notes'] = null;
            $row['lab_technician'] = null;
            $row['recorded_at'] = null;
            $row['recorded_by_name'] = null;
        }
        
        return $row;
    }

    public function create($data, $user_id = null) {
        if (!$this->db) return ['success'=>false,'message'=>'Database unavailable'];
        $patient_id = (int)($data['patient_id'] ?? 0);
        $doctor_id = (int)($data['doctor_id'] ?? 0);
        $test_name = trim($data['test_name'] ?? '');
        $test_category = trim($data['test_category'] ?? '');
        $test_date = trim($data['test_date'] ?? '');
        $notes = trim($data['notes'] ?? '');

        if ($patient_id <= 0 || $test_name === '' || $test_date === '') return ['success'=>false,'message'=>'Missing required fields'];

        // auto-assign doctor_id when the creator is a doctor and no doctor_id provided
        $user_type = $_SESSION['user_type'] ?? null;
        if (($doctor_id <= 0) && $user_type === 'doctor' && $user_id) {
            try {
                $dstmt = $this->db->prepare('SELECT id FROM doctors WHERE user_id = :uid LIMIT 1');
                $dstmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
                $dstmt->execute();
                $did = $dstmt->fetchColumn();
                if ($did) $doctor_id = (int)$did;
            } catch (Exception $e) {
                // fallback: leave doctor_id as provided
            }
        }

        // if creator is not a doctor, doctor_id must be provided
        if (($user_type !== 'doctor') && $doctor_id <= 0) {
            return ['success'=>false, 'message' => 'doctor_id is required when creating on behalf of a doctor'];
        }

        try {
            // ensure a non-null unique test_id (generate if not provided)
            $testId = trim($data['test_id'] ?? '');
            if ($testId === '') {
                try {
                    $row = $this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(test_id, 4) AS UNSIGNED)), 0) FROM lab_tests WHERE test_id REGEXP '^TST[0-9]+$'")->fetchColumn();
                    $next = (int)$row + 1;
                    $testId = 'TST' . str_pad($next, 3, '0', STR_PAD_LEFT);
                } catch (Exception $e) {
                    // fallback to timestamp-based id
                    $testId = 'TST' . time();
                }
            }

            $stmt = $this->db->prepare('INSERT INTO lab_tests (test_id, patient_id, doctor_id, test_name, test_category, test_date, sample_collected_date, status) VALUES (:tid, :pid, :did, :tname, :tcat, :tdate, :scol, :status)');
            $stmt->bindValue(':tid', $testId, PDO::PARAM_STR);
            $stmt->bindValue(':pid', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':did', $doctor_id > 0 ? $doctor_id : null, $doctor_id > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':tname', $test_name, PDO::PARAM_STR);
            $stmt->bindValue(':tcat', $test_category !== '' ? $test_category : null, $test_category !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tdate', $test_date, PDO::PARAM_STR);
            $stmt->bindValue(':scol', null, PDO::PARAM_NULL);
            $stmt->bindValue(':status', 'ordered', PDO::PARAM_STR);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            // if results, report_file, or lab_technician were provided, insert into lab_results (normalized)
            if (!empty($data['results']) || !empty($data['report_file']) || !empty($data['lab_technician']) || !empty($data['notes'])) {
                $this->insertLabResult($id, $data, $user_id);
            }
            // audit log
            $this->writeAuditLog($id, 'create', [], $data);
            return ['success'=>true,'id'=>$id];
        } catch (Exception $e) {
            error_log('[LabTestsController::create] Exception: '.$e->getMessage());
            return ['success'=>false,'message'=>'Insert failed'];
        }
    }

    public function update($id, $data, $user_id = null) {
        if (!$this->db) return ['success'=>false,'message'=>'Database unavailable'];
        $id = (int)$id; if ($id<=0) return ['success'=>false,'message'=>'Invalid id'];
        $test_name = trim($data['test_name'] ?? '');
        $test_category = trim($data['test_category'] ?? '');
        $test_date = trim($data['test_date'] ?? '');
        $status = trim($data['status'] ?? '');

        // enforce status transition permissions
        $user_type = $_SESSION['user_type'] ?? null;
        $newStatus = isset($data['status']) ? trim($data['status']) : null;
        $allowed = [
            'sample_collected' => ['admin','doctor','receptionist','secretary'],
            'in_progress'      => ['admin','doctor','receptionist','secretary'],
            'completed'        => ['admin','doctor'],
            'cancelled'        => ['admin','doctor'],
        ];
        if ($newStatus !== null) {
            if (!isset($allowed[$newStatus]) || !in_array($user_type, $allowed[$newStatus])) {
                return ['success'=>false,'message'=>'Not authorized to change status to ' . $newStatus];
            }
            // require results or report_file when completing
            if ($newStatus === 'completed' && empty($data['results']) && empty($data['report_file'])) {
                return ['success'=>false,'message'=>'Attach results or report before marking as completed'];
            }
        }

        try {
            $old = $this->getById($id);
            $sql = 'UPDATE lab_tests SET test_name = :tname, test_category = :tcat, test_date = :tdate, status = :status, updated_at = NOW() WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tname', $test_name !== '' ? $test_name : null, $test_name !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tcat', $test_category !== '' ? $test_category : null, $test_category !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tdate', $test_date !== '' ? $test_date : null, $test_date !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':status', $status !== '' ? $status : null, $status !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            // if results, report_file, lab_technician, or notes were provided, upsert into lab_results
            if (!empty($data['results']) || !empty($data['report_file']) || !empty($data['lab_technician']) || !empty($data['notes'])) {
                $this->updateLabResult($id, $data, $user_id);
            }
            // audit log
            $this->writeAuditLog($id, 'update', $old, $data);
            return ['success'=>true,'id'=>$id];
        } catch (Exception $e) {
            error_log('[LabTestsController::update] Exception: '.$e->getMessage());
            return ['success'=>false,'message'=>'Update failed'];
        }
    }

    private function writeAuditLog($recordId, $action, $oldValues, $newValues) {
        try {
            $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $sql = "INSERT INTO audit_log (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at) VALUES (:user_id, :action, 'lab_tests', :record_id, :old_values, :new_values, :ip, :ua, NOW())";
            $s = $this->db->prepare($sql);
            $s->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':record_id' => $recordId,
                ':old_values' => json_encode($oldValues),
                ':new_values' => json_encode($newValues),
                ':ip' => $ip,
                ':ua' => $ua
            ]);
        } catch (Exception $e) {
            // don't block main flow on audit errors
        }
    }

    private function insertLabResult($labTestId, $data, $userId = null) {
        if (!$this->db) return false;
        try {
            $stmt = $this->db->prepare('INSERT INTO lab_results (lab_test_id, recorded_by, recorded_at, results, normal_range, report_file, notes, lab_technician) VALUES (:ltid, :rb, :rtime, :results, :nrange, :rfile, :notes, :tech)');
            $stmt->bindValue(':ltid', $labTestId, PDO::PARAM_INT);
            $stmt->bindValue(':rb', $userId !== null ? $userId : null, $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':rtime', !empty($data['sample_collected_date']) ? $data['sample_collected_date'] : date('Y-m-d H:i:s'));
            $stmt->bindValue(':results', !empty($data['results']) ? $data['results'] : null, !empty($data['results']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':nrange', !empty($data['normal_range']) ? $data['normal_range'] : null, !empty($data['normal_range']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':rfile', !empty($data['report_file']) ? $data['report_file'] : null, !empty($data['report_file']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', !empty($data['notes']) ? $data['notes'] : null, !empty($data['notes']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tech', !empty($data['lab_technician']) ? $data['lab_technician'] : null, !empty($data['lab_technician']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('[LabTestsController::insertLabResult] Exception: '.$e->getMessage());
            return false;
        }
    }

    private function updateLabResult($labTestId, $data, $userId = null) {
        if (!$this->db) return false;
        try {
            // check if a lab_result exists for this lab_test
            $s = $this->db->prepare('SELECT id FROM lab_results WHERE lab_test_id = :ltid LIMIT 1');
            $s->bindValue(':ltid', $labTestId, PDO::PARAM_INT);
            $s->execute();
            $rid = $s->fetchColumn();
            if ($rid) {
                $stmt = $this->db->prepare('UPDATE lab_results SET recorded_by = :rb, recorded_at = :rtime, results = :results, normal_range = :nrange, report_file = :rfile, notes = :notes, lab_technician = :tech, updated_at = NOW() WHERE id = :id');
                $stmt->bindValue(':id', $rid, PDO::PARAM_INT);
            } else {
                $stmt = $this->db->prepare('INSERT INTO lab_results (lab_test_id, recorded_by, recorded_at, results, normal_range, report_file, notes, lab_technician) VALUES (:ltid, :rb, :rtime, :results, :nrange, :rfile, :notes, :tech)');
            }
            $stmt->bindValue(':ltid', $labTestId, PDO::PARAM_INT);
            $stmt->bindValue(':rb', $userId !== null ? $userId : null, $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':rtime', !empty($data['sample_collected_date']) ? $data['sample_collected_date'] : date('Y-m-d H:i:s'));
            $stmt->bindValue(':results', !empty($data['results']) ? $data['results'] : null, !empty($data['results']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':nrange', !empty($data['normal_range']) ? $data['normal_range'] : null, !empty($data['normal_range']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':rfile', !empty($data['report_file']) ? $data['report_file'] : null, !empty($data['report_file']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', !empty($data['notes']) ? $data['notes'] : null, !empty($data['notes']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tech', !empty($data['lab_technician']) ? $data['lab_technician'] : null, !empty($data['lab_technician']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            return $rid ?: $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log('[LabTestsController::updateLabResult] Exception: '.$e->getMessage());
            return false;
        }
    }

    public function delete($id, $user_id = null) {
        if (!$this->db) return ['success'=>false,'message'=>'Database unavailable'];
        $id = (int)$id; if ($id<=0) return ['success'=>false,'message'=>'Invalid id'];
        try {
            $stmt = $this->db->prepare('DELETE FROM lab_tests WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return ['success'=>true,'id'=>$id];
        } catch (Exception $e) {
            error_log('[LabTestsController::delete] Exception: '.$e->getMessage());
            return ['success'=>false,'message'=>'Delete failed'];
        }
    }

    public function getStatistics() {
        if (!$this->db) return [];
        
        try {
            $stats = [];
            
            // Total tests
            $stmt = $this->db->prepare('SELECT COUNT(*) FROM lab_tests');
            $stmt->execute();
            $stats['total_tests'] = (int)$stmt->fetchColumn();
            
            // Tests by status
            $stmt = $this->db->prepare('
                SELECT status, COUNT(*) as count 
                FROM lab_tests 
                GROUP BY status
            ');
            $stmt->execute();
            $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stats['by_status'] = [];
            foreach ($statusCounts as $row) {
                $stats['by_status'][$row['status']] = (int)$row['count'];
            }
            
            // Ensure all statuses are present
            $allStatuses = ['ordered', 'sample_collected', 'in_progress', 'completed', 'cancelled'];
            foreach ($allStatuses as $status) {
                if (!isset($stats['by_status'][$status])) {
                    $stats['by_status'][$status] = 0;
                }
            }
            
            // Recent activity (last 30 days)
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM lab_tests 
                WHERE test_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ');
            $stmt->execute();
            $stats['recent_tests'] = (int)$stmt->fetchColumn();
            
            // Tests by category (top 10)
            $stmt = $this->db->prepare('
                SELECT test_category, COUNT(*) as count 
                FROM lab_tests 
                WHERE test_category IS NOT NULL 
                AND test_category != ""
                GROUP BY test_category 
                ORDER BY count DESC 
                LIMIT 10
            ');
            $stmt->execute();
            $stats['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pending results (tests that need attention)
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM lab_tests 
                WHERE status IN ("ordered", "sample_collected", "in_progress")
            ');
            $stmt->execute();
            $stats['pending_results'] = (int)$stmt->fetchColumn();
            
            // Completed today
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM lab_tests 
                WHERE status = "completed"
                AND DATE(updated_at) = CURDATE()
            ');
            $stmt->execute();
            $stats['completed_today'] = (int)$stmt->fetchColumn();
            
            // Average turnaround time (in hours) for completed tests
            $stmt = $this->db->prepare('
                SELECT AVG(TIMESTAMPDIFF(HOUR, test_date, updated_at)) as avg_hours
                FROM lab_tests 
                WHERE status = "completed"
                AND updated_at IS NOT NULL
                AND test_date IS NOT NULL
                AND test_date >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
            ');
            $stmt->execute();
            $avgHours = $stmt->fetchColumn();
            $stats['avg_turnaround_hours'] = $avgHours ? round((float)$avgHours, 1) : 0;
            
            // Tests with critical values (if results contain specific keywords)
            $stmt = $this->db->prepare('
                SELECT COUNT(DISTINCT lt.id)
                FROM lab_tests lt
                JOIN lab_results lr ON lt.id = lr.lab_test_id
                WHERE (
                    lr.results LIKE "%critical%" OR
                    lr.results LIKE "%urgent%" OR
                    lr.results LIKE "%abnormal%" OR
                    lr.results LIKE "%high%" OR
                    lr.results LIKE "%low%"
                )
                AND lr.results IS NOT NULL
            ');
            $stmt->execute();
            $stats['critical_results'] = (int)$stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log('[LabTestsController::getStatistics] Exception: ' . $e->getMessage());
            return [];
        }
    }

    public function searchTests($criteria) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        try {
            $where = ['1=1'];
            $params = [];
            
            // Patient search
            if (!empty($criteria['patient_search'])) {
                $where[] = '(p.first_name LIKE :patient_search OR p.last_name LIKE :patient_search OR p.patient_id LIKE :patient_search OR CONCAT(p.first_name, " ", p.last_name) LIKE :patient_search)';
                $params[':patient_search'] = '%' . $criteria['patient_search'] . '%';
            }
            
            // Test name/category
            if (!empty($criteria['test_search'])) {
                $where[] = '(lt.test_name LIKE :test_search OR lt.test_category LIKE :test_search)';
                $params[':test_search'] = '%' . $criteria['test_search'] . '%';
            }
            
            // Status filter
            if (!empty($criteria['status']) && $criteria['status'] !== 'all') {
                $where[] = 'lt.status = :status';
                $params[':status'] = $criteria['status'];
            }
            
            // Date range
            if (!empty($criteria['date_from'])) {
                $where[] = 'lt.test_date >= :date_from';
                $params[':date_from'] = $criteria['date_from'];
            }
            
            if (!empty($criteria['date_to'])) {
                $where[] = 'lt.test_date <= :date_to';
                $params[':date_to'] = $criteria['date_to'];
            }
            
            // Doctor filter
            if (!empty($criteria['doctor_id'])) {
                $where[] = 'lt.doctor_id = :doctor_id';
                $params[':doctor_id'] = $criteria['doctor_id'];
            }
            
            // Category filter
            if (!empty($criteria['category'])) {
                $where[] = 'lt.test_category = :category';
                $params[':category'] = $criteria['category'];
            }
            
            // Critical results only
            if (!empty($criteria['critical_only']) && $criteria['critical_only'] === '1') {
                $where[] = 'EXISTS (SELECT 1 FROM lab_results lr WHERE lr.lab_test_id = lt.id AND (lr.results LIKE "%critical%" OR lr.results LIKE "%urgent%" OR lr.results LIKE "%abnormal%"))';
            }
            
            $whereClause = implode(' AND ', $where);
            
            $sql = "
                SELECT 
                    lt.*,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    p.patient_id as patient_code,
                    CONCAT(d.first_name, ' ', d.last_name) as doctor_name,
                    d.doctor_id as doctor_code,
                    lr.results,
                    lr.normal_range,
                    lr.lab_technician,
                    lr.recorded_at,
                    lr.report_file
                FROM lab_tests lt
                LEFT JOIN patients p ON lt.patient_id = p.id
                LEFT JOIN users d ON lt.doctor_id = d.id AND d.user_type = 'doctor'
                LEFT JOIN lab_results lr ON lt.id = lr.lab_test_id
                WHERE {$whereClause}
                ORDER BY lt.test_date DESC, lt.created_at DESC
            ";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $results];
            
        } catch (Exception $e) {
            error_log('[LabTestsController::searchTests] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Search failed'];
        }
    }

    public function getTestCategories() {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare('
                SELECT DISTINCT test_category 
                FROM lab_tests 
                WHERE test_category IS NOT NULL 
                AND test_category != ""
                ORDER BY test_category ASC
            ');
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (Exception $e) {
            error_log('[LabTestsController::getTestCategories] Exception: ' . $e->getMessage());
            return [];
        }
    }
}
