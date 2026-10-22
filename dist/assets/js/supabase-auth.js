/**
 * supabase-auth.js — Authentification cliente
 * Hôtel Le Lézard Bleu & Spa
 *
 * Charge le SDK Supabase embarqué (/vendor/supabase.js, aucune dépendance CDN)
 * puis expose :
 *   window.SupabaseAuth  — API d'authentification (email/mot de passe + Google)
 *   window.AuthUI        — messages, états de chargement
 *   window.AuthValidator — validations réutilisables
 *
 * Le module ne casse jamais la page : si la configuration ou le réseau est
 * indisponible, les appels renvoient une erreur explicite en français et
 * les formulaires affichent un message clair au lieu d'échouer en silence.
 */
(function () {
  'use strict';

  const SUPPORT_PHONE = '+257 22 00 00 00';
  const SUPPORT_EMAIL = 'reservation@lezardbleu-hotel.bi';
  const CONFIG_ERROR =
    "Le service de connexion n'est pas configuré (SUPABASE_URL / SUPABASE_ANON_KEY manquants). " +
    'Contactez la réception au ' + SUPPORT_PHONE + '.';

  const env = window.ENV || {};
  const config = {
    url: (env.SUPABASE_URL || '').trim(),
    anonKey: (env.SUPABASE_ANON_KEY || '').trim()
  };

  let client = null;
  let initError = null;

  function initClient() {
    try {
      if (!window.supabase || typeof window.supabase.createClient !== 'function') {
        throw new Error(
          "La bibliothèque d'authentification n'a pas pu être chargée. Vérifiez votre connexion puis rechargez la page."
        );
      }
      if (!config.url || !config.anonKey) throw new Error(CONFIG_ERROR);

      client = window.supabase.createClient(config.url, config.anonKey, {
        auth: {
          autoRefreshToken: true,
          persistSession: true,
          detectSessionInUrl: true,
          storage: window.localStorage,
          storageKey: 'hotel_auth_token',
          flowType: 'pkce'
        }
      });
    } catch (error) {
      initError = error;
      console.warn('[auth] Indisponible :', error.message);
    }
  }

  initClient();

  const unavailable = () =>
    Promise.reject(
      new Error(
        (initError && initError.message) ||
          'Le service de connexion est momentanément indisponible. Réessayez dans un instant.'
      )
    );

  /* ------------------------------------------------------------------ */
  /* Traduction des erreurs Supabase les plus fréquentes                */
  /* ------------------------------------------------------------------ */
  function humanizeError(error) {
    const raw = (error && (error.message || error.error_description)) || '';
    const code = (error && error.code) || '';
    const text = `${code} ${raw}`.toLowerCase();

    if (text.includes('invalid_credentials') || text.includes('invalid login')) {
      return 'E-mail ou mot de passe incorrect.';
    }
    if (text.includes('email_not_confirmed')) {
      return "Votre adresse e-mail n'est pas encore confirmée. Cliquez sur le lien reçu par e-mail, puis réessayez.";
    }
    if (text.includes('user_already_exists') || text.includes('already registered')) {
      return 'Cette adresse e-mail est déjà utilisée. Connectez-vous ou réinitialisez votre mot de passe.';
    }
    if (text.includes('weak_password') || text.includes('password should be')) {
      return 'Mot de passe trop faible : utilisez au moins 8 caractères, avec une lettre et un chiffre.';
    }
    if (text.includes('rate limit') || text.includes('too many') || text.includes('over_email')) {
      return 'Trop de tentatives. Patientez quelques minutes avant de réessayer.';
    }
    if (text.includes('provider is not enabled') || text.includes('unsupported provider')) {
      return 'La connexion Google n’est pas activée sur ce site. Utilisez votre e-mail et votre mot de passe.';
    }
    if (text.includes('failed to fetch') || text.includes('networkerror') || text.includes('load failed')) {
      return 'Connexion au serveur impossible. Vérifiez votre connexion Internet puis réessayez.';
    }
    if (text.includes('email address') && text.includes('invalid')) {
      return 'Cette adresse e-mail est refusée par le serveur. Utilisez une autre adresse.';
    }
    return raw || 'Une erreur est survenue. Veuillez réessayer.';
  }

  function requireClient() {
    if (!client) return unavailable();
    return null;
  }

  /* ------------------------------------------------------------------ */
  /* AuthUI                                                             */
  /* ------------------------------------------------------------------ */
  class AuthUI {
    static showLoading(button, message) {
      if (window.Forms && window.Forms.setLoading) {
        window.Forms.setLoading(button, message || 'Veuillez patienter…');
        return;
      }
      if (button) {
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.textContent = message || 'Veuillez patienter…';
      }
    }

    static hideLoading(button) {
      if (window.Forms && window.Forms.clearLoading) {
        window.Forms.clearLoading(button);
        return;
      }
      if (button && button.dataset.originalText) {
        button.disabled = false;
        button.textContent = button.dataset.originalText;
      }
    }

    static showMessage(message, type) {
      if (window.showToast) window.showToast(message, type || 'success');
    }

    static showError(error) {
      const message = typeof error === 'string' ? error : humanizeError(error);
      this.showMessage(message, 'error');
    }

    /** Bandeau d'alerte en haut d'un formulaire (id="authAlert"). */
    static setAlert(container, message, type) {
      const el = typeof container === 'string' ? document.getElementById(container) : container;
      if (!el) return;
      if (!message) {
        el.innerHTML = '';
        el.hidden = true;
        return;
      }
      const icons = { success: '✓', error: '⚠', warning: '⚠', info: 'ℹ' };
      const kind = type || 'info';
      el.className = `alert alert-${kind === 'error' ? 'error' : kind}`;
      el.innerHTML = `<span aria-hidden="true">${icons[kind] || 'ℹ'}</span><span>${escapeHtml(
        message
      )}</span>`;
      el.hidden = false;
      el.setAttribute('role', kind === 'error' ? 'alert' : 'status');
    }
  }

  /* ------------------------------------------------------------------ */
  /* AuthValidator                                                      */
  /* ------------------------------------------------------------------ */
  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;

  function escapeHtml(value) {
    if (window.Forms && window.Forms.utils && window.Forms.utils.escapeHtml) {
      return window.Forms.utils.escapeHtml(value);
    }
    return String(value).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  class AuthValidator {
    static validateEmail(email) {
      return EMAIL_RE.test((email || '').trim());
    }

    static validatePassword(password) {
      return !!password && password.length >= 8;
    }

    static validateStrongPassword(password) {
      return (
        !!password &&
        password.length >= 8 &&
        /[A-Za-z]/.test(password) &&
        /\d/.test(password)
      );
    }

    static validatePasswordMatch(password, confirmation) {
      return password === confirmation;
    }

    static validateFullName(name) {
      return !!name && name.trim().length >= 2;
    }

    static validatePhone(phone) {
      if (!phone) return true; // facultatif
      const digits = phone.replace(/\D/g, '');
      return digits.length >= 8 && /^[+\d\s().-]+$/.test(phone);
    }
  }

  /* ------------------------------------------------------------------ */
  /* SupabaseAuth                                                       */
  /* ------------------------------------------------------------------ */
  class SupabaseAuth {
    static isAvailable() {
      return !!client;
    }

    static getInitError() {
      return initError ? initError.message : null;
    }

    static async signUpWithEmail({ email, password, fullName, phone }) {
      const pending = requireClient();
      if (pending) return pending;
      const { data, error } = await client.auth.signUp({
        email: (email || '').trim(),
        password,
        options: {
          data: {
            full_name: fullName || '',
            display_name: fullName || '',
            phone: phone || ''
          },
          emailRedirectTo: `${window.location.origin}/login.html?verified=true`
        }
      });
      if (error) throw new Error(humanizeError(error));
      return {
        success: true,
        needsConfirmation: !data.session,
        user: data.user,
        session: data.session
      };
    }

    static async signInWithEmail({ email, password }) {
      const pending = requireClient();
      if (pending) return pending;
      const { data, error } = await client.auth.signInWithPassword({
        email: (email || '').trim(),
        password
      });
      if (error) throw new Error(humanizeError(error));
      return { success: true, user: data.user, session: data.session };
    }

    static async signInWithGoogle() {
      const pending = requireClient();
      if (pending) return pending;
      const { error } = await client.auth.signInWithOAuth({
        provider: 'google',
        options: {
          redirectTo: `${window.location.origin}/account.html`,
          queryParams: { access_type: 'offline', prompt: 'consent' }
        }
      });
      if (error) throw new Error(humanizeError(error));
      return { success: true, redirecting: true };
    }

    static async resetPassword(email) {
      const pending = requireClient();
      if (pending) return pending;
      const { error } = await client.auth.resetPasswordForEmail((email || '').trim(), {
        redirectTo: `${window.location.origin}/reset-password.html`
      });
      if (error) throw new Error(humanizeError(error));
      return {
        success: true,
        message:
          'Si un compte existe pour cette adresse, un lien de réinitialisation vient d’être envoyé. Pensez à vérifier vos spams.'
      };
    }

    static async updatePassword(newPassword) {
      const pending = requireClient();
      if (pending) return pending;
      const { data, error } = await client.auth.updateUser({ password: newPassword });
      if (error) throw new Error(humanizeError(error));
      return { success: true, user: data.user };
    }

    static async updateProfile(attributes) {
      const pending = requireClient();
      if (pending) return pending;
      const { data, error } = await client.auth.updateUser({ data: attributes });
      if (error) throw new Error(humanizeError(error));
      return { success: true, user: data.user };
    }

    static async signOut() {
      if (!client) return { success: true };
      const { error } = await client.auth.signOut();
      if (error) throw new Error(humanizeError(error));
      return { success: true };
    }

    static async getUser() {
      if (!client) return null;
      try {
        const { data } = await client.auth.getUser();
        return data ? data.user : null;
      } catch (error) {
        console.warn('[auth] getUser :', error.message);
        return null;
      }
    }

    static async getSession() {
      if (!client) return null;
      try {
        const { data } = await client.auth.getSession();
        return data ? data.session : null;
      } catch (error) {
        console.warn('[auth] getSession :', error.message);
        return null;
      }
    }

    static async getAccessToken() {
      const session = await this.getSession();
      return session ? session.access_token : null;
    }

    static async onAuthStateChange(callback) {
      if (!client) return { data: { subscription: { unsubscribe() {} } } };
      return client.auth.onAuthStateChange((event, session) => callback(event, session));
    }

    static async isAuthenticated() {
      return !!(await this.getSession());
    }

    static async getAuthProvider() {
      const user = await this.getUser();
      if (!user) return null;
      return (user.app_metadata && user.app_metadata.provider) || 'email';
    }

    static async isEmailConfirmed() {
      const user = await this.getUser();
      return !!(user && user.email_confirmed_at);
    }
  }


  window.SupabaseAuth = SupabaseAuth;
  window.AuthUI = AuthUI;
  window.AuthValidator = AuthValidator;
  window.supabaseClient = client;
  window.__supabaseAuthReady = Promise.resolve();

  document.addEventListener('DOMContentLoaded', () => {
    if (!client) {
      console.warn('[auth] Mode dégradé —', initError && initError.message);
    }
  });
})();
