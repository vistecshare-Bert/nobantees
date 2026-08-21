<?php
require_once 'auth.php';

$catFile  = dirname(__DIR__) . '/data/decorated_categories.json';
$prodFile = dirname(__DIR__) . '/data/decorated_products.json';
$categories = file_exists($catFile)  ? (json_decode(file_get_contents($catFile),  true) ?: []) : [];
$decProducts = file_exists($prodFile) ? (json_decode(file_get_contents($prodFile), true) ?: []) : [];

// Edit mode
$editId = $_GET['edit'] ?? null;
$editProduct = null;
if ($editId) {
    foreach ($decProducts as $p) { if ($p['id'] === $editId) { $editProduct = $p; break; } }
}

// Delete
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $decProducts = array_values(array_filter($decProducts, fn($p) => $p['id'] !== $delId));
    file_put_contents($prodFile, json_encode($decProducts, JSON_PRETTY_PRINT));
    header('Location: decorated.php'); exit;
}

$flash = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Decorated Products — NoBan Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#0f0f0f;color:#fff;font-family:'Inter',sans-serif;min-height:100vh;}
.layout{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:220px;background:#111;border-right:1px solid #1e1e1e;flex-shrink:0;position:sticky;top:0;height:100vh;display:flex;flex-direction:column;}
.sidebar-logo{padding:20px;border-bottom:1px solid #1e1e1e;font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:2px;display:flex;align-items:center;gap:8px;}
.sidebar-nav{padding:12px 0;flex:1;}
.sidebar-nav a{display:flex;align-items:center;gap:10px;padding:11px 20px;font-size:13px;color:#666;text-decoration:none;transition:all .15s;}
.sidebar-nav a:hover{color:#fff;background:#1a1a1a;}
.sidebar-nav a.active{color:#fff;background:#1a1a1a;border-left:2px solid #dc0000;}
.sidebar-nav a svg{width:15px;height:15px;flex-shrink:0;}
.sidebar-section{padding:8px 20px 4px;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#333;margin-top:8px;}
.sidebar-footer{border-top:1px solid #1e1e1e;padding:16px 20px;}
.sidebar-footer a{font-size:12px;color:#444;text-decoration:none;display:block;margin-bottom:6px;transition:color .2s;}
.sidebar-footer a:hover{color:#dc0000;}

/* MAIN */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden;}

/* TOOLBAR */
.toolbar{background:#111;border-bottom:1px solid #1e1e1e;padding:0 20px;display:flex;align-items:center;gap:4px;height:48px;}
.tb-btn{background:none;border:1px solid transparent;color:#666;padding:7px 12px;font-size:12px;cursor:pointer;font-family:'Inter',sans-serif;display:flex;align-items:center;gap:6px;transition:all .15s;border-radius:3px;}
.tb-btn:hover{border-color:#2a2a2a;color:#fff;background:#1a1a1a;}
.tb-btn.active{border-color:#dc0000;color:#dc0000;}
.tb-btn svg{width:14px;height:14px;}
.tb-sep{width:1px;height:24px;background:#1e1e1e;margin:0 4px;}
.tb-btn:disabled{opacity:.3;cursor:not-allowed;}

/* EDITOR BODY */
.editor-body{display:flex;flex:1;overflow:hidden;}

/* MOCKUP COLUMN */
.mockup-col{flex:1;display:flex;flex-direction:column;border-right:1px solid #1e1e1e;background:#0a0a0a;}
.mockup-area{flex:1;display:flex;align-items:center;justify-content:center;padding:24px;position:relative;overflow:hidden;}

/* GARMENT MOCKUP */
.garment-wrap{position:relative;width:300px;height:380px;flex-shrink:0;}
#garmentSvg{width:300px;height:380px;filter:drop-shadow(0 8px 32px rgba(0,0,0,.6));}
.print-zone{position:absolute;border:2px dashed #e08000;cursor:crosshair;overflow:hidden;}
.design-el{position:absolute;cursor:move;user-select:none;display:flex;align-items:center;justify-content:center;}
.design-el.selected{outline:2px solid #dc0000;}
.design-el.text-el{font-weight:600;white-space:nowrap;text-shadow:0 1px 3px rgba(0,0,0,.5);}
.design-el.img-el img{width:100%;height:100%;object-fit:contain;pointer-events:none;}
.resize-handle{position:absolute;bottom:-4px;right:-4px;width:10px;height:10px;background:#dc0000;cursor:se-resize;display:none;}
.design-el.selected .resize-handle{display:block;}

/* GARMENT CONTROLS */
.garment-controls{background:#111;border-top:1px solid #1e1e1e;padding:14px 20px;}
.garment-tabs{display:flex;gap:4px;margin-bottom:12px;}
.g-tab{background:none;border:1px solid #2a2a2a;color:#666;padding:7px 14px;font-size:12px;cursor:pointer;font-family:'Inter',sans-serif;transition:all .15s;}
.g-tab.active{background:#e08000;border-color:#e08000;color:#fff;}
.controls-row{display:flex;align-items:center;justify-content:space-between;}
.color-row{display:flex;gap:8px;align-items:center;}
.g-color{width:26px;height:26px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:transform .15s;}
.g-color:hover{transform:scale(1.15);}
.g-color.active{border-color:#fff;transform:scale(1.1);}
.view-toggle{display:flex;gap:0;}
.v-btn{background:none;border:1px solid #2a2a2a;color:#666;padding:7px 16px;font-size:12px;cursor:pointer;font-family:'Inter',sans-serif;transition:all .15s;}
.v-btn:first-child{border-right:none;}
.v-btn.active{background:#e08000;border-color:#e08000;color:#fff;}

/* FORM COLUMN */
.form-col{width:310px;flex-shrink:0;overflow-y:auto;background:#111;display:flex;flex-direction:column;}
.form-header{padding:20px 20px 12px;border-bottom:1px solid #1e1e1e;}
.form-header h2{font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:1px;color:#aaa;}
.form-body{padding:16px 20px;flex:1;}
.fg{margin-bottom:16px;}
label.fl{display:block;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#555;margin-bottom:6px;}
.fi{width:100%;background:#0a0a0a;border:1px solid #222;color:#fff;padding:10px 12px;font-size:13px;font-family:'Inter',sans-serif;transition:border-color .2s;}
.fi:focus{outline:none;border-color:#dc0000;}
select.fi{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23666' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;}
select.fi option{background:#111;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:10px;}

/* SIZE TOGGLES */
.size-grid{display:flex;flex-wrap:wrap;gap:6px;}
.sz-btn{background:none;border:1px solid #2a2a2a;color:#555;padding:6px 12px;font-size:12px;cursor:pointer;font-family:'Inter',sans-serif;transition:all .15s;min-width:40px;text-align:center;}
.sz-btn.active{border-color:#dc0000;color:#dc0000;background:rgba(220,0,0,.08);}

/* COLOR CIRCLES */
.color-circles{display:flex;flex-wrap:wrap;gap:8px;}
.cc{width:28px;height:28px;border-radius:50%;cursor:pointer;border:2px solid transparent;transition:transform .15s;position:relative;}
.cc:hover{transform:scale(1.15);}
.cc.active::after{content:'';position:absolute;inset:-4px;border:2px solid #dc0000;border-radius:50%;}

/* FORM ACTIONS */
.form-actions{padding:16px 20px;border-top:1px solid #1e1e1e;display:flex;gap:8px;}
.btn-save{flex:1;background:#dc0000;color:#fff;border:none;padding:13px;font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;cursor:pointer;transition:background .2s;}
.btn-save:hover{background:#ff2020;}
.btn-cancel-form{background:none;border:1px solid #2a2a2a;color:#666;padding:13px 20px;font-family:'Inter',sans-serif;font-size:13px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;transition:all .2s;}
.btn-cancel-form:hover{border-color:#fff;color:#fff;}

/* PRODUCT LIST */
.prod-list{padding:0 20px 20px;}
.prod-list h3{font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:1px;margin-bottom:12px;padding-top:20px;}
.prod-table{width:100%;border-collapse:collapse;}
.prod-table th{font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#444;padding:10px 12px;border-bottom:1px solid #1e1e1e;text-align:left;}
.prod-table td{padding:12px;border-bottom:1px solid #111;font-size:13px;vertical-align:middle;}
.prod-table tr:hover td{background:#141414;}
.swatch-dot{display:inline-block;width:14px;height:14px;border-radius:50%;border:1px solid rgba(255,255,255,.1);}
.badge-chip{display:inline-block;background:#1a1a1a;border:1px solid #2a2a2a;font-size:10px;padding:2px 8px;color:#888;letter-spacing:1px;}
.act-link{font-size:12px;color:#555;text-decoration:none;padding:5px 10px;border:1px solid #222;display:inline-block;transition:all .2s;}
.act-link:hover{border-color:#fff;color:#fff;}
.act-link.del:hover{border-color:#dc0000;color:#dc0000;}

/* CATEGORIES */
.cat-section{background:#111;border-top:1px solid #1e1e1e;padding:20px;}
.cat-section h3{font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:1px;margin-bottom:14px;}
.cat-tags{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px;}
.cat-tag{background:#1a1a1a;border:1px solid #2a2a2a;padding:6px 12px;font-size:12px;display:inline-flex;align-items:center;gap:8px;}
.cat-tag span{color:#888;font-size:10px;letter-spacing:1px;}
.cat-tag button{background:none;border:none;color:#555;cursor:pointer;font-size:14px;line-height:1;transition:color .2s;}
.cat-tag button:hover{color:#dc0000;}
.cat-add-form{display:flex;gap:8px;align-items:flex-end;}
.cat-input{background:#0a0a0a;border:1px solid #222;color:#fff;padding:9px 12px;font-size:13px;font-family:'Inter',sans-serif;flex:1;}
.cat-input:focus{outline:none;border-color:#dc0000;}
.cat-add-btn{background:#dc0000;color:#fff;border:none;padding:9px 18px;font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:1px;cursor:pointer;white-space:nowrap;}
.cat-add-btn:hover{background:#ff2020;}

/* TEXT MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:200;display:none;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:#141414;border:1px solid #2a2a2a;padding:28px;width:380px;}
.modal-box h3{font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:1px;margin-bottom:20px;}
.modal-row{margin-bottom:14px;}
.modal-row label{display:block;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#555;margin-bottom:6px;}
.modal-row input,.modal-row select{width:100%;background:#0a0a0a;border:1px solid #222;color:#fff;padding:9px 12px;font-size:13px;font-family:'Inter',sans-serif;}
.modal-row input:focus,.modal-row select:focus{outline:none;border-color:#dc0000;}
.modal-actions{display:flex;gap:8px;margin-top:20px;}
.modal-ok{flex:1;background:#dc0000;color:#fff;border:none;padding:12px;font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:2px;cursor:pointer;}
.modal-cancel{background:none;border:1px solid #2a2a2a;color:#666;padding:12px 20px;cursor:pointer;font-family:'Inter',sans-serif;}
.flash{background:rgba(0,200,100,.1);border:1px solid rgba(0,200,100,.3);color:#4dff9a;padding:10px 16px;margin:12px 20px 0;font-size:13px;}
</style>
</head>
<body>
<div class="layout">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <svg width="22" height="22" viewBox="0 0 30 30" fill="none">
      <circle cx="15" cy="15" r="13" stroke="#dc0000" stroke-width="2.5"/>
      <line x1="4.5" y1="4.5" x2="25.5" y2="25.5" stroke="#dc0000" stroke-width="2.5" stroke-linecap="round"/>
    </svg>
    BAN.
  </div>
  <nav class="sidebar-nav">
    <p class="sidebar-section">Manage</p>
    <a href="dashboard.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
      Products
    </a>
    <a href="decorated.php" class="active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
      Decorated
    </a>
    <p class="sidebar-section">Tools</p>
    <a href="edit.php">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
      Add Product
    </a>
    <p class="sidebar-section">Front Desk</p>
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
  </nav>
  <div class="sidebar-footer">
    <a href="../index.html" target="_blank">← View Store</a>
    <a href="logout.php">Logout</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">

<?php if ($flash): ?>
  <div class="flash"><?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<!-- TOOLBAR -->
<div class="toolbar">
  <button class="tb-btn" id="btnUndo" onclick="undo()" disabled title="Undo">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 7v6h6"/><path d="M3 13A9 9 0 1 0 6 6.3"/></svg> Undo
  </button>
  <button class="tb-btn" id="btnRedo" onclick="redo()" disabled title="Redo">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 7v6h-6"/><path d="M21 13A9 9 0 1 1 18 6.3"/></svg> Redo
  </button>
  <div class="tb-sep"></div>
  <button class="tb-btn" onclick="openTextModal()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><line x1="12" y1="4" x2="12" y2="20"/></svg> Add Text
  </button>
  <button class="tb-btn" onclick="document.getElementById('artUpload').click()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> Upload Art
  </button>
  <input type="file" id="artUpload" accept="image/*" style="display:none" onchange="uploadArt(this)">
  <div class="tb-sep"></div>
  <button class="tb-btn" id="btnDel" onclick="deleteSelected()" disabled>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg> Delete
  </button>
  <button class="tb-btn" onclick="clearAll()">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Clear All
  </button>
</div>

<!-- EDITOR BODY -->
<div class="editor-body">

  <!-- MOCKUP COLUMN -->
  <div class="mockup-col">
    <div class="mockup-area">
      <div class="garment-wrap" id="garmentWrap">
        <svg id="garmentSvg" viewBox="0 0 300 380" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <filter id="shadow">
              <feDropShadow dx="0" dy="4" stdDeviation="8" flood-opacity="0.5"/>
            </filter>
          </defs>
          <!-- Garment path — updated by JS -->
          <path id="garmentPath" filter="url(#shadow)"/>
          <!-- Collar/detail layer -->
          <path id="collarPath" fill="rgba(0,0,0,0.12)"/>
        </svg>
        <div class="print-zone" id="printZone" onclick="deselectAll()"></div>
      </div>
    </div>

    <!-- GARMENT CONTROLS -->
    <div class="garment-controls">
      <div class="garment-tabs">
        <button class="g-tab active" data-type="tshirt">T-Shirt</button>
        <button class="g-tab" data-type="wtee">Women's Tee</button>
        <button class="g-tab" data-type="hoodie">Hoodie</button>
        <button class="g-tab" data-type="whoodie">Women's Hoodie</button>
      </div>
      <div class="controls-row">
        <div class="color-row" id="colorSwatches"></div>
        <div class="view-toggle">
          <button class="v-btn active" data-view="front">Front</button>
          <button class="v-btn" data-view="back">Back</button>
        </div>
      </div>
    </div>
  </div>

  <!-- FORM COLUMN -->
  <div class="form-col">
    <div class="form-header">
      <h2>Product Details</h2>
    </div>
    <form method="POST" action="save_decorated.php" id="productForm">
      <?php if ($editProduct): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($editProduct['id']) ?>">
      <?php endif; ?>
      <input type="hidden" name="garmentType"  id="fGarmentType"  value="<?= htmlspecialchars($editProduct['garmentType']  ?? 'tshirt') ?>">
      <input type="hidden" name="garmentColor" id="fGarmentColor" value="<?= htmlspecialchars($editProduct['garmentColor'] ?? '#888888') ?>">
      <input type="hidden" name="sizes"        id="fSizes"        value="<?= htmlspecialchars(implode(',', $editProduct['sizes'] ?? [])) ?>">
      <input type="hidden" name="colors"       id="fColors"       value="<?= htmlspecialchars(implode(',', $editProduct['colors'] ?? [])) ?>">
      <input type="hidden" name="design_json"  id="fDesignJson"   value="">

      <div class="form-body">
        <div class="fg">
          <label class="fl">Product Name *</label>
          <input class="fi" type="text" name="name" value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>" placeholder="e.g. Anime Galaxy Tee" required>
        </div>
        <div class="row2">
          <div class="fg">
            <label class="fl">Category *</label>
            <select class="fi" name="category" required>
              <option value="">— Select —</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['key']) ?>"
                  <?= ($editProduct['category'] ?? '') === $cat['key'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="fg">
            <label class="fl">Price *</label>
            <input class="fi" type="number" name="price" value="<?= htmlspecialchars($editProduct['price'] ?? '25.00') ?>" min="1" step="0.01" placeholder="$25.00" required>
          </div>
        </div>
        <div class="row2">
          <div class="fg">
            <label class="fl">Badge (optional)</label>
            <input class="fi" type="text" name="badge" value="<?= htmlspecialchars($editProduct['badge'] ?? '') ?>" placeholder="New, Hot, Sale...">
          </div>
          <div class="fg">
            <label class="fl">Garment</label>
            <input class="fi" type="text" name="garment" value="<?= htmlspecialchars($editProduct['garment'] ?? 'Heavy Cotton T-Shirt') ?>" placeholder="Heavy Cotton T-Shirt">
          </div>
        </div>

        <div class="fg">
          <label class="fl">Available Sizes</label>
          <div class="size-grid" id="sizeGrid">
            <?php foreach (['S','M','L','XL','2XL','3XL','4XL','5XL'] as $s): ?>
              <button type="button" class="sz-btn" data-size="<?= $s ?>"><?= $s ?></button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="fg">
          <label class="fl">Available Colors</label>
          <div class="color-circles" id="colorCircles"></div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-save" onclick="prepareSubmit()"><?= $editProduct ? 'Save Changes' : 'Save Product' ?></button>
        <?php if ($editProduct): ?>
          <a href="decorated.php" class="btn-cancel-form">Cancel</a>
        <?php endif; ?>
      </div>
    </form>

    <!-- DECORATED PRODUCTS LIST -->
    <?php if (!empty($decProducts)): ?>
    <div class="prod-list">
      <h3>Saved Decorated (<?= count($decProducts) ?>)</h3>
      <table class="prod-table">
        <thead>
          <tr>
            <th>Name</th><th>Cat</th><th>Price</th><th>Colors</th><th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_reverse($decProducts) as $dp): ?>
          <tr>
            <td><?= htmlspecialchars($dp['name']) ?>
              <?php if (!empty($dp['badge'])): ?>
                <span class="badge-chip"><?= htmlspecialchars($dp['badge']) ?></span>
              <?php endif; ?>
            </td>
            <td style="color:#666;font-size:12px;"><?= htmlspecialchars($dp['category'] ?? '') ?></td>
            <td>$<?= number_format($dp['price'] ?? 0, 2) ?></td>
            <td>
              <?php foreach (array_slice($dp['colors'] ?? [], 0, 5) as $c): ?>
                <span class="swatch-dot" style="background:<?= htmlspecialchars($c) ?>"></span>
              <?php endforeach; ?>
            </td>
            <td style="white-space:nowrap;">
              <a href="?edit=<?= urlencode($dp['id']) ?>" class="act-link">Edit</a>
              <a href="?delete=<?= urlencode($dp['id']) ?>" class="act-link del"
                 onclick="return confirm('Delete <?= htmlspecialchars(addslashes($dp['name'])) ?>?')">Del</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /editor-body -->

<!-- CATEGORIES SECTION -->
<div class="cat-section">
  <h3>Manage Categories</h3>
  <div class="cat-tags" id="catTags"></div>
  <div class="cat-add-form">
    <input class="cat-input" id="catKey"  placeholder="Category key (no spaces, e.g. fun_print)" style="max-width:200px;">
    <input class="cat-input" id="catName" placeholder="Display Name, e.g. Fun Print">
    <button class="cat-add-btn" onclick="addCategory()">+ Add Category</button>
  </div>
</div>

</div><!-- /main -->
</div><!-- /layout -->

<!-- TEXT MODAL -->
<div class="modal-overlay" id="textModal">
  <div class="modal-box">
    <h3>Add Text</h3>
    <div class="modal-row">
      <label>Text Content</label>
      <input type="text" id="txtContent" placeholder="Type your text..." maxlength="60">
    </div>
    <div class="modal-row" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
      <div>
        <label>Font Size</label>
        <input type="number" id="txtSize" value="22" min="8" max="80">
      </div>
      <div>
        <label>Color</label>
        <input type="color" id="txtColor" value="#ffffff" style="height:38px;padding:2px;background:#0a0a0a;border:1px solid #222;width:100%;">
      </div>
    </div>
    <div class="modal-row">
      <label>Font</label>
      <select id="txtFont">
        <option value="'Bebas Neue',sans-serif">Bebas Neue (Bold Condensed)</option>
        <option value="Impact,sans-serif">Impact</option>
        <option value="Arial,sans-serif">Arial</option>
        <option value="Georgia,serif">Georgia</option>
        <option value="'Courier New',monospace">Courier New</option>
      </select>
    </div>
    <div class="modal-actions">
      <button class="modal-ok" onclick="addTextElement()">Add Text</button>
      <button class="modal-cancel" onclick="closeModal()">Cancel</button>
    </div>
  </div>
</div>

<script>
// ── GARMENT CONFIGS ──────────────────────────────
const garments = {
  tshirt: {
    front: {
      path: 'M 130 44 Q 142 62 150 62 Q 158 62 165 44 L 190 52 L 238 76 L 228 114 L 202 104 L 202 348 L 98 348 L 98 104 L 72 114 L 62 76 L 110 52 Z',
      collar: 'M 130 44 Q 150 70 165 44 Q 155 56 150 58 Q 145 56 130 44 Z',
      zoneLeft:'33%', zoneTop:'28%', zoneW:'34%', zoneH:'38%'
    },
    back: {
      path: 'M 110 44 L 62 76 L 72 114 L 98 104 L 98 348 L 202 348 L 202 104 L 228 114 L 238 76 L 190 52 L 165 44 L 150 50 L 135 44 Z',
      collar: 'M 135 44 Q 150 54 165 44 Q 155 58 150 60 Q 145 58 135 44 Z',
      zoneLeft:'33%', zoneTop:'26%', zoneW:'34%', zoneH:'40%'
    }
  },
  wtee: {
    front: {
      path: 'M 138 44 Q 148 60 155 60 Q 162 60 170 44 L 192 52 L 232 74 L 223 112 L 200 102 L 195 355 L 105 355 L 100 102 L 77 112 L 68 74 L 108 52 Z',
      collar: 'M 138 44 Q 155 68 170 44 Q 160 54 155 56 Q 150 54 138 44 Z',
      zoneLeft:'35%', zoneTop:'28%', zoneW:'30%', zoneH:'36%'
    },
    back: {
      path: 'M 108 44 L 68 74 L 77 112 L 100 102 L 105 355 L 195 355 L 200 102 L 223 112 L 232 74 L 192 52 L 170 44 L 155 52 L 140 44 Z',
      collar: 'M 140 44 Q 155 54 170 44 Q 162 56 155 58 Q 148 56 140 44 Z',
      zoneLeft:'35%', zoneTop:'26%', zoneW:'30%', zoneH:'38%'
    }
  },
  hoodie: {
    front: {
      path: 'M 128 38 L 136 24 Q 148 10 150 10 Q 152 10 164 24 L 172 38 L 195 50 L 240 76 L 228 118 L 202 106 L 202 358 L 98 358 L 98 106 L 72 118 L 60 76 L 105 50 Z',
      collar: 'M 128 38 L 136 24 Q 150 14 164 24 L 172 38 Q 164 48 150 50 Q 136 48 128 38 Z',
      zoneLeft:'34%', zoneTop:'32%', zoneW:'32%', zoneH:'36%'
    },
    back: {
      path: 'M 128 38 L 105 50 L 60 76 L 72 118 L 98 106 L 98 358 L 202 358 L 202 106 L 228 118 L 240 76 L 195 50 L 172 38 Q 160 48 150 50 Q 140 48 128 38 Z',
      collar: '',
      zoneLeft:'34%', zoneTop:'28%', zoneW:'32%', zoneH:'38%'
    }
  },
  whoodie: {
    front: {
      path: 'M 132 40 L 140 26 Q 150 12 160 26 L 168 40 L 190 52 L 234 76 L 222 116 L 198 104 L 200 362 L 100 362 L 102 104 L 78 116 L 66 76 L 110 52 Z',
      collar: 'M 132 40 L 140 26 Q 150 16 160 26 L 168 40 Q 160 50 150 52 Q 140 50 132 40 Z',
      zoneLeft:'35%', zoneTop:'32%', zoneW:'30%', zoneH:'34%'
    },
    back: {
      path: 'M 132 40 L 110 52 L 66 76 L 78 116 L 102 104 L 100 362 L 200 362 L 198 104 L 222 116 L 234 76 L 190 52 L 168 40 Q 158 50 150 52 Q 142 50 132 40 Z',
      collar: '',
      zoneLeft:'35%', zoneTop:'28%', zoneW:'30%', zoneH:'36%'
    }
  }
};

const COLORS = [
  {name:'Gray',   hex:'#888888'},
  {name:'White',  hex:'#F0F0F0'},
  {name:'Black',  hex:'#1A1A1A'},
  {name:'Navy',   hex:'#1B3A6B'},
  {name:'Red',    hex:'#CC1111'},
  {name:'Blue',   hex:'#3A6ACC'},
  {name:'Green',  hex:'#2A7A2A'},
  {name:'Maroon', hex:'#8B0000'},
];

// ── STATE ──────────────────────────────────────
let currentType  = '<?= htmlspecialchars($editProduct['garmentType'] ?? 'tshirt') ?>';
let currentView  = 'front';
let currentColor = '<?= htmlspecialchars($editProduct['garmentColor'] ?? '#888888') ?>';
let selectedEl   = null;
let elements     = []; // {id, type, el, data}
let history      = [];
let historyIdx   = -1;
let isDragging   = false;
let dragTarget   = null;
let dragOffX = 0, dragOffY = 0;
// resize
let isResizing   = false;
let resizeTarget = null;
let resizeStartW = 0, resizeStartH = 0, resizeStartX = 0, resizeStartY = 0;

// Edit mode: restore saved sizes/colors
const savedSizes  = '<?= htmlspecialchars(implode(',', $editProduct['sizes']  ?? [])) ?>'.split(',').filter(Boolean);
const savedColors = '<?= htmlspecialchars(implode(',', $editProduct['colors'] ?? [])) ?>'.split(',').filter(Boolean);
let activeSizes  = new Set(savedSizes);
let activeColors = new Set(savedColors);

// ── INIT ──────────────────────────────────────
function init() {
  renderColorSwatches();
  renderColorCircles();
  renderSizeButtons();
  applyGarment();
  renderCategories();
  setupEventListeners();
  saveHistory();
}

function applyGarment() {
  const cfg = garments[currentType][currentView];
  document.getElementById('garmentPath').setAttribute('d', cfg.path);
  document.getElementById('garmentPath').setAttribute('fill', currentColor);
  document.getElementById('collarPath').setAttribute('d', cfg.collar || '');

  const zone = document.getElementById('printZone');
  zone.style.left   = cfg.zoneLeft;
  zone.style.top    = cfg.zoneTop;
  zone.style.width  = cfg.zoneW;
  zone.style.height = cfg.zoneH;

  document.getElementById('fGarmentType').value  = currentType;
  document.getElementById('fGarmentColor').value = currentColor;
}

// ── GARMENT TABS ──────────────────────────────
document.querySelectorAll('.g-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.g-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentType = btn.dataset.type;
    applyGarment();
  });
});

document.querySelectorAll('.v-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.v-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentView = btn.dataset.view;
    applyGarment();
  });
});

// ── COLOR SWATCHES (garment color) ────────────
function renderColorSwatches() {
  const wrap = document.getElementById('colorSwatches');
  wrap.innerHTML = '';
  COLORS.forEach(c => {
    const el = document.createElement('div');
    el.className = 'g-color' + (c.hex === currentColor ? ' active' : '');
    el.style.background = c.hex;
    el.style.border = c.hex === '#F0F0F0' ? '2px solid #555' : '2px solid transparent';
    el.title = c.name;
    el.onclick = () => {
      currentColor = c.hex;
      document.querySelectorAll('.g-color').forEach(x => x.classList.remove('active'));
      el.classList.add('active');
      applyGarment();
    };
    wrap.appendChild(el);
  });
}

// ── COLOR CIRCLES (available colors for product) ──
function renderColorCircles() {
  const wrap = document.getElementById('colorCircles');
  wrap.innerHTML = '';
  COLORS.forEach(c => {
    const el = document.createElement('div');
    el.className = 'cc' + (activeColors.has(c.hex) ? ' active' : '');
    el.style.background = c.hex;
    if (c.hex === '#F0F0F0') el.style.border = '2px solid #555';
    el.title = c.name;
    el.onclick = () => {
      if (activeColors.has(c.hex)) { activeColors.delete(c.hex); el.classList.remove('active'); }
      else { activeColors.add(c.hex); el.classList.add('active'); }
      document.getElementById('fColors').value = [...activeColors].join(',');
    };
    wrap.appendChild(el);
  });
  document.getElementById('fColors').value = [...activeColors].join(',');
}

// ── SIZE BUTTONS ──────────────────────────────
function renderSizeButtons() {
  document.querySelectorAll('.sz-btn').forEach(btn => {
    const s = btn.dataset.size;
    if (activeSizes.has(s)) btn.classList.add('active');
    btn.onclick = () => {
      if (activeSizes.has(s)) { activeSizes.delete(s); btn.classList.remove('active'); }
      else { activeSizes.add(s); btn.classList.add('active'); }
      document.getElementById('fSizes').value = [...activeSizes].join(',');
    };
  });
  document.getElementById('fSizes').value = [...activeSizes].join(',');
}

// ── DESIGN ELEMENTS ───────────────────────────
function makeElement(type, data) {
  const zone = document.getElementById('printZone');
  const el   = document.createElement('div');
  const id   = 'el_' + Date.now();
  el.className = 'design-el ' + type + '-el';
  el.id = id;
  el.style.left = (data.x || 10) + 'px';
  el.style.top  = (data.y || 10) + 'px';

  const handle = document.createElement('div');
  handle.className = 'resize-handle';

  if (type === 'text') {
    el.className += ' text-el';
    el.textContent = data.content;
    el.style.fontSize   = (data.fontSize || 22) + 'px';
    el.style.color      = data.color  || '#fff';
    el.style.fontFamily = data.font   || "'Bebas Neue',sans-serif";
    el.style.letterSpacing = '1px';
    el.style.textShadow = '0 1px 4px rgba(0,0,0,.7)';
    el.style.padding = '2px 4px';
  } else {
    el.style.width  = (data.width  || 80) + 'px';
    el.style.height = (data.height || 80) + 'px';
    const img = document.createElement('img');
    img.src = data.src;
    img.style.pointerEvents = 'none';
    img.style.width = '100%';
    img.style.height = '100%';
    img.style.objectFit = 'contain';
    el.appendChild(img);

    handle.addEventListener('mousedown', startResize);
    el.appendChild(handle);
  }

  el.addEventListener('mousedown', startDrag);
  el.addEventListener('click', (e) => { e.stopPropagation(); selectEl(id); });

  zone.appendChild(el);
  elements.push({ id, type, el, data });
  selectEl(id);
  saveHistory();
}

function selectEl(id) {
  deselectAll();
  selectedEl = id;
  const obj = elements.find(e => e.id === id);
  if (obj) {
    obj.el.classList.add('selected');
    document.getElementById('btnDel').disabled = false;
  }
}

function deselectAll() {
  elements.forEach(e => e.el.classList.remove('selected'));
  selectedEl = null;
  document.getElementById('btnDel').disabled = true;
}

function deleteSelected() {
  if (!selectedEl) return;
  const idx = elements.findIndex(e => e.id === selectedEl);
  if (idx >= 0) {
    elements[idx].el.remove();
    elements.splice(idx, 1);
    selectedEl = null;
    document.getElementById('btnDel').disabled = true;
    saveHistory();
  }
}

function clearAll() {
  if (!confirm('Clear all design elements?')) return;
  elements.forEach(e => e.el.remove());
  elements = [];
  selectedEl = null;
  document.getElementById('btnDel').disabled = true;
  saveHistory();
}

// ── DRAG ──────────────────────────────────────
function startDrag(e) {
  if (e.target.classList.contains('resize-handle')) return;
  isDragging = true;
  dragTarget = e.currentTarget;
  const rect = dragTarget.getBoundingClientRect();
  dragOffX = e.clientX - rect.left;
  dragOffY = e.clientY - rect.top;
  e.preventDefault();
}

function startResize(e) {
  isResizing = true;
  resizeTarget = e.currentTarget.parentElement;
  resizeStartW = resizeTarget.offsetWidth;
  resizeStartH = resizeTarget.offsetHeight;
  resizeStartX = e.clientX;
  resizeStartY = e.clientY;
  e.stopPropagation();
  e.preventDefault();
}

document.addEventListener('mousemove', (e) => {
  if (isDragging && dragTarget) {
    const zone = document.getElementById('printZone');
    const zRect = zone.getBoundingClientRect();
    let x = e.clientX - zRect.left - dragOffX;
    let y = e.clientY - zRect.top  - dragOffY;
    x = Math.max(0, Math.min(x, zRect.width  - dragTarget.offsetWidth));
    y = Math.max(0, Math.min(y, zRect.height - dragTarget.offsetHeight));
    dragTarget.style.left = x + 'px';
    dragTarget.style.top  = y + 'px';
  }
  if (isResizing && resizeTarget) {
    const dx = e.clientX - resizeStartX;
    const dy = e.clientY - resizeStartY;
    const w = Math.max(30, resizeStartW + dx);
    const h = Math.max(30, resizeStartH + dy);
    resizeTarget.style.width  = w + 'px';
    resizeTarget.style.height = h + 'px';
  }
});

document.addEventListener('mouseup', () => {
  if (isDragging || isResizing) saveHistory();
  isDragging = false; dragTarget = null;
  isResizing = false; resizeTarget = null;
});

// ── TEXT ──────────────────────────────────────
function openTextModal() {
  document.getElementById('textModal').classList.add('open');
  document.getElementById('txtContent').focus();
}

function closeModal() {
  document.getElementById('textModal').classList.remove('open');
}

function addTextElement() {
  const content = document.getElementById('txtContent').value.trim();
  if (!content) return;
  makeElement('text', {
    content, x: 10, y: 10,
    fontSize:  parseInt(document.getElementById('txtSize').value)  || 22,
    color:     document.getElementById('txtColor').value,
    font:      document.getElementById('txtFont').value,
  });
  document.getElementById('txtContent').value = '';
  closeModal();
}

document.getElementById('txtContent').addEventListener('keydown', e => {
  if (e.key === 'Enter') addTextElement();
  if (e.key === 'Escape') closeModal();
});

document.getElementById('textModal').addEventListener('click', e => {
  if (e.target === document.getElementById('textModal')) closeModal();
});

// ── ART UPLOAD ────────────────────────────────
function uploadArt(input) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = ev => {
    makeElement('img', { src: ev.target.result, x: 10, y: 10, width: 80, height: 80 });
  };
  reader.readAsDataURL(file);
  input.value = '';
}

// ── UNDO / REDO ───────────────────────────────
function serializeState() {
  return elements.map(obj => ({
    id:   obj.id,
    type: obj.type,
    x:    parseInt(obj.el.style.left),
    y:    parseInt(obj.el.style.top),
    w:    obj.el.offsetWidth,
    h:    obj.el.offsetHeight,
    data: obj.data,
  }));
}

function saveHistory() {
  history = history.slice(0, historyIdx + 1);
  history.push(JSON.stringify(serializeState()));
  historyIdx = history.length - 1;
  updateHistoryBtns();
}

function undo() {
  if (historyIdx <= 0) return;
  historyIdx--;
  restoreState(JSON.parse(history[historyIdx]));
  updateHistoryBtns();
}

function redo() {
  if (historyIdx >= history.length - 1) return;
  historyIdx++;
  restoreState(JSON.parse(history[historyIdx]));
  updateHistoryBtns();
}

function restoreState(state) {
  elements.forEach(obj => obj.el.remove());
  elements = [];
  deselectAll();
  state.forEach(s => {
    makeElement(s.type, {...s.data, x: s.x, y: s.y, width: s.w, height: s.h});
  });
}

function updateHistoryBtns() {
  document.getElementById('btnUndo').disabled = historyIdx <= 0;
  document.getElementById('btnRedo').disabled = historyIdx >= history.length - 1;
}

// ── SERIALIZE FOR SAVE ────────────────────────
function prepareSubmit() {
  const zone  = document.getElementById('printZone');
  const zRect = zone.getBoundingClientRect();
  const data  = elements.map(obj => {
    const base = {
      type: obj.type,
      x: parseFloat(obj.el.style.left) / zRect.width,
      y: parseFloat(obj.el.style.top)  / zRect.height,
    };
    if (obj.type === 'text') {
      return {...base, ...obj.data};
    } else {
      return {...base, ...obj.data, width: obj.el.offsetWidth / zRect.width, height: obj.el.offsetHeight / zRect.height};
    }
  });
  document.getElementById('fDesignJson').value = JSON.stringify({elements: data});
  document.getElementById('fSizes').value  = [...activeSizes].join(',');
  document.getElementById('fColors').value = [...activeColors].join(',');
}

// ── KEYBOARD ──────────────────────────────────
document.addEventListener('keydown', e => {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
  if ((e.key === 'Delete' || e.key === 'Backspace') && selectedEl) deleteSelected();
  if (e.ctrlKey && e.key === 'z') undo();
  if (e.ctrlKey && e.key === 'y') redo();
});

// ── CATEGORIES ────────────────────────────────
function renderCategories() {
  fetch('categories_api.php')
    .then(r => r.json())
    .then(cats => {
      const wrap = document.getElementById('catTags');
      wrap.innerHTML = '';
      cats.forEach(c => {
        const tag = document.createElement('div');
        tag.className = 'cat-tag';
        tag.innerHTML = `<b>${c.name}</b><span>${c.key}</span>
          <button onclick="deleteCategory('${c.key}')" title="Delete">×</button>`;
        wrap.appendChild(tag);
      });
    });
}

function addCategory() {
  const key  = document.getElementById('catKey').value.trim();
  const name = document.getElementById('catName').value.trim();
  if (!key || !name) { alert('Fill in both key and display name.'); return; }

  const fd = new FormData();
  fd.append('action', 'add');
  fd.append('key', key);
  fd.append('name', name);

  fetch('categories_api.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(res => {
      if (res.error) { alert(res.error); return; }
      document.getElementById('catKey').value  = '';
      document.getElementById('catName').value = '';
      renderCategories();
      // Also add to the category dropdown
      const sel = document.querySelector('select[name="category"]');
      const opt = document.createElement('option');
      opt.value = res.key;
      opt.textContent = res.name;
      sel.appendChild(opt);
    });
}

function deleteCategory(key) {
  if (!confirm('Delete category "' + key + '"?')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('key', key);
  fetch('categories_api.php', {method:'POST', body:fd})
    .then(() => renderCategories());
}

function setupEventListeners() {
  // Click on empty area deselects
  document.getElementById('garmentWrap').addEventListener('click', e => {
    if (e.target === document.getElementById('garmentWrap')) deselectAll();
  });
}

init();
</script>
</body>
</html>
