<?php
// TEMP diagnostic — delete after use.
header('Content-Type: text/plain');

foreach (['orders.php', 'quotes.php', 'contacts.php'] as $f) {
    echo "=== $f ===\n";
    try {
        // Isolate each include in its own function scope so a successful one
        // (if any) doesn't leak variables/state into the next iteration.
        (function () use ($f) {
            include __DIR__ . '/' . $f;
        })();
        echo "(included without error -- unexpected given php -l failed it)\n\n";
    } catch (\Throwable $e) {
        echo get_class($e) . ': ' . $e->getMessage() . "\n";
        echo 'at ' . $e->getFile() . ':' . $e->getLine() . "\n\n";
    }
}
