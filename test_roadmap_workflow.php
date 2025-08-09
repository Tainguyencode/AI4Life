<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/ai_service.php';

echo "<h1>Test Roadmap Workflow</h1>";

// Test database connection
try {
    $pdo = getConnection();
    echo "<h2>✅ Database connection successful</h2>";
} catch (Exception $e) {
    echo "<h2>❌ Database connection failed</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    exit;
}

// Test if user is logged in
if (!isLoggedIn()) {
    echo "<h2>❌ User not logged in</h2>";
    echo "<p>Please login first</p>";
    exit;
}

$user = getCurrentUser();
echo "<h2>✅ User logged in</h2>";
echo "<p>User: " . htmlspecialchars($user['fullname']) . " (ID: " . $user['id'] . ")</p>";

// Test profile ID
$profileId = (int)($_GET['id'] ?? 0);
echo "<h2>Profile ID: " . $profileId . "</h2>";

if (!$profileId) {
    echo "<h2>❌ No profile ID provided</h2>";
    exit;
}

// Test fetching profile
try {
    $stmt = $pdo->prepare("SELECT sp.*, u.fullname FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.id = ? AND sp.user_id = ?");
    $stmt->execute([$profileId, $user['id']]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        echo "<h2>❌ Profile not found</h2>";
        exit;
    }
    
    echo "<h2>✅ Profile found</h2>";
    echo "<p><strong>Profile ID:</strong> " . $profile['id'] . "</p>";
    echo "<p><strong>Interests:</strong> " . htmlspecialchars($profile['interests']) . "</p>";
    echo "<p><strong>Tech Interest:</strong> " . $profile['tech_interest'] . "/10</p>";
    echo "<p><strong>Creativity:</strong> " . $profile['creativity'] . "/10</p>";
    echo "<p><strong>Communication:</strong> " . $profile['communication'] . "/10</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error fetching profile</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    exit;
}

// Test AI Service
echo "<h2>Testing AI Service</h2>";
try {
    $aiService = new AIService();
    echo "<h3>✅ AI Service initialized</h3>";
    
    // Test simple request
    $testPrompt = "Trả về JSON: {\"test\": \"OK\"}";
    $result = $aiService->sendRequest($testPrompt);
    
    if ($result['success']) {
        echo "<h3>✅ AI Service working</h3>";
        echo "<p>Response: " . htmlspecialchars($result['content']) . "</p>";
    } else {
        echo "<h3>❌ AI Service not working</h3>";
        echo "<p>Error: " . htmlspecialchars($result['error'] ?? 'Unknown error') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error initializing AI Service</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Test roadmap generation
echo "<h2>Testing Roadmap Generation</h2>";
$testMajor = "Ứng dụng phần mềm";
echo "<p>Testing with major: " . $testMajor . "</p>";

try {
    $aiService = new AIService();
    $roadmapPrompt = "Tạo lộ trình học tập chi tiết cho ngành '{$testMajor}' tại FPT Polytechnic. Trả về JSON với cấu trúc chính xác: {\"focus_subjects\": [{\"subject\": \"Tên môn học\", \"reason\": \"Lý do cần tập trung\", \"difficulty\": \"Độ khó\"}], \"skills_to_improve\": [{\"skill\": \"Tên kỹ năng\", \"current_level\": \"Mức độ hiện tại\", \"target_level\": \"Mức độ mục tiêu\", \"improvement_method\": \"Cách cải thiện\"}], \"semester_roadmap\": [{\"semester\": \"Kỳ học\", \"subjects\": [\"Môn học\"], \"focus\": \"Trọng tâm\"}], \"career_opportunities\": [{\"position\": \"Vị trí\", \"company_type\": \"Loại công ty\", \"salary_range\": \"Mức lương\", \"requirements\": \"Yêu cầu\"}]}. Chỉ trả về JSON, không có text thêm.";
    
    $result = $aiService->sendRequest($roadmapPrompt);
    
    if ($result['success']) {
        echo "<h3>✅ AI Roadmap Response</h3>";
        echo "<pre>" . htmlspecialchars($result['content']) . "</pre>";
        
        // Test JSON parsing
        preg_match('/\{.*\}/s', $result['content'], $matches);
        if (!empty($matches)) {
            $roadmapJson = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<h3>✅ JSON Parsed Successfully</h3>";
                echo "<pre>";
                print_r($roadmapJson);
                echo "</pre>";
            } else {
                echo "<h3>❌ JSON Parse Error</h3>";
                echo "<p>Error: " . json_last_error_msg() . "</p>";
            }
        } else {
            echo "<h3>❌ No JSON found in response</h3>";
        }
    } else {
        echo "<h3>❌ AI Roadmap Failed</h3>";
        echo "<p>Error: " . htmlspecialchars($result['error'] ?? 'Unknown error') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Error generating roadmap</h3>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Test fallback roadmap
echo "<h2>Testing Fallback Roadmap</h2>";
$fallbackRoadmap = generateFallbackRoadmap($testMajor);
echo "<h3>✅ Fallback Roadmap Generated</h3>";
echo "<pre>";
print_r($fallbackRoadmap);
echo "</pre>";

echo "<h2>🔧 Test completed</h2>";
echo "<p><a href='profile-detail.php?id=" . $profileId . "'>Back to Profile Detail</a></p>";

function generateFallbackRoadmap($major) {
    $roadmaps = [
        'Ứng dụng phần mềm' => [
            'major' => 'Ứng dụng phần mềm',
            'focus_subjects' => [
                ['subject' => 'Lập trình Java', 'reason' => 'Nền tảng phát triển ứng dụng', 'difficulty' => '8/10'],
                ['subject' => 'Cơ sở dữ liệu', 'reason' => 'Quản lý dữ liệu hiệu quả', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Tư duy logic', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Luyện giải bài tập lập trình']
            ],
            'semester_roadmap' => [
                ['semester' => 'Kỳ 1-2', 'subjects' => ['Tin học cơ sở', 'Lập trình C++'], 'focus' => 'Nền tảng lập trình'],
                ['semester' => 'Kỳ 3-4', 'subjects' => ['Lập trình Java', 'Cơ sở dữ liệu'], 'focus' => 'Phát triển ứng dụng']
            ],
            'career_opportunities' => [
                ['position' => 'Lập trình viên Java', 'company_type' => 'Công ty phần mềm', 'salary_range' => '15-30 triệu VNĐ/tháng', 'requirements' => 'Java, Spring Framework']
            ]
        ],
        'Thiết kế đồ họa' => [
            'major' => 'Thiết kế đồ họa',
            'focus_subjects' => [
                ['subject' => 'Thiết kế đồ họa cơ bản', 'reason' => 'Nền tảng về màu sắc và bố cục', 'difficulty' => '6/10'],
                ['subject' => 'Photoshop nâng cao', 'reason' => 'Công cụ chính cho thiết kế', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Khả năng sáng tạo', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Tham khảo portfolio, thực hành thiết kế']
            ],
            'semester_roadmap' => [
                ['semester' => 'Kỳ 1-2', 'subjects' => ['Mỹ thuật cơ bản', 'Photoshop cơ bản'], 'focus' => 'Xây dựng nền tảng mỹ thuật'],
                ['semester' => 'Kỳ 3-4', 'subjects' => ['Illustrator', 'Thiết kế UI/UX'], 'focus' => 'Phát triển kỹ năng chuyên nghiệp']
            ],
            'career_opportunities' => [
                ['position' => 'Graphic Designer', 'company_type' => 'Agency', 'salary_range' => '10-25 triệu VNĐ/tháng', 'requirements' => 'Thành thạo Photoshop, Illustrator']
            ]
        ],
        'Quản trị kinh doanh' => [
            'major' => 'Quản trị kinh doanh',
            'focus_subjects' => [
                ['subject' => 'Marketing cơ bản', 'reason' => 'Hiểu về thị trường và khách hàng', 'difficulty' => '6/10'],
                ['subject' => 'Quản lý dự án', 'reason' => 'Kỹ năng lãnh đạo và tổ chức', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Kỹ năng giao tiếp', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Tham gia thuyết trình, networking']
            ],
            'semester_roadmap' => [
                ['semester' => 'Kỳ 1-2', 'subjects' => ['Kinh tế vi mô', 'Marketing cơ bản'], 'focus' => 'Xây dựng nền tảng kinh doanh'],
                ['semester' => 'Kỳ 3-4', 'subjects' => ['Digital Marketing', 'Quản lý dự án'], 'focus' => 'Phát triển kỹ năng kinh doanh số']
            ],
            'career_opportunities' => [
                ['position' => 'Marketing Specialist', 'company_type' => 'Công ty marketing', 'salary_range' => '8-20 triệu VNĐ/tháng', 'requirements' => 'Digital marketing, kinh nghiệm thực tế']
            ]
        ]
    ];
    
    return $roadmaps[$major] ?? $roadmaps['Ứng dụng phần mềm'];
}
?>
