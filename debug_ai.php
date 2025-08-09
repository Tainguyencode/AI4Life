<?php
require_once 'includes/ai_service.php';

echo "<h1>Debug AI Service</h1>";

// Test simple prompt
$aiService = new AIService();
$prompt = "Đề xuất 3 ngành học tại FPT Polytechnic cho học sinh thích công nghệ. Trả về JSON: {\"recommendations\": [{\"major\": \"Tên ngành\", \"university\": \"Cao đẳng FPT Polytechnic\", \"confidence\": 0.8, \"reasoning\": \"Lý do\", \"career_prospects\": \"Triển vọng\", \"salary_range\": \"Mức lương\"}]}";

echo "<h2>Testing simple prompt...</h2>";
$result = $aiService->sendRequest($prompt);

echo "<h3>Raw Result:</h3>";
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['success']) {
    echo "<h3>Parsed Recommendations:</h3>";
    $parsed = $aiService->analyzeAndRecommend([
        'interests' => 'Công nghệ',
        'skills' => 'Tư duy logic',
        'math_score' => '8',
        'literature_score' => '7',
        'english_score' => '8',
        
        'favorite_subject' => 'Toán',
        'career_orientation' => 'Lập trình viên',
        'habits' => 'Thích máy tính',
        'tech_interest' => '9',
        'creativity' => '7',
        'communication' => '6',
        'academic_level' => 'THPT'
    ]);
    
    echo "<pre>";
    print_r($parsed);
    echo "</pre>";
}
?>
