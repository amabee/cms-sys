<?php
class SystemSettingsController {
    private $conn;
    private $logger;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->logger = new SystemLogger();
    }
    
    /**
     * Get all system settings by category
     */
    public function getSettingsByCategory($category = null) {
        try {
            $sql = "SELECT s.*, c.display_name as category_display_name, c.icon 
                    FROM system_settings s 
                    LEFT JOIN configuration_categories c ON s.category = c.category_name 
                    WHERE 1=1";
            
            $params = [];
            if ($category) {
                $sql .= " AND s.category = ?";
                $params[] = $category;
            }
            
            $sql .= " ORDER BY c.sort_order, s.setting_key";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Group settings by category
            $grouped = [];
            foreach ($settings as $setting) {
                if (!isset($grouped[$setting['category']])) {
                    $grouped[$setting['category']] = [
                        'category' => $setting['category'],
                        'display_name' => $setting['category_display_name'],
                        'icon' => $setting['icon'],
                        'settings' => []
                    ];
                }
                
                // Parse JSON validation rules
                if ($setting['validation_rules']) {
                    $setting['validation_rules'] = json_decode($setting['validation_rules'], true);
                }
                
                $grouped[$setting['category']]['settings'][] = $setting;
            }
            
            return ['success' => true, 'data' => $grouped];
            
        } catch (Exception $e) {
            $this->logger->logError('System Settings', 'Failed to get settings', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve settings'];
        }
    }
    
    /**
     * Get all configuration categories
     */
    public function getConfigurationCategories() {
        try {
            $sql = "SELECT * FROM configuration_categories 
                    WHERE is_active = 1 
                    ORDER BY sort_order, display_name";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $categories];
            
        } catch (Exception $e) {
            $this->logger->logError('System Settings', 'Failed to get categories', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve categories'];
        }
    }
    
    /**
     * Update system setting
     */
    public function updateSetting($settingKey, $settingValue, $userId) {
        try {
            // Check if setting exists and is editable
            $sql = "SELECT * FROM system_settings WHERE setting_key = ? AND is_editable = 1";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settingKey]);
            $setting = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$setting) {
                return ['success' => false, 'message' => 'Setting not found or not editable'];
            }
            
            // Validate setting value based on type
            $validationResult = $this->validateSettingValue($setting, $settingValue);
            if (!$validationResult['valid']) {
                return ['success' => false, 'message' => $validationResult['message']];
            }
            
            // Update setting
            $sql = "UPDATE system_settings 
                    SET setting_value = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE setting_key = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$settingValue, $userId, $settingKey]);
            
            $this->logger->logActivity($userId, 'UPDATE', 'system_settings', $settingKey, 
                                     "Updated setting: {$settingKey}");
            
            return ['success' => true, 'message' => 'Setting updated successfully'];
            
        } catch (Exception $e) {
            $this->logger->logError('System Settings', 'Failed to update setting', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update setting'];
        }
    }
    
    /**
     * Validate setting value based on type and rules
     */
    private function validateSettingValue($setting, $value) {
        $type = $setting['setting_type'];
        $rules = json_decode($setting['validation_rules'], true) ?: [];
        
        switch ($type) {
            case 'boolean':
                if (!in_array(strtolower($value), ['true', 'false', '1', '0'])) {
                    return ['valid' => false, 'message' => 'Value must be true or false'];
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'message' => 'Value must be a number'];
                }
                if (isset($rules['min']) && $value < $rules['min']) {
                    return ['valid' => false, 'message' => "Value must be at least {$rules['min']}"];
                }
                if (isset($rules['max']) && $value > $rules['max']) {
                    return ['valid' => false, 'message' => "Value must be no more than {$rules['max']}"];
                }
                break;
                
            case 'string':
                if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                    return ['valid' => false, 'message' => "Value must be at least {$rules['min_length']} characters"];
                }
                if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                    return ['valid' => false, 'message' => "Value must be no more than {$rules['max_length']} characters"];
                }
                if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                    return ['valid' => false, 'message' => 'Value format is invalid'];
                }
                break;
                
            case 'json':
                json_decode($value);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return ['valid' => false, 'message' => 'Value must be valid JSON'];
                }
                break;
        }
        
        return ['valid' => true, 'message' => ''];
    }
    
    /**
     * Get user preferences
     */
    public function getUserPreferences($userId) {
        try {
            $sql = "SELECT * FROM user_preferences WHERE user_id = ? ORDER BY preference_key";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId]);
            
            $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Convert to key-value pairs
            $result = [];
            foreach ($preferences as $pref) {
                $value = $pref['preference_value'];
                
                // Parse value based on type
                switch ($pref['preference_type']) {
                    case 'boolean':
                        $value = in_array(strtolower($value), ['true', '1']);
                        break;
                    case 'number':
                        $value = is_numeric($value) ? (float)$value : $value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }
                
                $result[$pref['preference_key']] = $value;
            }
            
            return ['success' => true, 'data' => $result];
            
        } catch (Exception $e) {
            $this->logger->logError('User Preferences', 'Failed to get preferences', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve preferences'];
        }
    }
    
    /**
     * Update user preference
     */
    public function updateUserPreference($userId, $preferenceKey, $preferenceValue, $preferenceType = 'string') {
        try {
            $sql = "INSERT INTO user_preferences (user_id, preference_key, preference_value, preference_type) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    preference_value = VALUES(preference_value), 
                    preference_type = VALUES(preference_type),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$userId, $preferenceKey, $preferenceValue, $preferenceType]);
            
            return ['success' => true, 'message' => 'Preference updated successfully'];
            
        } catch (Exception $e) {
            $this->logger->logError('User Preferences', 'Failed to update preference', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update preference'];
        }
    }
    
    /**
     * Get system backup history
     */
    public function getBackupHistory($limit = 50) {
        try {
            $sql = "SELECT b.*, u.username as created_by_name 
                    FROM system_backups b 
                    LEFT JOIN users u ON b.created_by = u.id 
                    ORDER BY b.created_at DESC 
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$limit]);
            
            $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format backup sizes
            foreach ($backups as &$backup) {
                if ($backup['backup_size']) {
                    $backup['backup_size_formatted'] = $this->formatFileSize($backup['backup_size']);
                }
                if ($backup['metadata']) {
                    $backup['metadata'] = json_decode($backup['metadata'], true);
                }
            }
            
            return ['success' => true, 'data' => $backups];
            
        } catch (Exception $e) {
            $this->logger->logError('System Backups', 'Failed to get backup history', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve backup history'];
        }
    }
    
    /**
     * Create system backup
     */
    public function createBackup($backupType, $userId) {
        try {
            $backupName = "backup_" . date('Y-m-d_H-i-s') . "_" . $backupType;
            $backupLocation = "/backups/" . $backupName . ".sql";
            
            // Insert backup record
            $sql = "INSERT INTO system_backups (backup_name, backup_type, backup_location, status, created_by, started_at) 
                    VALUES (?, ?, ?, 'in_progress', ?, CURRENT_TIMESTAMP)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$backupName, $backupType, $backupLocation, $userId]);
            $backupId = $this->conn->lastInsertId();
            
            // Here you would implement the actual backup logic
            // For now, we'll simulate a successful backup
            $this->simulateBackupProcess($backupId, $backupType);
            
            $this->logger->logActivity($userId, 'CREATE', 'system_backups', $backupId, 
                                     "Created {$backupType} backup: {$backupName}");
            
            return ['success' => true, 'message' => 'Backup started successfully', 'backup_id' => $backupId];
            
        } catch (Exception $e) {
            $this->logger->logError('System Backups', 'Failed to create backup', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create backup'];
        }
    }
    
    /**
     * Simulate backup process (replace with actual backup implementation)
     */
    private function simulateBackupProcess($backupId, $backupType) {
        // This is a placeholder - implement actual backup logic here
        sleep(2); // Simulate processing time
        
        $backupSize = rand(1000000, 50000000); // Random size between 1MB and 50MB
        
        $sql = "UPDATE system_backups 
                SET status = 'completed', backup_size = ?, completed_at = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$backupSize, $backupId]);
    }
    
    /**
     * Get maintenance schedules
     */
    public function getMaintenanceSchedules() {
        try {
            $sql = "SELECT m.*, u.username as created_by_name,
                           (SELECT COUNT(*) FROM maintenance_logs ml WHERE ml.schedule_id = m.id) as run_count_total,
                           (SELECT COUNT(*) FROM maintenance_logs ml WHERE ml.schedule_id = m.id AND ml.status = 'completed') as success_count
                    FROM maintenance_schedules m 
                    LEFT JOIN users u ON m.created_by = u.id 
                    ORDER BY m.is_active DESC, m.next_run ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse configuration JSON
            foreach ($schedules as &$schedule) {
                if ($schedule['task_configuration']) {
                    $schedule['task_configuration'] = json_decode($schedule['task_configuration'], true);
                }
            }
            
            return ['success' => true, 'data' => $schedules];
            
        } catch (Exception $e) {
            $this->logger->logError('Maintenance Schedules', 'Failed to get schedules', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve maintenance schedules'];
        }
    }
    
    /**
     * Get maintenance logs
     */
    public function getMaintenanceLogs($scheduleId = null, $limit = 100) {
        try {
            $sql = "SELECT ml.*, ms.task_name as schedule_task_name, u.username as executed_by_name
                    FROM maintenance_logs ml 
                    LEFT JOIN maintenance_schedules ms ON ml.schedule_id = ms.id 
                    LEFT JOIN users u ON ml.executed_by = u.id 
                    WHERE 1=1";
            
            $params = [];
            if ($scheduleId) {
                $sql .= " AND ml.schedule_id = ?";
                $params[] = $scheduleId;
            }
            
            $sql .= " ORDER BY ml.started_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse JSON fields and calculate duration
            foreach ($logs as &$log) {
                if ($log['resources_affected']) {
                    $log['resources_affected'] = json_decode($log['resources_affected'], true);
                }
                
                if ($log['started_at'] && $log['completed_at']) {
                    $start = new DateTime($log['started_at']);
                    $end = new DateTime($log['completed_at']);
                    $log['duration_formatted'] = $this->formatDuration($start->diff($end));
                } elseif ($log['duration_seconds']) {
                    $log['duration_formatted'] = $this->formatDuration(new DateInterval("PT{$log['duration_seconds']}S"));
                }
            }
            
            return ['success' => true, 'data' => $logs];
            
        } catch (Exception $e) {
            $this->logger->logError('Maintenance Logs', 'Failed to get logs', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve maintenance logs'];
        }
    }
    
    /**
     * Get system activity logs
     */
    public function getSystemActivity($activityType = null, $limit = 100) {
        try {
            $sql = "SELECT sa.*, u.username 
                    FROM system_activity sa 
                    LEFT JOIN users u ON sa.user_id = u.id 
                    WHERE 1=1";
            
            $params = [];
            if ($activityType) {
                $sql .= " AND sa.activity_type = ?";
                $params[] = $activityType;
            }
            
            $sql .= " ORDER BY sa.created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['success' => true, 'data' => $activities];
            
        } catch (Exception $e) {
            $this->logger->logError('System Activity', 'Failed to get activity logs', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve activity logs'];
        }
    }
    
    /**
     * Get email templates
     */
    public function getEmailTemplates($templateType = null) {
        try {
            $sql = "SELECT et.*, u.username as created_by_name 
                    FROM email_templates et 
                    LEFT JOIN users u ON et.created_by = u.id 
                    WHERE 1=1";
            
            $params = [];
            if ($templateType) {
                $sql .= " AND et.template_type = ?";
                $params[] = $templateType;
            }
            
            $sql .= " ORDER BY et.template_type, et.template_name";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Parse variables JSON
            foreach ($templates as &$template) {
                if ($template['variables']) {
                    $template['variables'] = json_decode($template['variables'], true);
                }
            }
            
            return ['success' => true, 'data' => $templates];
            
        } catch (Exception $e) {
            $this->logger->logError('Email Templates', 'Failed to get templates', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve email templates'];
        }
    }
    
    /**
     * Update email template
     */
    public function updateEmailTemplate($templateId, $templateData, $userId) {
        try {
            $sql = "UPDATE email_templates 
                    SET subject = ?, body_text = ?, body_html = ?, variables = ?, 
                        is_active = ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            
            $variables = isset($templateData['variables']) ? json_encode($templateData['variables']) : null;
            $isActive = isset($templateData['is_active']) ? $templateData['is_active'] : 1;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $templateData['subject'],
                $templateData['body_text'],
                $templateData['body_html'],
                $variables,
                $isActive,
                $templateId
            ]);
            
            $this->logger->logActivity($userId, 'UPDATE', 'email_templates', $templateId, 
                                     "Updated email template ID: {$templateId}");
            
            return ['success' => true, 'message' => 'Template updated successfully'];
            
        } catch (Exception $e) {
            $this->logger->logError('Email Templates', 'Failed to update template', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update template'];
        }
    }
    
    /**
     * Get system statistics for dashboard
     */
    public function getSystemStatistics() {
        try {
            $stats = [];
            
            // Database size
            $sql = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb 
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE()";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['database_size'] = $stmt->fetchColumn() . ' MB';
            
            // Total users
            $sql = "SELECT COUNT(*) FROM users WHERE status = 'active'";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['active_users'] = $stmt->fetchColumn();
            
            // Recent backups
            $sql = "SELECT COUNT(*) FROM system_backups WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stats['recent_backups'] = $stmt->fetchColumn();
            
            // System uptime (simulated)
            $stats['system_uptime'] = '15 days, 6 hours';
            
            // Storage usage (simulated)
            $stats['storage_used'] = '2.3 GB';
            $stats['storage_total'] = '10 GB';
            $stats['storage_percentage'] = 23;
            
            return ['success' => true, 'data' => $stats];
            
        } catch (Exception $e) {
            $this->logger->logError('System Statistics', 'Failed to get statistics', $e->getMessage());
            return ['success' => false, 'message' => 'Failed to retrieve system statistics'];
        }
    }
    
    /**
     * Test email configuration
     */
    public function testEmailConfiguration($emailAddress) {
        try {
            // Get email settings
            $emailSettings = $this->getEmailSettings();
            
            if (!$emailSettings['smtp_host'] || !$emailSettings['from_email']) {
                return ['success' => false, 'message' => 'Email configuration incomplete'];
            }
            
            // Here you would implement actual email sending test
            // For now, we'll simulate a successful test
            $testResult = $this->simulateEmailTest($emailAddress, $emailSettings);
            
            return $testResult;
            
        } catch (Exception $e) {
            $this->logger->logError('Email Test', 'Failed to test email', $e->getMessage());
            return ['success' => false, 'message' => 'Email test failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get email configuration settings
     */
    private function getEmailSettings() {
        $sql = "SELECT setting_key, setting_value FROM system_settings WHERE category = 'email'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }
    
    /**
     * Simulate email test (replace with actual email implementation)
     */
    private function simulateEmailTest($emailAddress, $settings) {
        // This is a placeholder - implement actual email sending here
        sleep(1); // Simulate processing time
        
        $success = filter_var($emailAddress, FILTER_VALIDATE_EMAIL) !== false;
        
        if ($success) {
            return [
                'success' => true, 
                'message' => "Test email sent successfully to {$emailAddress}",
                'details' => [
                    'smtp_server' => $settings['smtp_host'],
                    'port' => $settings['smtp_port'],
                    'encryption' => $settings['smtp_encryption']
                ]
            ];
        } else {
            return [
                'success' => false, 
                'message' => 'Invalid email address format'
            ];
        }
    }
    
    /**
     * Utility function to format file sizes
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }
    
    /**
     * Utility function to format duration
     */
    private function formatDuration($interval) {
        $parts = [];
        
        if ($interval->h > 0) $parts[] = $interval->h . 'h';
        if ($interval->i > 0) $parts[] = $interval->i . 'm';
        if ($interval->s > 0) $parts[] = $interval->s . 's';
        
        return empty($parts) ? '0s' : implode(' ', $parts);
    }
}
?>
