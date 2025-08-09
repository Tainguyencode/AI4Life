<?php
require_once 'includes/ai_service.php';

// Test data
$testData = [
    'interests' => 'Công nghệ, lập trình, game',
    'skills' => 'Tư duy logic tốt, thích máy tính',
    'math_score' => '8.5',
    'literature_score' => '7.0',
    'english_score' => '8.0',

    'favorite_subject' => 'Toán',
    'career_orientation' => 'Lập trình viên',
    'habits' => 'Thích chơi game, đọc sách công nghệ',
    'tech_interest' => '9',
    'creativity' => '7',
    'communication' => '6',
    'academic_level' => 'THPT'
];

echo "<h1>Test AI Service</h1>";

try {
    $aiService = new AIService();
    $result = $aiService->analyzeAndRecommend($testData);
    
    echo "<h2>Result:</h2>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<h2>Recommendations:</h2>";
        foreach ($result['recommendations'] as $index => $rec) {
            echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
            echo "<h3>" . ($index + 1) . ". " . htmlspecialchars($rec['major']) . "</h3>";
            echo "<p><strong>Confidence:</strong> " . round($rec['confidence'] * 100) . "%</p>";
            echo "<p><strong>Reasoning:</strong> " . htmlspecialchars($rec['reasoning']) . "</p>";
            echo "<p><strong>Career:</strong> " . htmlspecialchars($rec['career_prospects']) . "</p>";
            echo "<p><strong>Salary:</strong> " . htmlspecialchars($rec['salary_range']) . "</p>";
            echo "</div>";
        }
    } else {
        echo "<h2>Error:</h2>";
        echo "<p>" . htmlspecialchars($result['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Exception:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
