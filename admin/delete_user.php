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

$id = trim($_POST['id'] ?? '');
$me = $_SESSION['ban_user']['id'];

if (!$id) {
    header('Location: users.php');
    exit;
}

if ($id === $me) {
    $_SESSION['flash'] = 'Error: You cannot delete your own account.';
    header('Location: users.php');
    exit;
}

$users = loadUsers();
$target = null;
foreach ($users as $u) {
    if ($u['id'] === $id) { $target = $u; break; }
}

if (!$target) {
    $_SESSION['flash'] = 'Error: User not found.';
    header('Location: users.php');
    exit;
}

// Prevent deleting the last admin
if ($target['role'] === 'admin') {
    $adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
    if ($adminCount <= 1) {
        $_SESSION['flash'] = 'Error: Cannot delete the last admin account.';
        header('Location: users.php');
        exit;
    }
}

$users = array_filter($users, fn($u) => $u['id'] !== $id);
saveUsers($users);

$_SESSION['flash'] = "User \"{$target['name']}\" deleted.";
header('Location: users.php');
exit;
