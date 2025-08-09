<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/ai_service.php';

echo "<h1>Comprehensive Profile Detail Test</h1>";
echo "<style>
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>";

// Test 1: Check if user is logged in
echo "<h2>Test 1: User Authentication</h2>";
if (!isLoggedIn()) {
    echo "<p class='error'>❌ User not logged in</p>";
    exit;
}
$user = getCurrentUser();
echo "<p class='success'>✅ User logged in: " . htmlspecialchars($user['fullname']) . " (ID: " . $user['id'] . ")</p>";

// Test 2: Check profile ID
echo "<h2>Test 2: Profile ID</h2>";
$profileId = (int)($_GET['id'] ?? 0);
echo "<p class='info'>Profile ID: " . $profileId . "</p>";

if (!$profileId) {
    echo "<p class='error'>❌ No profile ID provided</p>";
    echo "<p>Add ?id=1 to URL to test with profile ID 1</p>";
    exit;
}

// Test 3: Database connection
echo "<h2>Test 3: Database Connection</h2>";
try {
    $pdo = getConnection();
    echo "<p class='success'>✅ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Test 4: Check if profile exists
echo "<h2>Test 4: Profile Existence</h2>";
try {
    $stmt = $pdo->prepare("SELECT sp.*, u.fullname FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.id = ? AND sp.user_id = ?");
    $stmt->execute([$profileId, $user['id']]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        echo "<p class='error'>❌ Profile not found or access denied</p>";
        
        // Check if profile exists at all
        $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE id = ?");
        $stmt->execute([$profileId]);
        $anyProfile = $stmt->fetch();
        
        if ($anyProfile) {
            echo "<p class='warning'>⚠️ Profile exists but belongs to user ID: " . $anyProfile['user_id'] . "</p>";
        } else {
            echo "<p class='error'>❌ Profile with ID " . $profileId . " does not exist</p>";
            
            // Show available profiles
            $stmt = $pdo->prepare("SELECT id, user_id, created_at FROM student_profiles WHERE user_id = ? LIMIT 5");
            $stmt->execute([$user['id']]);
            $availableProfiles = $stmt->fetchAll();
            
            if (!empty($availableProfiles)) {
                echo "<p class='info'>Available profiles for this user:</p>";
                foreach ($availableProfiles as $prof) {
                    echo "<p>• Profile ID: " . $prof['id'] . " (Created: " . $prof['created_at'] . ")</p>";
                }
            } else {
                echo "<p class='warning'>⚠️ No profiles found for this user</p>";
            }
        }
        exit;
    }
    
    echo "<p class='success'>✅ Profile found</p>";
    echo "<h3>Profile Data:</h3>";
    echo "<pre>" . print_r($profile, true) . "</pre>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 5: Check AI recommendations
echo "<h2>Test 5: AI Recommendations</h2>";
try {
    $stmt = $pdo->prepare("SELECT * FROM ai_recommendations WHERE profile_id = ? AND user_id = ? ORDER BY recommendation_order ASC");
    $stmt->execute([$profileId, $user['id']]);
    $recommendations = $stmt->fetchAll();
    
    if (!empty($recommendations)) {
        echo "<p class='success'>✅ Found " . count($recommendations) . " AI recommendations</p>";
        echo "<h3>Recommendations Data:</h3>";
        echo "<pre>" . print_r($recommendations, true) . "</pre>";
    } else {
        echo "<p class='warning'>⚠️ No AI recommendations found in database</p>";
        
        // Test AI service to generate recommendations
        echo "<h3>Testing AI Service:</h3>";
        try {
            $aiService = new AIService();
            $result = $aiService->analyzeAndRecommend($profile);
            
            if ($result['success']) {
                echo "<p class='success'>✅ AI service working, generated " . count($result['recommendations']) . " recommendations</p>";
                echo "<pre>" . print_r($result['recommendations'], true) . "</pre>";
            } else {
                echo "<p class='error'>❌ AI service failed: " . ($result['error'] ?? 'Unknown error') . "</p>";
                if (isset($result['raw_content'])) {
                    echo "<p class='info'>Raw AI response:</p>";
                    echo "<pre>" . htmlspecialchars($result['raw_content']) . "</pre>";
                }
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ AI service exception: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database error checking recommendations: " . $e->getMessage() . "</p>";
}

// Test 6: Test roadmap generation
echo "<h2>Test 6: Roadmap Generation</h2>";
try {
    $aiService = new AIService();
    $testMajor = "Ứng dụng phần mềm";
    $roadmapPrompt = "Tạo lộ trình học tập chi tiết cho ngành '{$testMajor}' tại FPT Polytechnic. Trả về JSON với cấu trúc chính xác.";
    
    $result = $aiService->sendRequest($roadmapPrompt);
    
    if ($result['success']) {
        echo "<p class='success'>✅ AI roadmap generation working</p>";
        echo "<p class='info'>AI Response (first 200 chars):</p>";
        echo "<pre>" . htmlspecialchars(substr($result['content'], 0, 200)) . "...</pre>";
    } else {
        echo "<p class='error'>❌ AI roadmap generation failed: " . ($result['error'] ?? 'Unknown error') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Roadmap generation exception: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><a href='my-profiles.php'>← Back to My Profiles</a></p>";
echo "<p><a href='profile-detail.php?id=" . $profileId . "'>→ Go to actual profile detail page</a></p>";
?>
