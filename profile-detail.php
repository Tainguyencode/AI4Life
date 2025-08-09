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

// Lấy dữ liệu với error handling
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT sp.*, u.fullname FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.id = ? AND sp.user_id = ?");
    $stmt->execute([$profileId, $user['id']]);
    $profile = $stmt->fetch();

    if (!$profile) {
        redirectWithMessage('my-profiles.php', 'Không tìm thấy thông tin hoặc bạn không có quyền truy cập', 'error');
    }

    $stmt = $pdo->prepare("SELECT * FROM ai_recommendations WHERE profile_id = ? AND user_id = ? ORDER BY recommendation_order ASC");
    $stmt->execute([$profileId, $user['id']]);
    $recommendations = $stmt->fetchAll();
    


} catch (PDOException $e) {
    error_log("Database error in profile-detail.php: " . $e->getMessage());
    redirectWithMessage('my-profiles.php', 'Có lỗi xảy ra khi tải dữ liệu', 'error');
}

// Xử lý roadmap với error handling và cache
$learningRoadmap = null;
if (isset($_POST['generate_roadmap'])) {
    $selectedMajor = $_POST['selected_major'] ?? '';
    if (!empty($selectedMajor)) {
        try {
            // Kiểm tra xem đã có roadmap trong database chưa
            $stmt = $pdo->prepare("SELECT roadmap_data FROM ai_learning_roadmaps WHERE user_id = ? AND profile_id = ? AND major_name = ?");
            $stmt->execute([$user['id'], $profileId, $selectedMajor]);
            $existingRoadmap = $stmt->fetch();
            
            if ($existingRoadmap) {
                // Sử dụng roadmap đã lưu
                $learningRoadmap = json_decode($existingRoadmap['roadmap_data'], true);
                error_log("Using cached roadmap for major: $selectedMajor");
            } else {
                // Tạo roadmap mới từ AI
                $aiService = new AIService();
                $roadmapPrompt = "Tạo lộ trình học tập chi tiết cho ngành '{$selectedMajor}' tại FPT Polytechnic. Trả về JSON với cấu trúc chính xác: {\"focus_subjects\": [{\"subject\": \"Tên môn học\", \"reason\": \"Lý do cần tập trung\", \"difficulty\": \"Độ khó\"}], \"skills_to_improve\": [{\"skill\": \"Tên kỹ năng\", \"current_level\": \"Mức độ hiện tại\", \"target_level\": \"Mức độ mục tiêu\", \"improvement_method\": \"Cách cải thiện\"}], \"semester_roadmap\": [{\"semester\": \"Kỳ học\", \"subjects\": [\"Môn học\"], \"focus\": \"Trọng tâm\"}], \"career_opportunities\": [{\"position\": \"Vị trí\", \"company_type\": \"Loại công ty\", \"salary_range\": \"Mức lương\", \"requirements\": \"Yêu cầu\"}]}. Chỉ trả về JSON, không có text thêm.";
                
                $result = $aiService->sendRequest($roadmapPrompt);
                
                if ($result['success']) {
                    // Thử parse JSON từ response
                    preg_match('/\{.*\}/s', $result['content'], $matches);
                    if (!empty($matches)) {
                        $roadmapJson = json_decode($matches[0], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $learningRoadmap = $roadmapJson;
                        }
                    }
                    
                    // Nếu không parse được JSON, thử parse toàn bộ content
                    if (!$learningRoadmap) {
                        $roadmapJson = json_decode($result['content'], true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $learningRoadmap = $roadmapJson;
                        }
                    }
                    
                    // Log để debug
                    error_log("AI Roadmap Response: " . $result['content']);
                }
                
                // Fallback nếu AI không hoạt động
                if (!$learningRoadmap) {
                    $learningRoadmap = generateFallbackRoadmap($selectedMajor);
                }
                
                // Lưu roadmap vào database nếu tạo thành công
                if ($learningRoadmap) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO ai_learning_roadmaps (user_id, profile_id, major_name, roadmap_data) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$user['id'], $profileId, $selectedMajor, json_encode($learningRoadmap)]);
                        error_log("Saved roadmap to database for major: $selectedMajor");
                    } catch (PDOException $e) {
                        error_log("Error saving roadmap to database: " . $e->getMessage());
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("AI Service error in profile-detail.php: " . $e->getMessage());
            $learningRoadmap = generateFallbackRoadmap($selectedMajor);
        }
    }
}

function generateFallbackRoadmap($major) {
    $roadmaps = [
        'Ứng dụng phần mềm' => [
            'major' => 'Ứng dụng phần mềm',
            'focus_subjects' => [
                ['subject' => 'Lập trình Java', 'reason' => 'Nền tảng phát triển ứng dụng', 'difficulty' => '8/10'],
                ['subject' => 'Cơ sở dữ liệu', 'reason' => 'Quản lý dữ liệu hiệu quả', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Tư duy logic', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Luyện giải bài tập lập trình']
            ],
            'semester_roadmap' => [
                ['semester' => 'Kỳ 1-2', 'subjects' => ['Tin học cơ sở', 'Lập trình C++'], 'focus' => 'Nền tảng lập trình'],
                ['semester' => 'Kỳ 3-4', 'subjects' => ['Lập trình Java', 'Cơ sở dữ liệu'], 'focus' => 'Phát triển ứng dụng']
            ],
            'career_opportunities' => [
                ['position' => 'Lập trình viên Java', 'company_type' => 'Công ty phần mềm', 'salary_range' => '15-30 triệu VNĐ/tháng', 'requirements' => 'Java, Spring Framework']
            ]
        ],
        'Thiết kế đồ họa' => [
            'major' => 'Thiết kế đồ họa',
            'focus_subjects' => [
                ['subject' => 'Thiết kế đồ họa cơ bản', 'reason' => 'Nền tảng về màu sắc và bố cục', 'difficulty' => '6/10'],
                ['subject' => 'Photoshop nâng cao', 'reason' => 'Công cụ chính cho thiết kế', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Khả năng sáng tạo', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Tham khảo portfolio, thực hành thiết kế']
            ],
            'semester_roadmap' => [
                ['semester' => 'Kỳ 1-2', 'subjects' => ['Mỹ thuật cơ bản', 'Photoshop cơ bản'], 'focus' => 'Xây dựng nền tảng mỹ thuật'],
                ['semester' => 'Kỳ 3-4', 'subjects' => ['Illustrator', 'Thiết kế UI/UX'], 'focus' => 'Phát triển kỹ năng chuyên nghiệp']
            ],
            'career_opportunities' => [
                ['position' => 'Graphic Designer', 'company_type' => 'Agency', 'salary_range' => '10-25 triệu VNĐ/tháng', 'requirements' => 'Thành thạo Photoshop, Illustrator']
            ]
        ],
        'Quản trị kinh doanh' => [
            'major' => 'Quản trị kinh doanh',
            'focus_subjects' => [
                ['subject' => 'Marketing cơ bản', 'reason' => 'Hiểu về thị trường và khách hàng', 'difficulty' => '6/10'],
                ['subject' => 'Quản lý dự án', 'reason' => 'Kỹ năng lãnh đạo và tổ chức', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Kỹ năng giao tiếp', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Tham gia thuyết trình, networking']
            ],
            'semester_roadmap' => [
                ['semester' => 'Kỳ 1-2', 'subjects' => ['Kinh tế vi mô', 'Marketing cơ bản'], 'focus' => 'Xây dựng nền tảng kinh doanh'],
                ['semester' => 'Kỳ 3-4', 'subjects' => ['Digital Marketing', 'Quản lý dự án'], 'focus' => 'Phát triển kỹ năng kinh doanh số']
            ],
            'career_opportunities' => [
                ['position' => 'Marketing Specialist', 'company_type' => 'Công ty marketing', 'salary_range' => '8-20 triệu VNĐ/tháng', 'requirements' => 'Digital marketing, kinh nghiệm thực tế']
            ]
        ]
    ];
    
    return $roadmaps[$major] ?? $roadmaps['Ứng dụng phần mềm'];
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
                    <a href="infographic.php?id=<?php echo $profileId; ?>" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-chart-pie mr-1"></i>Infographic
                    </a>
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
                                        <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($rec['major_name'] ?? 'Ngành học không xác định'); ?></h3>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <?php echo round(($rec['confidence'] ?? 0.7) * 100); ?>% phù hợp
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
                                            <?php echo htmlspecialchars($rec['reasoning'] ?? 'Không có thông tin'); ?>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-briefcase text-blue-600 mr-2"></i>
                                            Triển vọng nghề nghiệp
                                        </h4>
                                        <p class="text-gray-700 text-sm leading-relaxed">
                                            <?php echo htmlspecialchars($rec['career_prospects'] ?? 'Không có thông tin'); ?>
                                        </p>
                                        <p class="text-green-600 font-medium text-sm mt-2">
                                            <i class="fas fa-money-bill-wave mr-1"></i>
                                            Mức lương: <?php echo htmlspecialchars($rec['salary_range'] ?? 'Không có thông tin'); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Learning Roadmap Button -->
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <form method="POST" class="flex items-center justify-between">
                                        <input type="hidden" name="selected_major" value="<?php echo htmlspecialchars($rec['major_name']); ?>">
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
                        <p class="text-gray-600">Không có đề xuất AI nào được tìm thấy</p>
                        <p class="text-sm text-gray-500 mt-2">Hiển thị đề xuất dựa trên thông tin profile của bạn</p>
                    </div>
                    
                    <!-- Fallback Recommendations -->
                    <div class="space-y-6">
                        <?php
                        // Tạo fallback recommendations dựa trên profile
                        $techInterest = (int)$profile['tech_interest'];
                        $creativity = (int)$profile['creativity'];
                        $communication = (int)$profile['communication'];
                        $favoriteSubject = strtolower($profile['favorite_subject']);
                        $careerOrientation = strtolower($profile['career_orientation']);
                        
                        $fallbackRecommendations = [];
                        
                        // Logic gợi ý ngành dựa trên thông tin
                        if ($techInterest >= 8 || strpos($favoriteSubject, 'toán') !== false || strpos($careerOrientation, 'lập trình') !== false) {
                            $fallbackRecommendations[] = [
                                'major_name' => 'Ứng dụng phần mềm',
                                'confidence' => 0.85,
                                'reasoning' => 'Phù hợp với sở thích công nghệ và khả năng tư duy logic. Ngành này đào tạo chuyên sâu về lập trình, phát triển ứng dụng và công nghệ phần mềm.',
                                'career_prospects' => 'Lập trình viên, Kỹ sư phần mềm, Developer, Mobile App Developer, Web Developer',
                                'salary_range' => '15-50 triệu VNĐ/tháng'
                            ];
                        }
                        
                        if ($creativity >= 8 || strpos($careerOrientation, 'thiết kế') !== false) {
                            $fallbackRecommendations[] = [
                                'major_name' => 'Thiết kế đồ họa',
                                'confidence' => 0.80,
                                'reasoning' => 'Phù hợp với khả năng sáng tạo và yêu thích nghệ thuật. Ngành này kết hợp giữa công nghệ và nghệ thuật, đào tạo về thiết kế digital.',
                                'career_prospects' => 'Graphic Designer, UI/UX Designer, Digital Artist, Creative Designer, Brand Designer',
                                'salary_range' => '12-40 triệu VNĐ/tháng'
                            ];
                        }
                        
                        if ($communication >= 8 || strpos($careerOrientation, 'kinh doanh') !== false) {
                            $fallbackRecommendations[] = [
                                'major_name' => 'Quản trị kinh doanh',
                                'confidence' => 0.75,
                                'reasoning' => 'Phù hợp với khả năng giao tiếp và mục tiêu phát triển sự nghiệp. Ngành này đào tạo về quản lý, marketing và kinh doanh số.',
                                'career_prospects' => 'Business Analyst, Marketing Manager, Sales Manager, Project Manager, Entrepreneur',
                                'salary_range' => '10-35 triệu VNĐ/tháng'
                            ];
                        }
                        
                        // Nếu không có recommendation nào, thêm default
                        if (empty($fallbackRecommendations)) {
                            $fallbackRecommendations[] = [
                                'major_name' => 'Ứng dụng phần mềm',
                                'confidence' => 0.70,
                                'reasoning' => 'Ngành công nghệ thông tin phù hợp với xu hướng hiện tại và cơ hội việc làm rộng mở.',
                                'career_prospects' => 'Lập trình viên, Kỹ sư phần mềm, Developer, IT Support',
                                'salary_range' => '12-40 triệu VNĐ/tháng'
                            ];
                        }
                        
                        foreach ($fallbackRecommendations as $index => $rec):
                        ?>
                            <div class="border border-gray-200 rounded-lg p-6 <?php echo $index === 0 ? 'bg-gradient-to-r from-blue-50 to-indigo-50 border-blue-200' : 'bg-gray-50'; ?>">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <span class="bg-blue-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">
                                            <?php echo $index + 1; ?>
                                        </span>
                                        <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($rec['major_name'] ?? 'Ngành học không xác định'); ?></h3>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <?php echo round(($rec['confidence'] ?? 0.7) * 100); ?>% phù hợp
                                        </span>
                                        <?php if ($index === 0): ?>
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
                                                <i class="fas fa-crown mr-1"></i>Đề xuất hàng đầu
                                            </span>
                                        <?php endif; ?>
                                        <span class="bg-orange-100 text-orange-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <i class="fas fa-info-circle mr-1"></i>Dựa trên profile
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                                            Lý do đề xuất
                                        </h4>
                                        <p class="text-gray-700 text-sm leading-relaxed">
                                            <?php echo htmlspecialchars($rec['reasoning'] ?? 'Không có thông tin'); ?>
                                        </p>
                                    </div>
                                    
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-briefcase text-blue-600 mr-2"></i>
                                            Triển vọng nghề nghiệp
                                        </h4>
                                        <p class="text-gray-700 text-sm leading-relaxed">
                                            <?php echo htmlspecialchars($rec['career_prospects'] ?? 'Không có thông tin'); ?>
                                        </p>
                                        <p class="text-green-600 font-medium text-sm mt-2">
                                            <i class="fas fa-money-bill-wave mr-1"></i>
                                            Mức lương: <?php echo htmlspecialchars($rec['salary_range'] ?? 'Không có thông tin'); ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Learning Roadmap Button -->
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <form method="POST" class="flex items-center justify-between">
                                        <input type="hidden" name="selected_major" value="<?php echo htmlspecialchars($rec['major_name']); ?>">
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
                <?php endif; ?>
            </div>
        </div>

        <!-- Learning Roadmap -->
        <?php if ($learningRoadmap): ?>
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-road text-purple-600 mr-2"></i>
                        Lộ trình học tập - <?php echo htmlspecialchars($learningRoadmap['major'] ?? $selectedMajor ?? 'Ngành học'); ?>
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
                            <?php foreach ($learningRoadmap['focus_subjects'] ?? [] as $subject): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($subject['subject'] ?? 'Môn học'); ?></h4>
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs font-medium">
                                            Độ khó: <?php echo htmlspecialchars($subject['difficulty'] ?? 'N/A'); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($subject['reason'] ?? 'Không có thông tin'); ?>
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
                            <?php foreach ($learningRoadmap['skills_to_improve'] ?? [] as $skill): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($skill['skill'] ?? 'Kỹ năng'); ?></h4>
                                        <div class="flex items-center space-x-2">
                                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">
                                                Hiện tại: <?php echo htmlspecialchars($skill['current_level'] ?? 'N/A'); ?>
                                            </span>
                                            <i class="fas fa-arrow-right text-gray-400"></i>
                                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                                Mục tiêu: <?php echo htmlspecialchars($skill['target_level'] ?? 'N/A'); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <strong>Cách cải thiện:</strong> <?php echo htmlspecialchars($skill['improvement_method'] ?? 'Không có thông tin'); ?>
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
                            <?php foreach ($learningRoadmap['semester_roadmap'] ?? [] as $semester): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($semester['semester'] ?? 'Kỳ học'); ?></h4>
                                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                            <?php echo count($semester['subjects'] ?? []); ?> môn học
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <p class="text-sm text-gray-600 mb-2">
                                            <strong>Môn học:</strong>
                                        </p>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($semester['subjects'] ?? [] as $subject): ?>
                                                <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs">
                                                    <?php echo htmlspecialchars($subject); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        <strong>Trọng tâm:</strong> <?php echo htmlspecialchars($semester['focus'] ?? 'Không có thông tin'); ?>
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
                            <?php foreach ($learningRoadmap['career_opportunities'] ?? [] as $career): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($career['position'] ?? 'Vị trí'); ?></h4>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
                                            <?php echo htmlspecialchars($career['company_type'] ?? 'Công ty'); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <strong>Mức lương:</strong> <?php echo htmlspecialchars($career['salary_range'] ?? 'Không có thông tin'); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <strong>Yêu cầu:</strong> <?php echo htmlspecialchars($career['requirements'] ?? 'Không có thông tin'); ?>
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
