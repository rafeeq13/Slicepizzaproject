<?php
/**
 * Slice+ Deals Deep-Linking API
 * Solves: Deal click on website → specific Clover item
 * 
 * GET /api/deals.php?slug=large-pepperoni-pizza   → Find item by slug
 * GET /api/deals.php?clover_id=XXXXXXX            → Find item by Clover ID
 * GET /api/deals.php?map=1                         → Full deals map
 */

require_once __DIR__ . '/config.php';

$slug     = $_GET['slug'] ?? null;
$cloverId = $_GET['clover_id'] ?? null;
$showMap  = isset($_GET['map']);

// ---- FULL MAP ----
if ($showMap) {
    $map = cache_read(CACHE_DIR . 'deals_map.json');
    if (!$map) {
        json_response(['success' => false, 'message' => 'Deals map not cached. Run /api/sync.php first.'], 404);
    }
    json_response(['success' => true, 'deals_count' => count($map), 'deals' => $map]);
}

// ---- LOOKUP BY SLUG ----
if ($slug) {
    $slug = strtolower(trim($slug));
    $map  = cache_read(CACHE_DIR . 'deals_map.json');
    if (!$map) { json_error('Deals map not cached. Run sync first.', 404); }
    
    // Exact match
    if (isset($map[$slug])) {
        json_response(['success' => true, 'deal' => $map[$slug]]);
    }
    
    // Fuzzy match
    $bestMatch = null;
    $bestScore = 0;
    foreach ($map as $key => $deal) {
        if (strpos($key, $slug) !== false || strpos($slug, $key) !== false) {
            $score = similar_text($slug, $key);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMatch = $deal;
                $bestMatch['matched_slug'] = $key;
            }
        }
    }
    
    if ($bestMatch) {
        json_response(['success' => true, 'deal' => $bestMatch, 'fuzzy_match' => true]);
    }
    
    json_error('Deal not found for slug: ' . $slug, 404);
}

// ---- LOOKUP BY CLOVER ID ----
if ($cloverId) {
    $item = clover_api("/items/{$cloverId}?expand=categories,modifierGroups");
    if (isset($item['error'])) { json_error('Item not found in Clover', 404); }
    
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
    
    json_response([
        'success' => true,
        'item'    => [
            'id'              => $item['id'],
            'name'            => $item['name'],
            'description'     => $item['description'] ?? '',
            'price'           => isset($item['price']) ? format_price($item['price']) : '0.00',
            'price_cents'     => $item['price'] ?? 0,
            'modifier_groups' => $modGroupsDetailed
        ]
    ]);
}

json_error('Missing parameter: slug, clover_id, or map=1', 400);