<?php
/**
 * ChatGPT4Online - PHP API (Oturum Başlatma + Sohbet)
 */

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}

class ChatGPT4Online {
    private $base_url = "https://chatgpt4online.org/wp-json";
    private $session_id = null;
    private $nonce = null;
    private $chat_id = null;
    private $context_id = 5410;
    private $bot_id = "chatbot-qm966k";
    private $messages = [];
    private $response_text = "";
    
    public function __construct() {
        $this->startSession();
    }
    
    private function startSession() {
        $ch = curl_init($this->base_url . "/mwai/v1/start_session");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36",
            "Origin: https://chatgpt4online.org",
            "Referer: https://chatgpt4online.org/"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            $this->session_id = $data["sessionId"] ?? null;
            $this->nonce = $data["restNonce"] ?? null;
            return true;
        }
        return false;
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
        
        $ch = curl_init($this->base_url . "/mwai-ui/v1/chats/submit");
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
                "response" => null
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
            "response" => $this->response_text
        ];
    }
}

// ==================== ROUTER ====================

$client = new ChatGPT4Online();
$method = $_SERVER["REQUEST_METHOD"];
$path = $_SERVER["PATH_INFO"] ?? "/";

// GET ?message=Merhaba
if ($method === "GET" && ($path === "/" || $path === "")) {
    $message = $_GET["message"] ?? "";
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "message required"]);
        exit;
    }
    
    $result = $client->chat($message);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// POST / (JSON)
if ($method === "POST") {
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

// Varsayılan
echo json_encode([
    "name" => "gpt online ✅",
    "status" => "ok",
    "endpoints" => [
        "GET ?message=Merhaba" => "Sohbet et",
        "POST /" => "Sohbet et (JSON)"
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);