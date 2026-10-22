/**
 * account.js — Espace client : profil + historique des réservations.
 */
(function () {
  'use strict';

  const $ = (id) => document.getElementById(id);
  const state = { user: null };

  function setAlert(message, type) {
    const box = $('accountAlert');
    if (!box) return;
    if (!message) {
      box.hidden = true;
      box.innerHTML = '';
      return;
    }
    const icons = { success: '✓', error: '⚠', warning: '⚠', info: 'ℹ' };
    box.className = 'alert alert-' + (type || 'info');
    box.innerHTML = `<span aria-hidden="true">${icons[type] || 'ℹ'}</span><span>${window.Forms.utils.escapeHtml(
      message
    )}</span>`;
    box.hidden = false;
  }

  function displayName(user) {
    const meta = user.user_metadata || {};
    return meta.full_name || meta.display_name || (user.email ? user.email.split('@')[0] : 'Membre');
  }

  function hydrateProfile(user) {
    state.user = user;
    const name = displayName(user);
    $('welcomeMessage').textContent = `Bienvenue, ${name} !`;
    $('profileName').value = name;
    $('profileEmail').value = user.email || '';
    $('profilePhone').value = (user.user_metadata && user.user_metadata.phone) || user.phone || '';

    const avatar = $('profileAvatar');
    const avatarUrl = user.user_metadata && (user.user_metadata.avatar_url || user.user_metadata.picture);
    avatar.innerHTML = '';
    if (avatarUrl) {
      const img = document.createElement('img');
      img.src = avatarUrl;
      img.alt = '';
      avatar.appendChild(img);
    } else {
      avatar.textContent = name.charAt(0).toUpperCase();
    }

    const provider = (user.app_metadata && user.app_metadata.provider) || 'email';
    const createdAt = user.created_at ? new Date(user.created_at) : null;
    const meta = $('accountMeta');
    meta.innerHTML = '';
    const rows = [
      ['Connexion', provider === 'google' ? 'Google' : 'E-mail et mot de passe'],
      ['Adresse e-mail', user.email_confirmed_at ? 'Vérifiée ✓' : 'Non vérifiée — consultez votre boîte mail'],
      ['Membre depuis', createdAt ? createdAt.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' }) : '—']
    ];
    rows.forEach(([label, value]) => {
      const p = document.createElement('p');
      const strong = document.createElement('strong');
      strong.textContent = label + ' : ';
      p.append(strong, document.createTextNode(value));
      meta.appendChild(p);
    });
  }

  function statusClass(status) {
    const s = String(status || 'confirmed').toLowerCase();
    if (s.includes('cancel')) return 'cancelled';
    if (s.includes('pending') || s.includes('attente')) return 'pending';
    return 'confirmed';
  }

  function renderBookings(bookings, isDemo) {
    const list = $('bookingsList');
    const count = $('bookingsCount');
    count.textContent = `${bookings.length} réservation${bookings.length > 1 ? 's' : ''}`;

    if (!bookings.length) {
      list.innerHTML = `
        <div class="empty-state" style="padding: 2rem 1rem;">
          <div class="empty-state-icon" aria-hidden="true">🏨</div>
          <p class="empty-state-title">Aucune réservation pour le moment</p>
          <p class="empty-state-text">Vos prochains séjours apparaîtront ici.</p>
          <a href="/reservation.html" class="btn btn-gold" style="margin-top: 1rem;">Réserver un séjour</a>
        </div>`;
      return;
    }

    list.innerHTML = '';
    const wrapper = document.createElement('div');
    wrapper.className = 'booking-list';

    bookings.forEach((booking) => {
      const checkIn = booking.check_in || booking.check_in_date || booking.checkIn;
      const checkOut = booking.check_out || booking.check_out_date || booking.checkOut;
      const ref = booking.booking_reference || booking.reference || '—';
      const room = booking.room_name || booking.roomName || 'Chambre';
      const status = statusClass(booking.booking_status || booking.status);

      const item = document.createElement('article');
      item.className = 'booking-item';

      const head = document.createElement('div');
      head.className = 'booking-item-head';

      const refEl = document.createElement('span');
      refEl.className = 'booking-item-ref';
      refEl.textContent = ref;

      const pills = document.createElement('div');
      pills.style.display = 'flex';
      pills.style.gap = '0.5rem';
      pills.style.flexWrap = 'wrap';

      const pill = document.createElement('span');
      pill.className = 'booking-status-pill ' + status;
      pill.textContent = status === 'cancelled' ? 'Annulée' : status === 'pending' ? 'En attente' : 'Confirmée';
      pills.appendChild(pill);

      if (booking.demo) {
        const demo = document.createElement('span');
        demo.className = 'badge-demo';
        demo.textContent = 'démo';
        pills.appendChild(demo);
      }

      head.append(refEl, pills);

      const body = document.createElement('div');
      body.className = 'booking-item-body';

      const cells = [
        ['Séjour', checkIn && checkOut
          ? `${window.App.DateHelper.formatShort(checkIn)} → ${window.App.DateHelper.formatShort(checkOut)}`
          : '—'],
        ['Chambre', room],
        ['Voyageurs', booking.guests || booking.number_of_guests || '—'],
        ['Montant', booking.total_amount_bif || booking.total_amount
          ? window.formatPrice(Number(booking.total_amount_bif || booking.total_amount))
          : 'À confirmer']
      ];

      cells.forEach(([label, value]) => {
        const cell = document.createElement('div');
        const labelEl = document.createElement('span');
        labelEl.textContent = label;
        const valueEl = document.createElement('strong');
        valueEl.textContent = String(value);
        cell.append(labelEl, valueEl);
        body.appendChild(cell);
      });

      item.append(head, body);
      wrapper.appendChild(item);
    });

    list.appendChild(wrapper);

    if (isDemo) {
      setAlert(
        "Certaines réservations proviennent du mode démonstration (enregistrées sur cet appareil, non transmises à la réception).",
        'warning'
      );
    }
  }

  async function loadBookings() {
    try {
      const result = await window.App.Booking.getMyBookings();
      renderBookings(result.bookings || [], !!result.demo);
    } catch (error) {
      $('bookingsList').innerHTML =
        '<p style="color: var(--text-muted); text-align: center; padding: 1.5rem 0;">' +
        "Impossible de charger vos réservations pour l'instant. Réessayez plus tard." +
        '</p>';
      console.warn('[account] bookings:', error.message);
    }
  }

  function bindProfileForm() {
    const form = $('profileForm');
    if (!form) return;

    window.Forms.attach(form, {
      onSubmit: async (data, _form, ui) => {
        const button = $('profileSubmit');
        ui.setLoading(button, 'Enregistrement…');
        try {
          await window.SupabaseAuth.updateProfile({
            full_name: data.fullName,
            display_name: data.fullName,
            phone: data.phone || ''
          });
          const user = await window.SupabaseAuth.getUser();
          if (user) hydrateProfile(user);
          setAlert('Vos informations ont été mises à jour.', 'success');
          window.showToast('Profil mis à jour.', 'success');
        } catch (error) {
          setAlert(error.message, 'error');
        } finally {
          ui.clearLoading(button);
        }
      }
    });
  }

  function bindLogout() {
    document.querySelectorAll('.logout-btn').forEach((btn) => {
      btn.addEventListener('click', async (event) => {
        event.preventDefault();
        try {
          await window.SupabaseAuth.signOut();
          window.location.href = '/login.html';
        } catch (error) {
          setAlert(error.message, 'error');
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', async () => {
    if (!window.App || !window.Forms) return;

    if (!window.SupabaseAuth || !window.SupabaseAuth.isAvailable()) {
      setAlert(
        "L'espace client est momentanément indisponible. Vous pouvez réserver en tant qu'invité ou appeler la réception au +257 22 00 00 00.",
        'warning'
      );
      $('welcomeMessage').textContent = 'Service indisponible';
      $('bookingsList').innerHTML =
        '<p style="color: var(--text-muted); padding: 1rem 0;">Vos réservations seront visibles dès le rétablissement du service.</p>';
      return;
    }

    const user = await window.SupabaseAuth.getUser();
    if (!user) {
      window.location.href = '/login.html?redirect=/account.html';
      return;
    }

    hydrateProfile(user);
    bindProfileForm();
    bindLogout();
    await loadBookings();
  });
})();
