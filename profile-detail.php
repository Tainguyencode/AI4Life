<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/ai_service.php';

if (!isLoggedIn()) {
    redirectWithMessage('index.php', 'Vui lòng đăng nhập', 'error');
}

$user = getCurrentUser();
$profileId = (int)($_GET['id'] ?? 0);

if (!$profileId) {
    redirectWithMessage('my-profiles.php', 'Không tìm thấy thông tin', 'error');
}

// Lấy dữ liệu
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT sp.*, u.fullname FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.id = ? AND sp.user_id = ?");
$stmt->execute([$profileId, $user['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    redirectWithMessage('my-profiles.php', 'Không tìm thấy thông tin', 'error');
}

$stmt = $pdo->prepare("SELECT * FROM ai_recommendations WHERE profile_id = ? AND user_id = ? ORDER BY recommendation_order ASC");
$stmt->execute([$profileId, $user['id']]);
$recommendations = $stmt->fetchAll();

// Xử lý roadmap
$learningRoadmap = null;
if (isset($_POST['generate_roadmap'])) {
    $selectedMajor = $_POST['selected_major'] ?? '';
    if (!empty($selectedMajor)) {
        $aiService = new AIService();
        $roadmapPrompt = "Tạo lộ trình học tập cho ngành '{$selectedMajor}' tại FPT Polytechnic. Trả về JSON với: focus_subjects, skills_to_improve, semester_roadmap, career_opportunities";
        $result = $aiService->sendRequest($roadmapPrompt);
        
        if ($result['success']) {
            preg_match('/\{.*\}/s', $result['content'], $matches);
            if (!empty($matches)) {
                $roadmapJson = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $learningRoadmap = $roadmapJson;
                }
            }
        }
        
        if (!$learningRoadmap) {
            $learningRoadmap = generateFallbackRoadmap($selectedMajor);
        }
    }
}

function generateFallbackRoadmap($major) {
    return [
        'major' => $major,
        'focus_subjects' => [
            ['subject' => 'Không có thông tin', 'reason' => 'Không thể tạo lộ trình học tập', 'difficulty' => 'N/A']
        ],
        'skills_to_improve' => [
            ['skill' => 'Không có thông tin', 'current_level' => 'N/A', 'target_level' => 'N/A', 'improvement_method' => 'Không có thông tin']
        ],
        'semester_roadmap' => [
            ['semester' => 'Không có thông tin', 'subjects' => ['Không có thông tin'], 'focus' => 'Không thể tạo lộ trình']
        ],
        'career_opportunities' => [
            ['position' => 'Không có thông tin', 'company_type' => 'N/A', 'salary_range' => 'N/A', 'requirements' => 'Không có thông tin']
        ]
    ];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết phân tích - NguoiBanAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">
                        <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                        Chi tiết phân tích #<?php echo $profileId; ?>
                    </h1>
                </div>
                <nav class="flex space-x-4">
                    <a href="my-profiles.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i>Quay lại
                    </a>
                    <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-home mr-1"></i>Trang chủ
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Profile Information -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-user text-blue-600 mr-2"></i>
                    Thông tin phân tích
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Ngày phân tích: <?php echo formatDate($profile['created_at']); ?>
                </p>
            </div>
            
            <div class="p-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-md font-medium text-gray-900 mb-3">
                            <i class="fas fa-info-circle text-green-600 mr-2"></i>
                            Thông tin cơ bản
                        </h3>
                        <div class="space-y-2 text-sm">
                            <p><strong>Sở thích:</strong> <?php echo htmlspecialchars($profile['interests'] ?? 'Chưa cung cấp'); ?></p>
                            <p><strong>Kỹ năng:</strong> <?php echo htmlspecialchars($profile['skills'] ?? 'Chưa cung cấp'); ?></p>
                            <p><strong>Môn yêu thích:</strong> <?php echo htmlspecialchars($profile['favorite_subject'] ?? 'Chưa cung cấp'); ?></p>
                            <p><strong>Định hướng:</strong> <?php echo htmlspecialchars($profile['career_orientation'] ?? 'Chưa cung cấp'); ?></p>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-md font-medium text-gray-900 mb-3">
                            <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                            Điểm số các môn học
                        </h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <p><strong>Toán:</strong> <?php echo $profile['math_score'] ?? 'N/A'; ?></p>
                            <p><strong>Văn:</strong> <?php echo $profile['literature_score'] ?? 'N/A'; ?></p>
                            <p><strong>Anh:</strong> <?php echo $profile['english_score'] ?? 'N/A'; ?></p>
                            <p><strong>Lý:</strong> <?php echo $profile['physics_score'] ?? 'N/A'; ?></p>
                            <p><strong>Hóa:</strong> <?php echo $profile['chemistry_score'] ?? 'N/A'; ?></p>
                            <p><strong>Sinh:</strong> <?php echo $profile['biology_score'] ?? 'N/A'; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Skill Assessment -->
                <div class="mt-6">
                    <h3 class="text-md font-medium text-gray-900 mb-3">
                        <i class="fas fa-star text-yellow-600 mr-2"></i>
                        Đánh giá mức độ
                    </h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-blue-800">Công nghệ</span>
                                <span class="text-lg font-bold text-blue-600"><?php echo $profile['tech_interest'] ?? 'N/A'; ?>/10</span>
                            </div>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-green-800">Sáng tạo</span>
                                <span class="text-lg font-bold text-green-600"><?php echo $profile['creativity'] ?? 'N/A'; ?>/10</span>
                            </div>
                        </div>
                        <div class="bg-purple-50 p-3 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-purple-800">Giao tiếp</span>
                                <span class="text-lg font-bold text-purple-600"><?php echo $profile['communication'] ?? 'N/A'; ?>/10</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Recommendations -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-robot text-green-600 mr-2"></i>
                    Đề xuất ngành học từ AI
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Dựa trên thông tin của bạn, AI đã đề xuất các ngành học phù hợp tại FPT Polytechnic
                </p>
            </div>
            
            <div class="p-6">
                <?php if (!empty($recommendations)): ?>
                    <div class="space-y-6">
                        <?php foreach ($recommendations as $index => $rec): ?>
                            <div class="border border-gray-200 rounded-lg p-6 <?php echo $index === 0 ? 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200' : 'bg-gray-50'; ?>">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <span class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">
                                            <?php echo $index + 1; ?>
                                        </span>
                                        <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($rec['major']); ?></h3>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <?php echo round($rec['confidence'] * 100); ?>% phù hợp
                                        </span>
                                        <?php if ($index === 0): ?>
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                                <i class="fas fa-crown mr-1"></i>Đề xuất hàng đầu
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                                            Lý do đề xuất
                                        </h4>
                                        <p class="text-gray-700 text-sm leading-relaxed">
                                            <?php echo htmlspecialchars($rec['reasoning']); ?>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-briefcase text-blue-600 mr-2"></i>
                                            Triển vọng nghề nghiệp
                                        </h4>
                                        <p class="text-gray-700 text-sm leading-relaxed">
                                            <?php echo htmlspecialchars($rec['career_prospects']); ?>
                                        </p>
                                        <p class="text-green-600 font-medium text-sm mt-2">
                                            <i class="fas fa-money-bill-wave mr-1"></i>
                                            Mức lương: <?php echo htmlspecialchars($rec['salary_range']); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Learning Roadmap Button -->
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <form method="POST" class="flex items-center justify-between">
                                        <input type="hidden" name="selected_major" value="<?php echo htmlspecialchars($rec['major']); ?>">
                                        <button type="submit" name="generate_roadmap" 
                                                class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-2 rounded-lg hover:from-purple-700 hover:to-pink-700 transition-colors">
                                            <i class="fas fa-road mr-2"></i>
                                            Gợi ý lộ trình học
                                        </button>
                                        <span class="text-xs text-gray-500">
                                            Nhấn để xem chi tiết lộ trình học tập cho ngành này
                                        </span>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-4xl mb-4"></i>
                        <p class="text-gray-600">Không có đề xuất nào được tìm thấy</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Learning Roadmap -->
        <?php if ($learningRoadmap): ?>
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-road text-purple-600 mr-2"></i>
                        Lộ trình học tập - <?php echo htmlspecialchars($learningRoadmap['major']); ?>
                    </h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Chi tiết các môn học cần tập trung và kỹ năng cần cải thiện
                    </p>
                </div>
                
                <div class="p-6">
                    <!-- Focus Subjects -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-book text-blue-600 mr-2"></i>
                            Các môn học cần tập trung
                        </h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <?php foreach ($learningRoadmap['focus_subjects'] as $subject): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($subject['subject']); ?></h4>
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                                            Độ khó: <?php echo htmlspecialchars($subject['difficulty']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($subject['reason']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Skills to Improve -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-chart-line text-green-600 mr-2"></i>
                            Kỹ năng cần cải thiện
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($learningRoadmap['skills_to_improve'] as $skill): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($skill['skill']); ?></h4>
                                        <div class="flex items-center space-x-2">
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">
                                                Hiện tại: <?php echo htmlspecialchars($skill['current_level']); ?>
                                            </span>
                                            <i class="fas fa-arrow-right text-gray-400"></i>
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                                Mục tiêu: <?php echo htmlspecialchars($skill['target_level']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <strong>Cách cải thiện:</strong> <?php echo htmlspecialchars($skill['improvement_method']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Semester Roadmap -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-calendar-alt text-purple-600 mr-2"></i>
                            Lộ trình theo kỳ học
                        </h3>
                        <div class="space-y-4">
                            <?php foreach ($learningRoadmap['semester_roadmap'] as $semester): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($semester['semester']); ?></h4>
                                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                            <?php echo count($semester['subjects']); ?> môn học
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-600 mb-2">
                                            <strong>Môn học:</strong>
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($semester['subjects'] as $subject): ?>
                                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                                                    <?php echo htmlspecialchars($subject); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <strong>Trọng tâm:</strong> <?php echo htmlspecialchars($semester['focus']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Career Opportunities -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-briefcase text-orange-600 mr-2"></i>
                            Cơ hội nghề nghiệp
                        </h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <?php foreach ($learningRoadmap['career_opportunities'] as $career): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($career['position']); ?></h4>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                            <?php echo htmlspecialchars($career['company_type']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <strong>Mức lương:</strong> <?php echo htmlspecialchars($career['salary_range']); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <strong>Yêu cầu:</strong> <?php echo htmlspecialchars($career['requirements']); ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                <p>&copy; 2024 NguoiBanAI - Hệ thống tư vấn hướng nghiệp. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </footer>
</body>
</html>
