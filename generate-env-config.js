#!/usr/bin/env node
/**
 * generate-env-config.js
 * Génère dist/env-config.js depuis les variables d'environnement Netlify.
 * Remplace generate-env-config.sh pour une compatibilité totale
 * Windows / Linux sans dépendre des permissions Unix (chmod).
 *
 * Usage : node generate-env-config.js
 */

const fs = require('fs');
const path = require('path');

console.log('🔧 Génération de dist/env-config.js...');

const supabaseUrl  = process.env.SUPABASE_URL     || 'https://rwzzpzzwkutpwcqllqzt.supabase.co';
const supabaseKey  = process.env.SUPABASE_ANON_KEY || 'sb_publishable_LbZweT_3THf2p34VHi_iuA_vJo5UcR1';
const exchangeRate = process.env.DEFAULT_EXCHANGE_RATE || '6000';

if (!supabaseUrl || !supabaseKey) {
  console.error('❌ ERREUR : Les variables SUPABASE_URL et SUPABASE_ANON_KEY sont vides');
  process.exit(1);
}

const templatePath = path.join(__dirname, 'dist', 'env-config.template.js');
const outputPath   = path.join(__dirname, 'dist', 'env-config.js');

let content;

if (fs.existsSync(templatePath)) {
  // Utilise le template si disponible
  content = fs.readFileSync(templatePath, 'utf8');
  content = content
    .replace(/__SUPABASE_URL__/g,      supabaseUrl)
    .replace(/__SUPABASE_ANON_KEY__/g, supabaseKey)
    .replace(/__DEFAULT_EXCHANGE_RATE__/g, exchangeRate);
} else {
  // Génère directement si le template est absent
  content = `/**
 * Configuration des clés publiques Supabase
 * Généré automatiquement au build — ne pas éditer manuellement.
 *
 * IMPORTANT : Ce fichier contient UNIQUEMENT des clés publiques (anon/publishable).
 * La SUPABASE_SERVICE_ROLE_KEY (secret) ne doit JAMAIS apparaître ici.
 */

window.ENV = {
  SUPABASE_URL: '${supabaseUrl}',
  SUPABASE_ANON_KEY: '${supabaseKey}',
  DEFAULT_EXCHANGE_RATE: ${exchangeRate}
};
`;
}

fs.writeFileSync(outputPath, content, 'utf8');

console.log('✅ dist/env-config.js généré avec succès');
console.log(`   SUPABASE_URL      : ${supabaseUrl.substring(0, 40)}...`);
console.log(`   SUPABASE_ANON_KEY : ${supabaseKey.substring(0, 30)}...`);
console.log(`   EXCHANGE_RATE     : ${exchangeRate}`);
