<?php
/**
 * Slice+ Order Tracking API
 * 
 * GET:  /api/track.php?order=SP-1234           → Get order status
 * POST: /api/track.php  {key, order, status}   → Update order status
 * 
 * Statuses: placed, preparing, out_for_delivery, delivered, cancelled
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ORDERS_DIR = __DIR__ . '/../cache/orders/';
$UPDATE_KEY = 'sliceplus2025sync'; // Same key used for sync

// Ensure orders directory exists
if (!is_dir($ORDERS_DIR)) {
    mkdir($ORDERS_DIR, 0755, true);
}

// GET — Retrieve order status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $orderNum = trim($_GET['order'] ?? '');
    
    if (empty($orderNum)) {
        echo json_encode(['success' => false, 'message' => 'Order number required']);
        exit;
    }
    
    // Clean order number (remove # if present)
    $orderNum = str_replace('#', '', $orderNum);
    $orderNum = preg_replace('/[^A-Za-z0-9\-]/', '', $orderNum);
    
    $file = $ORDERS_DIR . $orderNum . '.json';
    
    if (!file_exists($file)) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    $data = json_decode(file_get_contents($file), true);
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Order data error']);
        exit;
    }
    
    echo json_encode(['success' => true, 'order' => $data]);
    exit;
}

// POST — Update order status (or save new order)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Save new order (called from checkout.php)
    if (isset($input['action']) && $input['action'] === 'save_order') {
        $order = $input['order'] ?? null;
        if (!$order || empty($order['number'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid order data']);
            exit;
        }
        
        $orderData = [
            'number' => $order['number'],
            'type' => $order['type'] ?? 'pickup',
            'status' => 'placed',
            'status_history' => [
                ['status' => 'placed', 'time' => date('c'), 'note' => 'Order placed and paid']
            ],
            'customer' => [
                'name' => $order['customer']['name'] ?? '',
                'phone' => $order['customer']['phone'] ?? '',
                'email' => $order['customer']['email'] ?? '',
                'address' => $order['customer']['address'] ?? '',
                'city' => $order['customer']['city'] ?? '',
                'postal' => $order['customer']['postal'] ?? ''
            ],
            'items' => $order['items'] ?? [],
            'subtotal' => $order['subtotal'] ?? '0.00',
            'tax' => $order['tax'] ?? '0.00',
            'delivery_fee' => $order['delivery_fee'] ?? '0.00',
            'total' => $order['total'] ?? '0.00',
            'payment' => $order['payment'] ?? null,
            'placed_at' => date('c'),
            'updated_at' => date('c'),
            'estimated_time' => $order['type'] === 'delivery' ? '30-45 min' : '20-30 min'
        ];
        
        $num = preg_replace('/[^A-Za-z0-9\-]/', '', $order['number']);
        file_put_contents($ORDERS_DIR . $num . '.json', json_encode($orderData, JSON_PRETTY_PRINT));
        
        echo json_encode(['success' => true, 'message' => 'Order saved']);
        exit;
    }
    
    // Update status (called manually or from delivery system)
    $key = $input['key'] ?? '';
    $orderNum = $input['order'] ?? '';
    $newStatus = $input['status'] ?? '';
    $note = $input['note'] ?? '';
    
    if ($key !== $UPDATE_KEY) {
        echo json_encode(['success' => false, 'message' => 'Invalid key']);
        exit;
    }
    
    if (empty($orderNum) || empty($newStatus)) {
        echo json_encode(['success' => false, 'message' => 'Order number and status required']);
        exit;
    }
    
    $validStatuses = ['placed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status. Use: ' . implode(', ', $validStatuses)]);
        exit;
    }
    
    $orderNum = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace('#', '', $orderNum));
    $file = $ORDERS_DIR . $orderNum . '.json';
    
    if (!file_exists($file)) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
    
    $data = json_decode(file_get_contents($file), true);
    $data['status'] = $newStatus;
    $data['updated_at'] = date('c');
    $data['status_history'][] = [
        'status' => $newStatus,
        'time' => date('c'),
        'note' => $note ?: ucfirst(str_replace('_', ' ', $newStatus))
    ];
    
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true, 'message' => 'Status updated to ' . $newStatus, 'order' => $data]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);