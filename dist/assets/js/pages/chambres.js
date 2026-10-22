/**
 * chambres.js — Catalogue des chambres et filtres par catégorie.
 */
(function () {
  'use strict';

  let allRooms = [];
  let currentCategory = 'all';

  const grid = () => document.getElementById('roomsGrid');

  function esc(value) {
    return window.Forms.utils.escapeHtml(String(value == null ? '' : value));
  }

  function priceLabel(room) {
    return window.formatPrice(room.price_per_night_bif);
  }

  function renderRooms(rooms) {
    const container = grid();
    if (!container) return;

    if (!rooms.length) {
      container.innerHTML = `
        <div class="empty-state" style="grid-column: 1 / -1;">
          <div class="empty-state-icon" aria-hidden="true">🔍</div>
          <p class="empty-state-title">Aucune chambre dans cette catégorie</p>
          <p class="empty-state-text">Affichez toutes les chambres pour voir l'ensemble de nos hébergements.</p>
        </div>`;
      return;
    }

    container.innerHTML = rooms
      .map(
        (room) => `
      <article class="card-luxury">
        <div class="card-img-wrapper">
          <img src="${esc(room.photo_main || '/assets/images/hero_hotel.jpg')}"
               alt="${esc(room.name)}" class="card-img" loading="lazy">
          <span class="card-badge">${esc(room.category)}</span>
        </div>
        <div class="card-body">
          <h2 class="card-title">${esc(room.name)}</h2>
          <p class="card-text">${esc(room.description)}</p>

          ${
            room.amenities && room.amenities.length
              ? `<ul class="card-amenities">
                   ${room.amenities.slice(0, 5).map((a) => `<li>${esc(a)}</li>`).join('')}
                 </ul>`
              : ''
          }

          <div class="card-specs">
            <div class="card-spec-item">👥 ${esc(room.max_guests || room.capacity_adults)} pers. max</div>
            <div class="card-spec-item">📐 ${esc(room.surface_m2 || '—')} m²</div>
            <div class="card-spec-item">🌅 Vue lac</div>
          </div>

          <div class="card-footer">
            <div class="card-price">
              <span class="card-price-amount" data-price-bif="${esc(room.price_per_night_bif)}">
                ${esc(priceLabel(room))}
              </span>
              <span class="card-price-unit">par nuitée</span>
            </div>
            <a href="/reservation.html?room_id=${encodeURIComponent(room.id)}"
               class="btn btn-gold" style="padding:.65rem 1.25rem;font-size:.8rem;">
              Réserver
            </a>
          </div>
        </div>
      </article>`
      )
      .join('');
  }

  function applyFilter(category) {
    currentCategory = category;
    const rooms =
      category === 'all' ? allRooms : allRooms.filter((room) => room.category === category);
    renderRooms(rooms);
  }

  function bindFilters() {
    document.querySelectorAll('.filter-btn').forEach((btn) => {
      btn.setAttribute('aria-pressed', btn.classList.contains('active') ? 'true' : 'false');
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach((b) => {
          b.classList.remove('active');
          b.setAttribute('aria-pressed', 'false');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-pressed', 'true');
        applyFilter(btn.dataset.category || 'all');
      });
    });
  }

  async function loadRooms() {
    allRooms = window.App.Rooms.catalogue();
    applyFilter(currentCategory);
    try {
      const fresh = await window.App.Rooms.fetchAll();
      if (fresh && fresh.length) {
        allRooms = fresh;
        applyFilter(currentCategory);
      }
    } catch (error) {
      console.warn('[chambres] catalogue local utilisé :', error.message);
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (!window.App) return;
    bindFilters();
    loadRooms();

    document.addEventListener('currencychange', () => {
      document.querySelectorAll('[data-price-bif]').forEach((el) => {
        const bif = parseFloat(el.getAttribute('data-price-bif'));
        if (!Number.isNaN(bif)) el.textContent = window.formatPrice(bif);
      });
    });
  });
})();
