<?php
/**
 * TRT Haber Canlı Yayın (YouTube)
 * Geliştirici: @zanetmez
 */

// YouTube canlı yayın embed URL'si
$youtube_url = "https://www.youtube.com/embed/live_stream?channel=UCYgM-zL0Rx-2c5T03ylq-gw";
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
            margin-right: 0.5rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="video-wrapper">
        <iframe 
            src="<?php echo $youtube_url; ?>" 
            allowfullscreen 
            allow="autoplay; encrypted-media"
            loading="lazy">
        </iframe>
    </div>
    <div class="info">
        <h2><span class="badge">● CANLI</span> TRT Haber Canlı Yayın</h2>
        <p>Geliştirici: @zanetmez | Kesintisiz HD Yayın</p>
    </div>
</div>
</body>
</html>