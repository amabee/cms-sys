<?php
require_once __DIR__ . '/../shared/config.php';

class QueueController {
    protected $db;

    public function __construct() {
        if (function_exists('getDBConnection')) $this->db = getDBConnection(); else $this->db = null;
    }

    /**
     * Get waiting patients queue for doctor dashboard
     */
    public function getWaitingQueue($doctor_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        try {
            $where = '';
            $params = [];
            
            // If doctor_id provided, filter by doctor's appointments
            if ($doctor_id) {
                $where = 'AND a.doctor_id = :doctor_id';
                $params[':doctor_id'] = $doctor_id;
            }
            
            $sql = "
                SELECT 
                    vq.id as queue_id,
                    vq.status as queue_status,
                    vq.queued_at,
                    vq.called_at,
                    vq.notes as queue_notes,
                    p.id as patient_id,
                    p.patient_id as patient_code,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    p.phone as patient_phone,
                    a.id as appointment_id,
                    a.appointment_time,
                    a.reason as appointment_reason,
                    a.status as appointment_status,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                FROM visit_queue vq
                LEFT JOIN appointments a ON vq.appointment_id = a.id
                LEFT JOIN patients p ON vq.patient_id = p.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE vq.status IN ('queued', 'called')
                {$where}
                ORDER BY vq.queued_at ASC
            ";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $queue];
            
        } catch (Exception $e) {
            error_log('[QueueController::getWaitingQueue] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to fetch queue'];
        }
    }

    /**
     * Add patient to queue (usually when they check in)
     */
    public function addToQueue($patient_id, $appointment_id = null, $notes = '', $vital_signs = []) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        try {
            // Check if patient is already in queue
            $checkStmt = $this->db->prepare("
                SELECT id FROM visit_queue 
                WHERE patient_id = :patient_id 
                AND status IN ('queued', 'called') 
                LIMIT 1
            ");
            $checkStmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn()) {
                return ['success' => false, 'message' => 'Patient already in queue'];
            }
            
            // Add vital signs to notes if provided
            if (!empty($vital_signs)) {
                $vitalSignsText = "\n\n--- Vital Signs ---";
                if (!empty($vital_signs['blood_pressure'])) $vitalSignsText .= "\nBP: " . $vital_signs['blood_pressure'];
                if (!empty($vital_signs['temperature'])) $vitalSignsText .= "\nTemp: " . $vital_signs['temperature'] . "°C";
                if (!empty($vital_signs['pulse'])) $vitalSignsText .= "\nPulse: " . $vital_signs['pulse'] . " bpm";
                if (!empty($vital_signs['respiratory_rate'])) $vitalSignsText .= "\nResp Rate: " . $vital_signs['respiratory_rate'];
                if (!empty($vital_signs['weight'])) $vitalSignsText .= "\nWeight: " . $vital_signs['weight'] . " kg";
                if (!empty($vital_signs['height'])) $vitalSignsText .= "\nHeight: " . $vital_signs['height'] . " cm";
                if (!empty($vital_signs['oxygen_saturation'])) $vitalSignsText .= "\nO2 Sat: " . $vital_signs['oxygen_saturation'] . "%";
                if (!empty($vital_signs['pain_level'])) $vitalSignsText .= "\nPain Level: " . $vital_signs['pain_level'] . "/10";
                $notes .= $vitalSignsText;
            }
            
            $sql = "
                INSERT INTO visit_queue (patient_id, appointment_id, status, queued_at, notes)
                VALUES (:patient_id, :appointment_id, 'queued', NOW(), :notes)
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':appointment_id', $appointment_id, $appointment_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
            $stmt->execute();
            
            $queue_id = $this->db->lastInsertId();
            
            // Log the action
            $this->logQueueAction($queue_id, 'queued', $_SESSION['user_id'] ?? null, 'Patient added to queue');
            
            return ['success' => true, 'queue_id' => $queue_id];
            
        } catch (Exception $e) {
            error_log('[QueueController::addToQueue] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to add to queue'];
        }
    }

    /**
     * Update queue status (call next, mark as served, etc.)
     */
    public function updateQueueStatus($queue_id, $status, $user_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        $allowed_statuses = ['queued', 'called', 'served', 'cancelled'];
        if (!in_array($status, $allowed_statuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        try {
            $updates = ['status = :status'];
            $params = [':queue_id' => $queue_id, ':status' => $status];
            
            if ($status === 'called') {
                $updates[] = 'called_at = NOW()';
            } elseif ($status === 'served') {
                $updates[] = 'served_at = NOW()';
            }
            
            $sql = "UPDATE visit_queue SET " . implode(', ', $updates) . " WHERE id = :queue_id";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            // Log the action
            $this->logQueueAction($queue_id, $status, $user_id, "Queue status updated to {$status}");
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log('[QueueController::updateQueueStatus] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update queue status'];
        }
    }

    /**
     * Get next patient in queue
     */
    public function getNextPatient($doctor_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        try {
            $where = '';
            $params = [];
            
            if ($doctor_id) {
                $where = 'AND a.doctor_id = :doctor_id';
                $params[':doctor_id'] = $doctor_id;
            }
            
            $sql = "
                SELECT 
                    vq.id as queue_id,
                    p.id as patient_id,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    p.patient_id as patient_code,
                    a.appointment_time,
                    a.reason
                FROM visit_queue vq
                LEFT JOIN appointments a ON vq.appointment_id = a.id
                LEFT JOIN patients p ON vq.patient_id = p.id
                WHERE vq.status = 'queued'
                {$where}
                ORDER BY vq.queued_at ASC
                LIMIT 1
            ";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $next = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $next];
            
        } catch (Exception $e) {
            error_log('[QueueController::getNextPatient] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get next patient'];
        }
    }

    /**
     * Log queue actions
     */
    private function logQueueAction($queue_id, $action, $user_id = null, $details = '') {
        if (!$this->db) return false;
        
        try {
            $sql = "
                INSERT INTO queue_logs (visit_queue_id, action, user_id, details, created_at)
                VALUES (:queue_id, :action, :user_id, :details, NOW())
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':queue_id', $queue_id, PDO::PARAM_INT);
            $stmt->bindValue(':action', $action, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $user_id, $user_id ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':details', $details, PDO::PARAM_STR);
            $stmt->execute();
            
            return true;
            
        } catch (Exception $e) {
            error_log('[QueueController::logQueueAction] Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats($doctor_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        
        try {
            $where = '';
            $params = [];
            
            if ($doctor_id) {
                $where = 'AND a.doctor_id = :doctor_id';
                $params[':doctor_id'] = $doctor_id;
            }
            
            $sql = "
                SELECT 
                    COUNT(CASE WHEN vq.status = 'queued' THEN 1 END) as waiting,
                    COUNT(CASE WHEN vq.status = 'called' THEN 1 END) as called,
                    COUNT(CASE WHEN vq.status = 'served' THEN 1 END) as served,
                    COUNT(CASE WHEN vq.status = 'cancelled' THEN 1 END) as cancelled
                FROM visit_queue vq
                LEFT JOIN appointments a ON vq.appointment_id = a.id
                WHERE DATE(vq.created_at) = CURDATE()
                {$where}
            ";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $stats];
            
        } catch (Exception $e) {
            error_log('[QueueController::getQueueStats] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get queue stats'];
        }
    }
}
