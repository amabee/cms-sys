<?php
/**
 * Enhanced Session Handler with Security Features
 * Integrates with SecurityController for comprehensive session management
 */

// Prevent multiple inclusions
if (defined('ENHANCED_SESSION_HANDLER_LOADED')) {
    return;
}
define('ENHANCED_SESSION_HANDLER_LOADED', true);

// Include original session handler
require_once __DIR__ . '/session_handler.php';

// Initialize security controller
require_once __DIR__ . '/../controllers/SecurityController.php';

$securityController = null;
try {
    $securityController = new SecurityController();
} catch (Exception $e) {
    error_log("Failed to initialize SecurityController: " . $e->getMessage());
}

/**
 * Enhanced authentication check with security validation
 */
if (!function_exists('checkAuthSecure')) {
    function checkAuthSecure() {
        global $securityController;
        
        if (!checkAuth()) {
            return false;
        }
        
        if ($securityController) {
            $sessionId = session_id();
            
            // Check if session exists and is valid
            try {
                $db = getDBConnection();
                if ($db) {
                    $stmt = $db->prepare('
                        SELECT id, expires_at 
                        FROM user_sessions 
                        WHERE session_id = ? AND is_active = 1 AND expires_at > NOW()
                    ');
                    $stmt->execute([$sessionId]);
                    $session = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$session) {
                        // Session expired or invalid
                        logoutSecure();
                        return false;
                    }
                    
                    // Update session activity
                    $securityController->updateSessionActivity($sessionId);
                }
            } catch (Exception $e) {
                error_log("Session validation error: " . $e->getMessage());
            }
        }
        
        return true;
    }
}

/**
 * Enhanced logout with security cleanup
 */
if (!function_exists('logoutSecure')) {
    function logoutSecure() {
        global $securityController;
        
        $sessionId = session_id();
        $userId = $_SESSION['user_id'] ?? null;
        
        // End session tracking
        if ($securityController && $sessionId) {
            $securityController->endSessionTracking($sessionId, $userId);
        }
        
        // Clear session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
}

/**
 * Enhanced role requirement with security logging
 */
if (!function_exists('requireRoleSecure')) {
    function requireRoleSecure($requiredRoles) {
        global $securityController;
        
        if (!checkAuthSecure()) {
            header('Location: ../login.php');
            exit();
        }
        
        if (!hasRole($requiredRoles)) {
            // Log permission denied
            if ($securityController) {
                $securityController->logSecurityEvent(
                    $_SESSION['user_id'] ?? null,
                    'PERMISSION_DENIED',
                    [
                        'required_roles' => is_array($requiredRoles) ? $requiredRoles : [$requiredRoles],
                        'user_role' => $_SESSION['user_type'] ?? '',
                        'requested_page' => $_SERVER['REQUEST_URI'] ?? ''
                    ],
                    'MEDIUM'
                );
            }
            
            header('Location: ../dashboard.php?error=access_denied');
            exit();
        }
    }
}

/**
 * CSRF Token validation for forms
 */
if (!function_exists('validateCSRF')) {
    function validateCSRF($token) {
        global $securityController;
        
        if (!$securityController) {
            return true; // Fallback if security controller not available
        }
        
        return $securityController->verifyCSRFToken($token);
    }
}

/**
 * Generate CSRF token for forms
 */
if (!function_exists('getCSRFToken')) {
    function getCSRFToken() {
        global $securityController;
        
        if (!$securityController) {
            return bin2hex(random_bytes(32)); // Fallback token
        }
        
        return $securityController->generateCSRFToken();
    }
}

/**
 * Render CSRF hidden input field
 */
if (!function_exists('csrfField')) {
    function csrfField() {
        $token = getCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}

/**
 * Log security event
 */
if (!function_exists('logSecurityEvent')) {
    function logSecurityEvent($eventType, $eventDetails = [], $riskLevel = 'LOW') {
        global $securityController;
        
        if ($securityController) {
            $securityController->logSecurityEvent(
                $_SESSION['user_id'] ?? null,
                $eventType,
                $eventDetails,
                $riskLevel
            );
        }
    }
}

/**
 * Check for session timeout and extend if active
 */
if (!function_exists('checkSessionTimeout')) {
    function checkSessionTimeout() {
        if (!checkAuth()) {
            return false;
        }
        
        $sessionTimeout = 3600; // 1 hour default
        $lastActivity = $_SESSION['last_activity'] ?? time();
        
        if (time() - $lastActivity > $sessionTimeout) {
            logoutSecure();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
}

/**
 * Initialize session security on every page load
 */
function initSessionSecurity() {
    global $securityController;
    
    if (checkAuth()) {
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Detect suspicious activity
        if ($securityController) {
            $securityController->detectSuspiciousActivity($_SESSION['user_id'] ?? null);
        }
    }
}

// Auto-initialize session security
if (session_status() !== PHP_SESSION_NONE) {
    initSessionSecurity();
}

/**
 * Password strength validation
 */
if (!function_exists('validatePasswordStrength')) {
    function validatePasswordStrength($password) {
        global $securityController;
        
        if ($securityController) {
            return $securityController->validatePassword($password);
        }
        
        // Fallback validation
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return $errors;
    }
}

/**
 * Enhanced user authentication check for critical operations
 */
if (!function_exists('requireUserAuthSecure')) {
    function requireUserAuthSecure($redirectUrl = '../login.php') {
        if (!checkAuthSecure()) {
            header("Location: $redirectUrl");
            exit();
        }
    }
}

// Set security headers
if (!headers_sent()) {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Strict transport security (if using HTTPS)
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // Content security policy (basic)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:;");
}

?>
