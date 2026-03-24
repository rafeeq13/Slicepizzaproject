<?php
/**
 * Slice+ Item Detail API
 * GET /api/items.php?id=ITEM_ID    → Single item with modifiers
 * GET /api/items.php?search=pizza  → Search items by name
 */

require_once __DIR__ . '/config.php';

$itemId    = $_GET['id'] ?? null;
$searchStr = $_GET['search'] ?? null;

// ---- SINGLE ITEM ----
if ($itemId) {
    $item = clover_api("/items/{$itemId}?expand=categories,modifierGroups,tags");
    if (isset($item['error'])) {
        json_error('Item not found', 404);
    }
    
    // Fetch full modifier details
    $modGroupsDetailed = [];
    foreach (($item['modifierGroups']['elements'] ?? []) as $mg) {
        $mgDetail = clover_api("/modifier_groups/{$mg['id']}?expand=modifiers");
        if (!isset($mgDetail['error'])) {
            $modGroupsDetailed[] = [
                'id'        => $mgDetail['id'],
                'name'      => $mgDetail['name'],
                'min'       => $mgDetail['minRequired'] ?? 0,
                'max'       => $mgDetail['maxAllowed'] ?? 0,
                'modifiers' => array_map(function($mod) {
                    return [
                        'id'          => $mod['id'],
                        'name'        => $mod['name'],
                        'price'       => isset($mod['price']) ? format_price($mod['price']) : '0.00',
                        'price_cents' => $mod['price'] ?? 0
                    ];
                }, $mgDetail['modifiers']['elements'] ?? [])
            ];
        }
    }
    
    $itemCats = array_map(function($c) {
        return ['id' => $c['id'], 'name' => $c['name']];
    }, $item['categories']['elements'] ?? []);
    
    // Check stock
    $available = true;
    $stockRaw = clover_api("/item_stocks/{$itemId}");
    if (!isset($stockRaw['error']) && isset($stockRaw['quantity']) && $stockRaw['quantity'] <= 0) {
        $available = false;
    }
    
    json_response([
        'success' => true,
        'item'    => [
            'id'              => $item['id'],
            'name'            => $item['name'],
            'description'     => $item['description'] ?? '',
            'price'           => isset($item['price']) ? format_price($item['price']) : '0.00',
            'price_cents'     => $item['price'] ?? 0,
            'available'       => $available,
            'categories'      => $itemCats,
            'modifier_groups' => $modGroupsDetailed
        ]
    ]);
}

// ---- SEARCH ----
if ($searchStr) {
    $searchStr = trim($searchStr);
    if (strlen($searchStr) < 2) {
        json_error('Search query must be at least 2 characters', 400);
    }
    
    // Fetch all items and filter locally (most reliable)
    $allItems = clover_api('/items?limit=500&expand=categories');
    $filtered = [];
    
    if (!isset($allItems['error'])) {
        foreach (($allItems['elements'] ?? []) as $item) {
            if (stripos($item['name'], $searchStr) !== false || 
                stripos($item['description'] ?? '', $searchStr) !== false) {
                $filtered[] = [
                    'id'          => $item['id'],
                    'name'        => $item['name'],
                    'description' => $item['description'] ?? '',
                    'price'       => isset($item['price']) ? format_price($item['price']) : '0.00',
                    'price_cents' => $item['price'] ?? 0
                ];
            }
        }
    }
    
    json_response(['success' => true, 'search' => $searchStr, 'results' => $filtered, 'results_count' => count($filtered)]);
}

json_error('Missing parameter: id or search', 400);