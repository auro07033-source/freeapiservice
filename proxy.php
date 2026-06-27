<?php
/**
 * TRT Segment Proxy
 * Geliştirici: @zanetmez
 */

$url = isset($_GET['url']) ? $_GET['url'] : '';

if (empty($url)) {
    die('URL gerekli');
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_REFERER, 'https://www.trthaber.com/canli-yayin');
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($response) {
    header('Content-Type: ' . $contentType);
    header('Access-Control-Allow-Origin: *');
    echo $response;
} else {
    http_response_code(404);
    echo 'Segment alınamadı';
}
?>