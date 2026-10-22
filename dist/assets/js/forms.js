/**
 * forms.js — Système de formulaires unifié
 * Hôtel Le Lézard Bleu & Spa — Bujumbura
 *
 * - Validation française cohérente (champ par champ + résumé)
 * - Messages d'erreur accessibles (aria-invalid / aria-describedby / role=alert)
 * - Œil d'affichage du mot de passe, indicateur de robustesse
 * - Contraintes de dates (arrivée / départ), formatage du téléphone
 * - États de chargement de bouton normalisés
 *
 * API :
 *   Forms.attach(formElement, { onSubmit: async (data, form, helpers) => {} })
 *   Forms.validate(formElement)        -> bool
 *   Forms.notify(message, type)        -> toast global
 *   Forms.setLoading(button, text)
 *   Forms.clearLoading(button)
 *   Forms.showSummary(form, messages[])
 *   Forms.clearErrors(form)
 *   Forms.setFieldError(input, message)
 *   Forms.read(form)                   -> objet clé/valeur nettoyé
 */
(function () {
  'use strict';

  const MESSAGES = {
    required: 'Ce champ est obligatoire.',
    email: 'Saisissez une adresse e-mail valide (ex. nom@domaine.com).',
    tel: 'Saisissez un numéro de téléphone valide (au moins 8 chiffres).',
    name: 'Indiquez au moins 2 caractères.',
    number: 'Saisissez un nombre valide.',
    min: (n) => `La valeur minimale est ${n}.`,
    max: (n) => `La valeur maximale est ${n}.`,
    minlength: (n) => `Ce champ doit contenir au moins ${n} caractères.`,
    maxlength: (n) => `Ce champ ne peut pas dépasser ${n} caractères.`,
    date: 'Saisissez une date valide.',
    datePast: 'La date ne peut pas être dans le passé.',
    dateAfter: (label) => `La date doit être postérieure à ${label}.`,
    match: 'Les deux valeurs ne correspondent pas.',
    checked: 'Merci de cocher cette case pour continuer.',
    password: 'Utilisez au moins 8 caractères, avec une lettre et un chiffre.'
  };

  const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;
  const ISO_DATE_RE = /^\d{4}-\d{2}-\d{2}$/;

  function isVisible(el) {
    return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));
  }

  function errorIdFor(field) {
    return (field.id || field.name || 'field') + '-error';
  }

  function getErrorElement(field) {
    const id = errorIdFor(field);
    let el = document.getElementById(id);
    if (!el) {
      el = document.createElement('p');
      el.id = id;
      el.className = 'field-error';
      el.setAttribute('role', 'alert');
      el.hidden = true;
      const group = field.closest('.form-group') || field.parentElement;
      group.appendChild(el);
    }
    return el;
  }

  function setFieldError(field, message) {
    field.classList.add('error');
    field.classList.remove('success');
    field.setAttribute('aria-invalid', 'true');
    const el = getErrorElement(field);
    el.textContent = message;
    el.hidden = false;
    field.setAttribute('aria-describedby', el.id);
  }

  function clearFieldError(field) {
    field.classList.remove('error');
    field.removeAttribute('aria-invalid');
    const el = document.getElementById(errorIdFor(field));
    if (el) {
      el.textContent = '';
      el.hidden = true;
      field.removeAttribute('aria-describedby');
    }
  }

  function todayISO() {
    const d = new Date();
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    return d.toISOString().slice(0, 10);
  }

  function addDaysISO(iso, days) {
    const d = new Date(iso + 'T00:00:00');
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
  }

  /** Validation d'un champ ; renvoie un message d'erreur ou null. */
  function validateField(field) {
    if (!field || field.disabled || field.type === 'hidden') return null;
    const value = (field.value || '').trim();
    const required = field.required || field.dataset.required === 'true';
    const rule = field.dataset.rule || '';

    if (field.type === 'checkbox') {
      if (required && !field.checked) return MESSAGES.checked;
      return null;
    }

    if (field.type === 'radio') {
      if (!required) return null;
      const group = field.form
        ? field.form.querySelectorAll(`input[type="radio"][name="${field.name}"]`)
        : [field];
      return Array.from(group).some((r) => r.checked) ? null : MESSAGES.required;
    }

    if (!value) {
      return required ? MESSAGES.required : null;
    }

    if (field.dataset.match) {
      const other = document.querySelector(field.dataset.match);
      if (other && other.value !== field.value) return MESSAGES.match;
    }

    if (rule === 'email' || field.type === 'email') {
      if (!EMAIL_RE.test(value)) return MESSAGES.email;
    }

    if (rule === 'tel' || field.type === 'tel') {
      const digits = value.replace(/\D/g, '');
      if (digits.length < 8) return MESSAGES.tel;
      if (!/^[+\d\s().-]+$/.test(value)) return MESSAGES.tel;
    }

    if (rule === 'name') {
      if (value.length < 2) return MESSAGES.name;
      if (!/[A-Za-zÀ-ÖØ-öø-ÿ'’\- ]{2,}/.test(value)) return MESSAGES.name;
    }

    if (rule === 'password') {
      if (value.length < 8) return 'Le mot de passe doit contenir au moins 8 caractères.';
      if (!/[A-Za-z]/.test(value) || !/\d/.test(value)) {
        return 'Ajoutez au moins une lettre et un chiffre à votre mot de passe.';
      }
    }

    if (rule === 'message') {
      if (value.length < 10) return 'Votre message doit contenir au moins 10 caractères.';
    }

    if (field.type === 'number') {
      const n = Number(value);
      if (Number.isNaN(n)) return MESSAGES.number;
      if (field.min !== '' && n < Number(field.min)) return MESSAGES.min(field.min);
      if (field.max !== '' && n > Number(field.max)) return MESSAGES.max(field.max);
    }

    if (field.type === 'date') {
      if (!ISO_DATE_RE.test(value)) return MESSAGES.date;
      if (field.dataset.min === 'today' && value < todayISO()) return MESSAGES.datePast;
      if (field.dataset.after) {
        const other = document.querySelector(field.dataset.after);
        if (other && other.value && value <= other.value) {
          const label = other.dataset.label || 'la date précédente';
          return MESSAGES.dateAfter(label);
        }
      }
    }

    if (field.minLength > 0 && value.length < field.minLength) {
      return MESSAGES.minlength(field.minLength);
    }
    if (field.maxLength > 0 && value.length > field.maxLength) {
      return MESSAGES.maxlength(field.maxLength);
    }

    return null;
  }

  function validate(form) {
    const fields = Array.from(
      form.querySelectorAll(
        'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea'
      )
    );
    const errors = [];
    let firstInvalid = null;

    fields.forEach((field) => {
      const message = validateField(field);
      if (message) {
        setFieldError(field, message);
        errors.push({ field, message });
        if (!firstInvalid) firstInvalid = field;
      } else {
        clearFieldError(field);
        if (field.type !== 'checkbox' && (field.value || '').trim()) {
          field.classList.add('success');
        }
      }
    });

    if (firstInvalid) {
      const summary = showSummary(form, errors.map((e) => e.message));
      if (summary) summary.focus();
      if (isVisible(firstInvalid)) {
        firstInvalid.focus({ preventScroll: true });
        const rect = firstInvalid.getBoundingClientRect();
        if (rect.top < 0 || rect.bottom > window.innerHeight) {
          firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      }
    } else {
      clearSummary(form);
    }

    return errors.length === 0;
  }

  function showSummary(form, messages) {
    if (!messages || !messages.length) return null;
    let summary = form.querySelector('.form-error-summary');
    if (!summary) {
      summary = document.createElement('div');
      summary.className = 'form-error-summary';
      summary.setAttribute('role', 'alert');
      summary.setAttribute('tabindex', '-1');
      form.insertBefore(summary, form.firstChild);
    }
    const unique = Array.from(new Set(messages));
    summary.innerHTML =
      '<strong>Veuillez corriger ' +
      (unique.length > 1 ? 'les champs suivants' : 'le champ suivant') +
      ' :</strong><ul>' +
      unique.map((m) => `<li>${escapeHtml(m)}</li>`).join('') +
      '</ul>';
    return summary;
  }

  function clearSummary(form) {
    const summary = form.querySelector('.form-error-summary');
    if (summary) summary.remove();
  }

  function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach((el) => {
      el.textContent = '';
      el.hidden = true;
    });
    form.querySelectorAll('.error, .success').forEach((el) => {
      el.classList.remove('error', 'success');
      el.removeAttribute('aria-invalid');
      el.removeAttribute('aria-describedby');
    });
    clearSummary(form);
  }

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  /** Lecture nettoyée des champs du formulaire. */
  function read(form) {
    const data = {};
    new FormData(form).forEach((value, key) => {
      if (typeof value === 'string') value = value.trim();
      if (value instanceof File) return;
      data[key] = value;
    });
    form.querySelectorAll('input[type="checkbox"][name]').forEach((cb) => {
      if (!(cb.name in data)) data[cb.name] = cb.checked;
    });
    return data;
  }

  function setValues(form, values) {
    Object.entries(values || {}).forEach(([key, value]) => {
      const field = form.elements[key];
      if (!field) return;
      if (field.type === 'checkbox') field.checked = !!value;
      else field.value = value == null ? '' : value;
    });
  }

  function setLoading(button, text) {
    if (!button) return;
    if (!button.dataset.label) button.dataset.label = button.innerHTML;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.innerHTML =
      '<span style="display:inline-flex;align-items:center;gap:0.5rem">' +
      '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" ' +
      'style="animation:lb-spin 1s linear infinite" aria-hidden="true">' +
      '<circle cx="12" cy="12" r="10" stroke-width="4" opacity="0.25"></circle>' +
      '<path d="M12 2a10 10 0 0 1 10 10" stroke-width="4" stroke-linecap="round"></path></svg>' +
      escapeHtml(text || 'Veuillez patienter…') +
      '</span>';
  }

  function clearLoading(button) {
    if (!button) return;
    button.disabled = false;
    button.removeAttribute('aria-busy');
    if (button.dataset.label) {
      button.innerHTML = button.dataset.label;
      delete button.dataset.label;
    }
  }

  function notify(message, type) {
    if (typeof window.showToast === 'function') {
      window.showToast(message, type);
      return;
    }
    if (type === 'error') console.error(message);
    else console.info(message);
  }

  /* ---------- Améliorations automatiques des champs ---------- */

  function addRequiredMarks(root) {
    root.querySelectorAll('input[required], select[required], textarea[required]').forEach((field) => {
      if (field.type === 'hidden') return;
      const label = field.id ? root.querySelector(`label[for="${field.id}"]`) : null;
      if (!label || label.querySelector('.required-mark') || label.querySelector('.optional-mark')) return;
      const mark = document.createElement('span');
      mark.className = 'required-mark';
      mark.setAttribute('aria-hidden', 'true');
      mark.textContent = '*';
      label.appendChild(mark);
    });
  }

  function addOptionalMarks(root) {
    root.querySelectorAll('label[data-optional="true"]').forEach((label) => {
      if (label.querySelector('.optional-mark') || label.querySelector('.required-mark')) return;
      const mark = document.createElement('span');
      mark.className = 'optional-mark';
      mark.textContent = '(facultatif)';
      label.appendChild(mark);
    });
  }

  function insertPasswordToggle(wrapper) {
    if (!wrapper || wrapper.querySelector('.password-toggle')) return;
    const input = wrapper.querySelector('input[type="password"], input[type="text"]');
    if (!input) return;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'password-toggle';
    btn.setAttribute('aria-label', 'Afficher le mot de passe');
    btn.setAttribute('aria-pressed', 'false');
    btn.innerHTML =
      '<svg class="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" ' +
      'fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">' +
      '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>' +
      '<svg class="eye-off-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" ' +
      'fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" style="display:none">' +
      '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>' +
      '<line x1="1" y1="1" x2="23" y2="23"></line></svg>';
    wrapper.appendChild(btn);
  }

  function togglePasswordVisibility(btn) {
    const wrapper = btn.closest('.password-wrapper');
    const input = wrapper && wrapper.querySelector('input');
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    const eye = btn.querySelector('.eye-icon');
    const eyeOff = btn.querySelector('.eye-off-icon');
    if (eye) eye.style.display = show ? 'none' : 'block';
    if (eyeOff) eye.style.display = show ? 'block' : 'none';
    btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    input.focus({ preventScroll: true });
  }

  /** Barre de robustesse sous un champ mot de passe. */
  function addStrengthMeter(input) {
    if (!input || input.dataset.strengthReady) return;
    input.dataset.strengthReady = 'true';
    const meter = document.createElement('div');
    meter.className = 'password-strength';
    meter.setAttribute('data-score', '0');
    meter.innerHTML =
      '<span class="password-strength-bars">' +
      '<span class="password-strength-bar"></span>'.repeat(4) +
      '</span><span class="password-strength-label">Robustesse du mot de passe</span>';
    const label = meter.querySelector('.password-strength-label');

    const update = () => {
      const value = input.value || '';
      let score = 0;
      if (value.length >= 8) score++;
      if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
      if (/\d/.test(value)) score++;
      if (/[^A-Za-z0-9]/.test(value) && value.length >= 10) score++;
      if (!value) score = 0;
      meter.setAttribute('data-score', String(score));
      const labels = ['Robustesse du mot de passe', 'Faible', 'Moyen', 'Bon', 'Excellent'];
      label.textContent = labels[score];
    };

    input.addEventListener('input', update);
    input.addEventListener('blur', update);
    const anchor = input.closest('.password-wrapper') || input;
    anchor.insertAdjacentElement('afterend', meter);
    update();
  }

  function applyDateConstraints(root) {
    const today = todayISO();
    root.querySelectorAll('input[type="date"][data-min="today"]').forEach((input) => {
      input.min = today;
      if (!input.value) input.value = today;
    });
    root.querySelectorAll('input[type="date"][data-after]').forEach((input) => {
      const other = root.querySelector(input.dataset.after) || document.querySelector(input.dataset.after);
      if (!other) return;
      const syncMin = () => {
        if (!other.value) return;
        input.min = addDaysISO(other.value, 1);
        if (input.value && input.value <= other.value) input.value = input.min;
      };
      other.addEventListener('change', syncMin);
      syncMin();
    });
  }

  /** Prépare les champs d'un conteneur (labels, œil, force, dates). */
  function enhance(root) {
    root = root || document;
    addRequiredMarks(root);
    addOptionalMarks(root);
    root.querySelectorAll('.password-wrapper').forEach(insertPasswordToggle);
    root.querySelectorAll('.password-toggle').forEach((btn) => {
      if (btn.dataset.boundToggle) return;
      btn.dataset.boundToggle = 'true';
      btn.addEventListener('click', (event) => {
        event.preventDefault();
        togglePasswordVisibility(btn);
      });
    });
    root.querySelectorAll('input[data-strength="true"]').forEach(addStrengthMeter);
    applyDateConstraints(root);

    root.querySelectorAll('input[type="tel"]').forEach((input) => {
      if (!input.dataset.formatPhone) return;
      input.addEventListener('blur', () => {
        input.value = input.value.replace(/[^\d+ ]/g, '').replace(/\s{2,}/g, ' ').trim();
      });
    });

    // Nettoyage des erreurs dès la saisie
    root.querySelectorAll('input, select, textarea').forEach((field) => {
      if (field.dataset.errorBound) return;
      field.dataset.errorBound = 'true';
      field.addEventListener('input', () => {
        if (field.classList.contains('error') && !validateField(field)) clearFieldError(field);
      });
      field.addEventListener('blur', () => {
        if (field.classList.contains('error')) {
          const message = validateField(field);
          if (message) setFieldError(field, message);
          else clearFieldError(field);
        }
      });
    });
  }

  /**
   * Branche la validation sur un formulaire.
   * @param {HTMLFormElement} form
   * @param {{onSubmit?: Function, onChange?: Function}} options
   */
  function attach(form, options) {
    if (!form || form.dataset.formsBound === 'true') return form;
    options = options || {};
    form.dataset.formsBound = 'true';
    form.noValidate = true;
    enhance(form);

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (form.dataset.submitting === 'true') return;
      clearSummary(form);
      if (!validate(form)) return;

      const data = read(form);
      if (typeof options.onSubmit === 'function') {
        form.dataset.submitting = 'true';
        try {
          await options.onSubmit(data, form, {
            setLoading,
            clearLoading,
            notify,
            validate: () => validate(form),
            reset: (keepValues) => {
              if (!keepValues) form.reset();
              clearErrors(form);
              applyDateConstraints(form);
            }
          });
        } finally {
          form.dataset.submitting = 'false';
        }
      }
    });

    if (typeof options.onChange === 'function') {
      form.addEventListener('change', () => options.onChange(read(form), form));
      form.addEventListener('input', () => options.onChange(read(form), form));
    }

    return form;
  }

  // Revalidation croisée : confirmation de mot de passe
  document.addEventListener('input', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLElement) || !target.dataset || !target.dataset.match) return;
    const other = document.querySelector(target.dataset.match);
    if (!other) return;
    if (other.classList.contains('error')) {
      const message = validateField(other);
      if (message) setFieldError(other, message);
      else clearFieldError(other);
    }
  });

  // Style de l'animation du spinner
  if (!document.getElementById('lb-form-styles')) {
    const style = document.createElement('style');
    style.id = 'lb-form-styles';
    style.textContent = '@keyframes lb-spin{from{transform:rotate(0deg)}to{transform:rotate(360deg)}}';
    document.head.appendChild(style);
  }

  document.addEventListener('DOMContentLoaded', () => enhance(document));

  window.Forms = {
    attach,
    enhance,
    validate,
    validateField,
    setFieldError,
    clearFieldError,
    clearErrors,
    showSummary,
    clearSummary,
    read,
    setValues,
    setLoading,
    clearLoading,
    notify,
    messages: MESSAGES,
    utils: { todayISO, addDaysISO, escapeHtml }
  };
})();
