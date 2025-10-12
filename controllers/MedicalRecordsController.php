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

        $where = '';
        $params = [];
        if ($searchValue !== '') {
            $where = "WHERE (record_id LIKE :q OR chief_complaint LIKE :q OR diagnosis LIKE :q OR treatment LIKE :q OR notes LIKE :q)";
            $params[':q'] = "%$searchValue%";
        }

        if (!$this->db) {
            return ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $total = (int)$this->db->query('SELECT COUNT(*) FROM medical_records')->fetchColumn();

        if ($where !== '') {
            $countSql = "SELECT COUNT(*) FROM medical_records $where";
            $stmt = $this->db->prepare($countSql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $recordsFiltered = (int)$stmt->fetchColumn();
        } else {
            $recordsFiltered = $total;
        }

        $orderSql = 'ORDER BY visit_date DESC, id DESC';
        $sql = "SELECT mr.id, mr.record_id, mr.patient_id, p.patient_id AS patient_code, CONCAT(p.first_name, ' ', p.last_name) AS patient_name, mr.doctor_id, d.doctor_id AS doctor_code, CONCAT(u.first_name, ' ', u.last_name) AS doctor_name, mr.visit_date, mr.chief_complaint, mr.diagnosis, mr.treatment, mr.notes FROM medical_records mr LEFT JOIN patients p ON mr.patient_id = p.id LEFT JOIN doctors d ON mr.doctor_id = d.id LEFT JOIN users u ON d.user_id = u.id $where $orderSql LIMIT :start, :length";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'id' => $r['id'],
                'record_id' => $r['record_id'],
                'patient_id' => $r['patient_id'],
                'patient_code' => $r['patient_code'],
                'patient_name' => $r['patient_name'],
                'doctor_id' => $r['doctor_id'],
                'doctor_code' => $r['doctor_code'],
                'doctor_name' => $r['doctor_name'],
                'visit_date' => $r['visit_date'],
                'chief_complaint' => $r['chief_complaint'],
                'diagnosis' => $r['diagnosis'],
                'treatment' => $r['treatment'],
                'notes' => $r['notes']
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
            $stmt = $this->db->prepare('INSERT INTO medical_records (record_id, patient_id, doctor_id, appointment_id, visit_date, chief_complaint, diagnosis, treatment, prescription, vital_signs, notes) VALUES (:rid, :pid, :did, :aid, :vdate, :chief, :diagnosis, :treatment, :prescription, :vitals, :notes)');
            $stmt->bindValue(':rid', null, PDO::PARAM_NULL);
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
            $stmt = $this->db->prepare('DELETE FROM medical_records WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log('[MedicalRecordsController::delete] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Delete failed'];
        }
    }
}
