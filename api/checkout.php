<?php
/**
 * Slice+ Checkout API — Clover Payment + Uber Direct Delivery + Tracking + Emails
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/uber-direct.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method not allowed', 405); }

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { json_error('Invalid request body', 400); }

$cart       = $input['cart'];
$customer   = $input['customer'];
$orderType  = $input['order_type'];
$payToken   = $input['payment_token'];
$tipCents   = intval($input['tip_amount'] ?? 0);

// VALIDATE
$errors = array();
if (empty($cart)) { $errors[] = 'Cart is empty'; }
if (empty($customer['name'])) { $errors[] = 'Name is required'; }
if (empty($customer['phone'])) { $errors[] = 'Phone is required'; }
if (empty($customer['email'])) { $errors[] = 'Email is required'; }
if ($orderType === 'delivery' && empty($customer['address'])) { $errors[] = 'Address is required'; }
if (empty($payToken)) { $errors[] = 'Payment token is missing'; }
if (!empty($errors)) { json_response(array('error' => true, 'messages' => $errors), 400); }

// ORDER API BASE
if (LIVE_MODE) {
    $orderApiBase = 'https://api.clover.com/v3/merchants/RT6ST4S4M0951';
    $orderApiToken = CLOVER_API_TOKEN;
} else {
    $orderApiBase = 'https://sandbox.dev.clover.com/v3/merchants/M155T7XWW06S1';
    $orderApiToken = '70f37817-6f7c-05a6-6bcd-37b26b443c34';
}

function order_api($base, $token, $endpoint, $method = 'GET', $data = null) {
    $url = $base . $endpoint;
    $ch = curl_init();
    $opts = array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
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
    if ($error) { return array('error' => $error); }
    if ($httpCode >= 400) {
        $d = json_decode($response, true);
        return array('error' => isset($d['message']) ? $d['message'] : 'HTTP ' . $httpCode);
    }
    return json_decode($response, true);
}

// CALCULATE TOTALS
$subtotalCents = 0;
$lineItems = array();
$emailItems = array();
$trackItems = array();

foreach ($cart as $cartItem) {
    $itemPrice = intval($cartItem['price_cents']);
    $qty = max(1, intval($cartItem['quantity']));
    $modsCents = 0;
    $modNames = array();
    if (!empty($cartItem['modifiers'])) {
        foreach ($cartItem['modifiers'] as $mod) {
            $modsCents += intval($mod['price_cents']);
            $modNames[] = $mod['name'];
        }
    }
    $lineTotal = ($itemPrice + $modsCents) * $qty;
    $subtotalCents += $lineTotal;
    
    $itemName = $cartItem['name'];
    if (!empty($modNames)) {
        $itemName .= ' (' . implode(', ', $modNames) . ')';
    }
    
    $lineItems[] = array(
        'name' => $cartItem['name'],
        'price_cents' => $itemPrice + $modsCents,
        'quantity' => $qty,
        'notes' => isset($cartItem['notes']) ? $cartItem['notes'] : ''
    );
    
    $emailItems[] = array(
        'name' => $itemName,
        'quantity' => $qty,
        'price' => format_price($lineTotal)
    );
    
    $trackItems[] = array(
        'name' => $cartItem['name'],
        'quantity' => $qty,
        'total' => $lineTotal,
        'modifiers' => !empty($cartItem['modifiers']) ? $cartItem['modifiers'] : array()
    );
}

$taxCents = round($subtotalCents * TAX_RATE);
$deliveryFeeCents = ($orderType === 'delivery') ? DELIVERY_FEE : 0;
$totalCents = $subtotalCents + $taxCents + $deliveryFeeCents + $tipCents;

// CREATE ORDER
$note = 'WEBSITE ORDER | ' . strtoupper($orderType) . ' | ' . $customer['name'] . ' | ' . $customer['phone'];
if ($orderType === 'delivery' && !empty($customer['address'])) {
    $note .= ' | ' . $customer['address'];
    if (!empty($customer['city'])) { $note .= ', ' . $customer['city']; }
    if (!empty($customer['postal'])) { $note .= ' ' . $customer['postal']; }
}

$order = order_api($orderApiBase, $orderApiToken, '/orders', 'POST', array(
    'state' => 'open', 'note' => $note, 'total' => $totalCents
));

if (isset($order['error'])) {
    json_response(array('error' => true, 'message' => 'Failed to create order.', 'debug' => $order), 500);
}

$orderId = isset($order['id']) ? $order['id'] : null;
if (!$orderId) { json_error('Order creation failed', 500); }

// ADD LINE ITEMS
foreach ($lineItems as $line) {
    $payload = array('name' => $line['name'], 'price' => $line['price_cents'], 'unitQty' => $line['quantity'] * 1000);
    if (!empty($line['notes'])) { $payload['note'] = $line['notes']; }
    order_api($orderApiBase, $orderApiToken, '/orders/' . $orderId . '/line_items', 'POST', $payload);
}
if ($deliveryFeeCents > 0) {
    order_api($orderApiBase, $orderApiToken, '/orders/' . $orderId . '/line_items', 'POST', array(
        'name' => 'Delivery Fee', 'price' => $deliveryFeeCents, 'unitQty' => 1000
    ));
}

// PROCESS PAYMENT
$charge = clover_ecom('/v1/charges', array(
    'source' => $payToken,
    'amount' => $totalCents,
        'currency' => CURRENCY,
        'tip_amount' => $tipCents,
        'tax_amount' => $taxCents
));

if (isset($charge['error'])) {
    order_api($orderApiBase, $orderApiToken, '/orders/' . $orderId, 'POST', array('state' => 'cancelled'));
    $msg = isset($charge['message']) ? $charge['message'] : 'Payment declined.';
    
    $cancelData = array('number' => strtoupper(substr($orderId, -6)), 'total' => format_price($totalCents));
    send_order_email('cancelled', $cancelData, $customer['email']);
    
    json_response(array('error' => true, 'message' => $msg, 'debug' => LIVE_MODE ? null : $charge), 402);
}

// MARK PAID
order_api($orderApiBase, $orderApiToken, '/orders/' . $orderId, 'POST', array(
    'state' => 'locked', 'paymentState' => 'PAID'
));

$num = strtoupper(substr($orderId, -6));
order_api($orderApiBase, $orderApiToken, '/orders/' . $orderId, 'POST', array(
    'note' => 'WEBSITE ORDER #' . $num . ' | ' . $note
));
$trackingUrl = 'https://sliceplus.ca/track.html?order=' . $num;

// ====== SAVE ORDER FOR TRACKING ======
$ordersDir = __DIR__ . '/../cache/orders/';
if (!is_dir($ordersDir)) { mkdir($ordersDir, 0755, true); }

$trackingData = array(
    'number' => $num,
    'clover_id' => $orderId,
    'type' => $orderType,
    'status' => 'placed',
    'status_history' => array(
        array('status' => 'placed', 'time' => date('c'), 'note' => 'Order placed and paid')
    ),
    'customer' => array(
        'name' => $customer['name'],
        'phone' => $customer['phone'],
        'email' => $customer['email'],
        'address' => isset($customer['address']) ? $customer['address'] : '',
        'city' => isset($customer['city']) ? $customer['city'] : '',
        'postal' => isset($customer['postal']) ? $customer['postal'] : ''
    ),
    'items' => $trackItems,
    'subtotal' => format_price($subtotalCents),
    'tax' => format_price($taxCents),
    'delivery_fee' => format_price($deliveryFeeCents),
        'tip' => format_price($tipCents),
        'total' => format_price($totalCents),
    'payment' => array(
        'status' => 'paid',
        'last4' => isset($charge['source']['last4']) ? $charge['source']['last4'] : null,
        'brand' => isset($charge['source']['brand']) ? $charge['source']['brand'] : null
    ),
    'tracking_url' => $trackingUrl,
    'uber_delivery' => null,
    'placed_at' => date('c'),
    'updated_at' => date('c'),
    'estimated_time' => $orderType === 'delivery' ? '30-45 min' : '20-30 min'
);

// ====== UBER DIRECT — AUTO DISPATCH FOR DELIVERY ORDERS ======
if ($orderType === 'delivery') {
    error_log('Slice+ Order #' . $num . ' — Dispatching Uber Direct rider...');
    
    $uberOrder = array(
        'number' => $num,
        'customer' => $customer,
        'items' => $emailItems,
        'total' => format_price($totalCents)
    );
    
    $uberResult = uber_create_delivery($uberOrder);
    
    if (isset($uberResult['error'])) {
        // Uber failed — order still goes through, just no auto-delivery
        error_log('Slice+ Order #' . $num . ' — Uber Direct FAILED: ' . json_encode($uberResult));
        $trackingData['uber_delivery'] = array(
            'status' => 'failed',
            'error' => $uberResult['message'] ?? 'Unknown error',
            'time' => date('c')
        );
    } else {
        // Uber delivery created successfully
        error_log('Slice+ Order #' . $num . ' — Uber Direct SUCCESS: Delivery ID ' . ($uberResult['id'] ?? 'unknown'));
        $trackingData['uber_delivery'] = array(
            'id' => $uberResult['id'] ?? null,
            'status' => $uberResult['status'] ?? 'pending',
            'tracking_url' => $uberResult['tracking_url'] ?? null,
            'pickup_eta' => $uberResult['pickup']['eta'] ?? null,
            'dropoff_eta' => $uberResult['dropoff']['eta'] ?? null,
            'fee' => $uberResult['fee'] ?? null,
            'courier' => null,
            'time' => date('c')
        );
        
        // Update estimated time with Uber ETA if available
        if (!empty($uberResult['dropoff']['eta'])) {
            $etaTime = strtotime($uberResult['dropoff']['eta']);
            if ($etaTime) {
                $minLeft = round(($etaTime - time()) / 60);
                if ($minLeft > 0) {
                    $trackingData['estimated_time'] = $minLeft . ' min';
                }
            }
        }
        
        // Update status to preparing
        $trackingData['status'] = 'preparing';
        $trackingData['status_history'][] = array(
            'status' => 'preparing',
            'time' => date('c'),
            'note' => 'Uber Direct rider dispatched'
        );
    }
}

// Save tracking file
file_put_contents($ordersDir . $num . '.json', json_encode($trackingData, JSON_PRETTY_PRINT));
error_log('Slice+ Tracking saved: ' . $ordersDir . $num . '.json');

// BUILD ORDER DATA
$orderResponse = array(
    'id' => $orderId,
    'number' => $num,
    'type' => $orderType,
    'subtotal' => format_price($subtotalCents),
    'tax' => format_price($taxCents),
    'delivery_fee' => format_price($deliveryFeeCents),
        'tip' => format_price($tipCents),
        'total' => format_price($totalCents),
    'items' => $emailItems,
    'customer' => array(
        'name' => $customer['name'],
        'phone' => $customer['phone'],
        'email' => $customer['email'],
        'address' => isset($customer['address']) ? $customer['address'] : ''
    ),
    'payment' => array(
        'status' => 'paid',
        'last4' => isset($charge['source']['last4']) ? $charge['source']['last4'] : null,
        'brand' => isset($charge['source']['brand']) ? $charge['source']['brand'] : null
    ),
    'tracking_url' => $trackingUrl
);

// EMAIL 1: Customer confirmation
send_order_email('confirmation', $orderResponse, $customer['email']);

// EMAIL 2: Store owner notification
send_order_email('new_order', $orderResponse, 'contact.restorise@gmail.com', 'Sliceplushfx@gmail.com');

// Log
error_log('Slice+ Order #' . $num . ' | Type: ' . $orderType . ' | Customer: ' . $customer['email'] . ' | Track: ' . $trackingUrl);

// RETURN CONFIRMATION
json_response(array(
    'success' => true,
    'order' => $orderResponse,
    'message' => $orderType === 'delivery'
        ? 'Order #' . $num . ' placed! Rider is being dispatched.'
        : 'Order #' . $num . ' placed! Pickup will be ready soon.'
));