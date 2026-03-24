<?php
/**
 * Slice+ Categories API
 * GET /api/categories.php          → All categories
 * GET /api/categories.php?id=ABC   → Single category with items
 */

require_once __DIR__ . '/config.php';

$categoryId = $_GET['id'] ?? null;

// Check cache
$cached = cache_read(CACHE_CATEGORIES_FILE);
if ($cached && !$categoryId) {
    json_response(['success' => true, 'cached' => true, 'categories' => $cached]);
}

// Fetch from Clover
$result = clover_api('/categories?orderBy=sortOrder&limit=100');
if (isset($result['error'])) {
    json_error('Failed to fetch categories: ' . $result['error']);
}

$categories = array_map(function($cat) {
    return [
        'id'         => $cat['id'],
        'name'       => $cat['name'],
        'sort_order' => $cat['sortOrder'] ?? 999
    ];
}, $result['elements'] ?? []);

cache_write(CACHE_CATEGORIES_FILE, $categories);

// Single category with items
if ($categoryId) {
    $itemsRaw = clover_api("/categories/{$categoryId}/items?expand=modifierGroups&limit=200");
    if (isset($itemsRaw['error'])) {
        json_error('Category not found', 404);
    }
    
    $items = array_map(function($item) {
        return [
            'id'          => $item['id'],
            'name'        => $item['name'],
            'description' => $item['description'] ?? '',
            'price'       => isset($item['price']) ? format_price($item['price']) : '0.00',
            'price_cents' => $item['price'] ?? 0
        ];
    }, $itemsRaw['elements'] ?? []);
    
    $catName = '';
    foreach ($categories as $c) {
        if ($c['id'] === $categoryId) { $catName = $c['name']; break; }
    }
    
    json_response(['success' => true, 'category' => ['id' => $categoryId, 'name' => $catName, 'items' => $items]]);
}

json_response(['success' => true, 'cached' => false, 'categories' => $categories, 'categories_count' => count($categories)]);