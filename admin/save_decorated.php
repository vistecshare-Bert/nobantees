<?php
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: decorated.php'); exit; }

$file = dirname(__DIR__) . '/data/decorated_products.json';
$products = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];

$id = trim($_POST['id'] ?? '');
$isNew = empty($id);
if ($isNew) $id = 'dec_' . uniqid();

// Parse sizes and colors from comma-separated hidden inputs
$sizes  = array_filter(array_map('trim', explode(',', $_POST['sizes']  ?? '')));
$colors = array_filter(array_map('trim', explode(',', $_POST['colors'] ?? '')));

$product = [
    'id'           => $id,
    'name'         => trim($_POST['name'] ?? ''),
    'category'     => trim($_POST['category'] ?? ''),
    'price'        => floatval($_POST['price'] ?? 0),
    'badge'        => trim($_POST['badge'] ?? ''),
    'garment'      => trim($_POST['garment'] ?? ''),
    'garmentType'  => trim($_POST['garmentType'] ?? 'tshirt'),
    'garmentColor' => trim($_POST['garmentColor'] ?? '#888888'),
    'sizes'        => array_values($sizes),
    'colors'       => array_values($colors),
    'design'       => json_decode($_POST['design_json'] ?? '{"elements":[]}', true),
    'created'      => $isNew ? date('Y-m-d H:i:s') : '',
    'updated'      => date('Y-m-d H:i:s'),
];

if ($isNew) {
    $products[] = $product;
} else {
    foreach ($products as &$p) {
        if ($p['id'] === $id) { $p = $product; break; }
    }
    unset($p);
}

file_put_contents($file, json_encode(array_values($products), JSON_PRETTY_PRINT));
$_SESSION['flash'] = ($isNew ? 'Product added' : 'Product updated') . ': ' . htmlspecialchars($product['name']);
header('Location: decorated.php');
exit;
