/**
 * BoviLogic – Main Application JS
 * Loaded on every page after body close.
 */

// ─── Service Worker Registration ─────────────────────────────────────────────
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(reg => {
        console.log('[SW] Registered:', reg.scope);
        // Check for updates
        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing;
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              showUpdateBanner();
            }
          });
        });
      })
      .catch(err => console.warn('[SW] Registration failed:', err));
  });
}

// ─── Offline / Online Detection ──────────────────────────────────────────────
const offlineBanner = document.getElementById('offline-banner');

function updateOnlineStatus() {
  if (offlineBanner) {
    offlineBanner.style.display = navigator.onLine ? 'none' : 'block';
  }
  document.body.classList.toggle('offline', !navigator.onLine);
}

window.addEventListener('online',  updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();

// Trigger sync when back online
window.addEventListener('online', () => {
  if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
    navigator.serviceWorker.controller.postMessage({ type: 'SYNC_NOW' });
  }
});

// ─── Modal Helpers ────────────────────────────────────────────────────────────
window.openModal = function(id) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  // Close on overlay click (outside sheet)
  overlay.addEventListener('click', function handler(e) {
    if (e.target === overlay) {
      closeModal(id);
      overlay.removeEventListener('click', handler);
    }
  });
};

window.closeModal = function(id) {
  const overlay = document.getElementById(id);
  if (!overlay) return;
  overlay.classList.remove('open');
  document.body.style.overflow = '';
};

// Close modal on Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => {
      closeModal(m.id);
    });
  }
});

// ─── Active Nav Item ──────────────────────────────────────────────────────────
(function() {
  const path = window.location.pathname.replace(/^\//, '').replace('.php', '') || 'index';
  document.querySelectorAll('.nav-item').forEach(a => {
    const href = a.getAttribute('href').replace(/^\//, '').replace('.php', '') || 'index';
    if (path === href || (path === '' && href === '') || (path === 'index' && href === '')) {
      a.classList.add('active');
    } else {
      a.classList.remove('active');
    }
  });
})();

// ─── Update Banner ────────────────────────────────────────────────────────────
function showUpdateBanner() {
  const banner = document.createElement('div');
  banner.className = 'alert-bar warning';
  banner.style.cssText = 'position:fixed;top:60px;left:16px;right:16px;z-index:999;';
  banner.innerHTML = `
    <svg viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.01-.25 1.97-.7 2.8l1.46 1.46C19.54 15.03 20 13.57 20 12c0-4.42-3.58-8-8-8z"/></svg>
    App updated. <button onclick="window.location.reload()" style="background:none;border:none;color:inherit;font-weight:700;text-decoration:underline;cursor:pointer;">Reload</button>
  `;
  document.body.appendChild(banner);
}

// ─── Simple Toast Notifications ──────────────────────────────────────────────
window.showToast = function(message, type = 'success', duration = 3000) {
  const toast = document.createElement('div');
  toast.className = `alert-bar ${type}`;
  toast.style.cssText = `
    position: fixed; bottom: calc(var(--nav-height) + env(safe-area-inset-bottom) + 16px);
    left: 16px; right: 16px; z-index: 999;
    animation: slideUp 0.3s ease;
  `;
  const icons = {
    success: '<svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>',
    warning: '<svg viewBox="0 0 24 24"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/></svg>',
    error:   '<svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
  };
  toast.innerHTML = (icons[type] || '') + message;
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateY(10px)';
    toast.style.transition = 'opacity 0.3s, transform 0.3s';
    setTimeout(() => toast.remove(), 300);
  }, duration);
};

// ─── Global Fetch Error Handler ───────────────────────────────────────────────
window.apiFetch = async function(url, options = {}) {
  try {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
      ...options,
    });
    // If server returned non-JSON (e.g. cPanel error page), redirect to login
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      window.location = '/login.php';
      return null;
    }
    const data = await res.json();
    // API always returns HTTP 200; auth failures have code:401 in the JSON body
    if (data && data.code === 401) {
      window.location = '/login.php';
      return null;
    }
    return data;
  } catch (err) {
    console.warn('[API] Network error:', err);
    return { success: false, message: 'Network error – working offline.' };
  }
};

// Slideup animation keyframe
const style = document.createElement('style');
style.textContent = '@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }';
document.head.appendChild(style);
