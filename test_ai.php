<?php
require_once 'includes/ai_service.php';

// Cấu hình API Key (thay thế bằng API key thực tế của bạn)
$apiKey = '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';

echo "<h1>Test Kết nối AI - OpenRouter</h1>";

// Test 1: Kiểm tra kết nối
echo "<h2>1. Test kết nối cơ bản</h2>";
$aiService = getAIService($apiKey);
$testResult = $aiService->testConnection();

if ($testResult['success']) {
    echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
    echo "<strong>✅ Kết nối thành công!</strong><br>";
    echo "Model: " . $testResult['model'] . "<br>";
    echo "Response: " . $testResult['response'];
    echo "</div>";
} else {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<strong>❌ Lỗi kết nối:</strong><br>";
    echo $testResult['error'];
    echo "</div>";
}

// Test 2: Phân tích ngành học
echo "<h2>2. Test phân tích ngành học tại FPT Polytechnic</h2>";
$userData = [
    'interests' => 'Công nghệ, máy tính, lập trình',
    'skills' => 'Tư duy logic tốt, thích giải quyết vấn đề',
    'goals' => 'Muốn làm việc trong lĩnh vực công nghệ thông tin',
    'academic_level' => 'THPT'
];

$analysisResult = analyzeMajorWithAI($userData, $apiKey);

if ($analysisResult['success']) {
    echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
    echo "<strong>✅ Phân tích thành công!</strong><br>";
    echo "<h3>Đề xuất ngành học tại FPT Polytechnic:</h3>";
    
    foreach ($analysisResult['recommendations'] as $index => $rec) {
        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd;'>";
        echo "<strong>" . ($index + 1) . ". " . $rec['major'] . "</strong><br>";
        echo "Trường: " . $rec['university'] . "<br>";
        echo "Độ tin cậy: " . round($rec['confidence'] * 100) . "%<br>";
        echo "Lý do: " . $rec['reasoning'] . "<br>";
        if (isset($rec['career_prospects'])) {
            echo "Triển vọng: " . $rec['career_prospects'] . "<br>";
        }
        if (isset($rec['salary_range'])) {
            echo "Mức lương: " . $rec['salary_range'] . "<br>";
        }
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>";
    echo "<strong>⚠️ Sử dụng đề xuất fallback:</strong><br>";
    echo $analysisResult['error'] . "<br><br>";
    
    echo "<h3>Đề xuất ngành học tại FPT Polytechnic (Fallback):</h3>";
    foreach ($analysisResult['recommendations'] as $index => $rec) {
        echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd;'>";
        echo "<strong>" . ($index + 1) . ". " . $rec['major'] . "</strong><br>";
        echo "Trường: " . $rec['university'] . "<br>";
        echo "Độ tin cậy: " . round($rec['confidence'] * 100) . "%<br>";
        echo "Lý do: " . $rec['reasoning'] . "<br>";
        echo "Triển vọng: " . $rec['career_prospects'] . "<br>";
        echo "Mức lương: " . $rec['salary_range'] . "<br>";
        echo "</div>";
    }
    echo "</div>";
}

// Test 3: Hiển thị các model có sẵn
echo "<h2>3. Các model AI có sẵn</h2>";
$models = $aiService->getAvailableModels();
echo "<ul>";
foreach ($models as $modelId => $modelName) {
    echo "<li><strong>" . $modelId . "</strong> - " . $modelName . "</li>";
}
echo "</ul>";

// Hướng dẫn cấu hình
echo "<h2>4. Hướng dẫn cấu hình</h2>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 5px;'>";
echo "<h3>Để sử dụng AI Service:</h3>";
echo "<ol>";
echo "<li>Đăng ký tài khoản tại <a href='https://openrouter.ai' target='_blank'>OpenRouter</a></li>";
echo "<li>Lấy API key từ dashboard</li>";
echo "<li>Cập nhật API key trong file <code>includes/ai_service.php</code></li>";
echo "<li>Hoặc truyền API key khi gọi hàm: <code>analyzeMajorWithAI(\$userData, 'your_api_key')</code></li>";
echo "</ol>";
echo "</div>";

// Thông tin về FPT Polytechnic
echo "<h2>5. Thông tin về Cao đẳng FPT Polytechnic</h2>";
echo "<div style='background: #e8f4fd; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Cao đẳng FPT Polytechnic</strong> là trường cao đẳng thuộc Tập đoàn FPT:</p>";
echo "<ul>";
echo "<li>✅ Đào tạo theo mô hình thực hành 70%</li>";
echo "<li>✅ Chương trình học 2.5 năm</li>";
echo "<li>✅ Có nhiều cơ sở tại Hà Nội, TP.HCM, Đà Nẵng, Cần Thơ</li>";
echo "<li>✅ Đảm bảo việc làm sau khi tốt nghiệp</li>";
echo "<li>✅ Các ngành chính: Ứng dụng phần mềm, Thiết kế đồ họa, Quản trị kinh doanh, Marketing số, v.v.</li>";
echo "</ul>";
echo "</div>";

// Danh sách các ngành học tại FPT Polytechnic
echo "<h2>6. Các ngành học tại FPT Polytechnic</h2>";
echo "<div style='background: #f0f8ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>Ngành Công nghệ thông tin:</h3>";
echo "<ul>";
echo "<li>Ứng dụng phần mềm</li>";
echo "<li>Lập trình máy tính</li>";
echo "<li>Thiết kế và phát triển web</li>";
echo "<li>Phát triển ứng dụng di động</li>";
echo "</ul>";

echo "<h3>Ngành Thiết kế và Nghệ thuật:</h3>";
echo "<ul>";
echo "<li>Thiết kế đồ họa</li>";
echo "<li>Thiết kế web</li>";
echo "<li>Thiết kế UI/UX</li>";
echo "</ul>";

echo "<h3>Ngành Kinh doanh và Quản lý:</h3>";
echo "<ul>";
echo "<li>Quản trị kinh doanh</li>";
echo "<li>Marketing số</li>";
echo "<li>Quản trị khách sạn</li>";
echo "<li>Quản trị nhà hàng và dịch vụ ăn uống</li>";
echo "</ul>";
echo "</div>";
?>
