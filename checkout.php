<?php
header('Content-Type: application/json');

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['items'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No cart items received']);
    exit;
}

$items = $input['items'];

// Mirror cart.html's shipping logic: free over $100, otherwise $8.99 flat
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += floatval($item['price'] ?? 0) * max(1, intval($item['quantity'] ?? 1));
}
$shipping = $subtotal >= 100 ? 0 : 8.99;

// Build Stripe line_items as form-encoded fields
$post_fields = [
    'mode'                    => 'payment',
    'success_url'             => SITE_URL . '/verify-order.php?sid={CHECKOUT_SESSION_ID}',
    'cancel_url'              => SITE_URL . '/cart.html',
    'payment_method_types[]'  => 'card',
    // Shared Stripe account with VistecPrints — force the card statement to read NOBANTEES, not the account default
    'payment_intent_data[statement_descriptor]' => 'NOBANTEES',
];

foreach ($items as $i => $item) {
    $name = htmlspecialchars($item['name']) . ' — Size: ' . htmlspecialchars($item['size']);
    $amount = intval(floatval($item['price']) * 100); // cents
    $qty = max(1, intval($item['quantity']));

    $post_fields["line_items[$i][price_data][currency]"]                    = 'usd';
    $post_fields["line_items[$i][price_data][product_data][name]"]          = $name;
    $post_fields["line_items[$i][price_data][unit_amount]"]                 = $amount;
    $post_fields["line_items[$i][quantity]"]                                = $qty;
}

if ($shipping > 0) {
    $si = count($items);
    $post_fields["line_items[$si][price_data][currency]"]           = 'usd';
    $post_fields["line_items[$si][price_data][product_data][name]"] = 'Shipping';
    $post_fields["line_items[$si][price_data][unit_amount]"]        = intval(round($shipping * 100));
    $post_fields["line_items[$si][quantity]"]                       = 1;
}

$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_fields));
curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$session = json_decode($response, true);

if ($http_code === 200 && isset($session['url'])) {
    // Save a pending order — finalized in verify-order.php after confirmed payment
    session_start();
    $orderId    = 'NBT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 6));
    $pendingDir = __DIR__ . '/pending_orders';
    if (!is_dir($pendingDir)) mkdir($pendingDir, 0755, true);

    $pending = [
        'orderId'      => $orderId,
        'status'       => 'pending_payment',
        'date'         => date('c'),
        'items'        => $items,
        'subtotal'     => $subtotal,
        'shipping'     => $shipping,
        'total'        => $subtotal + $shipping,
        'accountEmail' => isset($_SESSION['nb_customer']['email']) ? $_SESSION['nb_customer']['email'] : null,
    ];
    file_put_contents($pendingDir . '/' . $session['id'] . '.json',
        json_encode($pending, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo json_encode(['url' => $session['url']]);
} else {
    $error_msg = $session['error']['message'] ?? 'Checkout unavailable. Please try again.';
    http_response_code(500);
    echo json_encode(['error' => $error_msg]);
}
