<?php
/**
 * Telegram Veri API - Tüm Kayıtlar + ID Sorgu + Arayüz
 */

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

define('DATA_FILE_1', 'data.txt');
define('DATA_FILE_2', 'data2.txt');

// === VERİ OKUMA (Tümünü döndürür) ===
function loadAllData() {
    $data = [];
    $seen = [];
    
    // data.txt oku
    if (file_exists(DATA_FILE_1)) {
        $lines = file(DATA_FILE_1, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($lines)) {
            for ($i = 1; $i < count($lines); $i++) {
                $parts = array_map('trim', explode(',', $lines[$i]));
                while (count($parts) < 4) $parts[] = '';
                $row = [
                    'id' => $parts[0] ?? '',
                    'phone' => $parts[3] ?? '',
                    'username' => $parts[2] ?? '',
                    'first_name' => $parts[1] ?? '',
                    'last_name' => ''
                ];
                if (!empty($row['id']) && !isset($seen[$row['id']])) {
                    $data[] = $row;
                    $seen[$row['id']] = true;
                }
            }
        }
    }
    
    // data2.txt oku
    if (file_exists(DATA_FILE_2)) {
        $lines = file(DATA_FILE_2, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($lines)) {
            for ($i = 1; $i < count($lines); $i++) {
                $parts = array_map('trim', explode('|', $lines[$i]));
                while (count($parts) < 5) $parts[] = '';
                $row = [
                    'id' => $parts[0] ?? '',
                    'phone' => $parts[1] ?? '',
                    'username' => $parts[2] ?? '',
                    'first_name' => $parts[3] ?? '',
                    'last_name' => $parts[4] ?? ''
                ];
                if (!empty($row['id']) && !isset($seen[$row['id']])) {
                    $data[] = $row;
                    $seen[$row['id']] = true;
                }
            }
        }
    }
    
    return $data;
}

// === ID ile sorgula ===
function getUserById($id, $data) {
    foreach ($data as $user) {
        if ($user['id'] === $id) {
            return $user;
        }
    }
    return null;
}

// === JSON Yanıt ===
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

// ID sorgusu (GET ile)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $allData = loadAllData();
    $user = getUserById(trim($_GET['id']), $allData);
    if ($user) {
        jsonResponse(['success' => true, 'data' => $user]);
    } else {
        jsonResponse(['success' => false, 'message' => 'ID bulunamadı'], 404);
    }
}

// === API ROUTES ===
$allData = loadAllData();

// /all
if ($path === 'all') {
    jsonResponse(['success' => true, 'total' => count($allData), 'data' => $allData]);
}

// /stats
if ($path === 'stats') {
    $total = count($allData);
    $withPhone = 0;
    $withUsername = 0;
    foreach ($allData as $user) {
        if (!empty($user['phone'])) $withPhone++;
        if (!empty($user['username'])) $withUsername++;
    }
    jsonResponse([
        'success' => true,
        'stats' => [
            'total' => $total,
            'with_phone' => $withPhone,
            'with_username' => $withUsername
        ]
    ]);
}

// /user/{id}
if (preg_match('/^user\/(.+)$/', $path, $matches)) {
    $user = getUserById($matches[1], $allData);
    if ($user) {
        jsonResponse(['success' => true, 'data' => $user]);
    } else {
        jsonResponse(['success' => false, 'message' => 'ID bulunamadı'], 404);
    }
}

// === ANA SAYFA (Arayüz) ===
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
        .container { max-width: 900px; margin: 0 auto; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
        h1 { color: #58a6ff; border-bottom: 2px solid #30363d; padding-bottom: 12px; margin-top: 0; }
        h3 { color: #f0f6fc; margin-top: 0; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; margin: 2px; }
        .badge-green { background: #238636; color: #fff; }
        .badge-red { background: #da3633; color: #fff; }
        .badge-yellow { background: #9e6a03; color: #fff; }
        input, button { padding: 10px 16px; border-radius: 8px; border: 1px solid #30363d; background: #0d1117; color: #c9d1d9; font-size: 14px; }
        input { flex: 1; min-width: 200px; }
        button { background: #238636; color: white; cursor: pointer; border: none; font-weight: 600; }
        button:hover { background: #2ea043; }
        .flex { display: flex; gap: 10px; flex-wrap: wrap; margin: 10px 0; }
        .result-box { background: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 16px; margin-top: 10px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 8px 12px; background: #161b22; border-bottom: 2px solid #30363d; }
        td { padding: 8px 12px; border-bottom: 1px solid #21262d; }
        .not-found { color: #f85149; text-align: center; padding: 20px; }
        .endpoint { background: #0d1117; padding: 8px 12px; border-radius: 6px; font-family: monospace; margin: 4px 0; border: 1px solid #21262d; }
        .file-info { background: #0d1117; padding: 12px; border-radius: 8px; border: 1px solid #21262d; margin-top: 8px; }
        .file-info span { color: #58a6ff; }
        pre { background: #0d1117; padding: 12px; border-radius: 8px; overflow-x: auto; border: 1px solid #21262d; font-size: 13px; }
        .url-example { color: #58a6ff; word-break: break-all; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>📱 Telegram Veri API</h1>
        <p><strong>Toplam kayıt:</strong> <?= count($allData) ?></p>
        <div class="file-info">
            📁 <span>data.txt</span> (TG_ID,FIRST_NAME,TG_USERNAME,PHONE): 
            <?= file_exists(DATA_FILE_1) ? number_format(filesize(DATA_FILE_1)) . ' bytes' : 'bulunamadı' ?>
            <br>
            📁 <span>data2.txt</span> (id|phone|username|first_name|last_name): 
            <?= file_exists(DATA_FILE_2) ? number_format(filesize(DATA_FILE_2)) . ' bytes' : 'bulunamadı' ?>
        </div>
        <div style="margin-top:12px;">
            <span class="badge badge-green">✅ Telefon var: <?= array_reduce($allData, fn($c,$u)=>$c+(!empty($u['phone'])?1:0), 0) ?></span>
            <span class="badge badge-red">❌ Telefon yok: <?= array_reduce($allData, fn($c,$u)=>$c+(empty($u['phone'])?1:0), 0) ?></span>
            <span class="badge badge-yellow">📌 Kullanıcı adı var: <?= array_reduce($allData, fn($c,$u)=>$c+(!empty($u['username'])?1:0), 0) ?></span>
        </div>
    </div>

    <div class="card">
        <h3>🔍 ID ile Sorgula</h3>
        <div class="flex">
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="id" placeholder="ID gir (örn: 1683933939)" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">
                <button type="submit">🔍 Sorgula</button>
            </form>
        </div>

        <?php if (isset($_GET['id']) && !empty($_GET['id'])): 
            $user = getUserById(trim($_GET['id']), $allData);
        ?>
        <div class="result-box">
            <h4>Sonuç:</h4>
            <?php if ($user): ?>
                <table>
                    <tr><th>ID</th><td><?= htmlspecialchars($user['id']) ?></td></tr>
                    <tr><th>Telefon</th><td><?= htmlspecialchars($user['phone'] ?: '—') ?></td></tr>
                    <tr><th>Kullanıcı Adı</th><td><?= htmlspecialchars($user['username'] ?: '—') ?></td></tr>
                    <tr><th>Ad</th><td><?= htmlspecialchars($user['first_name'] ?: '—') ?></td></tr>
                    <tr><th>Soyad</th><td><?= htmlspecialchars($user['last_name'] ?: '—') ?></td></tr>
                </table>
            <?php else: ?>
                <div class="not-found">❌ ID <?= htmlspecialchars($_GET['id']) ?> bulunamadı.</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>🔗 API Kullanımı</h3>
        <p>Base URL: <span class="url-example">https://freeapiservice-q08q.onrender.com/tg.php</span></p>

        <div class="endpoint"><strong>GET</strong> /tg.php?id={id} → ID ile sorgula</div>
        <div class="endpoint"><strong>GET</strong> /tg.php/all → Tüm veri (JSON)</div>
        <div class="endpoint"><strong>GET</strong> /tg.php/stats → İstatistikler (JSON)</div>
        <div class="endpoint"><strong>GET</strong> /tg.php/user/{id} → ID ile sorgula (JSON)</div>

        <h4 style="margin-top:16px;margin-bottom:8px;">📌 Örnekler:</h4>
        <pre>
https://freeapiservice-q08q.onrender.com/tg.php?id=1683933939
https://freeapiservice-q08q.onrender.com/tg.php/user/1683933939
https://freeapiservice-q08q.onrender.com/tg.php/all
https://freeapiservice-q08q.onrender.com/tg.php/stats
        </pre>

        <h4 style="margin-top:16px;margin-bottom:8px;">📤 Örnek Yanıt (JSON):</h4>
        <pre>{
  "success": true,
  "data": {
    "id": "1683933939",
    "phone": "79529637711",
    "username": "example_user",
    "first_name": "John",
    "last_name": "Doe"
  }
}</pre>
    </div>
</div>
</body>
</html>