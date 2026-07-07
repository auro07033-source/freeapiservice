<?php
/**
 * Telegram Veri API - Bellek Dostu (Stream ile okuma)
 * Veriyi RAM'e yüklemez, satır satır okur.
 */

ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

define('DATA_FILE_1', 'data.txt');
define('DATA_FILE_2', 'data2.txt');

// === ID ile sorgula (Stream) ===
function getUserByIdStream($id) {
    $id = trim($id);
    if ($id === '') return null;
    
    // data.txt'de ara
    if (file_exists(DATA_FILE_1)) {
        $handle = fopen(DATA_FILE_1, 'r');
        $isFirst = true;
        while (($line = fgets($handle)) !== false) {
            if ($isFirst) { $isFirst = false; continue; }
            $parts = array_map('trim', explode(',', $line));
            if (($parts[0] ?? '') === $id) {
                fclose($handle);
                return [
                    'id' => $parts[0] ?? '',
                    'phone' => $parts[3] ?? '',
                    'username' => $parts[2] ?? '',
                    'first_name' => $parts[1] ?? '',
                    'last_name' => ''
                ];
            }
        }
        fclose($handle);
    }
    
    // data2.txt'de ara
    if (file_exists(DATA_FILE_2)) {
        $handle = fopen(DATA_FILE_2, 'r');
        $isFirst = true;
        while (($line = fgets($handle)) !== false) {
            if ($isFirst) { $isFirst = false; continue; }
            $parts = array_map('trim', explode('|', $line));
            if (($parts[0] ?? '') === $id) {
                fclose($handle);
                return [
                    'id' => $parts[0] ?? '',
                    'phone' => $parts[1] ?? '',
                    'username' => $parts[2] ?? '',
                    'first_name' => $parts[3] ?? '',
                    'last_name' => $parts[4] ?? ''
                ];
            }
        }
        fclose($handle);
    }
    
    return null;
}

// === Stats (Stream ile say) ===
function getStatsStream() {
    $total = 0;
    $withPhone = 0;
    $withUsername = 0;
    
    // data.txt
    if (file_exists(DATA_FILE_1)) {
        $handle = fopen(DATA_FILE_1, 'r');
        $isFirst = true;
        while (($line = fgets($handle)) !== false) {
            if ($isFirst) { $isFirst = false; continue; }
            $parts = array_map('trim', explode(',', $line));
            if (!empty($parts[0])) {
                $total++;
                if (!empty($parts[3])) $withPhone++;
                if (!empty($parts[2])) $withUsername++;
            }
        }
        fclose($handle);
    }
    
    // data2.txt
    if (file_exists(DATA_FILE_2)) {
        $handle = fopen(DATA_FILE_2, 'r');
        $isFirst = true;
        while (($line = fgets($handle)) !== false) {
            if ($isFirst) { $isFirst = false; continue; }
            $parts = array_map('trim', explode('|', $line));
            if (!empty($parts[0])) {
                $total++;
                if (!empty($parts[1])) $withPhone++;
                if (!empty($parts[2])) $withUsername++;
            }
        }
        fclose($handle);
    }
    
    return ['total' => $total, 'with_phone' => $withPhone, 'with_username' => $withUsername];
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
    $user = getUserByIdStream(trim($_GET['id']));
    if ($user) {
        jsonResponse(['success' => true, 'data' => $user]);
    } else {
        jsonResponse(['success' => false, 'message' => 'ID bulunamadı'], 404);
    }
}

// /stats
if ($path === 'stats') {
    $stats = getStatsStream();
    jsonResponse(['success' => true, 'stats' => $stats]);
}

// /user/{id}
if (preg_match('/^user\/(.+)$/', $path, $matches)) {
    $user = getUserByIdStream($matches[1]);
    if ($user) {
        jsonResponse(['success' => true, 'data' => $user]);
    } else {
        jsonResponse(['success' => false, 'message' => 'ID bulunamadı'], 404);
    }
}

// /all - LIMIT 5000 (RAM koruması)
if ($path === 'all') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5000;
    if ($limit > 10000) $limit = 10000;
    
    $results = [];
    $count = 0;
    
    // data.txt
    if (file_exists(DATA_FILE_1)) {
        $handle = fopen(DATA_FILE_1, 'r');
        $isFirst = true;
        while (($line = fgets($handle)) !== false && $count < $limit) {
            if ($isFirst) { $isFirst = false; continue; }
            $parts = array_map('trim', explode(',', $line));
            if (!empty($parts[0])) {
                $results[] = [
                    'id' => $parts[0] ?? '',
                    'phone' => $parts[3] ?? '',
                    'username' => $parts[2] ?? '',
                    'first_name' => $parts[1] ?? '',
                    'last_name' => ''
                ];
                $count++;
            }
        }
        fclose($handle);
    }
    
    // data2.txt (limit dolmadıysa)
    if ($count < $limit && file_exists(DATA_FILE_2)) {
        $handle = fopen(DATA_FILE_2, 'r');
        $isFirst = true;
        while (($line = fgets($handle)) !== false && $count < $limit) {
            if ($isFirst) { $isFirst = false; continue; }
            $parts = array_map('trim', explode('|', $line));
            if (!empty($parts[0])) {
                $results[] = [
                    'id' => $parts[0] ?? '',
                    'phone' => $parts[1] ?? '',
                    'username' => $parts[2] ?? '',
                    'first_name' => $parts[3] ?? '',
                    'last_name' => $parts[4] ?? ''
                ];
                $count++;
            }
        }
        fclose($handle);
    }
    
    jsonResponse(['success' => true, 'total' => $count, 'data' => $results]);
}

// === ANA SAYFA (Arayüz) ===
$stats = getStatsStream();
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
        <p><strong>Toplam kayıt:</strong> <?= $stats['total'] ?></p>
        <div class="file-info">
            📁 <span>data.txt</span>: <?= file_exists(DATA_FILE_1) ? number_format(filesize(DATA_FILE_1)) . ' bytes' : 'bulunamadı' ?><br>
            📁 <span>data2.txt</span>: <?= file_exists(DATA_FILE_2) ? number_format(filesize(DATA_FILE_2)) . ' bytes' : 'bulunamadı' ?>
        </div>
        <div style="margin-top:12px;">
            <span class="badge badge-green">✅ Telefon var: <?= $stats['with_phone'] ?></span>
            <span class="badge badge-red">❌ Telefon yok: <?= $stats['total'] - $stats['with_phone'] ?></span>
            <span class="badge badge-yellow">📌 Kullanıcı adı var: <?= $stats['with_username'] ?></span>
        </div>
        <div style="margin-top:8px;font-size:13px;color:#8b949e;">
            ⚡ Bellek dostu mod: Veri RAM'e yüklenmez, satır satır okunur.
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
            $user = getUserByIdStream(trim($_GET['id']));
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
        <div class="endpoint"><strong>GET</strong> /tg.php/all?limit=100 → Tüm veri (JSON, limit varsayılan 5000)</div>
        <div class="endpoint"><strong>GET</strong> /tg.php/stats → İstatistikler (JSON)</div>
        <div class="endpoint"><strong>GET</strong> /tg.php/user/{id} → ID ile sorgula (JSON)</div>

        <h4 style="margin-top:16px;margin-bottom:8px;">📌 Örnekler:</h4>
        <pre>
https://freeapiservice-q08q.onrender.com/tg.php?id=1683933939
https://freeapiservice-q08q.onrender.com/tg.php/user/1683933939
https://freeapiservice-q08q.onrender.com/tg.php/all?limit=100
https://freeapiservice-q08q.onrender.com/tg.php/stats
        </pre>
    </div>
</div>
</body>
</html>