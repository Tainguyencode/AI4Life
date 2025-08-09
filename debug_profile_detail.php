<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/ai_service.php';

echo "<h1>Debug Profile Detail</h1>";

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
    echo "<p>Redirecting to login...</p>";
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
        echo "<p>Profile ID: " . $profileId . "</p>";
        echo "<p>User ID: " . $user['id'] . "</p>";
        
        // Check if profile exists for any user
        $stmt = $pdo->prepare("SELECT sp.*, u.fullname FROM student_profiles sp JOIN users u ON sp.user_id = u.id WHERE sp.id = ?");
        $stmt->execute([$profileId]);
        $anyProfile = $stmt->fetch();
        
        if ($anyProfile) {
            echo "<p>Profile exists but belongs to user ID: " . $anyProfile['user_id'] . "</p>";
        } else {
            echo "<p>Profile does not exist in database</p>";
        }
        exit;
    }
    
    echo "<h2>✅ Profile found</h2>";
    echo "<pre>";
    print_r($profile);
    echo "</pre>";
    
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
        echo "<pre>";
        print_r($recommendations);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error fetching recommendations</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Test AI Service
try {
    $aiService = new AIService();
    echo "<h2>✅ AI Service initialized</h2>";
} catch (Exception $e) {
    echo "<h2>❌ Error initializing AI Service</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

echo "<h2>🔧 Test completed</h2>";
echo "<p><a href='profile-detail.php?id=" . $profileId . "'>Try accessing profile detail page</a></p>";
?>
