<?php
require_once __DIR__ . '/config.php';

$merchant = LIVE_MODE ? 'RT6ST4S4M0951' : 'M155T7XWW06S1';

json_response(array(
    'checkout_js' => CHECKOUT_JS_URL,
    'api_token' => ECOM_PUBLIC_TOKEN,
    'merchant_id' => $merchant,
    'live_mode' => LIVE_MODE,
    'tax_rate' => TAX_RATE,
    'delivery_fee' => DELIVERY_FEE,
    'currency' => CURRENCY,
    'store' => array(
        'name' => 'Slice+ Convenience & Pizzeria',
        'phone' => '(902) 800-4001',
        'address' => '6169 Quinpool Rd #111, Halifax, NS B3L 4P8'
    )
));