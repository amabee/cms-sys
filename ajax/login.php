<?php
/**
 * Login AJAX Endpoint
 * Handles user authentication and returns JSON response
 */

header('Content-Type: application/json');

// Start session
session_start();

// Include required files
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../controllers/SecurityController.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get POST data
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) && $_POST['remember'] == 1;
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        throw new Exception('Please provide both username and password');
    }
    
    // Get database connection
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    // Initialize security controller
    $securityController = new SecurityController();
    
    // Check if account is locked
    if ($securityController->isAccountLocked($username)) {
        throw new Exception('Account is temporarily locked due to multiple failed login attempts');
    }
    
    // Attempt login
    $sql = "SELECT id, username, email, password, role, is_active, last_login 
            FROM users 
            WHERE (username = ? OR email = ?) 
            LIMIT 1";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Record failed attempt in database manually
        $sql = "INSERT INTO login_attempts (username, ip_address, attempted_at, success) 
                VALUES (?, ?, NOW(), 0)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);
        
        throw new Exception('Invalid username or password');
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        // Record failed attempt in database manually
        $sql = "INSERT INTO login_attempts (username, ip_address, attempted_at, success) 
                VALUES (?, ?, NOW(), 0)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$username, $_SERVER['REMOTE_ADDR']]);
        
        throw new Exception('Invalid username or password');
    }
    
    // Check if user is active
    if ($user['is_active'] != 1) {
        throw new Exception('Your account is not active. Please contact administrator.');
    }
    
    // Clear failed login attempts manually
    $sql = "DELETE FROM login_attempts WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username]);
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['user_type'] = $user['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Handle remember me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        
        // Store token in database
        $sql = "INSERT INTO remember_tokens (user_id, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', $expiry)]);
        
        // Set cookie
        setcookie('remember_token', $token, $expiry, '/', '', true, true);
    }
    
    // Update last login
    $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user['id']]);
    
    // Log successful login manually
    $sql = "INSERT INTO login_attempts (username, ip_address, attempted_at, success, user_id) 
            VALUES (?, ?, NOW(), 1, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$username, $_SERVER['REMOTE_ADDR'], $user['id']]);
    
    // Determine redirect URL based on user type
    $redirectUrl = 'dashboard.php';
    switch ($user['role']) {
        case 'admin':
            $redirectUrl = 'pages/admin-dashboard.php';
            break;
        case 'doctor':
            $redirectUrl = 'pages/doctor-dashboard.php';
            break;
        case 'nurse':
            $redirectUrl = 'pages/nurse-dashboard.php';
            break;
        case 'receptionist':
            $redirectUrl = 'pages/receptionist-dashboard.php';
            break;
        case 'patient':
            $redirectUrl = 'pages/patient-dashboard.php';
            break;
        default:
            $redirectUrl = 'dashboard.php';
    }
    
    // Success response
    $response['success'] = true;
    $response['message'] = 'Login successful! Welcome back, ' . htmlspecialchars($user['username']);
    $response['redirect'] = $redirectUrl;
    $response['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'user_type' => $user['role']
    ];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error
    error_log('[Login Error] ' . $e->getMessage());
}

// Output JSON response
echo json_encode($response);
exit;
