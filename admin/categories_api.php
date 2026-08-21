<?php
require_once 'auth.php';
header('Content-Type: application/json');

$file = dirname(__DIR__) . '/data/decorated_categories.json';
$cats = file_exists($file) ? (json_decode(file_get_contents($file), true) ?: []) : [];

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $key  = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['key'] ?? '')));
    $name = trim($_POST['name'] ?? '');
    if (!$key || !$name) { echo json_encode(['error'=>'Key and name required']); exit; }
    foreach ($cats as $c) { if ($c['key'] === $key) { echo json_encode(['error'=>'Key already exists']); exit; } }
    $cats[] = ['key'=>$key,'name'=>$name];
    file_put_contents($file, json_encode($cats, JSON_PRETTY_PRINT));
    echo json_encode(['ok'=>true,'key'=>$key,'name'=>$name]);

} elseif ($action === 'delete') {
    $key = trim($_POST['key'] ?? '');
    $cats = array_values(array_filter($cats, fn($c) => $c['key'] !== $key));
    file_put_contents($file, json_encode($cats, JSON_PRETTY_PRINT));
    echo json_encode(['ok'=>true]);

} else {
    echo json_encode($cats);
}
