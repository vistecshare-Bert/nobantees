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

// Build image path
$catFolders = ['hoodies'=>'hoodies','shirts'=>'shirts','pants'=>'pants'];
$folder = $catFolders[$category] ?? $category;
$imagePath = ''; // will be set below

// Find existing product image if editing
if (!$isNew) {
    foreach ($products as $p) {
        if ($p['id'] === $id) { $imagePath = $p['image'] ?? ''; break; }
    }
}

// Handle image upload
if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
    finfo_close($finfo);

    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $validMime = in_array($mime, array_values($allowed));
    $validExt  = isset($allowed[$ext]);
    $validSize = $_FILES['image']['size'] <= 10 * 1024 * 1024;

    if ($validMime && $validExt && $validSize) {
        $destDir  = dirname(__DIR__) . "/images/$folder/";
        if (!is_dir($destDir)) mkdir($destDir, 0755, true);
        $filename = $id . '.' . $ext;
        $destPath = $destDir . $filename;

        // Remove old image file if extension changed
        if ($imagePath && file_exists(dirname(__DIR__) . '/' . $imagePath)) {
            $oldExt = pathinfo($imagePath, PATHINFO_EXTENSION);
            if ($oldExt !== $ext) {
                @unlink(dirname(__DIR__) . '/' . $imagePath);
            }
        }

        if (move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
            $imagePath = "images/$folder/$filename";
        }
    }
}

// Default image path if still empty
if (!$imagePath) {
    $imagePath = "images/$folder/$id.jpg";
}

$product = [
    'id'          => $id,
    'category'    => $category,
    'name'        => $name,
    'price'       => $price,
    'color'       => $color,
    'description' => $desc,
    'image'       => $imagePath,
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
