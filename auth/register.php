<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitizeInput($_POST['fullname'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        redirectWithMessage('../index.php', 'Vui lòng nhập đầy đủ thông tin', 'error');
    }
    
    if (!validateEmail($email)) {
        redirectWithMessage('../index.php', 'Email không hợp lệ', 'error');
    }
    
    if (strlen($password) < 6) {
        redirectWithMessage('../index.php', 'Mật khẩu phải có ít nhất 6 ký tự', 'error');
    }
    
    if ($password !== $confirm_password) {
        redirectWithMessage('../index.php', 'Mật khẩu xác nhận không khớp', 'error');
    }
    
    try {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            redirectWithMessage('../index.php', 'Email đã được sử dụng', 'error');
        }
        
        // Hash password
        $hashedPassword = hashPassword($password);
        
        // Tạo user mới
        $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$fullname, $email, $hashedPassword]);
        
        $userId = $pdo->lastInsertId();
        
        // Tạo session cho user mới
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_fullname'] = $fullname;
        
        redirectWithMessage('../dashboard.php', 'Đăng ký thành công! Chào mừng bạn đến với NguoiBanAI', 'success');
        
    } catch (PDOException $e) {
        redirectWithMessage('../index.php', 'Lỗi hệ thống, vui lòng thử lại sau', 'error');
    }
} else {
    // Nếu không phải POST request, redirect về trang chủ
    header('Location: ../index.php');
    exit();
}
?>

