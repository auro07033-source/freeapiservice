<?php
/**
 * Telegram User Info API - Tüm Kullanıcı Bilgilerini Çeker
 * Geliştirici: @zanetmez
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$developer = "@zanetmez";
$username = isset($_GET['username']) ? trim($_GET['username']) : '';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if (empty($username) && empty($user_id)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Kullanıcı adı veya ID gerekli',
        'usage' => '?username=zanetmez veya ?user_id=123456789',
        'developer' => $developer
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Telegram API ile kullanıcı bilgilerini çek
function getTelegramUserInfo($identifier) {
    $token = "8967230892:AAF79MTbRvk1NXbQtG2lhqcpqErOo2kKsX4";
    
    // Kullanıcı ID ise direkt, username ise @ ekle
    if (is_numeric($identifier)) {
        $url = "https://api.telegram.org/bot{$token}/getChat?chat_id={$identifier}";
    } else {
        $url = "https://api.telegram.org/bot{$token}/getChat?chat_id=@" . ltrim($identifier, '@');
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

$result = getTelegramUserInfo($username ?: $user_id);

if ($result && $result['ok']) {
    $chat = $result['result'];
    
    // Detaylı yanıt hazırla
    $response = [
        'status' => 'success',
        'data' => [
            'id' => $chat['id'] ?? null,
            'username' => $chat['username'] ?? null,
            'first_name' => $chat['first_name'] ?? null,
            'last_name' => $chat['last_name'] ?? null,
            'type' => $chat['type'] ?? null,
            'bio' => $chat['bio'] ?? null,
            'description' => $chat['description'] ?? null,
            'invite_link' => $chat['invite_link'] ?? null,
            'has_private_forwards' => $chat['has_private_forwards'] ?? null,
            'has_restricted_voice_and_video_messages' => $chat['has_restricted_voice_and_video_messages'] ?? null,
            'join_to_send_messages' => $chat['join_to_send_messages'] ?? null,
            'join_by_request' => $chat['join_by_request'] ?? null,
            'message_auto_delete_time' => $chat['message_auto_delete_time'] ?? null,
            'slow_mode_delay' => $chat['slow_mode_delay'] ?? null,
            'sticker_set_name' => $chat['sticker_set_name'] ?? null,
            'can_set_sticker_set' => $chat['can_set_sticker_set'] ?? null,
            'linked_chat_id' => $chat['linked_chat_id'] ?? null,
            'location' => $chat['location'] ?? null,
            'permissions' => $chat['permissions'] ?? null,
            'photo' => $chat['photo'] ?? null,
            'active_usernames' => $chat['active_usernames'] ?? [],
            'birthdate' => $chat['birthdate'] ?? null,
            'personal_channel_id' => $chat['personal_channel_id'] ?? null,
            'business_work_hours' => $chat['business_work_hours'] ?? null,
            'business_location' => $chat['business_location'] ?? null,
            'business_intro' => $chat['business_intro'] ?? null,
            'business_opening_hours' => $chat['business_opening_hours'] ?? null,
            'emoji_status' => $chat['emoji_status'] ?? null,
            'is_premium' => $chat['is_premium'] ?? false,
            'is_bot' => $chat['is_bot'] ?? false,
            'is_support' => $chat['is_support'] ?? false,
            'is_verified' => $chat['is_verified'] ?? false,
            'is_scam' => $chat['is_scam'] ?? false,
            'is_fake' => $chat['is_fake'] ?? false,
        ],
        'developer' => '@zanetmez'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => $result['description'] ?? 'Kullanıcı bulunamadı',
        'developer' => '@zanetmez'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>