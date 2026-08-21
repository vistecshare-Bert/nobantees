<?php
require_once 'auth.php';
require_once 'helpers.php';

if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$users = loadUsers();
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$me = $_SESSION['ban_user']['id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users — NoBan Admin</title>
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
    .main{flex:1;padding:40px;overflow-x:hidden;}
    .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;}
    .page-title{font-family:'Bebas Neue',sans-serif;font-size:40px;letter-spacing:2px;}
    .btn-add{background:#dc0000;color:#fff;border:none;padding:12px 28px;font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;cursor:pointer;text-decoration:none;display:inline-block;transition:background .2s;}
    .btn-add:hover{background:#ff2020;}
    .flash{padding:12px 20px;margin-bottom:24px;font-size:14px;}
    .flash.ok{background:rgba(0,200,100,.1);border:1px solid rgba(0,200,100,.3);color:#4dff9a;}
    .flash.err{background:rgba(220,0,0,.1);border:1px solid rgba(220,0,0,.3);color:#ff6666;}
    .table-wrap{background:#141414;border:1px solid #1e1e1e;overflow:hidden;}
    .table-head{display:grid;grid-template-columns:1fr 140px 100px 140px 130px;gap:16px;padding:14px 20px;border-bottom:1px solid #1e1e1e;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#444;}
    .table-row{display:grid;grid-template-columns:1fr 140px 100px 140px 130px;gap:16px;align-items:center;padding:14px 20px;border-bottom:1px solid #111;transition:background .15s;}
    .table-row:last-child{border-bottom:none;}
    .table-row:hover{background:#1a1a1a;}
    .user-name{font-weight:500;font-size:14px;}
    .user-username{font-size:12px;color:#555;margin-top:2px;font-family:monospace;}
    .role-badge{display:inline-block;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;padding:3px 10px;border:1px solid;}
    .role-badge.admin{border-color:#dc0000;color:#dc0000;}
    .role-badge.editor{border-color:#555;color:#888;}
    .you-tag{font-size:10px;color:#555;margin-left:6px;letter-spacing:1px;}
    .actions{display:flex;gap:8px;}
    .btn-edit{background:none;border:1px solid #2a2a2a;color:#888;padding:7px 14px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;transition:all .2s;font-family:'Inter',sans-serif;letter-spacing:.5px;}
    .btn-edit:hover{border-color:#fff;color:#fff;}
    .btn-del{background:none;border:1px solid #2a2a2a;color:#555;padding:7px 14px;font-size:12px;cursor:pointer;font-family:'Inter',sans-serif;letter-spacing:.5px;transition:all .2s;}
    .btn-del:hover{border-color:#dc0000;color:#dc0000;}
    .btn-del:disabled{opacity:.3;cursor:not-allowed;pointer-events:none;}
    .empty{text-align:center;padding:60px;color:#333;font-size:14px;}
    .tip{font-size:12px;color:#444;margin-top:12px;}
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
      <h1 class="page-title">Users</h1>
      <a href="edit_user.php" class="btn-add">+ Add User</a>
    </div>

    <?php if ($flash): ?>
      <?php $cls = str_starts_with($flash, 'Error') ? 'err' : 'ok'; ?>
      <div class="flash <?= $cls ?>"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="table-wrap">
      <div class="table-head">
        <span>Name / Username</span>
        <span>Role</span>
        <span>Created</span>
        <span>Last Login</span>
        <span>Actions</span>
      </div>

      <?php if (empty($users)): ?>
        <div class="empty">No users found.</div>
      <?php else: ?>
        <?php foreach ($users as $u): ?>
        <?php $isMe = $u['id'] === $me; ?>
        <div class="table-row">
          <div>
            <div class="user-name">
              <?= htmlspecialchars($u['name'] ?? $u['username']) ?>
              <?php if ($isMe): ?><span class="you-tag">(you)</span><?php endif; ?>
            </div>
            <div class="user-username">@<?= htmlspecialchars($u['username']) ?></div>
          </div>
          <div><span class="role-badge <?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></div>
          <div style="font-size:13px;color:#555;"><?= htmlspecialchars($u['created'] ?? '—') ?></div>
          <div style="font-size:13px;color:#555;"><?= htmlspecialchars($u['last_login'] ?? '—') ?></div>
          <div class="actions">
            <a href="edit_user.php?id=<?= htmlspecialchars($u['id']) ?>" class="btn-edit">Edit</a>
            <form method="POST" action="delete_user.php"
                  onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'] ?? $u['username'])) ?>?')">
              <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>">
              <button type="submit" class="btn-del" <?= $isMe ? 'disabled title="Cannot delete yourself"' : '' ?>>
                Delete
              </button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <p class="tip">Admins can manage users, products, and decorated items. Editors can manage products and decorated items only.</p>
  </main>
</div>
</body>
</html>
