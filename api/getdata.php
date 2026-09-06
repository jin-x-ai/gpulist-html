<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

const PAGE_URL = 'https://www.videocardbenchmark.net/GPU_mega_page.html';
const DATA_URL = 'https://www.videocardbenchmark.net/data';
const CACHE_TTL = 900;

function fail(string $message, int $status = 502): void
{
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_SLASHES);
    exit;
}

function request(string $url, array $headers, string $cookieFile): string
{
    $curl = curl_init($url);
    if ($curl === false) {
        fail('Unable to initialize cURL');
    }

    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; PassmarkMegaPage/1.0)',
    ]);

    $body = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if ($body === false) {
        fail('PassMark request failed: ' . $error);
    }
    if ($status < 200 || $status >= 300) {
        fail('PassMark request failed (HTTP ' . $status . ')');
    }
    return $body;
}

$cacheFile = sys_get_temp_dir() . '/gpulist-html-' . sha1(__DIR__) . '.json';
if (is_file($cacheFile) && filemtime($cacheFile) !== false && time() - filemtime($cacheFile) < CACHE_TTL) {
    readfile($cacheFile);
    exit;
}

$cookieFile = tempnam(sys_get_temp_dir(), 'gpulist-cookie-');
if ($cookieFile === false) {
    fail('Unable to create a temporary cookie file', 500);
}

try {
    request(PAGE_URL, [
        'Accept: text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.8',
    ], $cookieFile);

    $payload = request(DATA_URL, [
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Accept-Language: en-US,en;q=0.8',
        'Referer: ' . PAGE_URL,
        'X-Requested-With: XMLHttpRequest',
    ], $cookieFile);

    $decoded = json_decode($payload, true);
    if (!is_array($decoded) || !isset($decoded['data']) || !is_array($decoded['data'])) {
        fail('Unexpected PassMark data format');
    }

    file_put_contents($cacheFile, $payload, LOCK_EX);
    echo $payload;
} finally {
    @unlink($cookieFile);
}
