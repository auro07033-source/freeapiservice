<?php
/**
 * TRT Haber Canlı Yayın (JavaScript ile)
 * Geliştirici: @zanetmez
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRT Haber Canlı Yayın</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: #0a0a0a; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; }
        .container { width: 100%; max-width: 1200px; padding: 1rem; }
        .video-wrapper { background: #000; border-radius: 1rem; overflow: hidden; position: relative; height: 0; padding-bottom: 56.25%; }
        .video-wrapper iframe { 
            position: absolute; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            border: none;
            background: #000;
        }
        .info { text-align: center; padding: 1rem; color: #fff; background: #1a1a1a; border-top: 1px solid #333; }
        .info h2 { color: #ff6b35; }
        .info p { color: #888; font-size: 0.8rem; }
        .badge {
            display: inline-block;
            background: #ff0000;
            color: #fff;
            padding: 0.2rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.7rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .fallback {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            text-align: center;
            z-index: 10;
        }
        .fallback a {
            color: #ff6b35;
            text-decoration: none;
            font-size: 1.2rem;
            display: block;
            margin-top: 0.5rem;
        }
        .fallback a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <div class="video-wrapper">
        <div id="player"></div>
        <div class="fallback" id="fallback">
            <p>⚠️ Yüklenemiyor mu?</p>
            <a href="https://www.youtube.com/user/trthaber/live" target="_blank">📺 YouTube'da Aç</a>
            <a href="https://www.trtizle.com/canli/trt-haber" target="_blank">📡 TRT İzle'de Aç</a>
        </div>
        <div class="info">
            <h2><span class="badge">● CANLI</span> TRT Haber Canlı Yayın</h2>
            <p>Geliştirici: @zanetmez | Kesintisiz HD Yayın</p>
        </div>
    </div>
</div>

<script>
    // YouTube IFrame API ile oynat
    let player;
    const fallback = document.getElementById('fallback');

    function onYouTubeIframeAPIReady() {
        player = new YT.Player('player', {
            height: '100%',
            width: '100%',
            videoId: 'live_stream?channel=UCYgM-zL0Rx-2c5T03ylq-gw',
            playerVars: {
                'autoplay': 1,
                'rel': 0,
                'controls': 1,
                'modestbranding': 1,
                'origin': window.location.origin
            },
            events: {
                'onError': function(event) {
                    fallback.style.display = 'block';
                },
                'onReady': function(event) {
                    fallback.style.display = 'none';
                }
            }
        });
    }

    // YouTube API script'ini yükle
    const tag = document.createElement('script');
    tag.src = "https://www.youtube.com/iframe_api";
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

    // 10 saniye sonra hala yüklenmediyse fallback göster
    setTimeout(() => {
        if (!player || !player.getVideoLoadedFraction()) {
            fallback.style.display = 'block';
        }
    }, 10000);
</script>
</body>
</html>