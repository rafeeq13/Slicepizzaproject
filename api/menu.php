<?php
/**
 * Slice+ Menu API — Phase 3 Updated
 * Now includes: item images, category whitelist
 * 
 * GET /api/menu.php              → Full menu (whitelisted categories only)
 * GET /api/menu.php?fresh=1      → Bypass cache
 * GET /api/menu.php?category=ID  → Single category
 * GET /api/menu.php?all=1        → All categories (no filter)
 */

require_once __DIR__ . '/config.php';

// =============================================
// ONLINE ORDERING CATEGORIES WHITELIST
// Only these categories show on website
// =============================================
$ONLINE_CATEGORIES = [
    'Best Deals',
    'Weekly Special Deals',
    'Indian Pizza',
    'Gourmet Pizzas',
    'Build Your Own Pizza',
    'Subs',
    'Wraps',
    "MOMO'S / DUMPLINGS",
    'Chicken Fingers',
    'Chicken Wings',
    'Garlic Fingers',
    'Chicken Bites',
    'Salads',
    'Sides',
    "Dessert's"
];

$categoryFilter = $_GET['category'] ?? null;
$forceFresh     = isset($_GET['fresh']) && $_GET['fresh'] == '1';
$showAll        = isset($_GET['all']) && $_GET['all'] == '1';

// Check cache
if (!$forceFresh) {
    $cached = cache_read(CACHE_MENU_FILE);
    if ($cached) {
        $cats = $cached['categories'];
        
        // Apply whitelist filter
        if (!$showAll) {
            $cats = array_values(array_filter($cats, function($cat) use ($ONLINE_CATEGORIES) {
                return in_array($cat['name'], $ONLINE_CATEGORIES);
            }));
        }
        
        if ($categoryFilter) {
            $cats = array_values(array_filter($cats, function($cat) use ($categoryFilter) {
                return $cat['id'] === $categoryFilter || strtolower($cat['name']) === strtolower($categoryFilter);
            }));
        }
        
        $itemCount = 0;
        foreach ($cats as $c) { $itemCount += count($c['items']); }
        
        json_response([
            'success'          => true,
            'cached'           => true,
            'cached_at'        => date('Y-m-d H:i:s', filemtime(CACHE_MENU_FILE)),
            'categories'       => $cats,
            'items_count'      => $itemCount,
            'categories_count' => count($cats)
        ]);
    }
}

// ---- FETCH FROM CLOVER API ----

// Step 1: Categories
$categoriesRaw = clover_api('/categories?orderBy=sortOrder&limit=100');
if (isset($categoriesRaw['error'])) {
    json_error('Failed to fetch categories: ' . $categoriesRaw['error']);
}
$categories = $categoriesRaw['elements'] ?? [];

// Step 2: Items with images
$itemsRaw = clover_api('/items?limit=500&expand=categories,modifierGroups,tags&filter=hidden=false');
if (isset($itemsRaw['error'])) {
    json_error('Failed to fetch items: ' . $itemsRaw['error']);
}
$allItems = $itemsRaw['elements'] ?? [];

// Step 3: Modifier groups
$modGroupsRaw = clover_api('/modifier_groups?expand=modifiers&limit=200');
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

// Step 4: Stock
$stocksRaw = clover_api('/item_stocks?limit=500');
$stockMap = [];
if (!isset($stocksRaw['error'])) {
    foreach (($stocksRaw['elements'] ?? []) as $stock) {
        if (isset($stock['item']['id'])) {
            $stockMap[$stock['item']['id']] = $stock['quantity'] ?? null;
        }
    }
}

// ---- BUILD STRUCTURED MENU ----
$menuCategories = [];
$totalItems = 0;

foreach ($categories as $cat) {
    $catId = $cat['id'];
    $catItems = [];
    
    foreach ($allItems as $item) {
        $belongsToCategory = false;
        foreach (($item['categories']['elements'] ?? []) as $ic) {
            if ($ic['id'] === $catId) { $belongsToCategory = true; break; }
        }
        if (!$belongsToCategory) continue;
        
        // Modifiers
        $itemModGroups = [];
        foreach (($item['modifierGroups']['elements'] ?? []) as $img) {
            if (isset($modGroups[$img['id']])) {
                $itemModGroups[] = $modGroups[$img['id']];
            }
        }
        
        // Availability
        $available = true;
        if (isset($stockMap[$item['id']]) && $stockMap[$item['id']] !== null && $stockMap[$item['id']] <= 0) {
            $available = false;
        }
        
        // Image URL — Clover image endpoint
        $imageUrl = 'https://api.clover.com/v3/merchants/' . CLOVER_MERCHANT_ID . '/items/' . $item['id'] . '/image?access_token=' . CLOVER_API_TOKEN;
        
        $catItems[] = [
            'id'              => $item['id'],
            'name'            => $item['name'],
            'description'     => $item['description'] ?? '',
            'price'           => isset($item['price']) ? format_price($item['price']) : '0.00',
            'price_cents'     => $item['price'] ?? 0,
            'available'       => $available,
            'image'           => $imageUrl,
            'modifier_groups' => $itemModGroups,
            'sort_order'      => $item['sortOrder'] ?? 999
        ];
        $totalItems++;
    }
    
    usort($catItems, function($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
    
    $menuCategories[] = [
        'id'          => $catId,
        'name'        => $cat['name'],
        'sort_order'  => $cat['sortOrder'] ?? 999,
        'items'       => $catItems,
        'items_count' => count($catItems)
    ];
}

usort($menuCategories, function($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });

// Save full menu to cache (unfiltered)
$menuData = [
    'categories'       => $menuCategories,
    'items_count'      => $totalItems,
    'categories_count' => count($menuCategories),
    'modifier_groups'  => $modGroups,
    'synced_at'        => date('Y-m-d H:i:s')
];
cache_write(CACHE_MENU_FILE, $menuData);

// Apply whitelist filter for response
if (!$showAll) {
    $menuCategories = array_values(array_filter($menuCategories, function($cat) use ($ONLINE_CATEGORIES) {
        return in_array($cat['name'], $ONLINE_CATEGORIES);
    }));
}

if ($categoryFilter) {
    $menuCategories = array_values(array_filter($menuCategories, function($cat) use ($categoryFilter) {
        return $cat['id'] === $categoryFilter || strtolower($cat['name']) === strtolower($categoryFilter);
    }));
}

$filteredCount = 0;
foreach ($menuCategories as $c) { $filteredCount += count($c['items']); }

json_response([
    'success'          => true,
    'cached'           => false,
    'synced_at'        => $menuData['synced_at'],
    'categories'       => $menuCategories,
    'items_count'      => $filteredCount,
    'categories_count' => count($menuCategories)
]);