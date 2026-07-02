<?php
/**
 * Raphael Image Generator API
 * Direct image output on success, JSON error on failure
 * Developer: @zanetmez
 * 
 * Usage: aimg.php?prompt=ronaldo&count=1&aspect=1:1
 */

// Error handler - returns JSON on failure
function send_error($message, $code = 400) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => $message,
        'developer' => '@zanetmez'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Get parameters
$prompt = isset($_GET['prompt']) ? trim($_GET['prompt']) : null;
$count = isset($_GET['count']) ? intval($_GET['count']) : 1;
$aspect = isset($_GET['aspect']) ? $_GET['aspect'] : '1:1';

if (!$prompt) {
    send_error('Prompt required. Usage: aimg.php?prompt=ronaldo');
}

if ($count < 1 || $count > 4) $count = 1;

// Generate UUID
function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Fetch RSC token
function fetch_rsc_token($base_url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $base_url . '/tr/nano-banana-2?_rsc=KZBaAPMliKNNTq4f',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Android 15; Mobile; rv:151.0) Gecko/151.0 Firefox/151.0',
            'rsc: 1',
            'next-router-prefetch: 1'
        ]
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    
    if (preg_match('/_rsc=([^&\s"\']+)/', $resp, $m)) {
        return $m[1];
    }
    return 'KZBaAPMliKNNTq4f';
}

// Enhance prompt
function enhance_prompt($base_url, $prompt) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $base_url . '/api/video/enhance-prompt',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['prompt' => $prompt])
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    
    if ($resp) {
        $data = json_decode($resp, true);
        return $data['enhanced_prompt'] ?? $data['prompt'] ?? $prompt;
    }
    return $prompt;
}

// Generate image
function generate_image($base_url, $prompt, $enhanced, $count, $aspect) {
    $payload = [
        'prompt' => $prompt,
        'enhanced_prompt' => $enhanced,
        'entry_type' => 'ai-image',
        'aspect' => $aspect,
        'isSafeContent' => true,
        'autoTranslate' => true,
        'model_id' => 'raphael-basic',
        'number_of_images' => $count,
        'highQuality' => false,
        'fastMode' => false,
        'size' => $aspect,
        'quality' => 'low',
        'resolution' => '0.5k',
        'turnstileToken' => null,
        'client_request_id' => generate_uuid()
    ];
    
    $anon_id = 'anon_' . bin2hex(random_bytes(8));
    $session_id = 'session_' . bin2hex(random_bytes(8));
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $base_url . '/api/generate-image',
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Android 15; Mobile; rv:151.0) Gecko/151.0 Firefox/151.0',
            'Origin: https://raphael.app',
            'Referer: https://raphael.app/tr',
            'X-Anonymous-ID: ' . $anon_id,
            'X-Session-ID: ' . $session_id
        ],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);
    
    $resp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $resp) {
        return json_decode($resp, true);
    }
    
    return ['error' => 'HTTP ' . $http_code, 'raw' => $resp];
}

// Download image to buffer
function fetch_image_data($base_url, $url) {
    if (strpos($url, 'http') !== 0) {
        $url = $base_url . $url;
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $data) {
        return $data;
    }
    return null;
}

// --- MAIN EXECUTION ---
$base_url = 'https://raphael.app';

// Step 1: Enhance prompt
$enhanced = enhance_prompt($base_url, $prompt);

// Step 2: Generate image
$result = generate_image($base_url, $prompt, $enhanced, $count, $aspect);

// Step 3: Check result
if (isset($result['error'])) {
    // Error - return JSON
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'error' => $result['error'],
        'raw' => $result['raw'] ?? null,
        'prompt' => $prompt,
        'developer' => '@zanetmez'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Step 4: Extract image URL
$image_url = null;
if (isset($result['url'])) {
    $image_url = $result['url'];
} elseif (isset($result['images']) && is_array($result['images']) && count($result['images']) > 0) {
    $image_url = $result['images'][0]['url'] ?? null;
}

if (!$image_url) {
    // No image URL found - return full response as JSON
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'No image URL in response',
        'response' => $result,
        'developer' => '@zanetmez'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Step 5: Download and output image
$image_data = fetch_image_data($base_url, $image_url);

if (!$image_data) {
    // Download failed - return URL as JSON
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'partial',
        'message' => 'Image generated but download failed',
        'image_url' => $image_url,
        'developer' => '@zanetmez'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Step 6: Output image directly
$content_type = 'image/webp';
if (strpos($image_url, '.jpg') !== false) $content_type = 'image/jpeg';
if (strpos($image_url, '.png') !== false) $content_type = 'image/png';
if (strpos($image_url, '.gif') !== false) $content_type = 'image/gif';

header('Content-Type: ' . $content_type);
header('Content-Length: ' . strlen($image_data));
header('X-Generated-By: Raphael API');
header('X-Developer: @zanetmez');
header('Cache-Control: public, max-age=3600');

echo $image_data;
exit;