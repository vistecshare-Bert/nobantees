<?php
require_once 'auth.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$products = loadProducts();
$id       = trim($_POST['id'] ?? '');
$name     = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$price    = floatval($_POST['price'] ?? 0);
$color    = trim($_POST['color'] ?? '');
$desc     = trim($_POST['description'] ?? '');

if (!$name || !$category || $price <= 0) {
    $_SESSION['flash'] = 'Please fill in all required fields.';
    $redirect = $id ? "edit.php?id=$id" : 'edit.php';
    header("Location: $redirect");
    exit;
}

$isNew = empty($id);
if ($isNew) {
    $id = generateId($category, $products);
}

// Build image paths
$catFolders = ['hoodies'=>'hoodies','shirts'=>'shirts','pants'=>'pants'];
$folder = $catFolders[$category] ?? $category;
$images = [];

// Find existing product photos if editing
if (!$isNew) {
    foreach ($products as $p) {
        if ($p['id'] === $id) { $images = normalizeProductImages($p)['images']; break; }
    }
}

// Drop any photos the admin explicitly removed in the form
$removed = $_POST['removed_images'] ?? [];
if ($removed) {
    $images = array_values(array_filter($images, function ($img) use ($removed) {
        if (in_array($img, $removed, true)) {
            @unlink(dirname(__DIR__) . '/' . $img);
            return false;
        }
        return true;
    }));
}

// Handle newly uploaded photos (multiple)
$allowed = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
$finfo   = finfo_open(FILEINFO_MIME_TYPE);
$count   = count($images);

if (!empty($_FILES['images']['name']) && is_array($_FILES['images']['name'])) {
    foreach ($_FILES['images']['name'] as $i => $origName) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK || !$origName) continue;

        $tmpName = $_FILES['images']['tmp_name'][$i];
        $mime    = finfo_file($finfo, $tmpName);
        $ext     = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $validMime = in_array($mime, array_values($allowed));
        $validExt  = isset($allowed[$ext]);
        $validSize = $_FILES['images']['size'][$i] <= 10 * 1024 * 1024;
        if (!$validMime || !$validExt || !$validSize) continue;

        $destDir = dirname(__DIR__) . "/images/$folder/";
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $count++;
        $filename = $id . '-' . $count . '.' . $ext;
        $destPath = $destDir . $filename;

        if (move_uploaded_file($tmpName, $destPath)) {
            $images[] = "images/$folder/$filename";
        }
    }
}
finfo_close($finfo);

$product = [
    'id'          => $id,
    'category'    => $category,
    'name'        => $name,
    'price'       => $price,
    'color'       => $color,
    'description' => $desc,
    'images'      => $images,
];

if ($isNew) {
    $products[] = $product;
    $msg = "Product \"$name\" added successfully.";
} else {
    foreach ($products as &$p) {
        if ($p['id'] === $id) { $p = $product; break; }
    }
    unset($p);
    $msg = "Product \"$name\" updated successfully.";
}

saveProducts($products);

$_SESSION['flash'] = $msg;
header('Location: dashboard.php');
exit;
