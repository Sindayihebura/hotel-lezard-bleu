#!/usr/bin/env node
/**
 * test-flows.js — Tests fonctionnels des parcours clés (jsdom).
 *
 * Vérifie, sans navigateur :
 *  - l'affichage/masquage du mot de passe
 *  - la validation des champs (messages d'erreur, résumé)
 *  - le changement de devise et le formatage des prix
 *  - la création d'une réservation (repli démonstration hors backend)
 *  - le catalogue des chambres et le préremplissage depuis l'URL
 *
 * Usage : node tools/serve.js & node tools/test-flows.js
 */
'use strict';

const { JSDOM, VirtualConsole } = require('jsdom');

const BASE = process.argv[2] || 'http://127.0.0.1:8080';
let passed = 0;
let failed = 0;

function assert(condition, label, detail) {
  if (condition) {
    passed++;
    console.log(`  ✓ ${label}`);
  } else {
    failed++;
    console.error(`  ✗ ${label}${detail ? ' — ' + detail : ''}`);
  }
}

async function load(path, options = {}) {
  const virtualConsole = new VirtualConsole();
  virtualConsole.on('jsdomError', (error) => {
    if (/Could not load|fonts\.googleapis|navigation/.test(String(error.message))) return;
    if (options.verbose) console.error('[jsdom]', error.message);
  });
  const dom = await JSDOM.fromURL(BASE + path, {
    runScripts: 'dangerously',
    resources: 'usable',
    pretendToBeVisual: true,
    virtualConsole
  });
  await new Promise((resolve) => setTimeout(resolve, 600));
  return dom;
}

(async () => {
  /* ---------------- Connexion ---------------- */
  console.log('\nConnexion');
  {
    const dom = await load('/login.html');
    const { window } = dom;
    const document = window.document;

    const toggle = document.querySelector('.password-toggle');
    assert(!!toggle, 'bouton afficher/masquer présent');
    const password = document.getElementById('password');
    if (toggle) {
      toggle.click();
      assert(password.type === 'text', 'le clic affiche le mot de passe');
      toggle.click();
      assert(password.type === 'password', 'le second clic le masque');
    }

    const form = document.getElementById('loginForm');
    form.dispatchEvent(new window.Event('submit', { bubbles: true, cancelable: true }));
    await new Promise((r) => setTimeout(r, 100));
    assert(form.querySelectorAll('.field-error:not([hidden])').length >= 2, 'champs vides signalés');
    assert(!!form.querySelector('.form-error-summary'), 'résumé des erreurs affiché');
    assert(document.getElementById('email').getAttribute('aria-invalid') === 'true', 'aria-invalid posé');

    dom.window.close();
  }

  /* ---------------- Devise ---------------- */
  console.log('\nDevise et tarifs');
  {
    const dom = await load('/index.html');
    const { window } = dom;
    const document = window.document;

    window.updateAllPricesOnPage('USD');
    const price = document.querySelector('[data-price-bif]');
    assert(/\$/.test(price.textContent), 'les prix passent en USD', price.textContent);
    window.updateAllPricesOnPage('BIF');
    assert(/BIF/.test(price.textContent), 'les prix reviennent en BIF', price.textContent);
    assert(window.formatPrice(6000, 'USD') === '$ 1.00', 'conversion 6000 BIF = 1 USD');
    assert(typeof window.showToast === 'function', 'notifications disponibles');

    dom.window.close();
  }

  /* ---------------- Réservation ---------------- */
  console.log('\nRéservation');
  {
    const dom = await load('/reservation.html?room_id=2&date_arrivee=2030-01-10&date_depart=2030-01-13&nb_personnes=2');
    const { window } = dom;
    const document = window.document;

    const options = document.querySelectorAll('#roomSelect option');
    assert(options.length === 5, '5 options de chambres (placeholder + 4)', String(options.length));
    assert(document.getElementById('roomSelect').value === '2', 'chambre préremplie depuis l’URL');
    assert(document.getElementById('checkIn').value === '2030-01-10', 'date d’arrivée préremplie');

    // Validation de l'étape 1
    document.getElementById('nextToStep2').click();
    await new Promise((r) => setTimeout(r, 300));
    assert(document.getElementById('step2').hidden === false, 'passage à l’étape 2');

    const create = await window.App.Booking.create({
      checkIn: '2030-01-10',
      checkOut: '2030-01-13',
      guests: 2,
      roomId: 2,
      firstName: 'Aline',
      lastName: 'Ndayishimiye',
      email: 'aline@example.com',
      phone: '+257 79 00 00 00',
      country: 'BI',
      paymentMethod: 'cash'
    });
    assert(create.success === true, 'réservation créée');
    assert(create.demo === true, 'repli démonstration hors backend');
    assert(/^DEMO/.test(create.bookingReference), 'référence de démonstration', create.bookingReference);
    assert(create.booking.total_amount_bif === 2280000 * 3, 'total calculé (3 nuits)');

    const stored = JSON.parse(window.localStorage.getItem('hotel_demo_bookings') || '[]');
    assert(stored.length === 1, 'réservation conservée localement');

    dom.window.close();
  }

  /* ---------------- Contact ---------------- */
  console.log('\nContact');
  {
    const dom = await load('/contact.html');
    const { window } = dom;
    const document = window.document;
    const form = document.getElementById('contactForm');

    assert(window.Forms.validate(form) === false, 'formulaire vide refusé');

    document.getElementById('name').value = 'Jean Bosco';
    document.getElementById('email').value = 'jean@example.com';
    document.getElementById('subject').value = 'Réservation de groupe';
    document.getElementById('message').value = 'Nous souhaitons réserver 5 chambres en mars.';
    document.getElementById('consent').checked = true;
    assert(window.Forms.validate(form) === true, 'formulaire valide accepté');

    const result = await window.App.Contact.submit({
      name: 'Jean Bosco',
      email: 'jean@example.com',
      message: 'Demande de test'
    });
    assert(result.demo === true, 'message conservé en mode démonstration');

    dom.window.close();
  }

  /* ---------------- Compte ---------------- */
  console.log('\nEspace client (non connecté)');
  {
    const dom = await load('/account.html');
    const { window } = dom;
    assert(!!window.App.Booking.getMyBookings, 'API réservations disponible');
    assert(!!window.document.querySelector('.account-card'), 'interface du compte rendue');
    dom.window.close();
  }

  console.log(`\n${passed} test(s) réussi(s), ${failed} échec(s).`);
  process.exit(failed === 0 ? 0 : 1);
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
