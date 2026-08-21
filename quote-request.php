<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$name   = strip_tags(trim($input['name']   ?? ''));
$email  = strip_tags(trim($input['email']  ?? ''));
$phone  = strip_tags(trim($input['phone']  ?? ''));
$item   = strip_tags(trim($input['item']   ?? ''));
$size   = strip_tags(trim($input['size']   ?? ''));
$qty    = strip_tags(trim($input['qty']    ?? ''));
$budget = strip_tags(trim($input['budget'] ?? ''));
$notes  = strip_tags(trim($input['notes']  ?? ''));

if (!$name || !$email) {
    echo json_encode(['error' => 'Name and email are required.']); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Please enter a valid email address.']); exit;
}

$quoteId   = 'QR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)rand(), true)), 0, 6));
$quotesDir = __DIR__ . '/quotes';
if (!is_dir($quotesDir)) mkdir($quotesDir, 0755, true);

$record = [
    'id'     => $quoteId,
    'date'   => date('c'),
    'status' => 'new',
    'name'   => $name,
    'email'  => $email,
    'phone'  => $phone,
    'item'   => $item,
    'size'   => $size,
    'qty'    => $qty,
    'budget' => $budget,
    'notes'  => $notes,
    'ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
];
file_put_contents($quotesDir . '/' . $quoteId . '.json',
    json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

$htaccess = $quotesDir . '/.htaccess';
if (!file_exists($htaccess)) file_put_contents($htaccess, "Deny from all\n");

$subject = "Quote Request [$quoteId] from $name";
$message =
    "New quote request from nobantees.com\n" .
    str_repeat('-', 40) . "\n\n" .
    "ID:       $quoteId\n" .
    "Name:     $name\n" .
    "Email:    $email\n" .
    "Phone:    " . ($phone ?: '—') . "\n\n" .
    "Item:     " . ($item ?: '—') . "\n" .
    "Size:     " . ($size ?: '—') . "\n" .
    "Qty:      " . ($qty  ?: '—') . "\n" .
    "Budget:   " . ($budget ?: '—') . "\n\n" .
    "Notes:\n" . ($notes ?: '—') . "\n\n" .
    str_repeat('-', 40) . "\n" .
    "View in admin: " . SITE_URL . "/admin/quotes.php\n";

$headers =
    "From: noreply@nobantees.com\r\n" .
    "Reply-To: $email\r\n" .
    "Content-Type: text/plain; charset=UTF-8\r\n";

@mail(NOTIFY_EMAIL, $subject, $message, $headers);

echo json_encode(['success' => true, 'id' => $quoteId]);
