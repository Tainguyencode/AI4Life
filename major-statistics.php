<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectWithMessage('index.php', 'Vui lòng đăng nhập', 'error');
}

$user = getCurrentUser();

// Lấy thống kê ngành học từ database
try {
    $pdo = getConnection();
    
    // Thống kê các ngành được đề xuất nhiều nhất
    $stmt = $pdo->prepare("
        SELECT 
            major_name,
            COUNT(*) as recommendation_count,
            AVG(confidence) as avg_confidence
        FROM ai_recommendations 
        WHERE recommendation_order = 1 
        GROUP BY major_name 
        ORDER BY recommendation_count DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $majorStats = $stmt->fetchAll();
    
    // Thống kê tổng quan
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT profile_id) as total_profiles FROM ai_recommendations");
    $stmt->execute();
    $totalProfiles = $stmt->fetch()['total_profiles'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_recommendations FROM ai_recommendations");
    $stmt->execute();
    $totalRecommendations = $stmt->fetch()['total_recommendations'];
    
    // Thống kê theo thời gian (7 ngày gần nhất)
    $stmt = $pdo->prepare("
        SELECT 
            DATE(ar.created_at) as date,
            COUNT(*) as daily_recommendations
        FROM ai_recommendations ar
        WHERE ar.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(ar.created_at)
        ORDER BY date DESC
    ");
    $stmt->execute();
    $dailyStats = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Database error in major-statistics.php: " . $e->getMessage());
    $majorStats = [];
    $totalProfiles = 0;
    $totalRecommendations = 0;
    $dailyStats = [];
}

// Chuẩn bị dữ liệu cho biểu đồ
$chartLabels = [];
$chartData = [];
$chartColors = [
    '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6',
    '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6366F1'
];

foreach ($majorStats as $index => $stat) {
    $chartLabels[] = $stat['major_name'];
    $chartData[] = $stat['recommendation_count'];
}

// Dữ liệu cho biểu đồ theo thời gian
$timeLabels = [];
$timeData = [];
foreach (array_reverse($dailyStats) as $stat) {
    $timeLabels[] = date('d/m', strtotime($stat['date']));
    $timeData[] = $stat['daily_recommendations'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thống kê ngành học - NguoiBanAI</title>
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <i class="fas fa-chart-bar text-3xl mr-3"></i>
                    <h1 class="text-3xl font-bold">Thống kê ngành học</h1>
                </div>
                <nav class="flex space-x-4">
                    <a href="dashboard.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-home mr-1"></i>Trang chủ
                    </a>
                    <a href="my-profiles.php" class="text-white hover:text-blue-200 px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        <i class="fas fa-user mr-1"></i>Hồ sơ của tôi
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Overview Cards -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg card-glow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tổng hồ sơ</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalProfiles); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg card-glow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-lightbulb text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Tổng đề xuất</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($totalRecommendations); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg card-glow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-graduation-cap text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Ngành học</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo count($majorStats); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg card-glow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Trung bình/ngày</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo count($dailyStats) > 0 ? round(array_sum(array_column($dailyStats, 'daily_recommendations')) / count($dailyStats), 1) : 0; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid lg:grid-cols-2 gap-8 mb-8">
            <!-- Top Majors Chart -->
            <div class="bg-white rounded-xl shadow-lg card-glow p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-trophy mr-3 text-yellow-600"></i>
                    Top ngành học được quan tâm
                </h3>
                <div class="h-80">
                    <canvas id="majorsChart"></canvas>
                </div>
            </div>

            <!-- Daily Activity Chart -->
            <div class="bg-white rounded-xl shadow-lg card-glow p-6">
                <h3 class="text-xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-calendar-alt mr-3 text-blue-600"></i>
                    Hoạt động 7 ngày gần nhất
                </h3>
                <div class="h-80">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Statistics Table -->
        <div class="bg-white rounded-xl shadow-lg card-glow p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-6">
                <i class="fas fa-table mr-3 text-green-600"></i>
                Chi tiết thống kê ngành học
            </h3>
            
            <?php if (!empty($majorStats)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-medal mr-2"></i>Thứ hạng
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-graduation-cap mr-2"></i>Ngành học
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-chart-bar mr-2"></i>Số lần đề xuất
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-percentage mr-2"></i>Tỷ lệ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-star mr-2"></i>Độ tin cậy TB
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($majorStats as $index => $stat): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if ($index < 3): ?>
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full 
                                                    <?php echo $index === 0 ? 'bg-yellow-100 text-yellow-800' : 
                                                        ($index === 1 ? 'bg-gray-100 text-gray-800' : 'bg-orange-100 text-orange-800'); ?>">
                                                    <i class="fas fa-medal"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-gray-800">
                                                    <?php echo $index + 1; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($stat['major_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900 font-semibold">
                                            <?php echo number_format($stat['recommendation_count']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo round(($stat['recommendation_count'] / $totalRecommendations) * 100, 1); ?>%
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo round($stat['avg_confidence'] * 100, 1); ?>%
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-chart-bar text-4xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500">Chưa có dữ liệu thống kê</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Action Buttons -->
        <div class="bg-white rounded-xl shadow-lg card-glow p-6 mt-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6 text-center">
                <i class="fas fa-rocket mr-3 text-purple-600"></i>
                Hành động tiếp theo
            </h3>
            <div class="grid md:grid-cols-3 gap-6">
                <a href="ai-analysis.php"
                   class="bg-blue-600 text-white p-6 rounded-xl text-center hover:bg-blue-700 transition-colors">
                    <i class="fas fa-robot text-3xl mb-4"></i>
                    <h4 class="text-lg font-semibold mb-2">Phân tích ngành học</h4>
                    <p class="text-blue-100 text-sm">Tạo hồ sơ và nhận đề xuất ngành học</p>
                </a>

                <a href="my-profiles.php"
                   class="bg-green-600 text-white p-6 rounded-xl text-center hover:bg-green-700 transition-colors">
                    <i class="fas fa-user text-3xl mb-4"></i>
                    <h4 class="text-lg font-semibold mb-2">Hồ sơ của tôi</h4>
                    <p class="text-green-100 text-sm">Xem lại các phân tích đã thực hiện</p>
                </a>

                <a href="dashboard.php"
                   class="bg-purple-600 text-white p-6 rounded-xl text-center hover:bg-purple-700 transition-colors">
                    <i class="fas fa-home text-3xl mb-4"></i>
                    <h4 class="text-lg font-semibold mb-2">Trang chủ</h4>
                    <p class="text-purple-100 text-sm">Quay lại dashboard chính</p>
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
        // Top Majors Bar Chart
        const majorsCtx = document.getElementById('majorsChart').getContext('2d');
        new Chart(majorsCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chartLabels); ?>,
                datasets: [{
                    label: 'Số lần đề xuất',
                    data: <?php echo json_encode($chartData); ?>,
                    backgroundColor: <?php echo json_encode(array_slice($chartColors, 0, count($chartData))); ?>,
                    borderColor: <?php echo json_encode(array_slice($chartColors, 0, count($chartData))); ?>,
                    borderWidth: 1,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Số lần đề xuất: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                }
            }
        });

        // Daily Activity Line Chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($timeLabels); ?>,
                datasets: [{
                    label: 'Số đề xuất',
                    data: <?php echo json_encode($timeData); ?>,
                    borderColor: '#3B82F6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3B82F6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Số đề xuất: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
