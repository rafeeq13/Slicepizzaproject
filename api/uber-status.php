<?php
/**
 * Slice+ Uber Direct Status Updater
 * 
 * 1. Webhook: POST /api/uber-webhook.php (Uber calls this)
 * 2. Poll:    GET  /api/uber-status.php?order=SP-1234 (frontend calls this)
 * 
 * This file handles BOTH webhook and polling
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/uber-direct.php';
require_once __DIR__ . '/email.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$ordersDir = __DIR__ . '/../cache/orders/';

// ====== GET — Poll Uber for latest status ======
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $orderNum = trim($_GET['order'] ?? '');
    if (empty($orderNum)) {
        echo json_encode(array('success' => false, 'message' => 'Order number required'));
        exit;
    }
    
    $orderNum = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace('#', '', $orderNum));
    $file = $ordersDir . $orderNum . '.json';
    
    if (!file_exists($file)) {
        echo json_encode(array('success' => false, 'message' => 'Order not found'));
        exit;
    }
    
    $data = json_decode(file_get_contents($file), true);
    
    // If order has Uber delivery, poll Uber for latest status
    if (!empty($data['uber_delivery']['id']) && 
        !in_array($data['status'], array('delivered', 'cancelled'))) {
        
        $uberStatus = uber_get_delivery($data['uber_delivery']['id']);
        
        if (!isset($uberStatus['error'])) {
            $newStatus = uber_status_to_track($uberStatus['status'] ?? '');
            
            // Update if status changed
            if ($newStatus !== $data['status']) {
                $data['status'] = $newStatus;
                $data['updated_at'] = date('c');
                $data['status_history'][] = array(
                    'status' => $newStatus,
                    'time' => date('c'),
                    'note' => 'Uber: ' . ucfirst(str_replace('_', ' ', $uberStatus['status'] ?? ''))
                );
                
                // Update courier info
                if (!empty($uberStatus['courier'])) {
                    $data['uber_delivery']['courier'] = array(
                        'name' => $uberStatus['courier']['name'] ?? '',
                        'phone' => $uberStatus['courier']['phone_number'] ?? '',
                        'vehicle' => $uberStatus['courier']['vehicle_type'] ?? '',
                        'location' => $uberStatus['courier']['location'] ?? null
                    );
                }
                
                // Update ETA
                if (!empty($uberStatus['dropoff']['eta'])) {
                    $etaTime = strtotime($uberStatus['dropoff']['eta']);
                    if ($etaTime) {
                        $minLeft = max(1, round(($etaTime - time()) / 60));
                        $data['estimated_time'] = $minLeft . ' min';
                    }
                }
                
                // Update Uber tracking URL
                if (!empty($uberStatus['tracking_url'])) {
                    $data['uber_delivery']['tracking_url'] = $uberStatus['tracking_url'];
                }
                
                // Update Uber delivery status
                $data['uber_delivery']['status'] = $uberStatus['status'] ?? $data['uber_delivery']['status'];
                
                // Save updated data
                file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
                
                // Send delivered email if completed
                if ($newStatus === 'delivered') {
                    $emailData = array(
                        'number' => $data['number'],
                        'total' => $data['total']
                    );
                    send_order_email('delivered', $emailData, $data['customer']['email']);
                    error_log('Slice+ Order #' . $orderNum . ' — DELIVERED via Uber Direct');
                }
            }
        }
    }
    
    echo json_encode(array('success' => true, 'order' => $data));
    exit;
}

// ====== POST — Uber Webhook (Uber calls this when status changes) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    error_log('Uber webhook received: ' . $body);
    
    $payload = json_decode($body, true);
    if (!$payload) {
        http_response_code(400);
        echo json_encode(array('error' => 'Invalid payload'));
        exit;
    }
    
    $eventType = $payload['event_type'] ?? '';
    $deliveryId = $payload['data']['id'] ?? ($payload['delivery_id'] ?? '');
    $externalId = $payload['data']['external_id'] ?? '';
    
    // Find order by external_id (SP-XXXXXX) or delivery_id
    $orderFile = null;
    $orderData = null;
    
    // Try external_id first
    if (!empty($externalId)) {
        $num = str_replace('SP-', '', $externalId);
        $f = $ordersDir . $num . '.json';
        if (file_exists($f)) {
            $orderFile = $f;
            $orderData = json_decode(file_get_contents($f), true);
        }
    }
    
    // Fallback: search by delivery_id
    if (!$orderFile && !empty($deliveryId)) {
        $files = glob($ordersDir . '*.json');
        foreach ($files as $f) {
            $d = json_decode(file_get_contents($f), true);
            if (isset($d['uber_delivery']['id']) && $d['uber_delivery']['id'] === $deliveryId) {
                $orderFile = $f;
                $orderData = $d;
                break;
            }
        }
    }
    
    if (!$orderFile || !$orderData) {
        error_log('Uber webhook: Order not found for delivery ' . $deliveryId);
        http_response_code(200); // Still return 200 to prevent retries
        echo json_encode(array('message' => 'Order not found'));
        exit;
    }
    
    // Update status
    $uberStatus = $payload['data']['status'] ?? '';
    $newStatus = uber_status_to_track($uberStatus);
    
    if ($newStatus !== $orderData['status']) {
        $orderData['status'] = $newStatus;
        $orderData['updated_at'] = date('c');
        $orderData['status_history'][] = array(
            'status' => $newStatus,
            'time' => date('c'),
            'note' => 'Uber webhook: ' . ucfirst(str_replace('_', ' ', $uberStatus))
        );
        
        // Update courier info
        if (!empty($payload['data']['courier'])) {
            $orderData['uber_delivery']['courier'] = array(
                'name' => $payload['data']['courier']['name'] ?? '',
                'phone' => $payload['data']['courier']['phone_number'] ?? '',
                'vehicle' => $payload['data']['courier']['vehicle_type'] ?? ''
            );
        }
        
        $orderData['uber_delivery']['status'] = $uberStatus;
        
        file_put_contents($orderFile, json_encode($orderData, JSON_PRETTY_PRINT));
        error_log('Uber webhook: Order #' . $orderData['number'] . ' updated to ' . $newStatus);
        
        // Send delivered email
        if ($newStatus === 'delivered') {
            $emailData = array(
                'number' => $orderData['number'],
                'total' => $orderData['total']
            );
            send_order_email('delivered', $emailData, $orderData['customer']['email']);
        }
    }
    
    http_response_code(200);
    echo json_encode(array('success' => true));
    exit;
}

echo json_encode(array('error' => 'Invalid request'));