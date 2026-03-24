<?php
/**
 * Slice+ Menu Sync / Refresh
 * 
 * Manual:  GET /api/sync.php?key=sliceplus2025sync
 * Cron:    curl -s https://sliceplus.ca/api/sync.php?key=sliceplus2025sync > /dev/null
 */

require_once __DIR__ . '/config.php';

// ---- SECURITY KEY ----
define('SYNC_KEY', 'sliceplus2025sync');

$providedKey = $_GET['key'] ?? '';
if ($providedKey !== SYNC_KEY) {
    json_error('Unauthorized. Invalid sync key.', 401);
}

// ---- CLEAR CACHE ----
$cacheFiles = glob(CACHE_DIR . '*.json');
$cleared = 0;
foreach ($cacheFiles as $file) {
    if (unlink($file)) $cleared++;
}

// ---- SYNC CATEGORIES ----
$catResult = clover_api('/categories?orderBy=sortOrder&limit=100');
$categories = [];
$catError = null;

if (isset($catResult['error'])) {
    $catError = $catResult['error'];
} else {
    $categories = array_map(function($cat) {
        return [
            'id'         => $cat['id'],
            'name'       => $cat['name'],
            'sort_order' => $cat['sortOrder'] ?? 999
        ];
    }, $catResult['elements'] ?? []);
    cache_write(CACHE_CATEGORIES_FILE, $categories);
}

// ---- SYNC ITEMS + MODIFIERS + STOCK ----
$itemsRaw     = clover_api('/items?limit=500&expand=categories,modifierGroups,tags&filter=hidden=false');
$modGroupsRaw = clover_api('/modifier_groups?expand=modifiers&limit=200');
$stocksRaw    = clover_api('/item_stocks?limit=500');

$menuError = null;
$totalItems = 0;
$totalModGroups = 0;

if (isset($itemsRaw['error'])) {
    $menuError = $itemsRaw['error'];
} else {
    $allItems = $itemsRaw['elements'] ?? [];
    
    // Modifier groups
    $modGroups = [];
    if (!isset($modGroupsRaw['error'])) {
        foreach (($modGroupsRaw['elements'] ?? []) as $mg) {
            $modGroups[$mg['id']] = [
                'id'        => $mg['id'],
                'name'      => $mg['name'],
                'min'       => $mg['minRequired'] ?? 0,
                'max'       => $mg['maxAllowed'] ?? 0,
                'modifiers' => array_map(function($mod) {
                    return [
                        'id'          => $mod['id'],
                        'name'        => $mod['name'],
                        'price'       => isset($mod['price']) ? format_price($mod['price']) : '0.00',
                        'price_cents' => $mod['price'] ?? 0
                    ];
                }, $mg['modifiers']['elements'] ?? [])
            ];
        }
    }
    $totalModGroups = count($modGroups);
    
    // Stock map
    $stockMap = [];
    if (!isset($stocksRaw['error'])) {
        foreach (($stocksRaw['elements'] ?? []) as $stock) {
            if (isset($stock['item']['id'])) {
                $stockMap[$stock['item']['id']] = $stock['quantity'] ?? null;
            }
        }
    }
    
    // Build structured menu
    $menuCategories = [];
    foreach ($categories as $cat) {
        $catItems = [];
        foreach ($allItems as $item) {
            $belongsToCategory = false;
            foreach (($item['categories']['elements'] ?? []) as $ic) {
                if ($ic['id'] === $cat['id']) { $belongsToCategory = true; break; }
            }
            if (!$belongsToCategory) continue;
            
            $itemModGroups = [];
            foreach (($item['modifierGroups']['elements'] ?? []) as $img) {
                if (isset($modGroups[$img['id']])) { $itemModGroups[] = $modGroups[$img['id']]; }
            }
            
            $available = true;
            if (isset($stockMap[$item['id']]) && $stockMap[$item['id']] !== null && $stockMap[$item['id']] <= 0) {
                $available = false;
            }
            
            $catItems[] = [
                'id'              => $item['id'],
                'name'            => $item['name'],
                'description'     => $item['description'] ?? '',
                'price'           => isset($item['price']) ? format_price($item['price']) : '0.00',
                'price_cents'     => $item['price'] ?? 0,
                'available'       => $available,
                'modifier_groups' => $itemModGroups,
                'sort_order'      => $item['sortOrder'] ?? 999
            ];
            $totalItems++;
        }
        
        usort($catItems, function($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
        
        $menuCategories[] = [
            'id'          => $cat['id'],
            'name'        => $cat['name'],
            'sort_order'  => $cat['sort_order'],
            'items'       => $catItems,
            'items_count' => count($catItems)
        ];
    }
    
    usort($menuCategories, function($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
    
    $menuData = [
        'categories'       => $menuCategories,
        'items_count'      => $totalItems,
        'categories_count' => count($menuCategories),
        'modifier_groups'  => $modGroups,
        'synced_at'        => date('Y-m-d H:i:s')
    ];
    cache_write(CACHE_MENU_FILE, $menuData);
    
    // Build deals map
    $dealsMap = [];
    foreach ($allItems as $item) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $item['name']));
        $dealsMap[$slug] = [
            'clover_id' => $item['id'],
            'name'      => $item['name'],
            'price'     => isset($item['price']) ? format_price($item['price']) : '0.00'
        ];
    }
    cache_write(CACHE_DIR . 'deals_map.json', $dealsMap);
}

// ---- SYNC REPORT ----
json_response([
    'success' => true,
    'sync'    => [
        'timestamp'         => date('Y-m-d H:i:s'),
        'cache_cleared'     => $cleared . ' files',
        'categories_synced' => count($categories),
        'items_synced'      => $totalItems,
        'modifier_groups'   => $totalModGroups,
        'deals_mapped'      => isset($dealsMap) ? count($dealsMap) : 0,
        'errors'            => array_filter(['categories' => $catError, 'menu' => $menuError])
    ]
]);