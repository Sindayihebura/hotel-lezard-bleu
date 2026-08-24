/**
 * Dynamic Reservation Engine & Payment Gateway JavaScript
 * Hotel Le Lézard Bleu Bujumbura (Burundi)
 */

document.addEventListener('DOMContentLoaded', () => {
  const bookingForm = document.getElementById('bookingModuleForm');
  const dateArriveeInput = document.getElementById('date_arrivee');
  const dateDepartInput = document.getElementById('date_depart');
  const nbPersonnesInput = document.getElementById('nb_personnes');
  const nightsCountSpan = document.getElementById('nightsCount');
  const availableRoomsContainer = document.getElementById('availableRoomsContainer');
  const totalAmountDisplay = document.getElementById('totalAmountDisplay');
  const step1 = document.getElementById('bookingStep1');
  const step2 = document.getElementById('bookingStep2');
  const step3 = document.getElementById('bookingStep3');
  const confirmationScreen = document.getElementById('bookingConfirmationScreen');

  let selectedRoom = null;
  let calculatedNights = 1;

  // Date picker initialization & night calculation
  if (dateArriveeInput && dateDepartInput) {
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const todayStr = today.toISOString().split('T')[0];
    const tomorrowStr = tomorrow.toISOString().split('T')[0];

    if (!dateArriveeInput.value) dateArriveeInput.value = todayStr;
    dateArriveeInput.min = todayStr;

    if (!dateDepartInput.value) dateDepartInput.value = tomorrowStr;
    dateDepartInput.min = tomorrowStr;

    function calculateNights() {
      const arr = new Date(dateArriveeInput.value);
      const dep = new Date(dateDepartInput.value);
      if (dep > arr) {
        const diffTime = Math.abs(dep - arr);
        calculatedNights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      } else {
        calculatedNights = 1;
      }
      if (nightsCountSpan) nightsCountSpan.textContent = calculatedNights;
      updateTotalCalculation();
    }

    dateArriveeInput.addEventListener('change', calculateNights);
    dateDepartInput.addEventListener('change', calculateNights);
    calculateNights();
  }

  // Handle Quick Search or Step 1 Check
  if (bookingForm) {
    bookingForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const dateArrivee = dateArriveeInput.value;
      const dateDepart = dateDepartInput.value;
      const nbPersonnes = nbPersonnesInput ? nbPersonnesInput.value : 2;

      try {
        const response = await fetch(`/api/availability?checkin=${encodeURIComponent(dateArrivee)}&checkout=${encodeURIComponent(dateDepart)}&adults=${encodeURIComponent(nbPersonnes)}`);
        const data = await response.json();

        if (data.success && Array.isArray(data.data) && data.data.length > 0) {
          renderAvailableRooms(data.data);
          if (step1 && step2) {
            step1.style.display = 'none';
            step2.style.display = 'block';
            window.scrollTo({ top: step2.offsetTop - 100, behavior: 'smooth' });
          }
        } else {
          if (window.showToast) window.showToast(data.error?.message || 'Aucune chambre disponible pour ces dates.', 'warning');
        }
      } catch (err) {
        console.error(err);
        if (window.showToast) window.showToast('Erreur lors de la recherche des disponibilités.', 'error');
      }
    });
  }

  // Render Available Rooms Grid
  function renderAvailableRooms(rooms) {
    if (!availableRoomsContainer) return;
    availableRoomsContainer.innerHTML = '';

    if (rooms.length === 0) {
      availableRoomsContainer.innerHTML = '<p class="text-center text-gold">Aucune chambre disponible pour ces dates.</p>';
      return;
    }

    rooms.forEach(room => {
      const roomPriceBif = parseInt(room.price_per_night_bif || room.prix_nuit_bif || 2280000, 10);
      const roomTotalBif = roomPriceBif * calculatedNights;

      const card = document.createElement('div');
      card.className = 'card-luxury room-select-card';
      card.innerHTML = `
        <div class="card-img-wrapper">
          <img src="/${room.photo_main || room.photo_url || 'assets/images/hero_hotel.jpg'}" alt="${room.name || room.nom}" class="card-img" loading="lazy">
          <span class="card-badge">${room.category || room.categorie || ''}</span>
        </div>
        <div class="card-body">
          <h3 class="card-title">${room.name || room.nom}</h3>
          <p class="card-text">${room.description ? room.description.substring(0,120)+'…' : ''}</p>
          <div class="card-specs">
            <div class="card-spec-item">👥 ${room.capacity_adults || room.capacite || 2} pers. max</div>
            <div class="card-spec-item">📐 ${room.surface_m2 || room.surface || '—'} m²</div>
            <div class="card-spec-item">🌅 ${room.view || 'Vue Lac Tanganyika'}</div>
          </div>
          <div class="card-footer">
            <div class="card-price">
              <span class="card-price-amount" data-price-bif="${roomPriceBif}">
                ${window.formatPrice ? window.formatPrice(roomPriceBif) : roomPriceBif.toLocaleString('fr-BI') + ' BIF'}
              </span>
              <span class="card-price-unit">par nuitée</span>
            </div>
            <button type="button" class="btn btn-gold btn-select-room"
              data-room-id="${room.id}"
              data-room-name="${(room.name || room.nom || '').replace(/"/g,'&quot;')}"
              data-room-price-bif="${roomPriceBif}">
              Sélectionner
            </button>
          </div>
        </div>
      `;

      availableRoomsContainer.appendChild(card);
    });

    // Attach click handlers to select room
    document.querySelectorAll('.btn-select-room').forEach(btn => {
      btn.addEventListener('click', () => {
        const roomId = btn.getAttribute('data-room-id');
        const roomName = btn.getAttribute('data-room-name');
        const roomPriceBif = parseInt(btn.getAttribute('data-room-price-bif'), 10);

        selectedRoom = { id: roomId, name: roomName, priceBif: roomPriceBif };

        document.querySelectorAll('.btn-select-room').forEach(b => {
          b.textContent = 'Sélectionner';
          b.classList.remove('btn-outline-gold');
          b.classList.add('btn-gold');
        });
        btn.textContent = 'Sélectionnée ✓';
        btn.classList.remove('btn-gold');
        btn.classList.add('btn-outline-gold');

        updateTotalCalculation();

        if (step2 && step3) {
          step2.style.display = 'none';
          step3.style.display = 'block';
          document.getElementById('summaryRoomName').textContent = roomName;
          window.scrollTo({ top: step3.offsetTop - 100, behavior: 'smooth' });
        }
      });
    });
  }

  // Handle Payment Method Switching & Dynamic Fields
  const paymentInputs = document.querySelectorAll('input[name="mode_paiement"]');
  const fieldMobileMoney = document.getElementById('fieldMobileMoney');

  paymentInputs.forEach(input => {
    input.addEventListener('change', () => {
      const mode = input.value;
      if (fieldMobileMoney) {
        fieldMobileMoney.style.display = (mode === 'lumicash' || mode === 'ecocash' || mode === 'easypay') ? 'block' : 'none';
        const mobileInput = document.getElementById('mobile_number');
        if (mobileInput) mobileInput.required = (mode === 'lumicash' || mode === 'ecocash');
      }
    });
  });

  // Calculate live total amount
  const addonInputs = document.querySelectorAll('.addon-checkbox');
  addonInputs.forEach(input => input.addEventListener('change', updateTotalCalculation));

  function updateTotalCalculation() {
    if (!selectedRoom) return;
    let baseTotalBif = selectedRoom.priceBif * calculatedNights;

    let optionsTotalBif = 0;
    addonInputs.forEach(input => {
      if (input.checked) {
        optionsTotalBif += parseInt(input.getAttribute('data-price-bif') || '0', 10);
      }
    });

    const grandTotalBif = baseTotalBif + optionsTotalBif;
    if (totalAmountDisplay) {
      totalAmountDisplay.setAttribute('data-price-bif', grandTotalBif);
      totalAmountDisplay.textContent = window.formatPrice(grandTotalBif);
    }
  }

  // Listen to currency changes from main.js
  window.onCurrencyChange = function() {
    updateTotalCalculation();
  };

  // Final Form Submission via AJAX
  const finalBookingForm = document.getElementById('finalBookingForm');
  if (finalBookingForm) {
    finalBookingForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      if (!selectedRoom) {
        if (window.showToast) window.showToast('Veuillez d\'abord choisir une chambre.', 'error');
        return;
      }

      // Mapper les champs vers l'API modernisée
      const body = {
        room_id:         selectedRoom.id,
        date_arrivee:    dateArriveeInput.value,
        date_depart:     dateDepartInput.value,
        nb_adults:       nbPersonnesInput ? parseInt(nbPersonnesInput.value, 10) : 2,
        nb_children:     0,
        currency_chosen: window.currentCurrency || 'BIF',
        guest_first_name: document.getElementById('guest_first_name')?.value || '',
        guest_last_name:  document.getElementById('guest_last_name')?.value  || '',
        guest_email:      document.getElementById('guest_email')?.value       || '',
        guest_phone:      document.getElementById('guest_phone')?.value       || '',
        payment_method:   document.querySelector('input[name="mode_paiement"]:checked')?.value || 'cash_bif',
        csrf_token:       document.querySelector('input[name="csrf_token"]')?.value || '',
        special_requests: document.getElementById('special_requests')?.value || '',
      };

      try {
        const response = await fetch('/api/bookings', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body)
        });

        const data = await response.json();
        if (data.success && data.data) {
          const d = data.data;
          if (step3 && confirmationScreen) {
            step3.style.display = 'none';
            confirmationScreen.style.display = 'block';
            const refEl    = document.getElementById('confRefCode');
            const roomEl   = document.getElementById('confRoomName');
            const datesEl  = document.getElementById('confDates');
            const totalEl  = document.getElementById('confTotal');
            if (refEl)   refEl.textContent   = d.reference || '';
            if (roomEl)  roomEl.textContent  = selectedRoom.name;
            if (datesEl) datesEl.textContent = `${dateArriveeInput.value} → ${dateDepartInput.value} (${calculatedNights} nuit(s))`;
            if (totalEl) totalEl.textContent = (window.currentCurrency === 'USD')
              ? ('$ ' + (d.total_usd_cents / 100).toFixed(2))
              : (parseInt(d.total_bif, 10).toLocaleString('fr-BI') + ' BIF');
            window.scrollTo({ top: confirmationScreen.offsetTop - 100, behavior: 'smooth' });
          }
        } else {
          if (window.showToast) window.showToast(data.error?.message || 'Erreur lors de la réservation.', 'error');
        }
      } catch (err) {
        console.error('Booking error:', err);
        if (window.showToast) window.showToast('Erreur serveur. Veuillez réessayer.', 'error');
      }
    });
  }
});
