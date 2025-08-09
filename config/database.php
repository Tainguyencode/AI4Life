<?php
// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nguoibanai_nganhhoc'); // Changed by user

// Tạo kết nối database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// Tạo bảng users nếu chưa tồn tại
$createUsersTable = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Tạo bảng student_profiles để lưu thông tin sinh viên
$createStudentProfilesTable = "
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    interests TEXT,
    skills TEXT,
    math_score DECIMAL(3,1),
    literature_score DECIMAL(3,1),
    english_score DECIMAL(3,1),
    favorite_subject VARCHAR(100),
    career_orientation VARCHAR(255),
    habits TEXT,
    tech_interest INT,
    creativity INT,
    communication INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Tạo bảng ai_recommendations để lưu kết quả phân tích AI
$createAIRecommendationsTable = "
CREATE TABLE IF NOT EXISTS ai_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    major_name VARCHAR(255),
    university_name VARCHAR(255),
    confidence DECIMAL(3,2),
    reasoning TEXT,
    career_prospects TEXT,
    salary_range VARCHAR(100),
    recommendation_order INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Tạo bảng ai_learning_roadmaps để lưu lộ trình học tập từ AI
$createAILearningRoadmapsTable = "
CREATE TABLE IF NOT EXISTS ai_learning_roadmaps (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    profile_id INT NOT NULL,
    major_name VARCHAR(255) NOT NULL,
    roadmap_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES student_profiles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_major (user_id, profile_id, major_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    // Tạo bảng users
    $pdo->exec($createUsersTable);
    
    // Tạo bảng student_profiles
    $pdo->exec($createStudentProfilesTable);
    
    // Tạo bảng ai_recommendations
    $pdo->exec($createAIRecommendationsTable);
    
    // Tạo bảng ai_learning_roadmaps
    $pdo->exec($createAILearningRoadmapsTable);
    
    // Xóa các cột cũ nếu tồn tại (để tương thích với phiên bản mới)
    try {
        $pdo->exec("ALTER TABLE student_profiles DROP COLUMN IF EXISTS physics_score");
        $pdo->exec("ALTER TABLE student_profiles DROP COLUMN IF EXISTS chemistry_score");
        $pdo->exec("ALTER TABLE student_profiles DROP COLUMN IF EXISTS biology_score");
    } catch(PDOException $e) {
        // Bỏ qua lỗi nếu cột không tồn tại
    }

} catch(PDOException $e) {
    die("Lỗi tạo bảng: " . $e->getMessage());
}
?>
