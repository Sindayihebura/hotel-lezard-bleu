/**
 * contact.js — Formulaire de contact
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('contactForm');
    const submit = document.getElementById('contactSubmit');
    const alertBox = document.getElementById('contactAlert');
    const success = document.getElementById('contactSuccess');
    if (!form || !window.Forms || !window.App) return;

    function setAlert(message, type) {
      if (!message) {
        alertBox.hidden = true;
        alertBox.innerHTML = '';
        return;
      }
      const icons = { success: '✓', error: '⚠', warning: '⚠', info: 'ℹ' };
      alertBox.className = 'alert alert-' + (type || 'info');
      alertBox.innerHTML = `<span aria-hidden="true">${icons[type] || 'ℹ'}</span><span>${window.Forms.utils.escapeHtml(
        message
      )}</span>`;
      alertBox.hidden = false;
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    window.Forms.attach(form, {
      onSubmit: async (data, _form, ui) => {
        setAlert('', 'info');
        success.hidden = true;
        ui.setLoading(submit, 'Envoi en cours…');

        try {
          const result = await window.App.Contact.submit({
            name: data.name,
            email: data.email,
            phone: data.phone || null,
            subject: data.subject,
            message: data.message
          });

          success.hidden = false;
          form.reset();
          window.Forms.enhance(form);
          window.showToast('Message envoyé. Merci !', 'success');

          if (result.demo) {
            setAlert(
              "Mode démonstration : le service d'envoi n'est pas joignable. Votre message a été conservé sur cet appareil. " +
                'Pour une réponse immédiate, appelez le +257 22 00 00 00.',
              'warning'
            );
          }
        } catch (error) {
          setAlert(error.message || "L'envoi a échoué. Réessayez ou appelez la réception.", 'error');
        } finally {
          ui.clearLoading(submit);
        }
      }
    });
  });
})();
