<?php
// Copy this file to config.php and fill in real values.
// config.php is gitignored — it never gets committed or deployed from git.

// ─────────────────────────────────────────────
// Stripe API Keys — Replace with your real keys
// Get them from: https://dashboard.stripe.com/apikeys
// ─────────────────────────────────────────────
define('STRIPE_SECRET_KEY', 'sk_test_REPLACE_WITH_YOUR_KEY');

// Your live domain (used for redirect URLs after payment)
define('SITE_URL', 'https://nobantees.com');

// ─────────────────────────────────────────────
// Admin Panel Password — Change this!
// ─────────────────────────────────────────────
define('ADMIN_PASSWORD', 'REPLACE_ME');

// ─────────────────────────────────────────────
// Notification email — where order/quote/contact
// alerts get sent
// ─────────────────────────────────────────────
define('NOTIFY_EMAIL', 'you@example.com');
