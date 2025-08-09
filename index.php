<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Kiểm tra nếu đã đăng nhập
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NguoiBanAI - Tìm Ngành Học Thông Minh</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">
                <i class="fas fa-graduation-cap text-blue-600"></i>
                NguoiBanAI
            </h1>
            <p class="text-lg text-gray-600">Hệ thống tìm ngành học thông minh với AI</p>
        </div>

        <!-- Main Content -->
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-xl overflow-hidden">
            <!-- Tab Navigation -->
            <div class="flex">
                <button id="login-tab" class="flex-1 py-3 px-4 text-center bg-blue-600 text-white font-medium transition-colors">
                    <i class="fas fa-sign-in-alt mr-2"></i>Đăng Nhập
                </button>
                <button id="register-tab" class="flex-1 py-3 px-4 text-center bg-gray-200 text-gray-700 font-medium transition-colors">
                    <i class="fas fa-user-plus mr-2"></i>Đăng Ký
                </button>
            </div>

            <!-- Login Form -->
            <div id="login-form" class="p-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Đăng Nhập</h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form action="auth/login.php" method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập email của bạn">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                            <i class="fas fa-lock mr-2"></i>Mật khẩu
                        </label>
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập mật khẩu">
                    </div>

                    <button type="submit" 
                            class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i>Đăng Nhập
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-question-circle mr-1"></i>Quên mật khẩu?
                    </a>
                </div>
            </div>

            <!-- Register Form -->
            <div id="register-form" class="p-6 hidden">
                <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Đăng Ký</h2>
                
                <form action="auth/register.php" method="POST">
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="fullname">
                            <i class="fas fa-user mr-2"></i>Họ và tên
                        </label>
                        <input type="text" id="fullname" name="fullname" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập họ và tên">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="reg-email">
                            <i class="fas fa-envelope mr-2"></i>Email
                        </label>
                        <input type="email" id="reg-email" name="email" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập email của bạn">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="reg-password">
                            <i class="fas fa-lock mr-2"></i>Mật khẩu
                        </label>
                        <input type="password" id="reg-password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập mật khẩu">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm-password">
                            <i class="fas fa-lock mr-2"></i>Xác nhận mật khẩu
                        </label>
                        <input type="password" id="confirm-password" name="confirm_password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Nhập lại mật khẩu">
                    </div>

                    <button type="submit" 
                            class="w-full bg-green-600 text-white font-bold py-2 px-4 rounded-md hover:bg-green-700 transition-colors">
                        <i class="fas fa-user-plus mr-2"></i>Đăng Ký
                    </button>
                </form>
            </div>
        </div>

        <!-- Features Section -->
        <div class="mt-12 grid md:grid-cols-3 gap-6 max-w-4xl mx-auto">
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <i class="fas fa-robot text-3xl text-blue-600 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-800 mb-2">AI Thông Minh</h3>
                <p class="text-gray-600">Sử dụng AI để phân tích và đề xuất ngành học phù hợp</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <i class="fas fa-chart-line text-3xl text-green-600 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Phân Tích Chi Tiết</h3>
                <p class="text-gray-600">Đánh giá toàn diện về sở thích và năng lực của bạn</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md text-center">
                <i class="fas fa-university text-3xl text-purple-600 mb-4"></i>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Thông Tin Đầy Đủ</h3>
                <p class="text-gray-600">Cung cấp thông tin chi tiết về các ngành học và trường đại học</p>
            </div>
        </div>
    </div>

    <script>
        // Tab switching functionality
        const loginTab = document.getElementById('login-tab');
        const registerTab = document.getElementById('register-tab');
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');

        loginTab.addEventListener('click', () => {
            loginTab.classList.remove('bg-gray-200', 'text-gray-700');
            loginTab.classList.add('bg-blue-600', 'text-white');
            registerTab.classList.remove('bg-blue-600', 'text-white');
            registerTab.classList.add('bg-gray-200', 'text-gray-700');
            loginForm.classList.remove('hidden');
            registerForm.classList.add('hidden');
        });

        registerTab.addEventListener('click', () => {
            registerTab.classList.remove('bg-gray-200', 'text-gray-700');
            registerTab.classList.add('bg-blue-600', 'text-white');
            loginTab.classList.remove('bg-blue-600', 'text-white');
            loginTab.classList.add('bg-gray-200', 'text-gray-700');
            registerForm.classList.remove('hidden');
            loginForm.classList.add('hidden');
        });
    </script>
</body>
</html>
