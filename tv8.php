<?php
/*
TV8 Canlı Yayın API
Kullanım: https://yourdomain.com/tv8.php
Çıktı: M3U8 playlist veya embed player
*/

// M3U8 URL (güncel sid ve app parametreleri ile)
$stream_url = "https://tv8.daioncdn.net/tv8/tv8_1080p.m3u8?&sid=" . generate_sid() . "&app=" . generate_app() . "&ce=3";

// Parametreleri dinamik üret (sunucu her yenilediğinde yeni değerler alır)
function generate_sid() {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    return substr(str_shuffle($chars), 0, 12);
}

function generate_app() {
    return sprintf(
        '%s-%s-%s-%s-%s',
        substr(md5(uniqid()), 0, 8),
        substr(md5(uniqid()), 0, 4),
        substr(md5(uniqid()), 0, 4),
        substr(md5(uniqid()), 0, 4),
        substr(md5(uniqid()), 0, 12)
    );
}

// Get parametreleri
$action = isset($_GET['action']) ? $_GET['action'] : 'play';
$format = isset($_GET['format']) ? $_GET['format'] : 'm3u8';

if ($action == 'play') {
    // HTML Player göster
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>TV8 Canlı Yayın</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { background: #0a0a0a; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: Arial, sans-serif; }
            .container { width: 100%; max-width: 1200px; padding: 20px; }
            .player-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; background: #000; border-radius: 12px; box-shadow: 0 0 40px rgba(255,0,0,0.2); }
            .player-wrapper video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: #000; }
            .info { color: #888; text-align: center; margin-top: 15px; font-size: 14px; }
            .info a { color: #ff4444; text-decoration: none; }
            .info a:hover { text-decoration: underline; }
            .status { color: #00ff44; font-size: 12px; margin-top: 10px; text-align: center; }
            .status .dot { display: inline-block; width: 10px; height: 10px; background: #00ff44; border-radius: 50%; animation: blink 1s infinite; }
            @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
            @media (max-width: 768px) { .container { padding: 10px; } }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="player-wrapper">
                <video id="videoPlayer" controls autoplay playsinline>
                    <source src="<?php echo htmlspecialchars($stream_url); ?>" type="application/vnd.apple.mpegurl">
                    Tarayıcınız bu videoyu desteklemiyor.
                </video>
            </div>
            <div class="status">
                <span class="dot"></span> CANLI YAYIN
            </div>
            <div class="info">
                <a href="?action=m3u8">M3U8 Linki</a> | 
                <a href="?action=embed">Embed Kodu</a> |
                <a href="https://tv8.com.tr" target="_blank">TV8</a>
            </div>
        </div>
        <script>
            // HLS.js ile fallback
            if (!document.createElement('video').canPlayType('application/vnd.apple.mpegurl')) {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/hls.js@latest';
                script.onload = function() {
                    var video = document.getElementById('videoPlayer');
                    if (Hls.isSupported()) {
                        var hls = new Hls();
                        hls.loadSource('<?php echo htmlspecialchars($stream_url); ?>');
                        hls.attachMedia(video);
                        hls.on(Hls.Events.MANIFEST_PARSED, function() {
                            video.play();
                        });
                    }
                };
                document.head.appendChild(script);
            }
        </script>
    </body>
    </html>
    <?php
} elseif ($action == 'm3u8') {
    // Sadece M3U8 linkini göster
    header('Content-Type: text/plain');
    echo "TV8 Canlı Yayın M3U8 URL:\n";
    echo $stream_url . "\n\n";
    echo "VLC veya ffmpeg ile kullan:\n";
    echo "ffmpeg -i \"$stream_url\" -c copy tv8.mp4\n";
} elseif ($action == 'embed') {
    // Embed kodu
    header('Content-Type: text/plain');
    $embed_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $embed_url = str_replace('action=embed', 'action=play', $embed_url);
    echo '<iframe src="' . htmlspecialchars($embed_url) . '" width="100%" height="500" frameborder="0" allowfullscreen></iframe>';
} else {
    // Varsayılan: oynatıcıyı göster
    header('Location: ?action=play');
}
?>