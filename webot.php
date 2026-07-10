<?php
/**
 * Telegram Bot Yönetim Paneli - HERKESE AÇIK
 * Webhook ve bot yönetimi
 */

// ==================== KONFIGÜRASYON ====================
define('BOT_TOKEN', '8909832773:AAFV19brKwLojmm8q0S--2aZ4kx1fIpPF08');
$db_file = 'telegram_bots.json';
$log_file = 'telegram_logs.txt';

// ==================== VERİTABANI ====================
function loadBots() {
    global $db_file;
    if (!file_exists($db_file)) return ['bots' => []];
    $data = json_decode(file_get_contents($db_file), true);
    return $data ?: ['bots' => []];
}

function saveBots($data) {
    global $db_file;
    file_put_contents($db_file, json_encode($data, JSON_PRETTY_PRINT));
}

// ==================== TELEGRAM API ====================
function tgRequest($method, $params = []) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function sendMessage($chat_id, $text, $parse_mode = 'HTML') {
    return tgRequest('sendMessage', ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => $parse_mode]);
}

function getBotInfo($token) {
    $ch = curl_init("https://api.telegram.org/bot{$token}/getMe");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function setWebhook($token, $url) {
    $ch = curl_init("https://api.telegram.org/bot{$token}/setWebhook?url=" . urlencode($url));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

function deleteWebhook($token) {
    $ch = curl_init("https://api.telegram.org/bot{$token}/deleteWebhook");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

// ==================== WEBHOOK HANDLER ====================
// Bot mesajlarını alır ve işler - HERKES İÇİN AÇIK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['webhook'])) {
    $input = file_get_contents('php://input');
    $update = json_decode($input, true);
    
    // Log
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] WEBHOOK: " . substr($input, 0, 500) . "\n", FILE_APPEND);
    
    if ($update && isset($update['message'])) {
        $chat_id = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';
        $username = $update['message']['from']['username'] ?? 'bilinmeyen';
        $first_name = $update['message']['from']['first_name'] ?? '';
        
        // Log
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . "] $username ($chat_id): $text\n", FILE_APPEND);
        
        // Basit komutlar
        if ($text === '/start') {
            sendMessage($chat_id, "👋 Hoş geldin $first_name!\n\n📌 <b>Komutlar:</b>\n/start - Başlat\n/help - Yardım\n/about - Hakkında\n/botlar - Bot listesi");
        } elseif ($text === '/help') {
            sendMessage($chat_id, "📖 <b>Yardım Menüsü</b>\n\n/start - Başlat\n/help - Yardım\n/about - Hakkında\n/botlar - Bot listesi");
        } elseif ($text === '/about') {
            sendMessage($chat_id, "🤖 Bu bot, Telegram Bot Yönetim Paneli tarafından yönetiliyor.\n\n💻 @zanetmez");
        } elseif ($text === '/botlar') {
            $bots = loadBots();
            $msg = "📋 <b>Bot Listesi</b>\n\n";
            if (empty($bots['bots'])) {
                $msg .= "Henüz bot eklenmemiş.";
            } else {
                foreach ($bots['bots'] as $id => $bot) {
                    $status = ($bot['active'] ?? false) ? '🟢' : '🔴';
                    $msg .= "$status <b>" . htmlspecialchars($bot['name'] ?? 'İsimsiz') . "</b>\n";
                    $msg .= "   @" . htmlspecialchars($bot['username'] ?? 'bilinmiyor') . "\n";
                    $msg .= "   👤 " . ($bot['users'] ?? 0) . " kullanıcı\n\n";
                }
            }
            sendMessage($chat_id, $msg);
        } else {
            sendMessage($chat_id, "❌ Bilinmeyen komut: $text\n\n/start yazın.");
        }
    }
    
    http_response_code(200);
    echo 'OK';
    exit;
}

// ==================== WEB PANEL ====================
$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'];

ob_start();

// === DASHBOARD ===
if ($action === 'dashboard' && $method === 'GET') {
    $bots = loadBots();
    // Webhook kontrol
    $webhook_info = tgRequest('getWebhookInfo');
    $webhook_status = ($webhook_info['ok'] ?? false) ? $webhook_info['result']['url'] ?? 'Ayarlanmamış' : 'Hata';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Telegram Bot Panel</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; }
            .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
            h1 { color: #58a6ff; border-bottom: 2px solid #30363d; padding-bottom: 10px; }
            .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
            .bot-card { background: #0d1117; border: 1px solid #30363d; border-radius: 10px; padding: 16px; }
            .bot-card .name { font-size: 18px; font-weight: bold; color: #58a6ff; }
            .bot-card .username { color: #8b949e; font-size: 14px; }
            .status { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 12px; margin-top: 8px; }
            .status.on { background: #238636; color: #fff; }
            .status.off { background: #da3633; color: #fff; }
            .btn { display: inline-block; padding: 6px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; margin: 4px 2px; border: none; cursor: pointer; }
            .btn-primary { background: #238636; color: #fff; }
            .btn-danger { background: #da3633; color: #fff; }
            .btn-secondary { background: #30363d; color: #fff; }
            .btn-success { background: #1f6feb; color: #fff; }
            .form-group { margin: 12px 0; }
            .form-group label { display: block; margin-bottom: 4px; color: #8b949e; }
            .form-group input, .form-group select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #30363d; background: #0d1117; color: #c9d1d9; }
            .nav { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
            .nav a { color: #58a6ff; text-decoration: none; padding: 6px 14px; border-radius: 6px; border: 1px solid #30363d; }
            .nav a:hover { background: #30363d; }
            .nav a.active { background: #238636; border-color: #238636; color: #fff; }
            table { width: 100%; border-collapse: collapse; font-size: 14px; }
            th { text-align: left; padding: 10px; background: #161b22; border-bottom: 2px solid #30363d; }
            td { padding: 10px; border-bottom: 1px solid #21262d; }
            .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin: 2px; }
            .badge-success { background: #238636; color: #fff; }
            .badge-danger { background: #da3633; color: #fff; }
            .badge-warning { background: #9e6a03; color: #fff; }
            .log-area { background: #0d1117; border: 1px solid #30363d; border-radius: 6px; padding: 12px; font-family: monospace; font-size: 13px; max-height: 300px; overflow-y: auto; white-space: pre-wrap; }
            .mt-2 { margin-top: 12px; }
            .text-center { text-align: center; }
            .webhook-status { padding: 8px 16px; border-radius: 6px; }
            .webhook-status.ok { background: #238636; color: #fff; }
            .webhook-status.err { background: #da3633; color: #fff; }
            .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999; justify-content: center; align-items: center; }
            .modal-content { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; }
            .modal.active { display: flex; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="card">
            <h1>🤖 Telegram Bot Yönetim Paneli</h1>
            <div class="nav">
                <a href="?action=dashboard" class="active">📊 Dashboard</a>
                <a href="?action=add">➕ Bot Ekle</a>
                <a href="?action=commands">📝 Komutlar</a>
                <a href="?action=logs">📋 Loglar</a>
                <a href="?action=webhook">🔗 Webhook</a>
            </div>
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <p><strong>Toplam Bot:</strong> <?= count($bots['bots'] ?? []) ?></p>
                <p><strong>Webhook:</strong> <span class="webhook-status <?= ($webhook_status !== 'Ayarlanmamış' && $webhook_status !== 'Hata') ? 'ok' : 'err' ?>"><?= htmlspecialchars($webhook_status) ?></span></p>
            </div>
        </div>

        <div class="grid">
            <?php foreach (($bots['bots'] ?? []) as $id => $bot): ?>
            <div class="bot-card">
                <div class="name"><?= htmlspecialchars($bot['name'] ?? 'İsimsiz Bot') ?></div>
                <div class="username">@<?= htmlspecialchars($bot['username'] ?? 'bilinmiyor') ?></div>
                <div>
                    <span class="status <?= ($bot['active'] ?? false) ? 'on' : 'off' ?>">
                        <?= ($bot['active'] ?? false) ? '🟢 Aktif' : '🔴 Pasif' ?>
                    </span>
                    <span class="badge badge-success"><?= $bot['users'] ?? 0 ?> kullanıcı</span>
                </div>
                <div class="mt-2">
                    <a href="?action=edit&id=<?= $id ?>" class="btn btn-secondary">✏️ Düzenle</a>
                    <a href="?action=delete&id=<?= $id ?>" class="btn btn-danger" onclick="return confirm('Emin misin?')">🗑️ Sil</a>
                    <a href="?action=test&id=<?= $id ?>" class="btn btn-primary">🧪 Test</a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($bots['bots'])): ?>
            <div class="card" style="grid-column: 1/-1; text-align: center; color: #8b949e; padding: 40px;">
                Henüz bot eklenmemiş. <a href="?action=add" style="color:#58a6ff;">Bot ekle</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// === ADD BOT ===
if ($action === 'add') {
    if ($method === 'POST') {
        $token = trim($_POST['token'] ?? '');
        $name = trim($_POST['name'] ?? '');
        
        if (!$token) {
            $error = "Token gerekli!";
        } else {
            $info = getBotInfo($token);
            if ($info && $info['ok']) {
                $bots = loadBots();
                $id = uniqid();
                $bots['bots'][$id] = [
                    'token' => $token,
                    'name' => $name ?: $info['result']['first_name'],
                    'username' => $info['result']['username'] ?? '',
                    'active' => true,
                    'created' => date('Y-m-d H:i:s'),
                    'users' => 0,
                    'commands' => []
                ];
                saveBots($bots);
                
                // Otomatik webhook ayarla
                $webhook_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?webhook=1";
                setWebhook($token, $webhook_url);
                
                header('Location: ?action=dashboard');
                exit;
            } else {
                $error = "Geçersiz token! Bot bilgisi alınamadı.";
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Bot Ekle</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; }
            .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; }
            h1 { color: #58a6ff; }
            .form-group { margin: 16px 0; }
            .form-group label { display: block; margin-bottom: 4px; color: #8b949e; }
            .form-group input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #30363d; background: #0d1117; color: #c9d1d9; }
            .btn { padding: 10px 24px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; }
            .btn-primary { background: #238636; color: #fff; }
            .btn-secondary { background: #30363d; color: #fff; }
            .error { color: #f85149; padding: 10px; background: #161b22; border: 1px solid #da3633; border-radius: 6px; margin-bottom: 16px; }
            .nav a { color: #58a6ff; text-decoration: none; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="card">
            <h1>➕ Yeni Bot Ekle</h1>
            <p><a href="?action=dashboard">← Geri</a></p>
            <?php if (isset($error)): ?>
            <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Bot Token</label>
                    <input type="text" name="token" placeholder="1234567890:ABCdefGHIjklMNOpqrsTUVwxyz" required>
                </div>
                <div class="form-group">
                    <label>Bot Adı (isteğe bağlı)</label>
                    <input type="text" name="name" placeholder="Benim Botum">
                </div>
                <button type="submit" class="btn btn-primary">✅ Ekle</button>
                <a href="?action=dashboard" class="btn btn-secondary">İptal</a>
            </form>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// === DELETE BOT ===
if ($action === 'delete') {
    $id = $_GET['id'] ?? '';
    if ($id) {
        $bots = loadBots();
        unset($bots['bots'][$id]);
        saveBots($bots);
    }
    header('Location: ?action=dashboard');
    exit;
}

// === EDIT BOT ===
if ($action === 'edit') {
    $id = $_GET['id'] ?? '';
    $bots = loadBots();
    $bot = $bots['bots'][$id] ?? null;
    
    if (!$bot) {
        header('Location: ?action=dashboard');
        exit;
    }
    
    if ($method === 'POST') {
        $bot['name'] = trim($_POST['name'] ?? $bot['name']);
        $bot['active'] = isset($_POST['active']);
        $bots['bots'][$id] = $bot;
        saveBots($bots);
        header('Location: ?action=dashboard');
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Bot Düzenle</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; }
            .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; }
            h1 { color: #58a6ff; }
            .form-group { margin: 16px 0; }
            .form-group label { display: block; margin-bottom: 4px; color: #8b949e; }
            .form-group input { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #30363d; background: #0d1117; color: #c9d1d9; }
            .form-group input[type="checkbox"] { width: auto; }
            .btn { padding: 10px 24px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; }
            .btn-primary { background: #238636; color: #fff; }
            .btn-secondary { background: #30363d; color: #fff; }
            .nav a { color: #58a6ff; text-decoration: none; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="card">
            <h1>✏️ Bot Düzenle</h1>
            <p><a href="?action=dashboard">← Geri</a></p>
            <form method="POST">
                <div class="form-group">
                    <label>Bot Adı</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($bot['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="active" <?= ($bot['active'] ?? false) ? 'checked' : '' ?>>
                        Aktif
                    </label>
                </div>
                <button type="submit" class="btn btn-primary">💾 Kaydet</button>
                <a href="?action=dashboard" class="btn btn-secondary">İptal</a>
            </form>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// === COMMANDS ===
if ($action === 'commands') {
    $bots = loadBots();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Komutlar</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
            .container { max-width: 1000px; margin: 0 auto; }
            .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 20px; }
            h1 { color: #58a6ff; }
            table { width: 100%; border-collapse: collapse; font-size: 14px; }
            th { text-align: left; padding: 10px; background: #161b22; border-bottom: 2px solid #30363d; }
            td { padding: 10px; border-bottom: 1px solid #21262d; }
            .nav { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
            .nav a { color: #58a6ff; text-decoration: none; padding: 6px 14px; border-radius: 6px; border: 1px solid #30363d; }
            .nav a:hover { background: #30363d; }
            .btn { padding: 4px 12px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; }
            .btn-danger { background: #da3633; color: #fff; }
            .btn-primary { background: #238636; color: #fff; }
            .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 999; justify-content: center; align-items: center; }
            .modal-content { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; }
            .modal.active { display: flex; }
            .form-group { margin: 12px 0; }
            .form-group label { display: block; margin-bottom: 4px; color: #8b949e; }
            .form-group input, .form-group textarea { width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #30363d; background: #0d1117; color: #c9d1d9; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="card">
            <h1>📝 Bot Komutları</h1>
            <div class="nav">
                <a href="?action=dashboard">← Dashboard</a>
                <a href="#" onclick="document.getElementById('addCmdModal').classList.add('active')">➕ Komut Ekle</a>
            </div>
            <table>
                <tr><th>Bot</th><th>Komut</th><th>Açıklama</th><th>İşlem</th></tr>
                <?php foreach (($bots['bots'] ?? []) as $id => $bot): ?>
                    <?php foreach (($bot['commands'] ?? []) as $cmd => $desc): ?>
                    <tr>
                        <td><?= htmlspecialchars($bot['name'] ?? 'İsimsiz') ?></td>
                        <td><code><?= htmlspecialchars($cmd) ?></code></td>
                        <td><?= htmlspecialchars($desc) ?></td>
                        <td>
                            <a href="?action=delcmd&bot=<?= $id ?>&cmd=<?= urlencode($cmd) ?>" class="btn btn-danger" onclick="return confirm('Emin misin?')">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    <div class="modal" id="addCmdModal">
        <div class="modal-content">
            <h2>➕ Komut Ekle</h2>
            <form method="POST" action="?action=addcmd">
                <div class="form-group">
                    <label>Bot Seç</label>
                    <select name="bot_id" style="width:100%;padding:8px;border-radius:4px;border:1px solid #30363d;background:#0d1117;color:#c9d1d9;">
                        <?php foreach (($bots['bots'] ?? []) as $id => $bot): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($bot['name'] ?? 'İsimsiz') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Komut</label>
                    <input type="text" name="command" placeholder="/start" required>
                </div>
                <div class="form-group">
                    <label>Açıklama</label>
                    <input type="text" name="description" placeholder="Botu başlat">
                </div>
                <button type="submit" class="btn btn-primary">✅ Ekle</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('addCmdModal').classList.remove('active')">İptal</button>
            </form>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// === ADD COMMAND ===
if ($action === 'addcmd' && $method === 'POST') {
    $bot_id = $_POST['bot_id'] ?? '';
    $command = trim($_POST['command'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if ($bot_id && $command) {
        $bots = loadBots();
        if (isset($bots['bots'][$bot_id])) {
            $bots['bots'][$bot_id]['commands'][$command] = $description;
            saveBots($bots);
        }
    }
    header('Location: ?action=commands');
    exit;
}

// === DELETE COMMAND ===
if ($action === 'delcmd') {
    $bot_id = $_GET['bot'] ?? '';
    $cmd = $_GET['cmd'] ?? '';
    
    if ($bot_id && $cmd) {
        $bots = loadBots();
        if (isset($bots['bots'][$bot_id])) {
            unset($bots['bots'][$bot_id]['commands'][$cmd]);
            saveBots($bots);
        }
    }
    header('Location: ?action=commands');
    exit;
}

// === WEBHOOK ===
if ($action === 'webhook') {
    $bots = loadBots();
    $result = null;
    
    // Mevcut webhook durumu
    $webhook_info = tgRequest('getWebhookInfo');
    $current_webhook = $webhook_info['ok'] ?? false ? $webhook_info['result']['url'] ?? 'Ayarlanmamış' : 'Hata';
    
    if ($method === 'POST') {
        $action_type = $_POST['action_type'] ?? '';
        $bot_id = $_POST['bot_id'] ?? '';
        $webhook_url = trim($_POST['webhook_url'] ?? '');
        
        if ($action_type === 'set') {
            if ($bot_id && isset($bots['bots'][$bot_id])) {
                $token = $bots['bots'][$bot_id]['token'];
                if (empty($webhook_url)) {
                    $webhook_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?webhook=1";
                }
                $result = setWebhook($token, $webhook_url);
            } else {
                // Ana bot için
                if (empty($webhook_url)) {
                    $webhook_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?webhook=1";
                }
                $result = setWebhook(BOT_TOKEN, $webhook_url);
            }
        } elseif ($action_type === 'delete') {
            if ($bot_id && isset($bots['bots'][$bot_id])) {
                $token = $bots['bots'][$bot_id]['token'];
                $result = deleteWebhook($token);
            } else {
                $result = deleteWebhook(BOT_TOKEN);
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Webhook</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
            .container { max-width: 700px; margin: 0 auto; }
            .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; margin-bottom: 20px; }
            h1 { color: #58a6ff; }
            .form-group { margin: 16px 0; }
            .form-group label { display: block; margin-bottom: 4px; color: #8b949e; }
            .form-group input, .form-group select { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #30363d; background: #0d1117; color: #c9d1d9; }
            .btn { padding: 10px 24px; border-radius: 6px; border: none; cursor: pointer; font-size: 14px; }
            .btn-primary { background: #238636; color: #fff; }
            .btn-danger { background: #da3633; color: #fff; }
            .btn-secondary { background: #30363d; color: #fff; }
            .nav a { color: #58a6ff; text-decoration: none; }
            .result { background: #0d1117; border: 1px solid #30363d; border-radius: 6px; padding: 12px; font-family: monospace; font-size: 13px; overflow-x: auto; }
            .webhook-status { padding: 4px 12px; border-radius: 12px; font-size: 13px; }
            .webhook-status.on { background: #238636; color: #fff; }
            .webhook-status.off { background: #da3633; color: #fff; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="card">
            <h1>🔗 Webhook Yönetimi</h1>
            <p><a href="?action=dashboard">← Geri</a></p>
            
            <div style="margin:16px 0;padding:12px;background:#0d1117;border-radius:6px;border:1px solid #30363d;">
                <strong>Mevcut Webhook:</strong> 
                <span class="webhook-status <?= ($current_webhook !== 'Ayarlanmamış' && $current_webhook !== 'Hata') ? 'on' : 'off' ?>">
                    <?= htmlspecialchars($current_webhook) ?>
                </span>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label>Bot Seç (Boş bırak = Ana bot)</label>
                    <select name="bot_id">
                        <option value="">Ana Bot (<?= BOT_TOKEN ?>)</option>
                        <?php foreach (($bots['bots'] ?? []) as $id => $bot): ?>
                        <option value="<?= $id ?>"><?= htmlspecialchars($bot['name'] ?? 'İsimsiz') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Webhook URL (Boş bırak = Otomatik)</label>
                    <input type="url" name="webhook_url" placeholder="https://ornek.com/webhook">
                </div>
                <div style="display:flex; gap:10px; flex-wrap:wrap;">
                    <button type="submit" name="action_type" value="set" class="btn btn-primary">🔗 Ayarla</button>
                    <button type="submit" name="action_type" value="delete" class="btn btn-danger" onclick="return confirm('Webhook kaldırılsın mı?')">🗑️ Kaldır</button>
                </div>
            </form>
            
            <?php if ($result): ?>
            <div style="margin-top:20px;padding:12px;background:#0d1117;border-radius:6px;border:1px solid #30363d;">
                <h3>Sonuç:</h3>
                <div class="result"><?= json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// === LOGS ===
if ($action === 'logs') {
    $logs = file_exists($log_file) ? file_get_contents($log_file) : "Log bulunamadı.";
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Loglar</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
            .container { max-width: 1000px; margin: 0 auto; }
            .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 20px; }
            h1 { color: #58a6ff; }
            .log-area { background: #0d1117; border: 1px solid #30363d; border-radius: 6px; padding: 16px; font-family: monospace; font-size: 13px; max-height: 500px; overflow-y: auto; white-space: pre-wrap; }
            .nav a { color: #58a6ff; text-decoration: none; }
            .btn { padding: 6px 14px; border-radius: 4px; border: none; cursor: pointer; font-size: 13px; }
            .btn-danger { background: #da3633; color: #fff; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="card">
            <h1>📋 Loglar</h1>
            <p><a href="?action=dashboard">← Geri</a> | <a href="?action=clearlogs" class="btn btn-danger" onclick="return confirm('Logları temizle?')">🗑️ Temizle</a></p>
            <div class="log-area"><?= htmlspecialchars($logs) ?></div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// === CLEAR LOGS ===
if ($action === 'clearlogs') {
    file_put_contents($log_file, '');
    header('Location: ?action=logs');
    exit;
}

// === TEST BOT ===
if ($action === 'test') {
    $id = $_GET['id'] ?? '';
    $bots = loadBots();
    $bot = $bots['bots'][$id] ?? null;
    
    if ($bot) {
        $info = getBotInfo($bot['token']);
        if ($info && $info['ok']) {
            $msg = "✅ Bot çalışıyor!\n";
            $msg .= "İsim: " . ($info['result']['first_name'] ?? '') . "\n";
            $msg .= "Kullanıcı adı: @" . ($info['result']['username'] ?? '') . "\n";
            $msg .= "ID: " . ($info['result']['id'] ?? '');
        } else {
            $msg = "❌ Bot çalışmıyor! Token geçersiz.";
        }
    } else {
        $msg = "❌ Bot bulunamadı!";
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Bot Test</title>
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'Segoe UI', Arial, sans-serif; background: #0d1117; color: #c9d1d9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; }
            .card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 24px; }
            h1 { color: #58a6ff; }
            .result { background: #0d1117; border: 1px solid #30363d; border-radius: 6px; padding: 16px; font-family: monospace; font-size: 14px; white-space: pre-wrap; }
            .nav a { color: #58a6ff; text-decoration: none; }
        </style>
    </head>
    <body>
    <div class="container">
        <div class="card">
            <h1>🧪 Bot Test</h1>
            <p><a href="?action=dashboard">← Geri</a></p>
            <div class="result"><?= htmlspecialchars($msg) ?></div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit;
}

// Ana webhook (GET ile test için)
if (isset($_GET['webhook'])) {
    echo "✅ Webhook endpoint çalışıyor!";
    exit;
}

// Varsayılan: Dashboard
header('Location: ?action=dashboard');
exit;