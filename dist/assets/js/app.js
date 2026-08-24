/**
 * Application JavaScript principale
 * Hôtel Le Lézard Bleu - Version JAMstack Netlify
 *
 * Authentification : Supabase Auth (Google OAuth + Email/Password)
 * Ce fichier gère le UI auth (bouton header) et les helpers globaux.
 * La logique d'auth elle-même est dans supabase-auth.js (module ES).
 */

const API_BASE = '/.netlify/functions';

// ─── État global ────────────────────────────────────────────────────────────
const AppState = {
  user: null,
  currency: localStorage.getItem('hotel_currency') || 'BIF',
  exchangeRate: 6000,
  rooms: []
};

// ─── Auth UI — Supabase-aware ────────────────────────────────────────────────
class Auth {
  /**
   * Vérifie la session Supabase et met à jour le bouton header.
   * Appelé au DOMContentLoaded de chaque page.
   */
  static async checkAuth() {
    try {
      // supabase-auth.js expose window.supabaseClient sur les pages qui le chargent
      // Sur les pages sans le module (reservation.html, confirmation.html, etc.),
      // on vérifie le localStorage directement.
      if (window.SupabaseAuth) {
        const user = await window.SupabaseAuth.getUser();
        if (user) {
          AppState.user = user;
          this.updateAuthUI(user);
          return true;
        }
      } else {
        // Fallback : vérifier la présence d'une clé de session Supabase dans localStorage
        const sessionKey = Object.keys(localStorage).find(k => k.startsWith('sb-') && k.endsWith('-auth-token'));
        if (sessionKey) {
          try {
            const session = JSON.parse(localStorage.getItem(sessionKey));
            if (session?.user) {
              AppState.user = session.user;
              this.updateAuthUI(session.user);
              return true;
            }
          } catch (_) { /* ignore */ }
        }
        // Clé alternative utilisée dans supabase-auth.js
        const hotelSession = localStorage.getItem('hotel_auth_token');
        if (hotelSession) {
          try {
            const parsed = JSON.parse(hotelSession);
            if (parsed?.user) {
              AppState.user = parsed.user;
              this.updateAuthUI(parsed.user);
              return true;
            }
          } catch (_) { /* ignore */ }
        }
      }
    } catch (e) {
      // Session invalide ou expirée — silencieux
    }

    this.updateAuthUI(null);
    return false;
  }

  static logout() {
    if (window.SupabaseAuth) {
      window.SupabaseAuth.signOut().then(() => {
        AppState.user = null;
        this.updateAuthUI(null);
        window.location.href = '/index.html';
      }).catch(() => {
        AppState.user = null;
        localStorage.removeItem('hotel_auth_token');
        window.location.href = '/index.html';
      });
    } else {
      AppState.user = null;
      localStorage.removeItem('hotel_auth_token');
      window.location.href = '/index.html';
    }
  }

  static updateAuthUI(user) {
    const authButton   = document.getElementById('authButton');
    const mobileAuthLink = document.getElementById('mobileAuthLink');

    if (user) {
      const displayName = user.user_metadata?.full_name
        || user.user_metadata?.display_name
        || user.email?.split('@')[0]
        || 'Mon Compte';

      if (authButton) {
        authButton.textContent = '👤 ' + displayName;
        authButton.href = '/account.html';
        authButton.title = 'Accéder à mon compte';
      }
      if (mobileAuthLink) {
        mobileAuthLink.textContent = '👤 Mon Compte';
        mobileAuthLink.href = '/account.html';
      }
    } else {
      if (authButton) {
        authButton.textContent = 'Connexion';
        authButton.href = '/login.html';
      }
      if (mobileAuthLink) {
        mobileAuthLink.textContent = 'Connexion';
        mobileAuthLink.href = '/login.html';
      }
    }
  }

  static getUser() {
    return AppState.user;
  }
}

// ─── API Helper ──────────────────────────────────────────────────────────────
class API {
  static async get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    const url   = `${API_BASE}${endpoint}${query ? '?' + query : ''}`;
    const response = await fetch(url, {
      headers: { 'Content-Type': 'application/json' }
    });
    if (!response.ok) throw new Error(`API Error: ${response.statusText}`);
    return await response.json();
  }

  static async post(endpoint, data = {}) {
    const response = await fetch(`${API_BASE}${endpoint}`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(data)
    });
    if (!response.ok) {
      const err = await response.json().catch(() => ({}));
      throw new Error(err.message || err.error || 'Erreur API');
    }
    return await response.json();
  }
}

// ─── Rooms ────────────────────────────────────────────────────────────────────
class Rooms {
  static async fetchAll(filters = {}) {
    try {
      const data   = await API.get('/rooms', filters);
      AppState.rooms = data.rooms || [];
      return AppState.rooms;
    } catch {
      return this.getFallbackRooms();
    }
  }

  static async checkAvailability(roomId, checkIn, checkOut) {
    try {
      return await API.post('/availability', { roomId, checkIn, checkOut });
    } catch {
      return { available: true };
    }
  }

  static getFallbackRooms() {
    return [
      {
        id: 1, name: 'Suite Présidentielle Tanganyika Vue Lac', category: 'Suite',
        description: 'Vue féerique sur le Lac Tanganyika. Terrasse panoramique, jacuzzi, majordome 24h/24.',
        price_per_night_bif: 3900000, capacity_adults: 3, capacity_children: 2,
        surface_m2: 115, photo_main: '/assets/images/suite_presidentielle.jpg',
        amenities: ['Jacuzzi privé', 'Terrasse panoramique 40m²', 'Majordome 24h/24', 'Vue lac directe', 'Minibar premium']
      },
      {
        id: 2, name: 'Chambre Deluxe Plage & Jardins', category: 'Deluxe',
        description: 'Finitions en bois précieux burundais, terrasse ombragée et vue jardins verdoyants.',
        price_per_night_bif: 2280000, capacity_adults: 2, capacity_children: 1,
        surface_m2: 50, photo_main: '/assets/images/hero_hotel.jpg',
        amenities: ['Terrasse privée', 'Vue jardin', 'WiFi haut débit', 'Climatisation', 'Coffre-fort']
      },
      {
        id: 3, name: 'Chambre Executive Jardin & Spa', category: 'Executive',
        description: 'Accès direct au Spa holistique, relaxation privatif avec vue tropicale.',
        price_per_night_bif: 2700000, capacity_adults: 2, capacity_children: 1,
        surface_m2: 65, photo_main: '/assets/images/spa_piscine.jpg',
        amenities: ['Accès spa inclus', 'Balcon privé', 'Bureau de travail', 'Salle de bain marbre']
      },
      {
        id: 4, name: 'Villa Familiale Lac Tanganyika', category: 'Villa',
        description: 'Villa indépendante avec 3 chambres, salon privé, cuisine et jardin privatif.',
        price_per_night_bif: 5500000, capacity_adults: 6, capacity_children: 4,
        surface_m2: 180, photo_main: '/assets/images/hero_hotel.jpg',
        amenities: ['3 chambres', 'Piscine privée', 'Jardin 100m²', 'Cuisine équipée', 'Service butler']
      }
    ];
  }
}

// ─── Booking ─────────────────────────────────────────────────────────────────
class Booking {
  static async create(bookingData) {
    return await API.post('/bookings', bookingData);
  }

  static async getMyBookings() {
    try { return await API.get('/bookings'); }
    catch { return { bookings: [] }; }
  }

  static calculateNights(checkIn, checkOut) {
    return Math.ceil((new Date(checkOut) - new Date(checkIn)) / 86400000);
  }

  static calculateTotal(pricePerNight, nights, currency = AppState.currency) {
    const total = pricePerNight * nights;
    return currency === 'USD' ? (total / AppState.exchangeRate).toFixed(2) : total;
  }
}

// ─── Contact ─────────────────────────────────────────────────────────────────
class Contact {
  static async submit(formData) {
    return await API.post('/contact', formData);
  }
}

// ─── DateHelper ──────────────────────────────────────────────────────────────
class DateHelper {
  static format(date, locale = 'fr-FR') {
    return new Date(date).toLocaleDateString(locale, { year: 'numeric', month: 'long', day: 'numeric' });
  }
  static formatShort(date, locale = 'fr-FR') {
    return new Date(date).toLocaleDateString(locale, { year: 'numeric', month: '2-digit', day: '2-digit' });
  }
  static toISO(date)        { return new Date(date).toISOString().split('T')[0]; }
  static addDays(date, days){ const d = new Date(date); d.setDate(d.getDate() + days); return d; }
  static isFuture(date)     { return new Date(date) > new Date(); }
}

// ─── PriceHelper ─────────────────────────────────────────────────────────────
class PriceHelper {
  static convertBIFtoUSD(bif) { return (bif / AppState.exchangeRate).toFixed(2); }
  static convertUSDtoBIF(usd) { return Math.round(usd * AppState.exchangeRate); }
  static formatBIF(a) { return Math.round(a).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' BIF'; }
  static formatUSD(a) { return '$ ' + parseFloat(a).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }
}

// ─── Validator ────────────────────────────────────────────────────────────────
class Validator {
  static email(e)          { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }
  static phone(p)          { return /^[\d\s+\-()]+$/.test(p) && p.replace(/\D/g,'').length >= 8; }
  static required(v)       { return v && v.trim().length > 0; }
  static minLength(v, min) { return v && v.length >= min; }
  static date(d)           { const dd = new Date(d); return dd instanceof Date && !isNaN(dd); }
  static futureDate(d)     { return this.date(d) && DateHelper.isFuture(d); }
}

// ─── Storage ─────────────────────────────────────────────────────────────────
class Storage {
  static set(k, v)    { try { localStorage.setItem(`hotel_${k}`, JSON.stringify(v)); } catch(_){} }
  static get(k, def=null) { try { const i=localStorage.getItem(`hotel_${k}`); return i?JSON.parse(i):def; } catch(_){ return def; } }
  static remove(k)    { localStorage.removeItem(`hotel_${k}`); }
}

// ─── Loading ──────────────────────────────────────────────────────────────────
class Loading {
  static show(message = 'Chargement...') {
    let l = document.getElementById('globalLoader');
    if (!l) {
      l = document.createElement('div');
      l.id = 'globalLoader';
      l.innerHTML = `<div class="loader-overlay"><div class="loader-content"><div class="loader-spinner"></div><p class="loader-text">${message}</p></div></div>`;
      document.body.appendChild(l);
    }
    l.style.display = 'block';
  }
  static hide() {
    const l = document.getElementById('globalLoader');
    if (l) l.style.display = 'none';
  }
}

// ─── Initialisation ──────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
  // Mise à jour des prix selon la devise sauvegardée
  if (typeof window.updateAllPricesOnPage === 'function') {
    window.updateAllPricesOnPage(AppState.currency);
  }

  // Vérification auth Supabase pour le bouton header
  // On attend que supabase-auth.js soit prêt si présent sur la page
  if (window.__supabaseAuthReady) {
    await window.__supabaseAuthReady;
  }
  await Auth.checkAuth();

  // Écouter les changements d'état auth pour mettre à jour le bouton en temps réel
  if (window.SupabaseAuth) {
    window.SupabaseAuth.onAuthStateChange((event, session) => {
      if (event === 'SIGNED_IN' && session?.user) {
        AppState.user = session.user;
        Auth.updateAuthUI(session.user);
      } else if (event === 'SIGNED_OUT') {
        AppState.user = null;
        Auth.updateAuthUI(null);
      }
    });
  }
});

// ─── Export global ────────────────────────────────────────────────────────────
window.App = {
  Auth, API, Rooms, Booking, Contact,
  DateHelper, PriceHelper, Validator, Storage, Loading,
  State: AppState
};
