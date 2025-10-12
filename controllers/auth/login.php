<?php

class Login
{
  private $db;

  public function __construct()
  {
    $this->db = getDBConnection();
    if (!$this->db) {
      throw new Exception("Database connection failed");
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

      // Update last_login column if exists
      try {
        $updateStmt = $this->db->prepare('UPDATE users SET last_login = ? WHERE id = ?');
        $updateStmt->execute([$_SESSION['last_login'], $user['user_id']]);
      } catch (Exception $e) {
        error_log('Failed to update last_login: ' . $e->getMessage());
         if ($logger) {
        try { $logger->log(null, 'login', message: 'Failed to update last_login'); } catch (Exception $ex) {}
      }
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

