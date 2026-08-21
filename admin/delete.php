<?php
require_once 'auth.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['id'])) {
    header('Location: dashboard.php');
    exit;
}

$id = trim($_POST['id']);
$products = loadProducts();
$deleted = null;

$products = array_filter($products, function($p) use ($id, &$deleted) {
    if ($p['id'] === $id) { $deleted = $p; return false; }
    return true;
});

if ($deleted) {
    // Remove the image file
    if (!empty($deleted['image'])) {
        $imgFile = dirname(__DIR__) . '/' . $deleted['image'];
        if (file_exists($imgFile)) @unlink($imgFile);
    }
    saveProducts($products);
    $_SESSION['flash'] = 'Product "' . $deleted['name'] . '" deleted.';
} else {
    $_SESSION['flash'] = 'Product not found.';
}

header('Location: dashboard.php');
exit;
