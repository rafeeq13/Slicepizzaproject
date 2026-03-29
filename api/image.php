<?php
header('Cache-Control: public, max-age=2592000, immutable');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
/**
 * Slice+ Image Proxy — v3
 * 
 * Priority:
 * 1. Check local folder: assets/images/menu/{slug}.jpg / .png / .webp
 * 2. Check Clover API (if image exists and not green placeholder)
 * 3. Return 404 (frontend handles with CSS)
 * 
 * GET /api/image.php?id=ITEM_ID&name=Any+Medium+1+Topping+Pizza
 */

require_once __DIR__ . '/config.php';

$itemId   = $_GET['id'] ?? null;
$itemName = $_GET['name'] ?? '';

if (!$itemId) {
    header('Cache-Control: public, max-age=300');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
    http_response_code(404);
    exit;
}

$itemId = preg_replace('/[^a-zA-Z0-9]/', '', $itemId);

function sp_fetch_image_url($url, $headers = []) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER     => $headers
    ]);

    $data        = curl_exec($ch);
    $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $curlErr     = curl_error($ch);
    curl_close($ch);

    return [
        'ok'           => ($curlErr === '' && $httpCode === 200 && is_string($data) && $data !== ''),
        'http_code'    => $httpCode,
        'content_type' => $contentType ?: '',
        'data'         => is_string($data) ? $data : '',
        'error'        => $curlErr ?: ''
    ];
}

function sp_is_probable_green_placeholder($imageData) {
    if (!function_exists('imagecreatefromstring')) return false;
    $img = @imagecreatefromstring($imageData);
    if (!$img) return false;

    $w = imagesx($img);
    $h = imagesy($img);
    if ($w < 10 || $h < 10) { imagedestroy($img); return false; }

    $samples = 0;
    $greenish = 0;
    $minR = 255; $minG = 255; $minB = 255;
    $maxR = 0;   $maxG = 0;   $maxB = 0;

    // 5x5 grid sampling (avoid very edges)
    for ($gy = 1; $gy <= 5; $gy++) {
        for ($gx = 1; $gx <= 5; $gx++) {
            $x = intval($w * ($gx / 6));
            $y = intval($h * ($gy / 6));
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            $samples++;
            if ($g > 150 && $r < 120 && $b < 120) $greenish++;

            if ($r < $minR) $minR = $r;
            if ($g < $minG) $minG = $g;
            if ($b < $minB) $minB = $b;
            if ($r > $maxR) $maxR = $r;
            if ($g > $maxG) $maxG = $g;
            if ($b > $maxB) $maxB = $b;
        }
    }

    imagedestroy($img);

    if ($samples === 0) return false;
    $greenRatio = $greenish / $samples;
    $lowVar = (($maxR - $minR) < 70) && (($maxG - $minG) < 70) && (($maxB - $minB) < 70);

    return ($greenRatio >= 0.85 && $lowVar);
}

// =============================================
// STEP 1: Check local images folder
// =============================================
$menuImgDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/menu/';
$slug = slugify($itemName);

if ($slug) {
    $extensions = ['jpg', 'jpeg', 'png', 'webp'];
    foreach ($extensions as $ext) {
        $localFile = $menuImgDir . $slug . '.' . $ext;
        if (file_exists($localFile)) {
            $mimeTypes = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'webp' => 'image/webp'
            ];
            header('Content-Type: ' . $mimeTypes[$ext]);
            header('Cache-Control: public, max-age=86400');
            readfile($localFile);
            exit;
        }
    }
    
    // Also try with item ID as filename
    foreach ($extensions as $ext) {
        $localFile = $menuImgDir . $itemId . '.' . $ext;
        if (file_exists($localFile)) {
            $mimeTypes = [
                'jpg'  => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png'  => 'image/png',
                'webp' => 'image/webp'
            ];
            header('Content-Type: ' . $mimeTypes[$ext]);
            header('Cache-Control: public, max-age=86400');
            readfile($localFile);
            exit;
        }
    }
}

// =============================================
// STEP 2: Check Clover API
// =============================================
$imgCacheDir = CACHE_DIR . 'images/';
if (!is_dir($imgCacheDir)) {
    mkdir($imgCacheDir, 0755, true);
}

$cachedFile = $imgCacheDir . $itemId . '.img';
$cachedMeta = $imgCacheDir . $itemId . '.meta';

// Serve from cache
if (file_exists($cachedMeta)) {
    $meta = json_decode(file_get_contents($cachedMeta), true);
    if (is_array($meta)) {
        $maxAge = !empty($meta['is_valid']) ? 86400 : 300; // retry missing images sooner
        if ((time() - filemtime($cachedMeta)) < $maxAge) {
            if (!empty($meta['is_valid']) && file_exists($cachedFile)) {
                header('Content-Type: ' . ($meta['content_type'] ?? 'image/jpeg'));
                header('Cache-Control: public, max-age=86400');
                readfile($cachedFile);
                exit;
            } else {
                header('Cache-Control: public, max-age=300');
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
                http_response_code(404);
                exit;
            }
        }
    }
}

// Fetch from Clover
$source = 'clover_api';
$usedUrl = '';
$contentType = '';
$imageData = '';
$httpCode = 0;

$cloverRes = sp_fetch_image_url(
    CLOVER_API_BASE . '/items/' . $itemId . '/image',
    [
        'Authorization: Bearer ' . CLOVER_API_TOKEN,
        'Accept: image/*'
    ]
);
$usedUrl = CLOVER_API_BASE . '/items/' . $itemId . '/image';
$httpCode = $cloverRes['http_code'];
$contentType = $cloverRes['content_type'];
$imageData = $cloverRes['data'];

$isValid = false;

if ($httpCode === 200 && $imageData && strlen($imageData) > 500 && stripos($contentType, 'image/') === 0) {
    if (!sp_is_probable_green_placeholder($imageData)) {
        $isValid = true;
    }
}

// Step 2b: Fallback to Clover Online Ordering "menu-assets" images (no token)
if (!$isValid) {
    $source = 'clover_static';
    $candidates = [];
    $sizes = [1200, 600, 240, 120];
    $exts = ['jpeg', 'jpg'];
    foreach ($sizes as $sz) {
        foreach ($exts as $ext) {
            $candidates[] = "https://cloverstatic.com/menu-assets/items/{$itemId}_{$sz}x{$sz}.{$ext}";
        }
    }

    foreach ($candidates as $candUrl) {
        $res = sp_fetch_image_url($candUrl, ['Accept: image/*']);
        if ($res['ok'] && strlen($res['data']) > 500 && stripos($res['content_type'], 'image/') === 0) {
            $isValid = true;
            $imageData = $res['data'];
            $contentType = $res['content_type'];
            $httpCode = 200;
            $usedUrl = $candUrl;
            break;
        }
    }
}

// Cache result
file_put_contents($cachedMeta, json_encode([
    'is_valid'     => $isValid,
    'content_type' => $contentType ?? '',
    'fetched_at'   => date('Y-m-d H:i:s'),
    'item_id'      => $itemId,
    'source'       => $source,
    'url'          => $usedUrl
]));

if ($isValid) {
    file_put_contents($cachedFile, $imageData);
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=86400');
    echo $imageData;
} else {
    file_put_contents($cachedFile, '');
    header('Cache-Control: public, max-age=300');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 300) . ' GMT');
    http_response_code(404);
}

// =============================================
// HELPER: Convert item name to slug
// =============================================
function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[\'\"]+/', '', $text);       // Remove quotes
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);   // Non-alphanumeric to dash
    $text = preg_replace('/-+/', '-', $text);            // Multiple dashes to single
    $text = trim($text, '-');                            // Trim dashes from ends
    return $text;
}
