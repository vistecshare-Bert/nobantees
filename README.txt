NoBAN TEES — Website Setup Guide
==================================

STEP 1: Add Your Stripe Key
-----------------------------
1. Create a free account at stripe.com
2. Go to Developers > API Keys
3. Copy your Secret Key (starts with sk_test_ for testing, sk_live_ for real sales)
4. Open config.php and replace: sk_test_REPLACE_WITH_YOUR_KEY

STEP 2: Add Product Photos
-----------------------------
Put your product images in these folders:
  images/hoodies/h1.jpg through h5.jpg
  images/shirts/s1.jpg through s5.jpg
  images/pants/p1.jpg through p5.jpg

After adding images, open js/products.js and the site will show them automatically.
(The site currently shows "Photo Coming Soon" placeholders — this is normal.)

STEP 3: Upload to HostGator
-----------------------------
Upload ALL files to your public_html folder using:
- HostGator cPanel > File Manager, OR
- FTP client (FileZilla) using your HostGator FTP credentials

Upload everything: html files, css/, js/, images/, checkout.php, config.php

STEP 4: Enable HTTPS on HostGator
-----------------------------
Stripe requires HTTPS (SSL). In HostGator cPanel:
- Go to "SSL/TLS" or "Let's Encrypt SSL"
- Install a free SSL certificate for nobantees.com
- This is required for the checkout to work.

STEP 5: Update config.php for Live Sales
-----------------------------
When ready to accept real payments:
1. Go to Stripe dashboard and switch from Test to Live mode
2. Copy your live Secret Key (sk_live_...)
3. Update config.php with the live key

SITE STRUCTURE
==============
index.html     — Homepage
shop.html      — All products (filterable by category)
cart.html      — Shopping cart
success.html   — Order confirmed page
checkout.php   — Stripe payment session (server-side)
config.php     — Your Stripe API key goes here
css/style.css  — All styling
js/products.js — Product catalog (edit prices, names, descriptions here)
js/cart.js     — Cart logic (do not edit)

UPDATING PRICES / PRODUCT INFO
================================
Open js/products.js to change:
- Product names
- Prices
- Colors / descriptions

AUTO-DEPLOY
================================
Pushing to the "main" branch on GitHub automatically deploys to
nobantees.com within about a minute (GitHub Actions -> deploy.php).
Manual FTP/cPanel upload is no longer needed for code changes.

Files that only exist on the live server and are never overwritten by
a deploy: config.php, orders.json, users.json, pending_orders/,
quotes/, contacts/, data/products.json, js/products.js,
data/decorated_products.json, data/decorated_categories.json,
data/users.json, images/hoodies/, images/shirts/, images/pants/.

==================================
Need help? Visit: nobantees.com
