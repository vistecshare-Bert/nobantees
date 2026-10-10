<?php
require_once 'auth.php';
require_once 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $orderId = $_POST['orderId'] ?? '';
    $orders  = loadOrders();

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? 'pending';
        foreach ($orders as &$o) {
            if (($o['orderId'] ?? '') === $orderId) { $o['status'] = $status; break; }
        }
        unset($o);
        saveOrders($orders);
        $_SESSION['flash'] = 'Order updated.';
    } elseif ($action === 'delete') {
        $orders = array_filter($orders, fn($o) => ($o['orderId'] ?? '') !== $orderId);
        saveOrders($orders);
        $_SESSION['flash'] = 'Order deleted.';
    }
    // Return to the same filtered view the admin was looking at
    $back = array_filter(['status' => $_POST['f_status'] ?? '', 'q' => $_POST['f_q'] ?? '']);
    header('Location: orders.php' . ($back ? '?' . http_build_query($back) : ''));
    exit;
}

$orders = loadOrders();
$flash  = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

$counts = array_fill_keys($statuses, 0);
foreach ($orders as $o) { $s = $o['status'] ?? 'pending'; if (isset($counts[$s])) $counts[$s]++; }

// Filters: ?status=shipped and/or ?q=search text (order ID, customer, email, product)
$filterStatus = in_array($_GET['status'] ?? '', $statuses, true) ? $_GET['status'] : '';
$search       = trim($_GET['q'] ?? '');
$shown = array_filter($orders, function ($o) use ($filterStatus, $search) {
    if ($filterStatus && ($o['status'] ?? 'pending') !== $filterStatus) return false;
    if ($search === '') return true;
    $hay = ($o['orderId'] ?? '') . ' ' . ($o['customer']['name'] ?? '') . ' ' . ($o['customer']['email'] ?? '') . ' ' . ($o['customer']['phone'] ?? '');
    foreach (($o['items'] ?? []) as $it) $hay .= ' ' . ($it['name'] ?? '');
    return stripos($hay, $search) !== false;
});

// Older orders were saved without an image, so fall back to the product
// catalog — matched by product ID first, then by name.
$productsById = $productsByName = [];
foreach (loadProducts() as $p) {
    if (!empty($p['id']))   $productsById[$p['id']] = $p;
    if (!empty($p['name'])) $productsByName[strtolower($p['name'])] = $p;
}

function orderItemImage($it, $byId, $byName) {
    if (!empty($it['image'])) return $it['image'];
    $p = $byId[$it['id'] ?? ''] ?? $byName[strtolower($it['name'] ?? '')] ?? null;
    return $p['images'][0] ?? '';
}

// Product image paths are relative to the site root; this page lives in /admin.
function adminImgSrc($path) {
    if ($path === '' || preg_match('#^(https?:|data:image/|/)#i', $path)) return $path;
    return '../' . $path;
}

function orderStatusColor($s) {
    switch ($s) {
        case 'delivered':  return '#4dff9a';
        case 'shipped':    return '#dc0000';
        case 'processing': return '#4da3ff';
        case 'cancelled':  return '#666';
        default:           return '#e8a020';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders — NoBan Admin</title>
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
    .stats{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:32px;}
    .stat-card{background:#141414;border:1px solid #1e1e1e;padding:20px 24px;text-decoration:none;color:inherit;display:block;transition:border-color .2s;}
    .stat-card:hover{border-color:#333;}
    .stat-card.active{border-color:#dc0000;}
    .stat-num{font-family:'Bebas Neue',sans-serif;font-size:36px;color:#dc0000;line-height:1;}
    .stat-label{font-size:12px;color:#555;letter-spacing:2px;text-transform:uppercase;margin-top:4px;}
    .flash{background:rgba(0,200,100,.1);border:1px solid rgba(0,200,100,.3);color:#4dff9a;padding:12px 20px;margin-bottom:24px;font-size:14px;}
    .toolbar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;align-items:center;}
    .toolbar input{flex:1;min-width:220px;background:#141414;border:1px solid #2a2a2a;color:#fff;padding:10px 14px;font-size:13px;font-family:'Inter',sans-serif;}
    .toolbar input:focus{outline:none;border-color:#dc0000;}
    .toolbar button,.toolbar a{background:#dc0000;border:none;color:#fff;padding:10px 18px;font-size:12px;letter-spacing:1px;text-transform:uppercase;cursor:pointer;font-family:'Inter',sans-serif;text-decoration:none;}
    .toolbar a{background:none;border:1px solid #2a2a2a;color:#888;}
    .toolbar .count{font-size:12px;color:#555;margin-left:auto;}

    .order-card{background:#141414;border:1px solid #1e1e1e;margin-bottom:16px;}
    .order-head{display:flex;align-items:center;gap:16px;flex-wrap:wrap;padding:16px 20px;border-bottom:1px solid #1e1e1e;}
    .order-id{font-weight:600;font-size:15px;letter-spacing:.5px;}
    .order-date{font-size:12px;color:#555;}
    .status-pill{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;padding:4px 10px;border:1px solid currentColor;}
    .order-head .actions{margin-left:auto;}
    .order-body{display:grid;grid-template-columns:1fr 300px;}
    .order-items{padding:8px 20px;}
    .item{display:flex;gap:14px;align-items:center;padding:12px 0;border-bottom:1px solid #1a1a1a;}
    .item:last-child{border-bottom:none;}
    .thumb{width:72px;height:72px;flex-shrink:0;background:#0d0d0d;border:1px solid #222;display:flex;align-items:center;justify-content:center;overflow:hidden;font-size:10px;color:#333;text-align:center;}
    .thumb img{width:100%;height:100%;object-fit:cover;display:block;}
    a.thumb:hover{border-color:#dc0000;}
    .item-info{flex:1;min-width:0;}
    .item-name{font-size:14px;font-weight:600;}
    .item-sku{font-size:11px;color:#444;margin-left:6px;font-weight:400;}
    .chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;}
    .chip{font-size:11px;color:#aaa;background:#1c1c1c;border:1px solid #262626;padding:2px 8px;}
    .chip b{color:#666;font-weight:500;margin-right:4px;}
    .item-price{text-align:right;font-size:12px;color:#777;white-space:nowrap;}
    .item-price strong{display:block;color:#fff;font-size:14px;margin-top:2px;}
    .order-side{border-left:1px solid #1e1e1e;padding:16px 20px;font-size:12px;color:#888;line-height:1.6;}
    .side-label{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#444;margin:14px 0 4px;}
    .side-label:first-child{margin-top:0;}
    .side-strong{color:#ddd;font-size:13px;}
    .side-link{color:#888;text-decoration:none;}
    .side-link:hover{color:#dc0000;}
    .notes{background:#1a1500;border-left:2px solid #e8a020;color:#ccb;padding:8px 10px;white-space:pre-wrap;}
    .totals{margin-top:14px;border-top:1px solid #1e1e1e;padding-top:10px;}
    .totals div{display:flex;justify-content:space-between;}
    .totals .grand{color:#fff;font-weight:700;font-size:15px;margin-top:4px;}
    .totals .grand span:last-child{color:#dc0000;}
    @media (max-width:900px){
      .order-body{grid-template-columns:1fr;}
      .order-side{border-left:none;border-top:1px solid #1e1e1e;}
      .stats{grid-template-columns:repeat(3,1fr);}
    }
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
      <a href="orders.php" class="active">
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

  <main class="main">
    <div class="page-header">
      <h1 class="page-title">Orders</h1>
    </div>

    <?php if ($flash): ?>
      <div class="flash"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="stats">
      <?php foreach ($counts as $s => $n): ?>
      <a class="stat-card<?= $filterStatus === $s ? ' active' : '' ?>" href="?<?= http_build_query(array_filter(['status' => $filterStatus === $s ? '' : $s, 'q' => $search])) ?>" title="Click to filter">
        <div class="stat-num"><?= $n ?></div>
        <div class="stat-label"><?= ucfirst($s) ?></div>
      </a>
      <?php endforeach; ?>
    </div>

    <form class="toolbar" method="GET">
      <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
      <input type="search" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search order ID, customer, email, phone or product…">
      <button type="submit">Search</button>
      <?php if ($search || $filterStatus): ?><a href="orders.php">Clear</a><?php endif; ?>
      <span class="count"><?= count($shown) ?> of <?= count($orders) ?> orders</span>
    </form>

    <?php if (empty($orders)): ?>
      <div class="empty">No orders yet.</div>
    <?php elseif (empty($shown)): ?>
      <div class="empty">No orders match this filter.</div>
    <?php endif; ?>

    <?php foreach ($shown as $o):
      $status = $o['status'] ?? 'pending';
      $cust   = $o['customer'] ?? [];
      $ship   = $o['shippingAddress'] ?? null;
      $addr   = $cust['address'] ?? [];
      $itemsSubtotal = 0;
    ?>
    <div class="order-card">
      <div class="order-head">
        <span class="order-id"><?= htmlspecialchars($o['orderId'] ?? '') ?></span>
        <span class="order-date"><?= date('M j, Y g:ia', strtotime($o['paidAt'] ?? $o['date'] ?? 'now')) ?></span>
        <span class="status-pill" style="color:<?= orderStatusColor($status) ?>"><?= htmlspecialchars($status) ?></span>
        <div class="actions">
          <form method="POST" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="orderId" value="<?= htmlspecialchars($o['orderId'] ?? '') ?>">
            <input type="hidden" name="f_status" value="<?= htmlspecialchars($filterStatus) ?>">
            <input type="hidden" name="f_q" value="<?= htmlspecialchars($search) ?>">
            <select name="status" class="status-select" onchange="this.form.submit()">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </form>
          <form method="POST" onsubmit="return confirm('Delete order <?= htmlspecialchars($o['orderId'] ?? '', ENT_QUOTES) ?>? This cannot be undone.')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="orderId" value="<?= htmlspecialchars($o['orderId'] ?? '') ?>">
            <input type="hidden" name="f_status" value="<?= htmlspecialchars($filterStatus) ?>">
            <input type="hidden" name="f_q" value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-del">Delete</button>
          </form>
        </div>
      </div>

      <div class="order-body">
        <div class="order-items">
          <?php foreach (($o['items'] ?? []) as $it):
            $qty   = max(1, (int)($it['quantity'] ?? 1));
            $price = (float)($it['price'] ?? 0);
            $itemsSubtotal += $price * $qty;
            $img   = adminImgSrc(orderItemImage($it, $productsById, $productsByName));
          ?>
          <div class="item">
            <?php if ($img): ?>
              <a class="thumb" href="<?= htmlspecialchars($img) ?>" target="_blank" title="Open full image">
                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($it['name'] ?? 'Item') ?>" loading="lazy" onerror="this.parentNode.textContent='No photo'">
              </a>
            <?php else: ?>
              <div class="thumb">No photo</div>
            <?php endif; ?>
            <div class="item-info">
              <div class="item-name">
                <?= htmlspecialchars($it['name'] ?? 'Item') ?>
                <?php if (!empty($it['id'])): ?><span class="item-sku">#<?= htmlspecialchars($it['id']) ?></span><?php endif; ?>
              </div>
              <div class="chips">
                <?php if (!empty($it['size'])): ?><span class="chip"><b>Size</b><?= htmlspecialchars($it['size']) ?></span><?php endif; ?>
                <?php if (!empty($it['color'])): ?><span class="chip"><b>Color</b><?= htmlspecialchars($it['color']) ?></span><?php endif; ?>
                <?php if (!empty($it['printStyle'])): ?><span class="chip"><b>Print</b><?= htmlspecialchars($it['printStyle']) ?></span><?php endif; ?>
                <?php if (!empty($it['category'])): ?><span class="chip"><b>Type</b><?= htmlspecialchars(ucfirst($it['category'])) ?></span><?php endif; ?>
              </div>
            </div>
            <div class="item-price">
              <?= $qty ?> &times; $<?= number_format($price, 2) ?>
              <strong>$<?= number_format($price * $qty, 2) ?></strong>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="order-side">
          <div class="side-label">Customer</div>
          <?php if (!empty($cust['name'])): ?><div class="side-strong"><?= htmlspecialchars($cust['name']) ?></div><?php endif; ?>
          <?php if (!empty($cust['email'])): ?><div><a class="side-link" href="mailto:<?= htmlspecialchars($cust['email']) ?>"><?= htmlspecialchars($cust['email']) ?></a></div><?php endif; ?>
          <?php if (!empty($cust['phone'])): ?><div><a class="side-link" href="tel:<?= htmlspecialchars($cust['phone']) ?>"><?= htmlspecialchars($cust['phone']) ?></a></div><?php endif; ?>

          <div class="side-label">Ship to</div>
          <?php if ($ship || !empty($addr['line1'])):
            $line1 = $ship['line1'] ?? $addr['line1'] ?? '';
            $line2 = $ship['line2'] ?? '';
            $cityLine = trim(($ship['city'] ?? $addr['city'] ?? '') . ', ' . ($ship['state'] ?? $addr['state'] ?? '') . ' ' . ($ship['postal_code'] ?? $addr['zip'] ?? ''), ' ,');
          ?>
            <div><?= htmlspecialchars($ship['name'] ?? $cust['name'] ?? '') ?></div>
            <div><?= htmlspecialchars($line1) ?><?= $line2 ? ', ' . htmlspecialchars($line2) : '' ?></div>
            <div><?= htmlspecialchars($cityLine) ?></div>
          <?php else: ?>
            <div>No address on file</div>
          <?php endif; ?>

          <?php if (trim($o['notes'] ?? '') !== ''): ?>
            <div class="side-label">Customer notes</div>
            <div class="notes"><?= htmlspecialchars(trim($o['notes'])) ?></div>
          <?php endif; ?>

          <div class="totals">
            <div><span>Subtotal</span><span>$<?= number_format($o['subtotal'] ?? $itemsSubtotal, 2) ?></span></div>
            <?php if (isset($o['shipping'])): ?>
            <div><span>Shipping</span><span><?= (float)$o['shipping'] > 0 ? '$' . number_format($o['shipping'], 2) : 'Free' ?></span></div>
            <?php endif; ?>
            <div class="grand"><span>Total</span><span>$<?= number_format($o['total'] ?? $itemsSubtotal, 2) ?></span></div>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </main>
</div>
</body>
</html>
