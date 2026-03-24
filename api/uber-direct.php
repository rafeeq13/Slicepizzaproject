<?php
/**
 * Slice+ Uber Direct Integration
 * Handles: OAuth token, delivery quotes, delivery creation, status updates
 */

// Uber Direct Credentials
define('UBER_CUSTOMER_ID', '5ff60942-de97-4244-bbfa-9ecd7b5f5e49');
define('UBER_CLIENT_ID', 'IZpypHitGETNpMAV1b_IVWDxepVisxgI');
define('UBER_CLIENT_SECRET', 'Vkjach0BHAx-dORjhv00ABy36zc48V-QDIuUVTp2');

define('UBER_AUTH_URL', 'https://login.uber.com/oauth/v2/token');
define('UBER_API_BASE', 'https://api.uber.com/v1/customers/' . UBER_CUSTOMER_ID);

// Store pickup location
define('STORE_LAT', 44.6454);
define('STORE_LNG', -63.5974);
define('STORE_ADDRESS', '6169 Quinpool Rd #111');
define('STORE_CITY', 'Halifax');
define('STORE_STATE', 'NS');
define('STORE_ZIP', 'B3L 4P8');
define('STORE_COUNTRY', 'CA');
define('STORE_PHONE_NUM', '+19028004001');
define('STORE_BUSINESS_NAME', 'Slice+ Convenience & Pizzeria');

// Token cache file
define('UBER_TOKEN_FILE', __DIR__ . '/../cache/uber_token.json');

/**
 * Get OAuth2 access token (cached)
 */
function uber_get_token() {
    // Check cached token
    if (file_exists(UBER_TOKEN_FILE)) {
        $cached = json_decode(file_get_contents(UBER_TOKEN_FILE), true);
        if ($cached && isset($cached['expires_at']) && time() < $cached['expires_at'] - 60) {
            return $cached['access_token'];
        }
    }
    
    // Request new token
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => UBER_AUTH_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(array(
            'client_id' => UBER_CLIENT_ID,
            'client_secret' => UBER_CLIENT_SECRET,
            'grant_type' => 'client_credentials',
            'scope' => 'eats.deliveries'
        )),
        CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded'),
        CURLOPT_TIMEOUT => 15
    ));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('Uber Direct OAuth error: ' . $error);
        return null;
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode !== 200 || !isset($data['access_token'])) {
        error_log('Uber Direct OAuth failed: ' . $response);
        return null;
    }
    
    // Cache token
    $tokenData = array(
        'access_token' => $data['access_token'],
        'expires_at' => time() + intval($data['expires_in'] ?? 2700)
    );
    
    $cacheDir = dirname(UBER_TOKEN_FILE);
    if (!is_dir($cacheDir)) { mkdir($cacheDir, 0755, true); }
    file_put_contents(UBER_TOKEN_FILE, json_encode($tokenData));
    
    return $data['access_token'];
}

/**
 * Make Uber Direct API request
 */
function uber_api($endpoint, $method = 'GET', $data = null) {
    $token = uber_get_token();
    if (!$token) {
        return array('error' => true, 'message' => 'Failed to get Uber auth token');
    }
    
    $url = UBER_API_BASE . $endpoint;
    $ch = curl_init();
    
    $opts = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: application/json'
        )
    );
    
    if ($method === 'POST') {
        $opts[CURLOPT_POST] = true;
        if ($data) { $opts[CURLOPT_POSTFIELDS] = json_encode($data); }
    }
    
    curl_setopt_array($ch, $opts);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('Uber Direct API error: ' . $error);
        return array('error' => true, 'message' => $error);
    }
    
    $decoded = json_decode($response, true);
    
    if ($httpCode >= 400) {
        $msg = 'HTTP ' . $httpCode;
        if (isset($decoded['message'])) { $msg = $decoded['message']; }
        elseif (isset($decoded['error'])) { $msg = is_string($decoded['error']) ? $decoded['error'] : json_encode($decoded['error']); }
        error_log('Uber Direct API ' . $httpCode . ': ' . $response);
        return array('error' => true, 'message' => $msg, 'code' => $httpCode);
    }
    
    return $decoded ? $decoded : array();
}

/**
 * Get delivery quote (estimated cost & time)
 */
function uber_get_quote($dropoff_address, $dropoff_lat = null, $dropoff_lng = null) {
    $data = array(
        'pickup_address' => json_encode(array(
            'street_address' => array(STORE_ADDRESS),
            'city' => STORE_CITY,
            'state' => STORE_STATE,
            'zip_code' => STORE_ZIP,
            'country' => STORE_COUNTRY
        )),
        'dropoff_address' => $dropoff_address
    );
    
    if ($dropoff_lat && $dropoff_lng) {
        $data['dropoff_latitude'] = $dropoff_lat;
        $data['dropoff_longitude'] = $dropoff_lng;
    }
    
    return uber_api('/delivery_quotes', 'POST', $data);
}

/**
 * Create a delivery request
 */
function uber_create_delivery($orderData) {
    $customer = $orderData['customer'];
    $orderNum = $orderData['number'];
    
    // Build dropoff address
    $dropoffAddress = trim(
        ($customer['address'] ?? '') . ', ' .
        ($customer['city'] ?? 'Halifax') . ', NS ' .
        ($customer['postal'] ?? '')
    );
    
    $payload = array(
        'pickup_name' => STORE_BUSINESS_NAME,
        'pickup_address' => STORE_ADDRESS . ', ' . STORE_CITY . ', ' . STORE_STATE . ' ' . STORE_ZIP,
        'pickup_phone_number' => STORE_PHONE_NUM,
        'pickup_business_name' => STORE_BUSINESS_NAME,
        'pickup_notes' => 'Order #' . $orderNum . ' — ready for pickup at front counter.',
        
        'dropoff_name' => $customer['name'] ?? 'Customer',
        'dropoff_address' => $dropoffAddress,
        'dropoff_phone_number' => format_phone($customer['phone'] ?? ''),
        'dropoff_notes' => 'Order #' . $orderNum,
        
        'manifest_total_value' => intval(floatval(str_replace('$', '', $orderData['total'] ?? '0')) * 100),
        'manifest_reference' => 'SP-' . $orderNum,
        'manifest_items' => array(),
        
        'external_id' => 'SP-' . $orderNum,
        'external_store_id' => 'sliceplus-quinpool'
    );
    
    // Add items to manifest
    if (!empty($orderData['items'])) {
        foreach ($orderData['items'] as $item) {
            $payload['manifest_items'][] = array(
                'name' => $item['name'] ?? 'Item',
                'quantity' => intval($item['quantity'] ?? 1),
                'size' => 'small'
            );
        }
    }
    
    error_log('Uber Direct creating delivery for order #' . $orderNum . ' to ' . $dropoffAddress);
    
    $result = uber_api('/deliveries', 'POST', $payload);
    
    if (isset($result['error'])) {
        error_log('Uber Direct delivery creation failed: ' . json_encode($result));
    } else {
        error_log('Uber Direct delivery created: ' . ($result['id'] ?? 'unknown'));
    }
    
    return $result;
}

/**
 * Get delivery status
 */
function uber_get_delivery($delivery_id) {
    return uber_api('/deliveries/' . $delivery_id, 'GET');
}

/**
 * Cancel a delivery
 */
function uber_cancel_delivery($delivery_id) {
    return uber_api('/deliveries/' . $delivery_id . '/cancel', 'POST');
}

/**
 * Map Uber status to our tracking status
 */
function uber_status_to_track($uber_status) {
    $map = array(
        'pending' => 'placed',
        'pickup' => 'preparing',
        'pickup_complete' => 'out_for_delivery',
        'dropoff' => 'out_for_delivery',
        'delivered' => 'delivered',
        'canceled' => 'cancelled',
        'returned' => 'cancelled'
    );
    return isset($map[$uber_status]) ? $map[$uber_status] : 'preparing';
}

/**
 * Format phone number for Uber (needs +1)
 */
function format_phone($phone) {
    $digits = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($digits) === 10) {
        return '+1' . $digits;
    } elseif (strlen($digits) === 11 && $digits[0] === '1') {
        return '+' . $digits;
    }
    return '+1' . $digits;
}