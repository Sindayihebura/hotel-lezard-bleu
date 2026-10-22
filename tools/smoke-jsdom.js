#!/usr/bin/env node
/**
 * smoke-jsdom.js — Test de fumée des pages dans un DOM headless.
 *
 * Charge chaque page servie par tools/serve.js (ou une URL de base fournie),
 * exécute les scripts et vérifie :
 *  - l'absence d'erreur JavaScript non capturée
 *  - la présence des objets globaux (Forms, App, SupabaseAuth, AuthUI)
 *  - la présence du menu mobile et du pied de page
 *  - les champs attendus sur les formulaires clés
 *
 * Usage :
 *   node tools/serve.js &
 *   node tools/smoke-jsdom.js [http://127.0.0.1:8080]
 */
'use strict';

const { JSDOM, VirtualConsole } = require('jsdom');

const BASE = process.argv[2] || 'http://127.0.0.1:8080';

const PAGES = [
  { url: '/', checks: ['#roomsGrid .card-luxury', '#newsletterForm', '#mobileDrawer'] },
  { url: '/login.html', checks: ['#loginForm #email', '#loginForm #password', 'button.password-toggle'] },
  { url: '/register.html', checks: ['#registerForm #fullName', '#confirmPassword', '#terms', '.password-strength'] },
  { url: '/reset-password.html', checks: ['#resetForm #email', '#updatePasswordForm #newPassword'] },
  {
    url: '/reservation.html',
    checks: ['#bookingForm #checkIn', '#bookingForm #checkOut', '#roomSelect option[value="4"]', '#step3']
  },
  { url: '/contact.html', checks: ['#contactForm #name', '#contactForm #message', '#contactForm #consent'] },
  { url: '/account.html', checks: ['#profileForm #profileName', '#bookingsList', '#mobileDrawer'] },
  { url: '/confirmation.html', checks: ['#bookingReference', '#bookingDetails'] },
  { url: '/chambres.html', checks: ['#roomsGrid .card-luxury'] },
  { url: '/legal.html', checks: ['#mentions', '#cgv', '#confidentialite'] },
  { url: '/404.html', checks: ['.error-code'] }
];

async function checkPage(page) {
  const errors = [];
  const virtualConsole = new VirtualConsole();
  virtualConsole.on('jsdomError', (error) => {
    // Les ressources externes (polices Google) peuvent échouer hors ligne.
    if (/fonts\.googleapis|cdn\.jsdelivr|Could not load/i.test(String(error.message))) return;
    if (/Not implemented: HTMLFormElement.prototype.requestSubmit/.test(error.message)) return;
    // Les pages protégées redirigent vers la connexion : comportement attendu.
    if (/Not implemented: navigation/.test(error.message) && page.url === '/account.html') return;
    errors.push(error.message);
  });
  virtualConsole.on('error', (message) => {
    if (typeof message === 'string' && /Supabase|auth/i.test(message)) return;
    errors.push(String(message));
  });

  let dom;
  try {
    dom = await JSDOM.fromURL(BASE + page.url, {
      runScripts: 'dangerously',
      resources: 'usable',
      pretendToBeVisual: true,
      virtualConsole
    });
  } catch (error) {
    return { page: page.url, ok: false, errors: [`chargement impossible : ${error.message}`] };
  }

  await new Promise((resolve) => setTimeout(resolve, 1400));

  const { window } = dom;
  const document = window.document;

  const globals = ['Forms', 'App', 'SupabaseAuth', 'AuthUI', 'formatPrice'];
  globals.forEach((name) => {
    if (!window[name]) errors.push(`global manquant : window.${name}`);
  });

  page.checks.forEach((selector) => {
    if (!document.querySelector(selector)) errors.push(`sélecteur absent : ${selector}`);
  });

  dom.window.close();
  return { page: page.url, ok: errors.length === 0, errors };
}

(async () => {
  let failed = 0;
  for (const page of PAGES) {
    const result = await checkPage(page);
    if (result.ok) {
      console.log(`✓ ${result.page}`);
    } else {
      failed++;
      console.error(`✗ ${result.page}`);
      result.errors.forEach((error) => console.error('   - ' + error));
    }
  }
  console.log(`\n${PAGES.length - failed}/${PAGES.length} pages valides.`);
  process.exit(failed === 0 ? 0 : 1);
})();
