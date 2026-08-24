/**
 * Main UI JavaScript - Hotel Le Lézard Bleu Bujumbura
 * Global Multi-Currency Manager (BIF / USD)
 */

window.EXCHANGE_RATE = 6000; // 1 USD = 6 000 BIF par défaut
window.currentCurrency = localStorage.getItem('hotel_currency') || 'BIF';

// Helper de conversion et formatage client-side
window.formatPrice = function(amountBif, currency = window.currentCurrency) {
  const rate = window.EXCHANGE_RATE || 6000;
  if (currency === 'USD') {
    const usd = (amountBif / rate).toFixed(2);
    return '$ ' + usd.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  } else {
    return Math.round(amountBif).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' BIF';
  }
};

document.addEventListener('DOMContentLoaded', () => {
  // 1. Refresh prices across DOM elements with data-price-bif
  window.updateAllPricesOnPage = function(currency) {
    window.currentCurrency = currency;
    localStorage.setItem('hotel_currency', currency);

    // Sync active state on currency toggle buttons
    document.querySelectorAll('.currency-btn').forEach(btn => {
      if (btn.getAttribute('data-currency') === currency) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });

    // Update prices on cards/listings
    document.querySelectorAll('[data-price-bif]').forEach(elem => {
      const bifVal = parseFloat(elem.getAttribute('data-price-bif'));
      if (!isNaN(bifVal)) {
        elem.textContent = window.formatPrice(bifVal, currency);
      }
    });

    // Notify booking engine if present
    if (typeof window.onCurrencyChange === 'function') {
      window.onCurrencyChange(currency);
    }
  };

  // Currency switcher click listener
  document.querySelectorAll('.currency-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const newCurrency = btn.getAttribute('data-currency');
      window.updateAllPricesOnPage(newCurrency);

      // Currency preference is already saved in localStorage by updateAllPricesOnPage()
      // No server-side call needed for static site
    });
  });

  // Initialize prices on page load
  window.updateAllPricesOnPage(window.currentCurrency);

  // 2. Sticky Navigation Scroll Effect
  const navbar = document.querySelector('.header-navbar');
  if (navbar) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  }

  // 3. Mobile Drawer Navigation
  const mobileToggle = document.getElementById('mobileToggle');
  const mobileDrawer = document.getElementById('mobileDrawer');
  const drawerClose = document.getElementById('drawerClose');

  if (mobileToggle && mobileDrawer) {
    mobileToggle.addEventListener('click', () => mobileDrawer.classList.add('open'));
  }
  if (drawerClose && mobileDrawer) {
    drawerClose.addEventListener('click', () => mobileDrawer.classList.remove('open'));
  }

  // 4. Toast Notification Helper
  window.showToast = function(message, type = 'success') {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
      toastContainer = document.createElement('div');
      toastContainer.id = 'toastContainer';
      toastContainer.style.cssText = `
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 3000;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
      `;
      document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const isSuccess = type === 'success';
    toast.style.cssText = `
      background: ${isSuccess ? 'rgba(22, 34, 56, 0.95)' : 'rgba(80, 20, 20, 0.95)'};
      color: #fff;
      border: 1px solid ${isSuccess ? '#D4AF37' : '#EF4444'};
      padding: 1rem 1.5rem;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-size: 0.95rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      backdrop-filter: blur(10px);
    `;

    toast.innerHTML = `<span>${isSuccess ? '✨' : '⚠️'}</span><div>${message}</div>`;
    toastContainer.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100px)';
      toast.style.transition = 'all 0.4s ease';
      setTimeout(() => toast.remove(), 400);
    }, 4000);
  };
});
