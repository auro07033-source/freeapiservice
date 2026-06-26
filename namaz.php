<?php
/**
 * Namaz Vakti API - İl/İlçe'den Koordinat Al
 * Geliştirici: @zanetmez
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$developer = "@zanetmez";

// ==================== PARAMETRELER ====================
$il = isset($_GET['il']) ? trim($_GET['il']) : '';
$ilce = isset($_GET['ilce']) ? trim($_GET['ilce']) : '';
$tarih = isset($_GET['tarih']) ? trim($_GET['tarih']) : date('d-m-Y');

// ==================== KOORDİNAT AL ====================
function getCoordinates($il, $ilce = '') {
    $query = $il . ' il';
    if (!empty($ilce)) {
        $query .= ' ' . $ilce;
    }
    $query .= ' Türkiye';
    
    $url = "https://nominatim.openstreetmap.org/search";
    $params = [
        'format' => 'json',
        'q' => $query,
        'countrycodes' => 'tr',
        'limit' => 1
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if (!empty($data)) {
        return [
            'lat' => $data[0]['lat'] ?? null,
            'lon' => $data[0]['lon'] ?? null,
            'display_name' => $data[0]['display_name'] ?? null
        ];
    }
    return null;
}

// ==================== NAMAZ VAKTİ AL ====================
function getNamazVakti($lat, $lon, $tarih) {
    $url = "https://api.aladhan.com/v1/timings/{$tarih}";
    
    $params = [
        'latitude' => $lat,
        'longitude' => $lon,
        'method' => 13,  // Diyanet
        'school' => 0    // Hanefi
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

// ==================== API YANITI ====================

if (empty($il)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'İl parametresi gerekli. Örnek: ?il=istanbul&ilce=kadikoy',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Koordinat al
$coord = getCoordinates($il, $ilce);

if (!$coord) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Koordinat bulunamadı. Lütfen geçerli bir il/ilçe girin.',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Namaz vaktini al
$vakit = getNamazVakti($coord['lat'], $coord['lon'], $tarih);

if (!$vakit || !isset($vakit['data'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Namaz vakti alınamadı.',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Yanıtı hazırla
$timings = $vakit['data']['timings'];
$date = $vakit['data']['date']['readable'] ?? $tarih;

echo json_encode([
    'status' => 'success',
    'il' => ucfirst($il),
    'ilce' => $ilce ? ucfirst($ilce) : null,
    'tarih' => $date,
    'coordinates' => [
        'latitude' => $coord['lat'],
        'longitude' => $coord['lon']
    ],
    'vakitler' => [
        'imsak' => $timings['Imsak'] ?? null,
        'gunes' => $timings['Sunrise'] ?? null,
        'ogle' => $timings['Dhuhr'] ?? null,
        'ikindi' => $timings['Asr'] ?? null,
        'aksam' => $timings['Maghrib'] ?? null,
        'yatsi' => $timings['Isha'] ?? null
    ],
    'developer' => $developer
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>