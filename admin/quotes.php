<?php
require_once 'auth.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = $_POST['id']     ?? '';

    if ($action === 'update_status') {
        $quotes = loadQuotes();
        foreach ($quotes as $q) {
            if (($q['id'] ?? '') === $id) {
                $q['status'] = $_POST['status'] ?? 'new';
                saveQuote($q);
                break;
            }
        }
        $_SESSION['flash'] = 'Quote updated.';
    } elseif ($action === 'delete') {
        deleteQuote($id);
        $_SESSION['flash'] = 'Quote deleted.';
    }
    header('Location: quotes.php');
    exit;
}

$quotes = loadQuotes();
$flash  = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$statuses = ['new', 'contacted', 'closed'];

function quoteStatusColor($s) {
    return match($s) {
        'closed'    => '#4dff9a',
        'contacted' => '#dc0000',
        default     => '#e8a020',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quotes — NoBan Admin</title>
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
    .flash{background:rgba(0,200,100,.1);border:1px solid rgba(0,200,100,.3);color:#4dff9a;padding:12px 20px;margin-bottom:24px;font-size:14px;}
    .table-wrap{background:#141414;border:1px solid #1e1e1e;overflow:hidden;}
    .table-head{display:grid;grid-template-columns:170px 1fr 140px 130px 140px;gap:16px;padding:14px 20px;border-bottom:1px solid #1e1e1e;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#444;}
    .table-row{display:grid;grid-template-columns:170px 1fr 140px 130px 140px;gap:16px;align-items:center;padding:14px 20px;border-bottom:1px solid #111;transition:background .15s;}
    .table-row:last-child{border-bottom:none;}
    .table-row:hover{background:#1a1a1a;}
    .q-id{font-weight:600;font-size:13px;}
    .q-date{font-size:11px;color:#555;margin-top:2px;}
    .q-detail{font-size:12px;color:#888;line-height:1.5;}
    select.status-select{background:#1a1a1a;border:1px solid #2a2a2a;color:#fff;padding:6px 10px;font-size:12px;font-family:'Inter',sans-serif;cursor:pointer;}
    .btn-del{background:none;border:1px solid #2a2a2a;color:#555;padding:7px 14px;font-size:12px;cursor:pointer;font-family:'Inter',sans-serif;letter-spacing:.5px;transition:all .2s;}
    .btn-del:hover{border-color:#dc0000;color:#dc0000;}
    .empty{text-align:center;padding:60px;color:#333;font-size:14px;}
    .actions{display:flex;align-items:center;gap:8px;}
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
      <a href="quotes.php" class="active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Quotes
      </a>
      <a href="contacts.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Contacts
      </a>
      <?php if (isAdmin()): ?>
      <a href="users.php">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Users
      </a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="../index.html" target="_blank">← View Store</a>
      <a href="logout.php">Logout</a>
    </div>
  </aside>

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Quotes</h1>
    </div>

    <?php if ($flash): ?>
      <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="table-wrap">
      <div class="table-head">
        <span>Quote</span>
        <span>Details</span>
        <span>Qty / Budget</span>
        <span>Status</span>
        <span>Actions</span>
      </div>

      <?php if (empty($quotes)): ?>
        <div class="empty">No quote requests yet.</div>
      <?php else: ?>
        <?php foreach ($quotes as $q): ?>
        <div class="table-row">
          <div>
            <div class="q-id"><?= htmlspecialchars($q['id'] ?? '') ?></div>
            <div class="q-date"><?= htmlspecialchars($q['name'] ?? '') ?></div>
            <div class="q-date"><?= htmlspecialchars($q['email'] ?? '') ?></div>
          </div>
          <div class="q-detail">
            <?= htmlspecialchars($q['item'] ?? '—') ?> &middot; Size: <?= htmlspecialchars($q['size'] ?: '—') ?><br>
            <?= nl2br(htmlspecialchars($q['notes'] ?? '')) ?>
          </div>
          <div class="q-detail">
            Qty: <?= htmlspecialchars($q['qty'] ?: '—') ?><br>
            Budget: <?= htmlspecialchars($q['budget'] ?: '—') ?>
          </div>
          <div>
            <span style="color:<?= quoteStatusColor($q['status'] ?? 'new') ?>;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;"><?= htmlspecialchars($q['status'] ?? 'new') ?></span>
          </div>
          <div class="actions">
            <form method="POST" style="display:flex;gap:8px;align-items:center;">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="id" value="<?= htmlspecialchars($q['id'] ?? '') ?>">
              <select name="status" class="status-select" onchange="this.form.submit()">
                <?php foreach ($statuses as $s): ?>
                  <option value="<?= $s ?>" <?= ($q['status'] ?? 'new') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </form>
            <form method="POST" onsubmit="return confirm('Delete this quote?')">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= htmlspecialchars($q['id'] ?? '') ?>">
              <button type="submit" class="btn-del">Delete</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
