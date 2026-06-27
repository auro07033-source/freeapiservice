<?php
/**
 * AI Sohbet API (Pollinations.ai + DG AI + DevToolBox)
 * Geliştirici: @zanetmez
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$developer = "@zanetmez";

// ==================== PARAMETRELER ====================
$mesaj = isset($_GET['mesaj']) ? trim($_GET['mesaj']) : '';
$model = isset($_GET['model']) ? trim($_GET['model']) : 'openai';
$source = isset($_GET['source']) ? trim($_GET['source']) : 'pollinations'; // pollinations, dgai, devtoolbox

// ==================== AI API'LERİ ====================

// 1. Pollinations.ai
function aiPollinations($mesaj, $model = 'openai') {
    $url = "https://text.pollinations.ai/openai";
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Sen yardımsever bir AI asistanısın. Türkçe cevap ver.'],
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
    
    if (!$response) return null;
    
    $result = json_decode($response, true);
    return $result['choices'][0]['message']['content'] ?? null;
}

// 2. DG AI
function aiDG($mesaj) {
    $url = "https://dg-ai.scriptsnsenses.workers.dev/";
    
    $data = [
        'messages' => [
            ['role' => 'system', 'content' => 'Sen yardımsever bir AI asistanısın. Türkçe cevap ver.'],
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
    
    if (!$response) return null;
    
    $result = json_decode($response, true);
    return $result['response']['choices'][0]['message']['content'] ?? null;
}

// 3. DevToolBox
function aiDevToolBox($mesaj) {
    $url = "https://devtoolbox-api.devtoolbox-api.workers.dev/ai/generate";
    
    $data = ['prompt' => $mesaj];
    
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
    
    if (!$response) return null;
    
    $result = json_decode($response, true);
    return $result['response'] ?? $result['data'] ?? null;
}

// ==================== ANA FONKSİYON ====================
function aiSohbet($mesaj, $source = 'pollinations', $model = 'openai') {
    switch ($source) {
        case 'dgai':
            return aiDG($mesaj);
        case 'devtoolbox':
            return aiDevToolBox($mesaj);
        case 'pollinations':
        default:
            return aiPollinations($mesaj, $model);
    }
}

// ==================== API YANITI ====================

// Ana sayfa (parametresiz)
if (empty($mesaj)) {
    echo json_encode([
        'status' => 'info',
        'message' => 'AI Sohbet API - Kullanım Kılavuzu',
        'developer' => $developer,
        'endpoints' => [
            [
                'url' => '/ai.php?mesaj=Merhaba',
                'description' => 'Pollinations.ai ile sohbet (varsayılan)',
                'example' => '/ai.php?mesaj=Merhaba nasılsın?'
            ],
            [
                'url' => '/ai.php?mesaj=Merhaba&source=dgai',
                'description' => 'DG AI ile sohbet (hızlı)',
                'example' => '/ai.php?mesaj=Merhaba&source=dgai'
            ],
            [
                'url' => '/ai.php?mesaj=Merhaba&source=devtoolbox',
                'description' => 'DevToolBox ile sohbet',
                'example' => '/ai.php?mesaj=Merhaba&source=devtoolbox'
            ],
            [
                'url' => '/ai.php?mesaj=Merhaba&model=mistral',
                'description' => 'Farklı model ile sohbet (Pollinations)',
                'example' => '/ai.php?mesaj=Merhaba&model=mistral'
            ]
        ],
        'models' => [
            'Pollinations' => ['openai', 'mistral', 'llama', 'gemini', 'claude'],
            'DG AI' => ['otomatik'],
            'DevToolBox' => ['otomatik']
        ],
        'sources' => [
            'pollinations' => 'Kaliteli cevaplar, çok model',
            'dgai' => 'Hızlı cevaplar, düşük gecikme',
            'devtoolbox' => 'Cloudflare üzerinde, ücretsiz'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// AI yanıtı al
$cevap = aiSohbet($mesaj, $source, $model);

if ($cevap) {
    echo json_encode([
        'status' => 'success',
        'source' => $source,
        'model' => $model,
        'mesaj' => $mesaj,
        'cevap' => $cevap,
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    // Yedek: Bir sonraki API'yi dene
    $yedek = ($source == 'pollinations') ? 'dgai' : 'pollinations';
    $cevap2 = aiSohbet($mesaj, $yedek, $model);
    
    if ($cevap2) {
        echo json_encode([
            'status' => 'success',
            'source' => $yedek . ' (yedek)',
            'mesaj' => $mesaj,
            'cevap' => $cevap2,
            'developer' => $developer
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Tüm AI servisleri şu anda kullanılamıyor.',
            'developer' => $developer
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
?>