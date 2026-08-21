const CART_KEY = 'ban_cart';

function getCart() {
  return JSON.parse(localStorage.getItem(CART_KEY) || '[]');
}

function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartBadge();
}

function addToCart(productId, size) {
  const product = products.find(p => p.id === productId);
  if (!product || !size) return false;

  const cart = getCart();
  const key = productId + '-' + size;
  const existing = cart.find(item => item.key === key);

  if (existing) {
    existing.quantity += 1;
  } else {
    cart.push({
      key,
      id: product.id,
      name: product.name,
      price: product.price,
      category: product.category,
      image: product.image,
      size,
      quantity: 1
    });
  }

  saveCart(cart);
  return true;
}

function removeFromCart(key) {
  const cart = getCart().filter(item => item.key !== key);
  saveCart(cart);
}

function updateQuantity(key, qty) {
  const cart = getCart();
  const item = cart.find(i => i.key === key);
  if (item) {
    item.quantity = Math.max(1, qty);
    saveCart(cart);
  }
}

function getCartCount() {
  return getCart().reduce((sum, item) => sum + item.quantity, 0);
}

function getCartTotal() {
  return getCart().reduce((sum, item) => sum + item.price * item.quantity, 0);
}

function clearCart() {
  localStorage.removeItem(CART_KEY);
  updateCartBadge();
}

function updateCartBadge() {
  const badge = document.querySelector('.cart-count');
  if (badge) {
    const count = getCartCount();
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-flex' : 'none';
  }
}

document.addEventListener('DOMContentLoaded', updateCartBadge);
