<?php
/**
 * ChatGPT4Online - PHP REST API (POST & GET Desteği)
 * 
 * GET /api/chat?message=Merhaba  - Sohbet et (GET ile)
 * POST /api/chat - Sohbet et (POST ile JSON)
 * POST /api/reset - Oturumu sıfırla
 * GET /api/status - Durum bilgisi
 */

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

class ChatGPT4Online {
    private $base_url = "https://chatgpt4online.org/wp-json/mwai-ui/v1/chats/submit";
    private $nonce = "2fee91c1db";
    private $session_id;
    private $chat_id;
    private $context_id = 5410;
    private $bot_id = "chatbot-qm966k";
    private $messages = [];
    private $response_text = "";
    private $usage = [];
    
    public function __construct() {
        $this->session_id = substr(str_replace("-", "", uniqid()), 0, 13);
        $this->chat_id = null;
    }
    
    private function generateId() {
        return substr(str_replace("-", "", uniqid()), 0, 11);
    }
    
    public function chat($message) {
        if (empty($this->messages)) {
            $this->messages[] = [
                "id" => $this->generateId(),
                "role" => "assistant",
                "content" => "Hi! How can I help you?",
                "who" => "AI: ",
                "timestamp" => intval(microtime(true) * 1000),
                "key" => "start-" . intval(microtime(true) * 1000)
            ];
            $this->chat_id = "x" . substr($this->generateId(), 0, 8);
        }
        
        $payload = [
            "botId" => $this->bot_id,
            "customId" => null,
            "session" => $this->session_id,
            "chatId" => $this->chat_id,
            "contextId" => $this->context_id,
            "messages" => $this->messages,
            "newMessage" => $message,
            "newFileId" => null,
            "newFileIds" => [],
            "stream" => true
        ];
        
        $ch = curl_init($this->base_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "X-WP-Nonce: {$this->nonce}",
            "Accept: text/event-stream",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            "Origin: https://chatgpt4online.org",
            "Referer: https://chatgpt4online.org/"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
            static $buffer = "";
            $buffer .= $data;
            
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, "data:") !== 0) continue;
                
                $dataStr = trim(substr($line, 5));
                $json = json_decode($dataStr, true);
                
                if ($json && $json["type"] == "live") {
                    $this->response_text .= $json["data"];
                } elseif ($json && $json["type"] == "end") {
                    $endData = json_decode($json["data"], true);
                    $this->usage = $endData["usage"] ?? [];
                }
            }
            
            return strlen($data);
        });
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            return [
                "success" => false,
                "error" => "HTTP " . $httpCode,
                "response" => null,
                "total_tokens" => 0
            ];
        }
        
        if (!empty($this->response_text)) {
            $this->messages[] = [
                "id" => $this->generateId(),
                "role" => "assistant",
                "content" => $this->response_text,
                "who" => "AI: ",
                "timestamp" => intval(microtime(true) * 1000)
            ];
        }
        
        return [
            "success" => true,
            "response" => $this->response_text,
            "total_tokens" => $this->usage["total_tokens"] ?? 0,
            "usage" => $this->usage
        ];
    }
    
    public function reset() {
        $this->messages = [];
        $this->chat_id = null;
        $this->response_text = "";
        $this->usage = [];
        $this->session_id = substr(str_replace("-", "", uniqid()), 0, 13);
        return true;
    }
    
    public function getStatus() {
        return [
            "session_id" => $this->session_id,
            "chat_id" => $this->chat_id,
            "message_count" => count($this->messages),
            "nonce" => $this->nonce
        ];
    }
}

// ==================== ROUTER ====================

$client = new ChatGPT4Online();
$path = $_SERVER["PATH_INFO"] ?? "/";
$method = $_SERVER["REQUEST_METHOD"];

// === POST /api/chat (JSON ile) ===
if ($method === "POST" && ($path === "/chat" || $path === "/")) {
    $input = json_decode(file_get_contents("php://input"), true);
    $message = $input["message"] ?? "";
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "message required"]);
        exit;
    }
    
    $result = $client->chat($message);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// === GET /api/chat?message=Merhaba ===
if ($method === "GET" && ($path === "/chat" || $path === "/")) {
    $message = $_GET["message"] ?? "";
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "message parameter required. Example: ?message=Merhaba"]);
        exit;
    }
    
    $result = $client->chat($message);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// === POST /api/reset ===
if ($method === "POST" && $path === "/reset") {
    $client->reset();
    echo json_encode(["success" => true, "message" => "Session reset"]);
    exit;
}

// === GET /api/status ===
if ($method === "GET" && $path === "/status") {
    echo json_encode($client->getStatus(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// === GET / (Ana sayfa) ===
if ($method === "GET" && ($path === "/" || $path === "")) {
    echo json_encode([
        "name" => "gpt online ✅",
        "version" => "1.0",
        "endpoints" => [
            "GET /api/chat" => [
                "description" => "Sohbet et (GET)",
                "example" => "/api/chat?message=Merhaba"
            ],
            "POST /api/chat" => [
                "description" => "Sohbet et (POST)",
                "example" => '{"message":"Merhaba"}'
            ],
            "POST /api/reset" => "Oturumu sıfırla",
            "GET /api/status" => "API durumu"
        ],
        "example_curl" => [
            "GET" => 'curl "https://freeapiservice-q08q.onrender.com/ai2.php?message=Merhaba"'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// === 404 ===
http_response_code(404);
echo json_encode(["error" => "Endpoint not found"]);