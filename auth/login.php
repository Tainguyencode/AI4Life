<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($email) || empty($password)) {
        redirectWithMessage('../index.php', 'Vui lòng nhập đầy đủ thông tin', 'error');
    }
    
    if (!validateEmail($email)) {
        redirectWithMessage('../index.php', 'Email không hợp lệ', 'error');
    }
    
    try {
        // Kiểm tra user trong database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            // Đăng nhập thành công
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_fullname'] = $user['fullname'];
            
            redirectWithMessage('../dashboard.php', 'Đăng nhập thành công!', 'success');
        } else {
            redirectWithMessage('../index.php', 'Email hoặc mật khẩu không đúng', 'error');
        }
    } catch (PDOException $e) {
        redirectWithMessage('../index.php', 'Lỗi hệ thống, vui lòng thử lại sau', 'error');
    }
} else {
    // Nếu không phải POST request, redirect về trang chủ
    header('Location: ../index.php');
    exit();
}
?>
