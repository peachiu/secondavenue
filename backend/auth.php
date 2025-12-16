<?php
// backend/auth.php
session_start();
require_once __DIR__ . '/db.php';

function registerUser($name, $email, $password, $role = 'community', $location = '') {
    global $pdo;
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Professional users might default to verified=0, community=1 (or 0 if email verification needed)
    // Per requirements: Professionals need verification. Community can be auto-verified for now.
    $isVerified = ($role === 'professional') ? 0 : 1; 

    $sql = "INSERT INTO users (name, email, password, role, location, is_verified) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$name, $email, $hashedPassword, $role, $location, $isVerified]);
        return ['success' => true, 'message' => 'User registered successfully.'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function loginUser($email, $password) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_verified'] = $user['is_verified'];
        return ['success' => true, 'message' => 'Login successful.'];
    } else {
        return ['success' => false, 'message' => 'Invalid credentials.'];
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role'],
        'verified' => $_SESSION['user_verified']
    ];
}

function logoutUser() {
    session_destroy();
}
?>
