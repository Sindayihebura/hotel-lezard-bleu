/**
 * reservation.js — Parcours de réservation en 3 étapes
 *
 * - chambres et tarifs chargés depuis App.Rooms (API ou catalogue local)
 * - validation par étape (champs, capacité, dates, disponibilité)
 * - récapitulatif recalculé en direct (dates, nuits, devise, total)
 * - création réelle via /.netlify/functions/bookings, avec repli « démonstration »
 */
(function () {
  'use strict';

  const state = {
    rooms: [],
    room: null,
    step: 1,
    currency: (window.currentCurrency || 'BIF')
  };

  const $ = (id) => document.getElementById(id);

  function fmt(amountBif) {
    return window.formatPrice ? window.formatPrice(amountBif, state.currency) : `${amountBif} BIF`;
  }

  function nights() {
    const checkIn = $('checkIn').value;
    const checkOut = $('checkOut').value;
    return window.App ? window.App.DateHelper.nights(checkIn, checkOut) : 0;
  }

  function selectedRoom() {
    const id = $('roomSelect').value;
    return state.rooms.find((room) => String(room.id) === String(id)) || null;
  }

  function setAlert(message, type) {
    const box = $('bookingAlert');
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

  function updateStepUI() {
    document.querySelectorAll('.form-step').forEach((fieldset) => {
      const index = Number(fieldset.id.replace('step', ''));
      fieldset.hidden = index !== state.step;
      fieldset.classList.toggle('is-active', index === state.step);
    });
    document.querySelectorAll('[data-step-indicator]').forEach((item) => {
      const index = Number(item.getAttribute('data-step-indicator'));
      item.classList.toggle('is-active', index === state.step);
      item.classList.toggle('is-done', index < state.step);
      item.setAttribute('aria-current', index === state.step ? 'step' : 'false');
    });
    const card = document.querySelector('.reservation-card');
    if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function goTo(step) {
    state.step = Math.max(1, Math.min(3, step));
    updateStepUI();
    updateSummary();
  }

  /** Valide uniquement les champs d'une étape. */
  function validateStep(stepElement) {
    const fields = stepElement.querySelectorAll('input, select, textarea');
    let firstInvalid = null;
    let valid = true;

    fields.forEach((field) => {
      if (field.type === 'radio') return;
      const message = window.Forms.validateField(field);
      if (message) {
        window.Forms.setFieldError(field, message);
        valid = false;
        if (!firstInvalid) firstInvalid = field;
      } else {
        window.Forms.clearFieldError(field);
      }
    });

    // Groupe de boutons radio (paiement)
    const radios = stepElement.querySelectorAll('input[type="radio"][required]');
    if (radios.length) {
      const checked = Array.from(radios).some((radio) => radio.checked);
      if (!checked) {
        window.Forms.setFieldError(radios[0], 'Choisissez un mode de paiement.');
        valid = false;
        if (!firstInvalid) firstInvalid = radios[0];
      } else {
        window.Forms.clearFieldError(radios[0]);
      }
    }

    if (firstInvalid) firstInvalid.focus({ preventScroll: false });
    return valid;
  }

  function updateSummary() {
    const content = $('summaryContent');
    if (!content) return;

    const checkIn = $('checkIn').value;
    const checkOut = $('checkOut').value;
    const room = selectedRoom();
    const guests = Number($('guests').value) || 0;
    const count = nights();

    if (!checkIn || !checkOut || !room || count <= 0) {
      content.innerHTML = `
        <div class="empty-state" style="padding: 1.5rem 0.5rem;">
          <div class="empty-state-icon" aria-hidden="true">🗓️</div>
          <p class="empty-state-text">
            Choisissez vos dates et votre chambre pour voir le détail et le montant.
          </p>
        </div>`;
      return;
    }

    const total = room.price_per_night_bif * count;
    const payment = document.querySelector('input[name="paymentMethod"]:checked');
    const paymentLabels = {
      cash: 'Espèces à l’arrivée',
      lumicash: 'Lumicash',
      ecocash: 'EcoCash',
      bank: 'Virement bancaire',
      card: 'Carte bancaire'
    };

    const rows = [
      ['Chambre', room.name],
      ['Arrivée', window.App.DateHelper.format(checkIn)],
      ['Départ', window.App.DateHelper.format(checkOut)],
      ['Nuits', `${count} nuit${count > 1 ? 's' : ''}`],
      ['Voyageurs', `${guests} personne${guests > 1 ? 's' : ''}`],
      ['Tarif par nuit', fmt(room.price_per_night_bif)]
    ];

    content.innerHTML = `
      ${rows
        .map(
          ([label, value]) => `
        <div class="booking-summary-item">
          <span class="booking-summary-label">${window.Forms.utils.escapeHtml(label)}</span>
          <span class="booking-summary-value">${window.Forms.utils.escapeHtml(value)}</span>
        </div>`
        )
        .join('')}
      <div class="booking-summary-item">
        <span class="booking-summary-label">Total séjour</span>
        <span class="booking-summary-value booking-summary-total">${window.Forms.utils.escapeHtml(fmt(total))}</span>
      </div>
      <div class="booking-summary-item">
        <span class="booking-summary-label">Paiement</span>
        <span class="booking-summary-value">${window.Forms.utils.escapeHtml(
          payment ? paymentLabels[payment.value] || payment.value : 'À choisir'
        )}</span>
      </div>
      <p class="form-hint" style="margin-top: 0.85rem;">
        ${state.currency === 'USD'
          ? `Conversion indicative (1 USD ≈ ${window.EXCHANGE_RATE} BIF). Le règlement est confirmé par la réception.`
          : 'Tarifs en francs burundais, taxes locales incluses. Aucun frais caché.'}
      </p>
    `;
  }

  function updateNightsHint() {
    const count = nights();
    const box = $('nightsSummary');
    if (!box) return;
    if (count > 0) {
      const room = selectedRoom();
      const total = room ? room.price_per_night_bif * count : 0;
      box.hidden = false;
      box.innerHTML =
        `<strong style="color: var(--accent-gold-primary)">${count} nuit${count > 1 ? 's' : ''}</strong>` +
        (room ? ` · ${window.Forms.utils.escapeHtml(room.name)} — total estimé <strong>${window.Forms.utils.escapeHtml(fmt(total))}</strong>` : '');
    } else {
      box.hidden = true;
      box.innerHTML = '';
    }
  }

  function checkCapacity() {
    const room = selectedRoom();
    const guests = Number($('guests').value);
    const hint = $('capacityHint');
    if (!room) return true;
    if (guests > room.max_guests) {
      hint.innerHTML = `<span style="color:#FCA5A5">Cette chambre accueille ${room.max_guests} personnes maximum.</span>`;
      return false;
    }
    hint.textContent = `Capacité : ${room.max_guests} personnes. Adultes et enfants, chambre comprise.`;
    return true;
  }

  function syncRoomHint() {
    const room = selectedRoom();
    const hint = $('roomHint');
    if (!room) {
      hint.textContent = "Les tarifs s'affichent dans la devise choisie.";
      return;
    }
    hint.textContent = `${room.surface_m2 ? room.surface_m2 + ' m² · ' : ''}${room.max_guests} personnes max · ${fmt(
      room.price_per_night_bif
    )} / nuit`;
  }

  function loadRooms() {
    const select = $('roomSelect');
    const rooms = window.App.Rooms.catalogue();
    state.rooms = rooms;
    select.innerHTML =
      '<option value="">Choisissez une chambre…</option>' +
      rooms
        .map(
          (room) =>
            `<option value="${room.id}">${window.Forms.utils.escapeHtml(room.name)} — ${window.Forms.utils.escapeHtml(
              fmt(room.price_per_night_bif)
            )} / nuit</option>`
        )
        .join('');

    // Mise à jour des tarifs réels si l'API est disponible
    window.App.Rooms.fetchAll().then((fresh) => {
      if (!fresh || !fresh.length) return;
      state.rooms = fresh;
      const current = select.value;
      select.innerHTML =
        '<option value="">Choisissez une chambre…</option>' +
        fresh
          .map(
            (room) =>
              `<option value="${room.id}">${window.Forms.utils.escapeHtml(room.name)} — ${window.Forms.utils.escapeHtml(
                fmt(room.price_per_night_bif)
              )} / nuit</option>`
          )
          .join('');
      if (current) select.value = current;
      syncRoomHint();
      updateSummary();
      updateNightsHint();
    });
  }

  function prefillFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const roomId = params.get('room_id') || params.get('roomId');
    const arrival = params.get('date_arrivee') || params.get('checkIn');
    const departure = params.get('date_depart') || params.get('checkOut');
    const guests = params.get('nb_personnes') || params.get('guests');

    if (roomId) $('roomSelect').value = roomId;
    if (arrival) $('checkIn').value = arrival;
    if (departure) $('checkOut').value = departure;
    if (guests) $('guests').value = guests;

    // Cohérence des dates si elles viennent de l'URL
    if ($('checkIn').value) {
      const next = window.App.DateHelper.toISO(window.App.DateHelper.addDays($('checkIn').value, 1));
      $('checkOut').min = next;
      if (!$('checkOut').value || $('checkOut').value <= $('checkIn').value) $('checkOut').value = next;
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (!window.App || !window.Forms) return;

    const form = $('bookingForm');
    loadRooms();
    prefillFromUrl();
    syncRoomHint();
    updateNightsHint();
    updateSummary();

    // Recalculs
    ['checkIn', 'checkOut', 'guests', 'roomSelect'].forEach((id) => {
      const el = $(id);
      if (!el) return;
      el.addEventListener('change', () => {
        if (id === 'checkIn') {
          const next = window.App.DateHelper.toISO(window.App.DateHelper.addDays($('checkIn').value, 1));
          $('checkOut').min = next;
          if (!$('checkOut').value || $('checkOut').value <= $('checkIn').value) $('checkOut').value = next;
        }
        if (id === 'roomSelect' || id === 'guests') checkCapacity();
        syncRoomHint();
        updateNightsHint();
        updateSummary();
      });
      el.addEventListener('input', updateSummary);
    });

    document.addEventListener('currencychange', (event) => {
      state.currency = event.detail.currency;
      const current = $('roomSelect').value;
      const rooms = state.rooms;
      $('roomSelect').innerHTML =
        '<option value="">Choisissez une chambre…</option>' +
        rooms
          .map(
            (room) =>
              `<option value="${room.id}">${window.Forms.utils.escapeHtml(room.name)} — ${window.Forms.utils.escapeHtml(
                fmt(room.price_per_night_bif)
              )} / nuit</option>`
          )
          .join('');
      $('roomSelect').value = current;
      syncRoomHint();
      updateNightsHint();
      updateSummary();
    });

    document.querySelectorAll('input[name="paymentMethod"]').forEach((radio) => {
      radio.addEventListener('change', () => {
        document.querySelectorAll('.payment-method').forEach((label) => label.classList.remove('selected'));
        if (radio.checked) radio.closest('.payment-method').classList.add('selected');
        updateSummary();
      });
    });

    // Navigation
    $('nextToStep2').addEventListener('click', async () => {
      if (!validateStep($('step1'))) return;
      if (!checkCapacity()) {
        setAlert('Le nombre de voyageurs dépasse la capacité de la chambre sélectionnée.', 'error');
        return;
      }
      setAlert('', 'info');

      const room = selectedRoom();
      if (!room) return;
      const button = $('nextToStep2');
      window.Forms.setLoading(button, 'Vérification…');
      try {
        const availability = await window.App.Rooms.checkAvailability(
          room.id,
          $('checkIn').value,
          $('checkOut').value,
          $('guests').value
        );

        if (availability.status === 'unavailable') {
          setAlert(
            'Cette chambre n’est plus disponible sur ces dates. Choisissez une autre catégorie ou modifiez vos dates.',
            'error'
          );
          return;
        }

        if (availability.status === 'unknown') {
          setAlert(
            'Disponibilité non vérifiable en ligne pour le moment : votre demande reste enregistrée et la réception la confirme sous 24 h.',
            'warning'
          );
        } else {
          setAlert('', 'info');
        }

        goTo(2);
      } catch (error) {
        setAlert(error.message, 'error');
      } finally {
        window.Forms.clearLoading(button);
      }
    });

    $('backToStep1').addEventListener('click', () => goTo(1));

    $('nextToStep3').addEventListener('click', () => {
      if (!validateStep($('step2'))) return;
      setAlert('', 'info');
      goTo(3);
    });

    $('backToStep2').addEventListener('click', () => goTo(2));

    window.Forms.attach(form, {
      onSubmit: async (data, _form, ui) => {
        if (!validateStep($('step3'))) return;

        const room = selectedRoom();
        if (!room) {
          goTo(1);
          setAlert('Sélectionnez une chambre avant de confirmer.', 'error');
          return;
        }
        if (!checkCapacity()) {
          goTo(1);
          setAlert('Le nombre de voyageurs dépasse la capacité de la chambre sélectionnée.', 'error');
          return;
        }

        const submit = $('submitBooking');
        ui.setLoading(submit, 'Enregistrement…');
        setAlert('', 'info');

        try {
          const arrivalLabels = {
            'before-14': 'avant 14h00',
            '14-18': 'entre 14h00 et 18h00',
            '18-22': 'entre 18h00 et 22h00',
            'after-22': 'après 22h00'
          };
          const result = await window.App.Booking.create({
            checkIn: data.checkIn,
            checkOut: data.checkOut,
            guests: data.guests,
            roomId: data.roomId,
            firstName: data.firstName,
            lastName: data.lastName,
            email: data.email,
            phone: data.phone,
            country: data.country,
            paymentMethod: data.paymentMethod,
            specialRequests: [
              data.specialRequests,
              data.arrivalTime ? `Arrivée estimée : ${arrivalLabels[data.arrivalTime] || data.arrivalTime}` : ''
            ]
              .filter(Boolean)
              .join(' — '),
            currency: state.currency
          });

          const reference = result.bookingReference || result.bookingId;
          window.showToast('Réservation enregistrée. Référence ' + reference, 'success');
          const params = new URLSearchParams({ booking: reference });
          if (result.demo) params.set('demo', '1');
          window.location.href = '/confirmation.html?' + params.toString();
        } catch (error) {
          setAlert(error.message || 'La réservation n’a pas pu être enregistrée. Réessayez ou appelez la réception.', 'error');
          ui.clearLoading(submit);
        }
      }
    });
  });
})();
