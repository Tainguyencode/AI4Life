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

// Xử lý xóa profile
if (isset($_POST['delete_profile']) && isset($_POST['profile_id'])) {
    $profileId = (int)$_POST['profile_id'];
    if (deleteStudentProfile($profileId, $user['id'])) {
        redirectWithMessage('my-profiles.php', 'Đã xóa profile thành công!', 'success');
    } else {
        redirectWithMessage('my-profiles.php', 'Có lỗi xảy ra khi xóa!', 'error');
    }
}

// Lấy thống kê
$stats = getProfileStats($user['id']);
$profiles = getAllStudentProfiles($user['id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử phân tích - NguoiBanAI</title>
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

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-4">
                <i class="fas fa-history text-blue-600 mr-3"></i>
                Lịch sử phân tích AI
            </h1>
            <p class="text-lg text-gray-600">Xem lại các lần phân tích và kết quả đề xuất</p>
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

        <!-- Thống kê -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-chart-line text-blue-600 text-3xl mb-2"></i>
                <h3 class="text-2xl font-bold text-gray-800"><?php echo $stats['total_profiles']; ?></h3>
                <p class="text-gray-600">Lần phân tích</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-calendar text-green-600 text-3xl mb-2"></i>
                <h3 class="text-lg font-bold text-gray-800">
                    <?php echo $stats['last_analysis'] ? formatDate($stats['last_analysis']) : 'Chưa có'; ?>
                </h3>
                <p class="text-gray-600">Lần cuối phân tích</p>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <a href="ai-analysis.php" class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 py-3 rounded-lg hover:from-blue-700 hover:to-purple-700 transition-colors inline-block">
                    <i class="fas fa-plus mr-2"></i>Phân tích mới
                </a>
            </div>
        </div>

        <!-- Danh sách profiles -->
        <?php if (empty($profiles)): ?>
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fas fa-inbox text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-600 mb-2">Chưa có lần phân tích nào</h3>
            <p class="text-gray-500 mb-6">Hãy thực hiện phân tích AI đầu tiên để xem kết quả</p>
            <a href="ai-analysis.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-robot mr-2"></i>Bắt đầu phân tích
            </a>
        </div>
        <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($profiles as $profile): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-gray-800">
                            Phân tích #<?php echo $profile['id']; ?>
                        </h3>
                        <p class="text-gray-600">
                            <i class="fas fa-clock mr-2"></i>
                            <?php echo formatDate($profile['created_at']); ?>
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <a href="profile-detail.php?id=<?php echo $profile['id']; ?>" 
                           class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-eye mr-2"></i>Xem chi tiết
                        </a>
                        <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa profile này?')">
                            <input type="hidden" name="profile_id" value="<?php echo $profile['id']; ?>">
                            <button type="submit" name="delete_profile" 
                                    class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">
                                <i class="fas fa-trash mr-2"></i>Xóa
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Thông tin tóm tắt -->
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <h4 class="font-bold text-gray-700 mb-2">Thông tin cơ bản:</h4>
                        <div class="bg-gray-50 p-3 rounded">
                            <p><strong>Sở thích:</strong> <?php echo htmlspecialchars(substr($profile['interests'], 0, 50)) . (strlen($profile['interests']) > 50 ? '...' : ''); ?></p>
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
            </div>
            <?php endforeach; ?>
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

