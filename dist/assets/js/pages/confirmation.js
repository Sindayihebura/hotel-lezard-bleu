/**
 * confirmation.js — Affiche le récapitulatif de la réservation.
 * Source : dernière réservation enregistrée localement, complétée par l'API
 * si un identifiant est fourni.
 */
(function () {
  'use strict';

  const PAYMENT_LABELS = {
    cash: 'Espèces à l’arrivée',
    lumicash: 'Lumicash',
    ecocash: 'EcoCash',
    bank: 'Virement bancaire',
    card: 'Carte bancaire',
    manual: 'À confirmer avec la réception'
  };

  const $ = (id) => document.getElementById(id);

  function setText(id, value) {
    const el = $(id);
    if (el) el.textContent = value || '—';
  }

  function showAlert(message, type) {
    const box = $('confirmationAlert');
    if (!box) return;
    const icons = { success: '✓', error: '⚠', warning: '⚠', info: 'ℹ' };
    box.className = 'alert alert-' + (type || 'info');
    box.innerHTML = `<span aria-hidden="true">${icons[type] || 'ℹ'}</span><span>${window.Forms.utils.escapeHtml(
      message
    )}</span>`;
    box.hidden = false;
  }

  function render(booking, isDemo) {
    const reference = booking.booking_reference || booking.bookingReference || booking.id || '—';
    setText('bookingReference', reference);

    const checkIn = booking.check_in || booking.check_in_date || booking.checkIn;
    const checkOut = booking.check_out || booking.check_out_date || booking.checkOut;
    const fmtDate = (value) => (window.App ? window.App.DateHelper.format(String(value).slice(0, 10)) : value);
    setText('dates', checkIn && checkOut ? `${fmtDate(checkIn)} → ${fmtDate(checkOut)}` : '—');

    setText('roomName', booking.room_name || booking.roomName || booking.room || '—');

    const guests = booking.guests || booking.number_of_guests;
    setText('guests', guests ? `${guests} personne${Number(guests) > 1 ? 's' : ''}` : '—');

    const payment = booking.paymentMethod || booking.payment_method;
    setText('paymentMethod', PAYMENT_LABELS[payment] || payment || 'À confirmer avec la réception');

    const total = booking.total_amount_bif || booking.total_amount || booking.totalPrice;
    if (total) setText('totalPrice', window.formatPrice ? window.formatPrice(Number(total)) : `${total} BIF`);

    const name = [booking.customer_firstname || booking.firstName, booking.customer_lastname || booking.lastName]
      .filter(Boolean)
      .join(' ');
    const email = booking.customer_email || booking.email || '';
    setText('guestContact', [name, email].filter(Boolean).join(' · ') || '—');

    if (isDemo) {
      showAlert(
        "Mode démonstration : cette demande est enregistrée dans votre navigateur car le service de réservation n'est pas joignable. " +
          'Contactez la réception au +257 22 00 00 00 pour la confirmer.',
        'warning'
      );
    }
  }

  async function loadBooking(reference) {
    const last = window.App ? window.App.Booking.lastBooking() : null;
    if (last && (!reference || last.booking_reference === reference)) {
      render(last, !!last.demo);
      return;
    }

    // Recherche dans les réservations locales (mode démonstration)
    if (window.App) {
      const demo = window.App.Booking.demoBookings().find(
        (item) => item.booking_reference === reference
      );
      if (demo) {
        render(demo, true);
        return;
      }
    }

    if (!reference) {
      render({}, false);
      setText('bookingReference', 'AUCUNE DEMANDE');
      showAlert('Aucune réservation à afficher. Commencez une nouvelle réservation.', 'info');
      return;
    }

    render({ booking_reference: reference }, false);
    showAlert(
      'Détail indisponible en ligne pour le moment. Conservez votre référence et contactez la réception si besoin.',
      'info'
    );
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (!window.Forms || !window.App) return;
    const params = new URLSearchParams(window.location.search);
    const reference = params.get('booking') || params.get('reference') || '';
    loadBooking(reference);
  });
})();
