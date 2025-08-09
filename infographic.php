<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectWithMessage('index.php', 'Vui lòng đăng nhập', 'error');
}

$user = getCurrentUser();
$profileId = (int)($_GET['id'] ?? 0);

if (!$profileId) {
    redirectWithMessage('my-profiles.php', 'Không tìm thấy thông tin', 'error');
}

// Lấy dữ liệu profile và recommendations
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
    error_log("Database error in infographic.php: " . $e->getMessage());
    redirectWithMessage('my-profiles.php', 'Có lỗi xảy ra khi tải dữ liệu', 'error');
}

// Tính toán các chỉ số
$techScore = (int)($profile['tech_interest'] ?? 5);
$creativityScore = (int)($profile['creativity'] ?? 5);
$communicationScore = (int)($profile['communication'] ?? 5);

            // Tính điểm trung bình các môn học
            $subjects = ['math_score', 'literature_score', 'english_score'];
$totalScore = 0;
$validSubjects = 0;
foreach ($subjects as $subject) {
    if (!empty($profile[$subject])) {
        $totalScore += (float)$profile[$subject];
        $validSubjects++;
    }
}
$averageScore = $validSubjects > 0 ? round($totalScore / $validSubjects, 1) : 0;

// Xác định ngành phù hợp nhất
$bestMajor = 'Ứng dụng phần mềm';
$bestScore = 0;

if (!empty($recommendations)) {
    $bestMajor = $recommendations[0]['major_name'] ?? 'Ứng dụng phần mềm';
    $bestScore = round(($recommendations[0]['confidence'] ?? 0.7) * 100);
} else {
    // Logic fallback để xác định ngành phù hợp
    if ($techScore >= 8) {
        $bestMajor = 'Ứng dụng phần mềm';
        $bestScore = 85;
    } elseif ($creativityScore >= 8) {
        $bestMajor = 'Thiết kế đồ họa';
        $bestScore = 80;
    } elseif ($communicationScore >= 8) {
        $bestMajor = 'Quản trị kinh doanh';
        $bestScore = 75;
    } else {
        $bestMajor = 'Ứng dụng phần mềm';
        $bestScore = 70;
    }
}

// Tạo màu sắc cho biểu đồ
$colors = [
    'tech' => '#3B82F6',
    'creativity' => '#10B981', 
    'communication' => '#8B5CF6',
    'average' => '#F59E0B'
];

// Tạo dữ liệu cho biểu đồ radar
$radarData = [
    'labels' => ['Công nghệ', 'Sáng tạo', 'Giao tiếp', 'Điểm TB'],
    'datasets' => [
        [
            'label' => 'Điểm số của bạn',
            'data' => [$techScore, $creativityScore, $communicationScore, $averageScore],
            'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
            'borderColor' => '#3B82F6',
            'pointBackgroundColor' => '#3B82F6',
            'pointBorderColor' => '#fff',
            'pointHoverBackgroundColor' => '#fff',
            'pointHoverBorderColor' => '#3B82F6'
        ]
    ]
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infographic Tư vấn - NguoiBanAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-glow {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .card-glow:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
        .progress-ring-circle {
            transition: stroke-dasharray 0.35s;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <i class="fas fa-chart-pie text-3xl mr-3"></i>
                    <h1 class="text-3xl font-bold">Infographic Tư vấn</h1>
                </div>
                <nav class="flex space-x-4">
                    <a href="profile-detail.php?id=<?php echo $profileId; ?>" class="text-white hover:text-blue-200 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>Chi tiết
                    </a>
                    <a href="dashboard.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-home mr-1"></i>Trang chủ
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Student Info Card -->
        <div class="bg-white rounded-2xl shadow-xl card-glow mb-8 overflow-hidden">
            <div class="gradient-bg text-white p-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">
                            <i class="fas fa-user-graduate mr-3"></i>
                            <?php echo htmlspecialchars($user['fullname']); ?>
                        </h2>
                        <p class="text-blue-100 text-lg">
                            <i class="fas fa-calendar mr-2"></i>
                            Ngày phân tích: <?php echo formatDate($profile['created_at']); ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-4xl font-bold mb-2"><?php echo $bestScore; ?>%</div>
                        <div class="text-blue-100">Phù hợp với ngành</div>
                    </div>
                </div>
            </div>
            
            <div class="p-8">
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-4 relative">
                            <svg class="w-20 h-20 progress-ring">
                                <circle class="progress-ring-circle" stroke="#E5E7EB" stroke-width="8" fill="transparent" r="32" cx="40" cy="40"/>
                                <circle class="progress-ring-circle" stroke="#3B82F6" stroke-width="8" fill="transparent" r="32" cx="40" cy="40" 
                                        stroke-dasharray="<?php echo 2 * M_PI * 32 * $techScore / 10; ?> <?php echo 2 * M_PI * 32; ?>"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-bold text-gray-800"><?php echo $techScore; ?>/10</span>
                            </div>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">Công nghệ</h3>
                        <p class="text-sm text-gray-600">Khả năng tư duy logic</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-4 relative">
                            <svg class="w-20 h-20 progress-ring">
                                <circle class="progress-ring-circle" stroke="#E5E7EB" stroke-width="8" fill="transparent" r="32" cx="40" cy="40"/>
                                <circle class="progress-ring-circle" stroke="#10B981" stroke-width="8" fill="transparent" r="32" cx="40" cy="40" 
                                        stroke-dasharray="<?php echo 2 * M_PI * 32 * $creativityScore / 10; ?> <?php echo 2 * M_PI * 32; ?>"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-bold text-gray-800"><?php echo $creativityScore; ?>/10</span>
                            </div>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">Sáng tạo</h3>
                        <p class="text-sm text-gray-600">Khả năng nghệ thuật</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-20 h-20 mx-auto mb-4 relative">
                            <svg class="w-20 h-20 progress-ring">
                                <circle class="progress-ring-circle" stroke="#E5E7EB" stroke-width="8" fill="transparent" r="32" cx="40" cy="40"/>
                                <circle class="progress-ring-circle" stroke="#8B5CF6" stroke-width="8" fill="transparent" r="32" cx="40" cy="40" 
                                        stroke-dasharray="<?php echo 2 * M_PI * 32 * $communicationScore / 10; ?> <?php echo 2 * M_PI * 32; ?>"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="text-lg font-bold text-gray-800"><?php echo $communicationScore; ?>/10</span>
                            </div>
                        </div>
                        <h3 class="font-semibold text-gray-800 mb-1">Giao tiếp</h3>
                        <p class="text-sm text-gray-600">Khả năng tương tác</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Radar Chart -->
        <div class="bg-white rounded-2xl shadow-xl card-glow mb-8 p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                <i class="fas fa-chart-radar mr-3 text-blue-600"></i>
                Biểu đồ năng lực
            </h3>
            <div class="flex justify-center">
                <canvas id="radarChart" width="400" height="400"></canvas>
            </div>
        </div>

        <!-- Subject Scores -->
        <div class="bg-white rounded-2xl shadow-xl card-glow mb-8 p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-graduation-cap mr-3 text-green-600"></i>
                Điểm số các môn học
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php
                                            $subjects = [
                                'math_score' => ['Toán học', 'fas fa-square-root-alt', 'blue'],
                                'literature_score' => ['Văn học', 'fas fa-book-open', 'green'],
                                'english_score' => ['Tiếng Anh', 'fas fa-language', 'purple']
                            ];
                
                foreach ($subjects as $key => $subject):
                    $score = (float)($profile[$key] ?? 0);
                    $percentage = $score * 10; // Chuyển từ thang 10 sang phần trăm
                    $color = $subject[2];
                ?>
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <i class="<?php echo $subject[1]; ?> text-2xl text-<?php echo $color; ?>-600 mr-3"></i>
                            <h4 class="font-semibold text-gray-800"><?php echo $subject[0]; ?></h4>
                        </div>
                        <span class="text-2xl font-bold text-gray-800"><?php echo $score; ?>/10</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-<?php echo $color; ?>-600 h-3 rounded-full transition-all duration-500" 
                             style="width: <?php echo $percentage; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Major Recommendations -->
        <div class="bg-white rounded-2xl shadow-xl card-glow mb-8 p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6">
                <i class="fas fa-lightbulb mr-3 text-yellow-600"></i>
                Đề xuất ngành học
            </h3>
            
            <?php if (!empty($recommendations)): ?>
                <div class="space-y-6">
                    <?php foreach ($recommendations as $index => $rec): ?>
                        <div class="border-2 border-gray-200 rounded-xl p-6 <?php echo $index === 0 ? 'border-blue-500 bg-blue-50' : 'bg-gray-50'; ?>">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center text-xl font-bold mr-4">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <h4 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($rec['major_name'] ?? 'Ngành học'); ?></h4>
                                        <p class="text-gray-600">FPT Polytechnic</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-blue-600">
                                        <?php echo round(($rec['confidence'] ?? 0.7) * 100); ?>%
                                    </div>
                                    <div class="text-sm text-gray-600">Phù hợp</div>
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <h5 class="font-semibold text-gray-800 mb-2">
                                        <i class="fas fa-comment mr-2 text-blue-600"></i>
                                        Lý do đề xuất
                                    </h5>
                                    <p class="text-gray-700 text-sm leading-relaxed">
                                        <?php echo htmlspecialchars($rec['reasoning'] ?? 'Không có thông tin'); ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <h5 class="font-semibold text-gray-800 mb-2">
                                        <i class="fas fa-briefcase mr-2 text-green-600"></i>
                                        Triển vọng nghề nghiệp
                                    </h5>
                                    <p class="text-gray-700 text-sm leading-relaxed">
                                        <?php echo htmlspecialchars($rec['career_prospects'] ?? 'Không có thông tin'); ?>
                                    </p>
                                    <div class="mt-2 text-green-600 font-medium">
                                        <i class="fas fa-money-bill-wave mr-1"></i>
                                        <?php echo htmlspecialchars($rec['salary_range'] ?? 'Không có thông tin'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($index === 0): ?>
                                <div class="mt-4 p-4 bg-yellow-100 border border-yellow-300 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-crown text-yellow-600 mr-2"></i>
                                        <span class="font-semibold text-yellow-800">Đề xuất hàng đầu</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Fallback recommendations -->
                <div class="space-y-6">
                    <?php
                    $fallbackRecommendations = [];
                    
                    if ($techScore >= 7) {
                        $fallbackRecommendations[] = [
                            'major_name' => 'Ứng dụng phần mềm',
                            'confidence' => 0.85,
                            'reasoning' => 'Phù hợp với khả năng tư duy logic và sở thích công nghệ.',
                            'career_prospects' => 'Lập trình viên, Kỹ sư phần mềm, Developer',
                            'salary_range' => '15-50 triệu VNĐ/tháng'
                        ];
                    }
                    
                    if ($creativityScore >= 7) {
                        $fallbackRecommendations[] = [
                            'major_name' => 'Thiết kế đồ họa',
                            'confidence' => 0.80,
                            'reasoning' => 'Phù hợp với khả năng sáng tạo và yêu thích nghệ thuật.',
                            'career_prospects' => 'Graphic Designer, UI/UX Designer, Digital Artist',
                            'salary_range' => '12-40 triệu VNĐ/tháng'
                        ];
                    }
                    
                    if ($communicationScore >= 7) {
                        $fallbackRecommendations[] = [
                            'major_name' => 'Quản trị kinh doanh',
                            'confidence' => 0.75,
                            'reasoning' => 'Phù hợp với khả năng giao tiếp và mục tiêu phát triển sự nghiệp.',
                            'career_prospects' => 'Business Analyst, Marketing Manager, Sales Manager',
                            'salary_range' => '10-35 triệu VNĐ/tháng'
                        ];
                    }
                    
                    if (empty($fallbackRecommendations)) {
                        $fallbackRecommendations[] = [
                            'major_name' => 'Ứng dụng phần mềm',
                            'confidence' => 0.70,
                            'reasoning' => 'Ngành công nghệ thông tin phù hợp với xu hướng hiện tại.',
                            'career_prospects' => 'Lập trình viên, Kỹ sư phần mềm, Developer',
                            'salary_range' => '12-40 triệu VNĐ/tháng'
                        ];
                    }
                    
                    foreach ($fallbackRecommendations as $index => $rec):
                    ?>
                        <div class="border-2 border-gray-200 rounded-xl p-6 <?php echo $index === 0 ? 'border-blue-500 bg-blue-50' : 'bg-gray-50'; ?>">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center">
                                    <div class="w-12 h-12 bg-blue-600 text-white rounded-full flex items-center justify-center text-xl font-bold mr-4">
                                        <?php echo $index + 1; ?>
                                    </div>
                                    <div>
                                        <h4 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($rec['major_name']); ?></h4>
                                        <p class="text-gray-600">FPT Polytechnic</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-3xl font-bold text-blue-600">
                                        <?php echo round($rec['confidence'] * 100); ?>%
                                    </div>
                                    <div class="text-sm text-gray-600">Phù hợp</div>
                                </div>
                            </div>
                            
                            <div class="grid md:grid-cols-2 gap-6">
                                <div>
                                    <h5 class="font-semibold text-gray-800 mb-2">
                                        <i class="fas fa-comment mr-2 text-blue-600"></i>
                                        Lý do đề xuất
                                    </h5>
                                    <p class="text-gray-700 text-sm leading-relaxed">
                                        <?php echo htmlspecialchars($rec['reasoning']); ?>
                                    </p>
                                </div>
                                
                                <div>
                                    <h5 class="font-semibold text-gray-800 mb-2">
                                        <i class="fas fa-briefcase mr-2 text-green-600"></i>
                                        Triển vọng nghề nghiệp
                                    </h5>
                                    <p class="text-gray-700 text-sm leading-relaxed">
                                        <?php echo htmlspecialchars($rec['career_prospects']); ?>
                                    </p>
                                    <div class="mt-2 text-green-600 font-medium">
                                        <i class="fas fa-money-bill-wave mr-1"></i>
                                        <?php echo htmlspecialchars($rec['salary_range']); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($index === 0): ?>
                                <div class="mt-4 p-4 bg-yellow-100 border border-yellow-300 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-crown text-yellow-600 mr-2"></i>
                                        <span class="font-semibold text-yellow-800">Đề xuất hàng đầu</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white rounded-2xl shadow-xl card-glow p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">
                <i class="fas fa-rocket mr-3 text-purple-600"></i>
                Bước tiếp theo
            </h3>
            <div class="grid md:grid-cols-3 gap-6">
                <a href="profile-detail.php?id=<?php echo $profileId; ?>" 
                   class="bg-blue-600 text-white p-6 rounded-xl text-center hover:bg-blue-700 transition-colors">
                    <i class="fas fa-chart-line text-3xl mb-4"></i>
                    <h4 class="text-lg font-semibold mb-2">Xem chi tiết</h4>
                    <p class="text-blue-100 text-sm">Phân tích chi tiết và lộ trình học tập</p>
                </a>
                
                <a href="manage-roadmaps.php" 
                   class="bg-purple-600 text-white p-6 rounded-xl text-center hover:bg-purple-700 transition-colors">
                    <i class="fas fa-road text-3xl mb-4"></i>
                    <h4 class="text-lg font-semibold mb-2">Lộ trình học</h4>
                    <p class="text-purple-100 text-sm">Quản lý lộ trình học tập đã tạo</p>
                </a>
                
                <a href="my-profiles.php" 
                   class="bg-green-600 text-white p-6 rounded-xl text-center hover:bg-green-700 transition-colors">
                    <i class="fas fa-user text-3xl mb-4"></i>
                    <h4 class="text-lg font-semibold mb-2">Hồ sơ của tôi</h4>
                    <p class="text-green-100 text-sm">Xem tất cả hồ sơ đã tạo</p>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="text-center text-sm text-gray-500">
                <p>&copy; 2024 NguoiBanAI - Hệ thống tư vấn hướng nghiệp. Tất cả quyền được bảo lưu.</p>
            </div>
        </div>
    </footer>

    <script>
        // Radar Chart
        const ctx = document.getElementById('radarChart').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: ['Công nghệ', 'Sáng tạo', 'Giao tiếp', 'Điểm TB'],
                datasets: [{
                    label: 'Điểm số của bạn',
                    data: [<?php echo $techScore; ?>, <?php echo $creativityScore; ?>, <?php echo $communicationScore; ?>, <?php echo $averageScore; ?>],
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: '#3B82F6',
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#3B82F6',
                    borderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 2
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        pointLabels: {
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>
