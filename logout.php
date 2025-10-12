<?php
require_once __DIR__ . '/shared/config.php';
require_once __DIR__ . '/controllers/SystemLogger.php';

// Ensure session is started and session handler variables are available
require_once __DIR__ . '/shared/session_handler.php';

// Capture current user information before clearing session
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? null;

// Destroy session and cookies
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}
session_destroy();

// Log logout event (best-effort)
try {
    $logger = new SystemLogger();
    if ($userId) {
        $logger->logAuthAction($userId, 'LOGOUT');
    } else {
        // If no user id (rare), still write a generic logout entry
        $logger->log(null, 'LOGOUT', 'Anonymous logout');
    }
} catch (Exception $e) {
    // swallow logging errors to avoid exposing to user
}

// Redirect to login page
header('Location: login.php');
exit();
