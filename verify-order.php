<?php
require_once __DIR__ . '/config.php';

$sid = trim($_GET['sid'] ?? '');
if (!$sid || !preg_match('/^cs_/', $sid)) {
    header('Location: ' . SITE_URL . '/cart.html');
    exit;
}

// Verify payment status with Stripe — never trust the redirect alone
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sid));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => STRIPE_SECRET_KEY . ':',
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$res  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$session = json_decode($res, true);
$isPaid  = ($code === 200 && ($session['payment_status'] ?? '') === 'paid');

$pendingFile = __DIR__ . '/pending_orders/' . $sid . '.json';
$order = [];
if (file_exists($pendingFile)) {
    $order = json_decode(file_get_contents($pendingFile), true) ?? [];
}

if ($isPaid && !empty($order) && ($order['status'] ?? '') === 'pending_payment') {
    $stripeEmail = $session['customer_details']['email'] ?? '';
    $stripeName  = $session['customer_details']['name']  ?? '';

    $order['status']       = 'pending';
    $order['paidAt']       = date('c');
    $order['customer']     = ['email' => $stripeEmail, 'name' => $stripeName];

    $ordersFile = __DIR__ . '/orders.json';
    $orders = file_exists($ordersFile) ? (json_decode(file_get_contents($ordersFile), true) ?: []) : [];
    array_unshift($orders, $order);
    file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    @unlink($pendingFile);

    // ── Receipt email to customer ─────────────────────────────
    if ($stripeEmail) {
        $itemRows = '';
        foreach ($order['items'] ?? [] as $it) {
            $itName  = htmlspecialchars($it['name'] ?? 'Item');
            $itSize  = htmlspecialchars($it['size'] ?? '—');
            $itQty   = (int)($it['quantity'] ?? 1);
            $itPrice = number_format(floatval($it['price'] ?? 0) * $itQty, 2);
            $itemRows .= "
            <tr>
              <td style='padding:10px 0;border-bottom:1px solid #222;color:#fff;font-size:13px;'>{$itName} &mdash; Size {$itSize}</td>
              <td style='padding:10px 0;border-bottom:1px solid #222;color:#dc0000;font-size:13px;text-align:right;'>&times;{$itQty} &nbsp; \${$itPrice}</td>
            </tr>";
        }
        $orderTotal = number_format(floatval($order['total'] ?? 0), 2);

        $receiptHtml = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#0a0a0a;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#0a0a0a;padding:40px 20px;'>
<tr><td align='center'>
<table width='560' cellpadding='0' cellspacing='0' style='max-width:560px;width:100%;'>
  <tr><td style='padding-bottom:28px;'>
    <h1 style='font-family:Arial,sans-serif;font-size:26px;letter-spacing:2px;color:#dc0000;margin:0;'>NOBAN TEES</h1>
  </td></tr>
  <tr><td style='background:#181818;border:1px solid #222;border-radius:2px;padding:28px;'>
    <p style='color:#dc0000;font-size:11px;letter-spacing:2px;text-transform:uppercase;margin:0 0 6px;'>Order Confirmed</p>
    <h2 style='color:#fff;font-size:22px;margin:0 0 20px;'>Thank you" . ($stripeName ? ', ' . htmlspecialchars($stripeName) : '') . "!</h2>
    <p style='color:#888;font-size:13px;line-height:1.7;margin:0 0 24px;'>Your payment was received. We&rsquo;ll get your order ready and reach out with updates.</p>
    <p style='color:#888;font-size:11px;letter-spacing:1.5px;text-transform:uppercase;margin:0 0 4px;'>Order ID</p>
    <p style='color:#dc0000;font-size:18px;font-weight:bold;letter-spacing:2px;margin:0 0 20px;'>" . htmlspecialchars($order['orderId']) . "</p>
    <table width='100%' cellpadding='0' cellspacing='0'>{$itemRows}</table>
    <table width='100%' cellpadding='0' cellspacing='0' style='margin-top:16px;'>
      <tr>
        <td style='color:#fff;font-size:15px;font-weight:bold;padding-top:10px;border-top:1px solid #333;'>Total Charged</td>
        <td style='color:#dc0000;font-size:15px;font-weight:bold;padding-top:10px;border-top:1px solid #333;text-align:right;'>\${$orderTotal}</td>
      </tr>
    </table>
  </td></tr>
</table>
</td></tr></table>
</body></html>";

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: NoBan Tees <noreply@nobantees.com>\r\n";
        $headers .= "Reply-To: " . NOTIFY_EMAIL . "\r\n";
        @mail($stripeEmail, 'Order Confirmed — ' . $order['orderId'] . ' | NoBan Tees', $receiptHtml, $headers);
    }

    // ── Admin notification ──────────────────────────────────────
    $orderTotal = number_format(floatval($order['total'] ?? 0), 2);
    $adminLines = "New order received!\n\nOrder ID: {$order['orderId']}\nEmail: {$stripeEmail}\nTotal: \${$orderTotal}\n\n";
    foreach ($order['items'] ?? [] as $it) {
        $adminLines .= '  - ' . ($it['name'] ?? 'Item') . ' Size ' . ($it['size'] ?? '?') . ' ×' . (int)($it['quantity'] ?? 1) . "\n";
    }
    $adminLines .= "\nView in dashboard: " . SITE_URL . "/admin/orders.php";
    $aHeaders  = "From: NoBan Tees <noreply@nobantees.com>\r\n";
    $aHeaders .= "Reply-To: {$stripeEmail}\r\n";
    @mail(NOTIFY_EMAIL, 'New Order — ' . $order['orderId'], $adminLines, $aHeaders);
}

header('Location: ' . SITE_URL . '/success.html');
exit;
