<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

// Check if user is authorized (admin only)
if (!isset($user_id) || $user_type !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate required fields
$requiredFields = ['first_name', 'last_name', 'email', 'phone', 'username', 'password', 'doctor_id', 'specialization'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// User data
$firstName = trim($_POST['first_name']);
$lastName = trim($_POST['last_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$username = trim($_POST['username']);
$password = trim($_POST['password']);

// Doctor data
$doctorId = trim($_POST['doctor_id']);
$specialization = trim($_POST['specialization']);
$licenseNumber = isset($_POST['license_number']) ? trim($_POST['license_number']) : null;
$experienceYears = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : 0;
$consultationFee = isset($_POST['consultation_fee']) ? floatval($_POST['consultation_fee']) : 0.00;
$isAvailable = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;
$qualification = isset($_POST['qualification']) ? trim($_POST['qualification']) : null;
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;

// Generate default profile image URL
$profileImage = "https://avatar.iran.liara.run/public/boy?username=" . urlencode($username);

try {
    $db = getDBConnection();
    
    // Start transaction
    $db->beginTransaction();
    
    // Check if username already exists
    $checkUsernameQuery = "SELECT id FROM users WHERE username = :username";
    $checkUsernameStmt = $db->prepare($checkUsernameQuery);
    $checkUsernameStmt->bindValue(':username', $username);
    $checkUsernameStmt->execute();
    
    if ($checkUsernameStmt->fetch()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Username already exists']);
        exit();
    }
    
    // Check if email already exists
    $checkEmailQuery = "SELECT id FROM users WHERE email = :email";
    $checkEmailStmt = $db->prepare($checkEmailQuery);
    $checkEmailStmt->bindValue(':email', $email);
    $checkEmailStmt->execute();
    
    if ($checkEmailStmt->fetch()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    // Check if doctor_id already exists
    $checkDoctorIdQuery = "SELECT id FROM doctors WHERE doctor_id = :doctor_id";
    $checkDoctorIdStmt = $db->prepare($checkDoctorIdQuery);
    $checkDoctorIdStmt->bindValue(':doctor_id', $doctorId);
    $checkDoctorIdStmt->execute();
    
    if ($checkDoctorIdStmt->fetch()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Doctor ID already exists']);
        exit();
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user with profile_image
    $insertUserQuery = "INSERT INTO users 
                        (username, password, email, phone, first_name, last_name, role, is_active, profile_image) 
                        VALUES 
                        (:username, :password, :email, :phone, :first_name, :last_name, 'doctor', 1, :profile_image)";
    
    $insertUserStmt = $db->prepare($insertUserQuery);
    $insertUserStmt->bindValue(':username', $username);
    $insertUserStmt->bindValue(':password', $hashedPassword);
    $insertUserStmt->bindValue(':email', $email);
    $insertUserStmt->bindValue(':phone', $phone);
    $insertUserStmt->bindValue(':first_name', $firstName);
    $insertUserStmt->bindValue(':last_name', $lastName);
    $insertUserStmt->bindValue(':profile_image', $profileImage);
    
    if (!$insertUserStmt->execute()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to create user account']);
        exit();
    }
    
    $newUserId = $db->lastInsertId();
    
    // Insert doctor with profile_image
    $insertDoctorQuery = "INSERT INTO doctors 
                          (user_id, doctor_id, specialization, license_number, experience_years, 
                           consultation_fee, is_available, qualification, bio, profile_image) 
                          VALUES 
                          (:user_id, :doctor_id, :specialization, :license_number, :experience_years, 
                           :consultation_fee, :is_available, :qualification, :bio, :profile_image)";
    
    $insertDoctorStmt = $db->prepare($insertDoctorQuery);
    $insertDoctorStmt->bindValue(':user_id', $newUserId, PDO::PARAM_INT);
    $insertDoctorStmt->bindValue(':doctor_id', $doctorId);
    $insertDoctorStmt->bindValue(':specialization', $specialization);
    $insertDoctorStmt->bindValue(':license_number', $licenseNumber);
    $insertDoctorStmt->bindValue(':experience_years', $experienceYears, PDO::PARAM_INT);
    $insertDoctorStmt->bindValue(':consultation_fee', $consultationFee);
    $insertDoctorStmt->bindValue(':is_available', $isAvailable, PDO::PARAM_INT);
    $insertDoctorStmt->bindValue(':qualification', $qualification);
    $insertDoctorStmt->bindValue(':bio', $bio);
    $insertDoctorStmt->bindValue(':profile_image', $profileImage);
    
    // Debug logging
    error_log('[ajax/create_doctor] About to insert doctor with profile_image: ' . $profileImage);
    error_log('[ajax/create_doctor] Query: ' . $insertDoctorQuery);
    
    if ($insertDoctorStmt->execute()) {
        // Commit transaction
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Doctor and user account created successfully',
            'user_id' => $newUserId,
            'doctor_id' => $db->lastInsertId()
        ]);
    } else {
        $db->rollBack();
        error_log('[ajax/create_doctor] Failed to insert doctor profile ' . $profileImage);
        echo json_encode(['success' => false, 'message' => 'Failed to create doctor profile']);
    }
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[ajax/create_doctor] Failed to insert doctor profile ' . $profileImage);
    error_log('[ajax/create_doctor] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating doctor: ' . $e->getMessage()
    ]);
}

