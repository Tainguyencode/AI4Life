<?php
require_once 'includes/ai_service.php';

echo "<h1>Test AI Connection</h1>";

try {
    $aiService = new AIService();
    
    echo "<h2>Testing connection...</h2>";
    $result = $aiService->testConnection();
    
    echo "<h3>Connection Result:</h3>";
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<h2>✅ Connection successful!</h2>";
        
        // Test simple request
        echo "<h3>Testing simple request...</h3>";
        $simpleResult = $aiService->sendRequest("Hello, please respond with 'OK'");
        
        echo "<h4>Simple Request Result:</h4>";
        echo "<pre>";
        print_r($simpleResult);
        echo "</pre>";
        
    } else {
        echo "<h2>❌ Connection failed!</h2>";
        echo "<p>Error: " . htmlspecialchars($result['error']) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Exception occurred!</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
