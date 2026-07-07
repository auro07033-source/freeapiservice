<?php
/**
 * Telegram Veri API - Çift Dosya Desteği (Farklı Formatlar)
 * data.txt: TG_ID,FIRST_NAME,TG_USERNAME,PHONE (CSV formatı)
 * data2.txt: id|phone|username|first_name|last_name (Pipe formatı)
 */

// Dosya yolları
define('DATA_FILE_1', 'data.txt');
define('DATA_FILE_2', 'data2.txt');

/**
 * data.txt (CSV formatı) yükler
 * Format: TG_ID,FIRST_NAME,TG_USERNAME,PHONE
 */
function loadDataFile1() {
    $data = [];
    
    if (!file_exists(DATA_FILE_1)) {
        return $data;
    }
    
    $lines = file(DATA_FILE_1, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($lines)) {
        return $data;
    }
    
    // Başlıkları al (virgül ile ayrılmış)
    $headers = array_map('trim', explode(',', $lines[0]));
    
    // Veri satırlarını işle
    for ($i = 1; $i < count($lines); $i++) {
        $parts = array_map('trim', explode(',', $lines[$i]));
        
        // Eksik alanları doldur
        while (count($parts) < 4) {
            $parts[] = '';
        }
        
        // Standart formata dönüştür: id|phone|username|first_name|last_name
        $row = [
            'id' => $parts[0] ?? '',
            'phone' => $parts[3] ?? '',  // PHONE
            'username' => $parts[2] ?? '', // TG_USERNAME
            'first_name' => $parts[1] ?? '', // FIRST_NAME
            'last_name' => ''  // data.txt'de last_name yok
        ];
        
        // Sadece ID varsa ekle
        if (!empty($row['id'])) {
            $data[] = $row;
        }
    }
    
    return $data;
}

/**
 * data2.txt (Pipe formatı) yükler
 * Format: id|phone|username|first_name|last_name
 */
function loadDataFile2() {
    $data = [];
    
    if (!file_exists(DATA_FILE_2)) {
        return $data;
    }
    
    $lines = file(DATA_FILE_2, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($lines)) {
        return $data;
    }
    
    // Başlıkları al (pipe ile ayrılmış)
    $headers = array_map('trim', explode('|', $lines[0]));
    
    // Veri satırlarını işle
    for ($i = 1; $i < count($lines); $i++) {
        $parts = array_map('trim', explode('|', $lines[$i]));
        
        // Eksik alanları doldur
        while (count($parts) < count($headers)) {
            $parts[] = '';
        }
        
        // Header'lara göre eşleştir
        $row = [];
        foreach ($headers as $index => $header) {
            $row[$header] = $parts[$index] ?? '';
        }
        
        // Standart formata dönüştür
        $standardRow = [
            'id' => $row['id'] ?? '',
            'phone' => $row['phone'] ?? '',
            'username' => $row['username'] ?? '',
            'first_name' => $row['first_name'] ?? '',
            'last_name' => $row['last_name'] ?? ''
        ];
        
        if (!empty($standardRow['id'])) {
            $data[] = $standardRow;
        }
    }
    
    return $data;
}

/**
 * Tüm veriyi yükle (iki dosyayı birleştir)
 */
function loadAllData() {
    $data = [];
    $seenIds = [];
    
    // 1. data.txt'yi yükle
    $file1Data = loadDataFile1();
    foreach ($file1Data as $row) {
        $id = $row['id'];
        if (!isset($seenIds[$id])) {
            $data[] = $row;
            $seenIds[$id] = true;
        }
    }
    
    // 2. data2.txt'yi yükle
    $file2Data = loadDataFile2();
    foreach ($file2Data as $row) {
        $id = $row['id'];
        if (!isset($seenIds[$id])) {
            $data[] = $row;
            $seenIds[$id] = true;
        }
    }
    
    return $data;
}

/**
 * TG_ID ile kullanıcı ara
 */
function getUserById($tgId, $data) {
    $tgId = trim($tgId);
    if ($tgId === '') {
        return null;
    }
    
    foreach ($data as $user) {
        if ($user['id'] === $tgId) {
            return $user;
        }
    }
    return null;
}

/**
 * Telefon numarası ile ara (kısmi eşleşme)
 */
function searchByPhone($phone, $data) {
    $phone = trim(str_replace([' ', '-', '+'], '', $phone));
    if ($phone === '') {
        return [];
    }
    
    $results = [];
    foreach ($data as $user) {
        $userPhone = trim(str_replace([' ', '-', '+'], '', $user['phone'] ?? ''));
        if (strpos($userPhone, $phone) !== false || strpos($phone, $userPhone) !== false) {
            $results[] = $user;
        }
    }
    return $results;
}

/**
 * Kullanıcı adı ile ara (kısmi eşleşme)
 */
function searchByUsername($username, $data) {
    $username = strtolower(trim($username));
    if ($username === '') {
        return [];
    }
    
    $results = [];
    foreach ($data as $user) {
        $userUsername = strtolower(trim($user['username'] ?? ''));
        if (strpos($userUsername, $username) !== false) {
            $results[] = $user;
        }
    }
    return $results;
}

/**
 * İsim ile ara (kısmi eşleşme)
 */
function searchByName($name, $data) {
    $name = strtolower(trim($name));
    if ($name === '') {
        return [];
    }
    
    $results = [];
    foreach ($data as $user) {
        $firstName = strtolower(trim($user['first_name'] ?? ''));
        $lastName = strtolower(trim($user['last_name'] ?? ''));
        if (strpos($firstName, $name) !== false || strpos($lastName, $name) !== false) {
            $results[] = $user;
        }
    }
    return $results;
}

/**
 * JSON yanıt gönder
 */
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// === VERİYİ YÜKLE ===
$data = loadAllData();

// === API ROUTES ===

// URL path'ini al
$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH);
$path = str_replace('/api.php', '', $path);
$path = trim($path, '/');

// === ROUTE: /api/all ===
if ($path === 'all' || $path === '') {
    jsonResponse([
        'success' => true,
        'total' => count($data),
        'data' => $data
    ]);
}

// === ROUTE: /api/stats ===
if ($path === 'stats') {
    $total = count($data);
    $withPhone = 0;
    $withUsername = 0;
    $withName = 0;
    
    foreach ($data as $user) {
        if (!empty($user['phone'])) $withPhone++;
        if (!empty($user['username'])) $withUsername++;
        if (!empty($user['first_name']) || !empty($user['last_name'])) $withName++;
    }
    
    jsonResponse([
        'success' => true,
        'stats' => [
            'total' => $total,
            'with_phone' => $withPhone,
            'with_username' => $withUsername,
            'with_name' => $withName,
            'missing_phone' => $total - $withPhone,
            'missing_username' => $total - $withUsername,
            'missing_name' => $total - $withName
        ],
        'source_files' => [
            'data.txt' => file_exists(DATA_FILE_1) ? filesize(DATA_FILE_1) : 0,
            'data2.txt' => file_exists(DATA_FILE_2) ? filesize(DATA_FILE_2) : 0
        ],
        'file_formats' => [
            'data.txt' => 'TG_ID,FIRST_NAME,TG_USERNAME,PHONE (CSV)',
            'data2.txt' => 'id|phone|username|first_name|last_name (Pipe)'
        ]
    ]);
}

// === ROUTE: /api/user/{id} ===
if (preg_match('/^user\/(.+)$/', $path, $matches)) {
    $tgId = $matches[1];
    $user = getUserById($tgId, $data);
    
    if ($user) {
        jsonResponse(['success' => true, 'data' => $user]);
    } else {
        jsonResponse(['success' => false, 'message' => "ID {$tgId} için sonuç bulunamadı"], 404);
    }
}

// === ROUTE: /api/search ===
if ($path === 'search') {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : 'all';
    
    if ($query === '') {
        jsonResponse(['success' => false, 'message' => 'q parametresi gerekli'], 400);
    }
    
    $results = [];
    $resultType = '';
    
    // ID ile ara
    if (is_numeric($query) && ($type === 'all' || $type === 'id')) {
        $user = getUserById($query, $data);
        if ($user) {
            jsonResponse(['success' => true, 'data' => $user, 'type' => 'id']);
        }
    }
    
    // Telefon ile ara
    if ($type === 'all' || $type === 'phone') {
        $phoneResults = searchByPhone($query, $data);
        if (!empty($phoneResults)) {
            jsonResponse(['success' => true, 'data' => $phoneResults, 'type' => 'phone']);
        }
    }
    
    // Kullanıcı adı ile ara
    if ($type === 'all' || $type === 'username') {
        $usernameResults = searchByUsername($query, $data);
        if (!empty($usernameResults)) {
            jsonResponse(['success' => true, 'data' => $usernameResults, 'type' => 'username']);
        }
    }
    
    // İsim ile ara
    if ($type === 'all' || $type === 'name') {
        $nameResults = searchByName($query, $data);
        if (!empty($nameResults)) {
            jsonResponse(['success' => true, 'data' => $nameResults, 'type' => 'name']);
        }
    }
    
    jsonResponse(['success' => false, 'message' => 'Sonuç bulunamadı'], 404);
}

// === Varsayılan: Ana sayfa ===
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Veri API</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; margin: 0; padding: 20px; }
        .container { max-width: 950px; margin: 0 auto; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
        h1 { color: #58a6ff; border-bottom: 2px solid #30363d; padding-bottom: 12px; margin-top: 0; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; margin: 2px; }
        .badge-green { background: #238636; color: #fff; }
        .badge-red { background: #da3633; color: #fff; }
        .badge-yellow { background: #9e6a03; color: #fff; }
        .badge-blue { background: #1f6feb; color: #fff; }
        input, button { padding: 10px 16px; border-radius: 8px; border: 1px solid #30363d; background: #0d1117; color: #c9d1d9; font-size: 14px; }
        input { flex: 1; min-width: 200px; }
        button { background: #238636; color: white; cursor: pointer; border: none; font-weight: 600; }
        button:hover { background: #2ea043; }
        .flex { display: flex; gap: 10px; flex-wrap: wrap; margin: 10px 0; }
        .result-box { background: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 16px; margin-top: 10px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 8px 12px; background: #161b22; border-bottom: 2px solid #30363d; }
        td { padding: 8px 12px; border-bottom: 1px solid #21262d; }
        .not-found { color: #f85149; text-align: center; padding: 20px; }
        .endpoint { background: #0d1117; padding: 8px 12px; border-radius: 6px; font-family: monospace; margin: 4px 0; border: 1px solid #21262d; }
        .file-info { background: #0d1117; padding: 12px; border-radius: 8px; border: 1px solid #21262d; margin-top: 8px; }
        .file-info span { color: #58a6ff; }
        .format-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 11px; background: #1f6feb; color: #fff; }
        .search-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
        .search-tabs a { padding: 6px 14px; border-radius: 20px; text-decoration: none; font-size: 13px; background: #21262d; color: #8b949e; }
        .search-tabs a.active { background: #238636; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>📱 Telegram Veri API</h1>
        <p><strong>Toplam kayıt:</strong> <?= count($data) ?></p>
        <div class="file-info">
            📁 <span>data.txt</span> (TG_ID,FIRST_NAME,TG_USERNAME,PHONE): 
            <?= file_exists(DATA_FILE_1) ? number_format(filesize(DATA_FILE_1)) . ' bytes' : 'bulunamadı' ?>
            <span class="format-badge">CSV</span>
            <br>
            📁 <span>data2.txt</span> (id|phone|username|first_name|last_name): 
            <?= file_exists(DATA_FILE_2) ? number_format(filesize(DATA_FILE_2)) . ' bytes' : 'bulunamadı' ?>
            <span class="format-badge">Pipe</span>
        </div>
        <div style="margin-top:12px;">
            <span class="badge badge-green">✅ Telefon var: <?= array_reduce($data, fn($c,$u)=>$c+(!empty($u['phone'])?1:0), 0) ?></span>
            <span class="badge badge-red">❌ Telefon yok: <?= array_reduce($data, fn($c,$u)=>$c+(empty($u['phone'])?1:0), 0) ?></span>
            <span class="badge badge-yellow">📌 Kullanıcı adı var: <?= array_reduce($data, fn($c,$u)=>$c+(!empty($u['username'])?1:0), 0) ?></span>
            <span class="badge badge-blue">👤 İsim var: <?= array_reduce($data, fn($c,$u)=>$c+((!empty($u['first_name'])||!empty($u['last_name']))?1:0), 0) ?></span>
        </div>
    </div>

    <div class="card">
        <h3>🔍 Sorgula</h3>
        <div class="flex">
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="id" placeholder="ID (örn: 1485647396)" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">
                <button type="submit">🔍 ID Sorgula</button>
            </form>
        </div>
        <div class="flex">
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="phone" placeholder="Telefon (örn: 79529637711)" value="<?= htmlspecialchars($_GET['phone'] ?? '') ?>">
                <button type="submit">📞 Telefon Ara</button>
            </form>
        </div>
        <div class="flex">
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="username" placeholder="Kullanıcı adı" value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
                <button type="submit">@ Kullanıcı Ara</button>
            </form>
        </div>
        <div class="flex">
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="name" placeholder="İsim ara" value="<?= htmlspecialchars($_GET['name'] ?? '') ?>">
                <button type="submit">👤 İsim Ara</button>
            </form>
        </div>

        <?php
        $result = null;
        $searchId = $_GET['id'] ?? '';
        $searchPhone = $_GET['phone'] ?? '';
        $searchUsername = $_GET['username'] ?? '';
        $searchName = $_GET['name'] ?? '';

        if ($searchId !== '') {
            $result = getUserById($searchId, $data);
        } elseif ($searchPhone !== '') {
            $result = searchByPhone($searchPhone, $data);
        } elseif ($searchUsername !== '') {
            $result = searchByUsername($searchUsername, $data);
        } elseif ($searchName !== '') {
            $result = searchByName($searchName, $data);
        }

        if ($result !== null):
        ?>
        <div class="result-box">
            <h4>Sonuç:</h4>
            <?php if (isset($result['id']) && !is_array($result['id'])): ?>
                <table>
                    <tr><th>ID</th><td><?= htmlspecialchars($result['id'] ?? '') ?></td></tr>
                    <tr><th>Telefon</th><td><?= htmlspecialchars($result['phone'] ?? '—') ?></td></tr>
                    <tr><th>Kullanıcı Adı</th><td><?= htmlspecialchars($result['username'] ?? '—') ?></td></tr>
                    <tr><th>Ad</th><td><?= htmlspecialchars($result['first_name'] ?? '—') ?></td></tr>
                    <tr><th>Soyad</th><td><?= htmlspecialchars($result['last_name'] ?? '—') ?></td></tr>
                </table>
            <?php elseif (is_array($result) && count($result) > 0): ?>
                <table>
                    <tr><th>ID</th><th>Telefon</th><th>Kullanıcı Adı</th><th>Ad</th><th>Soyad</th></tr>
                    <?php foreach ($result as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['id'] ?? '') ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($user['username'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($user['first_name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($user['last_name'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p style="margin-top:10px;color:#8b949e;"><?= count($result) ?> sonuç bulundu.</p>
            <?php else: ?>
                <div class="not-found">❌ Sonuç bulunamadı.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>🔗 API Endpoint'leri</h3>
        <div class="endpoint">GET /api.php/all → Tüm veri</div>
        <div class="endpoint">GET /api.php/user/{id} → ID ile sorgula</div>
        <div class="endpoint">GET /api.php/search?q={query} → Arama (ID/telefon/kullanıcı adı/isim)</div>
        <div class="endpoint">GET /api.php/search?q={query}&type=phone → Sadece telefon ara</div>
        <div class="endpoint">GET /api.php/stats → İstatistikler</div>
    </div>
</div>
</body>
</html>