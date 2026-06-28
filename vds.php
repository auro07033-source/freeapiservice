<?php
/**
 * 𝐅𝐨𝐫𝐞𝐱 𝐅𝐫𝐞𝐞 𝐕𝐃𝐒 𝐁𝐨𝐭
 * Telegram Bot Hosting - PHP ile bot yönetimi
 * Geliştirici: @zanetmez
 */

// ==================== KONFIGÜRASYON ====================
$BOT_TOKEN = "8522279355:AAGcS44nGTJTUdZjbb4QzvuuYut4f2RRPa8";
$ADMIN_ID = 8108629455;
$BOT_DIR = __DIR__ . "/bots/";
$LOG_DIR = __DIR__ . "/logs/";
$REQUIREMENTS_DIR = __DIR__ . "/requirements/";

// Klasörleri oluştur
foreach ([$BOT_DIR, $LOG_DIR, $REQUIREMENTS_DIR] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0777, true);
}

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

// ==================== YARDIMCI ====================
function cleanFilename($name) {
    return preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
}

function getLog($userId) {
    $logFile = $LOG_DIR . $userId . ".txt";
    if (!file_exists($logFile)) return "📭 Henüz log oluşturulmamış.";
    return file_get_contents($logFile);
}

function getUserName($userId) {
    $users = getUsers();
    return $users[$userId]['username'] ?? $userId;
}

// ==================== BOT KOMUTLARI ====================

// Gelen mesajı al
$update = json_decode(file_get_contents("php://input"), true);
if (!$update) {
    echo "OK";
    exit;
}

$message = $update['message'] ?? null;
$callback = $update['callback_query'] ?? null;

if ($callback) {
    $chatId = $callback['message']['chat']['id'];
    $messageId = $callback['message']['message_id'];
    $data = $callback['data'];
    $userId = $callback['from']['id'];
    $username = $callback['from']['username'] ?? $userId;
    
    // İzin onayla
    if (strpos($data, 'approve_') === 0) {
        $botId = str_replace('approve_', '', $data);
        $bots = getBots();
        
        if (isset($bots[$botId])) {
            $bots[$botId]['approved'] = true;
            $bots[$botId]['approved_by'] = $userId;
            $bots[$botId]['approved_at'] = date('Y-m-d H:i:s');
            saveBots($bots);
            
            // Botu başlat
            startBot($botId);
            
            tgApi("editMessageText", [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => "✅ **Bot Onaylandı ve Başlatıldı!**\n\n📌 Bot ID: `$botId`\n👤 Onaylayan: @" . getUserName($userId) . "\n⏰ Zaman: " . date('H:i:s'),
                'parse_mode' => 'Markdown'
            ]);
            
            tgApi("sendMessage", [
                'chat_id' => $chatId,
                'text' => "🚀 **Bot Başlatıldı!**\n\nBot artık aktif. `/durum` komutuyla kontrol edebilirsin.",
                'parse_mode' => 'Markdown'
            ]);
        }
    }
    
    // Reddet
    if (strpos($data, 'reject_') === 0) {
        $botId = str_replace('reject_', '', $data);
        $bots = getBots();
        
        if (isset($bots[$botId])) {
            unset($bots[$botId]);
            saveBots($bots);
            
            // Dosyaları temizle
            $botFile = $BOT_DIR . $botId . ".py";
            if (file_exists($botFile)) unlink($botFile);
            
            tgApi("editMessageText", [
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => "❌ **Bot Reddedildi!**\n\n📌 Bot ID: `$botId`\n👤 Reddeden: @" . getUserName($userId),
                'parse_mode' => 'Markdown'
            ]);
        }
    }
    
    exit;
}

$chatId = $message['chat']['id'] ?? null;
$text = $message['text'] ?? '';
$userId = $message['from']['id'] ?? null;
$username = $message['from']['username'] ?? $userId;

if (!$chatId || !$userId) exit;

// Kullanıcıyı kaydet
$users = getUsers();
if (!isset($users[$userId])) {
    $users[$userId] = ['username' => $username, 'joined' => date('Y-m-d H:i:s')];
    saveUsers($users);
}

// ==================== KOMUTLAR ====================

// /start
if ($text === '/start') {
    $msg = "🤖 **𝐅𝐨𝐫𝐞𝐱 𝐅𝐫𝐞𝐞 𝐕𝐃𝐒 𝐁𝐨𝐭**\n"
          . "━━━━━━━━━━━━━━━━━━\n"
          . "Merhaba! 👋 Ben senin bot hostunum!\n\n"
          . "📌 **Nasıl Kullanırım?**\n"
          . "1️⃣ `.py` dosyasını bana gönder\n"
          . "2️⃣ Admin onayını bekle\n"
          . "3️⃣ Botun başlasın! 🚀\n\n"
          . "📜 **Komutlar:**\n"
          . "/start → Bu mesajı göster\n"
          . "/botlar → Botlarını listele\n"
          . "/durum → Bot durumunu kontrol et\n"
          . "/durdur → Botu durdur\n"
          . "/log → Bot loglarını gör\n"
          . "/sil → Botu sil\n\n"
          . "👤 **Geliştirici:** @zanetmez";
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /botlar
if ($text === '/botlar') {
    $bots = getBots();
    $userBots = array_filter($bots, function($b) use ($userId) {
        return $b['user_id'] == $userId;
    });
    
    if (empty($userBots)) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "📭 **Henüz botun yok!**\n\nBana bir `.py` dosyası gönder, admin onaylasın, botun başlasın! 🚀",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $msg = "📋 **Botların**\n━━━━━━━━━━━━━━━━━━\n";
    foreach ($userBots as $id => $bot) {
        $status = $bot['approved'] ? "🟢 Aktif" : "⏳ Beklemede";
        $msg .= "\n🔹 **$id**\n"
              . "   📅 " . $bot['date'] . "\n"
              . "   📊 $status\n";
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
            'text' => "❌ **Aktif botun yok!**\n\nBir bot başlatmak için `.py` dosyası gönder.",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    $status = isBotRunning($bot['id']) ? "🟢 **Çalışıyor**" : "🔴 **Durdu**";
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "📊 **Bot Durumu**\n━━━━━━━━━━━━━━━━━━\n"
                . "📌 Bot ID: `{$bot['id']}`\n"
                . "📅 Başlangıç: {$bot['date']}\n"
                . "📊 Durum: $status\n"
                . "📂 Dosya: `{$bot['file']}`\n\n"
                . "👤 @zanetmez",
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
                'text' => "⏹️ **Bot Durduruldu!**\n\nBot ID: `$id`\nTekrar başlatmak için `/botlar` menüsünü kullan.",
                'parse_mode' => 'Markdown'
            ]);
            exit;
        }
    }
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "❌ **Durdurulacak aktif bot bulunamadı!**",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// /log
if ($text === '/log') {
    $bots = getBots();
    foreach ($bots as $id => $b) {
        if ($b['user_id'] == $userId && $b['approved']) {
            $log = getLog($userId);
            $log = substr($log, -4000);
            
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
        'text' => "📭 **Henüz log yok!**",
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
            
            tgApi("sendMessage", [
                'chat_id' => $chatId,
                'text' => "🗑️ **Bot Silindi!**\n\nBot ID: `$id`\nTüm dosyalar temizlendi.",
                'parse_mode' => 'Markdown'
            ]);
            exit;
        }
    }
    
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "❌ **Silinecek bot bulunamadı!**",
        'parse_mode' => 'Markdown'
    ]);
    exit;
}

// ==================== DOSYA YÜKLEME ====================

if ($message['document'] ?? false) {
    $doc = $message['document'];
    $fileName = $doc['file_name'] ?? '';
    
    if (!str_ends_with($fileName, '.py')) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "⚠️ **Sadece `.py` dosyaları kabul edilir!**\n\nLütfen geçerli bir Python dosyası gönder.",
            'parse_mode' => 'Markdown'
        ]);
        exit;
    }
    
    // Dosyayı indir
    $fileId = $doc['file_id'];
    $fileInfo = tgApi("getFile", ['file_id' => $fileId]);
    
    if (!$fileInfo['ok']) {
        tgApi("sendMessage", [
            'chat_id' => $chatId,
            'text' => "❌ Dosya indirilemedi! Lütfen tekrar dene."
        ]);
        exit;
    }
    
    $filePath = $fileInfo['result']['file_path'];
    $fileUrl = "https://api.telegram.org/file/bot" . $BOT_TOKEN . "/" . $filePath;
    
    // Bot ID oluştur
    $botId = uniqid('bot_');
    $localFile = $BOT_DIR . $botId . ".py";
    
    // Dosyayı kaydet
    file_put_contents($localFile, file_get_contents($fileUrl));
    
    // Botu kaydet
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
    
    // Admin'e bildir
    $adminMsg = "📥 **Yeni Bot Yüklendi!**\n\n"
              . "👤 Kullanıcı: @" . $username . "\n"
              . "📁 Dosya: `$fileName`\n"
              . "🆔 Bot ID: `$botId`\n"
              . "⏰ Zaman: " . date('H:i:s') . "\n\n"
              . "✅ Onayla: /approve_$botId\n"
              . "❌ Reddet: /reject_$botId";
    
    tgApi("sendMessage", [
        'chat_id' => $ADMIN_ID,
        'text' => $adminMsg,
        'parse_mode' => 'Markdown'
    ]);
    
    // Kullanıcıya bildir
    tgApi("sendMessage", [
        'chat_id' => $chatId,
        'text' => "✅ **Dosya Alındı!**\n\n📁 `$fileName`\n🆔 Bot ID: `$botId`\n\n⏳ Admin onayı bekleniyor...\n\nOnaylandığında botun otomatik başlayacak! 🚀",
        'parse_mode' => 'Markdown'
    ]);
    
    exit;
}

// ==================== BOT YÖNETİM FONKSİYONLARI ====================

function startBot($botId) {
    global $BOT_DIR, $LOG_DIR;
    
    $botFile = $BOT_DIR . $botId . ".py";
    $logFile = $LOG_DIR . $botId . ".txt";
    
    if (!file_exists($botFile)) return false;
    
    // Önceki process'i durdur
    stopBot($botId);
    
    // Gereksinimleri kontrol et
    $requirements = getRequirements($botFile);
    if ($requirements) {
        installRequirements($requirements);
    }
    
    // Botu başlat
    $cmd = "python3 " . escapeshellarg($botFile) . " > " . escapeshellarg($logFile) . " 2>&1 &";
    exec($cmd);
    
    return true;
}

function stopBot($botId) {
    $cmd = "pkill -f 'python3 .*/$botId\.py'";
    exec($cmd);
    return true;
}

function isBotRunning($botId) {
    $cmd = "pgrep -f 'python3 .*/$botId\.py'";
    exec($cmd, $output);
    return !empty($output);
}

function getRequirements($botFile) {
    $content = file_get_contents($botFile);
    preg_match_all('/^import\s+(\w+)/m', $content, $imports);
    preg_match_all('/^from\s+(\w+)\s+import/m', $content, $froms);
    
    $libs = array_merge($imports[1], $froms[1]);
    return array_unique($libs);
}

function installRequirements($libs) {
    foreach ($libs as $lib) {
        $cmd = "pip3 install --quiet $lib 2>&1";
        exec($cmd, $output, $returnCode);
    }
}

// ==================== WEBHOOK CEVAP ====================
echo "OK";
?>