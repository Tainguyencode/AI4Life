<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Test Profile Detail Page</h1>";

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

// Get all profiles for this user
try {
    $stmt = $pdo->prepare("SELECT sp.*, u.fullname FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.user_id = ? ORDER BY sp.created_at DESC");
    $stmt->execute([$user['id']]);
    $profiles = $stmt->fetchAll();
    
    echo "<h2>Found " . count($profiles) . " profiles</h2>";
    
    if (!empty($profiles)) {
        echo "<h3>Available profiles:</h3>";
        echo "<ul>";
        foreach ($profiles as $profile) {
            echo "<li>";
            echo "<strong>Profile ID:</strong> " . $profile['id'] . "<br>";
            echo "<strong>Created:</strong> " . $profile['created_at'] . "<br>";
            echo "<strong>Interests:</strong> " . htmlspecialchars($profile['interests']) . "<br>";
            echo "<a href='profile-detail.php?id=" . $profile['id'] . "' target='_blank'>View Detail</a>";
            echo "</li><br>";
        }
        echo "</ul>";
        
        // Test first profile
        $firstProfile = $profiles[0];
        echo "<h3>Testing first profile (ID: " . $firstProfile['id'] . "):</h3>";
        
        // Test recommendations
        $stmt = $pdo->prepare("SELECT * FROM ai_recommendations WHERE profile_id = ? AND user_id = ? ORDER BY recommendation_order ASC");
        $stmt->execute([$firstProfile['id'], $user['id']]);
        $recommendations = $stmt->fetchAll();
        
        echo "<p>Found " . count($recommendations) . " recommendations</p>";
        
        if (!empty($recommendations)) {
            echo "<h4>Recommendations:</h4>";
            echo "<ul>";
            foreach ($recommendations as $rec) {
                echo "<li>";
                echo "<strong>Major:</strong> " . htmlspecialchars($rec['major']) . "<br>";
                echo "<strong>Confidence:</strong> " . $rec['confidence'] . "<br>";
                echo "<strong>Reasoning:</strong> " . htmlspecialchars(substr($rec['reasoning'], 0, 100)) . "...<br>";
                echo "</li><br>";
            }
            echo "</ul>";
        }
        
        echo "<h3>Direct link to test:</h3>";
        echo "<p><a href='profile-detail.php?id=" . $firstProfile['id'] . "' target='_blank'>Open Profile Detail Page</a></p>";
        
    } else {
        echo "<h3>No profiles found</h3>";
        echo "<p>Please create a profile first by using the AI analysis feature.</p>";
        echo "<p><a href='ai-analysis.php'>Go to AI Analysis</a></p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error fetching profiles</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>🔧 Test completed</h2>";
echo "<p><a href='my-profiles.php'>Back to My Profiles</a></p>";
?>
