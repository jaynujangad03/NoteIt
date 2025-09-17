<?php
session_start();

// Database connection
require_once 'config.php';

// If user is already logged in, redirect to admin
if (isset($_SESSION['user_id'])) {
    header('Location: admin.php');
    exit();
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters long.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $message = 'Username or email already exists.';
                $messageType = 'error';
            } else {
                // Create new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashedPassword]);
                
                // Get the new user ID
                $userId = $pdo->lastInsertId();
                
                // Set session
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                
                $message = 'Registration successful! Redirecting...';
                $messageType = 'success';
                
                // Redirect after a short delay
                echo '<script>setTimeout(() => { window.location.href = "admin.php"; }, 1500);</script>';
            }
        } catch (PDOException $e) {
            $message = 'Registration failed. Please try again.';
            $messageType = 'error';
        }
    }
}
?>
