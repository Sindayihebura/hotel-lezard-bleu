/**
 * TEMPLATE pour env-config.js
 * 
 * Ce fichier est un modèle. Le fichier réel est généré au build Netlify.
 * 
 * Variables Netlify requises :
 * - SUPABASE_URL
 * - SUPABASE_ANON_KEY
 */

window.ENV = {
  SUPABASE_URL: '__SUPABASE_URL__',
  SUPABASE_ANON_KEY: '__SUPABASE_ANON_KEY__'
};

// Vérification au chargement
if (window.ENV.SUPABASE_URL === '__SUPABASE_URL__' ||
    window.ENV.SUPABASE_ANON_KEY === '__SUPABASE_ANON_KEY__') {
  console.warn('⚠️  Les clés Supabase ne sont pas configurées. Vérifiez les variables Netlify.');
}
