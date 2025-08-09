<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/ai_service.php';

echo "<h1>Test Study Suggestion Feature</h1>";

// Test data
$testProfileData = [
    'tech_interest' => 9,
    'creativity' => 7,
    'communication' => 6,
    'favorite_subject' => 'Toán',
    'career_orientation' => 'Lập trình viên'
];

echo "<h2>Testing suggestMajorBasedOnProfile function:</h2>";
echo "<pre>";
print_r($testProfileData);
echo "</pre>";

// Test the function
$suggestedMajor = suggestMajorBasedOnProfile($testProfileData);
echo "<h3>Suggested Major: " . $suggestedMajor . "</h3>";

// Test fallback suggestion
echo "<h2>Testing generateFallbackStudySuggestion function:</h2>";
$fallbackSuggestion = generateFallbackStudySuggestion($suggestedMajor);
echo "<pre>";
print_r($fallbackSuggestion);
echo "</pre>";

// Test AI service
echo "<h2>Testing AI Service:</h2>";
try {
    $aiService = new AIService();
    $prompt = "Tạo gợi ý học tập cho ngành '{$suggestedMajor}' tại FPT Polytechnic. Trả về JSON với cấu trúc: {\"focus_subjects\": [{\"subject\": \"Tên môn học\", \"reason\": \"Lý do cần tập trung\", \"difficulty\": \"Độ khó\"}], \"skills_to_improve\": [{\"skill\": \"Tên kỹ năng\", \"current_level\": \"Mức độ hiện tại\", \"target_level\": \"Mức độ mục tiêu\", \"improvement_method\": \"Cách cải thiện\"}]}";
    
    $result = $aiService->sendRequest($prompt);
    
    echo "<h3>AI Response:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<h3>Parsed JSON:</h3>";
        preg_match('/\{.*\}/s', $result['content'], $matches);
        if (!empty($matches)) {
            $studyJson = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo "<pre>";
                print_r($studyJson);
                echo "</pre>";
            } else {
                echo "<p style='color: red;'>JSON parse error: " . json_last_error_msg() . "</p>";
            }
        } else {
            echo "<p style='color: orange;'>No JSON found in response</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

function suggestMajorBasedOnProfile($profileData) {
    // Dựa vào thông tin profile để gợi ý ngành
    $techInterest = (int)$profileData['tech_interest'];
    $creativity = (int)$profileData['creativity'];
    $communication = (int)$profileData['communication'];
    $favoriteSubject = strtolower($profileData['favorite_subject']);
    $careerOrientation = strtolower($profileData['career_orientation']);
    
    // Logic gợi ý ngành dựa trên thông tin
    if ($techInterest >= 8 || strpos($favoriteSubject, 'toán') !== false || strpos($careerOrientation, 'lập trình') !== false) {
        return 'Ứng dụng phần mềm';
    } elseif ($creativity >= 8 || strpos($careerOrientation, 'thiết kế') !== false) {
        return 'Thiết kế đồ họa';
    } elseif ($communication >= 8 || strpos($careerOrientation, 'kinh doanh') !== false) {
        return 'Quản trị kinh doanh';
    } elseif ($techInterest >= 7) {
        return 'Công nghệ thông tin';
    } else {
        return 'Quản trị kinh doanh'; // Default
    }
}

function generateFallbackStudySuggestion($major) {
    $suggestions = [
        'Ứng dụng phần mềm' => [
            'focus_subjects' => [
                ['subject' => 'Lập trình Java', 'reason' => 'Nền tảng phát triển ứng dụng', 'difficulty' => '8/10'],
                ['subject' => 'Cơ sở dữ liệu', 'reason' => 'Quản lý dữ liệu hiệu quả', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Tư duy logic', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Luyện giải bài tập lập trình']
            ]
        ],
        'Thiết kế đồ họa' => [
            'focus_subjects' => [
                ['subject' => 'Thiết kế đồ họa cơ bản', 'reason' => 'Nền tảng về màu sắc và bố cục', 'difficulty' => '6/10'],
                ['subject' => 'Photoshop nâng cao', 'reason' => 'Công cụ chính cho thiết kế', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Khả năng sáng tạo', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Tham khảo portfolio, thực hành thiết kế']
            ]
        ],
        'Quản trị kinh doanh' => [
            'focus_subjects' => [
                ['subject' => 'Marketing cơ bản', 'reason' => 'Hiểu về thị trường và khách hàng', 'difficulty' => '6/10'],
                ['subject' => 'Quản lý dự án', 'reason' => 'Kỹ năng lãnh đạo và tổ chức', 'difficulty' => '7/10']
            ],
            'skills_to_improve' => [
                ['skill' => 'Kỹ năng giao tiếp', 'current_level' => 'Trung bình', 'target_level' => 'Cao', 'improvement_method' => 'Tham gia thuyết trình, networking']
            ]
        ]
    ];
    
    return $suggestions[$major] ?? $suggestions['Ứng dụng phần mềm'];
}
?>
