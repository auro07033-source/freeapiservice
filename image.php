<?php
/**
 * Görsel Oluşturma API (Direkt Görsel)
 * Geliştirici: @zanetmez
 */

$prompt = isset($_GET['prompt']) ? trim($_GET['prompt']) : '';
$width = isset($_GET['width']) ? intval($_GET['width']) : 1024;
$height = isset($_GET['height']) ? intval($_GET['height']) : 1024;
$nologo = isset($_GET['nologo']) ? $_GET['nologo'] : 'true';

if (empty($prompt)) {
    // Prompt yoksa uyarı göster
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>🎨 Görsel Oluşturma</h1>';
    echo '<p>Kullanım: <b>?prompt=kedi</b></p>';
    echo '<p>Örnek: <a href="?prompt=gerçekçi kedi, 4k">?prompt=gerçekçi kedi, 4k</a></p>';
    echo '<p>Geliştirici: @zanetmez</p>';
    exit;
}

// Pollinations.ai'den görsel al
$url = "https://image.pollinations.ai/prompt/" . urlencode($prompt);
$url .= "?width=" . $width . "&height=" . $height . "&nologo=" . $nologo;

// CORS ve cache header
header('Content-Type: image/jpeg');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=86400');

// Görseli göster
readfile($url);
?>