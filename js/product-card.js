// Shared product card renderer + photo carousel, used by index.html and shop.html.
// Handles both the new `images` array field and the legacy single `image` field
// so it keeps working even before a product has been re-saved in the admin panel.

function imagesFor(product) {
  if (Array.isArray(product.images) && product.images.length) return product.images;
  if (product.image) return [product.image];
  return [];
}

// Filters/orders a product's photos to match the currently selected color
// and/or print style (either can be blank). A photo tagged for the "wrong"
// color or style is excluded; among the rest, photos actually tagged to
// match at least one active selection are shown first, followed by general
// untagged photos. Falls back to the full default set if nothing matches
// (e.g. no photo is tagged for that combination) or neither is selected.
function imagesForSelection(product, color, printStyle) {
  const all = imagesFor(product);
  if (!color && !printStyle) return all;
  const colorMap = product.imageColors || {};
  const styleMap = product.imagePrintStyles || {};

  const compatible = all.filter(src => {
    const colorOk = !color || !colorMap[src] || colorMap[src] === color;
    const styleOk = !printStyle || !styleMap[src] || styleMap[src] === printStyle;
    return colorOk && styleOk;
  });

  const tagged = compatible.filter(src =>
    (color && colorMap[src] === color) || (printStyle && styleMap[src] === printStyle)
  );
  const rest = compatible.filter(src => !tagged.includes(src));
  const ordered = [...tagged, ...rest];
  return ordered.length ? ordered : all;
}

function buildCarouselHtml(imgs, productId, productName) {
  if (!imgs.length) {
    return `
      <div class="product-placeholder">
        <svg class="placeholder-icon" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1">
          <rect x="3" y="3" width="18" height="18" rx="2"/>
          <circle cx="8.5" cy="8.5" r="1.5"/>
          <polyline points="21 15 16 10 5 21"/>
        </svg>
        <span class="placeholder-label">Photo Coming Soon</span>
      </div>`;
  }
  if (imgs.length === 1) {
    return `<img src="${imgs[0]}" alt="${productName}">`;
  }
  const slides = imgs.map((src, i) =>
    `<img class="carousel-slide${i === 0 ? ' active' : ''}" src="${src}" alt="${productName}" data-i="${i}">`
  ).join('');
  const dots = imgs.map((_, i) =>
    `<button type="button" class="dot${i === 0 ? ' active' : ''}" onclick="gotoCarousel(event,'${productId}',${i})" aria-label="Photo ${i + 1}"></button>`
  ).join('');
  return `
    ${slides}
    <button type="button" class="carousel-arrow prev" onclick="cycleCarousel(event,'${productId}',-1)" aria-label="Previous photo">&lsaquo;</button>
    <button type="button" class="carousel-arrow next" onclick="cycleCarousel(event,'${productId}',1)" aria-label="Next photo">&rsaquo;</button>
    <div class="carousel-dots">${dots}</div>`;
}

// Swaps the product card's photo(s) to match the currently selected color
// and/or print style. Reads both selects (whichever exist) so picking either
// one re-evaluates using both current selections together.
function updateCardImages(e, id) {
  const product = products.find(p => p.id === id);
  const wrap = document.querySelector(`.product-image[data-pid="${id}"]`);
  if (!product || !wrap) return;
  const colorSelect = document.getElementById(`color-${id}`);
  const styleSelect = document.getElementById(`printStyle-${id}`);
  const color = colorSelect ? colorSelect.value : '';
  const printStyle = styleSelect ? styleSelect.value : '';
  const imgs = imagesForSelection(product, color, printStyle);
  const badgeHtml = product.badge ? `<span class="product-badge">${product.badge}</span>` : '';
  wrap.innerHTML = badgeHtml + buildCarouselHtml(imgs, id, product.name);
}

function makeProductCard(product) {
  const imgHtml = buildCarouselHtml(imagesFor(product), product.id, product.name);

  const descHtml = product.description ? `
        <p class="product-desc" id="desc-${product.id}">${product.description}</p>
        <button type="button" class="desc-toggle" onclick="toggleDesc(event,'${product.id}')">Read more</button>` : '';

  const badgeHtml = product.badge ? `<span class="product-badge">${product.badge}</span>` : '';

  // One color (or none) is just shown as text — nothing to choose. Two or
  // more means the customer picks one before they can add it to the cart.
  const colors = Array.isArray(product.colors) ? product.colors : [];
  const colorTextHtml = colors.length === 1 ? `<p class="product-color">${colors[0]}</p>` : '';
  const colorSelectHtml = colors.length > 1 ? `
        <select class="size-select" id="color-${product.id}" onchange="updateCardImages(event,'${product.id}')">
          <option value="">— Select Color —</option>
          ${colors.map(c => `<option value="${c}">${c}</option>`).join('')}
        </select>` : '';

  // Same pattern for print style.
  const printStyles = Array.isArray(product.printStyles) ? product.printStyles : [];
  const printStyleTextHtml = printStyles.length === 1 ? `<p class="product-color">${printStyles[0]}</p>` : '';
  const printStyleSelectHtml = printStyles.length > 1 ? `
        <select class="size-select" id="printStyle-${product.id}" onchange="updateCardImages(event,'${product.id}')">
          <option value="">— Select Print Style —</option>
          ${printStyles.map(s => `<option value="${s}">${s}</option>`).join('')}
        </select>` : '';

  return `
    <div class="product-card">
      <div class="product-image" data-pid="${product.id}">${badgeHtml}${imgHtml}</div>
      <div class="product-info">
        <p class="product-category">${product.category}</p>
        <h3 class="product-name">${product.name}</h3>
        ${colorTextHtml}${printStyleTextHtml}${descHtml}
        <p class="product-price">$${product.price}.00</p>
        ${colorSelectHtml}
        ${printStyleSelectHtml}
        <select class="size-select" id="size-${product.id}">
          <option value="">— Select Size —</option>
          ${sizes.map(s => `<option value="${s}">${s}</option>`).join('')}
        </select>
        <button class="btn-add-cart" onclick="handleAddToCart('${product.id}')">
          Add to Cart
        </button>
      </div>
    </div>`;
}

function toggleDesc(e, id) {
  e.preventDefault();
  e.stopPropagation();
  const el = document.getElementById(`desc-${id}`);
  if (!el) return;
  const expanded = el.classList.toggle('expanded');
  e.target.textContent = expanded ? 'Read less' : 'Read more';
}

function cycleCarousel(e, id, dir) {
  e.preventDefault();
  e.stopPropagation();
  const wrap = document.querySelector(`.product-image[data-pid="${id}"]`);
  if (!wrap) return;
  const slides = [...wrap.querySelectorAll('.carousel-slide')];
  const dots = [...wrap.querySelectorAll('.dot')];
  let idx = slides.findIndex(s => s.classList.contains('active'));
  if (idx === -1) idx = 0;
  slides[idx].classList.remove('active');
  dots[idx] && dots[idx].classList.remove('active');
  idx = (idx + dir + slides.length) % slides.length;
  slides[idx].classList.add('active');
  dots[idx] && dots[idx].classList.add('active');
}

function gotoCarousel(e, id, targetIdx) {
  e.preventDefault();
  e.stopPropagation();
  const wrap = document.querySelector(`.product-image[data-pid="${id}"]`);
  if (!wrap) return;
  wrap.querySelectorAll('.carousel-slide').forEach((s, i) => s.classList.toggle('active', i === targetIdx));
  wrap.querySelectorAll('.dot').forEach((d, i) => d.classList.toggle('active', i === targetIdx));
}
