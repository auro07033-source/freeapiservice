<?php
/**
 * 𝐅𝐨𝐫𝐞𝐱 𝐅𝐫𝐞𝐞 𝐕𝐃𝐒 𝐁𝐨𝐭 - VDS Bot Hosting
 * Geliştirici: @zanetmez
 * Admin ID: 7650776904
 */

// ==================== KONFIGÜRASYON ====================
$BOT_TOKEN = "8522279355:AAGcS44nGTJTUdZjbb4QzvuuYut4f2RRPa8";
$ADMIN_ID = 7650776904;
$BOT_DIR = __DIR__ . "/bots/";
$LOG_DIR = __DIR__ . "/logs/";

if (!is_dir($BOT_DIR)) mkdir($BOT_DIR, 0777, true);
if (!is_dir($LOG_DIR)) mkdir($LOG_DIR, 0777, true);

// ==================== TELEGRAM API ====================
function tgApi($method, $params = []) {
    global $BOT_TOKEN;
    $url = "https://api.telegram.org/bot" . $BOT_TOKEN . "/" . $method;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// ==================== VERİTABANI ====================
function getBots() {
    $file = __DIR__ . "/bots.json";
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true);
}

function saveBots($bots) {
    file_put_contents(__DIR__ . "/bots.json", json_encode($bots, JSON_PRETTY_PRINT));
}

function getUsers() {
    $file = __DIR__ . "/users.json";
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true);
}

function saveUsers($users) {
    file_put_contents(__DIR__ . "/users.json", json_encode($users, JSON_PRETTY_PRINT));
}

// ==================== BOT YÖNETİMİ ====================
function startBot($botId) {
    global $BOT_DIR, $LOG_DIR;
    
    $botFile = $BOT_DIR . $botId . ".py";
    $logFile = $LOG_DIR . $botId . ".txt";
    
    if (!file_exists($botFile)) return false;
    
    stopBot($botId);
    $cmd = "python3 " . escapeshellarg($botFile) . " > " . escapeshellarg($logFile) . " 2>&1 &";
    exec($cmd);
    return true;
}

function stopBot($botId) {
    exec("pkill -f 'python3 .*/$botId\.py'");
    return true;
}

function isBotRunning($botId) {
    exec("pgrep -f 'python3 .*/$botId\.py'", $output);
    return !empty($output);
}

function getLog($botId) {
    global $LOG_DIR;
    $logFile = $LOG_DIR . $botId . ".txt";
    if (!file_exists($logFile)) return "📭 Log bulunamadı.";
    $content = file_get_contents($logFile);
    return substr($content, -4000);
}

function cleanFilename($name) {
    return preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
}

function getUserName($userId) {
    $users = getUsers();
    return isset($users[$userId]) ? $users[$userId]['username'] : $userId;
}

// ==================== WEBHOOK ====================
$update = json_decode(file_get_contents("php://input"), true);

if (!$update) {
    http_response_code(400);
    exit("Geçersiz istek");
}

$message = $update['message'] ?? null;
$callback = $update['callback_query'] ?? null;
$chatId = null;
$userId = null;
$username = null;
$text = '';

// Callback
if ($callback) {
    $chatId = $callback['message']['chat']['id'];
    $messageId = $callback['message']['message_id'];
    $userId = $callback['from']['id'];
    $username = $callback['from']['username'] ?? $userId;
    $data = $callback['data'];
    
    if (strpos($data, 'approve_') === 0) {
        $botId = str_replace('approve_', '', $data);
        $bots = getBots();
        
        if (isset($bots[$botId])) {
            $bots[$botId]['approved'] = true;
            $bots[$botId]['approved_by'] = $userId;
            $bots[$botId]['approved_at'] = date('Y-m-d H:i:s');
            saveBots($bots);
            startBot($botId);
            
            tgApi("editMessageText", [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => "✅ **Bot Onaylandı ve Başlatıldı!**\n📌 Bot ID: `$botId`\n👤 @zanetmez",
                'parse_mode' => 'Markdown'
            ]);
            
            tgApi("sendMessage", [
                'chat_id' => $chatId,
                'text' => "🚀 Bot başladı! `/durum` ile kontrol et.",
                'parse_mode' => 'Markdown'
            ]);
        }
    }
    
    if (strpos($data, 'reject_') === 0) {
        $botId = str_replace('reject_', '', $data);
        $bots = getBots();
        
        if (isset($bots[$botId])) {
            unset($bots[$botId]);
            saveBots($bots);
            $botFile = $BOT_DIR . $botId . ".py";
            if (file_exists($botFile)) unlink($botFile);
            
            tgApi("editMessageText", [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => "❌ **Bot Reddedildi!**\n📌 Bot ID: `$botId`",
                'parse_mode' => 'Markdown'
            ]);
        }
    }
    
    exit;
}

// Mesaj
if ($message) {
    $chatId = $message['chat']['id'];
    $userId = $message['from']['id'];
    $username = $message['from']['username'] ?? $userId;
    $text = $message['text'] ?? '';
}

if (!$chatId) exit;

// Kullanıcı kaydet
$users = getUsers();
if (!isset($users[$userId])) {
    $users[$userId] = ['username' => $username, 'joined' => date('Y-m-d H:i:s')];
    saveUsers($users);
}

// ==================== ADMIN KONTROLÜ ====================
$isAdmin = ($userId == $ADMIN_ID);

// ==================== KOMUTLAR ====================

// /start
if ($text === '/start') {
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "🤖 **𝐅𝐫𝐞𝐞 𝐕𝐃𝐒 𝐁𝐨𝐭**\n━━━━━━━━━━━━━━━━━━\nMerhaba! 👋\n\n📌 `.py` dosyası gönder, admin onaylasın, botun başlasın!\n\n📜 **Komutlar:**\n/start → Bu mesaj\n/botlar → Botlarını listele\n/durum → Bot durumu\n/durdur → Botu durdur\n/log → Logları gör\n/sil → Botu sil\n\n👤 @zanetmez",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /admin - Admin paneli
if ($text === '/admin' && $isAdmin) {
    $bots = getBots();
    $bekleyen = array_filter($bots, fn($b) => !$b['approved']);
    $aktif = array_filter($bots, fn($b) => $b['approved']);
    
    $msg = "👑 **Admin Paneli**\n━━━━━━━━━━━━━━━━━━\n\n"
          . "📥 **Bekleyen Botlar:** " . count($bekleyen) . "\n"
          . "🟢 **Aktif Botlar:** " . count($aktif) . "\n"
          . "📁 **Toplam Bot:** " . count($bots) . "\n\n"
          . "📌 **Komutlar:**\n"
          . "/admin_bekleyen → Bekleyen botları gör\n"
          . "/admin_aktif → Aktif botları gör\n"
          . "/admin_tum → Tüm botları gör\n"
          . "/admin_durdur <id> → Botu durdur\n"
          . "/admin_baslat <id> → Botu başlat\n"
          . "/admin_sil <id> → Botu sil\n\n"
          . "👤 @zanetmez";
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /admin_bekleyen
if ($text === '/admin_bekleyen' && $isAdmin) {
    $bots = getBots();
    $bekleyen = array_filter($bots, fn($b) => !$b['approved']);
    
    if (empty($bekleyen)) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "📭 **Bekleyen bot yok!**",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $msg = "📥 **Bekleyen Botlar**\n━━━━━━━━━━━━━━━━━━\n";
    foreach ($bekleyen as $id => $b) {
        $msg .= "\n🔹 `$id`\n   👤 @" . ($b['username'] ?? 'bilinmiyor') . "\n   📅 {$b['date']}\n   📁 `{$b['file']}`\n";
    }
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /admin_aktif
if ($text === '/admin_aktif' && $isAdmin) {
    $bots = getBots();
    $aktif = array_filter($bots, fn($b) => $b['approved']);
    
    if (empty($aktif)) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "📭 **Aktif bot yok!**",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $msg = "🟢 **Aktif Botlar**\n━━━━━━━━━━━━━━━━━━\n";
    foreach ($aktif as $id => $b) {
        $durum = isBotRunning($id) ? "🟢 Çalışıyor" : "🔴 Durdu";
        $msg .= "\n🔹 `$id`\n   👤 @" . ($b['username'] ?? 'bilinmiyor') . "\n   📊 $durum\n   📁 `{$b['file']}`\n";
    }
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /admin_tum
if ($text === '/admin_tum' && $isAdmin) {
    $bots = getBots();
    
    if (empty($bots)) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "📭 **Hiç bot yok!**",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $msg = "📁 **Tüm Botlar**\n━━━━━━━━━━━━━━━━━━\n";
    foreach ($bots as $id => $b) {
        $status = $b['approved'] ? "✅ Onaylı" : "⏳ Beklemede";
        $durum = $b['approved'] ? (isBotRunning($id) ? "🟢" : "🔴") : "⚪";
        $msg .= "\n$durum `$id`\n   👤 @" . ($b['username'] ?? 'bilinmiyor') . "\n   📊 $status\n";
    }
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /admin_durdur <id>
if (strpos($text, '/admin_durdur ') === 0 && $isAdmin) {
    $botId = trim(str_replace('/admin_durdur ', '', $text));
    stopBot($botId);
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "⏹️ **Bot Durduruldu!**\n📌 Bot ID: `$botId`",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /admin_baslat <id>
if (strpos($text, '/admin_baslat ') === 0 && $isAdmin) {
    $botId = trim(str_replace('/admin_baslat ', '', $text));
    startBot($botId);
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "🚀 **Bot Başlatıldı!**\n📌 Bot ID: `$botId`",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /admin_sil <id>
if (strpos($text, '/admin_sil ') === 0 && $isAdmin) {
    $botId = trim(str_replace('/admin_sil ', '', $text));
    $bots = getBots();
    
    if (isset($bots[$botId])) {
        stopBot($botId);
        unset($bots[$botId]);
        saveBots($bots);
        $botFile = $BOT_DIR . $botId . ".py";
        if (file_exists($botFile)) unlink($botFile);
        $logFile = $LOG_DIR . $botId . ".txt";
        if (file_exists($logFile)) unlink($logFile);
        
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "🗑️ **Bot Silindi!**\n📌 Bot ID: `$botId`",
            'parse_mode' => 'Markdown'
        ]);
    } else {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "❌ **Bot bulunamadı!**",
            'parse_mode' => 'Markdown'
        ]);
    }
    exit;
}

// /botlar
if ($text === '/botlar') {
    $bots = getBots();
    $userBots = array_filter($bots, fn($b) => $b['user_id'] == $userId);
    
    if (empty($userBots)) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "📭 **Henüz botun yok!**\n\nBir `.py` dosyası gönder, admin onaylasın.",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $msg = "📋 **Botların**\n━━━━━━━━━━━━━━━━━━\n";
    foreach ($userBots as $id => $b) {
        $status = $b['approved'] ? "🟢 Aktif" : "⏳ Beklemede";
        $msg .= "\n🔹 `$id`\n   📅 {$b['date']}\n   📊 $status\n";
    }
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /durum
if ($text === '/durum') {
    $bots = getBots();
    $bot = null;
    foreach ($bots as $id => $b) {
        if ($b['user_id'] == $userId && $b['approved']) {
            $bot = $b;
            break;
        }
    }
    
    if (!$bot) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "❌ **Aktif botun yok!**",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $status = isBotRunning($bot['id']) ? "🟢 Çalışıyor" : "🔴 Durdu";
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "📊 **Bot Durumu**\n━━━━━━━━━━━━━━━━━━\n📌 Bot ID: `{$bot['id']}`\n📊 Durum: $status\n📂 Dosya: `{$bot['file']}`\n\n👤 @zanetmez",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /durdur
if ($text === '/durdur') {
    $bots = getBots();
    foreach ($bots as $id => $b) {
        if ($b['user_id'] == $userId && $b['approved']) {
            stopBot($id);
            tgApi("sendMessage", [
                'chat_id' => $chatId,
                'text' => "⏹️ **Bot Durduruldu!**\n📌 Bot ID: `$id`",
                'parse_mode' => 'Markdown'
            ]);
            exit;
        }
    }
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "❌ **Durdurulacak bot yok!**",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /log
if ($text === '/log') {
    $bots = getBots();
    foreach ($bots as $id => $b) {
        if ($b['user_id'] == $userId && $b['approved']) {
            $log = getLog($id);
            tgApi("sendMessage", [
                'chat_id' => $chatId,
                'text' => "📄 **Bot Logları**\n━━━━━━━━━━━━━━━━━━\n```\n$log\n```\n\n👤 @zanetmez",
                'parse_mode' => 'Markdown'
            ]);
            exit;
        }
    }
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "📭 **Log bulunamadı!**",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /sil
if ($text === '/sil') {
    $bots = getBots();
    foreach ($bots as $id => $b) {
        if ($b['user_id'] == $userId) {
            stopBot($id);
            unset($bots[$id]);
            saveBots($bots);
            $botFile = $BOT_DIR . $id . ".py";
            if (file_exists($botFile)) unlink($botFile);
            $logFile = $LOG_DIR . $id . ".txt";
            if (file_exists($logFile)) unlink($logFile);
            
            tgApi("sendMessage", [
                'chat_id' => $chatId,
                'text' => "🗑️ **Bot Silindi!**\n📌 Bot ID: `$id`",
                'parse_mode' => 'Markdown'
            ]);
            exit;
        }
    }
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "❌ **Silinecek bot yok!**",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// Admin onay (mesaj ile)
if ($isAdmin && strpos($text, '/approve') === 0) {
    $botId = str_replace('/approve', '', $text);
    $bots = getBots();
    
    if (isset($bots[$botId])) {
        $bots[$botId]['approved'] = true;
        $bots[$botId]['approved_by'] = $userId;
        $bots[$botId]['approved_at'] = date('Y-m-d H:i:s');
        saveBots($bots);
        startBot($botId);
        
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "✅ **Bot Onaylandı!**\n📌 Bot ID: `$botId`\n🚀 Bot başlatıldı!",
            'parse_mode' => 'Markdown'
        ]);
    }
    exit;
}

if ($isAdmin && strpos($text, '/reject') === 0) {
    $botId = str_replace('/reject', '', $text);
    $bots = getBots();
    
    if (isset($bots[$botId])) {
        unset($bots[$botId]);
        saveBots($bots);
        $botFile = $BOT_DIR . $botId . ".py";
        if (file_exists($botFile)) unlink($botFile);
        $logFile = $LOG_DIR . $botId . ".txt";
        if (file_exists($logFile)) unlink($logFile);
        
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "❌ **Bot Reddedildi!**\n📌 Bot ID: `$botId`",
            'parse_mode' => 'Markdown'
        ]);
    }
    exit;
}

// Dosya yükleme
if (isset($message['document'])) {
    $doc = $message['document'];
    $fileName = $doc['file_name'] ?? '';
    
    if (!str_ends_with($fileName, '.py')) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "⚠️ **Sadece `.py` dosyaları kabul edilir!**",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $fileInfo = tgApi("getFile", ['file_id' => $doc['file_id']]);
    if (!$fileInfo['ok']) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "❌ **Dosya indirilemedi!**",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $fileUrl = "https://api.telegram.org/file/bot" . $BOT_TOKEN . "/" . $fileInfo['result']['file_path'];
    $botId = uniqid('bot_');
    $localFile = $BOT_DIR . $botId . ".py";
    
    file_put_contents($localFile, file_get_contents($fileUrl));
    
    $bots = getBots();
    $bots[$botId] = [
        'id' => $botId,
        'user_id' => $userId,
        'username' => $username,
        'file' => $fileName,
        'date' => date('Y-m-d H:i:s'),
        'approved' => false
    ];
    saveBots($bots);
    
    // Admin bildir
    tgApi("sendMessage", [
        'chat_id' => $ADMIN_ID,
        'text' => "📥 **Yeni Bot Yüklendi!**\n👤 @$username\n📁 `$fileName`\n🆔 `$botId`\n\n/approve$botId\n/reject$botId",
        'parse_mode' => 'Markdown'
    ]);
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "✅ **Dosya Alındı!**\n📁 `$fileName`\n🆔 `$botId`\n\n⏳ Admin onayı bekleniyor...\n\n👤 @zanetmez",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

echo "OK";
?>