<?php
// ─── USER HELPERS ────────────────────────────────────────────────────────────

function getUsersFile() {
    return dirname(__DIR__) . '/data/users.json';
}

function loadUsers() {
    $file = getUsersFile();
    if (!file_exists($file)) {
        $defaults = defaultUsers();
        saveUsers($defaults);
        return $defaults;
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveUsers($users) {
    file_put_contents(getUsersFile(), json_encode(array_values($users), JSON_PRETTY_PRINT));
}

function defaultUsers() {
    return [[
        'id'       => 'u1',
        'username' => 'admin',
        'password' => password_hash('nobantees2025', PASSWORD_DEFAULT),
        'name'     => 'Administrator',
        'role'     => 'admin',
        'created'  => date('Y-m-d'),
    ]];
}

function findUserByUsername($username) {
    foreach (loadUsers() as $u) {
        if (strtolower($u['username']) === strtolower($username)) return $u;
    }
    return null;
}

function findUserById($id) {
    foreach (loadUsers() as $u) {
        if ($u['id'] === $id) return $u;
    }
    return null;
}

function generateUserId($users) {
    $max = 0;
    foreach ($users as $u) {
        if (preg_match('/^u(\d+)$/', $u['id'], $m)) $max = max($max, (int)$m[1]);
    }
    return 'u' . ($max + 1);
}

function isAdmin() {
    return ($_SESSION['ban_user']['role'] ?? '') === 'admin';
}

// ─── PRODUCT HELPERS ─────────────────────────────────────────────────────────

function getProductsFile() {
    return dirname(__DIR__) . '/data/products.json';
}

function loadProducts() {
    $file = getProductsFile();
    if (!file_exists($file)) {
        $defaults = defaultProducts();
        saveProducts($defaults);
        return $defaults;
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveProducts($products) {
    file_put_contents(getProductsFile(), json_encode(array_values($products), JSON_PRETTY_PRINT));
    regenerateJs($products);
}

function regenerateJs($products) {
    $json = json_encode(array_values($products), JSON_PRETTY_PRINT);
    $js = "const products = $json;\n\nconst sizes = ['S', 'M', 'L', 'XL', 'XXL'];\n";
    file_put_contents(dirname(__DIR__) . '/js/products.js', $js);
}

function generateId($category, $products) {
    $prefix = $category === 'hoodies' ? 'h' : ($category === 'shirts' ? 's' : 'p');
    $max = 0;
    foreach ($products as $p) {
        if (isset($p['id']) && strpos($p['id'], $prefix) === 0) {
            $n = intval(substr($p['id'], 1));
            if ($n > $max) $max = $n;
        }
    }
    return $prefix . ($max + 1);
}

function defaultProducts() {
    return [
        ['id'=>'h1','category'=>'hoodies','name'=>'Classic BAN. Hoodie','price'=>65,'color'=>'Black','description'=>'The original. Heavy 400gsm fleece, embroidered BAN. logo on chest.','image'=>'images/hoodies/h1.jpg'],
        ['id'=>'h2','category'=>'hoodies','name'=>'Red Strike Hoodie','price'=>65,'color'=>'Red','description'=>'Bold red colorway. Screen-printed no-sign graphic across the back.','image'=>'images/hoodies/h2.jpg'],
        ['id'=>'h3','category'=>'hoodies','name'=>'Logo Pullover Hoodie','price'=>70,'color'=>'Charcoal','description'=>'Charcoal fleece with oversized BAN. print. Kangaroo pocket, ribbed cuffs.','image'=>'images/hoodies/h3.jpg'],
        ['id'=>'h4','category'=>'hoodies','name'=>'Banned Forever Zip-Up','price'=>75,'color'=>'Black','description'=>'Full-zip with YKK zipper. Dual pockets, fleece-lined hood.','image'=>'images/hoodies/h4.jpg'],
        ['id'=>'h5','category'=>'hoodies','name'=>'Street BAN. Hoodie','price'=>65,'color'=>'Washed Black','description'=>'Washed finish for a lived-in feel. Relaxed fit, dropped shoulders.','image'=>'images/hoodies/h5.jpg'],
        ['id'=>'s1','category'=>'shirts','name'=>'BAN. Logo Tee','price'=>35,'color'=>'Black','description'=>'Essential heavyweight tee. 240gsm cotton, BAN. logo front center.','image'=>'images/shirts/s1.jpg'],
        ['id'=>'s2','category'=>'shirts','name'=>'Red Circle Tee','price'=>35,'color'=>'White','description'=>'Clean white tee with full chest red circle-slash graphic.','image'=>'images/shirts/s2.jpg'],
        ['id'=>'s3','category'=>'shirts','name'=>'Strike Through Tee','price'=>38,'color'=>'Black','description'=>'Oversized cut. Large back graphic, small BAN. logo on left chest.','image'=>'images/shirts/s3.jpg'],
        ['id'=>'s4','category'=>'shirts','name'=>'Banned Graphic Tee','price'=>35,'color'=>'Red','description'=>'Washed red. Vintage-feel print with distressed BAN. logo.','image'=>'images/shirts/s4.jpg'],
        ['id'=>'s5','category'=>'shirts','name'=>'Classic Crew Tee','price'=>32,'color'=>'Charcoal','description'=>'Simple, clean, everyday. Minimal BAN. text on left hem.','image'=>'images/shirts/s5.jpg'],
        ['id'=>'p1','category'=>'pants','name'=>'BAN. Track Pants','price'=>55,'color'=>'Black','description'=>'Slim track pants with red side stripe and BAN. embroidery on left leg.','image'=>'images/pants/p1.jpg'],
        ['id'=>'p2','category'=>'pants','name'=>'Street Cargo Pants','price'=>65,'color'=>'Black','description'=>'Six-pocket cargo cut. Ripstop fabric, adjustable ankle cuffs.','image'=>'images/pants/p2.jpg'],
        ['id'=>'p3','category'=>'pants','name'=>'Logo Joggers','price'=>55,'color'=>'Charcoal','description'=>'French terry joggers. Elastic waist, tapered leg, BAN. logo left hip.','image'=>'images/pants/p3.jpg'],
        ['id'=>'p4','category'=>'pants','name'=>'Red Stripe Pants','price'=>60,'color'=>'Black / Red','description'=>'Bold red racing stripe down both legs. Slim fit, zip ankle.','image'=>'images/pants/p4.jpg'],
        ['id'=>'p5','category'=>'pants','name'=>'Classic BAN. Sweatpants','price'=>50,'color'=>'Black','description'=>'Relaxed sweatpants with BAN. wordmark printed down the left leg.','image'=>'images/pants/p5.jpg'],
    ];
}

// ─── ORDER HELPERS ───────────────────────────────────────────────────────────

function getOrdersFile() {
    return dirname(__DIR__) . '/orders.json';
}

function loadOrders() {
    $file = getOrdersFile();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function saveOrders($orders) {
    file_put_contents(getOrdersFile(), json_encode(array_values($orders), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// ─── QUOTE HELPERS ───────────────────────────────────────────────────────────

function getQuotesDir() {
    return dirname(__DIR__) . '/quotes';
}

function loadQuotes() {
    $dir = getQuotesDir();
    if (!is_dir($dir)) return [];
    $quotes = [];
    foreach (glob($dir . '/*.json') as $f) {
        $q = json_decode(file_get_contents($f), true);
        if ($q) $quotes[] = $q;
    }
    usort($quotes, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $quotes;
}

function saveQuote($quote) {
    $dir = getQuotesDir();
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . '/' . basename($quote['id']) . '.json',
        json_encode($quote, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function deleteQuote($id) {
    $file = getQuotesDir() . '/' . basename($id) . '.json';
    if (file_exists($file)) unlink($file);
}

// ─── CONTACT HELPERS ─────────────────────────────────────────────────────────

function getContactsDir() {
    return dirname(__DIR__) . '/contacts';
}

function loadContacts() {
    $dir = getContactsDir();
    if (!is_dir($dir)) return [];
    $contacts = [];
    foreach (glob($dir . '/*.json') as $f) {
        $c = json_decode(file_get_contents($f), true);
        if ($c) $contacts[] = $c;
    }
    usort($contacts, fn($a, $b) => strcmp($b['date'] ?? '', $a['date'] ?? ''));
    return $contacts;
}

function saveContact($contact) {
    $dir = getContactsDir();
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($dir . '/' . basename($contact['id']) . '.json',
        json_encode($contact, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function deleteContact($id) {
    $file = getContactsDir() . '/' . basename($id) . '.json';
    if (file_exists($file)) unlink($file);
}
