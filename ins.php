<?php

/**
 * Instagram Reels - Ücretsiz Etkileşim Gösterim API
 * Geliştirici: @zanetmez (Telegram)
 */

$toolId = "1d8ecdf4-08c4-4c67-9e66-975d621c6e46";
$baseUrl = "https://api.etkisepeti.com/v1";

$headers = [
    "Content-Type: application/json",
    "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? "Mozilla/5.0 (Android 15; Mobile; rv:151.0) Gecko/151.0 Firefox/151.0"),
    "Referer: " . ($_SERVER['HTTP_REFERER'] ?? "https://etkisepeti.com/instagram-ucretsiz-etkilesim-gosterim")
];

$identifiers = [
    "canvas_fp" => "3ab0325c2bd5f639028edfd20c24aa9b4739a9d75ec91b0fba387a1bcaba0617",
    "screen_res" => "396x902",
    "viewport_res" => "396x783",
    "pixel_ratio" => "2.727",
    "touch_points" => 5,
    "cores" => 8,
    "platform" => "Linux armv81",
    "timezone" => "Europe/Istanbul",
    "language" => "tr-TR",
    "audio_fp" => "03dca19b51b16e6d58dd141eb5c7a85394be6d464f58b77b665716aa49b931dd",
    "webgl_fp" => "c0d06c6db1bf26f0d5e77b249a7aa676b9d1e3c446070f83245e9f463a6c9bbd"
];

// POST ile gelen veriler
$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(["error" => "Geçersiz istek"]);
    exit;
}

$targetUrl = $input['target_url'] ?? '';
$quantity = $input['quantity'] ?? 100;

if (empty($targetUrl)) {
    http_response_code(400);
    echo json_encode(["error" => "target_url gerekli"]);
    exit;
}

// 1. Bekleme oturumu oluştur
$waitData = [
    "free_tool_id" => $toolId,
    "target_url" => $targetUrl,
    "quantity" => $quantity
];

$ch = curl_init($baseUrl . "/free-tool/waiting-session");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($waitData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$waitResult = json_decode($response, true);

if (!$waitResult['success']) {
    http_response_code(400);
    echo json_encode(["error" => "Oturum oluşturulamadı"]);
    exit;
}

$waitToken = $waitResult['data']['data']['wait_token'];

// 2. 60 saniye bekle (API'den gelen süre ne olursa olsun 60 saniye bekle)
sleep(60);

// 3. İşlem gönder
$requestData = [
    "free_tool_id" => $toolId,
    "target_url" => $targetUrl,
    "quantity" => $quantity,
    "wait_token" => $waitToken,
    "identifiers" => $identifiers
];

$ch = curl_init($baseUrl . "/free-tool/request");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// Sadece JSON döndür
header('Content-Type: application/json');
echo json_encode($result);