<?php
session_start();

$user = $_SESSION['nb_customer'] ?? null;

// Get this user's orders if logged in
$myOrders = [];
if ($user) {
    $ordersFile = __DIR__ . '/orders.json';
    if (file_exists($ordersFile)) {
        $all      = json_decode(file_get_contents($ordersFile), true) ?: [];
        $email    = strtolower($user['email'] ?? '');
        $myOrders = array_values(array_filter($all, fn($o) => strtolower($o['customer']['email'] ?? '') === $email));
    }
}

function nbStatusColor($s) {
    return match($s) {
        'delivered' => '#4dff9a',
        'shipped'   => '#dc0000',
        'cancelled' => '#888',
        default     => '#e8a020',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>My Account — NoBan Tees</title>
<link rel="stylesheet" href="css/style.css">
<style>
body{background:#0a0a0a;color:#fff;margin:0;font-family:sans-serif;}
.navbar{display:flex;align-items:center;justify-content:space-between;padding:0 40px;height:64px;background:#0a0a0a;border-bottom:1px solid #222;}
.ban-sign{width:30px!important;height:30px!important;flex-shrink:0;}
.nav-cart svg{width:22px!important;height:22px!important;}
.nav-links{display:flex;gap:32px;list-style:none;}
svg{overflow:visible;}

.page{max-width:900px;margin:0 auto;padding:56px 28px 100px;}

.profile-card{display:flex;align-items:center;gap:24px;background:var(--card);border:1px solid var(--border);padding:28px 32px;margin-bottom:48px;}
.profile-avatar-placeholder{width:72px;height:72px;border-radius:50%;border:3px solid var(--red);background:#181818;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-family:var(--font-head);font-size:26px;color:var(--red);}
.profile-info h1{font-family:var(--font-head);font-size:30px;letter-spacing:1px;color:#fff;margin-bottom:4px;}
.profile-info p{font-size:13px;color:var(--gray);}
.profile-info .joined{font-size:11px;color:#555;margin-top:6px;letter-spacing:1px;text-transform:uppercase;}
.profile-actions{margin-left:auto;display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end;}
.btn-outline-red{background:none;border:1px solid #333;color:#fff;padding:9px 18px;font-size:12px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;font-family:inherit;text-decoration:none;display:inline-block;transition:all .15s;}
.btn-outline-red:hover{border-color:var(--red);color:var(--red);}

.not-logged-in{text-align:center;padding:80px 20px;}
.not-logged-in h2{font-family:var(--font-head);font-size:34px;letter-spacing:1px;color:#fff;margin-bottom:12px;}
.not-logged-in p{font-size:14px;color:var(--gray);margin-bottom:32px;}

.order-card{background:var(--card);border:1px solid var(--border);margin-bottom:16px;overflow:hidden;}
.order-head{padding:14px 20px;background:#141414;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
.order-id{font-family:var(--font-head);font-size:18px;letter-spacing:1px;color:#fff;}
.order-date{font-size:12px;color:var(--gray);}
.order-total{font-weight:700;color:var(--red);margin-left:auto;font-size:15px;}
.order-status{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;}
.order-body{padding:16px 20px;}
.order-items{list-style:none;display:flex;flex-direction:column;gap:10px;}
.order-item{display:flex;align-items:center;gap:12px;padding:8px 0;border-bottom:1px solid var(--border);}
.order-item:last-child{border-bottom:none;}
.order-item-name{font-size:13px;color:#fff;flex:1;}
.order-item-size{font-size:12px;color:var(--gray);}
.order-item-price{font-size:13px;color:var(--red);white-space:nowrap;font-weight:600;}
.empty-orders{text-align:center;padding:48px 20px;color:var(--gray);}
.empty-orders a{color:var(--red);}

@media(max-width:600px){
  .profile-card{flex-direction:column;text-align:center;}
  .profile-actions{margin-left:0;justify-content:center;}
  .order-total{margin-left:0;}
}
</style>
</head>
<body>

<nav class="navbar">
  <a href="index.html" class="nav-logo">
    <svg class="ban-sign" width="30" height="30" viewBox="0 0 30 30" fill="none">
      <circle cx="15" cy="15" r="13" stroke="#dc0000" stroke-width="2.5"/>
      <line x1="4.5" y1="4.5" x2="25.5" y2="25.5" stroke="#dc0000" stroke-width="2.5" stroke-linecap="round"/>
    </svg>
    BAN.
  </a>
  <ul class="nav-links">
    <li><a href="index.html">Home</a></li>
    <li><a href="shop.html">Shop All</a></li>
  </ul>
  <a href="cart.html" class="nav-cart">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
      <line x1="3" y1="6" x2="21" y2="6"/>
      <path d="M16 10a4 4 0 01-8 0"/>
    </svg>
    <span class="cart-count">0</span>
  </a>
</nav>

<div class="page">

<?php if (!$user): ?>
  <div class="not-logged-in">
    <h2>Sign In to View Your Account</h2>
    <p>Track your orders and manage your profile.</p>
    <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;">
      <button class="btn-outline-red" onclick="nbAuth.open('login')">Login</button>
      <button class="btn-outline-red" onclick="nbAuth.open('signup')">Sign Up</button>
    </div>
  </div>

<?php else: ?>
  <div class="profile-card">
    <div class="profile-avatar-placeholder"><?= htmlspecialchars(strtoupper(substr($user['name'] ?? '?', 0, 1))) ?></div>
    <div class="profile-info">
      <h1><?= htmlspecialchars($user['name'] ?? 'Account') ?></h1>
      <p><?= htmlspecialchars($user['email'] ?? '') ?></p>
      <p class="joined">Member since <?= date('M Y', strtotime($user['createdAt'] ?? 'now')) ?> &nbsp;&middot;&nbsp; <?= count($myOrders) ?> order<?= count($myOrders) !== 1 ? 's' : '' ?></p>
    </div>
    <div class="profile-actions">
      <a href="shop.html" class="btn-outline-red">Shop Now</a>
      <button class="btn-outline-red" onclick="nbAuth.logout()">Sign Out</button>
    </div>
  </div>

  <div id="orders">
    <p class="section-label">Order History</p>

    <?php if (empty($myOrders)): ?>
      <div class="empty-orders">
        <p>No orders yet. <a href="shop.html">Start shopping &rarr;</a></p>
      </div>
    <?php else: ?>
      <?php foreach ($myOrders as $o):
        $status = $o['status'] ?? 'pending';
        $items  = $o['items']  ?? [];
      ?>
      <div class="order-card">
        <div class="order-head">
          <span class="order-id"><?= htmlspecialchars($o['orderId'] ?? '') ?></span>
          <span class="order-date"><?= date('M j, Y', strtotime($o['date'] ?? 'now')) ?></span>
          <span class="order-status" style="background:#141414;color:<?= nbStatusColor($status) ?>;border:1px solid <?= nbStatusColor($status) ?>44;"><?= ucfirst($status) ?></span>
          <span class="order-total">$<?= number_format($o['total'] ?? 0, 2) ?></span>
        </div>
        <div class="order-body">
          <ul class="order-items">
            <?php foreach ($items as $item): ?>
            <li class="order-item">
              <span class="order-item-name">
                <?= htmlspecialchars($item['name'] ?? '') ?>
                <span class="order-item-size"> &mdash; Size <?= htmlspecialchars($item['size'] ?? '') ?></span>
              </span>
              <span class="order-item-price">$<?= number_format(floatval($item['price'] ?? 0), 2) ?> &times;<?= intval($item['quantity'] ?? 1) ?></span>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

</div>

<script src="js/auth-modal.js"></script>
</body>
</html>
