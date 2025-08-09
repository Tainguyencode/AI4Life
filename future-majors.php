<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit();
}

$user = getCurrentUser();

// Dữ liệu ngành học của FPT Polytechnic với dự đoán xu hướng
$majors = [
    [
        'name' => 'Công nghệ thông tin',
        'code' => 'CNTT',
        'description' => 'Phát triển phần mềm, ứng dụng web, mobile',
        'current_demand' => 95,
        'future_demand' => 98,
        'salary_range' => '15-50 triệu VNĐ',
        'growth_rate' => 15,
        'trend' => 'up',
        'reasons' => [
            'Chuyển đổi số toàn cầu',
            'AI và Machine Learning phát triển',
            'Nhu cầu phần mềm tăng cao',
            'Startup công nghệ bùng nổ'
        ],
        'skills' => ['Lập trình', 'AI/ML', 'Cloud Computing', 'Cybersecurity'],
        'companies' => ['FPT Software', 'Viettel', 'VNG', 'Tiki', 'Shopee']
    ],
    [
        'name' => 'Thiết kế đồ họa',
        'code' => 'TKDH',
        'description' => 'Thiết kế UI/UX, branding, digital marketing',
        'current_demand' => 85,
        'future_demand' => 92,
        'salary_range' => '12-35 triệu VNĐ',
        'growth_rate' => 12,
        'trend' => 'up',
        'reasons' => [
            'Thương mại điện tử phát triển',
            'Nhu cầu branding tăng cao',
            'Social media marketing bùng nổ',
            'UX/UI quan trọng hơn'
        ],
        'skills' => ['Photoshop', 'Illustrator', 'Figma', 'UI/UX Design'],
        'companies' => ['Agency', 'Startup', 'E-commerce', 'Digital Marketing']
    ],
    [
        'name' => 'Quản trị kinh doanh',
        'code' => 'QTKD',
        'description' => 'Quản lý doanh nghiệp, marketing, bán hàng',
        'current_demand' => 80,
        'future_demand' => 88,
        'salary_range' => '10-30 triệu VNĐ',
        'growth_rate' => 10,
        'trend' => 'up',
        'reasons' => [
            'Startup ecosystem phát triển',
            'Digital marketing tăng trưởng',
            'E-commerce bùng nổ',
            'Quản lý dự án cần thiết'
        ],
        'skills' => ['Marketing', 'Sales', 'Project Management', 'Analytics'],
        'companies' => ['Startup', 'E-commerce', 'Agency', 'Corporation']
    ],
    [
        'name' => 'Truyền thông đa phương tiện',
        'code' => 'TTĐPT',
        'description' => 'Sản xuất video, content marketing, social media',
        'current_demand' => 75,
        'future_demand' => 85,
        'salary_range' => '8-25 triệu VNĐ',
        'growth_rate' => 13,
        'trend' => 'up',
        'reasons' => [
            'Content marketing tăng trưởng',
            'Social media bùng nổ',
            'Video content phổ biến',
            'Influencer marketing phát triển'
        ],
        'skills' => ['Video Editing', 'Content Creation', 'Social Media', 'Photography'],
        'companies' => ['Agency', 'Media', 'E-commerce', 'Startup']
    ],
    [
        'name' => 'Kế toán',
        'code' => 'KT',
        'description' => 'Kế toán, kiểm toán, tài chính doanh nghiệp',
        'current_demand' => 70,
        'future_demand' => 75,
        'salary_range' => '8-20 triệu VNĐ',
        'growth_rate' => 5,
        'trend' => 'stable',
        'reasons' => [
            'Luôn cần thiết trong mọi doanh nghiệp',
            'Automation giảm nhu cầu',
            'Tập trung vào phân tích dữ liệu',
            'Compliance và audit quan trọng'
        ],
        'skills' => ['Excel', 'ERP Systems', 'Financial Analysis', 'Tax'],
        'companies' => ['Big4', 'Corporation', 'Startup', 'Government']
    ],
    [
        'name' => 'Du lịch và lữ hành',
        'code' => 'DL',
        'description' => 'Quản lý du lịch, khách sạn, sự kiện',
        'current_demand' => 60,
        'future_demand' => 70,
        'salary_range' => '6-18 triệu VNĐ',
        'growth_rate' => 8,
        'trend' => 'up',
        'reasons' => [
            'Du lịch phục hồi sau Covid',
            'Domestic tourism tăng trưởng',
            'Digital transformation trong ngành',
            'Experience economy phát triển'
        ],
        'skills' => ['Customer Service', 'Event Planning', 'Digital Marketing', 'Languages'],
        'companies' => ['Hotels', 'Travel Agencies', 'Event Companies', 'Resorts']
    ]
];

// Sắp xếp theo xu hướng tương lai
usort($majors, function($a, $b) {
    return $b['future_demand'] - $a['future_demand'];
});

// Tính toán thống kê
$total_majors = count($majors);
$high_demand = count(array_filter($majors, function($m) { return $m['future_demand'] >= 85; }));
$growing_majors = count(array_filter($majors, function($m) { return $m['trend'] === 'up'; }));
$avg_growth = array_sum(array_column($majors, 'growth_rate')) / $total_majors;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dự đoán ngành học hot - FPT Polytechnic</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-university text-blue-600 text-2xl mr-3"></i>
                    <h1 class="text-2xl font-bold text-gray-900">Dự đoán ngành học hot</h1>
                </div>
                <nav class="flex space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-blue-600">
                        <i class="fas fa-home mr-1"></i>Dashboard
                    </a>
                    <a href="my-profiles.php" class="text-gray-600 hover:text-blue-600">
                        <i class="fas fa-user mr-1"></i>Hồ sơ
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-sign-out-alt mr-1"></i>Đăng xuất
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg p-8 mb-8">
            <div class="text-center">
                <h2 class="text-3xl font-bold mb-4">
                    <i class="fas fa-crystal-ball mr-3"></i>
                    Dự đoán ngành học hot 3-5 năm tới
                </h2>
                <p class="text-xl opacity-90 mb-6">
                    Phân tích xu hướng thị trường và dự đoán nhu cầu nhân lực trong tương lai
                </p>
                <div class="grid md:grid-cols-4 gap-4 text-center">
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <div class="text-2xl font-bold"><?php echo $total_majors; ?></div>
                        <div class="text-sm">Ngành học</div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <div class="text-2xl font-bold"><?php echo $high_demand; ?></div>
                        <div class="text-sm">Nhu cầu cao</div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <div class="text-2xl font-bold"><?php echo $growing_majors; ?></div>
                        <div class="text-sm">Đang tăng trưởng</div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4">
                        <div class="text-2xl font-bold"><?php echo round($avg_growth, 1); ?>%</div>
                        <div class="text-sm">Tăng trưởng TB</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-chart-line mr-2"></i>
                    So sánh nhu cầu hiện tại vs tương lai
                </h3>
                <canvas id="demandChart" height="300"></canvas>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-chart-pie mr-2"></i>
                    Tỷ lệ tăng trưởng theo ngành
                </h3>
                <canvas id="growthChart" height="300"></canvas>
            </div>
        </div>

        <!-- Majors Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($majors as $index => $major): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-500 text-white p-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold"><?php echo $major['name']; ?></h3>
                            <p class="text-sm opacity-90"><?php echo $major['code']; ?></p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold"><?php echo $major['future_demand']; ?>%</div>
                            <div class="text-xs">Nhu cầu tương lai</div>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="p-4">
                    <p class="text-gray-600 text-sm mb-4"><?php echo $major['description']; ?></p>
                    
                    <!-- Demand Progress -->
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span>Nhu cầu hiện tại: <?php echo $major['current_demand']; ?>%</span>
                            <span>Nhu cầu tương lai: <?php echo $major['future_demand']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $major['current_demand']; ?>%"></div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $major['future_demand']; ?>%"></div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="text-center">
                            <div class="text-lg font-bold text-blue-600"><?php echo $major['growth_rate']; ?>%</div>
                            <div class="text-xs text-gray-600">Tăng trưởng</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-bold text-green-600"><?php echo $major['salary_range']; ?></div>
                            <div class="text-xs text-gray-600">Mức lương</div>
                        </div>
                    </div>

                    <!-- Trend -->
                    <div class="flex items-center mb-4">
                        <span class="text-sm text-gray-600 mr-2">Xu hướng:</span>
                        <?php if ($major['trend'] === 'up'): ?>
                            <i class="fas fa-arrow-up text-green-600"></i>
                            <span class="text-sm text-green-600 ml-1">Tăng trưởng</span>
                        <?php elseif ($major['trend'] === 'stable'): ?>
                            <i class="fas fa-minus text-yellow-600"></i>
                            <span class="text-sm text-yellow-600 ml-1">Ổn định</span>
                        <?php else: ?>
                            <i class="fas fa-arrow-down text-red-600"></i>
                            <span class="text-sm text-red-600 ml-1">Giảm</span>
                        <?php endif; ?>
                    </div>

                    <!-- Reasons -->
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-800 mb-2">Lý do tăng trưởng:</h4>
                        <ul class="text-sm text-gray-600 space-y-1">
                            <?php foreach (array_slice($major['reasons'], 0, 3) as $reason): ?>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mr-2 mt-0.5"></i>
                                <?php echo $reason; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Skills -->
                    <div class="mb-4">
                        <h4 class="font-semibold text-gray-800 mb-2">Kỹ năng cần thiết:</h4>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach (array_slice($major['skills'], 0, 3) as $skill): ?>
                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded"><?php echo $skill; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Companies -->
                    <div>
                        <h4 class="font-semibold text-gray-800 mb-2">Công ty tuyển dụng:</h4>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach (array_slice($major['companies'], 0, 3) as $company): ?>
                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded"><?php echo $company; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Insights Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-lightbulb mr-2"></i>
                Nhận định và khuyến nghị
            </h3>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Xu hướng chính:</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-arrow-up text-green-600 mr-2 mt-1"></i>
                            <span>Công nghệ thông tin vẫn là ngành hot nhất với AI/ML</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-arrow-up text-green-600 mr-2 mt-1"></i>
                            <span>Thiết kế đồ họa tăng trưởng mạnh với e-commerce</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-arrow-up text-green-600 mr-2 mt-1"></i>
                            <span>Quản trị kinh doanh phù hợp với startup ecosystem</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-minus text-yellow-600 mr-2 mt-1"></i>
                            <span>Kế toán ổn định nhưng cần thêm kỹ năng phân tích</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 mb-3">Khuyến nghị cho sinh viên:</h4>
                    <ul class="space-y-2 text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                            <span>Chọn ngành phù hợp với đam mê và năng lực</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                            <span>Trau dồi kỹ năng mềm và ngoại ngữ</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                            <span>Thực tập sớm để có kinh nghiệm thực tế</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-blue-600 mr-2 mt-1"></i>
                            <span>Theo dõi xu hướng công nghệ mới</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Chart 1: Demand Comparison
        const demandCtx = document.getElementById('demandChart').getContext('2d');
        new Chart(demandCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($majors, 'name')); ?>,
                datasets: [{
                    label: 'Nhu cầu hiện tại',
                    data: <?php echo json_encode(array_column($majors, 'current_demand')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }, {
                    label: 'Nhu cầu tương lai',
                    data: <?php echo json_encode(array_column($majors, 'future_demand')); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgba(34, 197, 94, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                }
            }
        });

        // Chart 2: Growth Rate
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        new Chart(growthCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($majors, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($majors, 'growth_rate')); ?>,
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(251, 191, 36, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                        'rgba(236, 72, 153, 0.8)'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
