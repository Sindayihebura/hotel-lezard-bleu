#!/usr/bin/env node
/**
 * sync-legacy-assets.js
 *
 * Le site déployé (Netlify) vit dans dist/ : c'est la source de vérité.
 * L'application PHP historique (racine du dépôt) réutilise son CSS et ses
 * scripts. Ce script recopie les fichiers concernés pour éviter toute
 * divergence entre les deux copies.
 *
 * Usage : node tools/sync-legacy-assets.js
 */
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');

// Seuls les fichiers réellement partagés avec l'application PHP.
// booking.js et app.js diffèrent entre les deux plateformes : ne pas les copier.
const FILES = [
  ['dist/assets/css/style.css', 'assets/css/style.css'],
  ['dist/assets/js/main.js', 'assets/js/main.js']
];

FILES.forEach(([from, to]) => {
  const source = path.join(ROOT, from);
  const target = path.join(ROOT, to);
  if (!fs.existsSync(source)) {
    console.warn(`⚠ source absente : ${from}`);
    return;
  }
  fs.mkdirSync(path.dirname(target), { recursive: true });
  fs.copyFileSync(source, target);
  console.log(`✓ ${from} → ${to}`);
});
