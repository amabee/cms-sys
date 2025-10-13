<?php
require_once __DIR__ . '/../shared/config.php';

class MedicalRecordsController {
    protected $db;

    public function __construct() {
        if (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        } else {
            $this->db = null;
        }
    }

    public function listForDataTable($request) {
        $draw = isset($request['draw']) ? (int)$request['draw'] : null;
        $start = isset($request['start']) ? (int)$request['start'] : 0;
        $length = isset($request['length']) ? (int)$request['length'] : 10;

        $searchValue = '';
        if (isset($request['search']) && is_array($request['search']) && isset($request['search']['value'])) {
            $searchValue = trim($request['search']['value']);
        }

        // Enhanced filtering
        $patientFilter = isset($request['patient_filter']) ? trim($request['patient_filter']) : '';
        $doctorFilter = isset($request['doctor_filter']) ? trim($request['doctor_filter']) : '';
        $dateFromFilter = isset($request['date_from']) ? trim($request['date_from']) : '';
        $dateToFilter = isset($request['date_to']) ? trim($request['date_to']) : '';

        $whereConditions = [];
        $params = [];
        
        if ($searchValue !== '') {
            $whereConditions[] = "(mr.record_id LIKE :search OR mr.chief_complaint LIKE :search OR mr.diagnosis LIKE :search OR mr.treatment LIKE :search OR mr.notes LIKE :search OR p.first_name LIKE :search OR p.last_name LIKE :search OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)";
            $params[':search'] = "%$searchValue%";
        }

        if ($patientFilter !== '') {
            $whereConditions[] = "mr.patient_id = :patient_id";
            $params[':patient_id'] = (int)$patientFilter;
        }

        if ($doctorFilter !== '') {
            $whereConditions[] = "mr.doctor_id = :doctor_id";
            $params[':doctor_id'] = (int)$doctorFilter;
        }

        if ($dateFromFilter !== '') {
            $whereConditions[] = "mr.visit_date >= :date_from";
            $params[':date_from'] = $dateFromFilter;
        }

        if ($dateToFilter !== '') {
            $whereConditions[] = "mr.visit_date <= :date_to";
            $params[':date_to'] = $dateToFilter;
        }

        $where = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        if (!$this->db) {
            return ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $total = (int)$this->db->query('SELECT COUNT(*) FROM medical_records')->fetchColumn();

        if ($where !== '') {
            $countSql = "SELECT COUNT(*) FROM medical_records mr LEFT JOIN patients p ON mr.patient_id = p.id LEFT JOIN doctors d ON mr.doctor_id = d.id LEFT JOIN users u ON d.user_id = u.id $where";
            $stmt = $this->db->prepare($countSql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $recordsFiltered = (int)$stmt->fetchColumn();
        } else {
            $recordsFiltered = $total;
        }

        // Enhanced ordering
        $orderColumn = isset($request['order'][0]['column']) ? (int)$request['order'][0]['column'] : 4;
        $orderDir = isset($request['order'][0]['dir']) && $request['order'][0]['dir'] === 'asc' ? 'ASC' : 'DESC';
        
        $orderColumns = [
            0 => 'mr.record_id',
            1 => 'patient_name',
            2 => 'mr.chief_complaint',
            3 => 'mr.diagnosis', 
            4 => 'mr.visit_date',
            5 => 'doctor_name'
        ];
        
        $orderBy = isset($orderColumns[$orderColumn]) ? $orderColumns[$orderColumn] : 'mr.visit_date';
        $orderSql = "ORDER BY $orderBy $orderDir, mr.id DESC";

        // Enhanced SQL with more details
        $sql = "SELECT 
                    mr.id, 
                    mr.record_id, 
                    mr.patient_id, 
                    p.patient_id AS patient_code, 
                    CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                    p.phone AS patient_phone,
                    mr.doctor_id, 
                    d.doctor_id AS doctor_code, 
                    CONCAT(u.first_name, ' ', u.last_name) AS doctor_name,
                    mr.appointment_id,
                    mr.visit_date, 
                    mr.chief_complaint, 
                    mr.diagnosis, 
                    mr.treatment,
                    mr.prescription,
                    mr.vital_signs,
                    mr.notes,
                    mr.created_at,
                    mr.updated_at,
                    (SELECT COUNT(*) FROM medical_record_attachments WHERE medical_record_id = mr.id) AS attachment_count
                FROM medical_records mr 
                LEFT JOIN patients p ON mr.patient_id = p.id 
                LEFT JOIN doctors d ON mr.doctor_id = d.id 
                LEFT JOIN users u ON d.user_id = u.id 
                $where $orderSql 
                LIMIT :start, :length";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $vitalSigns = null;
            if (!empty($r['vital_signs'])) {
                $vitalSigns = json_decode($r['vital_signs'], true);
            }
            
            $data[] = [
                'id' => $r['id'],
                'record_id' => $r['record_id'],
                'patient_id' => $r['patient_id'],
                'patient_code' => $r['patient_code'],
                'patient_name' => $r['patient_name'],
                'patient_phone' => $r['patient_phone'],
                'doctor_id' => $r['doctor_id'],
                'doctor_code' => $r['doctor_code'],
                'doctor_name' => $r['doctor_name'],
                'appointment_id' => $r['appointment_id'],
                'visit_date' => $r['visit_date'],
                'chief_complaint' => $r['chief_complaint'],
                'diagnosis' => $r['diagnosis'],
                'treatment' => $r['treatment'],
                'prescription' => $r['prescription'],
                'vital_signs' => $vitalSigns,
                'notes' => $r['notes'],
                'created_at' => $r['created_at'],
                'updated_at' => $r['updated_at'],
                'attachment_count' => (int)$r['attachment_count'],
                'has_attachments' => (int)$r['attachment_count'] > 0
            ];
        }

        return ['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $recordsFiltered, 'data' => $data];
    }

    public function getById($id) {
        if (!$this->db) return null;
        $id = (int)$id;
        $stmt = $this->db->prepare('SELECT mr.*, p.patient_id AS patient_code, CONCAT(p.first_name, " ", p.last_name) AS patient_name, d.doctor_id AS doctor_code, CONCAT(u.first_name, " ", u.last_name) AS doctor_name FROM medical_records mr LEFT JOIN patients p ON mr.patient_id = p.id LEFT JOIN doctors d ON mr.doctor_id = d.id LEFT JOIN users u ON d.user_id = u.id WHERE mr.id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        // decode vital_signs JSON if present
        if (!empty($row['vital_signs'])) {
            $row['vital_signs'] = json_decode($row['vital_signs'], true);
        }
        // fetch attachments
        try {
            $stmtA = $this->db->prepare('SELECT id, file_path, original_name, uploaded_by, uploaded_at FROM medical_record_attachments WHERE medical_record_id = :mrid ORDER BY uploaded_at DESC');
            $stmtA->bindValue(':mrid', $id, PDO::PARAM_INT);
            $stmtA->execute();
            $atts = $stmtA->fetchAll(PDO::FETCH_ASSOC);
            $row['attachments'] = $atts ?: [];
        } catch (Exception $e) { $row['attachments'] = []; }
        return $row;
    }

    public function create($data, $user_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        $patient_id = (int)($data['patient_id'] ?? 0);
        $doctor_id = (int)($data['doctor_id'] ?? 0);
        $appointment_id = isset($data['appointment_id']) && $data['appointment_id'] !== '' ? (int)$data['appointment_id'] : null;
        $visit_date = trim($data['visit_date'] ?? '');
        $chief = trim($data['chief_complaint'] ?? '');
        $diagnosis = trim($data['diagnosis'] ?? '');
        $treatment = trim($data['treatment'] ?? '');
        $prescription = trim($data['prescription'] ?? '');
        $vitals = $data['vital_signs'] ?? null;
        $notes = trim($data['notes'] ?? '');

        if ($patient_id <= 0 || $doctor_id <= 0 || $visit_date === '') return ['success' => false, 'message' => 'Missing required fields'];

        // normalize vitals to JSON
        $vitalsJson = null;
        if ($vitals !== null && $vitals !== '') {
            if (is_array($vitals)) {
                $vitalsJson = json_encode($vitals);
            } else {
                // if string, try decode then re-encode to ensure valid JSON
                $decoded = json_decode($vitals, true);
                $vitalsJson = $decoded === null ? json_encode(['raw' => $vitals]) : json_encode($decoded);
            }
        }

        try {
            $recordId = $this->generateRecordId();
            $stmt = $this->db->prepare('INSERT INTO medical_records (record_id, patient_id, doctor_id, appointment_id, visit_date, chief_complaint, diagnosis, treatment, prescription, vital_signs, notes) VALUES (:rid, :pid, :did, :aid, :vdate, :chief, :diagnosis, :treatment, :prescription, :vitals, :notes)');
            $stmt->bindValue(':rid', $recordId, PDO::PARAM_STR);
            $stmt->bindValue(':pid', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':did', $doctor_id, PDO::PARAM_INT);
            if ($appointment_id !== null) $stmt->bindValue(':aid', $appointment_id, PDO::PARAM_INT); else $stmt->bindValue(':aid', null, PDO::PARAM_NULL);
            $stmt->bindValue(':vdate', $visit_date, PDO::PARAM_STR);
            $stmt->bindValue(':chief', $chief !== '' ? $chief : null, $chief !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':diagnosis', $diagnosis !== '' ? $diagnosis : null, $diagnosis !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':treatment', $treatment !== '' ? $treatment : null, $treatment !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':prescription', $prescription !== '' ? $prescription : null, $prescription !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':vitals', $vitalsJson !== null ? $vitalsJson : null, $vitalsJson !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', $notes !== '' ? $notes : null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->execute();
            $id = $this->db->lastInsertId();
            // handle attachment if provided
            if (!empty($data['attachment']) && is_array($data['attachment'])) {
                try {
                    $a = $data['attachment'];
                    $ins = $this->db->prepare('INSERT INTO medical_record_attachments (medical_record_id, file_path, original_name, uploaded_by) VALUES (:mrid, :path, :orig, :uid)');
                    $ins->bindValue(':mrid', $id, PDO::PARAM_INT);
                    $ins->bindValue(':path', $a['path'], PDO::PARAM_STR);
                    $ins->bindValue(':orig', $a['original_name'], PDO::PARAM_STR);
                    $ins->bindValue(':uid', $user_id !== null ? $user_id : null, $user_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $ins->execute();
                } catch (Exception $e) { error_log('[MedicalRecordsController::create] Attachment insert failed: '.$e->getMessage()); }
            }
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log('[MedicalRecordsController::create] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Insert failed'];
        }
    }

    public function update($id, $data, $user_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        $id = (int)$id;
        if ($id <= 0) return ['success' => false, 'message' => 'Invalid id'];

        $visit_date = trim($data['visit_date'] ?? '');
        $chief = trim($data['chief_complaint'] ?? '');
        $diagnosis = trim($data['diagnosis'] ?? '');
        $treatment = trim($data['treatment'] ?? '');
        $prescription = trim($data['prescription'] ?? '');
        $vitals = $data['vital_signs'] ?? null;
        $notes = trim($data['notes'] ?? '');

        $vitalsJson = null;
        if ($vitals !== null && $vitals !== '') {
            if (is_array($vitals)) $vitalsJson = json_encode($vitals);
            else {
                $decoded = json_decode($vitals, true);
                $vitalsJson = $decoded === null ? json_encode(['raw' => $vitals]) : json_encode($decoded);
            }
        }

        try {
            $sql = 'UPDATE medical_records SET visit_date = :vdate, chief_complaint = :chief, diagnosis = :diagnosis, treatment = :treatment, prescription = :prescription, vital_signs = :vitals, notes = :notes, updated_at = NOW() WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':vdate', $visit_date !== '' ? $visit_date : null, $visit_date !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':chief', $chief !== '' ? $chief : null, $chief !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':diagnosis', $diagnosis !== '' ? $diagnosis : null, $diagnosis !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':treatment', $treatment !== '' ? $treatment : null, $treatment !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':prescription', $prescription !== '' ? $prescription : null, $prescription !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':vitals', $vitalsJson !== null ? $vitalsJson : null, $vitalsJson !== null ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', $notes !== '' ? $notes : null, $notes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            // handle attachment upsert
            if (!empty($data['attachment']) && is_array($data['attachment'])) {
                try {
                    $a = $data['attachment'];
                    $s = $this->db->prepare('SELECT id FROM medical_record_attachments WHERE medical_record_id = :mrid LIMIT 1');
                    $s->bindValue(':mrid', $id, PDO::PARAM_INT);
                    $s->execute();
                    $aid = $s->fetchColumn();
                    if ($aid) {
                        $u = $this->db->prepare('UPDATE medical_record_attachments SET file_path = :path, original_name = :orig, uploaded_by = :uid, uploaded_at = NOW() WHERE id = :id');
                        $u->bindValue(':id', $aid, PDO::PARAM_INT);
                    } else {
                        $u = $this->db->prepare('INSERT INTO medical_record_attachments (medical_record_id, file_path, original_name, uploaded_by) VALUES (:mrid, :path, :orig, :uid)');
                        $u->bindValue(':mrid', $id, PDO::PARAM_INT);
                    }
                    $u->bindValue(':path', $a['path'], PDO::PARAM_STR);
                    $u->bindValue(':orig', $a['original_name'], PDO::PARAM_STR);
                    $u->bindValue(':uid', $user_id !== null ? $user_id : null, $user_id !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                    $u->execute();
                } catch (Exception $e) { error_log('[MedicalRecordsController::update] Attachment upsert failed: '.$e->getMessage()); }
            }
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log('[MedicalRecordsController::update] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Update failed'];
        }
    }

    public function delete($id, $user_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        $id = (int)$id;
        if ($id <= 0) return ['success' => false, 'message' => 'Invalid id'];
        try {
            // Delete attachments first (if table exists)
            try {
                $stmt = $this->db->prepare('DELETE FROM medical_record_attachments WHERE medical_record_id = :id');
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->execute();
            } catch (Exception $e) {
                // Attachments table might not exist yet
            }
            
            // Delete the medical record
            $stmt = $this->db->prepare('DELETE FROM medical_records WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log('[MedicalRecordsController::delete] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }

    public function getStatistics() {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        try {
            // Total records
            $totalRecords = (int)$this->db->query('SELECT COUNT(*) FROM medical_records')->fetchColumn();
            
            // Records this month
            $thisMonth = (int)$this->db->query("SELECT COUNT(*) FROM medical_records WHERE visit_date >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
            
            // Records today
            $today = (int)$this->db->query('SELECT COUNT(*) FROM medical_records WHERE visit_date = CURDATE()')->fetchColumn();
            
            // Top diagnoses
            $diagnosesStmt = $this->db->query("
                SELECT diagnosis, COUNT(*) as count 
                FROM medical_records 
                WHERE diagnosis IS NOT NULL AND diagnosis != ''
                GROUP BY diagnosis 
                ORDER BY count DESC 
                LIMIT 5
            ");
            $topDiagnoses = $diagnosesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Records by doctor (top 5)
            $doctorsStmt = $this->db->query("
                SELECT 
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    COUNT(*) as record_count
                FROM medical_records mr
                LEFT JOIN doctors d ON mr.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                GROUP BY mr.doctor_id, doctor_name
                ORDER BY record_count DESC
                LIMIT 5
            ");
            $recordsByDoctor = $doctorsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent records
            $recentStmt = $this->db->query("
                SELECT 
                    mr.id,
                    mr.record_id,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    mr.visit_date,
                    mr.chief_complaint
                FROM medical_records mr
                LEFT JOIN patients p ON mr.patient_id = p.id
                LEFT JOIN doctors d ON mr.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                ORDER BY mr.visit_date DESC, mr.created_at DESC
                LIMIT 10
            ");
            $recentRecords = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'total_records' => $totalRecords,
                    'records_this_month' => $thisMonth,
                    'records_today' => $today,
                    'top_diagnoses' => $topDiagnoses,
                    'records_by_doctor' => $recordsByDoctor,
                    'recent_records' => $recentRecords
                ]
            ];
        } catch (Exception $e) {
            error_log('[MedicalRecordsController::getStatistics] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get statistics'];
        }
    }

    public function getByPatient($patientId, $limit = 10) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        $patientId = (int)$patientId;
        if ($patientId <= 0) return ['success' => false, 'message' => 'Invalid patient ID'];
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    mr.*,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    d.doctor_id as doctor_code
                FROM medical_records mr
                LEFT JOIN doctors d ON mr.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE mr.patient_id = :patient_id
                ORDER BY mr.visit_date DESC, mr.created_at DESC
                LIMIT :limit
            ");
            $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode vital signs for each record
            foreach ($records as &$record) {
                if (!empty($record['vital_signs'])) {
                    $record['vital_signs'] = json_decode($record['vital_signs'], true);
                }
            }
            
            return ['success' => true, 'data' => $records];
        } catch (Exception $e) {
            error_log('[MedicalRecordsController::getByPatient] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get patient records'];
        }
    }

    public function searchRecords($searchTerm, $filters = []) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        $searchTerm = trim($searchTerm);
        if (empty($searchTerm)) return ['success' => true, 'data' => []];
        
        try {
            $whereConditions = [
                "(mr.chief_complaint LIKE :search OR mr.diagnosis LIKE :search OR mr.treatment LIKE :search OR mr.notes LIKE :search OR mr.record_id LIKE :search)"
            ];
            $params = [':search' => "%$searchTerm%"];
            
            // Add optional filters
            if (isset($filters['patient_id']) && !empty($filters['patient_id'])) {
                $whereConditions[] = "mr.patient_id = :patient_id";
                $params[':patient_id'] = (int)$filters['patient_id'];
            }
            
            if (isset($filters['doctor_id']) && !empty($filters['doctor_id'])) {
                $whereConditions[] = "mr.doctor_id = :doctor_id";
                $params[':doctor_id'] = (int)$filters['doctor_id'];
            }
            
            if (isset($filters['date_from']) && !empty($filters['date_from'])) {
                $whereConditions[] = "mr.visit_date >= :date_from";
                $params[':date_from'] = $filters['date_from'];
            }
            
            if (isset($filters['date_to']) && !empty($filters['date_to'])) {
                $whereConditions[] = "mr.visit_date <= :date_to";
                $params[':date_to'] = $filters['date_to'];
            }
            
            $where = 'WHERE ' . implode(' AND ', $whereConditions);
            
            $stmt = $this->db->prepare("
                SELECT 
                    mr.id,
                    mr.record_id,
                    mr.patient_id,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    mr.doctor_id,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                    mr.visit_date,
                    mr.chief_complaint,
                    mr.diagnosis,
                    mr.treatment
                FROM medical_records mr
                LEFT JOIN patients p ON mr.patient_id = p.id
                LEFT JOIN doctors d ON mr.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                $where
                ORDER BY mr.visit_date DESC
                LIMIT 50
            ");
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $results];
        } catch (Exception $e) {
            error_log('[MedicalRecordsController::searchRecords] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Search failed'];
        }
    }

    private function generateRecordId() {
        $prefix = 'MR';
        $date = date('Ymd');
        
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM medical_records WHERE DATE(created_at) = CURDATE()");
            $stmt->execute();
            $count = (int)$stmt->fetchColumn() + 1;
            
            return $prefix . $date . str_pad($count, 3, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            return $prefix . $date . rand(100, 999);
        }
    }
}
