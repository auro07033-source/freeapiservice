<?php
// weatherapi.php - Hava Durumu API (Türkçe)
// Geliştirici: @zanetmez

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$developer = "@zanetmez";
$city = $_GET['sehir'] ?? '';

if (empty($city)) {
    echo json_encode([
        'hata' => 'Şehir parametresi gerekli. Örnek: ?sehir=istanbul',
        'geliştirici' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Koordinat al
$geoUrl = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($city) . "&count=1&language=tr&format=json";
$geoResponse = file_get_contents($geoUrl);
$geoData = json_decode($geoResponse, true);

if (!$geoData || !isset($geoData['results'][0])) {
    echo json_encode([
        'hata' => "'$city' bulunamadı.",
        'geliştirici' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$lat = $geoData['results'][0]['latitude'];
$lon = $geoData['results'][0]['longitude'];
$cityName = $geoData['results'][0]['name'] ?? $city;

// 2. Hava durumu al
$weatherUrl = "https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lon&current_weather=true&hourly=temperature_2m,relative_humidity_2m,wind_speed_10m&timezone=Europe/Istanbul&forecast_days=1";
$weatherResponse = file_get_contents($weatherUrl);
$weatherData = json_decode($weatherResponse, true);

if (!$weatherData) {
    echo json_encode([
        'hata' => 'Hava durumu alınamadı.',
        'geliştirici' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 3. Türkçe alan adlarıyla dönüştür
$result = [
    'enlem' => $weatherData['latitude'] ?? null,
    'boylam' => $weatherData['longitude'] ?? null,
    'generationtime_ms' => $weatherData['generationtime_ms'] ?? null,
    'utc_offset_seconds' => $weatherData['utc_offset_seconds'] ?? null,
    'saat_dilimi' => $weatherData['timezone'] ?? null,
    'saat_dilimi_kisaltmasi' => $weatherData['timezone_abbreviation'] ?? null,
    'yukseklik' => $weatherData['elevation'] ?? null,
    'mevcut_hava_birimleri' => [
        'zaman' => $weatherData['current_weather_units']['time'] ?? null,
        'aralik' => $weatherData['current_weather_units']['interval'] ?? null,
        'sicaklik' => $weatherData['current_weather_units']['temperature'] ?? null,
        'ruzgar_hizi' => $weatherData['current_weather_units']['windspeed'] ?? null,
        'ruzgar_yonu' => $weatherData['current_weather_units']['winddirection'] ?? null,
        'is_day' => $weatherData['current_weather_units']['is_day'] ?? null,
        'hava_durumu_kodu' => $weatherData['current_weather_units']['weathercode'] ?? null
    ],
    'guncel_hava_durumu' => [
        'zaman' => $weatherData['current_weather']['time'] ?? null,
        'aralik' => $weatherData['current_weather']['interval'] ?? null,
        'sicaklik' => $weatherData['current_weather']['temperature'] ?? null,
        'ruzgar_hizi' => $weatherData['current_weather']['windspeed'] ?? null,
        'ruzgar_yonu' => $weatherData['current_weather']['winddirection'] ?? null,
        'is_day' => $weatherData['current_weather']['is_day'] ?? null,
        'hava_durumu_kodu' => $weatherData['current_weather']['weathercode'] ?? null
    ],
    'saatlik_birimler' => [
        'zaman' => $weatherData['hourly_units']['time'] ?? null,
        'sicaklik_2m' => $weatherData['hourly_units']['temperature_2m'] ?? null,
        'bagil_nem_2m' => $weatherData['hourly_units']['relative_humidity_2m'] ?? null,
        'ruzgar_hizi_10m' => $weatherData['hourly_units']['wind_speed_10m'] ?? null
    ],
    'geliştirici' => $developer
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);