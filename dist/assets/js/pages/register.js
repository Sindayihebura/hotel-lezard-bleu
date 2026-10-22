/**
 * register.js — Création de compte client
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const wrapper = document.getElementById('registerFormWrapper');
    const submitBtn = document.getElementById('submitBtn');
    const googleBtn = document.getElementById('googleSignUpBtn');
    const alertBox = document.getElementById('authAlert');
    const confirmation = document.getElementById('confirmationPanel');

    if (!window.SupabaseAuth || !window.SupabaseAuth.isAvailable()) {
      window.AuthUI.setAlert(
        alertBox,
        "La création de compte en ligne est indisponible pour le moment. Vous pouvez réserver directement en tant qu'invité ou nous appeler au +257 22 00 00 00.",
        'warning'
      );
      if (googleBtn) googleBtn.disabled = true;
      if (submitBtn) submitBtn.disabled = true;
      return;
    }

    window.Forms.attach(form, {
      onSubmit: async (data, _form, ui) => {
        window.AuthUI.setAlert(alertBox, '', 'info');
        ui.setLoading(submitBtn, 'Création du compte…');

        try {
          const result = await window.SupabaseAuth.signUpWithEmail({
            email: data.email,
            password: data.password,
            fullName: data.fullName,
            phone: data.phone || ''
          });

          if (result.needsConfirmation) {
            ui.clearLoading(submitBtn);
            form.hidden = true;
            confirmation.hidden = false;
            window.AuthUI.setAlert(
              alertBox,
              'Vérifiez votre boîte de réception (et vos spams) pour confirmer votre adresse e-mail.',
              'success'
            );
            wrapper.querySelectorAll('button').forEach((btn) => (btn.disabled = true));
            confirmation.scrollIntoView({ behavior: 'smooth', block: 'center' });
          } else {
            window.showToast('Compte créé. Bienvenue !', 'success');
            window.setTimeout(() => {
              window.location.href = '/account.html';
            }, 800);
          }
        } catch (error) {
          window.AuthUI.setAlert(alertBox, error.message, 'error');
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
