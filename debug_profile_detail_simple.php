<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "<h1>Debug Profile Detail</h1>";

// Check if user is logged in
if (!isLoggedIn()) {
    echo "<p style='color: red;'>❌ User not logged in</p>";
    exit;
}

$user = getCurrentUser();
echo "<p>✅ User logged in: " . htmlspecialchars($user['fullname']) . "</p>";

// Check if profile ID is provided
$profileId = (int)($_GET['id'] ?? 0);
echo "<p>Profile ID: " . $profileId . "</p>";

if (!$profileId) {
    echo "<p style='color: red;'>❌ No profile ID provided</p>";
    exit;
}

try {
    $pdo = getConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Check if profile exists
    $stmt = $pdo->prepare("SELECT sp.*, u.fullname FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.id = ? AND sp.user_id = ?");
    $stmt->execute([$profileId, $user['id']]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        echo "<p style='color: red;'>❌ Profile not found or access denied</p>";
        echo "<p>Profile ID: " . $profileId . "</p>";
        echo "<p>User ID: " . $user['id'] . "</p>";
        
        // Check if profile exists at all
        $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE id = ?");
        $stmt->execute([$profileId]);
        $anyProfile = $stmt->fetch();
        
        if ($anyProfile) {
            echo "<p>⚠️ Profile exists but belongs to user ID: " . $anyProfile['user_id'] . "</p>";
        } else {
            echo "<p>❌ Profile with ID " . $profileId . " does not exist</p>";
        }
        exit;
    }
    
    echo "<p>✅ Profile found</p>";
    echo "<h2>Profile Data:</h2>";
    echo "<pre>" . print_r($profile, true) . "</pre>";
    
    // Check AI recommendations
    $stmt = $pdo->prepare("SELECT * FROM ai_recommendations WHERE profile_id = ? AND user_id = ? ORDER BY recommendation_order ASC");
    $stmt->execute([$profileId, $user['id']]);
    $recommendations = $stmt->fetchAll();
    
    echo "<h2>AI Recommendations:</h2>";
    if (!empty($recommendations)) {
        echo "<p>✅ Found " . count($recommendations) . " recommendations</p>";
        echo "<pre>" . print_r($recommendations, true) . "</pre>";
    } else {
        echo "<p>⚠️ No AI recommendations found</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ General error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='my-profiles.php'>← Back to My Profiles</a></p>";
?>
