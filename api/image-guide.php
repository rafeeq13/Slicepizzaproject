<?php
/**
 * Slice+ Image Upload Helper
 * Shows all menu items and the exact filename to use for each image
 * 
 * ACCESS: /api/image-guide.php?key=sliceplus2025sync
 * DELETE THIS FILE after uploading all images
 */

require_once __DIR__ . '/config.php';

$key = $_GET['key'] ?? '';
if ($key !== 'sliceplus2025sync') {
    die('Unauthorized');
}

// Get cached menu
$cached = cache_read(CACHE_MENU_FILE);
if (!$cached) {
    die('Menu not cached. Run sync.php first.');
}

// Whitelist
$ONLINE_CATEGORIES = [
    'Best Deals', 'Weekly Special Deals', 'Indian Pizza', 'Gourmet Pizzas',
    'Build Your Own Pizza', 'Subs', 'Wraps', "MOMO'S / DUMPLINGS",
    'Chicken Fingers', 'Chicken Wings', 'Garlic Fingers', 'Chicken Bites',
    'Salads', 'Sides', "Dessert's"
];

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[\'\"]+/', '', $text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Check which images already exist
$menuImgDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/menu/';
$existingImages = [];
if (is_dir($menuImgDir)) {
    foreach (scandir($menuImgDir) as $f) {
        if ($f === '.' || $f === '..') continue;
        $nameOnly = pathinfo($f, PATHINFO_FILENAME);
        $existingImages[$nameOnly] = $f;
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Slice+ Image Upload Guide</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #0a0a0a; color: #fff; padding: 40px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #df2b2b; margin-bottom: 8px; }
        .subtitle { color: #888; margin-bottom: 32px; }
        .stats { display: flex; gap: 20px; margin-bottom: 32px; }
        .stat { background: #1a1a1a; padding: 16px 24px; border-radius: 12px; }
        .stat-num { font-size: 1.8rem; font-weight: 800; color: #df2b2b; }
        .stat-label { font-size: 0.8rem; color: #888; margin-top: 4px; }
        .cat-section { margin-bottom: 32px; }
        .cat-name { font-size: 1.1rem; font-weight: 700; padding: 10px 0; border-bottom: 1px solid #333; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 8px 12px; font-size: 0.75rem; color: #888; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #333; }
        td { padding: 10px 12px; border-bottom: 1px solid #1a1a1a; font-size: 0.88rem; }
        .filename { font-family: monospace; color: #22c55e; background: #111; padding: 4px 8px; border-radius: 4px; font-size: 0.82rem; }
        .status-yes { color: #22c55e; font-weight: 700; }
        .status-no { color: #ef4444; }
        .instructions { background: #1a1a1a; border-radius: 12px; padding: 24px; margin-bottom: 32px; border-left: 4px solid #df2b2b; }
        .instructions h3 { color: #df2b2b; margin-bottom: 12px; }
        .instructions code { background: #111; padding: 2px 6px; border-radius: 4px; color: #22c55e; }
        .copy-btn { background: #333; color: #fff; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.75rem; }
        .copy-btn:hover { background: #555; }
    </style>
</head>
<body>
    <h1>Slice+ Image Upload Guide</h1>
    <p class="subtitle">All menu items with exact image filenames required</p>
    
    <div class="instructions">
        <h3>How to Upload Images</h3>
        <p>1. Create folder: <code>assets/images/menu/</code> in cPanel</p>
        <p>2. For each item below, save the image with the <strong>exact filename</strong> shown</p>
        <p>3. Supported formats: <code>.jpg</code> <code>.jpeg</code> <code>.png</code> <code>.webp</code></p>
        <p>4. Recommended size: <strong>400x300px</strong> or similar ratio, under 200KB</p>
        <p>5. Upload all images to <code>assets/images/menu/</code></p>
        <p>6. Images will automatically appear on the website — no code changes needed</p>
    </div>

    <?php
    $totalItems = 0;
    $totalWithImage = 0;
    $totalWithout = 0;
    
    // Count first
    foreach ($cached['categories'] as $cat) {
        if (!in_array($cat['name'], $ONLINE_CATEGORIES)) continue;
        foreach ($cat['items'] as $item) {
            $totalItems++;
            $slug = slugify($item['name']);
            if (isset($existingImages[$slug])) $totalWithImage++;
            else $totalWithout++;
        }
    }
    ?>
    
    <div class="stats">
        <div class="stat"><div class="stat-num"><?= $totalItems ?></div><div class="stat-label">Total Items</div></div>
        <div class="stat"><div class="stat-num" style="color:#22c55e"><?= $totalWithImage ?></div><div class="stat-label">Images Uploaded</div></div>
        <div class="stat"><div class="stat-num" style="color:#ef4444"><?= $totalWithout ?></div><div class="stat-label">Images Missing</div></div>
    </div>
    
    <?php foreach ($cached['categories'] as $cat): ?>
        <?php if (!in_array($cat['name'], $ONLINE_CATEGORIES)) continue; ?>
        <div class="cat-section">
            <div class="cat-name"><?= htmlspecialchars($cat['name']) ?> (<?= count($cat['items']) ?> items)</div>
            <table>
                <tr><th>#</th><th>Item Name</th><th>Image Filename</th><th>Status</th></tr>
                <?php $n = 0; foreach ($cat['items'] as $item): $n++; ?>
                    <?php
                    $slug = slugify($item['name']);
                    $hasImage = isset($existingImages[$slug]);
                    ?>
                    <tr>
                        <td><?= $n ?></td>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><span class="filename"><?= $slug ?>.jpg</span></td>
                        <td class="<?= $hasImage ? 'status-yes' : 'status-no' ?>"><?= $hasImage ? '✓ Uploaded' : '✗ Missing' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endforeach; ?>
    
    <div style="margin-top:40px;padding:20px;background:rgba(239,68,68,0.1);border-radius:12px;border:1px solid rgba(239,68,68,0.2);">
        <strong style="color:#ef4444;">DELETE this file after uploading all images!</strong>
    </div>
</body>
</html>