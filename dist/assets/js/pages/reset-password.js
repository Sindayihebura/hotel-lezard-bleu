/**
 * reset-password.js — Demande et enregistrement d'un nouveau mot de passe.
 *
 * Deux modes :
 *  - demande : saisie de l'e-mail → envoi du lien
 *  - récupération : retour depuis l'e-mail (code / hash / événement PASSWORD_RECOVERY)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', async () => {
    const requestPanel = document.getElementById('requestPanel');
    const updatePanel = document.getElementById('updatePanel');
    const requestForm = document.getElementById('resetForm');
    const updateForm = document.getElementById('updatePasswordForm');
    const requestBtn = document.getElementById('requestBtn');
    const updateBtn = document.getElementById('updateBtn');
    const pageTitle = document.getElementById('pageTitle');
    const pageSubtitle = document.getElementById('pageSubtitle');
    const alertBox = document.getElementById('authAlert');

    if (!window.SupabaseAuth || !window.SupabaseAuth.isAvailable()) {
      window.AuthUI.setAlert(
        alertBox,
        "La réinitialisation en ligne est indisponible. Contactez la réception au +257 22 00 00 00 ou écrivez à reservation@lezardbleu-hotel.bi.",
        'warning'
      );
      if (requestBtn) requestBtn.disabled = true;
      if (updateBtn) updateBtn.disabled = true;
      return;
    }

    function showUpdateMode() {
      pageTitle.textContent = 'Nouveau mot de passe';
      pageSubtitle.textContent = 'Choisissez un nouveau mot de passe pour sécuriser votre compte.';
      requestPanel.hidden = true;
      updatePanel.hidden = false;
      const first = document.getElementById('newPassword');
      if (first) first.focus({ preventScroll: true });
    }

    function showRequestMode() {
      pageTitle.textContent = 'Mot de passe oublié';
      pageSubtitle.textContent =
        "Indiquez l'adresse e-mail de votre compte : nous vous enverrons un lien sécurisé.";
      updatePanel.hidden = true;
      requestPanel.hidden = false;
    }

    // Le SDK traite le lien de récupération et crée une session temporaire.
    const hash = new URLSearchParams(window.location.hash.replace(/^#/, ''));
    const query = new URLSearchParams(window.location.search);
    const looksLikeRecovery =
      hash.get('type') === 'recovery' ||
      query.get('type') === 'recovery' ||
      query.has('code') ||
      hash.has('access_token');

    if (looksLikeRecovery) {
      window.AuthUI.setAlert(alertBox, 'Vérification du lien sécurisé…', 'info');
      // Le SDK échange le code du lien : la session peut arriver avec un léger délai.
      let session = null;
      for (let attempt = 0; attempt < 20; attempt++) {
        session = await window.SupabaseAuth.getSession();
        if (session) break;
        await new Promise((resolve) => setTimeout(resolve, 300));
      }
      if (session) {
        window.AuthUI.setAlert(alertBox, 'Lien valide. Choisissez votre nouveau mot de passe.', 'success');
        showUpdateMode();
      } else {
        window.AuthUI.setAlert(
          alertBox,
          "Ce lien de réinitialisation est expiré ou invalide. Demandez un nouveau lien ci-dessous.",
          'error'
        );
        showRequestMode();
      }
    }

    if (window.SupabaseAuth.onAuthStateChange) {
      window.SupabaseAuth.onAuthStateChange((event) => {
        if (event === 'PASSWORD_RECOVERY') {
          showUpdateMode();
          window.AuthUI.setAlert(alertBox, 'Lien valide. Choisissez votre nouveau mot de passe.', 'success');
        }
      });
    }

    // --- Demande de lien ---
    window.Forms.attach(requestForm, {
      onSubmit: async (data, form, ui) => {
        window.AuthUI.setAlert(alertBox, '', 'info');
        ui.setLoading(requestBtn, 'Envoi en cours…');
        try {
          const result = await window.SupabaseAuth.resetPassword(data.email);
          window.AuthUI.setAlert(alertBox, result.message, 'success');
          form.reset();
        } catch (error) {
          window.AuthUI.setAlert(alertBox, error.message, 'error');
        } finally {
          ui.clearLoading(requestBtn);
        }
      }
    });

    // --- Nouveau mot de passe ---
    window.Forms.attach(updateForm, {
      onSubmit: async (data, form, ui) => {
        window.AuthUI.setAlert(alertBox, '', 'info');
        ui.setLoading(updateBtn, 'Enregistrement…');
        try {
          await window.SupabaseAuth.updatePassword(data.newPassword);
          await window.SupabaseAuth.signOut().catch(() => {});
          window.AuthUI.setAlert(alertBox, 'Mot de passe enregistré. Redirection vers la connexion…', 'success');
          form.reset();
          window.setTimeout(() => {
            window.location.href = '/login.html?reset=1';
          }, 1200);
        } catch (error) {
          window.AuthUI.setAlert(alertBox, error.message, 'error');
          ui.clearLoading(updateBtn);
        }
      }
    });
  });
})();
