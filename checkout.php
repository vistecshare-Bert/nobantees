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

// Mirror cart.html's shipping logic: free over $100, otherwise $4.99 flat
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += floatval($item['price'] ?? 0) * max(1, intval($item['quantity'] ?? 1));
}
$shipping = $subtotal >= 100 ? 0 : 4.99;

function buildLineItemFields($items, $shipping) {
    $fields = [];
    foreach ($items as $i => $item) {
        $name   = htmlspecialchars($item['name']) . ' — Size: ' . htmlspecialchars($item['size']);
        $amount = intval(floatval($item['price']) * 100); // cents
        $qty    = max(1, intval($item['quantity']));

        $fields["line_items[$i][price_data][currency]"]           = 'usd';
        $fields["line_items[$i][price_data][product_data][name]"] = $name;
        $fields["line_items[$i][price_data][unit_amount]"]        = $amount;
        $fields["line_items[$i][quantity]"]                       = $qty;
    }
    if ($shipping > 0) {
        $si = count($items);
        $fields["line_items[$si][price_data][currency]"]           = 'usd';
        $fields["line_items[$si][price_data][product_data][name]"] = 'Shipping';
        $fields["line_items[$si][price_data][unit_amount]"]        = intval(round($shipping * 100));
        $fields["line_items[$si][quantity]"]                       = 1;
    }
    return $fields;
}

function createCheckoutSession($postFields) {
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SECRET_KEY . ':');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$http_code, json_decode($response, true)];
}

$returnUrl = SITE_URL . '/verify-order.php?sid={CHECKOUT_SESSION_ID}';
$shared = [
    'mode'       => 'payment',
    'ui_mode'    => 'elements',
    'return_url' => $returnUrl,
    // Shared Stripe account with VistecPrints — force the card statement to read NOBANTEES, not the account default
    'payment_intent_data[statement_descriptor]' => 'NOBANTEES',
    // Collect a shipping address — this is a physical goods store, we need it to fulfill orders
    'shipping_address_collection[allowed_countries][0]' => 'US',
];

// Stripe has no single payment method type covering Apple Pay + Google Pay + Link + Cash App
// Pay without also pulling in a manual "enter card number" form, so checkout runs two parallel
// Checkout Sessions: one drives the Express Checkout Element (wallets), the other drives a
// Payment Element restricted to just Cash App Pay. Whichever the customer completes "wins" —
// verify-order.php expires the sibling session once one of them is paid.
$walletFields = $shared + [
    'payment_method_types[0]' => 'card',
    'payment_method_types[1]' => 'link',
] + buildLineItemFields($items, $shipping);

$cashappFields = $shared + [
    'payment_method_types[0]' => 'cashapp',
] + buildLineItemFields($items, $shipping);

[$walletCode, $walletSession]   = createCheckoutSession($walletFields);
[$cashappCode, $cashappSession] = createCheckoutSession($cashappFields);

if ($walletCode !== 200 || !isset($walletSession['client_secret'])) {
    http_response_code(500);
    echo json_encode(['error' => $walletSession['error']['message'] ?? 'Could not start checkout (wallets).']);
    exit;
}
if ($cashappCode !== 200 || !isset($cashappSession['client_secret'])) {
    http_response_code(500);
    echo json_encode(['error' => $cashappSession['error']['message'] ?? 'Could not start checkout (Cash App Pay).']);
    exit;
}

// Save a pending order for EACH session, cross-referencing the sibling.
session_start();
$orderId    = 'NBT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 6));
$pendingDir = __DIR__ . '/pending_orders';
if (!is_dir($pendingDir)) mkdir($pendingDir, 0755, true);

$basePending = [
    'orderId'      => $orderId,
    'status'       => 'pending_payment',
    'date'         => date('c'),
    'items'        => $items,
    'subtotal'     => $subtotal,
    'shipping'     => $shipping,
    'total'        => $subtotal + $shipping,
    'accountEmail' => isset($_SESSION['nb_customer']['email']) ? $_SESSION['nb_customer']['email'] : null,
];

file_put_contents(
    $pendingDir . '/' . $walletSession['id'] . '.json',
    json_encode($basePending + ['siblingSessionId' => $cashappSession['id']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);
file_put_contents(
    $pendingDir . '/' . $cashappSession['id'] . '.json',
    json_encode($basePending + ['siblingSessionId' => $walletSession['id']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

echo json_encode([
    'publishableKey'      => STRIPE_PUBLISHABLE_KEY,
    'walletClientSecret'  => $walletSession['client_secret'],
    'cashappClientSecret' => $cashappSession['client_secret'],
]);
