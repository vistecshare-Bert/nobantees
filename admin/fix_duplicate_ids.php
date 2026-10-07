<?php
// One-time repair tool: finds any products sharing the same ID (which breaks
// Edit/Delete for all but the first one found) and reassigns a fresh,
// globally-unique ID to every duplicate beyond the first. Safe to re-run --
// does nothing if there are no collisions. Delete this file once you've run it.
require_once 'auth.php';
require_once 'helpers.php';

$products = loadProducts();
$seen = [];
$fixed = [];

foreach ($products as &$p) {
    $id = $p['id'] ?? '';
    if ($id !== '' && isset($seen[$id])) {
        $newId = generateId($p['category'] ?? '', $products);
        $fixed[] = ($p['name'] ?? '(unnamed)') . " [{$p['category']}]: $id -> $newId";
        $p['id'] = $newId;
        $seen[$newId] = true;
    } else {
        $seen[$id] = true;
    }
}
unset($p);

if ($fixed) {
    saveProducts($products);
}

header('Content-Type: text/plain');
if ($fixed) {
    echo "Fixed " . count($fixed) . " duplicate product ID(s):\n\n" . implode("\n", $fixed) . "\n\nSafe to delete this file now.";
} else {
    echo "No duplicate product IDs found -- nothing to fix. Safe to delete this file.";
}
