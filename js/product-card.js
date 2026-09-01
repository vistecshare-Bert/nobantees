// Shared product card renderer + photo carousel, used by index.html and shop.html.
// Handles both the new `images` array field and the legacy single `image` field
// so it keeps working even before a product has been re-saved in the admin panel.

function imagesFor(product) {
  if (Array.isArray(product.images) && product.images.length) return product.images;
  if (product.image) return [product.image];
  return [];
}

function makeProductCard(product) {
  const imgs = imagesFor(product);

  let imgHtml;
  if (!imgs.length) {
    imgHtml = `
      <div class="product-placeholder">
        <svg class="placeholder-icon" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1">
          <rect x="3" y="3" width="18" height="18" rx="2"/>
          <circle cx="8.5" cy="8.5" r="1.5"/>
          <polyline points="21 15 16 10 5 21"/>
        </svg>
        <span class="placeholder-label">Photo Coming Soon</span>
      </div>`;
  } else if (imgs.length === 1) {
    imgHtml = `<img src="${imgs[0]}" alt="${product.name}">`;
  } else {
    const slides = imgs.map((src, i) =>
      `<img class="carousel-slide${i === 0 ? ' active' : ''}" src="${src}" alt="${product.name}" data-i="${i}">`
    ).join('');
    const dots = imgs.map((_, i) =>
      `<button type="button" class="dot${i === 0 ? ' active' : ''}" onclick="gotoCarousel(event,'${product.id}',${i})" aria-label="Photo ${i + 1}"></button>`
    ).join('');
    imgHtml = `
      ${slides}
      <button type="button" class="carousel-arrow prev" onclick="cycleCarousel(event,'${product.id}',-1)" aria-label="Previous photo">&lsaquo;</button>
      <button type="button" class="carousel-arrow next" onclick="cycleCarousel(event,'${product.id}',1)" aria-label="Next photo">&rsaquo;</button>
      <div class="carousel-dots">${dots}</div>`;
  }

  return `
    <div class="product-card">
      <div class="product-image" data-pid="${product.id}">${imgHtml}</div>
      <div class="product-info">
        <p class="product-category">${product.category}</p>
        <h3 class="product-name">${product.name}</h3>
        <p class="product-color">${product.color}</p>
        <p class="product-price">$${product.price}.00</p>
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
