<?php
// Slice+ Clover Configuration
// PAYMENT MODE: false = sandbox testing, true = real payments
define('LIVE_MODE', true);
// PRODUCTION - Menu & Inventory (always used)
define('CLOVER_MERCHANT_ID', 'RT6ST4S4M0951');
define('CLOVER_API_TOKEN', '967ac4d3-5b7b-b6bf-4f4a-c27eb92f8f7e');
define('CLOVER_API_BASE', 'https://api.clover.com/v3/merchants/RT6ST4S4M0951');
// SANDBOX eCommerce tokens (backup)
define('SANDBOX_PUBLIC_TOKEN', 'cc45affdc4338ebe181f473e4fafb783');
define('SANDBOX_PRIVATE_TOKEN', 'c0919121-bd7d-4570-c609-615315a94487');
// PAYMENT CONFIG (auto-selected)
if (LIVE_MODE) {
    define('ECOM_PUBLIC_TOKEN', '7aea24016f6d42913610807c24d130f0');
    define('ECOM_PRIVATE_TOKEN', '056139b5-055c-8f60-f7b4-e3a31d612ccb');
    define('PAY_ECOM_BASE', 'https://scl.clover.com');
    define('CHECKOUT_JS_URL', 'https://checkout.clover.com/sdk.js');
} else {
    define('ECOM_PUBLIC_TOKEN', 'cc45affdc4338ebe181f473e4fafb783');
    define('ECOM_PRIVATE_TOKEN', 'c0919121-bd7d-4570-c609-615315a94487');
    define('PAY_ECOM_BASE', 'https://scl-sandbox.dev.clover.com');
    define('CHECKOUT_JS_URL', 'https://checkout.sandbox.dev.clover.com/sdk.js');
}
// APP
define('CLOVER_APP_ID', 'K84NGKCRGJN5W');
define('CLOVER_APP_SECRET', 'be0cafd1-d6ae-db52-df5a-3375f2286552');
// STORE
define('TAX_RATE', 0.14);
define('DELIVERY_FEE', 399);
define('CURRENCY', 'cad');
define('SYNC_KEY', 'sliceplus2025sync');
// CACHE
define('CACHE_DIR', __DIR__ . '/../cache/');
define('CACHE_MENU_FILE', CACHE_DIR . 'menu_cache.json');
define('CACHE_CATEGORIES_FILE', CACHE_DIR . 'categories_cache.json');
define('CACHE_DEALS_FILE', CACHE_DIR . 'deals_map.json');
define('CACHE_TTL', 900);
function clover_api($endpoint, $method = 'GET', $data = null) {
    $url = CLOVER_API_BASE . $endpoint;
    $ch = curl_init();
    $opts = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . CLOVER_API_TOKEN,
            'Content-Type: application/json',
            'Accept: application/json'
        )
    );
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($data) { $opts[CURLOPT_POSTFIELDS] = json_encode($data); }
    } elseif ($method === 'PUT') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
        if ($data) { $opts[CURLOPT_POSTFIELDS] = json_encode($data); }
    } elseif ($method === 'DELETE') {
        $opts[CURLOPT_CUSTOMREQUEST] = 'DELETE';
    }
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) { return array('error' => $error); }
    if ($httpCode >= 400) {
        $d = json_decode($response, true);
        return array('error' => isset($d['message']) ? $d['message'] : 'HTTP ' . $httpCode, 'code' => $httpCode);
    }
    $result = json_decode($response, true);
    return $result ? $result : array();
}
function clover_ecom($endpoint, $data) {
    $url = PAY_ECOM_BASE . $endpoint;
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . ECOM_PRIVATE_TOKEN,
            'Content-Type: application/json',
            'Accept: application/json'
        )
    ));
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) { return array('error' => $error); }
    $decoded = json_decode($response, true);
    if ($httpCode >= 400) {
        $msg = 'HTTP ' . $httpCode;
        if (isset($decoded['error']['message'])) { $msg = $decoded['error']['message']; }
        elseif (isset($decoded['message'])) { $msg = $decoded['message']; }
        return array('error' => true, 'message' => $msg, 'code' => $httpCode);
    }
    return $decoded ? $decoded : array();
}
function cache_read($file) {
    if (!file_exists($file)) { return null; }
    if ((time() - filemtime($file)) > CACHE_TTL) { return null; }
    $data = json_decode(file_get_contents($file), true);
    return $data ? $data : null;
}
function cache_write($file, $data) {
    if (!is_dir(dirname($file))) { mkdir(dirname($file), 0755, true); }
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}
function json_error($message, $code = 500) {
    json_response(array('error' => true, 'message' => $message), $code);
}
function format_price($cents) {
    return number_format($cents / 100, 2, '.', '');
}