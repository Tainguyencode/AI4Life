<?php
/**
 * AI Service - Kết nối với AI thông qua OpenRouter
 * OpenRouter là dịch vụ proxy cho các model AI như OpenAI, Anthropic, Google, etc.
 */

class AIService {
    private $apiKey;
    private $baseUrl = 'https://openrouter.ai/api/v1';
    private $defaultModel = 'openai/gpt-3.5-turbo';
    
    public function __construct($apiKey = null) {
        $this->apiKey = $apiKey ?: 'your_openrouter_api_key_here';
    }
    
    /**
     * Gửi request đến OpenRouter API
     */
    public function sendRequest($prompt, $model = null, $maxTokens = 800) {
        $model = $model ?: $this->defaultModel;
        
        $data = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Bạn là một chuyên gia tư vấn hướng nghiệp tại Việt Nam với kinh nghiệm 20 năm. Hãy phân tích thông tin chi tiết và đưa ra lời khuyên về ngành học phù hợp tại Cao đẳng FPT Polytechnic dựa trên sở thích, điểm mạnh, điểm số các môn học và mục tiêu của học sinh. Hãy đưa ra 3 ngành học phù hợp nhất tại FPT Polytechnic với lý do cụ thể.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
            'top_p' => 0.9
        ];
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'HTTP-Referer: https://nguoibanai.com',
            'X-Title: NguoiBanAI - Hệ thống tư vấn hướng nghiệp'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . '/chat/completions');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'error' => 'CURL Error: ' . $error
            ];
        }
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP Error: ' . $httpCode,
                'response' => $response
            ];
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'content' => $result['choices'][0]['message']['content'],
                'model' => $result['model'],
                'usage' => $result['usage'] ?? null
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Invalid response format',
            'response' => $result
        ];
    }
    
    /**
     * Phân tích và đề xuất ngành học
     */
    public function analyzeAndRecommend($userData) {
        $prompt = $this->buildDetailedAnalysisPrompt($userData);
        $result = $this->sendRequest($prompt);
        
        if ($result['success']) {
            return $this->parseRecommendations($result['content']);
        }
        
        return [
            'success' => false,
            'error' => $result['error'],
            'recommendations' => $this->getFallbackRecommendations()
        ];
    }
    
    /**
     * Xây dựng prompt chi tiết cho phân tích
     */
    private function buildDetailedAnalysisPrompt($userData) {
        $prompt = "Dựa trên thông tin chi tiết sau, hãy đề xuất 3 ngành học phù hợp nhất tại Cao đẳng FPT Polytechnic:\n\n";
        
        // Thông tin cơ bản
        $prompt .= "=== THÔNG TIN CƠ BẢN ===\n";
        $prompt .= "Sở thích: " . ($userData['interests'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Kỹ năng và tố chất: " . ($userData['skills'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Môn học yêu thích: " . ($userData['favorite_subject'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Định hướng nghề nghiệp: " . ($userData['career_orientation'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Thói quen hàng ngày: " . ($userData['habits'] ?? 'Chưa cung cấp') . "\n\n";
        
        // Điểm số các môn học
        $prompt .= "=== ĐIỂM SỐ CÁC MÔN HỌC ===\n";
        $prompt .= "Toán: " . ($userData['math_score'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Văn: " . ($userData['literature_score'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Anh: " . ($userData['english_score'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Lý: " . ($userData['physics_score'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Hóa: " . ($userData['chemistry_score'] ?? 'Chưa cung cấp') . "\n";
        $prompt .= "Sinh: " . ($userData['biology_score'] ?? 'Chưa cung cấp') . "\n\n";
        
        // Đánh giá mức độ
        $prompt .= "=== ĐÁNH GIÁ MỨC ĐỘ (1-10) ===\n";
        $prompt .= "Yêu thích công nghệ: " . ($userData['tech_interest'] ?? 'Chưa cung cấp') . "/10\n";
        $prompt .= "Khả năng sáng tạo: " . ($userData['creativity'] ?? 'Chưa cung cấp') . "/10\n";
        $prompt .= "Kỹ năng giao tiếp: " . ($userData['communication'] ?? 'Chưa cung cấp') . "/10\n\n";
        
        $prompt .= "Trình độ học vấn: " . ($userData['academic_level'] ?? 'THPT') . "\n\n";
        
        $prompt .= "Hãy phân tích chi tiết và trả về kết quả theo format JSON với cấu trúc:\n";
        $prompt .= '{"recommendations": [{"major": "Tên ngành tại FPT Polytechnic", "university": "Cao đẳng FPT Polytechnic", "confidence": 0.85, "reasoning": "Lý do chi tiết dựa trên thông tin đã cung cấp", "career_prospects": "Triển vọng nghề nghiệp cụ thể", "salary_range": "Mức lương trung bình tại Việt Nam"}]}';
        
        $prompt .= "\n\nLưu ý: Hãy đưa ra lý do cụ thể dựa trên điểm số các môn học, sở thích và kỹ năng đã cung cấp. Chỉ đề xuất các ngành học có tại Cao đẳng FPT Polytechnic.";
        
        return $prompt;
    }
    
    /**
     * Parse kết quả từ AI
     */
    private function parseRecommendations($content) {
        // Tìm JSON trong response
        preg_match('/\{.*\}/s', $content, $matches);
        
        if (!empty($matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json['recommendations'])) {
                return [
                    'success' => true,
                    'recommendations' => $json['recommendations'],
                    'raw_content' => $content
                ];
            }
        }
        
        // Nếu không parse được JSON, trả về fallback
        return [
            'success' => false,
            'error' => 'Không thể parse kết quả từ AI',
            'recommendations' => $this->getFallbackRecommendations(),
            'raw_content' => $content
        ];
    }
    
    /**
     * Đề xuất fallback khi AI không hoạt động - Tất cả đều là ngành học tại FPT Polytechnic
     */
    private function getFallbackRecommendations() {
        return [
            [
                'major' => 'Ứng dụng phần mềm',
                'university' => 'Cao đẳng FPT Polytechnic',
                'confidence' => 0.8,
                'reasoning' => 'Phù hợp với sở thích công nghệ và khả năng tư duy logic. Ngành này đào tạo chuyên sâu về lập trình, phát triển ứng dụng và công nghệ phần mềm.',
                'career_prospects' => 'Lập trình viên, Kỹ sư phần mềm, Developer, Mobile App Developer, Web Developer',
                'salary_range' => '15-50 triệu VNĐ/tháng'
            ],
            [
                'major' => 'Thiết kế đồ họa',
                'university' => 'Cao đẳng FPT Polytechnic',
                'confidence' => 0.75,
                'reasoning' => 'Phù hợp với khả năng sáng tạo và yêu thích nghệ thuật. Ngành này kết hợp giữa công nghệ và nghệ thuật, đào tạo về thiết kế digital.',
                'career_prospects' => 'Graphic Designer, UI/UX Designer, Digital Artist, Creative Designer, Brand Designer',
                'salary_range' => '12-40 triệu VNĐ/tháng'
            ],
            [
                'major' => 'Quản trị kinh doanh',
                'university' => 'Cao đẳng FPT Polytechnic',
                'confidence' => 0.7,
                'reasoning' => 'Phù hợp với khả năng giao tiếp và mục tiêu phát triển sự nghiệp. Ngành này đào tạo về quản lý, marketing và kinh doanh số.',
                'career_prospects' => 'Business Analyst, Marketing Manager, Sales Manager, Project Manager, Entrepreneur',
                'salary_range' => '10-35 triệu VNĐ/tháng'
            ]
        ];
    }
    
    /**
     * Lấy danh sách các model có sẵn
     */
    public function getAvailableModels() {
        return [
            'openai/gpt-3.5-turbo' => 'GPT-3.5 Turbo (OpenAI)',
            'openai/gpt-4' => 'GPT-4 (OpenAI)',
            'anthropic/claude-3-haiku' => 'Claude 3 Haiku (Anthropic)',
            'anthropic/claude-3-sonnet' => 'Claude 3 Sonnet (Anthropic)',
            'google/gemini-pro' => 'Gemini Pro (Google)',
            'meta-llama/llama-2-13b-chat' => 'Llama 2 13B (Meta)'
        ];
    }
    
    /**
     * Test kết nối với OpenRouter
     */
    public function testConnection() {
        $result = $this->sendRequest('Xin chào! Hãy trả lời "Kết nối thành công" nếu bạn nhận được tin nhắn này.');
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Kết nối OpenRouter thành công!',
                'model' => $result['model'],
                'response' => $result['content']
            ];
        }
        
        return [
            'success' => false,
            'error' => $result['error']
        ];
    }
}

// Hàm tiện ích để sử dụng AI Service
function getAIService($apiKey = null) {
    return new AIService($apiKey);
}

// Hàm phân tích ngành học với AI
function analyzeMajorWithAI($userData, $apiKey = null) {
    $aiService = getAIService($apiKey);
    return $aiService->analyzeAndRecommend($userData);
}

// Hàm test kết nối AI
function testAIConnection($apiKey = null) {
    $aiService = getAIService($apiKey);
    return $aiService->testConnection();
}
?>
