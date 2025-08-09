<?php
// Hàm kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Hàm lấy thông tin user hiện tại
function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Hàm validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hàm hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Hàm verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Hàm sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Hàm redirect với message
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header('Location: ' . $url);
    exit();
}

// Hàm format date
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

// Hàm cập nhật thông tin user
function updateUserInfo($userId, $data) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE users 
        SET fullname = ?, phone = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    
    return $stmt->execute([
        $data['fullname'],
        $data['phone'],
        $userId
    ]);
}

// Hàm đổi mật khẩu
function changePassword($userId, $currentPassword, $newPassword) {
    global $pdo;
    
    // Kiểm tra mật khẩu hiện tại
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user || !verifyPassword($currentPassword, $user['password'])) {
        return false;
    }
    
    // Cập nhật mật khẩu mới
    $hashedPassword = hashPassword($newPassword);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashedPassword, $userId]);
}

// Hàm kiểm tra email tồn tại
function emailExists($email, $excludeUserId = null) {
    global $pdo;
    
    $sql = "SELECT id FROM users WHERE email = ?";
    $params = [$email];
    
    if ($excludeUserId) {
        $sql .= " AND id != ?";
        $params[] = $excludeUserId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch() !== false;
}

// Hàm lấy tất cả users
function getAllUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

// Hàm xóa user
function deleteUser($userId) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$userId]);
}

// ===== CÁC HÀM MỚI CHO STUDENT PROFILES =====

// Hàm lưu thông tin sinh viên
function saveStudentProfile($userId, $data) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO student_profiles (
            user_id, interests, skills, math_score, literature_score, 
            english_score, physics_score, chemistry_score, biology_score,
            favorite_subject, career_orientation, habits, tech_interest,
            creativity, communication
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    return $stmt->execute([
        $userId,
        $data['interests'],
        $data['skills'],
        $data['math_score'],
        $data['literature_score'],
        $data['english_score'],
        $data['physics_score'],
        $data['chemistry_score'],
        $data['biology_score'],
        $data['favorite_subject'],
        $data['career_orientation'],
        $data['habits'],
        $data['tech_interest'],
        $data['creativity'],
        $data['communication']
    ]);
}

// Hàm lấy thông tin sinh viên của user
function getStudentProfile($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM student_profiles 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Hàm lấy tất cả profiles của user
function getAllStudentProfiles($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM student_profiles 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// Hàm lưu kết quả AI recommendations
function saveAIRecommendations($userId, $profileId, $recommendations) {
    global $pdo;
    
    // Xóa recommendations cũ nếu có
    $stmt = $pdo->prepare("DELETE FROM ai_recommendations WHERE user_id = ? AND profile_id = ?");
    $stmt->execute([$userId, $profileId]);
    
    // Lưu recommendations mới
    $stmt = $pdo->prepare("
        INSERT INTO ai_recommendations (
            user_id, profile_id, major_name, university_name, confidence,
            reasoning, career_prospects, salary_range, recommendation_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($recommendations as $index => $rec) {
        $stmt->execute([
            $userId,
            $profileId,
            $rec['major'],
            $rec['university'],
            $rec['confidence'],
            $rec['reasoning'],
            $rec['career_prospects'] ?? '',
            $rec['salary_range'] ?? '',
            $index + 1
        ]);
    }
    
    return true;
}

// Hàm lấy AI recommendations của user
function getAIRecommendations($userId, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT ar.*, sp.created_at as profile_date
        FROM ai_recommendations ar
        JOIN student_profiles sp ON ar.profile_id = sp.id
        WHERE ar.user_id = ?
        ORDER BY ar.created_at DESC
        LIMIT ?
    ");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

// Hàm lấy thống kê profiles
function getProfileStats($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_profiles,
            MAX(created_at) as last_analysis
        FROM student_profiles 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Hàm xóa profile
function deleteStudentProfile($profileId, $userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        DELETE FROM student_profiles 
        WHERE id = ? AND user_id = ?
    ");
    return $stmt->execute([$profileId, $userId]);
}
?>
