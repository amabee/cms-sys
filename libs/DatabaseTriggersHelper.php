<?php
/**
 * Database Triggers Helper
 * Employee Management System
 * 
 * This helper class provides methods to work with database triggers
 * and ensure proper user context for logging triggers.
 */

class DatabaseTriggersHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Set the current user ID for trigger logging
     * Call this before performing any employee operations that should be logged
     */
    public function setCurrentUserForLogging($userId) {
        try {
            $stmt = $this->pdo->prepare("SET @current_user_id = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (Exception $e) {
            error_log("Error setting current user for logging: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create a new employee with proper validation and logging
     */
    public function createEmployee($data) {
        try {
            // Set current user for logging
            if (isset($_SESSION['user_id'])) {
                $this->setCurrentUserForLogging($_SESSION['user_id']);
            }
            
            // Prepare the insert statement
            $sql = "INSERT INTO employees (
                user_id, first_name, middle_name, last_name, email, contact_number,
                gender, birthdate, department_id, position_id, basic_salary,
                date_hired, employment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $data['user_id'],
                $data['first_name'],
                $data['middle_name'] ?? null,
                $data['last_name'],
                $data['email'],
                $data['contact_number'] ?? null,
                $data['gender'] ?? null,
                $data['birthdate'] ?? null,
                $data['department_id'] ?? null,
                $data['position_id'] ?? null,
                $data['basic_salary'],
                $data['date_hired'] ?? null,
                $data['employment_status'] ?? 1
            ]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            
            return false;
            
        } catch (Exception $e) {
            // The trigger validation will throw exceptions with meaningful messages
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Update an employee with proper validation and logging
     */
    public function updateEmployee($employeeId, $data) {
        try {
            // Set current user for logging
            if (isset($_SESSION['user_id'])) {
                $this->setCurrentUserForLogging($_SESSION['user_id']);
            }
            
            // Build dynamic update query based on provided data
            $updateFields = [];
            $params = [];
            
            $allowedFields = [
                'first_name', 'middle_name', 'last_name', 'email', 'contact_number',
                'gender', 'birthdate', 'department_id', 'position_id',
                'basic_salary', 'date_hired', 'employment_status', 'image'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception("No valid fields provided for update");
            }
            
            $sql = "UPDATE employees SET " . implode(', ', $updateFields) . " WHERE employee_id = ?";
            $params[] = $employeeId;
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            return $result;
            
        } catch (Exception $e) {
            // The trigger validation will throw exceptions with meaningful messages
            throw new Exception($e->getMessage());
        }
    }
    
    /**
     * Check if triggers are installed
     */
    public function checkTriggersInstalled() {
        try {
            $stmt = $this->pdo->query("
                SELECT TRIGGER_NAME 
                FROM information_schema.TRIGGERS 
                WHERE TRIGGER_SCHEMA = DATABASE() 
                AND EVENT_OBJECT_TABLE = 'employees'
                ORDER BY TRIGGER_NAME
            ");
            
            $triggers = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $expectedTriggers = [
                'after_employee_insert_logging',
                'after_employee_update_logging',
                'before_employee_insert_validation',
                'before_employee_update_validation'
            ];
            
            $result = [
                'installed' => count($triggers) === count($expectedTriggers),
                'found_triggers' => $triggers,
                'expected_triggers' => $expectedTriggers,
                'missing_triggers' => array_diff($expectedTriggers, $triggers)
            ];
            
            return $result;
            
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'installed' => false,
                'found_triggers' => [],
                'expected_triggers' => [],
                'missing_triggers' => []
            ];
        }
    }
    
    /**
     * Get recent employee-related logs
     */
    public function getEmployeeLogs($limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT sl.*, u.username, u.full_name
                FROM system_logs sl
                LEFT JOIN users u ON sl.user_id = u.user_id
                WHERE sl.full_description LIKE '%Employee%'
                ORDER BY sl.date_performed DESC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error fetching employee logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Test trigger functionality
     */
    public function testTriggers() {
        $results = [];
        
        try {
            // Test 1: Check if triggers are installed
            $triggerCheck = $this->checkTriggersInstalled();
            $results['trigger_installation'] = $triggerCheck;
            
            if (!$triggerCheck['installed']) {
                $results['error'] = "Not all triggers are installed. Please run the database_triggers.sql file.";
                return $results;
            }
            
            // Test 2: Test validation (this should fail)
            try {
                $this->createEmployee([
                    'user_id' => 1,
                    'first_name' => '', // Empty first name should fail
                    'last_name' => 'Test',
                    'email' => 'test@example.com',
                    'basic_salary' => 15000
                ]);
                $results['validation_test'] = 'FAILED - Should have rejected empty first name';
            } catch (Exception $e) {
                $results['validation_test'] = 'PASSED - Correctly rejected: ' . $e->getMessage();
            }
            
            // Test 3: Test valid insertion (should succeed)
            try {
                $testEmail = 'test_trigger_' . time() . '@example.com';
                $employeeId = $this->createEmployee([
                    'user_id' => 1,
                    'first_name' => 'Test',
                    'last_name' => 'Employee',
                    'email' => $testEmail,
                    'basic_salary' => 15000
                ]);
                
                if ($employeeId) {
                    $results['insert_test'] = 'PASSED - Employee created with ID: ' . $employeeId;
                    
                    // Test 4: Test update logging
                    $updateResult = $this->updateEmployee($employeeId, [
                        'basic_salary' => 16000,
                        'first_name' => 'Updated Test'
                    ]);
                    
                    if ($updateResult) {
                        $results['update_test'] = 'PASSED - Employee updated successfully';
                    } else {
                        $results['update_test'] = 'FAILED - Could not update employee';
                    }
                    
                    // Clean up test employee
                    $stmt = $this->pdo->prepare("DELETE FROM employees WHERE employee_id = ?");
                    $stmt->execute([$employeeId]);
                    
                } else {
                    $results['insert_test'] = 'FAILED - Could not create test employee';
                }
                
            } catch (Exception $e) {
                $results['insert_test'] = 'FAILED - ' . $e->getMessage();
            }
            
            // Test 5: Get recent logs to verify logging is working
            $recentLogs = $this->getEmployeeLogs(5);
            $results['logging_test'] = count($recentLogs) > 0 ? 'PASSED - Found ' . count($recentLogs) . ' recent logs' : 'No recent logs found';
            $results['recent_logs'] = $recentLogs;
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
}

/**
 * Example usage in your existing employee management files:
 * 
 * // In your add_employee.php or similar files:
 * 
 * $triggersHelper = new DatabaseTriggersHelper($pdo);
 * 
 * try {
 *     $employeeId = $triggersHelper->createEmployee([
 *         'user_id' => $userId,
 *         'first_name' => $_POST['first_name'],
 *         'last_name' => $_POST['last_name'],
 *         'email' => $_POST['email'],
 *         'basic_salary' => $_POST['basic_salary'],
 *         'department_id' => $_POST['department_id'],
 *         'position_id' => $_POST['position_id']
 *     ]);
 *     
 *     echo json_encode(['success' => true, 'employee_id' => $employeeId]);
 *     
 * } catch (Exception $e) {
 *     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
 * }
 * 
 * // For updates:
 * try {
 *     $result = $triggersHelper->updateEmployee($employeeId, [
 *         'first_name' => $_POST['first_name'],
 *         'basic_salary' => $_POST['basic_salary']
 *     ]);
 *     
 *     echo json_encode(['success' => true]);
 *     
 * } catch (Exception $e) {
 *     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
 * }
 */
?>
