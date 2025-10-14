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
$requiredFields = ['id', 'first_name', 'last_name', 'email', 'phone', 'specialization'];
foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

$doctorDbId = intval($_POST['id']);
$firstName = trim($_POST['first_name']);
$lastName = trim($_POST['last_name']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']);
$specialization = trim($_POST['specialization']);
$licenseNumber = isset($_POST['license_number']) ? trim($_POST['license_number']) : null;
$experienceYears = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : 0;
$consultationFee = isset($_POST['consultation_fee']) ? floatval($_POST['consultation_fee']) : 0.00;
$isAvailable = isset($_POST['is_available']) ? intval($_POST['is_available']) : 1;
$qualification = isset($_POST['qualification']) ? trim($_POST['qualification']) : null;
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : null;

try {
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Get user_id from doctor record
    $getDoctorQuery = "SELECT user_id FROM doctors WHERE id = :id";
    $getDoctorStmt = $db->prepare($getDoctorQuery);
    $getDoctorStmt->bindValue(':id', $doctorDbId, PDO::PARAM_INT);
    $getDoctorStmt->execute();
    $doctorRecord = $getDoctorStmt->fetch();
    
    if (!$doctorRecord) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Doctor not found']);
        exit();
    }
    
    $userId = $doctorRecord['user_id'];
    
    // Check if email already exists for another user
    $checkEmailQuery = "SELECT id FROM users WHERE email = :email AND id != :user_id";
    $checkEmailStmt = $db->prepare($checkEmailQuery);
    $checkEmailStmt->bindValue(':email', $email);
    $checkEmailStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkEmailStmt->execute();
    
    if ($checkEmailStmt->fetch()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit();
    }
    
    // Update user information
    $updateUserQuery = "UPDATE users 
                        SET email = :email, 
                            phone = :phone, 
                            first_name = :first_name, 
                            last_name = :last_name
                        WHERE id = :user_id";
    
    $updateUserStmt = $db->prepare($updateUserQuery);
    $updateUserStmt->bindValue(':email', $email);
    $updateUserStmt->bindValue(':phone', $phone);
    $updateUserStmt->bindValue(':first_name', $firstName);
    $updateUserStmt->bindValue(':last_name', $lastName);
    $updateUserStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    
    if (!$updateUserStmt->execute()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to update user information']);
        exit();
    }
    
    // Update doctor information
    $updateDoctorQuery = "UPDATE doctors 
                          SET specialization = :specialization,
                              license_number = :license_number,
                              experience_years = :experience_years,
                              consultation_fee = :consultation_fee,
                              is_available = :is_available,
                              qualification = :qualification,
                              bio = :bio
                          WHERE id = :id";
    
    $updateDoctorStmt = $db->prepare($updateDoctorQuery);
    $updateDoctorStmt->bindValue(':specialization', $specialization);
    $updateDoctorStmt->bindValue(':license_number', $licenseNumber);
    $updateDoctorStmt->bindValue(':experience_years', $experienceYears, PDO::PARAM_INT);
    $updateDoctorStmt->bindValue(':consultation_fee', $consultationFee);
    $updateDoctorStmt->bindValue(':is_available', $isAvailable, PDO::PARAM_INT);
    $updateDoctorStmt->bindValue(':qualification', $qualification);
    $updateDoctorStmt->bindValue(':bio', $bio);
    $updateDoctorStmt->bindValue(':id', $doctorDbId, PDO::PARAM_INT);
    
    if ($updateDoctorStmt->execute()) {
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Doctor profile updated successfully'
        ]);
    } else {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to update doctor profile']);
    }
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[ajax/update_doctor] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating doctor: ' . $e->getMessage()
    ]);
}
