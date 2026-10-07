<?php
// TEMP diagnostic — delete after use.
require_once 'auth.php';
header('Content-Type: text/plain');

foreach (['orders.php', 'quotes.php', 'contacts.php', 'dashboard.php', 'helpers.php'] as $f) {
    echo "=== $f ===\n";
    $out = [];
    $code = null;
    exec('php -l ' . escapeshellarg(__DIR__ . '/' . $f) . ' 2>&1', $out, $code);
    echo implode("\n", $out) . "\n(exit code: $code)\n\n";
}
