<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/secure_session_handler.php';

// Only administrators can update security settings
requireRoleSecure(['admin']);

if (!isset($user_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Validate CSRF token
if (!validateCSRF($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception("Database connection failed");
    }
    
    $settings = [
        'max_login_attempts' => max(1, min(20, intval($_POST['max_login_attempts'] ?? 5))),
        'lockout_duration' => max(5, min(1440, intval($_POST['lockout_duration'] ?? 15))) * 60, // Convert to seconds
        'session_timeout' => max(5, min(480, intval($_POST['session_timeout'] ?? 60))) * 60, // Convert to seconds
        'password_min_length' => max(6, min(50, intval($_POST['password_min_length'] ?? 8))),
        'password_require_uppercase' => isset($_POST['password_require_uppercase']) ? 1 : 0,
        'password_require_lowercase' => isset($_POST['password_require_lowercase']) ? 1 : 0,
        'password_require_numbers' => isset($_POST['password_require_numbers']) ? 1 : 0,
        'password_require_symbols' => isset($_POST['password_require_symbols']) ? 1 : 0,
        'password_history_count' => max(0, min(25, intval($_POST['password_history_count'] ?? 5)))
    ];
    
    $db->beginTransaction();
    
    // Update each setting
    $stmt = $db->prepare('
        UPDATE security_settings 
        SET setting_value = ?, updated_by = ?, updated_at = NOW() 
        WHERE setting_name = ?
    ');
    
    foreach ($settings as $name => $value) {
        $stmt->execute([$value, $user_id, $name]);
    }
    
    $db->commit();
    
    // Log the settings change
    logSecurityEvent('SECURITY_SETTINGS_CHANGED', [
        'changed_by' => $user_id,
        'settings' => array_keys($settings)
    ], 'HIGH');
    
    echo json_encode(['success' => true, 'message' => 'Security settings updated successfully']);
    
} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    error_log('[update_security_settings] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update security settings']);
}
?>
