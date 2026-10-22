#!/usr/bin/env node
/**
 * serve.js — Serveur statique local pour la prévisualisation du site.
 *
 * Usage : node tools/serve.js [--port 8080]
 * Sert le dossier dist/ sur 0.0.0.0, sans dépendance externe.
 *
 * - URLs propres (/chambres → chambres.html)
 * - page 404 personnalisée
 * - cache désactivé (développement)
 */
'use strict';

const http = require('http');
const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..', 'dist');
const args = process.argv.slice(2);
const portFlag = args.indexOf('--port');
const PORT = Number(portFlag !== -1 ? args[portFlag + 1] : process.env.PORT) || 8080;
const HOST = process.env.HOST || '0.0.0.0';

const MIME = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css; charset=utf-8',
  '.js': 'text/javascript; charset=utf-8',
  '.mjs': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.svg': 'image/svg+xml',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.webp': 'image/webp',
  '.ico': 'image/x-icon',
  '.txt': 'text/plain; charset=utf-8',
  '.xml': 'application/xml; charset=utf-8',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.map': 'application/json; charset=utf-8'
};

function safePath(urlPath) {
  const decoded = decodeURIComponent(urlPath.split('?')[0]);
  const normalized = path.normalize(decoded).replace(/^(\.\.[/\\])+/, '');
  const full = path.join(ROOT, normalized);
  if (!full.startsWith(ROOT)) return null;
  return full;
}

function resolveFile(urlPath) {
  let filePath = safePath(urlPath);
  if (!filePath) return null;

  const candidates = [filePath, `${filePath}.html`, path.join(filePath, 'index.html')];
  for (const candidate of candidates) {
    try {
      const stat = fs.statSync(candidate);
      if (stat.isFile()) return candidate;
    } catch (_) {
      /* essai suivant */
    }
  }
  return null;
}

const server = http.createServer((req, res) => {
  if (req.method !== 'GET' && req.method !== 'HEAD') {
    res.writeHead(405, { Allow: 'GET, HEAD', 'Content-Type': 'text/plain; charset=utf-8' });
    res.end('Méthode non autorisée');
    return;
  }

  const urlPath = req.url === '/' ? '/index.html' : req.url;
  const filePath = resolveFile(urlPath);

  if (!filePath) {
    const notFound = path.join(ROOT, '404.html');
    res.writeHead(404, {
      'Content-Type': 'text/html; charset=utf-8',
      'Cache-Control': 'no-store'
    });
    res.end(fs.existsSync(notFound) ? fs.readFileSync(notFound) : 'Page introuvable');
    return;
  }

  const ext = path.extname(filePath).toLowerCase();
  const headers = {
    'Content-Type': MIME[ext] || 'application/octet-stream',
    'Cache-Control': 'no-store',
    'X-Content-Type-Options': 'nosniff'
  };

  res.writeHead(200, headers);
  if (req.method === 'HEAD') {
    res.end();
    return;
  }
  fs.createReadStream(filePath).pipe(res);
});

server.listen(PORT, HOST, () => {
  console.log(`Hôtel Le Lézard Bleu — prévisualisation : http://localhost:${PORT}`);
  console.log(`Dossier servi : ${ROOT}`);
});
