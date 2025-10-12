<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../shared/session_handler.php';
require_once __DIR__ . '/../shared/config.php';

if (!isset($user_id)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 0;
$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$date = $_POST['appointment_date'] ?? '';
$time = $_POST['appointment_time'] ?? '';
$reason = $_POST['reason'] ?? '';

if ($patient_id <= 0 || $doctor_id <= 0 || !$date || !$time) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

try {
    $db = getDBConnection();

    // Basic schedule validation: check clinic working days and hours from settings
    $settingsStmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('working_hours_start','working_hours_end','working_days')");
    $settingsStmt->execute();
    $settings = [];
    foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $s) $settings[$s['setting_key']] = $s['setting_value'];

    $working_start = $settings['working_hours_start'] ?? '08:00';
    $working_end = $settings['working_hours_end'] ?? '20:00';
    $working_days = json_decode($settings['working_days'] ?? '[]', true) ?: [];

    $apptDay = strtolower(date('l', strtotime($date)));
    if (!in_array($apptDay, $working_days)) {
        echo json_encode(['success' => false, 'message' => 'Clinic is closed on selected day']);
        exit();
    }

    // check time within working hours
    if ($time < $working_start || $time > $working_end) {
        echo json_encode(['success' => false, 'message' => 'Selected time is outside clinic working hours']);
        exit();
    }

    // check doctor schedule for that day (basic availability check)
    $scheduleStmt = $db->prepare('SELECT start_time, end_time FROM doctor_schedules WHERE doctor_id = :did AND day_of_week = :dow LIMIT 1');
    $scheduleStmt->bindValue(':did', $doctor_id, PDO::PARAM_INT);
    $scheduleStmt->bindValue(':dow', $apptDay, PDO::PARAM_STR);
    $scheduleStmt->execute();
    $sched = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
    if ($sched) {
        if ($time < $sched['start_time'] || $time > $sched['end_time']) {
            echo json_encode(['success' => false, 'message' => 'Selected time is outside doctor working hours']);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Doctor is not scheduled on selected day']);
        exit();
    }
    // prevent overlapping appointments for same doctor at same date/time
    $overlapStmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = :did AND appointment_date = :adate AND appointment_time = :atime AND status IN ("scheduled","in_progress")');
    $overlapStmt->bindValue(':did', $doctor_id, PDO::PARAM_INT);
    $overlapStmt->bindValue(':adate', $date, PDO::PARAM_STR);
    $overlapStmt->bindValue(':atime', $time, PDO::PARAM_STR);
    $overlapStmt->execute();
    $count = (int)$overlapStmt->fetchColumn();
    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => 'Doctor already has an appointment at this time']);
        exit();
    }
    $stmt = $db->prepare('INSERT INTO appointments (appointment_id, patient_id, doctor_id, appointment_date, appointment_time, status, reason, created_by) VALUES (:aid, :pid, :did, :adate, :atime, :status, :reason, :created_by)');
    $stmt->bindValue(':aid', null, PDO::PARAM_NULL);
    $stmt->bindValue(':pid', $patient_id, PDO::PARAM_INT);
    $stmt->bindValue(':did', $doctor_id, PDO::PARAM_INT);
    $stmt->bindValue(':adate', $date, PDO::PARAM_STR);
    $stmt->bindValue(':atime', $time, PDO::PARAM_STR);
    $stmt->bindValue(':status', 'scheduled', PDO::PARAM_STR);
    if ($reason !== '') {
        $stmt->bindValue(':reason', $reason, PDO::PARAM_STR);
    } else {
        $stmt->bindValue(':reason', null, PDO::PARAM_NULL);
    }
    $stmt->bindValue(':created_by', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
} catch (Exception $e) {
    error_log('[ajax/create_appointment] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Insert failed']);
}
