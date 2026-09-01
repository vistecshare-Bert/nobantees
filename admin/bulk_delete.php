<?php
require_once 'auth.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['ids']) || !is_array($_POST['ids'])) {
    header('Location: dashboard.php');
    exit;
}

$ids      = array_map('trim', $_POST['ids']);
$products = loadProducts();
$deletedNames = [];

$products = array_filter($products, function ($p) use ($ids, &$deletedNames) {
    if (in_array($p['id'], $ids, true)) {
        // Remove all photo files for this product
        foreach (normalizeProductImages($p)['images'] as $img) {
            $imgFile = dirname(__DIR__) . '/' . $img;
            if (file_exists($imgFile)) @unlink($imgFile);
        }
        $deletedNames[] = $p['name'];
        return false;
    }
    return true;
});

saveProducts($products);

$_SESSION['flash'] = count($deletedNames)
    ? count($deletedNames) . ' product(s) deleted: ' . implode(', ', $deletedNames)
    : 'No matching products found.';

header('Location: dashboard.php');
exit;
