/**
 * TEMPLATE pour env-config.js
 *
 * Ce fichier est un modèle. Le fichier réel est généré au build Netlify
 * par generate-env-config.js à partir des variables d'environnement.
 *
 * Variables Netlify requises (Dashboard → Site Settings → Environment Variables) :
 *   SUPABASE_URL             = https://ztmbqaakfwugizvebscr.supabase.co
 *   SUPABASE_ANON_KEY        = sb_publishable_4M5nhT9daHi-uf-5WelfUA_o0HE_R6C
 *   DEFAULT_EXCHANGE_RATE    = 6000
 */

window.ENV = {
  SUPABASE_URL: '__SUPABASE_URL__',
  SUPABASE_ANON_KEY: '__SUPABASE_ANON_KEY__',
  DEFAULT_EXCHANGE_RATE: __DEFAULT_EXCHANGE_RATE__
};

// Vérification au chargement
if (window.ENV.SUPABASE_URL === '__SUPABASE_URL__' ||
    window.ENV.SUPABASE_ANON_KEY === '__SUPABASE_ANON_KEY__') {
  console.warn('⚠️  Les clés Supabase ne sont pas configurées. Vérifiez les variables Netlify.');
}
