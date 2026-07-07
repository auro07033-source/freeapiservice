<?php
/**
 * Telegram Veri API - Optimize Edilmiş (Bellek Dostu)
 * Büyük dosyaları satır satır okur, tüm veriyi RAM'e yüklemez.
 */

ini_set('memory_limit', '512M'); // Bellek limitini artır
ini_set('max_execution_time', 300); // Çalışma süresini uzat

define('DATA_FILE_1', 'data.txt');
define('DATA_FILE_2', 'data2.txt');

/**
 * data.txt (CSV) dosyasını satır satır okuyup filtrele
 */
function streamDataFile1($id = null, $phone = null, $username = null, $name = null) {
    if (!file_exists(DATA_FILE_1)) return [];
    
    $handle = fopen(DATA_FILE_1, 'r');
    $results = [];
    $isFirst = true;
    
    while (($line = fgets($handle)) !== false) {
        if ($isFirst) { $isFirst = false; continue; } // Başlık atla
        
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = array_map('trim', explode(',', $line));
        while (count($parts) < 4) $parts[] = '';
        
        $row = [
            'id' => $parts[0] ?? '',
            'phone' => $parts[3] ?? '',
            'username' => $parts[2] ?? '',
            'first_name' => $parts[1] ?? '',
            'last_name' => ''
        ];
        
        if (empty($row['id'])) continue;
        
        // Filtreleme
        if ($id && $row['id'] !== $id) continue;
        if ($phone && strpos(str_replace([' ', '-', '+'], '', $row['phone']), str_replace([' ', '-', '+'], '', $phone)) === false) continue;
        if ($username && stripos($row['username'], $username) === false) continue;
        if ($name && stripos($row['first_name'] . $row['last_name'], $name) === false) continue;
        
        $results[] = $row;
        
        // Sonuç limiti
        if (count($results) >= 10000) break;
    }
    
    fclose($handle);
    return $results;
}

/**
 * data2.txt (Pipe) dosyasını satır satır oku
 */
function streamDataFile2($id = null, $phone = null, $username = null, $name = null) {
    if (!file_exists(DATA_FILE_2)) return [];
    
    $handle = fopen(DATA_FILE_2, 'r');
    $results = [];
    $isFirst = true;
    $headers = ['id', 'phone', 'username', 'first_name', 'last_name'];
    
    while (($line = fgets($handle)) !== false) {
        if ($isFirst) { $isFirst = false; continue; }
        
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = array_map('trim', explode('|', $line));
        while (count($parts) < 5) $parts[] = '';
        
        $row = [
            'id' => $parts[0] ?? '',
            'phone' => $parts[1] ?? '',
            'username' => $parts[2] ?? '',
            'first_name' => $parts[3] ?? '',
            'last_name' => $parts[4] ?? ''
        ];
        
        if (empty($row['id'])) continue;
        
        // Filtreleme
        if ($id && $row['id'] !== $id) continue;
        if ($phone && strpos(str_replace([' ', '-', '+'], '', $row['phone']), str_replace([' ', '-', '+'], '', $phone)) === false) continue;
        if ($username && stripos($row['username'], $username) === false) continue;
        if ($name && stripos($row['first_name'] . $row['last_name'], $name) === false) continue;
        
        $results[] = $row;
        
        if (count($results) >= 10000) break;
    }
    
    fclose($handle);
    return $results;
}

/**
 * Birleşik arama
 */
function searchAll($id = null, $phone = null, $username = null, $name = null) {
    $results = [];
    $seen = [];
    
    // data.txt'den ara
    $file1 = streamDataFile1($id, $phone, $username, $name);
    foreach ($file1 as $row) {
        if (!isset($seen[$row['id']])) {
            $results[] = $row;
            $seen[$row['id']] = true;
        }
    }
    
    // data2.txt'den ara
    $file2 = streamDataFile2($id, $phone, $username, $name);
    foreach ($file2 as $row) {
        if (!isset($seen[$row['id']])) {
            $results[] = $row;
            $seen[$row['id']] = true;
        }
    }
    
    return $results;
}

/**
 * ID ile tekil arama (ilk eşleşme)
 */
function searchById($id) {
    $result = streamDataFile1($id);
    if (!empty($result)) return $result[0];
    
    $result = streamDataFile2($id);
    if (!empty($result)) return $result[0];
    
    return null;
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// === ROUTE ===
$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH);
$path = str_replace('/tg.php', '', $path);
$path = trim($path, '/');

// All
if ($path === 'all' || $path === '') {
    $results = searchAll();
    jsonResponse(['success' => true, 'total' => count($results), 'data' => $results]);
}

// Stats
if ($path === 'stats') {
    $total = 0;
    $withPhone = 0;
    $withUsername = 0;
    
    // Sadece sayıları al (tüm veriyi yüklemeden)
    $handle = fopen(DATA_FILE_1, 'r');
    $isFirst = true;
    while (($line = fgets($handle)) !== false) {
        if ($isFirst) { $isFirst = false; continue; }
        $parts = explode(',', $line);
        if (count($parts) >= 4) {
            $total++;
            if (!empty(trim($parts[3]))) $withPhone++;
            if (!empty(trim($parts[2]))) $withUsername++;
        }
    }
    fclose($handle);
    
    $handle = fopen(DATA_FILE_2, 'r');
    $isFirst = true;
    while (($line = fgets($handle)) !== false) {
        if ($isFirst) { $isFirst = false; continue; }
        $parts = explode('|', $line);
        if (count($parts) >= 3) {
            $total++;
            if (!empty(trim($parts[1] ?? ''))) $withPhone++;
            if (!empty(trim($parts[2] ?? ''))) $withUsername++;
        }
    }
    fclose($handle);
    
    jsonResponse([
        'success' => true,
        'stats' => [
            'total' => $total,
            'with_phone' => $withPhone,
            'with_username' => $withUsername
        ]
    ]);
}

// User by ID
if (preg_match('/^user\/(.+)$/', $path, $matches)) {
    $id = $matches[1];
    $user = searchById($id);
    if ($user) {
        jsonResponse(['success' => true, 'data' => $user]);
    } else {
        jsonResponse(['success' => false, 'message' => "ID $id bulunamadı"], 404);
    }
}

// Search
if ($path === 'search') {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
    
    if ($q === '') {
        jsonResponse(['success' => false, 'message' => 'q parametresi gerekli'], 400);
    }
    
    $results = [];
    $resultType = '';
    
    // ID ile ara
    if ($type === 'all' || $type === 'id') {
        $user = searchById($q);
        if ($user) {
            jsonResponse(['success' => true, 'data' => $user, 'type' => 'id']);
        }
    }
    
    // Telefon ile ara
    if ($type === 'all' || $type === 'phone') {
        $results = searchAll(null, $q);
        if (!empty($results)) {
            jsonResponse(['success' => true, 'data' => $results, 'type' => 'phone']);
        }
    }
    
    // Kullanıcı adı ile ara
    if ($type === 'all' || $type === 'username') {
        $results = searchAll(null, null, $q);
        if (!empty($results)) {
            jsonResponse(['success' => true, 'data' => $results, 'type' => 'username']);
        }
    }
    
    // İsim ile ara
    if ($type === 'all' || $type === 'name') {
        $results = searchAll(null, null, null, $q);
        if (!empty($results)) {
            jsonResponse(['success' => true, 'data' => $results, 'type' => 'name']);
        }
    }
    
    jsonResponse(['success' => false, 'message' => 'Sonuç bulunamadı'], 404);
}

// Varsayılan: Bilgi
jsonResponse([
    'success' => true,
    'message' => 'Telegram Veri API',
    'endpoints' => [
        '/all' => 'Tüm veri',
        '/user/{id}' => 'ID ile sorgula',
        '/search?q={query}' => 'Arama',
        '/stats' => 'İstatistikler'
    ]
]);