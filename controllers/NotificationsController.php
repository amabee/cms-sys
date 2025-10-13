<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/SystemLogger.php';

class NotificationsController {
    private $pdo;
    private $logger;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->logger = new SystemLogger();
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($data) {
        try {
            $notification_id = $this->generateNotificationId($data['type']);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (
                    notification_id, user_id, patient_id, type, title, message, 
                    priority, delivery_method, reference_data, scheduled_for,
                    email_to, email_subject, sms_to, created_by
                ) VALUES (
                    :notification_id, :user_id, :patient_id, :type, :title, :message,
                    :priority, :delivery_method, :reference_data, :scheduled_for,
                    :email_to, :email_subject, :sms_to, :created_by
                )
            ");
            
            $stmt->execute([
                'notification_id' => $notification_id,
                'user_id' => $data['user_id'] ?? null,
                'patient_id' => $data['patient_id'] ?? null,
                'type' => $data['type'],
                'title' => $data['title'],
                'message' => $data['message'],
                'priority' => $data['priority'] ?? 'normal',
                'delivery_method' => $data['delivery_method'] ?? 'in_app',
                'reference_data' => isset($data['reference_data']) ? json_encode($data['reference_data']) : null,
                'scheduled_for' => $data['scheduled_for'] ?? null,
                'email_to' => $data['email_to'] ?? null,
                'email_subject' => $data['email_subject'] ?? null,
                'sms_to' => $data['sms_to'] ?? null,
                'created_by' => $data['created_by'] ?? null
            ]);
            
            $this->logger->log('INFO', 'Notification created: ' . $notification_id, [
                'type' => $data['type'],
                'priority' => $data['priority'] ?? 'normal'
            ]);
            
            return [
                'success' => true,
                'notification_id' => $notification_id
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to create notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create notification'
            ];
        }
    }
    
    /**
     * Get notifications for a user or patient
     */
    public function getNotifications($userId = null, $patientId = null, $filters = []) {
        try {
            $whereConditions = [];
            $params = [];
            
            if ($userId) {
                $whereConditions[] = "n.user_id = :user_id";
                $params['user_id'] = $userId;
            }
            
            if ($patientId) {
                $whereConditions[] = "n.patient_id = :patient_id";
                $params['patient_id'] = $patientId;
            }
            
            // Apply filters
            if (isset($filters['type'])) {
                $whereConditions[] = "n.type = :type";
                $params['type'] = $filters['type'];
            }
            
            if (isset($filters['status'])) {
                $whereConditions[] = "n.status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (isset($filters['priority'])) {
                $whereConditions[] = "n.priority = :priority";
                $params['priority'] = $filters['priority'];
            }
            
            if (isset($filters['unread_only']) && $filters['unread_only']) {
                $whereConditions[] = "n.read_at IS NULL";
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    n.*,
                    p.first_name as patient_first_name,
                    p.last_name as patient_last_name,
                    u.first_name as user_first_name,
                    u.last_name as user_last_name
                FROM notifications n
                LEFT JOIN patients p ON n.patient_id = p.id
                LEFT JOIN users u ON n.user_id = u.id
                $whereClause
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            // Set pagination parameters
            $params['limit'] = $filters['limit'] ?? 50;
            $params['offset'] = $filters['offset'] ?? 0;
            
            $stmt->execute($params);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Process notifications to include parsed reference data
            foreach ($notifications as &$notification) {
                if ($notification['reference_data']) {
                    $notification['reference_data'] = json_decode($notification['reference_data'], true);
                }
                
                // Add recipient name
                if ($notification['patient_id']) {
                    $notification['recipient_name'] = $notification['patient_first_name'] . ' ' . $notification['patient_last_name'];
                } elseif ($notification['user_id']) {
                    $notification['recipient_name'] = $notification['user_first_name'] . ' ' . $notification['user_last_name'];
                } else {
                    $notification['recipient_name'] = 'System';
                }
                
                // Format created_at for display
                $notification['created_at_formatted'] = date('M j, Y g:i A', strtotime($notification['created_at']));
                $notification['time_ago'] = $this->timeAgo($notification['created_at']);
            }
            
            return [
                'success' => true,
                'data' => $notifications
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve notifications'
            ];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET read_at = CURRENT_TIMESTAMP 
                WHERE notification_id = :notification_id AND read_at IS NULL
            ");
            
            $stmt->execute(['notification_id' => $notificationId]);
            
            return [
                'success' => true,
                'message' => 'Notification marked as read'
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to mark notification as read: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ];
        }
    }
    
    /**
     * Get notification statistics
     */
    public function getNotificationStatistics($userId = null, $patientId = null) {
        try {
            $whereConditions = [];
            $params = [];
            
            if ($userId) {
                $whereConditions[] = "user_id = :user_id";
                $params['user_id'] = $userId;
            }
            
            if ($patientId) {
                $whereConditions[] = "patient_id = :patient_id";
                $params['patient_id'] = $patientId;
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_notifications,
                    COUNT(CASE WHEN read_at IS NULL THEN 1 END) as unread_count,
                    COUNT(CASE WHEN priority = 'critical' THEN 1 END) as critical_count,
                    COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_count,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count,
                    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h_count
                FROM notifications
                $whereClause
            ");
            
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get notification statistics: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve notification statistics'
            ];
        }
    }
    
    /**
     * Process pending notifications
     */
    public function processPendingNotifications($limit = 100) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                WHERE status = 'pending' 
                AND (scheduled_for IS NULL OR scheduled_for <= NOW())
                ORDER BY priority DESC, created_at ASC
                LIMIT :limit
            ");
            
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $processed = 0;
            $failed = 0;
            
            foreach ($notifications as $notification) {
                if ($this->processNotification($notification)) {
                    $processed++;
                } else {
                    $failed++;
                }
            }
            
            return [
                'success' => true,
                'processed' => $processed,
                'failed' => $failed,
                'total' => count($notifications)
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to process pending notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process pending notifications'
            ];
        }
    }
    
    /**
     * Process a single notification
     */
    private function processNotification($notification) {
        try {
            $deliveryMethods = explode(',', $notification['delivery_method']);
            $allSuccess = true;
            
            foreach ($deliveryMethods as $method) {
                $method = trim($method);
                
                switch ($method) {
                    case 'email':
                        $result = $this->sendEmail($notification);
                        break;
                    case 'sms':
                        $result = $this->sendSms($notification);
                        break;
                    case 'in_app':
                        $result = $this->processInAppNotification($notification);
                        break;
                    default:
                        $result = false;
                }
                
                if (!$result) {
                    $allSuccess = false;
                }
            }
            
            // Update notification status
            $status = $allSuccess ? 'sent' : 'failed';
            $this->updateNotificationStatus($notification['notification_id'], $status);
            
            return $allSuccess;
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to process notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email notification
     */
    private function sendEmail($notification) {
        try {
            // Get email template
            $template = $this->getNotificationTemplate($notification['type'], 'email');
            
            if (!$template) {
                return false;
            }
            
            // Get recipient email
            $email = $this->getRecipientEmail($notification);
            if (!$email) {
                return false;
            }
            
            // Process template
            $subject = $this->processTemplate($template['subject_template'], $notification);
            $body = $this->processTemplate($template['message_template'], $notification);
            
            // Add to email queue
            $stmt = $this->pdo->prepare("
                INSERT INTO email_queue (
                    notification_id, to_email, subject, body_html, priority, scheduled_for
                ) VALUES (
                    :notification_id, :to_email, :subject, :body_html, :priority, NOW()
                )
            ");
            
            $stmt->execute([
                'notification_id' => $notification['notification_id'],
                'to_email' => $email,
                'subject' => $subject,
                'body_html' => $body,
                'priority' => $notification['priority'] === 'critical' ? 'urgent' : $notification['priority']
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to send email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send SMS notification
     */
    private function sendSms($notification) {
        try {
            // Get SMS template
            $template = $this->getNotificationTemplate($notification['type'], 'sms');
            
            if (!$template) {
                return false;
            }
            
            // Get recipient phone
            $phone = $this->getRecipientPhone($notification);
            if (!$phone) {
                return false;
            }
            
            // Process template
            $message = $this->processTemplate($template['message_template'], $notification);
            
            // Add to SMS queue
            $stmt = $this->pdo->prepare("
                INSERT INTO sms_queue (
                    notification_id, to_phone, message, priority, scheduled_for
                ) VALUES (
                    :notification_id, :to_phone, :message, :priority, NOW()
                )
            ");
            
            $stmt->execute([
                'notification_id' => $notification['notification_id'],
                'to_phone' => $phone,
                'message' => $message,
                'priority' => $notification['priority'] === 'critical' ? 'urgent' : $notification['priority']
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to send SMS: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Process in-app notification
     */
    private function processInAppNotification($notification) {
        // In-app notifications are already stored in the notifications table
        // Just mark as delivered
        return true;
    }
    
    /**
     * Get notification template
     */
    private function getNotificationTemplate($type, $method) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notification_templates 
                WHERE notification_type = :type 
                AND delivery_method = :method 
                AND is_active = 1
                LIMIT 1
            ");
            
            $stmt->execute([
                'type' => $type,
                'method' => $method
            ]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get notification template: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Process template with variables
     */
    private function processTemplate($template, $notification) {
        $variables = $this->getTemplateVariables($notification);
        
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        
        return $template;
    }
    
    /**
     * Get template variables
     */
    private function getTemplateVariables($notification) {
        $variables = [];
        
        // System variables
        $variables['clinic_name'] = 'HealthCare Clinic'; // From settings
        $variables['clinic_phone'] = '(555) 123-4567'; // From settings
        $variables['clinic_address'] = '123 Health Street, Medical District'; // From settings
        
        // Patient data
        if ($notification['patient_id']) {
            $patient = $this->getPatientData($notification['patient_id']);
            if ($patient) {
                $variables['patient_name'] = $patient['first_name'] . ' ' . $patient['last_name'];
                $variables['patient_email'] = $patient['email'];
                $variables['patient_phone'] = $patient['phone'];
            }
        }
        
        // Reference data from notification
        if ($notification['reference_data']) {
            $refData = json_decode($notification['reference_data'], true);
            if ($refData) {
                foreach ($refData as $key => $value) {
                    $variables[$key] = $value;
                }
                
                // Process specific reference data types
                if (isset($refData['appointment_id'])) {
                    $appointment = $this->getAppointmentData($refData['appointment_id']);
                    if ($appointment) {
                        $variables['appointment_date'] = date('F j, Y', strtotime($appointment['appointment_date']));
                        $variables['appointment_time'] = date('g:i A', strtotime($appointment['appointment_time']));
                        $variables['appointment_reason'] = $appointment['reason'];
                        
                        // Get doctor data
                        $doctor = $this->getDoctorData($appointment['doctor_id']);
                        if ($doctor) {
                            $variables['doctor_name'] = $doctor['first_name'] . ' ' . $doctor['last_name'];
                        }
                    }
                }
            }
        }
        
        return $variables;
    }
    
    /**
     * Get recipient email
     */
    private function getRecipientEmail($notification) {
        if ($notification['email_to']) {
            return $notification['email_to'];
        }
        
        if ($notification['patient_id']) {
            $patient = $this->getPatientData($notification['patient_id']);
            return $patient['email'] ?? null;
        }
        
        if ($notification['user_id']) {
            $user = $this->getUserData($notification['user_id']);
            return $user['email'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Get recipient phone
     */
    private function getRecipientPhone($notification) {
        if ($notification['sms_to']) {
            return $notification['sms_to'];
        }
        
        if ($notification['patient_id']) {
            $patient = $this->getPatientData($notification['patient_id']);
            return $patient['phone'] ?? null;
        }
        
        if ($notification['user_id']) {
            $user = $this->getUserData($notification['user_id']);
            return $user['phone'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Helper methods for data retrieval
     */
    private function getPatientData($patientId) {
        $stmt = $this->pdo->prepare("SELECT * FROM patients WHERE id = :id");
        $stmt->execute(['id' => $patientId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getUserData($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getAppointmentData($appointmentId) {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE id = :id");
        $stmt->execute(['id' => $appointmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function getDoctorData($doctorId) {
        $stmt = $this->pdo->prepare("
            SELECT u.* FROM doctors d 
            JOIN users u ON d.user_id = u.id 
            WHERE d.id = :id
        ");
        $stmt->execute(['id' => $doctorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update notification status
     */
    private function updateNotificationStatus($notificationId, $status) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET status = :status, sent_at = CASE WHEN :status = 'sent' THEN NOW() ELSE sent_at END 
                WHERE notification_id = :notification_id
            ");
            
            $stmt->execute([
                'status' => $status,
                'notification_id' => $notificationId
            ]);
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to update notification status: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate unique notification ID
     */
    private function generateNotificationId($type) {
        $prefix = strtoupper(substr($type, 0, 3));
        return $prefix . '_' . date('Ymd') . '_' . uniqid();
    }
    
    /**
     * Calculate time ago
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time / 60) . 'm ago';
        if ($time < 86400) return floor($time / 3600) . 'h ago';
        if ($time < 2592000) return floor($time / 86400) . 'd ago';
        
        return date('M j, Y', strtotime($datetime));
    }
    
    /**
     * Delete old notifications
     */
    public function cleanupOldNotifications($daysOld = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days_old DAY) 
                AND status != 'failed'
            ");
            
            $stmt->execute(['days_old' => $daysOld]);
            $deleted = $stmt->rowCount();
            
            $this->logger->log('INFO', "Deleted $deleted old notifications");
            
            return [
                'success' => true,
                'deleted' => $deleted
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to cleanup old notifications: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to cleanup old notifications'
            ];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($notification_id, $user_id) {
        try {
            // Check if notification exists and belongs to user
            $stmt = $this->pdo->prepare("
                SELECT id FROM notifications 
                WHERE id = :notification_id 
                AND (user_id = :user_id OR patient_id IN (
                    SELECT id FROM patients WHERE user_id = :user_id
                ))
            ");
            
            $stmt->execute([
                'notification_id' => $notification_id,
                'user_id' => $user_id
            ]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Notification not found or unauthorized'];
            }
            
            // Mark as read
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE id = :notification_id
            ");
            
            $stmt->execute(['notification_id' => $notification_id]);
            
            $this->logger->log('INFO', "Notification $notification_id marked as read by user $user_id");
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to mark notification as read: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($notification_id, $user_id) {
        try {
            // Check if user has permission to delete
            $stmt = $this->pdo->prepare("
                SELECT id FROM notifications 
                WHERE id = :notification_id 
                AND (user_id = :user_id OR created_by = :user_id)
            ");
            
            $stmt->execute([
                'notification_id' => $notification_id,
                'user_id' => $user_id
            ]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Notification not found or unauthorized'];
            }
            
            // Delete notification
            $stmt = $this->pdo->prepare("DELETE FROM notifications WHERE id = :notification_id");
            $stmt->execute(['notification_id' => $notification_id]);
            
            $this->logger->log('INFO', "Notification $notification_id deleted by user $user_id");
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to delete notification: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Retry failed notification
     */
    public function retryNotification($notification_id) {
        try {
            // Reset notification status to pending
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET status = 'pending', 
                    retry_count = retry_count + 1,
                    last_retry_at = NOW()
                WHERE id = :notification_id 
                AND status = 'failed'
                AND retry_count < 3
            ");
            
            $stmt->execute(['notification_id' => $notification_id]);
            
            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'Cannot retry this notification'];
            }
            
            $this->logger->log('INFO', "Notification $notification_id queued for retry");
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to retry notification: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get notification templates
     */
    public function getTemplates() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM notification_templates 
                ORDER BY notification_type, delivery_method
            ");
            
            return [
                'success' => true,
                'templates' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get templates: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Save notification template
     */
    public function saveTemplate($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notification_templates 
                (name, template_code, notification_type, delivery_method, subject_template, message_template)
                VALUES (:name, :code, :type, :method, :subject, :message)
            ");
            
            $stmt->execute([
                'name' => $data['name'],
                'code' => $data['code'],
                'type' => $data['type'],
                'method' => $data['method'],
                'subject' => $data['subject_template'] ?? null,
                'message' => $data['message_template']
            ]);
            
            $template_id = $this->pdo->lastInsertId();
            
            $this->logger->log('INFO', "Template saved with ID: $template_id");
            
            return [
                'success' => true,
                'template_id' => $template_id
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to save template: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
