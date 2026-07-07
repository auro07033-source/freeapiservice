<?php
// test_api.php - Telz API test

$phone = '+905545715516'; // Test numarası

function randomAndroidId() {
    return bin2hex(random_bytes(8));
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$androidId = randomAndroidId();
$uuid = generateUUID();

$payload = [
    'event' => 'auth_call',
    'phone' => $phone,
    'attempt' => '0',
    'lang' => 'tr',
    'android_id' => $androidId,
    'app_version' => '17.5.33',
    'os' => 'android',
    'os_version' => '15',
    'ts' => (int)(microtime(true) * 1000),
    'uuid' => $uuid
];

echo "📤 Gönderilen payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

$ch = curl_init('https://api.telz.com/app/auth_call');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Telz-Android/17.5.33',
    'Content-Type: application/json; charset=UTF-8',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 HTTP Status: $httpCode\n";
if ($error) {
    echo "❌ CURL Hatası: $error\n";
}

$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

echo "📋 Headers:\n$headers\n";
echo "📄 Body:\n$body\n";