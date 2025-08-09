<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/ai_service.php';

// Demo data
$demoProfiles = [
    [
        'id' => 1,
        'interests' => 'Công nghệ, lập trình, game',
        'favorite_subject' => 'Toán',
        'career_orientation' => 'Lập trình viên',
        'tech_interest' => 9,
        'creativity' => 7,
        'communication' => 6,
        'math_score' => 9.0,
        'literature_score' => 7.0,
        'english_score' => 8.0,
        'created_at' => '2024-01-15 10:30:00'
    ],
    [
        'id' => 2,
        'interests' => 'Nghệ thuật, thiết kế, vẽ',
        'favorite_subject' => 'Mỹ thuật',
        'career_orientation' => 'Thiết kế đồ họa',
        'tech_interest' => 6,
        'creativity' => 9,
        'communication' => 7,
        'math_score' => 6.0,
        'literature_score' => 8.0,
        'english_score' => 7.0,
        'created_at' => '2024-01-16 14:20:00'
    ],
    [
        'id' => 3,
        'interests' => 'Kinh doanh, marketing, giao tiếp',
        'favorite_subject' => 'Văn',
        'career_orientation' => 'Quản lý kinh doanh',
        'tech_interest' => 5,
        'creativity' => 6,
        'communication' => 9,
        'math_score' => 7.0,
        'literature_score' => 9.0,
        'english_score' => 8.0,
        'created_at' => '2024-01-17 09:15:00'
    ]
];

function suggestMajorBasedOnProfile($profileData) {
    $techInterest = (int)$profileData['tech_interest'];
    $creativity = (int)$profileData['creativity'];
    $communication = (int)$profileData['communication'];
    $favoriteSubject = strtolower($profileData['favorite_subject']);
    $careerOrientation = strtolower($profileData['career_orientation']);
    
    if ($techInterest >= 8 || strpos($favoriteSubject, 'toán') !== false || strpos($careerOrientation, 'lập trình') !== false) {
        return 'Ứng dụng phần mềm';
    } elseif ($creativity >= 8 || strpos($careerOrientation, 'thiết kế') !== false) {
        return 'Thiết kế đồ họa';
    } elseif ($communication >= 8 || strpos($careerOrientation, 'kinh doanh') !== false) {
        return 'Quản trị kinh doanh';
    } elseif ($techInterest >= 7) {
        return 'Công nghệ thông tin';
    } else {
        return 'Quản trị kinh doanh';
    }
}

function generateFallbackStudySuggestion($major) {
    $suggestions = [
        'Ứng dụng phần mềm' => [
            'focus_subjects' => [
                ['subject' => 'Lập trình Java', 'reason' => 'Nền tảng phát triển ứng dụng', 'difficulty' => '8/10'],
                ['subject' => 'Cơ sở dữ liệu', 'reason' => 'Quản lý dữ liệu hiệu quả', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Tư duy logic', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Luyện giải bài tập lập trình']
            ]
        ],
        'Thiết kế đồ họa' => [
            'focus_subjects' => [
                ['subject' => 'Thiết kế đồ họa cơ bản', 'reason' => 'Nền tảng về màu sắc và bố cục', 'difficulty' => '6/10'],
                ['subject' => 'Photoshop nâng cao', 'reason' => 'Công cụ chính cho thiết kế', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Khả năng sáng tạo', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Tham khảo portfolio, thực hành thiết kế']
            ]
        ],
        'Quản trị kinh doanh' => [
            'focus_subjects' => [
                ['subject' => 'Marketing cơ bản', 'reason' => 'Hiểu về thị trường và khách hàng', 'difficulty' => '6/10'],
                ['subject' => 'Quản lý dự án', 'reason' => 'Kỹ năng lãnh đạo và tổ chức', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Kỹ năng giao tiếp', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Tham gia thuyết trình, networking']
            ]
        ]
    ];
    
    return $suggestions[$major] ?? $suggestions['Ứng dụng phần mềm'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Gợi ý học tập - NguoiBanAI</title>
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
                    <span class="text-gray-700">Demo - Gợi ý học tập</span>
                    <a href="my-profiles.php" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Quay lại
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                <i class="fas fa-graduation-cap text-green-600 mr-3"></i>
                Demo - Tính năng Gợi ý học tập
            </h1>
            <p class="text-lg text-gray-600">Xem cách AI tự động gợi ý môn học và kỹ năng cần cải thiện</p>
        </div>

        <!-- Demo Profiles -->
        <div class="space-y-6">
            <?php foreach ($demoProfiles as $profile): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            Demo Profile #<?php echo $profile['id']; ?>
                        </h3>
                        <p class="text-gray-600">
                            <i class="fas fa-clock mr-2"></i>
                            <?php echo date('d/m/Y H:i', strtotime($profile['created_at'])); ?>
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="showStudySuggestion(<?php echo $profile['id']; ?>)" 
                                class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-graduation-cap mr-2"></i>Gợi ý học
                        </button>
                    </div>
                </div>
                
                <!-- Thông tin tóm tắt -->
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <h4 class="font-bold text-gray-700 mb-2">Thông tin cơ bản:</h4>
                        <div class="bg-gray-50 p-3 rounded">
                            <p><strong>Sở thích:</strong> <?php echo htmlspecialchars($profile['interests']); ?></p>
                            <p><strong>Môn yêu thích:</strong> <?php echo htmlspecialchars($profile['favorite_subject']); ?></p>
                            <p><strong>Định hướng:</strong> <?php echo htmlspecialchars($profile['career_orientation']); ?></p>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-700 mb-2">Điểm số:</h4>
                        <div class="bg-gray-50 p-3 rounded">
                            <p><strong>Toán:</strong> <?php echo $profile['math_score']; ?></p>
                            <p><strong>Văn:</strong> <?php echo $profile['literature_score']; ?></p>
                            <p><strong>Anh:</strong> <?php echo $profile['english_score']; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Đánh giá mức độ -->
                <div>
                    <h4 class="font-bold text-gray-700 mb-2">Đánh giá mức độ:</h4>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-blue-50 p-3 rounded text-center">
                            <p class="text-sm text-gray-600">Công nghệ</p>
                            <p class="text-lg font-bold text-blue-600"><?php echo $profile['tech_interest']; ?>/10</p>
                        </div>
                        <div class="bg-green-50 p-3 rounded text-center">
                            <p class="text-sm text-gray-600">Sáng tạo</p>
                            <p class="text-lg font-bold text-green-600"><?php echo $profile['creativity']; ?>/10</p>
                        </div>
                        <div class="bg-purple-50 p-3 rounded text-center">
                            <p class="text-sm text-gray-600">Giao tiếp</p>
                            <p class="text-lg font-bold text-purple-600"><?php echo $profile['communication']; ?>/10</p>
                        </div>
                    </div>
                </div>
                
                <!-- Study Suggestion (Hidden by default) -->
                <div id="studySuggestion<?php echo $profile['id']; ?>" class="hidden mt-6">
                    <?php 
                    $suggestedMajor = suggestMajorBasedOnProfile($profile);
                    $suggestion = generateFallbackStudySuggestion($suggestedMajor);
                    ?>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-graduation-cap text-green-600 mr-2"></i>
                                Gợi ý học tập - <?php echo htmlspecialchars($suggestedMajor); ?>
                            </h3>
                            <button onclick="hideStudySuggestion(<?php echo $profile['id']; ?>)" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Môn học cần tập trung -->
                        <div class="mb-6">
                            <h4 class="font-bold text-gray-800 mb-3">
                                <i class="fas fa-book text-blue-600 mr-2"></i>
                                Môn học cần tập trung
                            </h4>
                            <div class="grid md:grid-cols-2 gap-4">
                                <?php foreach ($suggestion['focus_subjects'] as $subject): ?>
                                <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                    <div class="flex items-center justify-between mb-2">
                                        <h5 class="font-medium text-gray-900"><?php echo htmlspecialchars($subject['subject']); ?></h5>
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
                        
                        <!-- Kỹ năng cần cải thiện -->
                        <div>
                            <h4 class="font-bold text-gray-800 mb-3">
                                <i class="fas fa-chart-line text-green-600 mr-2"></i>
                                Kỹ năng cần cải thiện
                            </h4>
                            <div class="space-y-4">
                                <?php foreach ($suggestion['skills_to_improve'] as $skill): ?>
                                <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                    <div class="flex items-center justify-between mb-2">
                                        <h5 class="font-medium text-gray-900"><?php echo htmlspecialchars($skill['skill']); ?></h5>
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
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function showStudySuggestion(profileId) {
            const suggestion = document.getElementById('studySuggestion' + profileId);
            suggestion.classList.remove('hidden');
        }
        
        function hideStudySuggestion(profileId) {
            const suggestion = document.getElementById('studySuggestion' + profileId);
            suggestion.classList.add('hidden');
        }
    </script>
</body>
</html>
