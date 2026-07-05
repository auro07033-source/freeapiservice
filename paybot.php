<?php
ob_start();
$API_KEY = '8835803800:AAEWRdYqYwy8FevVCDKikckzqePSKk1O7HY'; // Bot Token'ını buraya yaz
define('API_KEY', $API_KEY);

// Webhook ayarla
echo file_get_contents("https://api.telegram.org/bot" . API_KEY . "/setwebhook?url=https://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME']);

function bot($method, $webhook = []) {
    $webhook = http_build_query($webhook);
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method . "?" . $webhook;
    $webhook = file_get_contents($url);
    return json_decode($webhook);
}

// Rastgele payload oluştur
function rand_text() {
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
    $text = '#';
    for ($i = 0; $i < 10; $i++) {
        $text .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $text;
}

// Gelen veriyi oku
$update = json_decode(file_get_contents('php://input'));

if ($update->message) {
    $message = $update->message;
    $text = $message->text;
    $message_id = $message->message_id;
    $chat_id = $message->chat->id;
    $from_id = $message->from->id;
}

if ($update->callback_query) {
    $chat_id = $update->callback_query->message->chat->id;
    $message_id = $update->callback_query->message->message_id;
    $data = $update->callback_query->data;
}

$ex = explode("-", $data);

// /start komutu
if ($text == '/start') {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "🎉 **Hoş geldin!**\n\nBotumuzu kullandığın için teşekkür ederiz.\n\n📌 **Bağış yaparak** geliştiriciyi destekleyebilirsin.\n💰 Telegram Yıldızları ile bağış yapmak için butona tıkla!",
        "reply_to_message_id" => $message_id,
        "parse_mode" => "markdown",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "🌟 Bağış Yap", 'callback_data' => "donation"]]
            ]
        ])
    ]);
}

// Bağış menüsü
if ($data == 'donation') {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "💎 **Bağış Miktarını Seç:**",
        'message_id' => $message_id,
        "parse_mode" => "markdown",
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [['text' => "1 ⭐", 'callback_data' => "donation-1"]],
                [['text' => "5 ⭐", 'callback_data' => "donation-5"]],
                [['text' => "10 ⭐", 'callback_data' => "donation-10"]],
                [['text' => "50 ⭐", 'callback_data' => "donation-50"]],
                [['text' => "100 ⭐", 'callback_data' => "donation-100"]],
                [['text' => "500 ⭐", 'callback_data' => "donation-500"]],
                [['text' => "1000 ⭐", 'callback_data' => "donation-1000"]],
            ]
        ])
    ]);
}

// Fatura oluştur
if ($ex[0] == "donation" && isset($ex[1])) {
    $price = json_encode([['label' => "Bağış", 'amount' => $ex[1]]]);
    bot('sendInvoice', [
        'chat_id' => $chat_id,
        'title' => "💎 Bağış",
        'description' => "Geliştiriciye " . $ex[1] . " Telegram Yıldızı ile bağış yapıyorsunuz.",
        'payload' => rand_text(),
        'provider_token' => "",
        'start_parameter' => "",
        'currency' => "XTR",
        'prices' => $price,
    ]);
}

// Ödeme öncesi kontrol
if ($update->pre_checkout_query) {
    $id_query = $update->pre_checkout_query->id;
    bot('answerPreCheckoutQuery', [
        'pre_checkout_query_id' => $id_query,
        'ok' => true
    ]);
}

// Başarılı ödeme
if ($message->successful_payment) {
    bot('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "✅ **Bağışınız başarıyla alındı!**\n\n🙏 Destekleriniz için çok teşekkür ederiz.\n👨‍💻 Geliştirici en kısa sürede size dönüş yapacak.",
        'parse_mode' => "MarkDown",
    ]);
}
?>