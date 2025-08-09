<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectWithMessage('index.php', 'Vui lòng đăng nhập', 'error');
}

$user = getCurrentUser();

// Xử lý xóa roadmap
if (isset($_POST['delete_roadmap'])) {
    $roadmapId = (int)$_POST['roadmap_id'];
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("DELETE FROM ai_learning_roadmaps WHERE id = ? AND user_id = ?");
        $stmt->execute([$roadmapId, $user['id']]);
        
        if ($stmt->rowCount() > 0) {
            redirectWithMessage('manage-roadmaps.php', 'Đã xóa lộ trình học tập thành công', 'success');
        } else {
            redirectWithMessage('manage-roadmaps.php', 'Không tìm thấy lộ trình để xóa', 'error');
        }
    } catch (PDOException $e) {
        redirectWithMessage('manage-roadmaps.php', 'Có lỗi xảy ra khi xóa lộ trình', 'error');
    }
}

// Lấy danh sách roadmap của user
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("
        SELECT r.*, sp.id as profile_id, sp.favorite_subject, sp.career_orientation
        FROM ai_learning_roadmaps r
        JOIN student_profiles sp ON r.profile_id = sp.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $roadmaps = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error in manage-roadmaps.php: " . $e->getMessage());
    $roadmaps = [];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý lộ trình học tập - NguoiBanAI</title>
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
                        <i class="fas fa-road text-purple-600 mr-2"></i>
                        Quản lý lộ trình học tập
                    </h1>
                </div>
                <nav class="flex space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-home mr-1"></i>Trang chủ
                    </a>
                    <a href="my-profiles.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-user mr-1"></i>Hồ sơ của tôi
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Roadmaps List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-list text-blue-600 mr-2"></i>
                    Danh sách lộ trình học tập
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Các lộ trình học tập đã được tạo từ AI
                </p>
            </div>
            
            <div class="p-6">
                <?php if (!empty($roadmaps)): ?>
                    <div class="space-y-6">
                        <?php foreach ($roadmaps as $roadmap): ?>
                            <?php $roadmapData = json_decode($roadmap['roadmap_data'], true); ?>
                            <div class="border border-gray-200 rounded-lg p-6 bg-gray-50">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <span class="bg-purple-600 text-white rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">
                                            <i class="fas fa-graduation-cap"></i>
                                        </span>
                                        <div>
                                            <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($roadmap['major_name']); ?></h3>
                                            <p class="text-sm text-gray-600">
                                                Tạo ngày: <?php echo formatDate($roadmap['created_at']); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <?php echo count($roadmapData['focus_subjects'] ?? []); ?> môn học
                                        </span>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                                            <?php echo count($roadmapData['skills_to_improve'] ?? []); ?> kỹ năng
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Quick Preview -->
                                <div class="grid md:grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-book text-blue-600 mr-2"></i>
                                            Môn học chính
                                        </h4>
                                        <div class="space-y-1">
                                            <?php foreach (array_slice($roadmapData['focus_subjects'] ?? [], 0, 3) as $subject): ?>
                                                <p class="text-sm text-gray-700">
                                                    • <?php echo htmlspecialchars($subject['subject']); ?>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h4 class="font-medium text-gray-900 mb-2">
                                            <i class="fas fa-chart-line text-green-600 mr-2"></i>
                                            Kỹ năng cần cải thiện
                                        </h4>
                                        <div class="space-y-1">
                                            <?php foreach (array_slice($roadmapData['skills_to_improve'] ?? [], 0, 3) as $skill): ?>
                                                <p class="text-sm text-gray-700">
                                                    • <?php echo htmlspecialchars($skill['skill']); ?>
                                                </p>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Actions -->
                                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                    <div class="flex items-center space-x-2">
                                        <a href="profile-detail.php?id=<?php echo $roadmap['profile_id']; ?>&roadmap=<?php echo $roadmap['id']; ?>" 
                                           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                            <i class="fas fa-eye mr-2"></i>
                                            Xem chi tiết
                                        </a>
                                        <a href="profile-detail.php?id=<?php echo $roadmap['profile_id']; ?>" 
                                           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                                            <i class="fas fa-edit mr-2"></i>
                                            Chỉnh sửa
                                        </a>
                                    </div>
                                    
                                    <form method="POST" class="inline" onsubmit="return confirm('Bạn có chắc muốn xóa lộ trình này?')">
                                        <input type="hidden" name="roadmap_id" value="<?php echo $roadmap['id']; ?>">
                                        <button type="submit" name="delete_roadmap" 
                                                class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">
                                            <i class="fas fa-trash mr-2"></i>
                                            Xóa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-road text-gray-400 text-6xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Chưa có lộ trình học tập nào</h3>
                        <p class="text-gray-600 mb-6">Tạo lộ trình học tập đầu tiên từ hồ sơ của bạn</p>
                        <a href="my-profiles.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Tạo lộ trình mới
                        </a>
                    </div>
                <?php endif; ?>
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
</body>
</html>
