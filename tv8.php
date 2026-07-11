<?php
/*
TV8 Canlı Yayın API v2
- HLS.js ile tüm tarayıcılar
- CORS düzeltmesi
- Fallback player
*/

// M3U8 URL (dinamik)
$stream_url = "https://tv8.daioncdn.net/tv8/tv8_1080p.m3u8?&sid=" . generate_sid() . "&app=" . generate_app() . "&ce=3";

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

$action = isset($_GET['action']) ? $_GET['action'] : 'play';

if ($action == 'play') {
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
            .info a { color: #ff4444; text-decoration: none; margin: 0 10px; }
            .info a:hover { text-decoration: underline; }
            .status { color: #00ff44; font-size: 12px; margin-top: 10px; text-align: center; }
            .dot { display: inline-block; width: 10px; height: 10px; background: #00ff44; border-radius: 50%; animation: blink 1s infinite; }
            @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
            .error-msg { color: #ff4444; text-align: center; margin-top: 20px; font-size: 14px; display: none; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="player-wrapper">
                <video id="videoPlayer" controls autoplay playsinline muted></video>
            </div>
            <div class="status">
                <span class="dot"></span> CANLI YAYIN
            </div>
            <div class="error-msg" id="errorMsg">
                ⚠️ Video yüklenemiyor. <a href="#" onclick="location.reload()">Yenile</a> veya <a href="?action=m3u8">M3U8 linkini</a> VLC ile aç.
            </div>
            <div class="info">
                <a href="?action=m3u8">M3U8 Linki</a> |
                <a href="?action=embed">Embed Kodu</a> |
                <a href="https://tv8.com.tr" target="_blank">TV8</a>
            </div>
        </div>

        <!-- HLS.js -->
        <script src="https://cdn.jsdelivr.net/npm/hls.js@0.14.17"></script>
        <script>
            (function() {
                var video = document.getElementById('videoPlayer');
                var errorMsg = document.getElementById('errorMsg');
                var streamUrl = '<?php echo addslashes($stream_url); ?>';

                function loadVideo(url) {
                    // Native HLS (Safari)
                    if (video.canPlayType('application/vnd.apple.mpegurl')) {
                        video.src = url;
                        video.play().catch(function(e) {
                            console.log('Native play error:', e);
                            errorMsg.style.display = 'block';
                        });
                        return;
                    }

                    // HLS.js
                    if (Hls.isSupported()) {
                        var hls = new Hls({
                            enableWorker: true,
                            lowLatencyMode: true,
                            backbufferLength: 30
                        });
                        hls.loadSource(url);
                        hls.attachMedia(video);
                        hls.on(Hls.Events.MANIFEST_PARSED, function() {
                            video.play().catch(function(e) {
                                console.log('HLS play error:', e);
                            });
                        });
                        hls.on(Hls.Events.ERROR, function(event, data) {
                            if (data.fatal) {
                                errorMsg.style.display = 'block';
                            }
                        });
                        window.hls = hls;
                    } else {
                        // Fallback
                        errorMsg.style.display = 'block';
                    }
                }

                // Yükle
                loadVideo(streamUrl);

                // Yenileme butonu
                document.querySelector('.error-msg a[href="#"]')?.addEventListener('click', function(e) {
                    e.preventDefault();
                    location.reload();
                });
            })();
        </script>
    </body>
    </html>
    <?php
} elseif ($action == 'm3u8') {
    header('Content-Type: text/plain');
    echo "TV8 Canlı Yayın M3U8 URL:\n" . $stream_url . "\n\n";
    echo "VLC: Medya -> Ağ Akışı Aç -> URL yapıştır\n";
    echo "FFmpeg: ffmpeg -i \"" . $stream_url . "\" -c copy tv8.mp4\n";
} elseif ($action == 'embed') {
    $embed_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $embed_url = str_replace('action=embed', 'action=play', $embed_url);
    header('Content-Type: text/plain');
    echo '<iframe src="' . htmlspecialchars($embed_url) . '" width="100%" height="500" frameborder="0" allowfullscreen></iframe>';
} else {
    header('Location: ?action=play');
}
?>