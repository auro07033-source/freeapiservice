<?php
/**
 * Forex Userbot - Hesabından Otomatik Yanıt
 * Geliştirici: @zanetmez
 */

// ==================== KONFIG ====================
$API_ID = 37530959;
$API_HASH = "ead1e5bd23f9361738579b6acde959d6";

// ==================== FONKSİYONLAR ====================

/**
 * Python ile kullanıcı hesabından mesaj gönder
 */
function sendAsUser($chat_id, $message) {
    $cmd = "python3 /path/to/send.py " . escapeshellarg($chat_id) . " " . escapeshellarg($message) . " 2>&1";
    $output = shell_exec($cmd);
    return json_decode($output, true);
}

function getReply($name = '') {
    $replies = [
        "Selam! 👋 Şu an aktif değilim ama en kısa sürede dönüyorum! 😊",
        "Hey! 👀 Şu anda offline'ım, mesajını ileteceğim! 💬",
        "Merhaba! 🤖 Forex AI sahibi şu anda müsait değil. :/",
        "Selam! 🚀 Birazdan döneceğim, sabırlı ol! 💪",
        "Hey! ❤️ Seni çok seviyorum, ama şu an uyuyor olabilirim! 😴"
    ];
    $msg = $replies[array_rand($replies)];
    if ($name) $msg = str_replace("!", " $name!", $msg);
    return $msg . "\n\n📌 @zanetmez";
}

// ==================== WEBHOOK ====================

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) exit;

$message = $update['message'] ?? null;
$chat_id = $message['chat']['id'] ?? null;
$text = $message['text'] ?? '';
$username = $message['from']['username'] ?? '';

if ($chat_id && !str_starts_with($text, '/')) {
    sleep(2);
    $reply = getReply($username ? "@$username" : '');
    sendAsUser($chat_id, $reply);
}

if ($text === '/start') {
    sendAsUser($chat_id, "🤖 **Forex AI Bot**\n━━━━━━━━━━━━━━━━━━\nMesaj at, anında samimi cevap al!\n\n📌 @zanetmez");
}
?>