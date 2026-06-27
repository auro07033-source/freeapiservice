<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRT Haber Canlı Yayın</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background: #0a0a0a; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { width: 100%; max-width: 1200px; padding: 1rem; }
        .video-wrapper { position: relative; height: 0; padding-bottom: 56.25%; background: #000; border-radius: 1rem; overflow: hidden; }
        .video-wrapper iframe { 
            position: absolute; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            border: none;
        }
        .info { text-align: center; padding: 1rem; color: #fff; background: #1a1a1a; }
        .info h2 { color: #ff6b35; }
        .info p { color: #888; font-size: 0.8rem; }
        .tabs { display: flex; gap: 1rem; justify-content: center; padding: 1rem; background: #1a1a1a; flex-wrap: wrap; }
        .tabs button { 
            padding: 0.5rem 1.5rem; 
            border: none; 
            border-radius: 0.5rem; 
            background: #333; 
            color: #fff; 
            cursor: pointer; 
            font-weight: bold;
            transition: 0.3s;
        }
        .tabs button:hover, .tabs button.active { background: #ff6b35; }
    </style>
</head>
<body>
<div class="container">
    <div class="tabs">
        <button class="active" onclick="changeSource('trtizle')">📺 TRT İzle</button>
        <button onclick="changeSource('youtube')">▶️ YouTube</button>
        <button onclick="changeSource('m3u8')">📡 M3U8</button>
    </div>
    <div class="video-wrapper">
        <iframe id="player" src="https://www.trtizle.com/canli/trt-haber" allowfullscreen></iframe>
    </div>
    <div class="info">
        <h2>📺 TRT Haber Canlı Yayın</h2>
        <p>Geliştirici: @zanetmez | Kesintisiz HD Yayın</p>
    </div>
</div>

<script>
    function changeSource(type) {
        const player = document.getElementById('player');
        const buttons = document.querySelectorAll('.tabs button');
        buttons.forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        
        const sources = {
            'trtizle': 'https://www.trtizle.com/canli/trt-haber',
            'youtube': 'https://www.youtube.com/embed/live_stream?channel=UCYgM-zL0Rx-2c5T03ylq-gw',
            'm3u8': 'https://tv-trthaber.medya.trt.com.tr/master_1440.m3u8'
        };
        
        player.src = sources[type] || sources['trtizle'];
    }
</script>
</body>
</html>