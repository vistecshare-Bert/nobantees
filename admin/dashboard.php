<?php
require_once 'auth.php';
require_once 'helpers.php';

$products = loadProducts();
$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);

$counts = ['hoodies'=>0,'shirts'=>0,'pants'=>0];
foreach ($products as $p) { if (isset($counts[$p['category']])) $counts[$p['category']]++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — NoBan Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{background:#0a0a0a;color:#fff;font-family:'Inter',sans-serif;min-height:100vh;}
    /* SIDEBAR */
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
    /* MAIN */
    .main{flex:1;padding:40px;overflow-x:hidden;}
    .page-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;}
    .page-title{font-family:'Bebas Neue',sans-serif;font-size:40px;letter-spacing:2px;}
    .btn-add{background:#dc0000;color:#fff;border:none;padding:12px 28px;font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;cursor:pointer;text-decoration:none;display:inline-block;transition:background .2s;}
    .btn-add:hover{background:#ff2020;}
    /* STATS */
    .stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:32px;}
    .stat-card{background:#141414;border:1px solid #1e1e1e;padding:20px 24px;}
    .stat-num{font-family:'Bebas Neue',sans-serif;font-size:40px;color:#dc0000;line-height:1;}
    .stat-label{font-size:12px;color:#555;letter-spacing:2px;text-transform:uppercase;margin-top:4px;}
    /* FLASH */
    .flash{background:rgba(0,200,100,.1);border:1px solid rgba(0,200,100,.3);color:#4dff9a;padding:12px 20px;margin-bottom:24px;font-size:14px;}
    /* TABLE */
    .table-wrap{background:#141414;border:1px solid #1e1e1e;overflow:hidden;}
    .table-head{display:grid;grid-template-columns:28px 64px 1fr 120px 80px 100px 120px;gap:16px;padding:14px 20px;border-bottom:1px solid #1e1e1e;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#444;align-items:center;}
    .table-row{display:grid;grid-template-columns:28px 64px 1fr 120px 80px 100px 120px;gap:16px;align-items:center;padding:14px 20px;border-bottom:1px solid #111;transition:background .15s;}
    .table-row:last-child{border-bottom:none;}
    .table-row:hover{background:#1a1a1a;}
    .thumb{width:52px;height:64px;object-fit:cover;background:#1a1a1a;display:flex;align-items:center;justify-content:center;font-size:9px;color:#333;flex-shrink:0;position:relative;}
    .thumb img{width:100%;height:100%;object-fit:cover;}
    .prod-name{font-weight:500;font-size:14px;line-height:1.3;}
    .prod-color{font-size:12px;color:#555;margin-top:2px;}
    .cat-badge{display:inline-block;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;padding:3px 10px;border:1px solid #2a2a2a;color:#666;}
    .cat-badge.hoodies{border-color:#333;color:#aaa;}
    .cat-badge.shirts{border-color:#333;color:#aaa;}
    .cat-badge.pants{border-color:#333;color:#aaa;}
    .price{font-weight:600;font-size:15px;}
    .actions{display:flex;gap:8px;}
    .btn-edit{background:none;border:1px solid #2a2a2a;color:#888;padding:7px 14px;font-size:12px;cursor:pointer;text-decoration:none;display:inline-block;transition:all .2s;font-family:'Inter',sans-serif;letter-spacing:.5px;}
    .btn-edit:hover{border-color:#fff;color:#fff;}
    .btn-del{background:none;border:1px solid #2a2a2a;color:#555;padding:7px 14px;font-size:12px;cursor:pointer;font-family:'Inter',sans-serif;letter-spacing:.5px;transition:all .2s;}
    .btn-del:hover{border-color:#dc0000;color:#dc0000;}
    .empty{text-align:center;padding:60px;color:#333;font-size:14px;}
    /* BULK SELECT */
    .row-check,#selectAll{width:16px;height:16px;accent-color:#dc0000;cursor:pointer;}
    .bulk-bar{display:none;align-items:center;justify-content:space-between;background:#1a1010;border:1px solid #dc0000;padding:12px 20px;margin-bottom:16px;font-size:14px;}
    .bulk-bar.show{display:flex;}
    .bulk-bar span{color:#fff;}
    .btn-bulk-del{background:#dc0000;color:#fff;border:none;padding:9px 20px;font-family:'Bebas Neue',sans-serif;font-size:15px;letter-spacing:1.5px;cursor:pointer;transition:background .2s;}
    .btn-bulk-del:hover{background:#ff2020;}
    .btn-clear-sel{background:none;border:none;color:#888;font-size:12px;cursor:pointer;text-decoration:underline;margin-left:16px;font-family:'Inter',sans-serif;}
    .btn-clear-sel:hover{color:#fff;}
  </style>
</head>
<body>
<div class="layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <svg width="24" height="24" viewBox="0 0 30 30" fill="none">
        <circle cx="15" cy="15" r="13" stroke="#dc0000" stroke-width="2.5"/>
        <line x1="4.5" y1="4.5" x2="25.5" y2="25.5" stroke="#dc0000" stroke-width="2.5" stroke-linecap="round"/>
      </svg>
      BAN.
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php" class="active">
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

  <!-- MAIN -->
  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Products</h1>
      <a href="edit.php" class="btn-add">+ Add Product</a>
    </div>

    <?php if ($flash): ?>
      <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats">
      <div class="stat-card">
        <div class="stat-num"><?= count($products) ?></div>
        <div class="stat-label">Total Products</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $counts['hoodies'] ?></div>
        <div class="stat-label">Hoodies</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $counts['shirts'] ?></div>
        <div class="stat-label">Shirts</div>
      </div>
      <div class="stat-card">
        <div class="stat-num"><?= $counts['pants'] ?></div>
        <div class="stat-label">Pants</div>
      </div>
    </div>

    <!-- BULK ACTIONS -->
    <div class="bulk-bar" id="bulkBar">
      <span><strong id="bulkCount">0</strong> selected</span>
      <div>
        <button type="button" class="btn-bulk-del" onclick="bulkDelete()">Delete Selected</button>
        <button type="button" class="btn-clear-sel" onclick="clearSelection()">Clear</button>
      </div>
    </div>

    <!-- PRODUCT TABLE -->
    <div class="table-wrap">
      <div class="table-head">
        <span><input type="checkbox" id="selectAll" onchange="toggleAll(this)" <?= empty($products) ? 'disabled' : '' ?>></span>
        <span>Photo</span>
        <span>Product</span>
        <span>Category</span>
        <span>Price</span>
        <span>Color</span>
        <span>Actions</span>
      </div>

      <?php if (empty($products)): ?>
        <div class="empty">No products yet. <a href="edit.php" style="color:#dc0000;">Add your first product →</a></div>
      <?php else: ?>
        <?php foreach ($products as $p): ?>
        <div class="table-row">
          <div><input type="checkbox" class="row-check" value="<?= htmlspecialchars($p['id']) ?>" onchange="updateBulkBar()"></div>
          <div class="thumb">
            <?php
              $thumbImg = normalizeProductImages($p)['images'][0] ?? '';
              $imgPath  = dirname(__DIR__) . '/' . $thumbImg;
              if ($thumbImg && file_exists($imgPath)):
            ?>
              <img src="../<?= htmlspecialchars($thumbImg) ?>?v=<?= filemtime($imgPath) ?>" alt="">
              <?php if (count(normalizeProductImages($p)['images']) > 1): ?>
                <span style="position:absolute;bottom:2px;right:2px;background:rgba(0,0,0,.7);color:#fff;font-size:9px;padding:1px 5px;border-radius:8px;">+<?= count(normalizeProductImages($p)['images']) - 1 ?></span>
              <?php endif; ?>
            <?php else: ?>
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <?php endif; ?>
          </div>
          <div>
            <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="prod-color"><?= htmlspecialchars($p['description'] ?? '') ?></div>
          </div>
          <div><span class="cat-badge <?= htmlspecialchars($p['category']) ?>"><?= htmlspecialchars($p['category']) ?></span></div>
          <div class="price">$<?= number_format($p['price'], 2) ?></div>
          <div style="font-size:13px;color:#777;"><?= htmlspecialchars($p['color'] ?? '') ?></div>
          <div class="actions">
            <a href="edit.php?id=<?= htmlspecialchars($p['id']) ?>" class="btn-edit">Edit</a>
            <form method="POST" action="delete.php" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($p['name'])) ?>?')">
              <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
              <button type="submit" class="btn-del">Delete</button>
            </form>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>
</div>
<script>
  function getChecked() {
    return [...document.querySelectorAll('.row-check')].filter(c => c.checked);
  }

  function updateBulkBar() {
    const checked = getChecked();
    const bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = checked.length;
    bar.classList.toggle('show', checked.length > 0);

    const all = [...document.querySelectorAll('.row-check')];
    const selectAll = document.getElementById('selectAll');
    if (selectAll) selectAll.checked = all.length > 0 && checked.length === all.length;
  }

  function toggleAll(box) {
    document.querySelectorAll('.row-check').forEach(c => c.checked = box.checked);
    updateBulkBar();
  }

  function clearSelection() {
    document.querySelectorAll('.row-check').forEach(c => c.checked = false);
    updateBulkBar();
  }

  function bulkDelete() {
    const ids = getChecked().map(c => c.value);
    if (!ids.length) return;
    if (!confirm(`Delete ${ids.length} product(s)? This cannot be undone.`)) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'bulk_delete.php';
    ids.forEach(id => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'ids[]';
      input.value = id;
      form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
  }
</script>
</body>
</html>
