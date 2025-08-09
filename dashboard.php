<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$user = getCurrentUser();
$stats = getProfileStats($user['id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - NguoiBanAI</title>
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
                    <a href="index.php?logout=1" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>Đăng xuất
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg p-6 mb-8">
            <h1 class="text-3xl font-bold mb-2">Chào mừng trở lại!</h1>
            <p class="text-lg opacity-90">Hệ thống tư vấn ngành học tại Cao đẳng FPT Polytechnic</p>
        </div>

        <!-- User Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                <i class="fas fa-user-circle mr-2 text-blue-600"></i>Thông tin cá nhân
            </h2>
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Họ và tên:</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($user['fullname']); ?></p>
                </div>
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Email:</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Số điện thoại:</h3>
                    <p class="text-gray-600"><?php echo htmlspecialchars($user['phone'] ?: 'Chưa cập nhật'); ?></p>
                </div>
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Ngày tham gia:</h3>
                    <p class="text-gray-600"><?php echo formatDate($user['created_at']); ?></p>
                </div>
            </div>
            <div class="mt-6">
                <a href="profile.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors inline-block">
                    <i class="fas fa-edit mr-2"></i>Cập nhật thông tin
                </a>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-user-edit text-blue-600 text-2xl mr-3"></i>
                    <h3 class="text-xl font-bold text-gray-800">Cập nhật hồ sơ</h3>
                </div>
                <p class="text-gray-600 mb-4">Cập nhật thông tin cá nhân và liên hệ</p>
                <a href="update-profile.php" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors inline-block">
                    <i class="fas fa-edit mr-2"></i>Cập nhật
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-robot text-green-600 text-2xl mr-3"></i>
                    <h3 class="text-xl font-bold text-gray-800">Khám phá ngành học ngay với Fpoly</h3>
                </div>
                <p class="text-gray-600 mb-4">Khám phá ngành học phù hợp tại FPT Polytechnic</p>
                <a href="ai-analysis.php" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors inline-block">
                    <i class="fas fa-magic mr-2"></i>Phân tích ngay
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-history text-purple-600 text-2xl mr-3"></i>
                    <h3 class="text-xl font-bold text-gray-800">Lịch sử phân tích</h3>
                </div>
                <p class="text-gray-600 mb-4">Xem lại các lần phân tích trước đó</p>
                <a href="my-profiles.php" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition-colors inline-block">
                    <i class="fas fa-clock mr-2"></i>Xem lịch sử
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-lock text-red-600 text-2xl mr-3"></i>
                    <h3 class="text-xl font-bold text-gray-800">Đổi mật khẩu</h3>
                </div>
                <p class="text-gray-600 mb-4">Thay đổi mật khẩu để bảo mật tài khoản</p>
                <a href="change-password.php" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors inline-block">
                    <i class="fas fa-key mr-2"></i>Đổi mật khẩu
                </a>
            </div>
        </div>

        <!-- AI Analysis Feature -->
        <div class="bg-gradient-to-r from-green-500 to-blue-600 text-white rounded-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2">
                        <i class="fas fa-robot mr-3"></i>
                        Khám phá ngành học tại FPT Polytechnic
                    </h2>
                    <p class="text-lg opacity-90 mb-4">
                        Cung cấp thông tin chi tiết để AI đưa ra đề xuất ngành học phù hợp tại Cao đẳng FPT Polytechnic
                    </p>
                    <ul class="space-y-2 text-sm opacity-90">
                        <li><i class="fas fa-check mr-2"></i>Phân tích điểm số các môn học</li>
                        <li><i class="fas fa-check mr-2"></i>Đánh giá kỹ năng và tố chất</li>
                        <li><i class="fas fa-check mr-2"></i>Đề xuất 3 ngành học phù hợp nhất tại FPT Polytechnic</li>
                        <li><i class="fas fa-check mr-2"></i>Thông tin triển vọng nghề nghiệp</li>
                    </ul>
                </div>
                <div class="text-center">
                    <a href="ai-analysis.php" 
                       class="bg-white text-green-600 px-6 py-3 rounded-lg hover:bg-gray-100 transition-colors inline-block font-bold text-lg">
                        <i class="fas fa-rocket mr-2"></i>
                        Bắt đầu ngay!
                    </a>
                </div>
            </div>
        </div>

        <!-- FPT Polytechnic Information -->
        <div class="bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-lg p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold mb-2">
                        <i class="fas fa-university mr-3"></i>
                        Về Cao đẳng FPT Polytechnic
                    </h2>
                    <p class="text-lg opacity-90 mb-4">
                        Trường cao đẳng thuộc Tập đoàn FPT với mô hình đào tạo thực hành 70%
                    </p>
                    <ul class="space-y-2 text-sm opacity-90">
                        <li><i class="fas fa-graduation-cap mr-2"></i>Chương trình học 2.5 năm</li>
                        <li><i class="fas fa-map-marker-alt mr-2"></i>Nhiều cơ sở: Hà Nội, TP.HCM, Đà Nẵng, Cần Thơ</li>
                        <li><i class="fas fa-briefcase mr-2"></i>Đảm bảo việc làm sau khi tốt nghiệp</li>
                        <li><i class="fas fa-code mr-2"></i>Chuyên ngành: CNTT, Thiết kế, Kinh doanh</li>
                    </ul>
                </div>
                <div class="text-center">
                    <a href="https://caodang.fpt.edu.vn" target="_blank" 
                       class="bg-white text-orange-600 px-6 py-3 rounded-lg hover:bg-gray-100 transition-colors inline-block font-bold text-lg">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Tìm hiểu thêm
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid md:grid-cols-4 gap-6 mt-8">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-users text-blue-600 text-3xl mb-2"></i>
                <h3 class="text-2xl font-bold text-gray-800">1000+</h3>
                <p class="text-gray-600">Người dùng đã đăng ký</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-chart-line text-green-600 text-3xl mb-2"></i>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_profiles']; ?></h3>
                <p class="text-gray-600">Lần phân tích của bạn</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-university text-purple-600 text-3xl mb-2"></i>
                <h3 class="text-2xl font-bold text-gray-800">4</h3>
                <p class="text-gray-600">Cơ sở FPT Polytechnic</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-graduation-cap text-indigo-600 text-3xl mb-2"></i>
                <h3 class="text-2xl font-bold text-gray-800">15+</h3>
                <p class="text-gray-600">Ngành học tại FPT Polytechnic</p>
            </div>
        </div>
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
