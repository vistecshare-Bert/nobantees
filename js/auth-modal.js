(function () {
  'use strict';

  const AUTH_URL    = '/auth.php';
  let   currentUser = null;
  let   currentMode = 'login';

  // ── CSS ──────────────────────────────────────────────────
  const css = `
  #nbAuthOverlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:99999;align-items:center;justify-content:center;padding:20px;backdrop-filter:blur(6px);}
  #nbAuthOverlay.nb-open{display:flex;}
  .nb-modal{background:#111;border:1px solid #222;border-radius:2px;width:100%;max-width:400px;padding:36px 32px 28px;position:relative;font-family:'Inter',sans-serif;}
  .nb-modal-close{position:absolute;top:12px;right:16px;background:none;border:none;color:#555;font-size:24px;cursor:pointer;line-height:1;transition:color .15s;}
  .nb-modal-close:hover{color:#fff;}
  .nb-modal-eyebrow{font-family:'Bebas Neue',sans-serif;font-size:13px;letter-spacing:3px;color:#dc0000;margin-bottom:8px;}
  .nb-modal-title{font-family:'Bebas Neue',sans-serif;font-size:28px;letter-spacing:1px;color:#fff;margin-bottom:20px;}

  /* Form fields */
  .nb-field{margin-bottom:14px;}
  .nb-field label{display:block;font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:#888;margin-bottom:5px;}
  .nb-field input{width:100%;background:#181818;border:1px solid #333;border-radius:2px;color:#fff;font-size:14px;padding:10px 12px;font-family:inherit;outline:none;transition:border-color .15s;box-sizing:border-box;}
  .nb-field input:focus{border-color:#dc0000;}
  .nb-field input::placeholder{color:#444;}
  .nb-error{font-size:12px;color:#ff4d4d;min-height:16px;margin-bottom:10px;line-height:1.4;}
  .nb-btn-submit{width:100%;background:#dc0000;color:#fff;border:none;border-radius:2px;font-family:'Bebas Neue',sans-serif;font-size:17px;letter-spacing:2px;text-transform:uppercase;padding:12px;cursor:pointer;transition:background .15s;margin-bottom:16px;}
  .nb-btn-submit:hover{background:#ff2020;}
  .nb-btn-submit:disabled{opacity:.5;cursor:not-allowed;}

  /* Footer */
  .nb-modal-note{font-size:11px;color:#444;text-align:center;line-height:1.6;margin-bottom:10px;}
  .nb-modal-switch{font-size:12px;color:#555;text-align:center;}
  .nb-modal-switch button{background:none;border:none;color:#dc0000;font-size:12px;cursor:pointer;font-family:inherit;text-decoration:underline;padding:0;}

  /* Nav — logged out: two buttons */
  .nb-nav-auth-wrap{display:flex;align-items:center;gap:8px;flex-shrink:0;margin-right:16px;}
  .nb-nav-login{background:none;border:1px solid #333;color:#fff;padding:7px 14px;border-radius:2px;font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;white-space:nowrap;font-family:inherit;transition:all .15s;flex-shrink:0;}
  .nb-nav-login:hover{border-color:#dc0000;color:#dc0000;}
  .nb-nav-signup{background:#dc0000;border:1px solid #dc0000;color:#fff;padding:7px 16px;border-radius:2px;font-size:11px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;cursor:pointer;white-space:nowrap;font-family:inherit;transition:background .15s;flex-shrink:0;}
  .nb-nav-signup:hover{background:#ff2020;}

  /* Nav — logged in: avatar pill */
  .nb-nav-user{display:flex;align-items:center;gap:8px;cursor:pointer;position:relative;flex-shrink:0;margin-right:16px;}
  .nb-nav-user-avatar{width:30px;height:30px;border-radius:50%;border:2px solid #dc0000;background:#181818;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#dc0000;flex-shrink:0;}
  .nb-nav-user-name{font-size:12px;color:#fff;font-weight:600;letter-spacing:.5px;white-space:nowrap;}
  .nb-nav-drop{display:none;position:absolute;top:calc(100% + 10px);right:0;background:#111;border:1px solid #222;border-radius:2px;min-width:160px;z-index:9999;overflow:hidden;box-shadow:0 8px 24px rgba(0,0,0,.5);}
  .nb-nav-user:hover .nb-nav-drop,.nb-nav-user.open .nb-nav-drop{display:block;}
  .nb-nav-drop a,.nb-nav-drop button{display:block;width:100%;text-align:left;padding:11px 16px;font-size:13px;color:#bbb;text-decoration:none;background:none;border:none;border-bottom:1px solid #1a1a1a;cursor:pointer;font-family:inherit;transition:background .15s;}
  .nb-nav-drop a:last-child,.nb-nav-drop button:last-child{border-bottom:none;}
  .nb-nav-drop a:hover,.nb-nav-drop button:hover{background:#1a1a1a;color:#dc0000;}
  `;
  const styleEl = document.createElement('style');
  styleEl.textContent = css;
  document.head.appendChild(styleEl);

  // ── Modal shell (content swapped dynamically) ─────────────
  const modalHtml = `
  <div id="nbAuthOverlay">
    <div class="nb-modal" role="dialog" aria-modal="true">
      <button class="nb-modal-close" aria-label="Close" onclick="nbAuth.close()">&times;</button>
      <div class="nb-modal-eyebrow">NoBan Tees</div>
      <h2 class="nb-modal-title" id="nbModalTitle"></h2>
      <div id="nbModalBody"></div>
    </div>
  </div>`;

  // ── Form templates ────────────────────────────────────────
  function loginBody() {
    return `
      <form id="nbLoginForm" onsubmit="return false;">
        <div class="nb-field">
          <label for="nbLoginEmail">Email</label>
          <input type="email" id="nbLoginEmail" placeholder="your@email.com" autocomplete="email" required/>
        </div>
        <div class="nb-field">
          <label for="nbLoginPass">Password</label>
          <input type="password" id="nbLoginPass" placeholder="••••••••" autocomplete="current-password" required/>
        </div>
        <div class="nb-error" id="nbLoginErr"></div>
        <button type="submit" class="nb-btn-submit" id="nbLoginBtn">Login</button>
      </form>
      <p class="nb-modal-note">We only store your name, email, and order history.</p>
      <p class="nb-modal-switch">Don't have an account? <button onclick="nbAuth.open('signup')">Sign Up</button></p>`;
  }

  function signupBody() {
    return `
      <form id="nbSignupForm" onsubmit="return false;">
        <div class="nb-field">
          <label for="nbSignupName">Full Name</label>
          <input type="text" id="nbSignupName" placeholder="Your name" autocomplete="name" required/>
        </div>
        <div class="nb-field">
          <label for="nbSignupEmail">Email</label>
          <input type="email" id="nbSignupEmail" placeholder="your@email.com" autocomplete="email" required/>
        </div>
        <div class="nb-field">
          <label for="nbSignupPass">Password</label>
          <input type="password" id="nbSignupPass" placeholder="Min. 8 characters" autocomplete="new-password" required/>
        </div>
        <div class="nb-field">
          <label for="nbSignupPass2">Confirm Password</label>
          <input type="password" id="nbSignupPass2" placeholder="Repeat password" autocomplete="new-password" required/>
        </div>
        <div class="nb-error" id="nbSignupErr"></div>
        <button type="submit" class="nb-btn-submit" id="nbSignupBtn">Create Account</button>
      </form>
      <p class="nb-modal-note">We only store your name, email, and order history.</p>
      <p class="nb-modal-switch">Already have an account? <button onclick="nbAuth.open('login')">Login</button></p>`;
  }

  // ── Inject modal ──────────────────────────────────────────
  function injectModal() {
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    document.getElementById('nbAuthOverlay').addEventListener('click', function (e) {
      if (e.target === this) nbAuth.close();
    });
  }

  // ── Set modal content for mode ────────────────────────────
  function applyMode(mode) {
    currentMode = mode;
    document.getElementById('nbModalTitle').textContent = mode === 'signup' ? 'Create Account' : 'Welcome Back';
    document.getElementById('nbModalBody').innerHTML    = mode === 'signup' ? signupBody() : loginBody();

    if (mode === 'login') {
      document.getElementById('nbLoginForm').addEventListener('submit', handleLoginSubmit);
    } else {
      document.getElementById('nbSignupForm').addEventListener('submit', handleSignupSubmit);
    }
  }

  // ── Form submit handlers ──────────────────────────────────
  async function handleLoginSubmit() {
    const email  = document.getElementById('nbLoginEmail').value.trim();
    const pass   = document.getElementById('nbLoginPass').value;
    const errEl  = document.getElementById('nbLoginErr');
    const btn    = document.getElementById('nbLoginBtn');
    errEl.textContent = '';
    btn.disabled = true;
    btn.textContent = 'Signing in…';

    try {
      const fd = new FormData();
      fd.append('email', email);
      fd.append('password', pass);
      const r = await fetch(AUTH_URL + '?action=login', { method: 'POST', body: fd, credentials: 'include' });
      const d = await r.json();
      if (d.success) {
        onAuthSuccess(d.user);
      } else {
        errEl.textContent = d.error || 'Login failed. Please try again.';
        btn.disabled = false;
        btn.textContent = 'Login';
      }
    } catch (e) {
      errEl.textContent = 'Connection error. Please try again.';
      btn.disabled = false;
      btn.textContent = 'Login';
    }
  }

  async function handleSignupSubmit() {
    const name   = document.getElementById('nbSignupName').value.trim();
    const email  = document.getElementById('nbSignupEmail').value.trim();
    const pass   = document.getElementById('nbSignupPass').value;
    const pass2  = document.getElementById('nbSignupPass2').value;
    const errEl  = document.getElementById('nbSignupErr');
    const btn    = document.getElementById('nbSignupBtn');
    errEl.textContent = '';

    if (pass !== pass2) { errEl.textContent = 'Passwords do not match.'; return; }
    if (pass.length < 8) { errEl.textContent = 'Password must be at least 8 characters.'; return; }

    btn.disabled = true;
    btn.textContent = 'Creating account…';

    try {
      const fd = new FormData();
      fd.append('name',     name);
      fd.append('email',    email);
      fd.append('password', pass);
      const r = await fetch(AUTH_URL + '?action=register', { method: 'POST', body: fd, credentials: 'include' });
      const d = await r.json();
      if (d.success) {
        onAuthSuccess(d.user);
      } else {
        errEl.textContent = d.error || 'Sign up failed. Please try again.';
        btn.disabled = false;
        btn.textContent = 'Create Account';
      }
    } catch (e) {
      errEl.textContent = 'Connection error. Please try again.';
      btn.disabled = false;
      btn.textContent = 'Create Account';
    }
  }

  // ── Shared post-auth handler ──────────────────────────────
  function onAuthSuccess(user) {
    currentUser = user;
    refreshNav(user);
    nbAuth.close();
    const msg = currentMode === 'signup'
      ? 'Account created — welcome, ' + user.name.split(' ')[0] + '!'
      : 'Welcome back, ' + user.name.split(' ')[0] + '!';
    showToast(msg);
  }

  // ── Toast ─────────────────────────────────────────────────
  function showToast(msg) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:28px;left:50%;transform:translateX(-50%);background:#dc0000;color:#fff;padding:12px 28px;border-radius:2px;font-size:13px;font-weight:700;letter-spacing:1px;z-index:99999;box-shadow:0 4px 20px rgba(0,0,0,.4);transition:opacity .4s;white-space:nowrap;';
    t.textContent   = msg;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }, 3000);
  }

  // ── Nav buttons ───────────────────────────────────────────
  function makeAuthWrap() {
    const wrap = document.createElement('div');
    wrap.className = 'nb-nav-auth-wrap';
    wrap.id        = 'nbNavAuthWrap';

    const loginBtn = document.createElement('button');
    loginBtn.className   = 'nb-nav-login';
    loginBtn.textContent = 'Login';
    loginBtn.onclick     = () => nbAuth.open('login');

    const signupBtn = document.createElement('button');
    signupBtn.className   = 'nb-nav-signup';
    signupBtn.textContent = 'Sign Up';
    signupBtn.onclick     = () => nbAuth.open('signup');

    wrap.appendChild(loginBtn);
    wrap.appendChild(signupBtn);
    return wrap;
  }

  function injectNavButton() {
    const navbar = document.querySelector('.navbar');
    const cart   = document.querySelector('.nav-cart');
    if (!navbar || document.getElementById('nbNavAuthWrap')) return;
    const wrap = makeAuthWrap();
    if (cart) navbar.insertBefore(wrap, cart); else navbar.appendChild(wrap);
  }

  function escHtml(s) {
    return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function refreshNav(user) {
    const existing = document.getElementById('nbNavAuthWrap') || document.getElementById('nbNavUser');
    if (!existing) return;
    const parent = existing.parentNode;

    if (!user) {
      parent.replaceChild(makeAuthWrap(), existing);
    } else {
      const first = (user.name || 'Account').split(' ')[0];
      const pill  = document.createElement('div');
      pill.className = 'nb-nav-user';
      pill.id        = 'nbNavUser';
      pill.innerHTML = `
        <div class="nb-nav-user-avatar">${escHtml(first[0] || '?')}</div>
        <span class="nb-nav-user-name">${escHtml(first)}</span>
        <div class="nb-nav-drop">
          <a href="/account.php">My Account</a>
          <a href="/account.php#orders">My Orders</a>
          <button onclick="nbAuth.logout()">Sign Out</button>
        </div>`;
      parent.replaceChild(pill, existing);
    }
  }

  // ── Session check ─────────────────────────────────────────
  async function checkStatus() {
    try {
      const r = await fetch(AUTH_URL + '?action=status', { credentials: 'include' });
      const d = await r.json();
      if (d.loggedIn) { currentUser = d.user; refreshNav(currentUser); }
    } catch (e) {}
  }

  // ── Public API ────────────────────────────────────────────
  window.nbAuth = {
    open(mode = 'login') {
      const overlay = document.getElementById('nbAuthOverlay');
      if (!overlay) return;
      applyMode(mode);
      overlay.classList.add('nb-open');
      document.body.style.overflow = 'hidden';
    },
    close() {
      const overlay = document.getElementById('nbAuthOverlay');
      if (overlay) overlay.classList.remove('nb-open');
      document.body.style.overflow = '';
    },
    async logout() {
      try { await fetch(AUTH_URL + '?action=logout', { credentials: 'include' }); } catch (e) {}
      currentUser = null;
      refreshNav(null);
      if (window.location.pathname.includes('account')) window.location.href = '/index.html';
    },
    getUser() { return currentUser; },
  };

  // ── Boot ──────────────────────────────────────────────────
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { injectModal(); injectNavButton(); checkStatus(); });
  } else {
    injectModal(); injectNavButton(); checkStatus();
  }

}());
