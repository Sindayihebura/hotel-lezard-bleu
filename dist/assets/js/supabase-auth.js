/**
 * Supabase Authentication Module
 * Hôtel Le Lézard Bleu - Authentification Google OAuth + Email/Password
 * 
 * Sécurité : Utilise uniquement les clés publiques côté client
 * - SUPABASE_URL
 * - SUPABASE_ANON_KEY
 * 
 * JAMAIS exposé :
 * - SUPABASE_SERVICE_ROLE_KEY (serveur uniquement)
 * - Google Client Secret (Supabase uniquement)
 */

// Import Supabase depuis CDN (compatible HTML statique)
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2/+esm';

// Configuration publique Supabase
// Ces valeurs doivent être définies dans les variables d'environnement Netlify
// et injectées au build, ou chargées via une Netlify Function publique
const SUPABASE_CONFIG = {
  url: window.ENV?.SUPABASE_URL || 'https://rwzzpzzwkutpwcqllqzt.supabase.co',
  anonKey: window.ENV?.SUPABASE_ANON_KEY || 'sb_publishable_LbZweT_3THf2p34VHi_iuA_vJo5UcR1'
};

// Validation configuration
if (!SUPABASE_CONFIG.url || !SUPABASE_CONFIG.anonKey) {
  console.warn('⚠️  Configuration Supabase manquante. Authentification non disponible.');
}

// Créer le client Supabase
const supabase = createClient(SUPABASE_CONFIG.url, SUPABASE_CONFIG.anonKey, {
  auth: {
    autoRefreshToken: true,
    persistSession: true,
    detectSessionInUrl: true,
    storage: window.localStorage,
    storageKey: 'hotel_auth_token',
    flowType: 'pkce' // Plus sécurisé pour les applications publiques
  }
});

/**
 * Classe d'authentification centralisée
 */
class SupabaseAuth {
  
  /**
   * Inscription avec email + mot de passe
   */
  static async signUpWithEmail({ email, password, fullName }) {
    try {
      const { data, error } = await supabase.auth.signUp({
        email,
        password,
        options: {
          data: {
            full_name: fullName,
            display_name: fullName
          },
          emailRedirectTo: `${window.location.origin}/account.html`
        }
      });

      if (error) {
        throw error;
      }

      return {
        success: true,
        needsConfirmation: !data.session, // Si pas de session, confirmation email requise
        user: data.user,
        session: data.session
      };
    } catch (error) {
      console.error('Sign up error:', error);
      
      // Messages d'erreur utilisateur-friendly
      let message = 'Une erreur est survenue lors de l\'inscription.';
      
      if (error.message.includes('already registered')) {
        message = 'Cette adresse email est déjà enregistrée. Veuillez vous connecter.';
      } else if (error.message.includes('password')) {
        message = 'Le mot de passe doit contenir au moins 8 caractères.';
      } else if (error.message.includes('email')) {
        message = 'Adresse email invalide.';
      }

      throw new Error(message);
    }
  }

  /**
   * Connexion avec email + mot de passe
   */
  static async signInWithEmail({ email, password }) {
    try {
      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password
      });

      if (error) {
        throw error;
      }

      return {
        success: true,
        user: data.user,
        session: data.session
      };
    } catch (error) {
      console.error('Sign in error:', error);
      
      // Message générique pour ne pas révéler si le compte existe
      throw new Error('Email ou mot de passe incorrect.');
    }
  }

  /**
   * Connexion / Inscription avec Google OAuth
   */
  static async signInWithGoogle() {
    try {
      const { data, error } = await supabase.auth.signInWithOAuth({
        provider: 'google',
        options: {
          redirectTo: `${window.location.origin}/account.html`,
          queryParams: {
            access_type: 'offline',
            prompt: 'consent'
          }
        }
      });

      if (error) {
        throw error;
      }

      // La redirection vers Google se fait automatiquement
      return {
        success: true,
        redirecting: true
      };
    } catch (error) {
      console.error('Google OAuth error:', error);
      
      let message = 'Impossible de se connecter avec Google.';
      
      if (error.message.includes('popup')) {
        message = 'Veuillez autoriser les popups pour vous connecter avec Google.';
      }

      throw new Error(message);
    }
  }

  /**
   * Réinitialisation du mot de passe
   */
  static async resetPassword(email) {
    try {
      const { error } = await supabase.auth.resetPasswordForEmail(email, {
        redirectTo: `${window.location.origin}/reset-password.html`
      });

      if (error) {
        throw error;
      }

      // Ne pas révéler si l'email existe
      return {
        success: true,
        message: 'Si cette adresse email est enregistrée, vous recevrez un lien de réinitialisation.'
      };
    } catch (error) {
      console.error('Reset password error:', error);
      
      // Message générique
      return {
        success: true,
        message: 'Si cette adresse email est enregistrée, vous recevrez un lien de réinitialisation.'
      };
    }
  }

  /**
   * Mise à jour du mot de passe (après reset)
   */
  static async updatePassword(newPassword) {
    try {
      const { data, error } = await supabase.auth.updateUser({
        password: newPassword
      });

      if (error) {
        throw error;
      }

      return {
        success: true,
        user: data.user
      };
    } catch (error) {
      console.error('Update password error:', error);
      
      let message = 'Impossible de mettre à jour le mot de passe.';
      
      if (error.message.includes('same')) {
        message = 'Le nouveau mot de passe doit être différent de l\'ancien.';
      } else if (error.message.includes('password')) {
        message = 'Le mot de passe doit contenir au moins 8 caractères.';
      }

      throw new Error(message);
    }
  }

  /**
   * Déconnexion
   */
  static async signOut() {
    try {
      const { error } = await supabase.auth.signOut();

      if (error) {
        throw error;
      }

      return { success: true };
    } catch (error) {
      console.error('Sign out error:', error);
      throw new Error('Erreur lors de la déconnexion.');
    }
  }

  /**
   * Récupérer l'utilisateur connecté
   */
  static async getUser() {
    try {
      const { data: { user }, error } = await supabase.auth.getUser();

      if (error) {
        throw error;
      }

      return user;
    } catch (error) {
      console.error('Get user error:', error);
      return null;
    }
  }

  /**
   * Récupérer la session active
   */
  static async getSession() {
    try {
      const { data: { session }, error } = await supabase.auth.getSession();

      if (error) {
        throw error;
      }

      return session;
    } catch (error) {
      console.error('Get session error:', error);
      return null;
    }
  }

  /**
   * Écouter les changements d'authentification
   */
  static onAuthStateChange(callback) {
    return supabase.auth.onAuthStateChange((event, session) => {
      callback(event, session);
    });
  }

  /**
   * Vérifier si l'utilisateur est connecté
   */
  static async isAuthenticated() {
    const session = await this.getSession();
    return !!session;
  }

  /**
   * Obtenir le provider de connexion (google, email, etc.)
   */
  static async getAuthProvider() {
    try {
      const user = await this.getUser();
      
      if (!user) return null;

      // Le provider est dans app_metadata ou user_metadata
      const provider = user.app_metadata?.provider || 'email';
      
      return provider;
    } catch (error) {
      console.error('Get provider error:', error);
      return null;
    }
  }

  /**
   * Vérifier si l'utilisateur a confirmé son email
   */
  static async isEmailConfirmed() {
    try {
      const user = await this.getUser();
      return user?.email_confirmed_at != null;
    } catch (error) {
      return false;
    }
  }
}

// Helper pour afficher les messages utilisateur
class AuthUI {
  static showLoading(button, message = 'Chargement...') {
    if (button) {
      button.disabled = true;
      button.dataset.originalText = button.textContent;
      button.innerHTML = `<span style="display: inline-flex; align-items: center; gap: 0.5rem;">
        <svg style="animation: spin 1s linear infinite; width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <circle cx="12" cy="12" r="10" stroke-width="4" opacity="0.25"/>
          <path d="M12 2a10 10 0 0 1 10 10" stroke-width="4" stroke-linecap="round"/>
        </svg>
        ${message}
      </span>`;
      
      // Ajouter l'animation spin si elle n'existe pas
      if (!document.getElementById('spin-animation')) {
        const style = document.createElement('style');
        style.id = 'spin-animation';
        style.textContent = `
          @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
          }
        `;
        document.head.appendChild(style);
      }
    }
  }

  static hideLoading(button) {
    if (button && button.dataset.originalText) {
      button.disabled = false;
      button.textContent = button.dataset.originalText;
    }
  }

  static showMessage(message, type = 'success') {
    if (window.showToast) {
      window.showToast(message, type);
    } else {
      alert(message);
    }
  }

  static showError(error) {
    const message = error.message || 'Une erreur est survenue.';
    this.showMessage(message, 'error');
  }
}

// Validation côté client
class AuthValidator {
  static validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
  }

  static validatePassword(password) {
    return password && password.length >= 8;
  }

  static validatePasswordMatch(password, confirmPassword) {
    return password === confirmPassword;
  }

  static validateFullName(name) {
    return name && name.trim().length >= 2;
  }
}

// Export pour utilisation globale
window.SupabaseAuth = SupabaseAuth;
window.AuthUI = AuthUI;
window.AuthValidator = AuthValidator;
window.supabaseClient = supabase; // Pour usage avancé si nécessaire

// Promesse résolue que les pages peuvent await pour attendre que le module soit prêt
window.__supabaseAuthReady = Promise.resolve();

// Log de disponibilité
console.log('✅ Supabase Auth Module loaded');
console.log('   - Google OAuth available');
console.log('   - Email/Password available');
