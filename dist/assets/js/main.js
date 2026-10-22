/**
 * main.js — Interface globale du site
 * Hôtel Le Lézard Bleu & Spa — Bujumbura
 *
 * Responsabilités :
 *  - devise d'affichage (BIF / USD) et formatage des prix
 *  - menu mobile (drawer) accessible au clavier
 *  - navigation active
 *  - notifications (toasts)
 *  - année du copyright
 */
(function () {
  'use strict';

  const DEFAULT_RATE = 6000;
  const STORAGE_KEY = 'hotel_currency';

  window.EXCHANGE_RATE = Number(window.EXCHANGE_RATE) || DEFAULT_RATE;

  function getStoredCurrency() {
    const stored = localStorage.getItem(STORAGE_KEY);
    return stored === 'USD' || stored === 'BIF' ? stored : 'BIF';
  }

  window.currentCurrency = getStoredCurrency();

  function formatNumber(value) {
    return Math.round(value)
      .toString()
      .replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  /**
   * Formate un montant exprimé en BIF dans la devise demandée.
   * @param {number} amountBif
   * @param {'BIF'|'USD'} [currency]
   */
  window.formatPrice = function (amountBif, currency) {
    const target = currency || window.currentCurrency || 'BIF';
    const rate = Number(window.EXCHANGE_RATE) || DEFAULT_RATE;
    const amount = Number(amountBif) || 0;
    if (target === 'USD') {
      const usd = amount / rate;
      return (
        '$ ' +
        usd
          .toFixed(2)
          .replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
      );
    }
    return formatNumber(amount) + ' BIF';
  };

  function syncCurrencyButtons(currency) {
    document.querySelectorAll('.currency-btn').forEach((btn) => {
      const isActive = btn.getAttribute('data-currency') === currency;
      btn.classList.toggle('active', isActive);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });
  }

  /**
   * Applique la devise partout dans la page.
   * @param {'BIF'|'USD'} currency
   */
  window.updateAllPricesOnPage = function (currency) {
    const target = currency === 'USD' ? 'USD' : 'BIF';
    window.currentCurrency = target;
    try {
      localStorage.setItem(STORAGE_KEY, target);
    } catch (_) {
      /* stockage indisponible (navigation privée) */
    }

    syncCurrencyButtons(target);

    document.querySelectorAll('[data-price-bif]').forEach((el) => {
      const bif = parseFloat(el.getAttribute('data-price-bif'));
      if (!Number.isNaN(bif)) el.textContent = window.formatPrice(bif, target);
    });

    document.querySelectorAll('[data-price-label]').forEach((el) => {
      el.textContent = target === 'USD' ? 'Tarif affiché en USD (1 USD ≈ ' + window.EXCHANGE_RATE + ' BIF)' : 'Tarif affiché en Francs burundais (BIF)';
    });

    document.dispatchEvent(new CustomEvent('currencychange', { detail: { currency: target } }));
    if (typeof window.onCurrencyChange === 'function') window.onCurrencyChange(target);
  };

  /* ------------------------------------------------------------------ */
  /* Notifications                                                      */
  /* ------------------------------------------------------------------ */
  window.showToast = function (message, type) {
    const kind = type === 'error' ? 'error' : type === 'warning' ? 'warning' : 'success';
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      container.setAttribute('role', 'status');
      container.setAttribute('aria-live', 'polite');
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'toast' + (kind === 'error' ? ' toast--error' : kind === 'warning' ? ' toast--warning' : '');
    const icon = kind === 'success' ? '✓' : '⚠';
    const text = document.createElement('span');
    text.textContent = message;
    const iconEl = document.createElement('span');
    iconEl.setAttribute('aria-hidden', 'true');
    iconEl.textContent = icon;
    toast.append(iconEl, text);
    container.appendChild(toast);

    window.setTimeout(() => {
      toast.style.transition = 'opacity .35s ease, transform .35s ease';
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(30px)';
      window.setTimeout(() => toast.remove(), 400);
    }, 5000);
  };

  /* ------------------------------------------------------------------ */
  /* Initialisation                                                     */
  /* ------------------------------------------------------------------ */
  function initDrawer() {
    const drawer = document.getElementById('mobileDrawer');
    const toggle = document.getElementById('mobileToggle');
    const close = document.getElementById('drawerClose');
    if (!drawer || !toggle) return;

    let backdrop = document.getElementById('drawerBackdrop');
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.id = 'drawerBackdrop';
      backdrop.className = 'drawer-backdrop';
      drawer.insertAdjacentElement('afterend', backdrop);
    }

    const open = () => {
      drawer.classList.add('open');
      backdrop.classList.add('visible');
      document.body.classList.add('drawer-open');
      toggle.setAttribute('aria-expanded', 'true');
      drawer.setAttribute('aria-hidden', 'false');
      const firstLink = drawer.querySelector('a, button');
      if (firstLink) firstLink.focus({ preventScroll: true });
    };

    const closeDrawer = () => {
      drawer.classList.remove('open');
      backdrop.classList.remove('visible');
      document.body.classList.remove('drawer-open');
      toggle.setAttribute('aria-expanded', 'false');
      drawer.setAttribute('aria-hidden', 'true');
    };

    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      drawer.classList.contains('open') ? closeDrawer() : open();
    });
    if (close) close.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);
    drawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeDrawer));
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && drawer.classList.contains('open')) {
        closeDrawer();
        toggle.focus();
      }
    });

    drawer.setAttribute('aria-hidden', 'true');
    toggle.setAttribute('aria-expanded', 'false');
  }

  function initCurrency() {
    document.querySelectorAll('.currency-btn').forEach((btn) => {
      if (btn.dataset.currencyBound === 'true') return;
      btn.dataset.currencyBound = 'true';
      const current = btn.getAttribute('data-currency');
      btn.classList.toggle('active', current === window.currentCurrency);
      btn.setAttribute('aria-pressed', current === window.currentCurrency ? 'true' : 'false');
      btn.addEventListener('click', () => window.updateAllPricesOnPage(current));
    });
    window.updateAllPricesOnPage(window.currentCurrency);
  }

  function initActiveNav() {
    const path = window.location.pathname.replace(/\/$/, '') || '/index.html';
    const page = path.split('/').pop() || 'index.html';
    const map = {
      'index.html': '/index.html',
      'presentation.html': '/presentation.html',
      'chambres.html': '/chambres.html',
      'reservation.html': '/reservation.html',
      'galerie.html': '/galerie.html',
      'services.html': '/services.html',
      'conferences.html': '/conferences.html',
      'offres.html': '/offres.html',
      'contact.html': '/contact.html'
    };
    const active = map[page];
    if (!active) return;
    document.querySelectorAll('.nav-link, .drawer-link').forEach((link) => {
      const href = link.getAttribute('href') || '';
      if (href.split('?')[0] === active) {
        link.classList.add('active');
        link.setAttribute('aria-current', 'page');
      }
    });
  }

  function initYears() {
    const year = new Date().getFullYear();
    document.querySelectorAll('#currentYear, [data-year]').forEach((el) => {
      el.textContent = String(year);
    });
  }

  function initScroll() {
    const navbar = document.querySelector('.header-navbar');
    if (!navbar) return;
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 40);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  function init() {
    initDrawer();
    initCurrency();
    initActiveNav();
    initYears();
    initScroll();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
