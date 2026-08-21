<?php
require_once 'auth.php';
require_once 'helpers.php';

if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$users = loadUsers();
$id = $_GET['id'] ?? null;
$user = null;
$isEdit = false;

if ($id) {
    $user = findUserById($id);
    if ($user) $isEdit = true;
}

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEdit ? 'Edit User' : 'Add User' ?> — NoBan Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{background:#0a0a0a;color:#fff;font-family:'Inter',sans-serif;min-height:100vh;}
    .layout{display:flex;min-height:100vh;}
    .sidebar{width:220px;background:#111;border-right:1px solid #1e1e1e;padding:0;flex-shrink:0;position:sticky;top:0;height:100vh;}
    .sidebar-logo{padding:24px 20px;border-bottom:1px solid #1e1e1e;font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:2px;display:flex;align-items:center;gap:8px;}
    .sidebar-nav{padding:16px 0;}
    .sidebar-nav a{display:flex;align-items:center;gap:10px;padding:12px 20px;font-size:13px;color:#666;text-decoration:none;transition:all .2s;letter-spacing:.5px;}
    .sidebar-nav a:hover{color:#fff;background:#1a1a1a;}
    .sidebar-nav a.active{color:#fff;background:#1a1a1a;border-left:2px solid #dc0000;}
    .sidebar-nav a svg{width:16px;height:16px;flex-shrink:0;}
    .sidebar-footer{position:absolute;bottom:0;width:100%;border-top:1px solid #1e1e1e;padding:16px 20px;}
    .sidebar-footer a{font-size:12px;color:#444;text-decoration:none;display:block;transition:color .2s;margin-bottom:8px;}
    .sidebar-footer a:hover{color:#dc0000;}
    .main{flex:1;padding:40px;max-width:640px;}
    .page-header{display:flex;align-items:center;gap:16px;margin-bottom:32px;}
    .back-btn{color:#555;text-decoration:none;font-size:13px;transition:color .2s;}
    .back-btn:hover{color:#fff;}
    .page-title{font-family:'Bebas Neue',sans-serif;font-size:40px;letter-spacing:2px;}
    .flash{padding:12px 20px;margin-bottom:24px;font-size:14px;}
    .flash.err{background:rgba(220,0,0,.1);border:1px solid rgba(220,0,0,.3);color:#ff6666;}
    .form-card{background:#141414;border:1px solid #1e1e1e;padding:32px;}
    .form-group{margin-bottom:24px;}
    label{display:block;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#555;margin-bottom:8px;}
    .required{color:#dc0000;}
    input[type=text],input[type=password],select{
      width:100%;background:#0a0a0a;border:1px solid #2a2a2a;color:#fff;
      padding:12px 16px;font-size:14px;font-family:'Inter',sans-serif;transition:border-color .2s;
    }
    input:focus,select:focus{outline:none;border-color:#dc0000;}
    select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23666' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;}
    select option{background:#111;}
    .hint{font-size:12px;color:#444;margin-top:6px;}
    .form-actions{display:flex;gap:12px;margin-top:8px;}
    .btn-save{background:#dc0000;color:#fff;border:none;padding:13px 32px;font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;cursor:pointer;transition:background .2s;}
    .btn-save:hover{background:#ff2020;}
    .btn-cancel{background:none;border:1px solid #2a2a2a;color:#666;padding:13px 24px;font-size:13px;cursor:pointer;text-decoration:none;display:inline-block;font-family:'Inter',sans-serif;transition:all .2s;}
    .btn-cancel:hover{border-color:#555;color:#fff;}
    .pw-toggle{position:relative;}
    .pw-toggle input{padding-right:48px;}
    .pw-toggle button{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:#555;cursor:pointer;font-size:12px;padding:4px 6px;font-family:'Inter',sans-serif;transition:color .2s;}
    .pw-toggle button:hover{color:#fff;}
  </style>
</head>
<body>
<div class="layout">
  <aside class="sidebar">
    <div class="sidebar-logo">
      <svg width="24" height="24" viewBox="0 0 30 30" fill="none">
        <circle cx="15" cy="15" r="13" stroke="#dc0000" stroke-width="2.5"/>
        <line x1="4.5" y1="4.5" x2="25.5" y2="25.5" stroke="#dc0000" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      BAN.
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Products
      </a>
      <a href="decorated.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
        Decorated
      </a>
      <a href="edit.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Add Product
      </a>
      <a href="orders.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
        Orders
      </a>
      <a href="quotes.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Quotes
      </a>
      <a href="contacts.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Contacts
      </a>
      <a href="users.php" class="active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Users
      </a>
    </nav>
    <div class="sidebar-footer">
      <a href="../index.html" target="_blank">← View Store</a>
      <a href="logout.php">Logout</a>
    </div>
  </aside>

  <main class="main">
    <div class="page-header">
      <a href="users.php" class="back-btn">← Users</a>
      <h1 class="page-title"><?= $isEdit ? 'Edit User' : 'Add User' ?></h1>
    </div>

    <?php if ($flash): ?>
      <div class="flash err"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="form-card">
      <form method="POST" action="save_user.php">
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                 placeholder="e.g. Jane Smith" required>
        </div>

        <div class="form-group">
          <label>Username <span class="required">*</span></label>
          <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                 placeholder="e.g. janesmith" required autocomplete="off"
                 pattern="[a-zA-Z0-9_\-]+" title="Letters, numbers, underscores, hyphens only">
          <p class="hint">Letters, numbers, _ and - only. Used to log in.</p>
        </div>

        <div class="form-group">
          <label><?= $isEdit ? 'New Password' : 'Password' ?> <?= $isEdit ? '' : '<span class="required">*</span>' ?></label>
          <div class="pw-toggle">
            <input type="password" name="password" id="pwField"
                   placeholder="<?= $isEdit ? 'Leave blank to keep current password' : 'Set a strong password' ?>"
                   <?= $isEdit ? '' : 'required' ?> autocomplete="new-password" minlength="6">
            <button type="button" onclick="togglePw()">Show</button>
          </div>
          <?php if ($isEdit): ?>
            <p class="hint">Leave blank to keep the current password.</p>
          <?php else: ?>
            <p class="hint">Minimum 6 characters.</p>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label>Role <span class="required">*</span></label>
          <select name="role" required>
            <option value="admin"  <?= ($user['role'] ?? '') === 'admin'  ? 'selected' : '' ?>>Admin — Full access (products, decorated, users)</option>
            <option value="editor" <?= ($user['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor — Products &amp; decorated only</option>
          </select>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save"><?= $isEdit ? 'Save Changes' : 'Create User' ?></button>
          <a href="users.php" class="btn-cancel">Cancel</a>
        </div>
      </form>
    </div>
  </main>
</div>
<script>
function togglePw() {
  const f = document.getElementById('pwField');
  const btn = f.nextElementSibling;
  if (f.type === 'password') { f.type = 'text'; btn.textContent = 'Hide'; }
  else { f.type = 'password'; btn.textContent = 'Show'; }
}
</script>
</body>
</html>
