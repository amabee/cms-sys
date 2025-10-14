<?php
/**
 * Check Session AJAX Endpoint
 * Returns current session information
 */

header('Content-Type: application/json');
session_start();

$response = [
    'success' => false,
    'message' => '',
    'user_id' => null,
    'username' => null,
    'user_type' => null,
    'email' => null
];

try {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
        $response['success'] = true;
        $response['user_id'] = $_SESSION['user_id'];
        $response['username'] = $_SESSION['username'] ?? '';
        $response['user_type'] = $_SESSION['user_type'];
        $response['email'] = $_SESSION['email'] ?? '';
        $response['first_name'] = $_SESSION['first_name'] ?? '';
        $response['last_name'] = $_SESSION['last_name'] ?? '';
        
        // If user is a doctor, get their doctor_id
        if ($_SESSION['user_type'] === 'doctor') {
            require_once __DIR__ . '/../shared/config.php';
            try {
                $pdo = new PDO(
                    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $doctor = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($doctor) {
                    $response['doctor_id'] = $doctor['id'];
                }
            } catch (Exception $e) {
                error_log('Error fetching doctor_id: ' . $e->getMessage());
            }
        }
    } else {
        $response['message'] = 'Not authenticated';
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
