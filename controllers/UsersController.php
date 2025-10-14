<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/SystemLogger.php';

class UsersController {
    private $pdo;
    private $logger;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->logger = new SystemLogger();
    }
    
    /**
     * Get all users with filtering and pagination
     */
    public function getUsers($filters = []) {
        try {
            $whereConditions = [];
            $params = [];
            
            // Apply filters
            if (!empty($filters['role'])) {
                $whereConditions[] = "role = :role";
                $params['role'] = $filters['role'];
            }
            
            if (isset($filters['is_active']) && $filters['is_active'] !== '') {
                $whereConditions[] = "is_active = :is_active";
                $params['is_active'] = (int)$filters['is_active'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(first_name LIKE :search OR last_name LIKE :search OR email LIKE :search OR username LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            // Get total count for pagination
            $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM users $whereClause");
            foreach ($params as $key => $value) {
                $countStmt->bindValue(':' . $key, $value);
            }
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get users with pagination
            $limit = (int)($filters['limit'] ?? 50);
            $offset = (int)($filters['offset'] ?? 0);
            
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, first_name, last_name, role, phone, 
                       is_active, last_login, created_at, updated_at
                FROM users 
                $whereClause
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            // Bind pagination parameters
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            // Bind other parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue(':' . $key, $value);
            }
            
            $stmt->execute();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format user data
            foreach ($users as &$user) {
                $user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $user['status_label'] = $user['is_active'] ? 'Active' : 'Inactive';
                $user['role_label'] = ucfirst($user['role']);
                $user['last_login_formatted'] = $user['last_login'] 
                    ? date('M j, Y g:i A', strtotime($user['last_login'])) 
                    : 'Never';
                $user['created_at_formatted'] = date('M j, Y', strtotime($user['created_at']));
            }
            
            return [
                'success' => true,
                'users' => $users,
                'total_count' => $totalCount,
                'total_pages' => ceil($totalCount / $limit)
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get users: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve users'
            ];
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, first_name, last_name, role, phone, 
                       is_active, last_login, created_at, updated_at
                FROM users 
                WHERE id = :id
                LIMIT 1
            ");
            
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            // Format user data
            $user['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $user['status_label'] = $user['is_active'] ? 'Active' : 'Inactive';
            $user['role_label'] = ucfirst($user['role']);
            $user['last_login_formatted'] = $user['last_login'] 
                ? date('M j, Y g:i A', strtotime($user['last_login'])) 
                : 'Never';
            $user['created_at_formatted'] = date('M j, Y g:i A', strtotime($user['created_at']));
            
            return [
                'success' => true,
                'user' => $user
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get user by ID: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve user'
            ];
        }
    }
    
    /**
     * Create new user
     */
    public function createUser($data) {
        try {
            // Validate required fields
            $required_fields = ['username', 'email', 'password', 'first_name', 'last_name', 'role'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ];
                }
            }
            
            // Validate role
            $valid_roles = ['admin', 'doctor', 'nurse', 'receptionist', 'secretary'];
            if (!in_array($data['role'], $valid_roles)) {
                return [
                    'success' => false,
                    'message' => 'Invalid role specified'
                ];
            }
            
            // Check for duplicate username or email
            $checkStmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM users 
                WHERE username = :username OR email = :email
            ");
            $checkStmt->execute([
                'username' => $data['username'],
                'email' => $data['email']
            ]);
            
            if ($checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Username or email already exists'
                ];
            }
            
            // Hash password
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $this->pdo->prepare("
                INSERT INTO users (
                    username, email, password, first_name, last_name, 
                    role, phone, is_active
                ) VALUES (
                    :username, :email, :password, :first_name, :last_name,
                    :role, :phone, :is_active
                )
            ");
            
            $stmt->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'password' => $hashedPassword,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]);
            
            $user_id = $this->pdo->lastInsertId();
            
            $this->logger->log('INFO', "User created: {$data['username']} (ID: $user_id)", [
                'user_id' => $user_id,
                'role' => $data['role']
            ]);
            
            return [
                'success' => true,
                'user_id' => $user_id,
                'message' => 'User created successfully'
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to create user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to create user'
            ];
        }
    }
    
    /**
     * Update user
     */
    public function updateUser($id, $data) {
        try {
            // Validate required fields
            $required_fields = ['username', 'email', 'first_name', 'last_name', 'role'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    return [
                        'success' => false,
                        'message' => "Field '$field' is required"
                    ];
                }
            }
            
            // Validate role
            $valid_roles = ['admin', 'doctor', 'nurse', 'receptionist', 'secretary'];
            if (!in_array($data['role'], $valid_roles)) {
                return [
                    'success' => false,
                    'message' => 'Invalid role specified'
                ];
            }
            
            // Check if user exists
            $checkStmt = $this->pdo->prepare("SELECT id FROM users WHERE id = :id");
            $checkStmt->execute(['id' => $id]);
            if (!$checkStmt->fetch()) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Check for duplicate username or email (excluding current user)
            $duplicateStmt = $this->pdo->prepare("
                SELECT COUNT(*) as count FROM users 
                WHERE (username = :username OR email = :email) AND id != :id
            ");
            $duplicateStmt->execute([
                'username' => $data['username'],
                'email' => $data['email'],
                'id' => $id
            ]);
            
            if ($duplicateStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Username or email already exists'
                ];
            }
            
            // Prepare update SQL
            $sql = "
                UPDATE users SET 
                    username = :username,
                    email = :email,
                    first_name = :first_name,
                    last_name = :last_name,
                    role = :role,
                    phone = :phone,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
            ";
            
            $params = [
                'username' => $data['username'],
                'email' => $data['email'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'role' => $data['role'],
                'phone' => $data['phone'] ?? null,
                'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
                'id' => $id
            ];
            
            // Update password if provided
            if (!empty($data['password'])) {
                $sql .= ", password = :password";
                $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $this->logger->log('INFO', "User updated: {$data['username']} (ID: $id)", [
                'user_id' => $id,
                'role' => $data['role']
            ]);
            
            return [
                'success' => true,
                'message' => 'User updated successfully'
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to update user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update user'
            ];
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($id) {
        try {
            // Check if user exists
            $checkStmt = $this->pdo->prepare("
                SELECT username FROM users WHERE id = :id
            ");
            $checkStmt->execute(['id' => $id]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Prevent deletion of the current user
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ];
            }
            
            // Delete user
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $id]);
            
            $this->logger->log('INFO', "User deleted: {$user['username']} (ID: $id)");
            
            return [
                'success' => true,
                'message' => 'User deleted successfully'
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to delete user: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to delete user'
            ];
        }
    }
    
    /**
     * Deactivate/Activate user
     */
    public function toggleUserStatus($id, $is_active) {
        try {
            // Check if user exists
            $checkStmt = $this->pdo->prepare("
                SELECT username FROM users WHERE id = :id
            ");
            $checkStmt->execute(['id' => $id]);
            $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            
            // Prevent deactivation of current user
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id && !$is_active) {
                return [
                    'success' => false,
                    'message' => 'Cannot deactivate your own account'
                ];
            }
            
            // Update user status
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET is_active = :is_active, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmt->execute([
                'is_active' => (int)$is_active,
                'id' => $id
            ]);
            
            $action = $is_active ? 'activated' : 'deactivated';
            $this->logger->log('INFO', "User $action: {$user['username']} (ID: $id)");
            
            return [
                'success' => true,
                'message' => "User $action successfully"
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to toggle user status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update user status'
            ];
        }
    }
    
    /**
     * Get user statistics
     */
    public function getUserStatistics() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
                    COUNT(CASE WHEN is_active = 0 THEN 1 END) as inactive_users,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
                    COUNT(CASE WHEN role = 'doctor' THEN 1 END) as doctor_users,
                    COUNT(CASE WHEN role = 'nurse' THEN 1 END) as nurse_users,
                    COUNT(CASE WHEN role = 'receptionist' THEN 1 END) as receptionist_users,
                    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_logins
                FROM users
            ");
            
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => $stats
            ];
            
        } catch (Exception $e) {
            $this->logger->log('ERROR', 'Failed to get user statistics: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to retrieve user statistics'
            ];
        }
    }
    
    /**
     * Validate user data
     */
    private function validateUserData($data, $isUpdate = false) {
        $errors = [];
        
        // Username validation
        if (empty($data['username'])) {
            $errors[] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters long';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }
        
        // Email validation
        if (empty($data['email'])) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // Password validation (only for new users or when password is provided)
        if (!$isUpdate || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors[] = 'Password is required';
            } elseif (strlen($data['password']) < 6) {
                $errors[] = 'Password must be at least 6 characters long';
            }
        }
        
        // Name validation
        if (empty($data['first_name'])) {
            $errors[] = 'First name is required';
        }
        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required';
        }
        
        // Role validation
        $valid_roles = ['admin', 'doctor', 'nurse', 'receptionist', 'secretary'];
        if (empty($data['role']) || !in_array($data['role'], $valid_roles)) {
            $errors[] = 'Invalid role specified';
        }
        
        return $errors;
    }
}
