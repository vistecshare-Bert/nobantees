<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$usersFile = __DIR__ . '/users.json';

function loadUsers($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}
function saveUsers($file, $users) {
    file_put_contents($file, json_encode(array_values($users), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
function sessionUser($user) {
    // Never put passwordHash in the session
    $u = $user;
    unset($u['passwordHash']);
    return $u;
}

$action = $_GET['action'] ?? '';

// ── Email / Password Login ────────────────────────────────
if ($action === 'login') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$email || !$password) {
        echo json_encode(['success' => false, 'error' => 'Email and password are required.']); exit;
    }

    $users = loadUsers($usersFile);
    $found = null;
    foreach ($users as $u) {
        if (strtolower($u['email'] ?? '') === strtolower($email)) { $found = $u; break; }
    }

    if (!$found) {
        echo json_encode(['success' => false, 'error' => 'No account found with that email.']); exit;
    }
    if (empty($found['passwordHash'])) {
        echo json_encode(['success' => false, 'error' => 'This account has no password set. Please contact support.']); exit;
    }
    if (!password_verify($password, $found['passwordHash'])) {
        echo json_encode(['success' => false, 'error' => 'Incorrect password.']); exit;
    }

    $su = sessionUser($found);
    $_SESSION['nb_customer'] = $su;
    echo json_encode(['success' => true, 'user' => $su]);
    exit;
}

// ── Email / Password Register ─────────────────────────────
if ($action === 'register') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$name || !$email || !$password) {
        echo json_encode(['success' => false, 'error' => 'All fields are required.']); exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']); exit;
    }
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters.']); exit;
    }

    $users = loadUsers($usersFile);
    foreach ($users as $u) {
        if (strtolower($u['email'] ?? '') === strtolower($email)) {
            echo json_encode(['success' => false, 'error' => 'An account with that email already exists.']); exit;
        }
    }

    $user = [
        'id'           => 'USR-' . strtoupper(substr(md5($email . time()), 0, 8)),
        'email'        => $email,
        'name'         => $name,
        'picture'      => '',
        'passwordHash' => password_hash($password, PASSWORD_DEFAULT),
        'createdAt'    => date('c'),
    ];
    array_unshift($users, $user);
    saveUsers($usersFile, $users);

    $su = sessionUser($user);
    $_SESSION['nb_customer'] = $su;
    echo json_encode(['success' => true, 'user' => $su]);
    exit;
}

// ── Logout ────────────────────────────────────────────────
if ($action === 'logout') {
    unset($_SESSION['nb_customer']);
    echo json_encode(['success' => true]);
    exit;
}

// ── Session status ────────────────────────────────────────
if ($action === 'status') {
    if (!empty($_SESSION['nb_customer'])) {
        $ordersFile = __DIR__ . '/orders.json';
        $allOrders  = file_exists($ordersFile) ? (json_decode(file_get_contents($ordersFile), true) ?: []) : [];
        $email      = strtolower($_SESSION['nb_customer']['email'] ?? '');
        $count      = count(array_filter($allOrders, fn($o) => strtolower($o['customer']['email'] ?? '') === $email));
        echo json_encode(['loggedIn' => true, 'user' => $_SESSION['nb_customer'], 'orderCount' => $count]);
    } else {
        echo json_encode(['loggedIn' => false]);
    }
    exit;
}

// ── Orders for logged-in user ─────────────────────────────
if ($action === 'orders') {
    if (empty($_SESSION['nb_customer'])) { echo json_encode(['success' => false, 'error' => 'Not logged in']); exit; }
    $ordersFile = __DIR__ . '/orders.json';
    $allOrders  = file_exists($ordersFile) ? (json_decode(file_get_contents($ordersFile), true) ?: []) : [];
    $email      = strtolower($_SESSION['nb_customer']['email']);
    $myOrders   = array_values(array_filter($allOrders, fn($o) => strtolower($o['customer']['email'] ?? '') === $email));
    echo json_encode(['success' => true, 'orders' => $myOrders]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);
