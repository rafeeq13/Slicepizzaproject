<?php
/**
 * Slice+ Order Status Email Trigger
 * Admin endpoint to send delivery/cancellation notifications
 * 
 * POST /api/order-status.php
 * Body: { "key": "sliceplus2025sync", "type": "delivered|cancelled", "order_number": "KCHHKP", "email": "customer@email.com", "total": "19.54" }
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/email.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error('Method not allowed', 405); }

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) { json_error('Invalid request', 400); }

// Security check
if (empty($input['key']) || $input['key'] !== SYNC_KEY) {
    json_error('Unauthorized', 401);
}

$type = isset($input['type']) ? $input['type'] : '';
$email = isset($input['email']) ? $input['email'] : '';
$orderNum = isset($input['order_number']) ? $input['order_number'] : '';
$total = isset($input['total']) ? $input['total'] : '0.00';

if (!in_array($type, array('delivered', 'cancelled'))) {
    json_error('Invalid type. Use: delivered or cancelled', 400);
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Valid email required', 400);
}
if (empty($orderNum)) {
    json_error('Order number required', 400);
}

$orderData = array(
    'number' => $orderNum,
    'total' => $total,
    'type' => isset($input['order_type']) ? $input['order_type'] : 'pickup'
);

$sent = send_order_email($type, $orderData, $email);

json_response(array(
    'success' => $sent ? true : false,
    'message' => $sent ? 'Email sent to ' . $email : 'Failed to send email',
    'type' => $type,
    'order' => $orderNum
));