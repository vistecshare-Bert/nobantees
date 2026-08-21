<?php
require_once 'auth.php';
require_once 'helpers.php';

$products = loadProducts();
$id = $_GET['id'] ?? null;
$product = null;
$isEdit = false;

if ($id) {
    foreach ($products as $p) {
        if ($p['id'] === $id) { $product = $p; $isEdit = true; break; }
    }
}

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $isEdit ? 'Edit Product' : 'Add Product' ?> — NoBan Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{background:#0a0a0a;color:#fff;font-family:'Inter',sans-serif;min-height:100vh;}
    .layout{display:flex;min-height:100vh;}
    .sidebar{width:220px;background:#111;border-right:1px solid #1e1e1e;padding:0;flex-shrink:0;position:sticky;top:0;height:100vh;}
    .sidebar-logo{padding:24px 20px;border-bottom:1px solid #1e1e1e;font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:2px;display:flex;align-items:center;gap:8px;}
    .sidebar-nav a{display:flex;align-items:center;gap:10px;padding:12px 20px;font-size:13px;color:#666;text-decoration:none;transition:all .2s;}
    .sidebar-nav a:hover{color:#fff;background:#1a1a1a;}
    .sidebar-nav a.active{color:#fff;background:#1a1a1a;border-left:2px solid #dc0000;}
    .sidebar-nav a svg{width:16px;height:16px;flex-shrink:0;}
    .sidebar-footer{position:absolute;bottom:0;width:100%;border-top:1px solid #1e1e1e;padding:16px 20px;}
    .sidebar-footer a{font-size:12px;color:#444;text-decoration:none;display:block;transition:color .2s;margin-bottom:8px;}
    .sidebar-footer a:hover{color:#dc0000;}
    .main{flex:1;padding:40px;max-width:800px;}
    .page-header{display:flex;align-items:center;gap:16px;margin-bottom:32px;}
    .back-btn{color:#555;text-decoration:none;font-size:13px;transition:color .2s;}
    .back-btn:hover{color:#fff;}
    .page-title{font-family:'Bebas Neue',sans-serif;font-size:40px;letter-spacing:2px;}
    .flash-ok{background:rgba(0,200,100,.1);border:1px solid rgba(0,200,100,.3);color:#4dff9a;padding:12px 20px;margin-bottom:24px;font-size:14px;}
    .form-card{background:#141414;border:1px solid #1e1e1e;padding:32px;}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
    .form-group{margin-bottom:24px;}
    .form-group.full{grid-column:1/-1;}
    label{display:block;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#555;margin-bottom:8px;}
    .required{color:#dc0000;}
    input[type=text],input[type=number],select,textarea{
      width:100%;background:#0a0a0a;border:1px solid #2a2a2a;color:#fff;
      padding:12px 16px;font-size:14px;font-family:'Inter',sans-serif;
      transition:border-color .2s;
    }
    input[type=text]:focus,input[type=number]:focus,select:focus,textarea:focus{outline:none;border-color:#dc0000;}
    select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23666' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;}
    select option{background:#111;}
    textarea{resize:vertical;min-height:100px;line-height:1.6;}
    /* IMAGE UPLOAD */
    .upload-area{border:2px dashed #2a2a2a;padding:32px;text-align:center;cursor:pointer;transition:border-color .2s;position:relative;}
    .upload-area:hover{border-color:#dc0000;}
    .upload-area input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
    .upload-icon{width:40px;height:40px;margin:0 auto 12px;color:#444;}
    .upload-label{font-size:14px;color:#555;margin-bottom:4px;}
    .upload-hint{font-size:12px;color:#333;}
    .preview-wrap{margin-top:16px;display:none;}
    .preview-wrap img{max-height:200px;max-width:100%;object-fit:contain;border:1px solid #2a2a2a;}
    .current-img{margin-bottom:12px;}
    .current-img img{height:120px;object-fit:cover;border:1px solid #2a2a2a;}
    .current-img p{font-size:12px;color:#555;margin-top:6px;}
    /* ACTIONS */
    .form-actions{display:flex;gap:12px;margin-top:8px;}
    .btn-save{background:#dc0000;color:#fff;border:none;padding:14px 40px;font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:2px;cursor:pointer;transition:background .2s;}
    .btn-save:hover{background:#ff2020;}
    .btn-cancel{background:none;border:1px solid #2a2a2a;color:#666;padding:14px 32px;font-family:'Inter',sans-serif;font-size:14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;transition:all .2s;}
    .btn-cancel:hover{border-color:#fff;color:#fff;}
    .hint{font-size:12px;color:#444;margin-top:6px;}
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
      <a href="edit.php" class="active">
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

  <main class="main">
    <div class="page-header">
      <a href="dashboard.php" class="back-btn">← Back</a>
      <h1 class="page-title"><?= $isEdit ? 'Edit Product' : 'Add Product' ?></h1>
    </div>

    <?php if ($flash): ?>
      <div class="flash-ok"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <form method="POST" action="save.php" enctype="multipart/form-data">
      <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($product['id']) ?>">
      <?php endif; ?>

      <div class="form-card">
        <div class="form-row">

          <div class="form-group">
            <label>Product Name <span class="required">*</span></label>
            <input type="text" name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" placeholder="e.g. Classic BAN. Hoodie" required>
          </div>

          <div class="form-group">
            <label>Category <span class="required">*</span></label>
            <select name="category" required>
              <option value="">— Select —</option>
              <?php foreach (['hoodies','shirts','pants'] as $cat): ?>
                <option value="<?= $cat ?>" <?= ($product['category'] ?? '') === $cat ? 'selected' : '' ?>><?= ucfirst($cat) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Price (USD) <span class="required">*</span></label>
            <input type="number" name="price" value="<?= htmlspecialchars($product['price'] ?? '') ?>" placeholder="65" min="1" step="0.01" required>
          </div>

          <div class="form-group">
            <label>Color / Colorway</label>
            <input type="text" name="color" value="<?= htmlspecialchars($product['color'] ?? '') ?>" placeholder="e.g. Black, Washed Red">
          </div>

          <div class="form-group full">
            <label>Description</label>
            <textarea name="description" placeholder="Short product description shown on store..."><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
          </div>

          <div class="form-group full">
            <label>Product Photo</label>

            <?php
              $currentImg = $product['image'] ?? '';
              $imgPath = dirname(__DIR__) . '/' . $currentImg;
              if ($isEdit && $currentImg && file_exists($imgPath)):
            ?>
              <div class="current-img">
                <img src="../<?= htmlspecialchars($currentImg) ?>?v=<?= filemtime($imgPath) ?>" alt="Current photo">
                <p>Current photo — upload a new one to replace it</p>
              </div>
            <?php endif; ?>

            <div class="upload-area" id="uploadArea">
              <input type="file" name="image" id="imageInput" accept=".jpg,.jpeg,.png,.webp">
              <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
              </svg>
              <p class="upload-label">Click to upload photo</p>
              <p class="upload-hint">JPG, PNG, WEBP — Max 10MB</p>
              <div class="preview-wrap" id="previewWrap">
                <img id="previewImg" src="" alt="Preview">
              </div>
            </div>
            <p class="hint">Photo will be saved and shown on the store automatically.</p>
          </div>

        </div>

        <div class="form-actions">
          <button type="submit" class="btn-save"><?= $isEdit ? 'Save Changes' : 'Add Product' ?></button>
          <a href="dashboard.php" class="btn-cancel">Cancel</a>
        </div>
      </div>
    </form>
  </main>
</div>

<script>
  document.getElementById('imageInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev) {
      document.getElementById('previewImg').src = ev.target.result;
      document.getElementById('previewWrap').style.display = 'block';
      document.querySelector('.upload-label').textContent = file.name;
    };
    reader.readAsDataURL(file);
  });
</script>
</body>
</html>
