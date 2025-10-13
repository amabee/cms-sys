<?php
/**
 * Patient Portal Controller
 * Handles all patient portal functionality including authentication, appointments, 
 * medical records access, messaging, and health tracking
 */

require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/SystemLogger.php';

class PatientPortalController {
    
    private $pdo;
    private $logger;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->logger = new SystemLogger();
    }
    
    /**
     * Authenticate patient for portal access
     */
    public function authenticatePatient($email, $password) {
        try {
            // Check if patient exists and is active
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.first_name, p.last_name, p.email, p.phone, p.password,
                       p.portal_enabled, p.portal_login_attempts, p.portal_locked_until,
                       p.email_verified
                FROM patients p 
                WHERE p.email = :email AND p.is_active = 1
            ");
            
            $stmt->execute(['email' => $email]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$patient) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Check if portal is enabled
            if (!$patient['portal_enabled']) {
                return ['success' => false, 'message' => 'Portal access is disabled for this account'];
            }
            
            // Check if account is locked
            if ($patient['portal_locked_until'] && $patient['portal_locked_until'] > date('Y-m-d H:i:s')) {
                return ['success' => false, 'message' => 'Account is temporarily locked. Please try again later'];
            }
            
            // Check if email is verified
            if (!$patient['email_verified']) {
                return ['success' => false, 'message' => 'Please verify your email address before accessing the portal'];
            }
            
            // Verify password
            if (!password_verify($password, $patient['password'])) {
                // Increment login attempts
                $this->incrementLoginAttempts($patient['id']);
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
            
            // Reset login attempts on successful login
            $this->resetLoginAttempts($patient['id']);
            
            // Create session
            $session_token = $this->createPatientSession($patient['id']);
            
            // Update last login
            $this->updateLastLogin($patient['id']);
            
            // Log activity
            $this->logActivity($patient['id'], 'login', 'Patient logged in successfully');
            
            return [
                'success' => true,
                'patient' => [
                    'id' => $patient['id'],
                    'name' => $patient['first_name'] . ' ' . $patient['last_name'],
                    'email' => $patient['email'],
                    'phone' => $patient['phone']
                ],
                'session_token' => $session_token
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Patient authentication failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Authentication failed'];
        }
    }
    
    /**
     * Create patient session
     */
    private function createPatientSession($patient_id) {
        $session_token = bin2hex(random_bytes(64));
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        $stmt = $this->pdo->prepare("
            INSERT INTO patient_sessions (patient_id, session_token, ip_address, user_agent, expires_at)
            VALUES (:patient_id, :session_token, :ip_address, :user_agent, :expires_at)
        ");
        
        $stmt->execute([
            'patient_id' => $patient_id,
            'session_token' => $session_token,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'expires_at' => $expires_at
        ]);
        
        return $session_token;
    }
    
    /**
     * Validate patient session
     */
    public function validateSession($session_token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT ps.patient_id, p.first_name, p.last_name, p.email, p.portal_enabled
                FROM patient_sessions ps
                JOIN patients p ON ps.patient_id = p.id
                WHERE ps.session_token = :session_token 
                AND ps.expires_at > NOW() 
                AND ps.is_active = 1
                AND p.is_active = 1
                AND p.portal_enabled = 1
            ");
            
            $stmt->execute(['session_token' => $session_token]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($session) {
                // Update last activity
                $this->updateSessionActivity($session_token);
                return [
                    'success' => true,
                    'patient' => $session
                ];
            }
            
            return ['success' => false, 'message' => 'Invalid or expired session'];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Session validation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Session validation failed'];
        }
    }
    
    /**
     * Get patient dashboard data
     */
    public function getDashboardData($patient_id) {
        try {
            $data = [];
            
            // Recent appointments
            $stmt = $this->pdo->prepare("
                SELECT a.*, u.first_name as doctor_name, u.last_name as doctor_last_name
                FROM appointments a
                LEFT JOIN users u ON a.doctor_id = u.id
                WHERE a.patient_id = :patient_id 
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
                LIMIT 5
            ");
            $stmt->execute(['patient_id' => $patient_id]);
            $data['recent_appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Upcoming appointments
            $stmt = $this->pdo->prepare("
                SELECT a.*, u.first_name as doctor_name, u.last_name as doctor_last_name
                FROM appointments a
                LEFT JOIN users u ON a.doctor_id = u.id
                WHERE a.patient_id = :patient_id 
                AND a.appointment_date >= CURDATE()
                AND a.status NOT IN ('cancelled', 'completed')
                ORDER BY a.appointment_date ASC, a.appointment_time ASC
                LIMIT 3
            ");
            $stmt->execute(['patient_id' => $patient_id]);
            $data['upcoming_appointments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recent lab results
            $stmt = $this->pdo->prepare("
                SELECT lt.*, u.first_name as ordered_by_name, u.last_name as ordered_by_last_name
                FROM lab_tests lt
                LEFT JOIN users u ON lt.ordered_by = u.id
                WHERE lt.patient_id = :patient_id 
                ORDER BY lt.test_date DESC
                LIMIT 5
            ");
            $stmt->execute(['patient_id' => $patient_id]);
            $data['recent_lab_results'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Unread messages
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as unread_count
                FROM patient_messages 
                WHERE recipient_type = 'patient' 
                AND recipient_id = :patient_id 
                AND is_read = 0
            ");
            $stmt->execute(['patient_id' => $patient_id]);
            $data['unread_messages'] = $stmt->fetch(PDO::FETCH_ASSOC)['unread_count'];
            
            // Outstanding bills
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as outstanding_bills, COALESCE(SUM(total_amount - paid_amount), 0) as total_outstanding
                FROM billing 
                WHERE patient_id = :patient_id 
                AND status NOT IN ('paid', 'cancelled')
            ");
            $stmt->execute(['patient_id' => $patient_id]);
            $billing_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $data['outstanding_bills'] = $billing_data['outstanding_bills'];
            $data['total_outstanding'] = $billing_data['total_outstanding'];
            
            // Health goals progress
            $stmt = $this->pdo->prepare("
                SELECT * FROM patient_health_goals 
                WHERE patient_id = :patient_id 
                AND status = 'active'
                ORDER BY created_at DESC
                LIMIT 3
            ");
            $stmt->execute(['patient_id' => $patient_id]);
            $data['active_health_goals'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $data];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get dashboard data: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load dashboard data'];
        }
    }
    
    /**
     * Get patient appointments
     */
    public function getPatientAppointments($patient_id, $filters = []) {
        try {
            $where_conditions = ['a.patient_id = :patient_id'];
            $params = ['patient_id' => $patient_id];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $where_conditions[] = 'a.status = :status';
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = 'a.appointment_date >= :date_from';
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = 'a.appointment_date <= :date_to';
                $params['date_to'] = $filters['date_to'];
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            $sql = "
                SELECT a.*, 
                       u.first_name as doctor_name, 
                       u.last_name as doctor_last_name,
                       u.specialization as doctor_specialization
                FROM appointments a
                LEFT JOIN users u ON a.doctor_id = u.id
                WHERE $where_clause
                ORDER BY a.appointment_date DESC, a.appointment_time DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return [
                'success' => true,
                'appointments' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get patient appointments: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load appointments'];
        }
    }
    
    /**
     * Create appointment request
     */
    public function createAppointmentRequest($patient_id, $data) {
        try {
            // Validate required fields
            $required_fields = ['appointment_type', 'preferred_date', 'reason'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "Field '$field' is required"];
                }
            }
            
            // Check if preferred date is in the future
            if (strtotime($data['preferred_date']) <= strtotime(date('Y-m-d'))) {
                return ['success' => false, 'message' => 'Appointment date must be in the future'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO patient_appointment_requests (
                    patient_id, requested_doctor_id, appointment_type, preferred_date,
                    preferred_time_start, preferred_time_end, alternative_dates,
                    reason, urgency
                ) VALUES (
                    :patient_id, :doctor_id, :appointment_type, :preferred_date,
                    :preferred_time_start, :preferred_time_end, :alternative_dates,
                    :reason, :urgency
                )
            ");
            
            $stmt->execute([
                'patient_id' => $patient_id,
                'doctor_id' => $data['requested_doctor_id'] ?? null,
                'appointment_type' => $data['appointment_type'],
                'preferred_date' => $data['preferred_date'],
                'preferred_time_start' => $data['preferred_time_start'] ?? null,
                'preferred_time_end' => $data['preferred_time_end'] ?? null,
                'alternative_dates' => isset($data['alternative_dates']) ? json_encode($data['alternative_dates']) : null,
                'reason' => $data['reason'],
                'urgency' => $data['urgency'] ?? 'routine'
            ]);
            
            $request_id = $this->pdo->lastInsertId();
            
            $this->logger->log('INFO', "Appointment request created: ID $request_id for patient $patient_id");
            
            return [
                'success' => true,
                'message' => 'Appointment request submitted successfully',
                'request_id' => $request_id
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to create appointment request: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to submit appointment request'];
        }
    }
    
    /**
     * Get patient messages
     */
    public function getPatientMessages($patient_id, $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Get messages
            $stmt = $this->pdo->prepare("
                SELECT m.*,
                       CASE 
                           WHEN m.sender_type = 'patient' THEN CONCAT(p.first_name, ' ', p.last_name)
                           WHEN m.sender_type = 'provider' THEN CONCAT(u.first_name, ' ', u.last_name)
                       END as sender_name,
                       CASE 
                           WHEN m.recipient_type = 'patient' THEN CONCAT(p2.first_name, ' ', p2.last_name)
                           WHEN m.recipient_type = 'provider' THEN CONCAT(u2.first_name, ' ', u2.last_name)
                       END as recipient_name
                FROM patient_messages m
                LEFT JOIN patients p ON m.sender_type = 'patient' AND m.sender_id = p.id
                LEFT JOIN users u ON m.sender_type = 'provider' AND m.sender_id = u.id
                LEFT JOIN patients p2 ON m.recipient_type = 'patient' AND m.recipient_id = p2.id
                LEFT JOIN users u2 ON m.recipient_type = 'provider' AND m.recipient_id = u2.id
                WHERE m.patient_id = :patient_id
                ORDER BY m.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $count_stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM patient_messages WHERE patient_id = :patient_id
            ");
            $count_stmt->execute(['patient_id' => $patient_id]);
            $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'success' => true,
                'messages' => $messages,
                'total_count' => $total_count,
                'total_pages' => ceil($total_count / $limit)
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get patient messages: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load messages'];
        }
    }
    
    /**
     * Send message from patient
     */
    public function sendMessage($patient_id, $data) {
        try {
            // Validate required fields
            if (empty($data['subject']) || empty($data['message']) || empty($data['recipient_id'])) {
                return ['success' => false, 'message' => 'Subject, message, and recipient are required'];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO patient_messages (
                    patient_id, sender_type, sender_id, recipient_type, recipient_id,
                    subject, message, message_type, priority, parent_message_id
                ) VALUES (
                    :patient_id, 'patient', :patient_id, 'provider', :recipient_id,
                    :subject, :message, :message_type, :priority, :parent_message_id
                )
            ");
            
            $stmt->execute([
                'patient_id' => $patient_id,
                'recipient_id' => $data['recipient_id'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'message_type' => $data['message_type'] ?? 'general',
                'priority' => $data['priority'] ?? 'normal',
                'parent_message_id' => $data['parent_message_id'] ?? null
            ]);
            
            $message_id = $this->pdo->lastInsertId();
            
            $this->logger->log('INFO', "Message sent by patient $patient_id: ID $message_id");
            
            return [
                'success' => true,
                'message' => 'Message sent successfully',
                'message_id' => $message_id
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to send message: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send message'];
        }
    }
    
    /**
     * Get patient medical records
     */
    public function getPatientMedicalRecords($patient_id, $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->pdo->prepare("
                SELECT mr.*, 
                       u.first_name as doctor_name, 
                       u.last_name as doctor_last_name
                FROM medical_records mr
                LEFT JOIN users u ON mr.doctor_id = u.id
                WHERE mr.patient_id = :patient_id
                ORDER BY mr.visit_date DESC, mr.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $count_stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM medical_records WHERE patient_id = :patient_id
            ");
            $count_stmt->execute(['patient_id' => $patient_id]);
            $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Log access
            foreach ($records as $record) {
                $this->logDocumentAccess($patient_id, 'medical_record', $record['id'], 'view');
            }
            
            return [
                'success' => true,
                'records' => $records,
                'total_count' => $total_count,
                'total_pages' => ceil($total_count / $limit)
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get medical records: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load medical records'];
        }
    }
    
    /**
     * Get patient lab results
     */
    public function getPatientLabResults($patient_id, $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->pdo->prepare("
                SELECT lt.*, 
                       u.first_name as doctor_name, 
                       u.last_name as doctor_last_name,
                       ltc.name as category_name
                FROM lab_tests lt
                LEFT JOIN users u ON lt.ordered_by = u.id
                LEFT JOIN lab_test_categories ltc ON lt.category_id = ltc.id
                WHERE lt.patient_id = :patient_id
                ORDER BY lt.test_date DESC, lt.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $count_stmt = $this->pdo->prepare("
                SELECT COUNT(*) as total FROM lab_tests WHERE patient_id = :patient_id
            ");
            $count_stmt->execute(['patient_id' => $patient_id]);
            $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Log access
            foreach ($results as $result) {
                $this->logDocumentAccess($patient_id, 'lab_result', $result['id'], 'view');
            }
            
            return [
                'success' => true,
                'results' => $results,
                'total_count' => $total_count,
                'total_pages' => ceil($total_count / $limit)
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get lab results: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load lab results'];
        }
    }
    
    /**
     * Get patient billing information
     */
    public function getPatientBilling($patient_id, $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM billing 
                WHERE patient_id = :patient_id
                ORDER BY bill_date DESC, created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count and summary
            $summary_stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_bills,
                    COALESCE(SUM(total_amount), 0) as total_billed,
                    COALESCE(SUM(paid_amount), 0) as total_paid,
                    COALESCE(SUM(total_amount - paid_amount), 0) as total_outstanding
                FROM billing 
                WHERE patient_id = :patient_id
            ");
            $summary_stmt->execute(['patient_id' => $patient_id]);
            $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'bills' => $bills,
                'summary' => $summary,
                'total_pages' => ceil($summary['total_bills'] / $limit)
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get billing information: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load billing information'];
        }
    }
    
    // Helper methods
    private function incrementLoginAttempts($patient_id) {
        $stmt = $this->pdo->prepare("
            UPDATE patients 
            SET portal_login_attempts = portal_login_attempts + 1,
                portal_locked_until = CASE 
                    WHEN portal_login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                    ELSE portal_locked_until
                END
            WHERE id = :patient_id
        ");
        $stmt->execute(['patient_id' => $patient_id]);
    }
    
    private function resetLoginAttempts($patient_id) {
        $stmt = $this->pdo->prepare("
            UPDATE patients 
            SET portal_login_attempts = 0, portal_locked_until = NULL
            WHERE id = :patient_id
        ");
        $stmt->execute(['patient_id' => $patient_id]);
    }
    
    private function updateLastLogin($patient_id) {
        $stmt = $this->pdo->prepare("
            UPDATE patients 
            SET portal_last_login = NOW()
            WHERE id = :patient_id
        ");
        $stmt->execute(['patient_id' => $patient_id]);
    }
    
    private function updateSessionActivity($session_token) {
        $stmt = $this->pdo->prepare("
            UPDATE patient_sessions 
            SET last_activity = NOW()
            WHERE session_token = :session_token
        ");
        $stmt->execute(['session_token' => $session_token]);
    }
    
    private function logActivity($patient_id, $activity_type, $description) {
        $stmt = $this->pdo->prepare("
            INSERT INTO patient_portal_activity_log (patient_id, activity_type, activity_description, ip_address, user_agent)
            VALUES (:patient_id, :activity_type, :description, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            'patient_id' => $patient_id,
            'activity_type' => $activity_type,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
    
    private function logDocumentAccess($patient_id, $document_type, $document_id, $action) {
        $stmt = $this->pdo->prepare("
            INSERT INTO patient_document_access_log (patient_id, document_type, document_id, action, ip_address, user_agent)
            VALUES (:patient_id, :document_type, :document_id, :action, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            'patient_id' => $patient_id,
            'document_type' => $document_type,
            'document_id' => $document_id,
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}
?>
