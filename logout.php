<?php
require_once __DIR__ . '/shared/config.php';
require_once __DIR__ . '/controllers/SystemLogger.php';

// Use enhanced secure session handler
require_once __DIR__ . '/shared/secure_session_handler.php';

// Capture current user information before clearing session
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? null;

// Log logout event (before session destruction)
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

// Perform enhanced secure logout with security cleanup
logoutSecure();

// Redirect to login page with logout confirmation
header('Location: login.php?logged_out=1');
exit();
