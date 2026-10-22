/**
 * app.js — Logique applicative du site
 * Hôtel Le Lézard Bleu & Spa — Bujumbura
 *
 * Contenu :
 *  - catalogue local des chambres (source de vérité pour les formulaires)
 *  - client API (routes /api → Netlify Functions) + mode démonstration explicite
 *  - création / lecture des réservations
 *  - contact et newsletter
 *  - synchronisation de l'état de connexion avec l'en-tête
 */
(function () {
  'use strict';

  const API_BASE = '/api';
  const DEMO_BOOKINGS_KEY = 'hotel_demo_bookings';
  const DEMO_MESSAGES_KEY = 'hotel_demo_messages';
  const ROOMS_CACHE_KEY = 'hotel_rooms_cache';

  /** Catalogue de secours (identique aux données par défaut des Functions). */
  const ROOMS = [
    {
      id: 1,
      name: 'Suite Présidentielle Tanganyika Vue Lac',
      short_name: 'Suite Présidentielle',
      category: 'Suite',
      description:
        'Vue féerique sur le Lac Tanganyika. Terrasse panoramique de 40 m², jacuzzi privé, majordome 24h/24 et minibar premium.',
      price_per_night_bif: 3900000,
      capacity_adults: 3,
      capacity_children: 2,
      max_guests: 4,
      surface_m2: 115,
      photo_main: '/assets/images/suite_presidentielle.jpg',
      amenities: ['Jacuzzi privé', 'Terrasse panoramique', 'Majordome 24h/24', 'Vue lac directe', 'Minibar premium']
    },
    {
      id: 2,
      name: 'Chambre Deluxe Plage & Jardins',
      short_name: 'Chambre Deluxe',
      category: 'Deluxe',
      description:
        'Finitions en bois précieux burundais, terrasse ombragée et vue sur les jardins tropicaux. Ambiance chaleureuse et raffinée.',
      price_per_night_bif: 2280000,
      capacity_adults: 2,
      capacity_children: 1,
      max_guests: 3,
      surface_m2: 50,
      photo_main: '/assets/images/chambre_deluxe.jpg',
      amenities: ['Terrasse privée', 'Vue jardin', 'Climatisation', 'WiFi haut débit', 'Coffre-fort']
    },
    {
      id: 3,
      name: 'Chambre Executive Jardin & Spa',
      short_name: 'Chambre Executive',
      category: 'Executive',
      description:
        'Accès direct au spa holistique et espaces de relaxation privatifs. Parfait pour une escapade bien-être au bord du lac.',
      price_per_night_bif: 2700000,
      capacity_adults: 2,
      capacity_children: 1,
      max_guests: 3,
      surface_m2: 65,
      photo_main: '/assets/images/spa_piscine.jpg',
      amenities: ['Accès spa inclus', 'Balcon privé', 'Bureau de travail', 'Salle de bain en marbre']
    },
    {
      id: 4,
      name: 'Villa Familiale Lac Tanganyika',
      short_name: 'Villa Familiale',
      category: 'Villa',
      description:
        'Villa indépendante de 3 chambres avec salon privé, cuisine équipée et jardin privatif. Idéale pour les familles et les groupes.',
      price_per_night_bif: 5500000,
      capacity_adults: 6,
      capacity_children: 4,
      max_guests: 8,
      surface_m2: 180,
      photo_main: '/assets/images/hero_hotel.jpg',
      amenities: ['3 chambres', 'Piscine privée', 'Jardin privatif', 'Cuisine équipée', 'Service butler']
    }
  ];

  /* ------------------------------------------------------------------ */
  /* Stockage local (mode démonstration)                                */
  /* ------------------------------------------------------------------ */
  const Store = {
    read(key, fallback) {
      try {
        const raw = localStorage.getItem(key);
        return raw ? JSON.parse(raw) : fallback;
      } catch (_) {
        return fallback;
      }
    },
    write(key, value) {
      try {
        localStorage.setItem(key, JSON.stringify(value));
        return true;
      } catch (_) {
        return false;
      }
    }
  };

  /* ------------------------------------------------------------------ */
  /* API                                                               */
  /* ------------------------------------------------------------------ */
  class ServiceUnavailableError extends Error {
    constructor(message) {
      super(message || 'Le service en ligne est momentanément indisponible.');
      this.name = 'ServiceUnavailableError';
      this.demo = true;
    }
  }

  const API = {
    /**
     * Appelle une Netlify Function et renvoie le JSON.
     * @throws {ServiceUnavailableError} si la fonction n'est pas déployée/joignable
     * @throws {Error} si l'API renvoie une erreur métier
     */
    async call(path, options) {
      const opts = options || {};
      let response;
      try {
        response = await fetch(`${API_BASE}${path}`, {
          method: opts.method || 'GET',
          headers: Object.assign(
            { 'Content-Type': 'application/json' },
            opts.token ? { Authorization: `Bearer ${opts.token}` } : {}
          ),
          body: opts.body ? JSON.stringify(opts.body) : undefined
        });
      } catch (_) {
        throw new ServiceUnavailableError(
          "Impossible d'atteindre le serveur. Vérifiez votre connexion Internet."
        );
      }

      const contentType = response.headers.get('content-type') || '';
      const isJson = contentType.includes('application/json');
      if (!response.ok || !isJson) {
        // Function absente (404/501/HTML) → environnement sans backend
        if (!isJson || response.status === 404 || response.status === 405 || response.status === 501) {
          throw new ServiceUnavailableError();
        }
        const payload = await response.json().catch(() => ({}));
        throw new Error(payload.message || payload.error || `Erreur serveur (${response.status})`);
      }

      const payload = await response.json().catch(() => ({}));
      if (payload && payload.success === false) {
        throw new Error(payload.message || payload.error || 'Demande refusée par le serveur.');
      }
      return payload;
    },

    get(path, params, token) {
      const query = params ? new URLSearchParams(params).toString() : '';
      return this.call(`${path}${query ? '?' + query : ''}`, { token });
    },

    post(path, body, token) {
      return this.call(path, { method: 'POST', body, token });
    }
  };

  /* ------------------------------------------------------------------ */
  /* Chambres                                                           */
  /* ------------------------------------------------------------------ */
  const Rooms = {
    catalogue() {
      return ROOMS.map((room) => Object.assign({}, room));
    },

    byId(id) {
      return ROOMS.find((room) => String(room.id) === String(id)) || null;
    },

    async fetchAll() {
      try {
        const data = await API.get('/rooms');
        const rooms = (data && data.rooms) || [];
        if (!rooms.length) throw new ServiceUnavailableError();
        const normalized = rooms.map((room) => ({
          id: room.id,
          name: room.name,
          short_name: room.name,
          category: room.category,
          description: room.description,
          price_per_night_bif: Number(room.price_per_night_bif) || 0,
          capacity_adults: Number(room.capacity_adults) || 2,
          capacity_children: Number(room.capacity_children) || 0,
          max_guests:
            (Number(room.capacity_adults) || 2) + (Number(room.capacity_children) || 0),
          surface_m2: room.surface_m2,
          photo_main: room.photo_main || '/assets/images/hero_hotel.jpg',
          amenities: room.amenities || []
        }));
        Store.write(ROOMS_CACHE_KEY, normalized);
        return normalized;
      } catch (_) {
        return ROOMS.map((room) => Object.assign({}, room));
      }
    },

    /**
     * Vérifie la disponibilité d'une chambre sur une période.
     * @returns {Promise<{status: 'available'|'unavailable'|'unknown', rooms?: Array}>}
     *          « unknown » = service en ligne injoignable (aucune garantie)
     */
    async checkAvailability(roomId, checkIn, checkOut, adults) {
      try {
        const payload = await API.get('/availability', {
          checkin: checkIn,
          checkout: checkOut,
          adults: adults || 1
        });
        const list = Array.isArray(payload.data)
          ? payload.data
          : Array.isArray(payload.rooms)
          ? payload.rooms
          : null;
        if (!list) return { status: 'unknown' };
        const found = list.some((room) => String(room.id) === String(roomId));
        return { status: found ? 'available' : 'unavailable', rooms: list };
      } catch (error) {
        if (error instanceof ServiceUnavailableError) return { status: 'unknown' };
        throw error;
      }
    }
  };

  /* ------------------------------------------------------------------ */
  /* Outils dates                                                       */
  /* ------------------------------------------------------------------ */
  const DateHelper = {
    todayISO() {
      const d = new Date();
      d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
      return d.toISOString().slice(0, 10);
    },
    addDays(date, days) {
      const d = new Date(date);
      d.setDate(d.getDate() + days);
      return d;
    },
    toISO(date) {
      const d = new Date(date);
      d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
      return d.toISOString().slice(0, 10);
    },
    nights(checkIn, checkOut) {
      if (!checkIn || !checkOut) return 0;
      const start = new Date(checkIn + 'T00:00:00');
      const end = new Date(checkOut + 'T00:00:00');
      const diff = Math.round((end - start) / 86400000);
      return diff > 0 ? diff : 0;
    },
    format(date, locale) {
      if (!date) return '—';
      return new Date(date + (String(date).length === 10 ? 'T00:00:00' : '')).toLocaleDateString(
        locale || 'fr-FR',
        { day: '2-digit', month: 'long', year: 'numeric' }
      );
    },
    formatShort(date, locale) {
      if (!date) return '—';
      return new Date(date + (String(date).length === 10 ? 'T00:00:00' : '')).toLocaleDateString(
        locale || 'fr-FR',
        { day: '2-digit', month: '2-digit', year: 'numeric' }
      );
    }
  };

  /* ------------------------------------------------------------------ */
  /* Réservations                                                       */
  /* ------------------------------------------------------------------ */
  function generateReference(prefix) {
    const stamp = Date.now().toString(36).toUpperCase().slice(-6);
    const random = Math.random().toString(36).toUpperCase().slice(2, 5);
    return (prefix || 'LB') + stamp + random;
  }

  const Booking = {
    /**
     * Crée une réservation. Si le backend n'est pas disponible, la demande est
     * enregistrée localement et signalée comme « démonstration ».
     */
    async create(data) {
      const room = Rooms.byId(data.roomId) || {};
      const nights = DateHelper.nights(data.checkIn, data.checkOut);
      const payload = {
        checkIn: data.checkIn,
        checkOut: data.checkOut,
        guests: Number(data.guests) || 2,
        roomId: Number(data.roomId),
        firstName: data.firstName,
        lastName: data.lastName,
        email: data.email,
        phone: data.phone,
        country: data.country || 'BI',
        paymentMethod: data.paymentMethod || 'cash',
        specialRequests: data.specialRequests || '',
        currency: window.currentCurrency || 'BIF',
        nights,
        roomName: room.name || null
      };

      let result;
      try {
        result = await API.post('/bookings', payload);
      } catch (error) {
        if (!(error instanceof ServiceUnavailableError)) throw error;
        result = this.saveDemo(payload);
      }

      const booking = Object.assign(
        {
          booking_reference: result.bookingReference || result.booking_reference || generateReference(),
          room_name: room.name || payload.roomName,
          check_in: payload.checkIn,
          check_out: payload.checkOut,
          guests: payload.guests,
          nights,
          price_per_night_bif: room.price_per_night_bif || 0,
          total_amount_bif: (room.price_per_night_bif || 0) * nights,
          status: 'confirmed',
          created_at: new Date().toISOString()
        },
        (result.booking || {})
      );

      Store.write('hotel_last_booking', booking);
      return {
        success: true,
        demo: !!result.demo,
        bookingId: result.bookingId || booking.booking_reference,
        bookingReference: booking.booking_reference,
        booking
      };
    },

    saveDemo(payload) {
      const reference = generateReference('DEMO');
      const booking = Object.assign({}, payload, {
        booking_reference: reference,
        demo: true,
        created_at: new Date().toISOString()
      });
      const all = Store.read(DEMO_BOOKINGS_KEY, []);
      all.unshift(booking);
      Store.write(DEMO_BOOKINGS_KEY, all.slice(0, 30));
      return {
        success: true,
        demo: true,
        bookingId: reference,
        bookingReference: reference,
        booking
      };
    },

    demoBookings() {
      return Store.read(DEMO_BOOKINGS_KEY, []);
    },

    lastBooking() {
      return Store.read('hotel_last_booking', null);
    },

    async getMyBookings() {
      const token =
        window.SupabaseAuth && window.SupabaseAuth.getAccessToken
          ? await window.SupabaseAuth.getAccessToken()
          : null;
      const demo = this.demoBookings();
      if (!token) return { bookings: demo, demo: true };

      try {
        const data = await API.get('/bookings', null, token);
        return { bookings: (data.bookings || []).concat(demo), demo: false };
      } catch (error) {
        if (error instanceof ServiceUnavailableError) return { bookings: demo, demo: true };
        throw error;
      }
    },

    calculateNights: DateHelper.nights,

    calculateTotal(pricePerNight, nights) {
      return Number(pricePerNight) * Number(nights);
    }
  };

  /* ------------------------------------------------------------------ */
  /* Contact & newsletter                                               */
  /* ------------------------------------------------------------------ */
  const Contact = {
    async submit(data) {
      try {
        return await API.post('/contact', data);
      } catch (error) {
        if (!(error instanceof ServiceUnavailableError)) throw error;
        const messages = Store.read(DEMO_MESSAGES_KEY, []);
        messages.unshift(Object.assign({}, data, { created_at: new Date().toISOString() }));
        Store.write(DEMO_MESSAGES_KEY, messages.slice(0, 20));
        return { success: true, demo: true };
      }
    }
  };

  const Newsletter = {
    async subscribe(email) {
      try {
        return await API.post('/newsletter', { email });
      } catch (error) {
        if (!(error instanceof ServiceUnavailableError)) throw error;
        const list = Store.read('hotel_newsletter', []);
        if (!list.includes(email)) list.push(email);
        Store.write('hotel_newsletter', list);
        return { success: true, demo: true };
      }
    }
  };

  /* ------------------------------------------------------------------ */
  /* En-tête : état de connexion                                        */
  /* ------------------------------------------------------------------ */
  const AuthUIHeader = {
    update(user) {
      const button = document.getElementById('authButton');
      const mobileLink = document.getElementById('mobileAuthLink');
      if (user) {
        const name =
          (user.user_metadata &&
            (user.user_metadata.full_name || user.user_metadata.display_name)) ||
          (user.email ? user.email.split('@')[0] : 'Mon compte');
        if (button) {
          button.textContent = '👤 ' + name.split(' ')[0];
          button.href = '/account.html';
          button.title = 'Accéder à mon compte';
        }
        if (mobileLink) {
          mobileLink.textContent = '👤 Mon compte';
          mobileLink.href = '/account.html';
        }
      } else {
        if (button) {
          button.textContent = 'Connexion';
          button.href = '/login.html';
          button.title = 'Se connecter';
        }
        if (mobileLink) {
          mobileLink.textContent = 'Connexion';
          mobileLink.href = '/login.html';
        }
      }
    },

    async refresh() {
      if (!window.SupabaseAuth) return null;
      const user = await window.SupabaseAuth.getUser();
      this.update(user);
      return user;
    }
  };

  /* ------------------------------------------------------------------ */
  /* Newsletter (formulaire global du pied de page)                     */
  /* ------------------------------------------------------------------ */
  function initNewsletter() {
    const form = document.getElementById('newsletterForm');
    if (!form || !window.Forms) return;

    window.Forms.attach(form, {
      onSubmit: async (data, f, ui) => {
        const button = form.querySelector('button[type="submit"]');
        ui.setLoading(button, 'Envoi…');
        try {
          const result = await Newsletter.subscribe(data.email);
          window.showToast(
            result.demo
              ? 'Merci ! Votre inscription est enregistrée (mode démonstration).'
              : 'Merci ! Vous êtes inscrit à la newsletter privilège.',
            result.demo ? 'warning' : 'success'
          );
          form.reset();
        } catch (error) {
          window.showToast(error.message || "L'inscription a échoué. Réessayez plus tard.", 'error');
        } finally {
          ui.clearLoading(button);
        }
      }
    });
  }

  /* ------------------------------------------------------------------ */
  /* Initialisation                                                     */
  /* ------------------------------------------------------------------ */
  async function init() {
    initNewsletter();
    if (window.__supabaseAuthReady) await window.__supabaseAuthReady;
    await AuthUIHeader.refresh();

    if (window.SupabaseAuth && window.SupabaseAuth.isAvailable()) {
      window.SupabaseAuth.onAuthStateChange((event, session) => {
        if (event === 'SIGNED_IN' && session && session.user) AuthUIHeader.update(session.user);
        if (event === 'SIGNED_OUT') AuthUIHeader.update(null);
        if (event === 'USER_UPDATED' && session && session.user) AuthUIHeader.update(session.user);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /* ------------------------------------------------------------------ */
  /* Export global                                                      */
  /* ------------------------------------------------------------------ */
  window.App = {
    API,
    Rooms,
    Booking,
    Contact,
    Newsletter,
    DateHelper,
    AuthUIHeader,
    ServiceUnavailableError,
    Storage: Store,
    State: { currency: window.currentCurrency || 'BIF', rooms: ROOMS }
  };

  // Compatibilité avec les scripts existants
  window.App.Loading = {
    show(message) {
      const loader = document.getElementById('globalLoader');
      if (loader) {
        loader.style.display = 'flex';
        const text = loader.querySelector('.loader-text');
        if (text && message) text.textContent = message;
      }
    },
    hide() {
      const loader = document.getElementById('globalLoader');
      if (loader) loader.style.display = 'none';
    }
  };
  window.App.Validator = {
    email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value || ''),
    phone: (value) => (value || '').replace(/\D/g, '').length >= 8
  };
  window.App.PriceHelper = window.App.PriceHelper || {};
})();
