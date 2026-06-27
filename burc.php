<?php
// horoscope.php - Burç Yorum API
// Geliştirici: @zanetmez

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$developer = "@zanetmez";

// Burçlar listesi
$signs = [
    'koc' => ['tr' => 'Koç', 'en' => 'Aries', 'emoji' => '♈', 'date' => '21 Mart - 19 Nisan'],
    'boga' => ['tr' => 'Boğa', 'en' => 'Taurus', 'emoji' => '♉', 'date' => '20 Nisan - 20 Mayıs'],
    'ikizler' => ['tr' => 'İkizler', 'en' => 'Gemini', 'emoji' => '♊', 'date' => '21 Mayıs - 20 Haziran'],
    'yengec' => ['tr' => 'Yengeç', 'en' => 'Cancer', 'emoji' => '♋', 'date' => '21 Haziran - 22 Temmuz'],
    'aslan' => ['tr' => 'Aslan', 'en' => 'Leo', 'emoji' => '♌', 'date' => '23 Temmuz - 22 Ağustos'],
    'basak' => ['tr' => 'Başak', 'en' => 'Virgo', 'emoji' => '♍', 'date' => '23 Ağustos - 22 Eylül'],
    'terazi' => ['tr' => 'Terazi', 'en' => 'Libra', 'emoji' => '♎', 'date' => '23 Eylül - 22 Ekim'],
    'akrep' => ['tr' => 'Akrep', 'en' => 'Scorpio', 'emoji' => '♏', 'date' => '23 Ekim - 21 Kasım'],
    'yay' => ['tr' => 'Yay', 'en' => 'Sagittarius', 'emoji' => '♐', 'date' => '22 Kasım - 21 Aralık'],
    'oglak' => ['tr' => 'Oğlak', 'en' => 'Capricorn', 'emoji' => '♑', 'date' => '22 Aralık - 19 Ocak'],
    'kova' => ['tr' => 'Kova', 'en' => 'Aquarius', 'emoji' => '♒', 'date' => '20 Ocak - 18 Şubat'],
    'balik' => ['tr' => 'Balık', 'en' => 'Pisces', 'emoji' => '♓', 'date' => '19 Şubat - 20 Mart']
];

// Burç yorumları (gerçek AI'dan alınabilir)
$horoscopes = [
    'koc' => [
        'ask' => 'Bugün aşk hayatında sürpriz gelişmeler olabilir. Kalbini aç, yeni insanlara şans ver! ❤️',
        'is' => 'Kariyerinde yükseliş dönemindesin. Yeni projeler için cesur adımlar at! 💼',
        'saglik' => 'Enerjin yüksek, spor yapmak için harika bir gün. Yürüyüşe çık! 🏃‍♂️',
        'gunluk' => 'Bugün kendine güvenin tam. İç sesini dinle, doğru kararlar alacaksın. ✨'
    ],
    'boga' => [
        'ask' => 'Bugün romantizmin ön planda. Sevgiline sürpriz yapabilirsin! 💝',
        'is' => 'Maddi konularda şanslısın. Yatırım fırsatlarını değerlendir! 💰',
        'saglik' => 'Sağlığına dikkat et, bol su iç ve dinlenmeye zaman ayır. 🧘',
        'gunluk' => 'Sabırlı ol, her şey zamanında olacak. Rahatla ve akışa güven. 🌊'
    ],
    // Diğer burçlar için de AI'dan alınabilir
];

// ==================== PARAMETRELER ====================

$sign = $_GET['burc'] ?? $_GET['sign'] ?? null;
$type = $_GET['tip'] ?? $_GET['type'] ?? 'gunluk'; // ask, is, saglik, gunluk
$lang = $_GET['dil'] ?? $_GET['lang'] ?? 'tr';

// ==================== BURÇ LİSTESİ ====================

if (!$sign) {
    $list = [];
    foreach ($signs as $key => $value) {
        $list[] = [
            'key' => $key,
            'name' => $value['tr'],
            'emoji' => $value['emoji'],
            'date' => $value['date']
        ];
    }
    
    echo json_encode([
        'status' => 'success',
        'type' => 'list',
        'message' => 'Lütfen bir burç seçin. Örnek: ?burc=koc',
        'signs' => $list,
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ==================== BURÇ KONTROLÜ ====================

$signKey = strtolower(trim($sign));
if (!isset($signs[$signKey])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Geçersiz burç. Lütfen listeden birini seçin.',
        'available' => array_keys($signs),
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ==================== YORUM AL ====================

$horoscope = getHoroscope($signKey, $type);

echo json_encode([
    'status' => 'success',
    'type' => 'horoscope',
    'sign' => [
        'key' => $signKey,
        'name' => $signs[$signKey]['tr'],
        'emoji' => $signs[$signKey]['emoji'],
        'date' => $signs[$signKey]['date']
    ],
    'category' => $type,
    'category_tr' => categoryName($type),
    'horoscope' => $horoscope,
    'date' => date('d.m.Y'),
    'developer' => $developer
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

// ==================== FONKSİYONLAR ====================

function categoryName($type) {
    $map = [
        'ask' => 'Aşk',
        'is' => 'İş & Kariyer',
        'saglik' => 'Sağlık',
        'gunluk' => 'Günlük'
    ];
    return $map[$type] ?? 'Günlük';
}

function getHoroscope($sign, $type) {
    // Önce statik yorumları kontrol et
    if (isset($GLOBALS['horoscopes'][$sign][$type])) {
        return $GLOBALS['horoscopes'][$sign][$type];
    }
    
    // AI'den yorum al (DG AI)
    $prompt = "$sign burcu için $type yorumu yaz. Kısa, samimi ve motive edici olsun. Emoji kullan.";
    $aiResponse = getAIResponse($prompt);
    
    return $aiResponse ?: "Bugün için özel bir yorumumuz yok. Ama günün güzel geçsin! 🌟";
}

function getAIResponse($message) {
    $url = "https://dg-ai.scriptsnsenses.workers.dev/";
    
    $data = [
        "messages" => [
            ["role" => "system", "content" => "Sen bir astroloji uzmanısın. Burç yorumları yapıyorsun. Kısa, samimi ve motive edici cevaplar ver. Türkçe cevap ver."],
            ["role" => "user", "content" => $message]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) return null;
    
    $result = json_decode($response, true);
    
    if (isset($result['response']['choices'][0]['message']['content'])) {
        return $result['response']['choices'][0]['message']['content'];
    }
    
    return null;
}
?>