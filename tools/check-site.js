#!/usr/bin/env node
/**
 * check-site.js — Contrôle qualité du site statique (dist/)
 *
 * Vérifie :
 *  - les liens et ressources internes (href/src/action)
 *  - les identifiants HTML dupliqués
 *  - les champs de formulaire sans étiquette accessible
 *  - la présence des scripts communs sur chaque page
 *
 * Usage : node tools/check-site.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const DIST = path.join(__dirname, '..', 'dist');
const pages = fs.readdirSync(DIST).filter((file) => file.endsWith('.html'));

const errors = [];
const warnings = [];

function allFiles(dir, acc = []) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) allFiles(full, acc);
    else acc.push(full);
  }
  return acc;
}

const files = new Set(
  allFiles(DIST).map((file) => path.relative(DIST, file).split(path.sep).join('/'))
);

// Alias servis par les Netlify Functions (pas des fichiers statiques)
const API_PREFIXES = ['/api/', '/.netlify/functions/'];

for (const page of pages) {
  const html = fs.readFileSync(path.join(DIST, page), 'utf8');

  const refs = [...html.matchAll(/(?:href|src|action)="([^"]+)"/g)].map((m) => m[1]);
  for (const ref of refs) {
    if (/^(https?:|mailto:|tel:|#|javascript:|data:|\/\/)/.test(ref)) continue;
    if (ref.includes('${')) continue;

    const clean = ref.split('?')[0].split('#')[0];
    if (!clean) continue;
    if (API_PREFIXES.some((prefix) => clean.startsWith(prefix))) continue;

    const relative = clean.replace(/^\//, '') || 'index.html';
    if (!files.has(relative)) {
      errors.push(`${page} : ressource introuvable « ${ref} »`);
    }
  }

  // Identifiants dupliqués
  const ids = [...html.matchAll(/\sid="([^"]+)"/g)].map((m) => m[1]);
  const duplicates = ids.filter((id, index) => ids.indexOf(id) !== index);
  [...new Set(duplicates)].forEach((id) => {
    errors.push(`${page} : identifiant dupliqué « ${id} »`);
  });

  // Champs sans étiquette accessible
  const controls = [...html.matchAll(/<(input|select|textarea)\b[^>]*>/gs)];
  for (const control of controls) {
    const tag = control[0];
    if (/type="(hidden|submit|button|reset)"/.test(tag)) continue;

    const idMatch = tag.match(/\sid="([^"]+)"/);
    const id = idMatch ? idMatch[1] : null;
    const hasLabel = id && new RegExp(`<label[^>]*for="${id}"`).test(html);
    const hasAriaLabel = /aria-label=/.test(tag);
    const insideLabel = new RegExp(`<label[^>]*>(?:(?!</label>)[\\s\\S]){0,400}${id ? `id="${id}"` : 'input'}`).test(html);

    if (!hasLabel && !hasAriaLabel && !insideLabel) {
      warnings.push(`${page} : champ sans étiquette explicite (${tag.slice(0, 70)}…)`);
    }
  }

  // Scripts communs
  const requiredScripts = [
    '/env-config.js',
    '/vendor/supabase.js',
    '/assets/js/forms.js',
    '/assets/js/main.js',
    '/assets/js/supabase-auth.js',
    '/assets/js/app.js'
  ];
  for (const script of requiredScripts) {
    if (!html.includes(`src="${script}"`)) {
      errors.push(`${page} : script commun manquant ${script}`);
    }
  }
}

console.log(`\nContrôle de ${pages.length} pages…\n`);
if (warnings.length) {
  console.log(`⚠ ${warnings.length} avertissement(s) :`);
  warnings.forEach((warning) => console.log('  - ' + warning));
}
if (errors.length) {
  console.error(`\n✗ ${errors.length} erreur(s) :`);
  errors.forEach((error) => console.error('  - ' + error));
  process.exit(1);
}
console.log('\n✓ Aucune erreur détectée.');
