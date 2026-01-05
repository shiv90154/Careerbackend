<?php
require_once 'database.php';
require_once 'jwt.php';

class Auth {
    private $conn;
    private $table = 'users';

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function login($email, $password) {
        $query = "SELECT id, role, full_name, email, password, is_active 
                  FROM " . $this->table . " 
                  WHERE email = :email AND is_active = 1 
                  LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                // Update last login
                $this->updateLastLogin($row['id']);
                
                // Generate JWT token
                $token = JWTHandler::generateToken($row['id'], $row['role'], $row['email']);
                
                // Remove password from response
                unset($row['password']);
                
                return [
                    'success' => true,
                    'token' => $token,
                    'user' => $row
                ];
            }
        }
        
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    private function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table . " 
                  SET last_login = NOW() 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
    }

    public function register($data) {
        // Check if email already exists
        $checkQuery = "SELECT id FROM " . $this->table . " WHERE email = :email";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(':email', $data['email']);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Hash password
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);

        $query = "INSERT INTO " . $this->table . " 
                  (role, full_name, email, password, phone, address, city, state, pincode) 
                  VALUES 
                  ('student', :full_name, :email, :password, :phone, :address, :city, :state, :pincode)";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':full_name', $data['full_name']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':address', $data['address']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':state', $data['state']);
        $stmt->bindParam(':pincode', $data['pincode']);

        if ($stmt->execute()) {
            $user_id = $this->conn->lastInsertId();
            $token = JWTHandler::generateToken($user_id, 'student', $data['email']);
            
            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user_id,
                    'role' => 'student',
                    'full_name' => $data['full_name'],
                    'email' => $data['email']
                ]
            ];
        }

        return ['success' => false, 'message' => 'Registration failed'];
    }

    public function validateToken() {
        $token = JWTHandler::getBearerToken();
        
        if (!$token) {
            return ['valid' => false, 'message' => 'Token not provided'];
        }

        $decoded = JWTHandler::validateToken($token);
        
        if (!$decoded) {
            return ['valid' => false, 'message' => 'Invalid or expired token'];
        }

        return ['valid' => true, 'user' => $decoded];
    }

    public function checkAccess($required_role = null) {
        $validation = $this->validateToken();
        
        if (!$validation['valid']) {
            return $validation;
        }

        if ($required_role && $validation['user']['role'] !== $required_role) {
            return ['valid' => false, 'message' => 'Insufficient permissions'];
        }

        return $validation;
    }
}
?>