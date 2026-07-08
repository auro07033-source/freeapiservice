<?php
/**
 * Instagram İletişim Bilgisi Bulucu - PHP API
 * Direkt API yanıtını JSON olarak döndürür
 */

header('Content-Type: application/json; charset=utf-8');

// === KONFIG ===
define('BASE_URL', 'https://www.instagram.com/api/graphql/');
define('LSD', 'AdRs3OdVaQurU9jBNT0IjiKWV6s');
define('CSRF_TOKEN', 'V75qyaXHG3BHk7OZHgkvzV0FvMPXVzpA');
define('DOC_ID', '31115866268061587');

// Rate limit dosyası
$rate_limit_file = sys_get_temp_dir() . '/instagram_rate_limit_' . md5($_SERVER['REMOTE_ADDR'] ?? 'local');

function checkRateLimit(): bool {
    global $rate_limit_file;
    $now = time();
    $data = @json_decode(@file_get_contents($rate_limit_file), true) ?: [];
    $window = $now - 60;
    $requests = array_filter($data, function($t) use ($window) {
        return $t > $window;
    });
    if (count($requests) >= 10) {
        return false;
    }
    $data[] = $now;
    file_put_contents($rate_limit_file, json_encode($data));
    return true;
}

function generateUUID(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function searchInstagram($username) {
    if (empty($username)) {
        return ['error' => 'Kullanıcı adı boş olamaz'];
    }
    
    if (!checkRateLimit()) {
        return ['error' => 'Çok fazla istek. Lütfen 1 dakika bekleyin.'];
    }
    
    $payload = [
        'lsd' => LSD,
        'variables' => json_encode([
            'params' => [
                'event_request_id' => generateUUID(),
                'search_query' => $username,
                'waterfall_id' => generateUUID()
            ]
        ]),
        'doc_id' => DOC_ID
    ];
    
    $ch = curl_init(BASE_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0',
        'X-IG-App-ID: 936619743392459',
        'X-FB-LSD: ' . LSD,
        'Cookie: csrftoken=' . CSRF_TOKEN . ';',
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 429) {
        return ['error' => 'Rate limit aşıldı'];
    }
    if ($httpCode !== 200) {
        return ['error' => "HTTP $httpCode", 'raw' => substr($response, 0, 200)];
    }
    if ($error) {
        return ['error' => "CURL Hatası: $error"];
    }
    
    $data = json_decode($response, true);
    if ($data === null) {
        return ['error' => 'JSON parse hatası', 'raw' => substr($response, 0, 200)];
    }
    
    $contact_points = $data['data']['caa_ar_ig_account_search']['contact_points'] ?? [];
    
    return [
        'username' => $username,
        'contact_points' => $contact_points,
        'total' => count($contact_points)
    ];
}

// === ANA İŞLEM ===
$username = $_GET['username'] ?? $_POST['username'] ?? null;

if (!$username) {
    echo json_encode(['error' => 'username parametresi gerekli. Örnek: ?username=instagram'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$result = searchInstagram(trim($username));
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);