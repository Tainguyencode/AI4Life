<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/ai_service.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
$apiKey = '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';

// Xử lý form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'interests' => sanitizeInput($_POST['interests'] ?? ''),
        'skills' => sanitizeInput($_POST['skills'] ?? ''),
        'math_score' => sanitizeInput($_POST['math_score'] ?? ''),
        'literature_score' => sanitizeInput($_POST['literature_score'] ?? ''),
        'english_score' => sanitizeInput($_POST['english_score'] ?? ''),
        'physics_score' => sanitizeInput($_POST['physics_score'] ?? ''),
        'chemistry_score' => sanitizeInput($_POST['chemistry_score'] ?? ''),
        'biology_score' => sanitizeInput($_POST['biology_score'] ?? ''),
        'favorite_subject' => sanitizeInput($_POST['favorite_subject'] ?? ''),
        'career_orientation' => sanitizeInput($_POST['career_orientation'] ?? ''),
        'habits' => sanitizeInput($_POST['habits'] ?? ''),
        'tech_interest' => sanitizeInput($_POST['tech_interest'] ?? ''),
        'creativity' => sanitizeInput($_POST['creativity'] ?? ''),
        'communication' => sanitizeInput($_POST['communication'] ?? ''),
        'academic_level' => 'THPT'
    ];
    
    try {
        // Lưu thông tin sinh viên vào database
        if (saveStudentProfile($user['id'], $userData)) {
            // Lấy ID của profile vừa tạo
            $profileId = $pdo->lastInsertId();
            
            // Gọi AI để phân tích
            $aiResult = analyzeMajorWithAI($userData, $apiKey);
            
            // Lưu kết quả AI vào database nếu thành công
            if ($aiResult['success']) {
                saveAIRecommendations($user['id'], $profileId, $aiResult['recommendations']);
            }
            
            // Lưu kết quả vào session để hiển thị
            $_SESSION['ai_analysis_result'] = $aiResult;
            $_SESSION['user_analysis_data'] = $userData;
            $_SESSION['profile_id'] = $profileId;
            
            redirectWithMessage('ai-analysis.php?result=1', 'Phân tích AI thành công!', 'success');
        } else {
            redirectWithMessage('ai-analysis.php', 'Có lỗi xảy ra khi lưu thông tin!', 'error');
        }
    } catch (Exception $e) {
        redirectWithMessage('ai-analysis.php', 'Có lỗi xảy ra: ' . $e->getMessage(), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phân tích AI - NguoiBanAI</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <i class="fas fa-graduation-cap text-blue-600 text-2xl mr-3"></i>
                    <span class="text-xl font-bold text-gray-800">NguoiBanAI</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Xin chào, <?php echo htmlspecialchars($user['fullname']); ?></span>
                    <a href="dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors">
                        <i class="fas fa-home mr-2"></i>Dashboard
                    </a>
                    <a href="index.php?logout=1" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                <i class="fas fa-robot text-blue-600 mr-3"></i>
                Phân tích AI - Khám phá ngành học tại FPT Polytechnic
            </h1>
            <p class="text-lg text-gray-600">Hãy cung cấp thông tin chi tiết để AI có thể đưa ra đề xuất chính xác nhất về ngành học tại Cao đẳng FPT Polytechnic</p>
        </div>

        <!-- Hiển thị thông báo -->
        <?php if (isset($_SESSION['message'])): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
            <div class="flex items-center">
                <i class="fas <?php echo $_SESSION['message_type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
                <?php echo htmlspecialchars($_SESSION['message']); ?>
            </div>
        </div>
        <?php 
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        endif; 
        ?>

        <?php if (isset($_GET['result']) && isset($_SESSION['ai_analysis_result'])): ?>
        <!-- Kết quả phân tích -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                Kết quả phân tích AI
            </h2>
            
            <?php 
            $result = $_SESSION['ai_analysis_result'];
            $userData = $_SESSION['user_analysis_data'];
            ?>
            
            <div class="grid md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Thông tin đã cung cấp:</h3>
                    <div class="bg-gray-50 p-4 rounded">
                        <p><strong>Sở thích:</strong> <?php echo htmlspecialchars($userData['interests']); ?></p>
                        <p><strong>Kỹ năng:</strong> <?php echo htmlspecialchars($userData['skills']); ?></p>
                        <p><strong>Môn yêu thích:</strong> <?php echo htmlspecialchars($userData['favorite_subject']); ?></p>
                        <p><strong>Định hướng:</strong> <?php echo htmlspecialchars($userData['career_orientation']); ?></p>
                    </div>
                </div>
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Điểm số các môn:</h3>
                    <div class="bg-gray-50 p-4 rounded">
                        <p><strong>Toán:</strong> <?php echo htmlspecialchars($userData['math_score']); ?></p>
                        <p><strong>Văn:</strong> <?php echo htmlspecialchars($userData['literature_score']); ?></p>
                        <p><strong>Anh:</strong> <?php echo htmlspecialchars($userData['english_score']); ?></p>
                    </div>
                </div>
            </div>

            <h3 class="text-xl font-bold text-gray-800 mb-4">Đề xuất ngành học tại FPT Polytechnic:</h3>
            
            <?php if ($result['success']): ?>
                <div class="grid md:grid-cols-3 gap-6">
                    <?php foreach ($result['recommendations'] as $index => $rec): ?>
                    <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-lg font-bold text-blue-600"><?php echo ($index + 1) . ". " . htmlspecialchars($rec['major']); ?></h4>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
                                <?php echo round($rec['confidence'] * 100); ?>%
                            </span>
                        </div>
                        <p class="text-gray-600 mb-2"><strong>Trường:</strong> <?php echo htmlspecialchars($rec['university']); ?></p>
                        <p class="text-gray-700 mb-3"><strong>Lý do:</strong> <?php echo htmlspecialchars($rec['reasoning']); ?></p>
                        
                        <?php if (isset($rec['career_prospects'])): ?>
                        <p class="text-gray-600 mb-2"><strong>Triển vọng:</strong> <?php echo htmlspecialchars($rec['career_prospects']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (isset($rec['salary_range'])): ?>
                        <p class="text-gray-600"><strong>Mức lương:</strong> <?php echo htmlspecialchars($rec['salary_range']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <p class="text-yellow-800"><strong>⚠️ Lưu ý:</strong> <?php echo htmlspecialchars($result['error']); ?></p>
                </div>
                
                <!-- Debug info -->
                <?php if (isset($result['raw_content'])): ?>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Debug - Raw AI Response:</h4>
                    <pre class="text-xs text-gray-600 overflow-x-auto"><?php echo htmlspecialchars(substr($result['raw_content'], 0, 500)); ?></pre>
                </div>
                <?php endif; ?>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <?php foreach ($result['recommendations'] as $index => $rec): ?>
                    <div class="border border-gray-200 rounded-lg p-6">
                        <h4 class="text-lg font-bold text-blue-600 mb-2"><?php echo ($index + 1) . ". " . htmlspecialchars($rec['major']); ?></h4>
                        <p class="text-gray-600 mb-2"><strong>Trường:</strong> <?php echo htmlspecialchars($rec['university']); ?></p>
                        <p class="text-gray-700 mb-2"><strong>Lý do:</strong> <?php echo htmlspecialchars($rec['reasoning']); ?></p>
                        <p class="text-gray-600 mb-2"><strong>Triển vọng:</strong> <?php echo htmlspecialchars($rec['career_prospects']); ?></p>
                        <p class="text-gray-600"><strong>Mức lương:</strong> <?php echo htmlspecialchars($rec['salary_range']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-6 text-center space-x-4">
                <a href="ai-analysis.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-redo mr-2"></i>Phân tích lại
                </a>
                <a href="my-profiles.php" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-history mr-2"></i>Xem lịch sử
                </a>
            </div>
        </div>
        
        <?php else: ?>
        <!-- Form nhập thông tin -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-edit text-blue-600 mr-2"></i>
                Thông tin cá nhân
            </h2>
            
            <form method="POST" class="space-y-6">
                <!-- Thông tin cơ bản -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">
                            <i class="fas fa-heart mr-2 text-red-500"></i>Sở thích
                        </label>
                        <textarea name="interests" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Ví dụ: Công nghệ, âm nhạc, thể thao, đọc sách..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">
                            <i class="fas fa-star mr-2 text-yellow-500"></i>Kỹ năng và tố chất
                        </label>
                        <textarea name="skills" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Ví dụ: Tư duy logic, sáng tạo, giao tiếp tốt..."></textarea>
                    </div>
                </div>

                <!-- Điểm số các môn học -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-chart-line mr-2 text-green-500"></i>Điểm số các môn học
                    </h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Toán</label>
                            <input type="number" name="math_score" min="0" max="10" step="0.1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0-10">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Văn</label>
                            <input type="number" name="literature_score" min="0" max="10" step="0.1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0-10">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Anh</label>
                            <input type="number" name="english_score" min="0" max="10" step="0.1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0-10">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Lý</label>
                            <input type="number" name="physics_score" min="0" max="10" step="0.1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0-10">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Hóa</label>
                            <input type="number" name="chemistry_score" min="0" max="10" step="0.1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0-10">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Sinh</label>
                            <input type="number" name="biology_score" min="0" max="10" step="0.1" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0-10">
                        </div>
                    </div>
                </div>

                <!-- Môn yêu thích và định hướng -->
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">
                            <i class="fas fa-book mr-2 text-purple-500"></i>Môn học yêu thích nhất
                        </label>
                        <input type="text" name="favorite_subject" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ví dụ: Toán, Văn, Lý, Hóa, Sinh, Anh...">
                    </div>
                    
                    <div>
                        <label class="block text-gray-700 font-bold mb-2">
                            <i class="fas fa-briefcase mr-2 text-blue-500"></i>Định hướng nghề nghiệp
                        </label>
                        <input type="text" name="career_orientation" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Ví dụ: Lập trình viên, bác sĩ, giáo viên...">
                    </div>
                </div>

                <!-- Thói quen và đánh giá -->
                <div>
                    <label class="block text-gray-700 font-bold mb-2">
                        <i class="fas fa-clock mr-2 text-orange-500"></i>Thói quen hàng ngày
                    </label>
                    <textarea name="habits" rows="2" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Ví dụ: Thích đọc sách, chơi game, thể thao..."></textarea>
                </div>

                <!-- Mức độ đánh giá -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-chart-bar mr-2 text-indigo-500"></i>Đánh giá mức độ (1-10)
                    </h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Yêu thích công nghệ</label>
                            <input type="number" name="tech_interest" min="1" max="10" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="1-10">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Khả năng sáng tạo</label>
                            <input type="number" name="creativity" min="1" max="10" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="1-10">
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Kỹ năng giao tiếp</label>
                            <input type="number" name="communication" min="1" max="10" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="1-10">
                        </div>
                    </div>
                </div>

                <!-- Nút submit -->
                <div class="text-center pt-6">
                    <button type="submit" 
                            class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all transform hover:scale-105">
                        <i class="fas fa-robot mr-2"></i>
                        <span class="text-lg font-bold">Khám phá ngành học tại FPT Polytechnic!</span>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
