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
    http_response_code(404);
    exit;
}

$itemId = preg_replace('/[^a-zA-Z0-9]/', '', $itemId);

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
$cacheMaxAge = 86400;

// Serve from cache
if (file_exists($cachedMeta) && (time() - filemtime($cachedMeta)) < $cacheMaxAge) {
    $meta = json_decode(file_get_contents($cachedMeta), true);
    if ($meta) {
        if (!empty($meta['is_valid']) && file_exists($cachedFile)) {
            header('Content-Type: ' . $meta['content_type']);
            header('Cache-Control: public, max-age=86400');
            readfile($cachedFile);
            exit;
        } else {
            http_response_code(404);
            exit;
        }
    }
}

// Fetch from Clover
$url = CLOVER_API_BASE . '/items/' . $itemId . '/image';
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . CLOVER_API_TOKEN,
        'Accept: image/*'
    ]
]);

$imageData   = curl_exec($ch);
$httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

$isValid = false;

if ($httpCode === 200 && $imageData && strlen($imageData) > 500) {
    $isGreen = false;
    
    if (function_exists('imagecreatefromstring')) {
        $img = @imagecreatefromstring($imageData);
        if ($img) {
            $w = imagesx($img);
            $h = imagesy($img);
            $rgb = imagecolorat($img, intval($w/2), intval($h/2));
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            if ($g > 150 && $r < 100 && $b < 100) {
                $isGreen = true;
            }
            
            if (!$isGreen) {
                $samples = [
                    imagecolorat($img, 5, 5),
                    imagecolorat($img, $w-5, 5),
                    imagecolorat($img, 5, $h-5),
                    imagecolorat($img, $w-5, $h-5)
                ];
                if (count(array_unique($samples)) === 1) {
                    $sg = ($samples[0] >> 8) & 0xFF;
                    $sr = ($samples[0] >> 16) & 0xFF;
                    if ($sg > 100 && $sr < 100) {
                        $isGreen = true;
                    }
                }
            }
            
            imagedestroy($img);
        }
    }
    
    if (!$isGreen) {
        $isValid = true;
    }
}

// Cache result
file_put_contents($cachedMeta, json_encode([
    'is_valid'     => $isValid,
    'content_type' => $contentType ?? '',
    'fetched_at'   => date('Y-m-d H:i:s'),
    'item_id'      => $itemId
]));

if ($isValid) {
    file_put_contents($cachedFile, $imageData);
    header('Content-Type: ' . $contentType);
    header('Cache-Control: public, max-age=86400');
    echo $imageData;
} else {
    file_put_contents($cachedFile, '');
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