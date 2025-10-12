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
            $where = "WHERE (test_id LIKE :q OR test_name LIKE :q OR test_category LIKE :q OR results LIKE :q)";
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

        $sql = "SELECT lt.id, lt.test_id, lt.patient_id, CONCAT(p.first_name,' ',p.last_name) AS patient_name, lt.test_name, lt.test_category, lt.test_date, lt.status, lt.report_file FROM lab_tests lt LEFT JOIN patients p ON lt.patient_id = p.id $where ORDER BY lt.test_date DESC LIMIT :start, :length";
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
                'report_file'=>$r['report_file']
            ];
        }

        return ['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$recordsFiltered,'data'=>$data];
    }

    public function getById($id) {
        if (!$this->db) return null;
        $id = (int)$id;
        $stmt = $this->db->prepare('SELECT lt.*, CONCAT(p.first_name, " ", p.last_name) AS patient_name, p.patient_id AS patient_code, CONCAT(u.first_name, " ", u.last_name) AS doctor_name FROM lab_tests lt LEFT JOIN patients p ON lt.patient_id = p.id LEFT JOIN doctors d ON lt.doctor_id = d.id LEFT JOIN users u ON d.user_id = u.id WHERE lt.id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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

            $stmt = $this->db->prepare('INSERT INTO lab_tests (test_id, patient_id, doctor_id, test_name, test_category, test_date, sample_collected_date, results, normal_range, status, lab_technician, report_file, notes) VALUES (:tid, :pid, :did, :tname, :tcat, :tdate, :scol, :results, :nrange, :status, :tech, :rfile, :notes)');
            $stmt->bindValue(':tid', $testId, PDO::PARAM_STR);
            $stmt->bindValue(':pid', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':did', $doctor_id > 0 ? $doctor_id : null, $doctor_id > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':tname', $test_name, PDO::PARAM_STR);
            $stmt->bindValue(':tcat', $test_category !== '' ? $test_category : null, $test_category !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tdate', $test_date, PDO::PARAM_STR);
            $stmt->bindValue(':scol', null, PDO::PARAM_NULL);
            $stmt->bindValue(':results', null, PDO::PARAM_NULL);
            $stmt->bindValue(':nrange', null, PDO::PARAM_NULL);
            $stmt->bindValue(':status', 'ordered', PDO::PARAM_STR);
            $stmt->bindValue(':tech', null, PDO::PARAM_NULL);
            $stmt->bindValue(':rfile', null, PDO::PARAM_NULL);
            $stmt->bindValue(':notes', $notes !== '' ? $notes : null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            // if results or report_file were provided, insert into lab_results (normalized)
            if (!empty($data['results']) || !empty($data['report_file'])) {
                $this->insertLabResult($id, $data, $user_id);
                // keep lab_tests row clean (report/results stored in lab_results)
                try {
                    $u = $this->db->prepare('UPDATE lab_tests SET results = NULL, normal_range = NULL, report_file = NULL, notes = NULL WHERE id = :id');
                    $u->bindValue(':id', $id, PDO::PARAM_INT);
                    $u->execute();
                } catch (Exception $e) { /* ignore */ }
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
        $results = trim($data['results'] ?? '');
        $status = trim($data['status'] ?? '');
        $report_file = trim($data['report_file'] ?? '');
        $notes = trim($data['notes'] ?? '');

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
            $sql = 'UPDATE lab_tests SET test_name = :tname, test_category = :tcat, test_date = :tdate, results = :results, status = :status, report_file = :rfile, notes = :notes, updated_at = NOW() WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':tname', $test_name !== '' ? $test_name : null, $test_name !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tcat', $test_category !== '' ? $test_category : null, $test_category !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':tdate', $test_date !== '' ? $test_date : null, $test_date !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':results', $results !== '' ? $results : null, $results !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':status', $status !== '' ? $status : null, $status !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':rfile', $report_file !== '' ? $report_file : null, $report_file !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', $notes !== '' ? $notes : null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            // if results or report_file were provided, upsert into lab_results and clear lab_tests fields
            if (!empty($data['results']) || !empty($data['report_file'])) {
                $this->updateLabResult($id, $data, $user_id);
                try {
                    $u = $this->db->prepare('UPDATE lab_tests SET results = NULL, normal_range = NULL, report_file = NULL, notes = NULL WHERE id = :id');
                    $u->bindValue(':id', $id, PDO::PARAM_INT);
                    $u->execute();
                } catch (Exception $e) { /* ignore */ }
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
            $stmt = $this->db->prepare('INSERT INTO lab_results (lab_test_id, recorded_by, recorded_at, results, normal_range, report_file, notes) VALUES (:ltid, :rb, :rtime, :results, :nrange, :rfile, :notes)');
            $stmt->bindValue(':ltid', $labTestId, PDO::PARAM_INT);
            $stmt->bindValue(':rb', $userId !== null ? $userId : null, $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':rtime', !empty($data['sample_collected_date']) ? $data['sample_collected_date'] : date('Y-m-d H:i:s'));
            $stmt->bindValue(':results', !empty($data['results']) ? $data['results'] : null, !empty($data['results']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':nrange', !empty($data['normal_range']) ? $data['normal_range'] : null, !empty($data['normal_range']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':rfile', !empty($data['report_file']) ? $data['report_file'] : null, !empty($data['report_file']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', !empty($data['notes']) ? $data['notes'] : null, !empty($data['notes']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
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
                $stmt = $this->db->prepare('UPDATE lab_results SET recorded_by = :rb, recorded_at = :rtime, results = :results, normal_range = :nrange, report_file = :rfile, notes = :notes, updated_at = NOW() WHERE id = :id');
                $stmt->bindValue(':id', $rid, PDO::PARAM_INT);
            } else {
                $stmt = $this->db->prepare('INSERT INTO lab_results (lab_test_id, recorded_by, recorded_at, results, normal_range, report_file, notes) VALUES (:ltid, :rb, :rtime, :results, :nrange, :rfile, :notes)');
            }
            $stmt->bindValue(':ltid', $labTestId, PDO::PARAM_INT);
            $stmt->bindValue(':rb', $userId !== null ? $userId : null, $userId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':rtime', !empty($data['sample_collected_date']) ? $data['sample_collected_date'] : date('Y-m-d H:i:s'));
            $stmt->bindValue(':results', !empty($data['results']) ? $data['results'] : null, !empty($data['results']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':nrange', !empty($data['normal_range']) ? $data['normal_range'] : null, !empty($data['normal_range']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':rfile', !empty($data['report_file']) ? $data['report_file'] : null, !empty($data['report_file']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', !empty($data['notes']) ? $data['notes'] : null, !empty($data['notes']) ? PDO::PARAM_STR : PDO::PARAM_NULL);
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
}
