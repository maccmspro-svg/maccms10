<?php
/**
 * 公告代理接口（前端同源访问，后端拉取远程）
 * 访问：/api/notice.php
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$empty = [
    'show' => false,
];

$cfg = @include dirname(__DIR__) . '/application/extra/notice_tip.php';
if (!is_array($cfg) || empty($cfg['api'])) {
    echo json_encode($empty, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$remote = trim((string)$cfg['api']);
if ($remote === '') {
    echo json_encode($empty, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
// 将当前站点域名透传给远程接口，便于远程按域名统计使用站点
$site = '';
if (isset($_SERVER['HTTP_HOST'])) {
    $site = strtolower(trim((string)$_SERVER['HTTP_HOST']));
}
if ($site !== '') {
    $remote .= (strpos($remote, '?') === false ? '?' : '&') . 'site=' . rawurlencode($site);
}

$raw = '';

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $raw = curl_exec($ch);
    curl_close($ch);
} else {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 3,
            'ignore_errors' => true,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    $raw = @file_get_contents($remote, false, $ctx);
}

if (!is_string($raw) || trim($raw) === '') {
    echo json_encode($empty, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$json = json_decode($raw, true);
if (!is_array($json)) {
    echo json_encode($empty, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$show = isset($json['show']) ? (bool)$json['show'] : false;
$text = isset($json['text']) ? trim((string)$json['text']) : '';
$url = isset($json['url']) ? trim((string)$json['url']) : '';
$delay = isset($json['delay_seconds']) ? intval($json['delay_seconds']) : 2;
$daily = isset($json['daily_limit']) ? intval($json['daily_limit']) : 1;
if ($delay < 0) {
    $delay = 0;
}
if ($daily < 1) {
    $daily = 1;
}

echo json_encode([
    'show' => $show,
    'text' => $text,
    'url' => $url,
    'delay_seconds' => $delay,
    'daily_limit' => $daily,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
