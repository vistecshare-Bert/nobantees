<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/helpers.php';

if (!empty($_SESSION['ban_user'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = findUserByUsername($username);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['ban_user'] = [
            'id'       => $user['id'],
            'username' => $user['username'],
            'name'     => $user['name'] ?? $user['username'],
            'role'     => $user['role'],
        ];
        // Record last login timestamp
        $allUsers = loadUsers();
        foreach ($allUsers as &$u) {
            if ($u['id'] === $user['id']) { $u['last_login'] = date('Y-m-d H:i'); break; }
        }
        unset($u);
        saveUsers($allUsers);
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Incorrect username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — NoBan Tees</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{background:#0a0a0a;color:#fff;font-family:'Inter',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;}
    .box{background:#141414;border:1px solid #222;padding:48px 40px;width:100%;max-width:400px;}
    .logo{font-family:'Bebas Neue',sans-serif;font-size:36px;letter-spacing:3px;margin-bottom:8px;display:flex;align-items:center;gap:10px;}
    .sub{font-size:12px;letter-spacing:3px;text-transform:uppercase;color:#555;margin-bottom:32px;}
    label{display:block;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#666;margin-bottom:8px;}
    input[type=text],input[type=password]{width:100%;background:#0a0a0a;border:1px solid #2a2a2a;color:#fff;padding:13px 16px;font-size:15px;font-family:'Inter',sans-serif;margin-bottom:20px;transition:border-color .2s;}
    input[type=text]:focus,input[type=password]:focus{outline:none;border-color:#dc0000;}
    button{width:100%;background:#dc0000;color:#fff;border:none;padding:14px;font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:3px;cursor:pointer;transition:background .2s;}
    button:hover{background:#ff2020;}
    .error{background:rgba(220,0,0,.12);border:1px solid #dc0000;color:#ff6666;padding:12px 16px;font-size:13px;margin-bottom:20px;}
    .back{display:block;text-align:center;margin-top:20px;font-size:12px;color:#444;text-decoration:none;transition:color .2s;}
    .back:hover{color:#fff;}
  </style>
</head>
<body>
<div class="box">
  <div class="logo">
    <svg width="32" height="32" viewBox="0 0 30 30" fill="none">
      <circle cx="15" cy="15" r="13" stroke="#dc0000" stroke-width="2.5"/>
      <line x1="4.5" y1="4.5" x2="25.5" y2="25.5" stroke="#dc0000" stroke-width="2.5" stroke-linecap="round"/>
    </svg>
    BAN. Admin
  </div>
  <p class="sub">Product Management</p>
  <?php if ($error): ?>
    <div class="error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <label>Username</label>
    <input type="text" name="username" placeholder="Enter username" required autofocus autocomplete="username">
    <label>Password</label>
    <input type="password" name="password" placeholder="Enter password" required autocomplete="current-password">
    <button type="submit">Login</button>
  </form>
  <a href="../index.html" class="back">← Back to store</a>
</div>
</body>
</html>
