<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    redirectWithMessage('index.php', 'Vui lòng đăng nhập để cập nhật hồ sơ', 'error');
}

$user = getCurrentUser();
$message = '';
$messageType = '';

// Xử lý form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitizeInput($_POST['fullname'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($fullname)) {
        $errors[] = 'Họ tên không được để trống';
    }
    
    if (empty($email)) {
        $errors[] = 'Email không được để trống';
    } elseif (!validateEmail($email)) {
        $errors[] = 'Email không hợp lệ';
    }
    
    // Kiểm tra email đã tồn tại chưa (trừ email hiện tại của user)
    if ($email !== $user['email'] && emailExists($email)) {
        $errors[] = 'Email đã được sử dụng bởi tài khoản khác';
    }
    
    // Nếu muốn đổi mật khẩu
    if (!empty($newPassword)) {
        if (empty($currentPassword)) {
            $errors[] = 'Vui lòng nhập mật khẩu hiện tại';
        } elseif (!verifyPassword($currentPassword, $user['password'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng';
        }
        
        if (strlen($newPassword) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }
    }
    
    // Nếu không có lỗi, cập nhật thông tin
    if (empty($errors)) {
        try {
            $pdo = getConnection();
            
            // Cập nhật thông tin cơ bản
            $sql = "UPDATE users SET fullname = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fullname, $email, $phone, $user['id']]);
            
            // Nếu có đổi mật khẩu
            if (!empty($newPassword)) {
                $hashedPassword = hashPassword($newPassword);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$hashedPassword, $user['id']]);
            }
            
            // Cập nhật session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'fullname' => $fullname,
                'email' => $email,
                'phone' => $phone
            ];
            
            $message = 'Cập nhật hồ sơ thành công!';
            $messageType = 'success';
            
            // Refresh user data
            $user = getCurrentUser();
            
        } catch (PDOException $e) {
            $message = 'Lỗi cập nhật hồ sơ: ' . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $errors);
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật hồ sơ - NguoiBanAI</title>
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
                        <i class="fas fa-user-edit text-blue-600 mr-2"></i>
                        Cập nhật hồ sơ
                    </h1>
                </div>
                <nav class="flex space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-home mr-1"></i>Trang chủ
                    </a>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 px-3 py-2 rounded-md text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i>Đăng xuất
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Message -->
        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm"><?php echo $message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Profile Update Form -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-medium text-gray-900">
                    <i class="fas fa-user-cog text-blue-600 mr-2"></i>
                    Thông tin cá nhân
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Cập nhật thông tin cá nhân và thay đổi mật khẩu
                </p>
            </div>

            <form method="POST" class="p-6 space-y-6">
                <!-- Personal Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fullname" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>Họ và tên
                        </label>
                        <input type="text" 
                               id="fullname" 
                               name="fullname" 
                               value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-1"></i>Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               required>
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-phone mr-1"></i>Số điện thoại
                        </label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               placeholder="0123456789">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-calendar mr-1"></i>Ngày tham gia
                        </label>
                        <div class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-600">
                            <?php echo date('d/m/Y', strtotime($user['created_at'] ?? 'now')); ?>
                        </div>
                    </div>
                </div>

                <!-- Password Change Section -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-md font-medium text-gray-900 mb-4">
                        <i class="fas fa-lock text-orange-600 mr-2"></i>
                        Thay đổi mật khẩu
                    </h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Để thay đổi mật khẩu, vui lòng điền thông tin bên dưới. Nếu không muốn thay đổi, hãy để trống.
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Mật khẩu hiện tại
                            </label>
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="Nhập mật khẩu hiện tại">
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Mật khẩu mới
                            </label>
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="Tối thiểu 6 ký tự">
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Xác nhận mật khẩu
                            </label>
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                   placeholder="Nhập lại mật khẩu mới">
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <a href="dashboard.php" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Quay lại
                    </a>

                    <div class="flex space-x-3">
                        <button type="reset" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            <i class="fas fa-undo mr-2"></i>
                            Làm mới
                        </button>
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i>
                            Cập nhật hồ sơ
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Additional Information -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Lưu ý</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc list-inside space-y-1">
                            <li>Email sẽ được sử dụng để đăng nhập vào hệ thống</li>
                            <li>Mật khẩu phải có ít nhất 6 ký tự</li>
                            <li>Thông tin cá nhân sẽ được bảo mật theo quy định</li>
                            <li>Bạn có thể cập nhật thông tin bất cứ lúc nào</li>
                        </ul>
                    </div>
                </div>
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
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-50, .bg-red-50');
            messages.forEach(function(message) {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);

        // Password confirmation validation
        document.getElementById('new_password').addEventListener('input', function() {
            const newPassword = this.value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (confirmPassword && newPassword !== confirmPassword) {
                document.getElementById('confirm_password').setCustomValidity('Mật khẩu không khớp');
            } else {
                document.getElementById('confirm_password').setCustomValidity('');
            }
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword && newPassword !== confirmPassword) {
                this.setCustomValidity('Mật khẩu không khớp');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
