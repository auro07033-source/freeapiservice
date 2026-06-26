<?php
/**
 * Namaz Vakti API - İl/İlçe'den Koordinat Al
 * Geliştirici: @zanetmez
 * Domain: https://freeapiservice-fy27.onrender.com
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$developer = "@zanetmez";

// ==================== PARAMETRELER ====================
$il = isset($_GET['il']) ? trim($_GET['il']) : '';
$ilce = isset($_GET['ilce']) ? trim($_GET['ilce']) : '';
$tarih = isset($_GET['tarih']) ? trim($_GET['tarih']) : date('d-m-Y');
$lat = isset($_GET['lat']) ? trim($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? trim($_GET['lon']) : null;

// ==================== KOORDİNAT AL ====================
function getCoordinates($il, $ilce = '') {
    // Türkçe karakterleri düzelt
    $il = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'], 
                      ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'], $il);
    
    $query = $il;
    if (!empty($ilce)) {
        $ilce = str_replace(['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç'],
                           ['i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c'], $ilce);
        $query .= ' ' . $ilce;
    }
    $query .= ' Türkiye';
    
    $url = "https://nominatim.openstreetmap.org/search";
    $params = [
        'format' => 'json',
        'q' => $query,
        'countrycodes' => 'tr',
        'limit' => 1,
        'accept-language' => 'tr'
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
            'display_name' => $data[0]['display_name'] ?? null,
            'city' => $data[0]['address']['city'] ?? $data[0]['address']['town'] ?? $data[0]['address']['village'] ?? null
        ];
    }
    
    // Alternatif: Sadece il ile dene
    if (!empty($ilce)) {
        $params['q'] = $il . ' Türkiye';
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
                'display_name' => $data[0]['display_name'] ?? null,
                'city' => $data[0]['address']['city'] ?? $data[0]['address']['town'] ?? null
            ];
        }
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

// Koordinat kontrolü
if ($lat && $lon) {
    // Koordinatları kullan
    $coord = [
        'lat' => $lat,
        'lon' => $lon,
        'city' => 'Koordinat ile'
    ];
} elseif (!empty($il)) {
    // İl/İlçe'den koordinat al
    $coord = getCoordinates($il, $ilce);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'İl veya koordinat parametresi gerekli. Örnek: ?il=istanbul&ilce=kadikoy veya ?lat=41.0082&lon=28.9784',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if (!$coord) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Koordinat bulunamadı. Lütfen geçerli bir il/ilçe veya koordinat girin.',
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
    'il' => $il ? ucfirst($il) : null,
    'ilce' => $ilce ? ucfirst($ilce) : null,
    'konum' => $coord['city'] ?? null,
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