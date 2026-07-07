<?php
/**
 * Telegram Veri API - PHP
 * data.txt dosyasından veri çeker ve API ile sorgulama sağlar
 */

// Dosya yolu
define('DATA_FILE', 'data.txt');

/**
 * data.txt dosyasını okur ve array'e dönüştürür
 */
function loadData() {
    $data = [];
    
    if (!file_exists(DATA_FILE)) {
        return $data;
    }
    
    $lines = file(DATA_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (empty($lines)) {
        return $data;
    }
    
    // Başlıkları al
    $headers = array_map('trim', explode(',', $lines[0]));
    
    // Veri satırlarını işle
    for ($i = 1; $i < count($lines); $i++) {
        $parts = array_map('trim', explode(',', $lines[$i]));
        
        // Eksik alanları doldur
        while (count($parts) < 4) {
            $parts[] = '';
        }
        
        $row = [
            'TG_ID' => $parts[0] ?? '',
            'FIRST_NAME' => $parts[1] ?? '',
            'TG_USERNAME' => $parts[2] ?? '',
            'PHONE' => $parts[3] ?? ''
        ];
        
        $data[] = $row;
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
        if ($user['TG_ID'] === $tgId) {
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
        $userPhone = trim(str_replace([' ', '-', '+'], '', $user['PHONE']));
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
        $userUsername = strtolower(trim($user['TG_USERNAME']));
        if (strpos($userUsername, $username) !== false) {
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

// === API ROUTES ===

// Veriyi yükle
$data = loadData();

// URL path'ini al
$path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($path, PHP_URL_PATH);
$path = str_replace('/api.php', '', $path);
$path = trim($path, '/');

// Method
$method = $_SERVER['REQUEST_METHOD'];

// === ROUTE: /api/all ===
if ($path === 'all' || $path === '') {
    jsonResponse([
        'success' => true,
        'total' => count($data),
        'data' => $data
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
    
    if ($query === '') {
        jsonResponse(['success' => false, 'message' => 'q parametresi gerekli'], 400);
    }
    
    // Önce ID ile dene
    if (is_numeric($query)) {
        $user = getUserById($query, $data);
        if ($user) {
            jsonResponse(['success' => true, 'data' => $user, 'type' => 'id']);
        }
    }
    
    // Telefon ile dene
    $phoneResults = searchByPhone($query, $data);
    if (!empty($phoneResults)) {
        jsonResponse(['success' => true, 'data' => $phoneResults, 'type' => 'phone']);
    }
    
    // Kullanıcı adı ile dene
    $usernameResults = searchByUsername($query, $data);
    if (!empty($usernameResults)) {
        jsonResponse(['success' => true, 'data' => $usernameResults, 'type' => 'username']);
    }
    
    jsonResponse(['success' => false, 'message' => 'Sonuç bulunamadı'], 404);
}

// === ROUTE: /api/upload - Dosya yükleme ===
if ($path === 'upload' && $method === 'POST') {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['success' => false, 'message' => 'Dosya yüklenemedi'], 400);
    }
    
    $file = $_FILES['file'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Sadece txt ve csv kabul et
    if (!in_array(strtolower($ext), ['txt', 'csv'])) {
        jsonResponse(['success' => false, 'message' => 'Sadece .txt veya .csv dosyası kabul edilir'], 400);
    }
    
    // Dosyayı kaydet
    if (move_uploaded_file($file['tmp_name'], DATA_FILE)) {
        // Yeni veriyi yükle
        $newData = loadData();
        jsonResponse([
            'success' => true,
            'message' => 'Dosya başarıyla yüklendi',
            'total' => count($newData)
        ]);
    } else {
        jsonResponse(['success' => false, 'message' => 'Dosya kaydedilemedi'], 500);
    }
}

// === ROUTE: /api/stats ===
if ($path === 'stats') {
    $total = count($data);
    $withPhone = 0;
    $withUsername = 0;
    
    foreach ($data as $user) {
        if (!empty($user['PHONE'])) $withPhone++;
        if (!empty($user['TG_USERNAME'])) $withUsername++;
    }
    
    jsonResponse([
        'success' => true,
        'stats' => [
            'total' => $total,
            'with_phone' => $withPhone,
            'with_username' => $withUsername,
            'missing_phone' => $total - $withPhone,
            'missing_username' => $total - $withUsername
        ]
    ]);
}

// === Varsayılan: Ana sayfa (Web arayüzü) ===
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
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 13px; margin: 2px; }
        .badge-green { background: #238636; color: #fff; }
        .badge-red { background: #da3633; color: #fff; }
        .badge-yellow { background: #9e6a03; color: #fff; }
        input, button { padding: 10px 16px; border-radius: 8px; border: 1px solid #30363d; background: #0d1117; color: #c9d1d9; font-size: 14px; }
        input { flex: 1; min-width: 200px; }
        button { background: #238636; color: white; cursor: pointer; border: none; font-weight: 600; }
        button:hover { background: #2ea043; }
        .flex { display: flex; gap: 10px; flex-wrap: wrap; margin: 10px 0; }
        .result-box { background: #0d1117; border: 1px solid #30363d; border-radius: 8px; padding: 16px; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 8px 12px; background: #161b22; border-bottom: 2px solid #30363d; }
        td { padding: 8px 12px; border-bottom: 1px solid #21262d; }
        .not-found { color: #f85149; text-align: center; padding: 20px; }
        .endpoint { background: #0d1117; padding: 8px 12px; border-radius: 6px; font-family: monospace; margin: 4px 0; border: 1px solid #21262d; }
        .upload-area { border: 2px dashed #30363d; border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; }
        .upload-area:hover { border-color: #58a6ff; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>📱 Telegram Veri API</h1>
        <p><strong>Toplam kayıt:</strong> <?= count($data) ?></p>
        <div>
            <span class="badge badge-green">✅ Telefon var: <?= array_reduce($data, fn($c,$u)=>$c+(!empty($u['PHONE'])?1:0), 0) ?></span>
            <span class="badge badge-red">❌ Telefon yok: <?= array_reduce($data, fn($c,$u)=>$c+(empty($u['PHONE'])?1:0), 0) ?></span>
        </div>
    </div>

    <div class="card">
        <h3>🔍 Sorgula</h3>
        <div class="flex">
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="id" placeholder="TG_ID (örn: 1448535818)" value="<?= htmlspecialchars($_GET['id'] ?? '') ?>">
                <button type="submit">ID Sorgula</button>
            </form>
        </div>
        <div class="flex">
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="phone" placeholder="Telefon (örn: 79251274133)" value="<?= htmlspecialchars($_GET['phone'] ?? '') ?>">
                <button type="submit">📞 Telefon Ara</button>
            </form>
        </div>
        <div class="flex">
            <form method="GET" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
                <input type="text" name="username" placeholder="Kullanıcı adı" value="<?= htmlspecialchars($_GET['username'] ?? '') ?>">
                <button type="submit">@ Kullanıcı Ara</button>
            </form>
        </div>

        <?php
        $result = null;
        $searchId = $_GET['id'] ?? '';
        $searchPhone = $_GET['phone'] ?? '';
        $searchUsername = $_GET['username'] ?? '';

        if ($searchId !== '') {
            $result = getUserById($searchId, $data);
        } elseif ($searchPhone !== '') {
            $result = searchByPhone($searchPhone, $data);
        } elseif ($searchUsername !== '') {
            $result = searchByUsername($searchUsername, $data);
        }

        if ($result !== null):
        ?>
        <div class="result-box">
            <h4>Sonuç:</h4>
            <?php if (isset($result['TG_ID'])): ?>
                <table>
                    <tr><th>TG_ID</th><td><?= htmlspecialchars($result['TG_ID']) ?></td></tr>
                    <tr><th>İsim</th><td><?= htmlspecialchars($result['FIRST_NAME']) ?></td></tr>
                    <tr><th>Kullanıcı Adı</th><td><?= htmlspecialchars($result['TG_USERNAME'] ?: '—') ?></td></tr>
                    <tr><th>Telefon</th><td><?= htmlspecialchars($result['PHONE'] ?: '—') ?></td></tr>
                </table>
            <?php elseif (is_array($result) && count($result) > 0): ?>
                <table>
                    <tr><th>TG_ID</th><th>İsim</th><th>Kullanıcı Adı</th><th>Telefon</th></tr>
                    <?php foreach ($result as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['TG_ID']) ?></td>
                        <td><?= htmlspecialchars($user['FIRST_NAME']) ?></td>
                        <td><?= htmlspecialchars($user['TG_USERNAME'] ?: '—') ?></td>
                        <td><?= htmlspecialchars($user['PHONE'] ?: '—') ?></td>
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
        <h3>📤 Dosya Yükle</h3>
        <form method="POST" enctype="multipart/form-data" action="?upload=1">
            <div class="upload-area" onclick="document.getElementById('fileInput').click()">
                <p>📁 <strong>data.txt</strong> dosyasını sürükleyin veya tıklayın</p>
                <input type="file" name="file" id="fileInput" accept=".txt,.csv" style="display:none" onchange="this.form.submit()">
            </div>
        </form>
        <?php if (isset($_GET['upload']) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <?php
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                if (in_array(strtolower($ext), ['txt', 'csv'])) {
                    if (move_uploaded_file($_FILES['file']['tmp_name'], DATA_FILE)) {
                        echo '<p style="color:#3fb950;">✅ Dosya başarıyla yüklendi!</p>';
                    } else {
                        echo '<p style="color:#f85149;">❌ Dosya kaydedilemedi.</p>';
                    }
                } else {
                    echo '<p style="color:#f85149;">❌ Sadece .txt veya .csv dosyası kabul edilir.</p>';
                }
            }
            ?>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>🔗 API Endpoint'leri</h3>
        <div class="endpoint">GET /api.php/all → Tüm veri</div>
        <div class="endpoint">GET /api.php/user/{TG_ID} → ID ile sorgula</div>
        <div class="endpoint">GET /api.php/search?q={query} → Arama</div>
        <div class="endpoint">GET /api.php/stats → İstatistikler</div>
        <div class="endpoint">POST /api.php/upload → Dosya yükle</div>
    </div>
</div>
</body>
</html>