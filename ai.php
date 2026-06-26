<?php
/**
 * AI Sohbet API (Pollinations.ai)
 * Geliştirici: @zanetmez
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$developer = "@zanetmez";

// ==================== PARAMETRELER ====================
$mesaj = isset($_GET['mesaj']) ? trim($_GET['mesaj']) : '';
$model = isset($_GET['model']) ? trim($_GET['model']) : 'openai';

// ==================== AI SOHBET ====================
function aiSohbet($mesaj, $model = 'openai') {
    $url = "https://text.pollinations.ai/openai";
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $mesaj]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) {
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }
    
    return null;
}

// ==================== API YANITI ====================

if (empty($mesaj)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Mesaj gerekli. Örnek: ?mesaj=Merhaba',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$cevap = aiSohbet($mesaj, $model);

if ($cevap) {
    echo json_encode([
        'status' => 'success',
        'mesaj' => $mesaj,
        'cevap' => $cevap,
        'model' => $model,
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'AI yanıtı alınamadı.',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>