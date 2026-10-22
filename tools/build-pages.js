#!/usr/bin/env node
/**
 * build-pages.js — Prépare une version du site publiable sur GitHub Pages.
 *
 * GitHub Pages sert les projets à l'adresse
 *   https://<utilisateur>.github.io/<dépôt>/
 * ce qui casse tous les chemins absolus du site (`/assets/...`, `/index.html`).
 * Ce script copie `dist/` vers `_site/` en réécrivant ces chemins avec le
 * préfixe du dépôt, sans modifier les sources de `dist/`.
 *
 * Usage :
 *   node tools/build-pages.js            # base déduite de GITHUB_REPOSITORY
 *   node tools/build-pages.js --base /hotel-lezard-bleu
 */
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const SRC = path.join(ROOT, 'dist');
const OUT = path.join(ROOT, '_site');

const baseArg = process.argv.indexOf('--base');
const repo = process.env.GITHUB_REPOSITORY || '';
const repoName = repo.split('/')[1] || '';
const BASE = baseArg !== -1 ? process.argv[baseArg + 1] : repoName ? `/${repoName}` : '';

/** Réécrit un chemin absolu interne en tenant compte du préfixe Pages. */
function rewrite(value) {
  if (!BASE) return value;
  if (/^(https?:|mailto:|tel:|data:|#|\/\/)/.test(value)) return value;
  if (!value.startsWith('/')) return value;
  if (value === '/' || value === '') return `${BASE}/`;
  if (value.startsWith(`${BASE}/`)) return value;
  return BASE + value;
}

const TEXT_EXT = new Set(['.html', '.js', '.css', '.xml', '.txt', '.json', '.svg', '.webmanifest']);

function copyDir(from, to) {
  fs.mkdirSync(to, { recursive: true });
  for (const entry of fs.readdirSync(from, { withFileTypes: true })) {
    const source = path.join(from, entry.name);
    const target = path.join(to, entry.name);
    if (entry.isDirectory()) {
      copyDir(source, target);
      continue;
    }
    const ext = path.extname(entry.name).toLowerCase();
    if (!TEXT_EXT.has(ext)) {
      fs.copyFileSync(source, target);
      continue;
    }
    let content = fs.readFileSync(source, 'utf8');
    const before = content;

    if (ext === '.html' || ext === '.xml') {
      content = content.replace(/\b(href|src|action|content)="(\/[^"]*)"/g, (_m, attr, url) => {
        return `${attr}="${rewrite(url)}"`;
      });
    }

    if (ext === '.js' || ext === '.css') {
      // Chaînes JavaScript vers une page ou une ressource du site.
      // Les routes /api et /.netlify restent intactes : aucune fonction
      // serverless n'existe sur GitHub Pages (le site passe en démonstration).
      content = content.replace(/(['"`])(\/[^'"`\s$]*)(['"`])/g, (match, open, url, close) => {
        if (url.startsWith('//') || url.startsWith('/api') || url.startsWith('/.netlify')) return match;
        const isSitePath =
          url === '/' ||
          /^\/(assets|vendor|env-config)/i.test(url) ||
          /^\/[a-z0-9-]+\.html/i.test(url);
        if (!isSitePath) return match;
        return `${open}${rewrite(url)}${close}`;
      });
      content = content.replace(/url\((\/[^)]+)\)/g, (_m, url) => `url(${rewrite(url)})`);
    }

    if (content !== before) fs.writeFileSync(target, content, 'utf8');
    else fs.copyFileSync(source, target);
  }
}

fs.rmSync(OUT, { recursive: true, force: true });
copyDir(SRC, OUT);

// GitHub Pages n'interprète pas _redirects : on retire le fichier et on ajoute
// un 404.html déjà présent, plus un .nojekyll pour servir tous les fichiers.
const redirects = path.join(OUT, '_redirects');
if (fs.existsSync(redirects)) fs.rmSync(redirects);
fs.writeFileSync(path.join(OUT, '.nojekyll'), '');

// Les URLs propres (/chambres) ne sont pas gérées par Pages : on crée des
// copies `chambres/index.html` pour conserver les liens sans extension.
const CLEAN_URLS = [
  'presentation',
  'chambres',
  'reservation',
  'galerie',
  'services',
  'offres',
  'conferences',
  'contact',
  'login',
  'register',
  'account',
  'confirmation',
  'reset-password',
  'legal'
];
for (const name of CLEAN_URLS) {
  const source = path.join(OUT, `${name}.html`);
  if (!fs.existsSync(source)) continue;
  const dir = path.join(OUT, name);
  fs.mkdirSync(dir, { recursive: true });
  fs.copyFileSync(source, path.join(dir, 'index.html'));
}

console.log(`✓ _site généré (${OUT})`);
console.log(`  préfixe utilisé : ${BASE || '(racine)'}`);
