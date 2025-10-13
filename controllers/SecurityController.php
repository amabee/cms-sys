<?php

/**
 * Security Controller for Clinic Management System
 * Handles advanced security features including:
 * - Login attempt tracking
 * - Account lockouts
 * - Session management
 * - Password policies
 * - Security logging
 * - Suspicious activity detection
 */

class SecurityController {
    private $db;
    private $settings;
    
    public function __construct() {
        $this->db = getDBConnection();
        if (!$this->db) {
            throw new Exception("Database connection failed");
        }
        $this->loadSettings();
    }
    
    /**
     * Load security settings from database
     */
    private function loadSettings() {
        $this->settings = [];
        try {
            $stmt = $this->db->prepare('SELECT setting_name, setting_value FROM security_settings');
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($results as $setting) {
                $this->settings[$setting['setting_name']] = $setting['setting_value'];
            }
        } catch (Exception $e) {
            error_log('[SecurityController] Failed to load settings: ' . $e->getMessage());
            // Set defaults if database fails
            $this->settings = [
                'max_login_attempts' => 5,
                'lockout_duration' => 900,
                'session_timeout' => 3600,
                'password_min_length' => 8,
                'password_require_uppercase' => 1,
                'password_require_lowercase' => 1,
                'password_require_numbers' => 1,
                'password_require_symbols' => 1,
                'password_history_count' => 5
            ];
        }
    }
    
    /**
     * Get security setting value
     */
    public function getSetting($name, $default = null) {
        return $this->settings[$name] ?? $default;
    }
    
    /**
     * Check if account is locked
     */
    public function isAccountLocked($username) {
        if (!$this->db) return false;
        
        try {
            // Check by username lockout
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM account_lockouts 
                WHERE (username = ? OR user_id = (SELECT id FROM users WHERE username = ? OR email = ?))
                AND is_active = 1 
                AND (unlock_at IS NULL OR unlock_at > NOW())
            ');
            $stmt->execute([$username, $username, $username]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (Exception $e) {
            error_log('[SecurityController::isAccountLocked] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if IP is locked
     */
    public function isIPLocked($ipAddress) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM account_lockouts 
                WHERE ip_address = ? 
                AND is_active = 1 
                AND (unlock_at IS NULL OR unlock_at > NOW())
            ');
            $stmt->execute([$ipAddress]);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (Exception $e) {
            error_log('[SecurityController::isIPLocked] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Record login attempt
     */
    public function recordLoginAttempt($username, $success, $failureReason = null, $userId = null) {
        if (!$this->db) return false;
        
        $ipAddress = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            // Record in login_attempts
            $stmt = $this->db->prepare('
                INSERT INTO login_attempts (username, ip_address, user_agent, success, failure_reason) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$username, $ipAddress, $userAgent, $success, $failureReason]);
            
            if ($success) {
                // Clear failed attempts on successful login
                $this->clearFailedAttempts($username);
                
                // Log security event
                $this->logSecurityEvent($userId, 'LOGIN', [
                    'username' => $username,
                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent
                ], 'LOW');
                
                // Start session tracking
                $this->startSessionTracking($userId, $ipAddress, $userAgent);
                
            } else {
                // Increment failed attempts
                $this->incrementFailedAttempts($username);
                
                // Log failed attempt
                $this->logSecurityEvent($userId, 'LOGIN_FAILED', [
                    'username' => $username,
                    'ip_address' => $ipAddress,
                    'failure_reason' => $failureReason,
                    'user_agent' => $userAgent
                ], 'MEDIUM');
                
                // Check if account should be locked
                $this->checkAndLockAccount($username, $ipAddress);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('[SecurityController::recordLoginAttempt] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clear failed attempts for user
     */
    private function clearFailedAttempts($username) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare('
                UPDATE users 
                SET failed_login_attempts = 0, locked_until = NULL 
                WHERE username = ? OR email = ?
            ');
            $stmt->execute([$username, $username]);
        } catch (Exception $e) {
            error_log('[SecurityController::clearFailedAttempts] Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Increment failed attempts counter
     */
    private function incrementFailedAttempts($username) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare('
                UPDATE users 
                SET failed_login_attempts = failed_login_attempts + 1 
                WHERE username = ? OR email = ?
            ');
            $stmt->execute([$username, $username]);
        } catch (Exception $e) {
            error_log('[SecurityController::incrementFailedAttempts] Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if account should be locked based on failed attempts
     */
    private function checkAndLockAccount($username, $ipAddress) {
        if (!$this->db) return;
        
        $maxAttempts = (int)$this->getSetting('max_login_attempts', 5);
        $lockoutDuration = (int)$this->getSetting('lockout_duration', 900);
        
        try {
            // Check user failed attempts
            $stmt = $this->db->prepare('
                SELECT id, failed_login_attempts 
                FROM users 
                WHERE (username = ? OR email = ?) AND failed_login_attempts >= ?
            ');
            $stmt->execute([$username, $username, $maxAttempts]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $this->lockAccount($user['id'], $username, $ipAddress, 'FAILED_ATTEMPTS', $lockoutDuration);
            }
            
        } catch (Exception $e) {
            error_log('[SecurityController::checkAndLockAccount] Exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Lock user account
     */
    public function lockAccount($userId, $username, $ipAddress, $reason = 'FAILED_ATTEMPTS', $duration = null) {
        if (!$this->db) return false;
        
        if ($duration === null) {
            $duration = (int)$this->getSetting('lockout_duration', 900);
        }
        
        try {
            $this->db->beginTransaction();
            
            // Create lockout record
            $unlockAt = date('Y-m-d H:i:s', time() + $duration);
            $stmt = $this->db->prepare('
                INSERT INTO account_lockouts (user_id, username, ip_address, lockout_reason, unlock_at) 
                VALUES (?, ?, ?, ?, ?)
            ');
            $stmt->execute([$userId, $username, $ipAddress, $reason, $unlockAt]);
            
            // Update user table
            $stmt = $this->db->prepare('
                UPDATE users 
                SET locked_until = ? 
                WHERE id = ?
            ');
            $stmt->execute([$unlockAt, $userId]);
            
            // Log security event
            $this->logSecurityEvent($userId, 'ACCOUNT_LOCKED', [
                'username' => $username,
                'reason' => $reason,
                'unlock_at' => $unlockAt,
                'ip_address' => $ipAddress
            ], 'HIGH');
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[SecurityController::lockAccount] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Unlock user account
     */
    public function unlockAccount($userId, $unlockedBy) {
        if (!$this->db) return false;
        
        try {
            $this->db->beginTransaction();
            
            // Deactivate lockout records
            $stmt = $this->db->prepare('
                UPDATE account_lockouts 
                SET is_active = 0, unlocked_by = ?, unlocked_at = NOW() 
                WHERE user_id = ? AND is_active = 1
            ');
            $stmt->execute([$unlockedBy, $userId]);
            
            // Clear user lockout
            $stmt = $this->db->prepare('
                UPDATE users 
                SET locked_until = NULL, failed_login_attempts = 0 
                WHERE id = ?
            ');
            $stmt->execute([$userId]);
            
            // Log security event
            $this->logSecurityEvent($userId, 'ACCOUNT_UNLOCKED', [
                'unlocked_by' => $unlockedBy
            ], 'MEDIUM');
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[SecurityController::unlockAccount] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate password strength
     */
    public function validatePassword($password) {
        $errors = [];
        
        $minLength = (int)$this->getSetting('password_min_length', 8);
        if (strlen($password) < $minLength) {
            $errors[] = "Password must be at least {$minLength} characters long";
        }
        
        if ($this->getSetting('password_require_uppercase', 1) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if ($this->getSetting('password_require_lowercase', 1) && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if ($this->getSetting('password_require_numbers', 1) && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if ($this->getSetting('password_require_symbols', 1) && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
    
    /**
     * Check if password has been used recently
     */
    public function isPasswordReused($userId, $password) {
        if (!$this->db) return false;
        
        $historyCount = (int)$this->getSetting('password_history_count', 5);
        if ($historyCount <= 0) return false;
        
        try {
            $stmt = $this->db->prepare('
                SELECT password_hash 
                FROM password_history 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ');
            $stmt->execute([$userId, $historyCount]);
            
            $previousHashes = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($previousHashes as $hash) {
                if (password_verify($password, $hash)) {
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('[SecurityController::isPasswordReused] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Store password in history
     */
    public function storePasswordHistory($userId, $passwordHash) {
        if (!$this->db) return false;
        
        try {
            // Add new password to history
            $stmt = $this->db->prepare('
                INSERT INTO password_history (user_id, password_hash) 
                VALUES (?, ?)
            ');
            $stmt->execute([$userId, $passwordHash]);
            
            // Clean old history
            $historyCount = (int)$this->getSetting('password_history_count', 5);
            $stmt = $this->db->prepare('
                DELETE FROM password_history 
                WHERE user_id = ? AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM password_history 
                        WHERE user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT ?
                    ) AS recent
                )
            ');
            $stmt->execute([$userId, $userId, $historyCount]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[SecurityController::storePasswordHistory] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Start session tracking
     */
    public function startSessionTracking($userId, $ipAddress, $userAgent) {
        if (!$this->db) return false;
        
        $sessionId = session_id();
        $sessionTimeout = (int)$this->getSetting('session_timeout', 3600);
        $expiresAt = date('Y-m-d H:i:s', time() + $sessionTimeout);
        
        try {
            // Deactivate old sessions for this user
            $stmt = $this->db->prepare('
                UPDATE user_sessions 
                SET is_active = 0 
                WHERE user_id = ? AND session_id != ?
            ');
            $stmt->execute([$userId, $sessionId]);
            
            // Insert or update current session
            $stmt = $this->db->prepare('
                INSERT INTO user_sessions (session_id, user_id, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    last_activity = NOW(), 
                    is_active = 1, 
                    expires_at = ?
            ');
            $stmt->execute([$sessionId, $userId, $ipAddress, $userAgent, $expiresAt, $expiresAt]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[SecurityController::startSessionTracking] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update session activity
     */
    public function updateSessionActivity($sessionId) {
        if (!$this->db) return false;
        
        $sessionTimeout = (int)$this->getSetting('session_timeout', 3600);
        $expiresAt = date('Y-m-d H:i:s', time() + $sessionTimeout);
        
        try {
            $stmt = $this->db->prepare('
                UPDATE user_sessions 
                SET last_activity = NOW(), expires_at = ? 
                WHERE session_id = ? AND is_active = 1
            ');
            $stmt->execute([$expiresAt, $sessionId]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[SecurityController::updateSessionActivity] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * End session tracking
     */
    public function endSessionTracking($sessionId, $userId = null) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare('
                UPDATE user_sessions 
                SET is_active = 0 
                WHERE session_id = ?
            ');
            $stmt->execute([$sessionId]);
            
            if ($userId) {
                $this->logSecurityEvent($userId, 'LOGOUT', [
                    'session_id' => $sessionId
                ], 'LOW');
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('[SecurityController::endSessionTracking] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($userId, $eventType, $eventDetails = [], $riskLevel = 'LOW') {
        if (!$this->db) return false;
        
        $sessionId = session_id();
        $ipAddress = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            $stmt = $this->db->prepare('
                INSERT INTO security_logs (user_id, session_id, ip_address, user_agent, event_type, event_details, risk_level) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId, 
                $sessionId, 
                $ipAddress, 
                $userAgent, 
                $eventType, 
                json_encode($eventDetails), 
                $riskLevel
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[SecurityController::logSecurityEvent] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Detect suspicious activity
     */
    public function detectSuspiciousActivity($userId, $ipAddress = null) {
        if (!$this->db || !$this->getSetting('suspicious_activity_detection', 1)) {
            return false;
        }
        
        if (!$ipAddress) {
            $ipAddress = $this->getClientIP();
        }
        
        try {
            // Check for multiple failed logins from same IP
            $stmt = $this->db->prepare('
                SELECT COUNT(*) as failed_count 
                FROM login_attempts 
                WHERE ip_address = ? AND success = 0 AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ');
            $stmt->execute([$ipAddress]);
            $ipFailures = $stmt->fetchColumn();
            
            if ($ipFailures >= 10) {
                $this->logSecurityEvent($userId, 'SUSPICIOUS_ACTIVITY', [
                    'type' => 'MULTIPLE_FAILED_LOGINS_IP',
                    'ip_address' => $ipAddress,
                    'failed_count' => $ipFailures
                ], 'HIGH');
                
                // Lock IP
                $this->lockIPAddress($ipAddress, 'SUSPICIOUS_ACTIVITY');
                return true;
            }
            
            // Check for logins from multiple IPs for same user
            if ($userId) {
                $stmt = $this->db->prepare('
                    SELECT COUNT(DISTINCT ip_address) as ip_count 
                    FROM user_sessions 
                    WHERE user_id = ? AND login_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ');
                $stmt->execute([$userId]);
                $ipCount = $stmt->fetchColumn();
                
                if ($ipCount >= 3) {
                    $this->logSecurityEvent($userId, 'SUSPICIOUS_ACTIVITY', [
                        'type' => 'MULTIPLE_IP_LOGINS',
                        'ip_count' => $ipCount
                    ], 'MEDIUM');
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('[SecurityController::detectSuspiciousActivity] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lock IP address
     */
    private function lockIPAddress($ipAddress, $reason, $duration = 3600) {
        if (!$this->db) return false;
        
        try {
            $unlockAt = date('Y-m-d H:i:s', time() + $duration);
            $stmt = $this->db->prepare('
                INSERT INTO account_lockouts (ip_address, lockout_reason, unlock_at) 
                VALUES (?, ?, ?)
            ');
            $stmt->execute([$ipAddress, $reason, $unlockAt]);
            
            return true;
            
        } catch (Exception $e) {
            error_log('[SecurityController::lockIPAddress] Exception: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client IP address
     */
    public function getClientIP() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Check token age (valid for 1 hour)
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get security dashboard data
     */
    public function getSecurityStats() {
        if (!$this->db) return [];
        
        try {
            $stats = [];
            
            // Failed logins in last 24 hours
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM login_attempts 
                WHERE success = 0 AND attempt_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ');
            $stmt->execute();
            $stats['failed_logins_24h'] = $stmt->fetchColumn();
            
            // Active lockouts
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM account_lockouts 
                WHERE is_active = 1 AND (unlock_at IS NULL OR unlock_at > NOW())
            ');
            $stmt->execute();
            $stats['active_lockouts'] = $stmt->fetchColumn();
            
            // Active sessions
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM user_sessions 
                WHERE is_active = 1 AND expires_at > NOW()
            ');
            $stmt->execute();
            $stats['active_sessions'] = $stmt->fetchColumn();
            
            // High risk events in last 7 days
            $stmt = $this->db->prepare('
                SELECT COUNT(*) 
                FROM security_logs 
                WHERE risk_level IN ("HIGH", "CRITICAL") 
                AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ');
            $stmt->execute();
            $stats['high_risk_events'] = $stmt->fetchColumn();
            
            return $stats;
            
        } catch (Exception $e) {
            error_log('[SecurityController::getSecurityStats] Exception: ' . $e->getMessage());
            return [];
        }
    }
}

?>
