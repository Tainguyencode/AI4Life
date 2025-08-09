<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Debug AI Recommendations</h1>";

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
    echo "<p><strong>Created:</strong> " . $profile['created_at'] . "</p>";
    echo "<p><strong>Interests:</strong> " . htmlspecialchars($profile['interests']) . "</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error fetching profile</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
    exit;
}

// Test fetching recommendations
try {
    $stmt = $pdo->prepare("SELECT * FROM ai_recommendations WHERE profile_id = ? AND user_id = ? ORDER BY recommendation_order ASC");
    $stmt->execute([$profileId, $user['id']]);
    $recommendations = $stmt->fetchAll();
    
    echo "<h2>✅ Recommendations fetched</h2>";
    echo "<p>Found " . count($recommendations) . " recommendations</p>";
    
    if (!empty($recommendations)) {
        echo "<h3>Recommendations:</h3>";
        echo "<ul>";
        foreach ($recommendations as $rec) {
            echo "<li>";
            echo "<strong>Major:</strong> " . htmlspecialchars($rec['major']) . "<br>";
            echo "<strong>Confidence:</strong> " . $rec['confidence'] . "<br>";
            echo "<strong>Reasoning:</strong> " . htmlspecialchars(substr($rec['reasoning'], 0, 100)) . "...<br>";
            echo "<strong>Career Prospects:</strong> " . htmlspecialchars(substr($rec['career_prospects'], 0, 100)) . "...<br>";
            echo "<strong>Salary Range:</strong> " . htmlspecialchars($rec['salary_range']) . "<br>";
            echo "</li><br>";
        }
        echo "</ul>";
    } else {
        echo "<h3>❌ No recommendations found</h3>";
        echo "<p>This could be because:</p>";
        echo "<ul>";
        echo "<li>AI analysis failed</li>";
        echo "<li>Recommendations were not saved to database</li>";
        echo "<li>There was an error during analysis</li>";
        echo "</ul>";
        
        // Check if there are any recommendations for this profile (any user)
        $stmt = $pdo->prepare("SELECT * FROM ai_recommendations WHERE profile_id = ?");
        $stmt->execute([$profileId]);
        $anyRecommendations = $stmt->fetchAll();
        
        if (!empty($anyRecommendations)) {
            echo "<h3>Found recommendations for other users:</h3>";
            echo "<p>There are " . count($anyRecommendations) . " recommendations for this profile, but they belong to different users.</p>";
        } else {
            echo "<h3>No recommendations exist for this profile at all</h3>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error fetching recommendations</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Test creating a sample recommendation
echo "<h2>Test: Create Sample Recommendation</h2>";
echo "<p>This will create a test recommendation to see if the display works:</p>";

if (isset($_GET['create_test'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO ai_recommendations (user_id, profile_id, major_name, university_name, confidence, reasoning, career_prospects, salary_range, recommendation_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['id'],
            $profileId,
            'Ứng dụng phần mềm',
            'Cao đẳng FPT Polytechnic',
            0.85,
            'Phù hợp với sở thích công nghệ và khả năng tư duy logic. Ngành này đào tạo chuyên sâu về lập trình, phát triển ứng dụng và công nghệ phần mềm.',
            'Lập trình viên, Kỹ sư phần mềm, Developer, Mobile App Developer, Web Developer',
            '15-50 triệu VNĐ/tháng',
            1
        ]);
        
        echo "<p>✅ Test recommendation created successfully!</p>";
        echo "<p><a href='profile-detail.php?id=" . $profileId . "'>View Profile Detail</a></p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Error creating test recommendation: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p><a href='debug_recommendations.php?id=" . $profileId . "&create_test=1'>Create Test Recommendation</a></p>";
}

echo "<h2>🔧 Test completed</h2>";
echo "<p><a href='profile-detail.php?id=" . $profileId . "'>Back to Profile Detail</a></p>";
?>
