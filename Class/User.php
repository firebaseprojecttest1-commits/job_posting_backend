<?php
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        $sql = "SELECT u.id, u.email, u.password, u.role, i.first_name, i.last_name, i.middle_name 
                FROM user u 
                LEFT JOIN info i ON u.id = i.user_id 
                WHERE u.email = ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if ($password === $row['password']) {
                return json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'user_id' => $row['id'],
                    'role' => $row['role'],
                    'first_name' => $row['first_name'] ?? 'User',
                    'last_name' => $row['last_name'] ?? ''
                ]);
            }
        }

        return json_encode([
            'success' => false,
            'message' => 'Invalid email or password'
        ]);
    }

    public function register($email, $password, $role, $firstname, $lastname, $middlename, $birthdate, $gender, $address, $contact) {
        $checkSql = "SELECT id FROM user WHERE email = ?";
        $checkStmt = $this->conn->prepare($checkSql);
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return json_encode(['success' => false, 'message' => 'Email already registered.']);
        }

        $userSql = "INSERT INTO user (email, password, role) VALUES (?, ?, ?)";
        $userStmt = $this->conn->prepare($userSql);
        $userStmt->bind_param("sss", $email, $password, $role);

        if ($userStmt->execute()) {
            $userId = $this->conn->insert_id;

            $infoSql = "INSERT INTO info (user_id, last_name, first_name, middle_name, birth_date, gender, address, contact_no) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $infoStmt = $this->conn->prepare($infoSql);
            $infoStmt->bind_param("isssssss", $userId, $lastname, $firstname, $middlename, $birthdate, $gender, $address, $contact);
            $infoStmt->execute();

            return json_encode(['success' => true, 'message' => 'Registration successful!']);
        }

        return json_encode(['success' => false, 'message' => 'Registration failed.']);
    }

    public function getUserProfile($userId) {
        $sql = "SELECT u.id, u.email, u.role, i.first_name, i.last_name, i.middle_name, i.birth_date, i.gender, i.address, i.contact_no 
                FROM user u 
                LEFT JOIN info i ON u.id = i.user_id 
                WHERE u.id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return json_encode(['success' => true, 'data' => $row]);
        }
        return json_encode(['success' => false, 'message' => 'User profile not found.']);
    }

    public function updateProfile($userId, $fn, $ln, $mn, $bdate, $gender, $contact, $address) {
        $sql = "UPDATE info SET first_name = ?, last_name = ?, middle_name = ?, birth_date = ?, gender = ?, contact_no = ?, address = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssi", $fn, $ln, $mn, $bdate, $gender, $contact, $address, $userId);

        if ($stmt->execute()) {
            return json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        }
        return json_encode(['success' => false, 'message' => 'Failed to update profile.']);
    }
}
?>