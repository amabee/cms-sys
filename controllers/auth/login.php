<?php

class Login
{
  private $db;
  private $securityController;

  public function __construct()
  {
    $this->db = getDBConnection();
    if (!$this->db) {
      throw new Exception("Database connection failed");
    }
    
    // Initialize security controller
    require_once __DIR__ . '/../SecurityController.php';
    try {
      $this->securityController = new SecurityController();
    } catch (Exception $e) {
      error_log("SecurityController init failed: " . $e->getMessage());
      $this->securityController = null;
    }
  }

  public function authenticate($username, $password)
  {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    // include logger (best-effort)
    require_once __DIR__ . '/../SystemLogger.php';
    $logger = null;
    try {
      $logger = new SystemLogger();
    } catch (Exception $e) {
      error_log("Logger init failed: " . $e->getMessage());
    }

    // sanitize inputs
    $username = trim((string)$username);
    
    // Security checks
    if ($this->securityController) {
      $clientIP = $this->securityController->getClientIP();
      
      // Check if IP is locked
      if ($this->securityController->isIPLocked($clientIP)) {
        $this->securityController->recordLoginAttempt($username, false, 'IP_LOCKED');
        throw new Exception("Access denied. IP address is temporarily locked due to suspicious activity.");
      }
      
      // Check if account is locked
      if ($this->securityController->isAccountLocked($username)) {
        $this->securityController->recordLoginAttempt($username, false, 'ACCOUNT_LOCKED');
        throw new Exception("Account is temporarily locked due to multiple failed login attempts. Please try again later.");
      }
      
      // Detect suspicious activity
      $this->securityController->detectSuspiciousActivity(null, $clientIP);
    }

    // New users table: id, username, email, password, first_name, last_name, role, profile_image
    try {
      $stmt = $this->db->prepare(
        'SELECT id as user_id, username, email, password, first_name, last_name, role, profile_image
         FROM users
         WHERE username = ? OR email = ? LIMIT 1'
      );
      $stmt->execute([$username, $username]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
      error_log('DB error during authentication: ' . $e->getMessage());
      if ($logger) {
        try { $logger->log(null, 'AUTH_ERROR', 'DB error during authenticate'); } catch (Exception $ex) {}
      }
      return false;
    }

    $dummyHash = password_hash('invalid_password', PASSWORD_DEFAULT);
    $passwordOk = false;
    if ($user && !empty($user['password'])) {
      $passwordOk = password_verify($password, $user['password']);
    } else {
      password_verify($password, $dummyHash);
    }

    if ($user && $passwordOk) {
      // Prevent session fixation
      session_regenerate_id(true);

      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['username'] = $user['username'];
      $_SESSION['user_email'] = $user['email'] ?? '';
      $_SESSION['user_name'] = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['username'];
      $_SESSION['user_type'] = strtolower($user['role'] ?? '');
      $_SESSION['last_login'] = date('Y-m-d H:i:s');
      $_SESSION['user_image'] = $user['profile_image'] ?? '../assets/img/avatars/default.png';

      // Enhanced security: Generate CSRF token
      if ($this->securityController) {
        $_SESSION['csrf_token'] = $this->securityController->generateCSRFToken();
      }

      // Update last_login column if exists
      try {
        $updateStmt = $this->db->prepare('UPDATE users SET last_login = ?, failed_login_attempts = 0, locked_until = NULL WHERE id = ?');
        $updateStmt->execute([$_SESSION['last_login'], $user['user_id']]);
      } catch (Exception $e) {
        error_log('Failed to update last_login: ' . $e->getMessage());
        if ($logger) {
          try { $logger->log(null, 'login', message: 'Failed to update last_login'); } catch (Exception $ex) {}
        }
      }

      // Enhanced security logging
      if ($this->securityController) {
        $this->securityController->recordLoginAttempt($username, true, null, $user['user_id']);
      }

      // Log successful login (best-effort)
      try {
        if ($logger) {
          $logger->logAuthAction($user['user_id'], 'LOGIN');
        }
      } catch (Exception $e) {
        error_log('Login logging failed: ' . $e->getMessage());
      }

      return true;
    }

    // Enhanced security: Record failed attempt
    if ($this->securityController) {
      $failureReason = $user ? 'INVALID_PASSWORD' : 'INVALID_USERNAME';
      $this->securityController->recordLoginAttempt($username, false, $failureReason, $user['user_id'] ?? null);
    }

    try {
      if ($logger) {
        $actorId = $user['user_id'] ?? null;
        $logger->log($actorId, 'LOGIN_FAIL', "Failed login attempt for '{$username}'");
      }
    } catch (Exception $e) {
      error_log('Failed login attempt logging failed: ' . $e->getMessage());
    }

    return false;
  }
}

?>

