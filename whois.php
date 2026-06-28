<?php
// whoisapi.php - Sadece who.is sansürlü
// Geliştirici: @zanetmez

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$developer = "@zanetmez";
$domain = $_GET['domain'] ?? '';

if (empty($domain)) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Domain parametresi gerekli.',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$domain = str_replace(['http://', 'https://', 'www.'], '', $domain);
$domain = preg_replace('/[^a-zA-Z0-9.-]/', '', $domain);

if (empty($domain)) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Geçersiz domain formatı.',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Orijinal WHOIS verisini çek
$rawData = fetchWhois($domain);

if (!$rawData) {
    die(json_encode([
        'status' => 'error',
        'message' => 'WHOIS verisi alınamadı.',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// SADECE who.is ile ilgili kısımları sansürle, gerisi orijinal
$censored = censorWhoIs($rawData);

echo json_encode([
    'status' => 'success',
    'domain' => $domain,
    'data' => $censored,
    'developer' => $developer,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

function fetchWhois($domain) {
    $url = "https://who.is/whois/" . urlencode($domain);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: tr-TR,tr;q=0.9,en;q=0.8'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate, br');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $response) {
        $clean = strip_tags($response);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean);
    }
    
    return null;
}

function censorWhoIs($text) {
    // Sadece who.is ile ilgili ifadeleri sansürle
    $patterns = [
        '/who\.is/i' => '***',
        '/whois\.who\.is/i' => '***',
        '/whois\.is/i' => '***'
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $text = preg_replace($pattern, $replacement, $text);
    }
    
    return $text;
}