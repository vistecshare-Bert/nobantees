<?php
require_once 'auth.php';
require_once 'helpers.php';

if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: users.php');
    exit;
}

$id       = trim($_POST['id'] ?? '');
$name     = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? '';
$isEdit   = !empty($id);

// Validate
$errors = [];
if (!$name)     $errors[] = 'Name is required.';
if (!$username) $errors[] = 'Username is required.';
if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username)) $errors[] = 'Username may only contain letters, numbers, underscores, and hyphens.';
if (!$isEdit && !$password) $errors[] = 'Password is required.';
if ($password && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
if (!in_array($role, ['admin', 'editor'], true)) $errors[] = 'Invalid role.';

$users = loadUsers();

// Check username uniqueness
foreach ($users as $u) {
    if (strtolower($u['username']) === strtolower($username) && $u['id'] !== $id) {
        $errors[] = 'That username is already taken.';
        break;
    }
}

// On edit, find existing user
$existing = null;
if ($isEdit) {
    foreach ($users as $u) {
        if ($u['id'] === $id) { $existing = $u; break; }
    }
    if (!$existing) $errors[] = 'User not found.';
}

// Prevent removing the last admin
if ($isEdit && $existing && $existing['role'] === 'admin' && $role === 'editor') {
    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
    if ($adminCount <= 1) $errors[] = 'Cannot change role: at least one admin is required.';
}

if ($errors) {
    $_SESSION['flash'] = 'Error: ' . implode(' ', $errors);
    $redirect = $isEdit ? "edit_user.php?id=$id" : 'edit_user.php';
    header("Location: $redirect");
    exit;
}

if ($isEdit) {
    // Update existing user
    foreach ($users as &$u) {
        if ($u['id'] === $id) {
            $u['name']     = $name;
            $u['username'] = $username;
            $u['role']     = $role;
            if ($password) $u['password'] = password_hash($password, PASSWORD_DEFAULT);
            break;
        }
    }
    unset($u);
    $msg = "User \"{$name}\" updated.";
} else {
    // Create new user
    $newUser = [
        'id'       => generateUserId($users),
        'username' => $username,
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'name'     => $name,
        'role'     => $role,
        'created'  => date('Y-m-d'),
    ];
    $users[] = $newUser;
    $msg = "User \"{$name}\" created successfully.";
}

saveUsers($users);
$_SESSION['flash'] = $msg;
header('Location: users.php');
exit;
