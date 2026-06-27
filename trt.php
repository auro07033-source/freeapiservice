<?php
/**
 * TRT Haber Canlı Yayın
 * Geliştirici: @zanetmez
 */

// TRT Haber M3U8 URL'si (Token gerektirmez)
$m3u8_url = "https://tv-trthaber.medya.trt.com.tr/master_1440.m3u8";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRT Haber Canlı Yayın</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { 
            background: #0a0a0a; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }
        .container { 
            width: 100%;
            max-width: 1000px;
            padding: 1rem;
        }
        .video-wrapper {
            background: #000;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.8);
            position: relative;
        }
        video {
            width: 100%;
            height: auto;
            display: block;
            background: #000;
            min-height: 400px;
        }
        .info {
            text-align: center;
            padding: 1rem;
            color: #fff;
            background: #1a1a1a;
            border-top: 1px solid #333;
        }
        .info h2 { color: #ff6b35; margin-bottom: 0.3rem; }
        .info p { color: #888; font-size: 0.8rem; }
        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #fff;
            font-size: 1.2rem;
            background: rgba(0,0,0,0.7);
            padding: 1rem 2rem;
            border-radius: 0.5rem;
            z-index: 10;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="video-wrapper">
        <div class="loading" id="loading">⏳ Yayın yükleniyor...</div>
        <video id="videoPlayer" controls autoplay playsinline></video>
        <div class="info">
            <h2>📺 TRT Haber Canlı Yayın</h2>
            <p>Geliştirici: @zanetmez | Kesintisiz HD Yayın</p>
        </div>
    </div>
</div>

<script>
    const video = document.getElementById('videoPlayer');
    const loading = document.getElementById('loading');
    
    // M3U8 URL (PHP'den alındı)
    const m3u8Url = '<?php echo $m3u8_url; ?>';
    
    // HLS.js ile M3U8 oynat
    if (Hls.isSupported()) {
        const hls = new Hls({
            enableWorker: true,
            lowLatencyMode: true,
            backbufferLength: 30
        });
        
        hls.loadSource(m3u8Url);
        hls.attachMedia(video);
        
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
            loading.style.display = 'none';
            video.play();
        });
        
        hls.on(Hls.Events.ERROR, function(event, data) {
            if (data.fatal) {
                loading.innerHTML = '❌ Yayın hatası, yeniden bağlanılıyor...';
                setTimeout(() => {
                    window.location.reload();
                }, 5000);
            }
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        // Safari için
        video.src = m3u8Url;
        video.addEventListener('loadedmetadata', function() {
            loading.style.display = 'none';
            video.play();
        });
    } else {
        loading.innerHTML = '❌ Tarayıcınız M3U8 desteklemiyor.';
    }
</script>

<!-- HLS.js kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/hls.js@0.14.17/dist/hls.min.js"></script>
</body>
</html>