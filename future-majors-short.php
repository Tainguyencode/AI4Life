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
        'current_demand' => 95,
        'future_demand' => 98,
        'growth_rate' => 15,
        'trend' => 'up',
        'color' => 'blue'
    ],
    [
        'name' => 'Thiết kế đồ họa',
        'code' => 'TKDH',
        'current_demand' => 85,
        'future_demand' => 92,
        'growth_rate' => 12,
        'trend' => 'up',
        'color' => 'green'
    ],
    [
        'name' => 'Quản trị kinh doanh',
        'code' => 'QTKD',
        'current_demand' => 80,
        'future_demand' => 88,
        'growth_rate' => 10,
        'trend' => 'up',
        'color' => 'purple'
    ],
    [
        'name' => 'Truyền thông đa phương tiện',
        'code' => 'TTĐPT',
        'current_demand' => 75,
        'future_demand' => 85,
        'growth_rate' => 13,
        'trend' => 'up',
        'color' => 'pink'
    ],
    [
        'name' => 'Kế toán',
        'code' => 'KT',
        'current_demand' => 70,
        'future_demand' => 75,
        'growth_rate' => 5,
        'trend' => 'stable',
        'color' => 'yellow'
    ],
    [
        'name' => 'Du lịch và lữ hành',
        'code' => 'DL',
        'current_demand' => 60,
        'future_demand' => 70,
        'growth_rate' => 8,
        'trend' => 'up',
        'color' => 'orange'
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
                    <a href="future-majors.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-expand mr-1"></i>Xem chi tiết
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Hero Section -->
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg p-6 mb-8">
            <div class="text-center">
                <h2 class="text-2xl font-bold mb-3">
                    <i class="fas fa-crystal-ball mr-3"></i>
                    Dự đoán ngành học hot 3-5 năm tới
                </h2>
                <p class="text-lg opacity-90 mb-4">
                    Phân tích xu hướng thị trường và dự đoán nhu cầu nhân lực
                </p>
                <div class="grid md:grid-cols-4 gap-4 text-center">
                    <div class="bg-white bg-opacity-20 rounded-lg p-3">
                        <div class="text-xl font-bold"><?php echo $total_majors; ?></div>
                        <div class="text-xs">Ngành học</div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-lg p-3">
                        <div class="text-xl font-bold"><?php echo $high_demand; ?></div>
                        <div class="text-xs">Nhu cầu cao</div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-lg p-3">
                        <div class="text-xl font-bold"><?php echo $growing_majors; ?></div>
                        <div class="text-xs">Đang tăng trưởng</div>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-lg p-3">
                        <div class="text-xl font-bold"><?php echo round($avg_growth, 1); ?>%</div>
                        <div class="text-xs">Tăng trưởng TB</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-chart-line mr-2"></i>
                    Biểu đồ phân tích
                </h3>
                <button id="toggleCharts" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors font-medium">
                    <i class="fas fa-eye mr-2"></i>Hiện/Ẩn biểu đồ
                </button>
            </div>
            <div id="chartsContainer" class="grid md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-md font-semibold text-gray-700 mb-3">So sánh nhu cầu hiện tại vs tương lai</h4>
                    <div style="height: 180px;">
                        <canvas id="demandChart"></canvas>
                    </div>
                </div>
                <div>
                    <h4 class="text-md font-semibold text-gray-700 mb-3">Tỷ lệ tăng trưởng theo ngành</h4>
                    <div style="height: 180px;">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Majors Grid -->
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach (array_slice($majors, 0, 6) as $index => $major): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-<?php echo $major['color']; ?>-500 to-<?php echo $major['color']; ?>-600 text-white p-4">
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
                    <!-- Demand Progress -->
                    <div class="mb-3">
                        <div class="flex justify-between text-xs mb-1">
                            <span>Hiện tại: <?php echo $major['current_demand']; ?>%</span>
                            <span>Tương lai: <?php echo $major['future_demand']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $major['current_demand']; ?>%"></div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                            <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $major['future_demand']; ?>%"></div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div class="text-center">
                            <div class="text-lg font-bold text-blue-600"><?php echo $major['growth_rate']; ?>%</div>
                            <div class="text-xs text-gray-600">Tăng trưởng</div>
                        </div>
                        <div class="text-center">
                            <div class="text-sm font-bold text-green-600">
                                <?php if ($major['trend'] === 'up'): ?>
                                    <i class="fas fa-arrow-up"></i> Tăng
                                <?php elseif ($major['trend'] === 'stable'): ?>
                                    <i class="fas fa-minus"></i> Ổn định
                                <?php else: ?>
                                    <i class="fas fa-arrow-down"></i> Giảm
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-600">Xu hướng</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quick Insights -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">
                <i class="fas fa-lightbulb mr-2"></i>
                Nhận định nhanh
            </h3>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Top 3 ngành hot nhất:</h4>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                            <span>Công nghệ thông tin (98% nhu cầu)</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-medal text-gray-400 mr-2"></i>
                            <span>Thiết kế đồ họa (92% nhu cầu)</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-award text-orange-500 mr-2"></i>
                            <span>Quản trị kinh doanh (88% nhu cầu)</span>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-800 mb-2">Xu hướng chính:</h4>
                    <ul class="space-y-1 text-sm text-gray-600">
                        <li class="flex items-center">
                            <i class="fas fa-arrow-up text-green-600 mr-2"></i>
                            <span>5/6 ngành đang tăng trưởng</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-chart-line text-blue-600 mr-2"></i>
                            <span>Tăng trưởng TB: <?php echo round($avg_growth, 1); ?>%</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-star text-yellow-500 mr-2"></i>
                            <span>4 ngành có nhu cầu >85%</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="text-center mt-8">
            <a href="future-majors.php" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-3 rounded-lg hover:from-blue-700 hover:to-purple-700 transition-all inline-block font-bold text-lg shadow-lg">
                <i class="fas fa-expand mr-2"></i>
                Xem phân tích chi tiết đầy đủ
            </a>
        </div>
    </div>

    <script>
        // Toggle charts functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toggleButton = document.getElementById('toggleCharts');
            const chartsContainer = document.getElementById('chartsContainer');
            
            if (toggleButton && chartsContainer) {
                toggleButton.addEventListener('click', function() {
                    if (chartsContainer.style.display === 'none') {
                        chartsContainer.style.display = 'grid';
                        this.innerHTML = '<i class="fas fa-eye mr-2"></i>Hiện/Ẩn biểu đồ';
                    } else {
                        chartsContainer.style.display = 'none';
                        this.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>Hiện/Ẩn biểu đồ';
                    }
                });
            }
        });

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
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: {
                                size: 10
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 8,
                            font: {
                                size: 10
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 5,
                        bottom: 5
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
                        position: 'bottom',
                        labels: {
                            boxWidth: 8,
                            padding: 6,
                            font: {
                                size: 9
                            }
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 5,
                        bottom: 5
                    }
                }
            }
        });
    </script>
</body>
</html>
