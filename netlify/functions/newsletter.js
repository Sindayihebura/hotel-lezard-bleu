/**
 * Netlify Function: Newsletter
 * Inscription à la newsletter privilège.
 *
 * POST { email }
 * → enregistre dans Supabase (table newsletter_subscribers) si configuré,
 *   sinon répond en mode démonstration explicite.
 */

const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_SERVICE_ROLE_KEY || process.env.SUPABASE_ANON_KEY;

const ALLOWED_ORIGINS = [
  'https://lelezardbleu.netlify.app',
  'https://lezardbleu.infinityfreeapp.com',
  'https://lezardbleu-hotel.bi',
  'http://localhost:8888',
  'http://localhost:8080',
  'http://localhost:3000'
];

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[a-zA-Z]{2,}$/;

exports.handler = async (event) => {
  const origin = (event.headers && (event.headers.origin || event.headers.Origin)) || '';
  const allowedOrigin = ALLOWED_ORIGINS.includes(origin) ? origin : ALLOWED_ORIGINS[0];

  const headers = {
    'Access-Control-Allow-Origin': allowedOrigin,
    'Access-Control-Allow-Headers': 'Content-Type',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    Vary: 'Origin',
    'Content-Type': 'application/json'
  };

  if (event.httpMethod === 'OPTIONS') return { statusCode: 200, headers, body: '' };

  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ success: false, error: 'Méthode non autorisée' })
    };
  }

  let email = '';
  try {
    const body = JSON.parse(event.body || '{}');
    email = String(body.email || '').trim().toLowerCase();
  } catch (_) {
    return {
      statusCode: 400,
      headers,
      body: JSON.stringify({ success: false, error: 'Requête invalide' })
    };
  }

  if (!EMAIL_RE.test(email)) {
    return {
      statusCode: 400,
      headers,
      body: JSON.stringify({ success: false, error: 'Adresse e-mail invalide' })
    };
  }

  if (supabaseUrl && supabaseKey) {
    try {
      const supabase = createClient(supabaseUrl, supabaseKey);
      const { error } = await supabase
        .from('newsletter_subscribers')
        .upsert([{ email, subscribed_at: new Date().toISOString(), is_active: true }], {
          onConflict: 'email'
        });
      if (error) throw error;

      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({ success: true, message: 'Inscription enregistrée' })
      };
    } catch (error) {
      console.error('Newsletter DB error:', error.message);
    }
  }

  // Base non configurée : succès en mode démonstration, clairement signalé.
  return {
    statusCode: 200,
    headers,
    body: JSON.stringify({
      success: true,
      demo: true,
      message: "Inscription enregistrée en mode démonstration (base de données non configurée)"
    })
  };
};
