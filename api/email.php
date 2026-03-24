<?php
/**
 * Slice+ Email System
 * Customer emails + Store owner notifications
 */

define('STORE_LOGO', 'https://sliceplus.ca/assets/images/logo-white.png');
define('STORE_NAME', 'Slice+ Convenience & Pizzeria');
define('STORE_PHONE', '(902) 800-4001');
define('STORE_ADDRESS', '6169 Quinpool Rd #111, Halifax, NS');
define('STORE_WEBSITE', 'sliceplus.ca');

function send_order_email($type, $orderData, $toEmail, $bccEmail = '') {
    $subject = '';
    $body = '';
    
    switch ($type) {
        case 'confirmation':
            $subject = 'Order #' . $orderData['number'] . ' Confirmed - ' . STORE_NAME;
            $body = build_customer_confirmation($orderData);
            break;
        case 'new_order':
            $custName = isset($orderData['customer']['name']) ? $orderData['customer']['name'] : 'Customer';
            $subject = 'New Order #' . $orderData['number'] . ' from ' . $custName . ' - ' . STORE_NAME;
            $body = build_owner_notification($orderData);
            break;
        case 'cancelled':
            $subject = 'Order #' . $orderData['number'] . ' Cancelled - ' . STORE_NAME;
            $body = build_cancelled_email($orderData);
            break;
        case 'delivered':
            $subject = 'Order #' . $orderData['number'] . ' Delivered - ' . STORE_NAME;
            $body = build_delivered_email($orderData);
            break;
        default:
            return false;
    }
    
    $headers = array();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . STORE_NAME . ' <noreply@sliceplus.ca>';
    $headers[] = 'Reply-To: contact.restorise@gmail.com';
    $headers[] = 'X-Mailer: SlicePlus/1.0';
    if (!empty($bccEmail)) {
        $headers[] = 'Bcc: ' . $bccEmail;
    }
    
    return mail($toEmail, $subject, $body, implode("\r\n", $headers));
}

// =============================================
// EMAIL WRAPPER WITH LOGO
// =============================================
function email_wrapper($content) {
    return '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background-color:#0a0a0a;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#0a0a0a;padding:20px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#141414;border-radius:16px;overflow:hidden;border:1px solid #262626;">

<!-- Header with Logo -->
<tr>
<td style="background:linear-gradient(135deg,#df2b2b,#b91c1c);padding:28px 40px;text-align:center;">
    <img src="' . STORE_LOGO . '" alt="Slice+" style="height:50px;margin:0 auto 8px;display:block;" />
    <p style="margin:0;color:rgba(255,255,255,0.8);font-size:12px;letter-spacing:2px;text-transform:uppercase;">CONVENIENCE & PIZZERIA</p>
</td>
</tr>

<!-- Content -->
<tr>
<td style="padding:32px 40px;">
' . $content . '
</td>
</tr>

<!-- Footer -->
<tr>
<td style="padding:24px 40px;border-top:1px solid #262626;text-align:center;">
    <p style="margin:0 0 6px;color:#737373;font-size:13px;font-weight:600;">' . STORE_NAME . '</p>
    <p style="margin:0 0 4px;color:#525252;font-size:12px;">' . STORE_ADDRESS . '</p>
    <p style="margin:0 0 4px;color:#525252;font-size:12px;">' . STORE_PHONE . ' &middot; ' . STORE_WEBSITE . '</p>
    <p style="margin:12px 0 0;color:#404040;font-size:11px;">This is an automated email. Please do not reply directly.</p>
</td>
</tr>

</table>
</td></tr>
</table>
</body>
</html>';
}

// =============================================
// ITEMS TABLE BUILDER
// =============================================
function build_items_table($items) {
    $html = '';
    if (!empty($items)) {
        foreach ($items as $item) {
            $qty = isset($item['quantity']) ? $item['quantity'] : 1;
            $name = isset($item['name']) ? htmlspecialchars($item['name']) : 'Item';
            $price = isset($item['price']) ? $item['price'] : '0.00';
            $html .= '<tr>
                <td style="padding:10px 0;border-bottom:1px solid #262626;color:#e5e5e5;font-size:14px;">' . $qty . 'x ' . $name . '</td>
                <td style="padding:10px 0;border-bottom:1px solid #262626;color:#e5e5e5;font-size:14px;text-align:right;font-weight:600;">$' . $price . '</td>
            </tr>';
        }
    }
    return $html;
}

// =============================================
// TOTALS ROW BUILDER
// =============================================
function build_totals($order) {
    $html = '
        <tr>
            <td style="padding:10px 0;color:#a3a3a3;font-size:13px;">Subtotal</td>
            <td style="padding:10px 0;color:#e5e5e5;font-size:13px;text-align:right;">$' . $order['subtotal'] . '</td>
        </tr>
        <tr>
            <td style="padding:6px 0;color:#a3a3a3;font-size:13px;">HST (15%)</td>
            <td style="padding:6px 0;color:#e5e5e5;font-size:13px;text-align:right;">$' . $order['tax'] . '</td>
        </tr>';
    
    if ($order['type'] === 'delivery' && $order['delivery_fee'] !== '0.00') {
        $html .= '
        <tr>
            <td style="padding:6px 0;color:#a3a3a3;font-size:13px;">Delivery Fee</td>
            <td style="padding:6px 0;color:#e5e5e5;font-size:13px;text-align:right;">$' . $order['delivery_fee'] . '</td>
        </tr>';
    }
    
    $html .= '
        <tr>
            <td style="padding:14px 0 0;border-top:1px solid #404040;color:#ffffff;font-size:16px;font-weight:800;">Total</td>
            <td style="padding:14px 0 0;border-top:1px solid #404040;color:#22c55e;font-size:16px;font-weight:800;text-align:right;">$' . $order['total'] . '</td>
        </tr>';
    
    return $html;
}

// =============================================
// 1. CUSTOMER CONFIRMATION EMAIL
// =============================================
function build_customer_confirmation($order) {
    $isDelivery = $order['type'] === 'delivery';
    $itemsHtml = build_items_table(isset($order['items']) ? $order['items'] : array());
    $totalsHtml = build_totals($order);
    
    $content = '
    <!-- Status -->
    <div style="text-align:center;margin-bottom:24px;">
        <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:rgba(34,197,94,0.15);line-height:64px;font-size:28px;color:#22c55e;">&#10004;</div>
    </div>
    
    <h2 style="margin:0 0 8px;color:#ffffff;font-size:22px;text-align:center;font-weight:700;">Order Confirmed!</h2>
    <p style="margin:0 0 28px;color:#a3a3a3;font-size:14px;text-align:center;">Your order #' . htmlspecialchars($order['number']) . ' has been placed successfully.</p>
    
    <!-- Order Info -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:12px;margin-bottom:16px;">
    <tr><td style="padding:20px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;">Order Number</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;font-weight:700;">#' . htmlspecialchars($order['number']) . '</td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Order Type</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;font-weight:600;border-top:1px solid #262626;">' . ($isDelivery ? 'Delivery' : 'Pickup') . '</td>
            </tr>';
    
    if ($isDelivery && !empty($order['customer']['address'])) {
        $content .= '
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Delivery Address</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;border-top:1px solid #262626;">' . htmlspecialchars($order['customer']['address']) . '</td>
            </tr>';
    }
    
    if (!empty($order['payment']['brand'])) {
        $content .= '
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Payment</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;border-top:1px solid #262626;">' . htmlspecialchars($order['payment']['brand']) . ' ****' . htmlspecialchars($order['payment']['last4']) . '</td>
            </tr>';
    }
    
    $content .= '
        </table>
    </td></tr>
    </table>
    
    <!-- Items + Totals -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:12px;margin-bottom:16px;">
    <tr><td style="padding:20px;">
        <h3 style="margin:0 0 12px;color:#a3a3a3;font-size:12px;text-transform:uppercase;letter-spacing:1px;">Order Items</h3>
        <table width="100%" cellpadding="0" cellspacing="0">
            ' . $itemsHtml . $totalsHtml . '
        </table>
    </td></tr>
    </table>
    
    <!-- Estimated Time -->
    <div style="background:rgba(223,43,43,0.08);border:1px solid rgba(223,43,43,0.2);border-radius:12px;padding:20px;text-align:center;margin-bottom:20px;">
        <p style="margin:0 0 4px;color:#df2b2b;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Estimated ' . ($isDelivery ? 'Delivery' : 'Pickup') . ' Time</p>
        <p style="margin:0;color:#ffffff;font-size:20px;font-weight:700;">' . ($isDelivery ? '30 - 45 minutes' : '20 - 30 minutes') . '</p>
    </div>
    
    <!-- Track Your Order Button -->
    ' . (isset($order['tracking_url']) ? '
    <div style="text-align:center;margin-bottom:20px;">
        <a href="' . htmlspecialchars($order['tracking_url']) . '" style="display:inline-block;background:#df2b2b;color:#ffffff;padding:16px 40px;border-radius:50px;font-size:15px;font-weight:700;text-decoration:none;letter-spacing:0.5px;">&#128205; Track Your Order</a>
    </div>
    <p style="color:#525252;font-size:12px;text-align:center;margin-bottom:20px;">Or copy this link: <span style="color:#a3a3a3;">' . htmlspecialchars($order['tracking_url']) . '</span></p>
    ' : '') . '
    
    <!-- Store Info -->
    <p style="color:#737373;font-size:13px;text-align:center;line-height:1.8;">
        ' . ($isDelivery ? 'Your order is being prepared and will be delivered to your address.' : 'Your order is being prepared. Pick it up at:') . '
        ' . (!$isDelivery ? '<br><strong style="color:#e5e5e5;">' . STORE_ADDRESS . '</strong>' : '') . '
        <br>Questions? Call us at <strong style="color:#e5e5e5;">' . STORE_PHONE . '</strong>
    </p>';
    
    return email_wrapper($content);
}

// =============================================
// 2. OWNER / STORE NOTIFICATION EMAIL
// =============================================
function build_owner_notification($order) {
    $isDelivery = $order['type'] === 'delivery';
    $itemsHtml = build_items_table(isset($order['items']) ? $order['items'] : array());
    $totalsHtml = build_totals($order);
    $custName = isset($order['customer']['name']) ? htmlspecialchars($order['customer']['name']) : 'Customer';
    $custPhone = isset($order['customer']['phone']) ? htmlspecialchars($order['customer']['phone']) : '';
    $custEmail = isset($order['customer']['email']) ? htmlspecialchars($order['customer']['email']) : '';
    $custAddress = isset($order['customer']['address']) ? htmlspecialchars($order['customer']['address']) : '';
    
    $content = '
    <!-- Alert Badge -->
    <div style="text-align:center;margin-bottom:24px;">
        <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:rgba(245,158,11,0.15);line-height:64px;font-size:28px;color:#f59e0b;">&#128276;</div>
    </div>
    
    <h2 style="margin:0 0 8px;color:#ffffff;font-size:22px;text-align:center;font-weight:700;">New Order Received!</h2>
    <p style="margin:0 0 28px;color:#a3a3a3;font-size:14px;text-align:center;">Order #' . htmlspecialchars($order['number']) . ' from <strong style="color:#ffffff;">' . $custName . '</strong></p>
    
    <!-- Order Type Banner -->
    <div style="background:' . ($isDelivery ? 'rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.3)' : 'rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3)') . ';border-radius:12px;padding:16px;text-align:center;margin-bottom:16px;">
        <p style="margin:0;color:' . ($isDelivery ? '#60a5fa' : '#22c55e') . ';font-size:18px;font-weight:800;text-transform:uppercase;letter-spacing:2px;">' . ($isDelivery ? 'DELIVERY ORDER' : 'PICKUP ORDER') . '</p>
    </div>
    
    <!-- Customer Details -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:12px;margin-bottom:16px;">
    <tr><td style="padding:20px;">
        <h3 style="margin:0 0 12px;color:#f59e0b;font-size:12px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Customer Details</h3>
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;">Name</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;font-weight:700;">' . $custName . '</td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Phone</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;border-top:1px solid #262626;">
                    <a href="tel:' . $custPhone . '" style="color:#60a5fa;text-decoration:none;font-weight:600;">' . $custPhone . '</a>
                </td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Email</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;border-top:1px solid #262626;">
                    <a href="mailto:' . $custEmail . '" style="color:#60a5fa;text-decoration:none;">' . $custEmail . '</a>
                </td>
            </tr>';
    
    if ($isDelivery && !empty($custAddress)) {
        $content .= '
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Delivery Address</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;font-weight:600;border-top:1px solid #262626;">' . $custAddress . '</td>
            </tr>';
    }
    
    $content .= '
        </table>
    </td></tr>
    </table>
    
    <!-- Items + Totals -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:12px;margin-bottom:16px;">
    <tr><td style="padding:20px;">
        <h3 style="margin:0 0 12px;color:#f59e0b;font-size:12px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Order Items</h3>
        <table width="100%" cellpadding="0" cellspacing="0">
            ' . $itemsHtml . $totalsHtml . '
        </table>
    </td></tr>
    </table>
    
    <!-- Payment Info -->
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:12px;margin-bottom:16px;">
    <tr><td style="padding:20px;">
        <h3 style="margin:0 0 12px;color:#f59e0b;font-size:12px;text-transform:uppercase;letter-spacing:1px;font-weight:700;">Payment</h3>
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;">Status</td>
                <td style="padding:8px 0;color:#22c55e;font-size:14px;text-align:right;font-weight:700;">PAID</td>
            </tr>';
    
    if (!empty($order['payment']['brand'])) {
        $content .= '
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Card</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;border-top:1px solid #262626;">' . htmlspecialchars($order['payment']['brand']) . ' ****' . htmlspecialchars($order['payment']['last4']) . '</td>
            </tr>';
    }
    
    $content .= '
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Total Collected</td>
                <td style="padding:8px 0;color:#22c55e;font-size:16px;text-align:right;font-weight:800;border-top:1px solid #262626;">$' . $order['total'] . '</td>
            </tr>
        </table>
    </td></tr>
    </table>';
    
    // Tracking Link for Owner
    if (isset($order['tracking_url'])) {
        $content .= '
    <table width="100%" cellpadding="0" cellspacing="0" style="background:rgba(223,43,43,0.05);border:1px solid rgba(223,43,43,0.2);border-radius:12px;margin-bottom:16px;">
    <tr><td style="padding:20px;text-align:center;">
        <p style="margin:0 0 8px;color:#df2b2b;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:1px;">Order Tracking</p>
        <a href="' . htmlspecialchars($order['tracking_url']) . '" style="color:#60a5fa;font-size:14px;text-decoration:none;font-weight:600;">' . htmlspecialchars($order['tracking_url']) . '</a>
        <p style="margin:8px 0 0;color:#525252;font-size:12px;">Update status: POST /api/track.php with order number</p>
    </td></tr>
    </table>';
    }
    
    return email_wrapper($content);
}

// =============================================
// 3. CANCELLATION EMAIL (Customer)
// =============================================
function build_cancelled_email($order) {
    $content = '
    <div style="text-align:center;margin-bottom:24px;">
        <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:rgba(239,68,68,0.15);line-height:64px;font-size:28px;color:#ef4444;">&#10006;</div>
    </div>
    
    <h2 style="margin:0 0 8px;color:#ffffff;font-size:22px;text-align:center;font-weight:700;">Order Cancelled</h2>
    <p style="margin:0 0 24px;color:#a3a3a3;font-size:14px;text-align:center;">Your order #' . htmlspecialchars($order['number']) . ' has been cancelled.</p>
    
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:12px;margin-bottom:16px;">
    <tr><td style="padding:20px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;">Order Number</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;font-weight:700;">#' . htmlspecialchars($order['number']) . '</td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Total</td>
                <td style="padding:8px 0;color:#ef4444;font-size:14px;text-align:right;font-weight:700;text-decoration:line-through;border-top:1px solid #262626;">$' . $order['total'] . '</td>
            </tr>
        </table>
    </td></tr>
    </table>
    
    <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:12px;padding:20px;text-align:center;margin-bottom:20px;">
        <p style="margin:0;color:#fca5a5;font-size:14px;">If you were charged, a refund will be processed within 3-5 business days.</p>
    </div>
    
    <p style="color:#737373;font-size:13px;text-align:center;line-height:1.6;">
        Questions? Contact us at <strong style="color:#e5e5e5;">' . STORE_PHONE . '</strong>
        <br>or email <strong style="color:#e5e5e5;">contact.restorise@gmail.com</strong>
    </p>';
    
    return email_wrapper($content);
}

// =============================================
// 4. DELIVERED EMAIL (Customer)
// =============================================
function build_delivered_email($order) {
    $content = '
    <div style="text-align:center;margin-bottom:24px;">
        <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:rgba(34,197,94,0.15);line-height:64px;font-size:28px;color:#22c55e;">&#9989;</div>
    </div>
    
    <h2 style="margin:0 0 8px;color:#ffffff;font-size:22px;text-align:center;font-weight:700;">Order Delivered!</h2>
    <p style="margin:0 0 24px;color:#a3a3a3;font-size:14px;text-align:center;">Your order #' . htmlspecialchars($order['number']) . ' has been delivered. Enjoy your meal!</p>
    
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#1a1a1a;border-radius:12px;margin-bottom:16px;">
    <tr><td style="padding:20px;">
        <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;">Order Number</td>
                <td style="padding:8px 0;color:#ffffff;font-size:14px;text-align:right;font-weight:700;">#' . htmlspecialchars($order['number']) . '</td>
            </tr>
            <tr>
                <td style="padding:8px 0;color:#737373;font-size:13px;border-top:1px solid #262626;">Total Paid</td>
                <td style="padding:8px 0;color:#22c55e;font-size:14px;text-align:right;font-weight:700;border-top:1px solid #262626;">$' . $order['total'] . '</td>
            </tr>
        </table>
    </td></tr>
    </table>
    
    <div style="background:rgba(34,197,94,0.08);border:1px solid rgba(34,197,94,0.2);border-radius:12px;padding:20px;text-align:center;margin-bottom:20px;">
        <p style="margin:0 0 8px;color:#22c55e;font-size:14px;font-weight:700;">Enjoying your food?</p>
        <p style="margin:0;color:#a3a3a3;font-size:13px;">Order again at <a href="https://sliceplus.ca" style="color:#df2b2b;text-decoration:none;font-weight:600;">sliceplus.ca</a></p>
    </div>
    
    <p style="color:#737373;font-size:13px;text-align:center;">Thank you for choosing Slice+!</p>';
    
    return email_wrapper($content);
}