/**
 * login.js — Connexion espace client
 */
(function () {
  'use strict';

  function getRedirectTarget() {
    const params = new URLSearchParams(window.location.search);
    const redirect = params.get('redirect') || '';
    // Autorise uniquement les chemins internes (anti open-redirect)
    if (/^\/(?!\/)/.test(redirect)) return redirect;
    return '/account.html';
  }

  document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');
    const googleBtn = document.getElementById('googleSignInBtn');
    const params = new URLSearchParams(window.location.search);
    const alertBox = document.getElementById('authAlert');

    // Messages de retour
    if (params.get('verified') === 'true') {
      window.AuthUI.setAlert(alertBox, 'Votre adresse e-mail est confirmée. Vous pouvez vous connecter.', 'success');
    } else if (params.get('registered') === 'true') {
      window.AuthUI.setAlert(alertBox, 'Compte créé. Connectez-vous avec vos identifiants.', 'success');
    } else if (params.get('reset') === '1') {
      window.AuthUI.setAlert(alertBox, 'Mot de passe mis à jour. Connectez-vous avec votre nouveau mot de passe.', 'success');
    } else if (params.get('session') === 'expired') {
      window.AuthUI.setAlert(alertBox, 'Votre session a expiré. Reconnectez-vous pour continuer.', 'warning');
    }

    if (!window.SupabaseAuth || !window.SupabaseAuth.isAvailable()) {
      window.AuthUI.setAlert(
        alertBox,
        "La connexion en ligne est indisponible pour le moment. Vous pouvez réserver en tant qu'invité ou nous appeler au +257 22 00 00 00.",
        'warning'
      );
      if (googleBtn) googleBtn.disabled = true;
      if (submitBtn) submitBtn.disabled = true;
      return;
    }

    // Déjà connecté : proposer directement l'espace client
    const existing = await window.SupabaseAuth.getUser();
    if (existing) {
      window.AuthUI.setAlert(alertBox, 'Vous êtes déjà connecté. Redirection vers votre compte…', 'info');
      window.setTimeout(() => {
        window.location.href = getRedirectTarget();
      }, 900);
      return;
    }

    window.Forms.attach(form, {
      onSubmit: async (data, _form, ui) => {
        window.AuthUI.setAlert(alertBox, '', 'info');
        ui.setLoading(submitBtn, 'Connexion…');
        try {
          await window.SupabaseAuth.signInWithEmail({ email: data.email, password: data.password });
          window.showToast('Connexion réussie. Bienvenue !', 'success');
          window.setTimeout(() => {
            window.location.href = getRedirectTarget();
          }, 700);
        } catch (error) {
          window.AuthUI.setAlert(alertBox, error.message, 'error');
          window.AuthUI.showError(error);
        } finally {
          ui.clearLoading(submitBtn);
        }
      }
    });

    if (googleBtn) {
      googleBtn.addEventListener('click', async () => {
        window.AuthUI.setAlert(alertBox, '', 'info');
        window.AuthUI.showLoading(googleBtn, 'Redirection vers Google…');
        try {
          await window.SupabaseAuth.signInWithGoogle();
        } catch (error) {
          window.AuthUI.hideLoading(googleBtn);
          window.AuthUI.setAlert(alertBox, error.message, 'error');
        }
      });
    }
  });
})();
