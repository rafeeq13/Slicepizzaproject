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
// MENU OVERRIDES (display + ordering)
// =============================================
function sp_norm_key($text) {
    $text = strtolower((string)$text);
    $text = preg_replace('/[^a-z0-9]+/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function sp_ensure_image_name(&$cats) {
    if (!is_array($cats)) return;
    foreach ($cats as &$cat) {
        if (empty($cat['items']) || !is_array($cat['items'])) continue;
        foreach ($cat['items'] as &$it) {
            if (empty($it['image_name']) && !empty($it['name'])) {
                $it['image_name'] = $it['name'];
            }
        }
        unset($it);
    }
    unset($cat);
}

function sp_read_json_any($file) {
    if (!file_exists($file)) { return null; }
    $d = json_decode(file_get_contents($file), true);
    return is_array($d) ? $d : null;
}

function sp_write_json_any($file, $data) {
    $dir = dirname($file);
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function sp_try_fetch_clover_item($itemId) {
    $itemId = preg_replace('/[^a-zA-Z0-9]/', '', (string)$itemId);
    if (!$itemId) { return null; }

    $item = clover_api("/items/{$itemId}?expand=modifierGroups");
    if (isset($item['error']) || empty($item['id'])) { return null; }

    $modGroupsDetailed = [];
    foreach (($item['modifierGroups']['elements'] ?? []) as $mg) {
        if (empty($mg['id'])) continue;
        $mgDetail = clover_api("/modifier_groups/{$mg['id']}?expand=modifiers");
        if (isset($mgDetail['error']) || empty($mgDetail['id'])) { continue; }
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

    return [
        'id'              => $item['id'],
        'name'            => $item['name'],
        'description'     => $item['description'] ?? '',
        'price'           => isset($item['price']) ? format_price($item['price']) : '0.00',
        'price_cents'     => $item['price'] ?? 0,
        'available'       => true,
        'modifier_groups' => $modGroupsDetailed,
        'sort_order'      => $item['sortOrder'] ?? 999
    ];
}

function sp_apply_category_display_names(&$cats) {
    if (!is_array($cats)) return;
    $catRename = [
        // 'Candy'        => 'Bulk Candies',
        // 'Energy Drink' => 'Energy Drinks',
        // 'Chocolate'    => 'Chocolate Bars',
        // 'Soda'         => 'Pop (Cans & 2L)',
        // 'Sports Drink' => 'Sports Drinks',
    ];
    foreach ($cats as &$cat) {
        $name = $cat['name'] ?? '';
        if (isset($catRename[$name])) {
            $cat['name'] = $catRename[$name];
        }
    }
    unset($cat);
}

function sp_apply_menu_overrides(&$cats) {
    if (!is_array($cats)) return;

    foreach ($cats as &$cat) {
        $catName = $cat['name'] ?? '';
        $origCatName = $catName;

        // ---------- Best Deals ordering + cleanup ----------
        if ($origCatName === 'Best Deals' && !empty($cat['items']) && is_array($cat['items'])) {
            $want = [
                'any medium 1 topping pizza' => 1,
                'any 2 type foot long pizzas' => 2,
                'any medium pizza small garlic fingers' => 3,
                'any xl pizza 8 pc wings 2l pop' => 4,
                'any xl pizza xl garlic fingers 2l pop' => 5,
                'any 2xl pizza xl garlic fingers 2l pop' => 6,
                '2 medium donair wrap 710ml bottle' => 7,
                'any 2 subs' => 8,
            ];

            $picked = [];
            foreach ($cat['items'] as $it) {
                $k = sp_norm_key($it['name'] ?? '');
                // if (!isset($want[$k])) continue; // hide extras (ex: Any 2 Salads)

                // Display fixes
                if ($k === 'any 2 type foot long pizzas') {
                    $it['name'] = 'Any 2 Footlong Pizzas';
                } elseif ($k === '2 medium donair wrap 710ml bottle') {
                    $it['name'] = '2 Medium Donair Wraps + 710ml Bottle';
                }

                // Deal rule: allow cheese pizza (no required topping)
                if ($k === 'any medium 1 topping pizza' && !empty($it['modifier_groups']) && is_array($it['modifier_groups'])) {
                    foreach ($it['modifier_groups'] as &$mg) {
                        $mgKey = sp_norm_key($mg['name'] ?? '');
                        if (strpos($mgKey, 'topping') !== false) {
                            $mg['min'] = 0;
                        }
                    }
                    unset($mg);
                }

                $it['_sp_rank'] = $want[$k];
                $picked[] = $it;
            }

            usort($picked, function($a, $b) {
                return intval($a['_sp_rank'] ?? 999) <=> intval($b['_sp_rank'] ?? 999);
            });
            foreach ($picked as &$it) { unset($it['_sp_rank']); }
            unset($it);

            $cat['items'] = $picked;
            $cat['items_count'] = count($picked);
        }

        // ---------- Weekly Special Deals: ordering + label fix + missing Monday/Tuesday ----------
        if ($origCatName === 'Weekly Special Deals' && !empty($cat['items']) && is_array($cat['items'])) {
            foreach ($cat['items'] as &$it) {
                if (($it['name'] ?? '') === 'Healthy Way Thursday') {
                    $it['name'] = 'Footlong Thursday';
                }
            }
            unset($it);

            $dayRank = function($name) {
                $k = sp_norm_key($name);
                if (strpos($k, 'monday') !== false) return 1;
                if (strpos($k, 'tuesday') !== false) return 2;
                if (strpos($k, 'wednesday') !== false) return 3;
                if (strpos($k, 'thursday') !== false) return 4;
                if (strpos($k, 'friday') !== false) return 5;
                if (strpos($k, 'saturday') !== false) return 6;
                if (strpos($k, 'sunday') !== false) return 7;
                return 99;
            };

            usort($cat['items'], function($a, $b) use ($dayRank) {
                $ra = $dayRank($a['name'] ?? '');
                $rb = $dayRank($b['name'] ?? '');
                if ($ra !== $rb) return $ra <=> $rb;
                return sp_norm_key($a['name'] ?? '') <=> sp_norm_key($b['name'] ?? '');
            });
        }

        // ---------- Garlic Fingers: Original first ----------
        if ($origCatName === 'Garlic Fingers' && !empty($cat['items']) && is_array($cat['items'])) {
            usort($cat['items'], function($a, $b) {
                $an = $a['name'] ?? '';
                $bn = $b['name'] ?? '';
                if ($an === 'Original Garlic Fingers' && $bn !== 'Original Garlic Fingers') return -1;
                if ($bn === 'Original Garlic Fingers' && $an !== 'Original Garlic Fingers') return 1;
                return sp_norm_key($an) <=> sp_norm_key($bn);
            });
        }

        // ---------- Dessert: cookie packs allow multiple flavors ----------
        if ($origCatName === "Dessert's" && !empty($cat['items']) && is_array($cat['items'])) {
            foreach ($cat['items'] as &$it) {
                $k = sp_norm_key($it['name'] ?? '');
                if (($k === 'cookie 3 pc' || $k === 'cookie 6 pc') && !empty($it['modifier_groups']) && is_array($it['modifier_groups'])) {
                    $max = ($k === 'cookie 3 pc') ? 3 : 6;
                    foreach ($it['modifier_groups'] as &$mg) {
                        $mg['name'] = 'Choose Cookies';
                        $mg['max'] = $max;
                    }
                    unset($mg);
                }
            }
            unset($it);
        }
    }
    unset($cat);

    // Inject weekday specials (ex: Donair Monday) into Weekly Special Deals if missing.
    $weeklyIdx = null;
    foreach ($cats as $i => $c) {
        if (($c['name'] ?? '') === 'Weekly Special Deals') { $weeklyIdx = $i; break; }
    }
    if ($weeklyIdx !== null) {
        $weekly = &$cats[$weeklyIdx];
        if (empty($weekly['items']) || !is_array($weekly['items'])) { $weekly['items'] = []; }

        $existingIds = [];
        foreach ($weekly['items'] as $it) { if (!empty($it['id'])) $existingIds[$it['id']] = true; }

        $map = sp_read_json_any(CACHE_DEALS_FILE);
        $injFile = CACHE_DIR . 'injected_items.json';
        $inj = sp_read_json_any($injFile);
        if (!is_array($inj)) { $inj = []; }
        $injChanged = false;

        if (is_array($map)) {
            foreach ($map as $slug => $deal) {
                $name = $deal['name'] ?? '';
                $nk = sp_norm_key($name);
                if (strpos($nk, 'monday') === false && strpos($nk, 'tuesday') === false) continue;

                $id = $deal['clover_id'] ?? null;
                if (!$id || isset($existingIds[$id])) continue;

                $full = isset($inj[$id]) && is_array($inj[$id]) ? $inj[$id] : null;
                if (!$full) {
                    $full = sp_try_fetch_clover_item($id);
                    if (!$full) {
                        $price = $deal['price'] ?? '0.00';
                        $cents = intval(round(floatval($price) * 100));
                        $full = [
                            'id'              => $id,
                            'name'            => $name,
                            'description'     => '',
                            'price'           => $price,
                            'price_cents'     => $cents,
                            'available'       => true,
                            'modifier_groups' => [],
                            'sort_order'      => 999
                        ];
                    }
                    $inj[$id] = $full;
                    $injChanged = true;
                }

                $weekly['items'][] = $full;
                $existingIds[$id] = true;
            }
        }

        // If Tuesday is still missing, try to discover it in Clover (often hidden / uncategorized).
        $hasTuesday = false;
        foreach ($weekly['items'] as $it) {
            if (strpos(sp_norm_key($it['name'] ?? ''), 'tuesday') !== false) { $hasTuesday = true; break; }
        }
        if (!$hasTuesday) {
            $wsFile = CACHE_DIR . 'weekday_specials.json';
            $ws = sp_read_json_any($wsFile);
            if (!is_array($ws)) { $ws = []; }

            if (!array_key_exists('tuesday', $ws)) {
                // Discover once
                $found = null;
                $hidden = clover_api('/items?limit=500&expand=modifierGroups&filter=hidden=true');
                $src = (!isset($hidden['error']) && !empty($hidden['elements'])) ? ($hidden['elements'] ?? []) : null;
                if ($src === null) {
                    $all = clover_api('/items?limit=500&expand=modifierGroups');
                    $src = (!isset($all['error']) && !empty($all['elements'])) ? ($all['elements'] ?? []) : [];
                }
                foreach ($src as $ci) {
                    $n = $ci['name'] ?? '';
                    if ($n && stripos($n, 'tuesday') !== false) {
                        $found = ['id' => $ci['id'], 'name' => $n];
                        break;
                    }
                }
                $ws['tuesday'] = $found ? $found : false;
                sp_write_json_any($wsFile, $ws);
            }

            if (!empty($ws['tuesday']) && is_array($ws['tuesday']) && !empty($ws['tuesday']['id'])) {
                $tid = $ws['tuesday']['id'];
                if (!isset($existingIds[$tid])) {
                    $full = isset($inj[$tid]) && is_array($inj[$tid]) ? $inj[$tid] : null;
                    if (!$full) {
                        $full = sp_try_fetch_clover_item($tid);
                        if ($full) {
                            $inj[$tid] = $full;
                            $injChanged = true;
                        }
                    }
                    if ($full) {
                        $weekly['items'][] = $full;
                        $existingIds[$tid] = true;
                    }
                }
            }
        }

        if ($injChanged) {
            sp_write_json_any($injFile, $inj);
        }

        // Re-sort again after injection
        $dayRank = function($name) {
            $k = sp_norm_key($name);
            if (strpos($k, 'monday') !== false) return 1;
            if (strpos($k, 'tuesday') !== false) return 2;
            if (strpos($k, 'wednesday') !== false) return 3;
            if (strpos($k, 'thursday') !== false) return 4;
            if (strpos($k, 'friday') !== false) return 5;
            if (strpos($k, 'saturday') !== false) return 6;
            if (strpos($k, 'sunday') !== false) return 7;
            return 99;
        };
        usort($weekly['items'], function($a, $b) use ($dayRank) {
            $ra = $dayRank($a['name'] ?? '');
            $rb = $dayRank($b['name'] ?? '');
            if ($ra !== $rb) return $ra <=> $rb;
            return sp_norm_key($a['name'] ?? '') <=> sp_norm_key($b['name'] ?? '');
        });
        $weekly['items_count'] = count($weekly['items']);
        unset($weekly);
    }
    unset($cat);

    // ---------- Munchies: merge snack categories into one section ----------
    $munchSrc = ['Meat Snacks', 'Nuts', 'Gum', 'Ice Cream'];
    $mItems = [];
    $mSeen = [];
    $out = [];
    foreach ($cats as $c) {
        $n = $c['name'] ?? '';
        if (in_array($n, $munchSrc, true)) {
            if (!empty($c['items']) && is_array($c['items'])) {
                foreach ($c['items'] as $it) {
                    $id = $it['id'] ?? '';
                    if ($id && isset($mSeen[$id])) continue;
                    if ($id) $mSeen[$id] = true;
                    $mItems[] = $it;
                }
            }
            continue; // hide source categories (they'll appear under Munchies)
        }
        $out[] = $c;
    }

    if (!empty($mItems)) {
        usort($mItems, function($a, $b) {
            $ao = intval($a['sort_order'] ?? 999);
            $bo = intval($b['sort_order'] ?? 999);
            if ($ao !== $bo) return $ao <=> $bo;
            return sp_norm_key($a['name'] ?? '') <=> sp_norm_key($b['name'] ?? '');
        });

        // $out[] = [
        //     'id'          => 'sp_munchies',
        //     'name'        => 'Munchies',
        //     'sort_order'  => 9999,
        //     'items'       => array_values($mItems),
        //     'items_count' => count($mItems)
        // ];
    }

    $cats = $out;
}

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
    'Chicken Fingers', // not in clover 
    'Chicken Wings',
    'Garlic Fingers',
    'Chicken Bites',
    'Crispy Chicken',
    'Salads',
    'Sides',
    "Dessert's",
    // Convenience store items (requested)
    'Candy',
    'Energy Drinks',
    'Beverages',
    'Chocolate',
    'Chips',
    'Soda',
    'Sports Drink',
    'Dip Sauces',
    "Munchies",
    // Munchies (snacks) — merged into one category by overrides
    // 'Meat Snacks',
    // 'Nuts',
    // 'Gum',
    // 'Ice Cream'
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

        // Backfill `image_name` for older cache files (must happen before overrides mutate `name`)
        sp_ensure_image_name($cats);

        // Apply display/order overrides on cached data
        sp_apply_menu_overrides($cats);
        sp_apply_category_display_names($cats);
        
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
            'image_name'      => $item['name'],
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

// Apply display/order overrides on response data
sp_ensure_image_name($menuCategories);
sp_apply_menu_overrides($menuCategories);
sp_apply_category_display_names($menuCategories);

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
