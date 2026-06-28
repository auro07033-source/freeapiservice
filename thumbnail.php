<?php
/**
 * YouTube Thumbnail Oluşturucu (Pollinations.ai)
 * Geliştirici: @zanetmez
 */

// ==================== KONFIGÜRASYON ====================
$developer = "@zanetmez";
$api_url = "https://image.pollinations.ai/prompt/";

// Kullanıcıdan gelen parametreler
$prompt = isset($_POST['prompt']) ? trim($_POST['prompt']) : '';
$width = isset($_POST['width']) ? intval($_POST['width']) : 1280;
$height = isset($_POST['height']) ? intval($_POST['height']) : 720;
$nologo = isset($_POST['nologo']) ? $_POST['nologo'] : 'true';
$model = isset($_POST['model']) ? trim($_POST['model']) : 'openai';

// ==================== GÖRSEL OLUŞTUR ====================
$thumbnail_url = '';
$error = '';

if (!empty($prompt)) {
    // Prompt'u düzenle
    $full_prompt = "YouTube thumbnail for video: $prompt, high quality, eye catching, colorful, 8k";
    
    // API URL'sini oluştur
    $thumbnail_url = $api_url . urlencode($full_prompt) . "?width={$width}&height={$height}&nologo={$nologo}";
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Thumbnail Oluşturucu</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            padding: 2rem 1rem;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #fff;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #ff6b35, #ff2d55);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .sub {
            text-align: center;
            color: rgba(255,255,255,0.5);
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        .card {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 1.5rem;
        }
        .card h2 {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            color: #ff6b35;
        }
        label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: 500;
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
        }
        input, select, textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 0.8rem;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(0,0,0,0.3);
            color: #fff;
            font-size: 1rem;
            transition: 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 20px rgba(255,107,53,0.2);
        }
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 0.8rem;
            background: linear-gradient(135deg, #ff6b35, #ff2d55);
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 0.5rem;
        }
        .btn:hover {
            transform: scale(1.02);
            box-shadow: 0 0 30px rgba(255,107,53,0.4);
        }
        .btn:active {
            transform: scale(0.98);
        }
        .result {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(0,0,0,0.3);
            border-radius: 1rem;
            text-align: center;
            display: <?php echo !empty($thumbnail_url) ? 'block' : 'none'; ?>;
        }
        .result img {
            max-width: 100%;
            border-radius: 0.8rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            max-height: 500px;
            object-fit: contain;
        }
        .result .info {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .result .info a {
            padding: 0.6rem 1.5rem;
            background: rgba(255,255,255,0.1);
            border-radius: 0.6rem;
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.3s;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .result .info a:hover {
            background: rgba(255,255,255,0.2);
        }
        .badge {
            display: inline-block;
            padding: 0.2rem 0.8rem;
            border-radius: 0.5rem;
            font-size: 0.7rem;
            background: #ff6b35;
            color: #fff;
            margin-bottom: 0.5rem;
        }
        .footer {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255,255,255,0.3);
            font-size: 0.8rem;
        }
        .presets {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .preset-btn {
            padding: 0.4rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid rgba(255,255,255,0.15);
            background: rgba(255,255,255,0.05);
            color: #fff;
            cursor: pointer;
            font-size: 0.8rem;
            transition: 0.3s;
        }
        .preset-btn:hover {
            background: rgba(255,107,53,0.2);
            border-color: #ff6b35;
        }
        @media (max-width: 600px) {
            .row { grid-template-columns: 1fr; }
            h1 { font-size: 1.6rem; }
            .card { padding: 1.2rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🎬 YouTube Thumbnail Oluşturucu</h1>
    <p class="sub">Pollinations.ai ile otomatik YouTube küçük resmi oluşturun</p>
    
    <div class="card">
        <h2>📝 Thumbnail Ayarları</h2>
        <form method="POST" action="">
            <label>📌 Video Başlığı / Açıklaması</label>
            <textarea name="prompt" placeholder="Örnek: Python ile Yapay Zeka, Eğlenceli ve Renkli"><?php echo htmlspecialchars($prompt); ?></textarea>
            
            <div class="presets">
                <span style="color:rgba(255,255,255,0.4); font-size:0.8rem;">Hazır promptlar:</span>
                <button type="button" class="preset-btn" onclick="document.querySelector('textarea').value='Kedi, sevimli, renkli, YouTube kapağı'">🐱 Kedi</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('textarea').value='Teknoloji, yapay zeka, mavi, neon, YouTube kapağı'">🤖 AI</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('textarea').value='Oyun, eğlence, patlama, aksiyon, YouTube kapağı'">🎮 Oyun</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('textarea').value='Seyahat, doğa, güzel manzara, YouTube kapağı'">🌍 Seyahat</button>
            </div>
            
            <div class="row" style="margin-top:1rem;">
                <div>
                    <label>📐 Genişlik</label>
                    <input type="number" name="width" value="<?php echo $width; ?>" min="256" max="2048">
                </div>
                <div>
                    <label>📐 Yükseklik</label>
                    <input type="number" name="height" value="<?php echo $height; ?>" min="256" max="2048">
                </div>
            </div>
            
            <div class="row">
                <div>
                    <label>🎨 Model</label>
                    <select name="model">
                        <option value="openai" <?php echo $model == 'openai' ? 'selected' : ''; ?>>OpenAI</option>
                        <option value="mistral" <?php echo $model == 'mistral' ? 'selected' : ''; ?>>Mistral</option>
                        <option value="llama" <?php echo $model == 'llama' ? 'selected' : ''; ?>>Llama</option>
                        <option value="gemini" <?php echo $model == 'gemini' ? 'selected' : ''; ?>>Gemini</option>
                    </select>
                </div>
                <div>
                    <label>🖼️ Logo</label>
                    <select name="nologo">
                        <option value="true" <?php echo $nologo == 'true' ? 'selected' : ''; ?>>Logo yok</option>
                        <option value="false" <?php echo $nologo == 'false' ? 'selected' : ''; ?>>Logo var</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn">🎨 Thumbnail Oluştur</button>
        </form>
    </div>
    
    <?php if (!empty($thumbnail_url)): ?>
    <div class="result" style="display:block;">
        <div class="badge">✅ OLUŞTURULDU</div>
        <img src="<?php echo htmlspecialchars($thumbnail_url); ?>" alt="Thumbnail" loading="lazy">
        <div class="info">
            <a href="<?php echo htmlspecialchars($thumbnail_url); ?>" target="_blank">📷 Tam Boyut</a>
            <a href="#" onclick="downloadImage('<?php echo htmlspecialchars($thumbnail_url); ?>')">💾 İndir</a>
            <a href="?">🔄 Yeni Oluştur</a>
        </div>
        <p style="color:rgba(255,255,255,0.4); font-size:0.8rem; margin-top:0.5rem;">
            📌 Prompt: <?php echo htmlspecialchars($prompt); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        Geliştirici: <?php echo $developer; ?> | Pollinations.ai ile güçlendirilmiştir
    </div>
</div>

<script>
function downloadImage(url) {
    fetch(url)
        .then(res => res.blob())
        .then(blob => {
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'thumbnail_' + Date.now() + '.jpg';
            a.click();
            URL.revokeObjectURL(a.href);
        })
        .catch(() => alert('İndirme hatası, sağ tıklayıp "Resmi Farklı Kaydet" ile indirin.'));
}
</script>
</body>
</html>