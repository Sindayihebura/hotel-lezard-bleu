/**
 * Netlify Function: Authentication API
 * Gère l'inscription, connexion et vérification des utilisateurs
 */

const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY;

// Domaines autorisés pour les requêtes CORS
const ALLOWED_ORIGINS = [
  'https://lelezardbleu.netlify.app',
  'https://lezardbleu.infinityfreeapp.com',
  // Autorisé en développement local uniquement
  'http://localhost:8888',
  'http://localhost:3000'
];

exports.handler = async (event, context) => {
  const origin = event.headers.origin || event.headers.Origin || '';
  const allowedOrigin = ALLOWED_ORIGINS.includes(origin) ? origin : ALLOWED_ORIGINS[0];

  const headers = {
    'Access-Control-Allow-Origin': allowedOrigin,
    'Access-Control-Allow-Headers': 'Content-Type, Authorization',
    'Access-Control-Allow-Methods': 'POST, OPTIONS',
    'Vary': 'Origin',
    'Content-Type': 'application/json'
  };

  if (event.httpMethod === 'OPTIONS') {
    return { statusCode: 200, headers, body: '' };
  }

  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ error: 'Method not allowed' })
    };
  }

  try {
    const body = JSON.parse(event.body);
    const { action } = body;

    if (!supabaseUrl || !supabaseKey) {
      return {
        statusCode: 503,
        headers,
        body: JSON.stringify({
          success: false,
          error: 'Service d\'authentification non configuré',
          message: 'Mode démo actif - L\'authentification complète nécessite Supabase'
        })
      };
    }

    const supabase = createClient(supabaseUrl, supabaseKey);

    // LOGIN
    if (action === 'login') {
      const { email, password } = body;

      if (!email || !password) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Email et mot de passe requis'
          })
        };
      }

      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password
      });

      if (error) {
        return {
          statusCode: 401,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Email ou mot de passe incorrect'
          })
        };
      }

      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          message: 'Connexion réussie',
          token: data.session.access_token,
          user: {
            id: data.user.id,
            email: data.user.email,
            name: data.user.user_metadata?.name || email.split('@')[0]
          }
        })
      };
    }

    // REGISTER
    if (action === 'register') {
      const { email, password, firstName, lastName, phone, country } = body;

      if (!email || !password || !firstName || !lastName) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Tous les champs sont requis'
          })
        };
      }

      if (password.length < 8) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Le mot de passe doit contenir au moins 8 caractères'
          })
        };
      }

      const { data, error } = await supabase.auth.signUp({
        email,
        password,
        options: {
          data: {
            first_name: firstName,
            last_name: lastName,
            name: `${firstName} ${lastName}`,
            phone: phone || null,
            country: country || 'BI'
          }
        }
      });

      if (error) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: error.message || 'Erreur lors de l\'inscription'
          })
        };
      }

      // Create customer record in database
      try {
        await supabase.from('customers').insert([{
          email: email,
          first_name: firstName,
          last_name: lastName,
          phone: phone || null,
          country: country || 'BI',
          auth_user_id: data.user?.id,
          created_at: new Date().toISOString()
        }]);
      } catch (dbError) {
        console.error('Error creating customer record:', dbError);
        // Continue anyway
      }

      return {
        statusCode: 201,
        headers,
        body: JSON.stringify({
          success: true,
          message: 'Inscription réussie ! Vérifiez votre email pour activer votre compte.',
          user: {
            id: data.user?.id,
            email: data.user?.email
          }
        })
      };
    }

    // VERIFY TOKEN
    if (action === 'verify') {
      const authHeader = event.headers.authorization || event.headers.Authorization;
      
      if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return {
          statusCode: 401,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Token manquant'
          })
        };
      }

      const token = authHeader.substring(7);

      const { data: { user }, error } = await supabase.auth.getUser(token);

      if (error || !user) {
        return {
          statusCode: 401,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Token invalide ou expiré'
          })
        };
      }

      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          user: {
            id: user.id,
            email: user.email,
            name: user.user_metadata?.name || user.email.split('@')[0],
            firstName: user.user_metadata?.first_name,
            lastName: user.user_metadata?.last_name
          }
        })
      };
    }

    // LOGOUT
    if (action === 'logout') {
      const authHeader = event.headers.authorization || event.headers.Authorization;
      
      if (authHeader && authHeader.startsWith('Bearer ')) {
        const token = authHeader.substring(7);
        
        // Créer un client avec le token utilisateur pour signer correctement
        const userSupabase = createClient(supabaseUrl, supabaseKey, {
          global: { headers: { Authorization: `Bearer ${token}` } }
        });
        await userSupabase.auth.signOut();
      }

      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          message: 'Déconnexion réussie'
        })
      };
    }

    // PASSWORD RESET REQUEST
    if (action === 'reset-password') {
      const { email } = body;

      if (!email) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Email requis'
          })
        };
      }

      const { error } = await supabase.auth.resetPasswordForEmail(email, {
        redirectTo: `${process.env.URL || process.env.SITE_URL || ''}/reset-password.html`
      });

      if (error) {
        console.error('Password reset error:', error);
      }

      // Always return success to prevent email enumeration
      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          message: 'Si un compte existe avec cet email, vous recevrez un lien de réinitialisation.'
        })
      };
    }

    return {
      statusCode: 400,
      headers,
      body: JSON.stringify({
        success: false,
        error: 'Action non reconnue'
      })
    };

  } catch (error) {
    console.error('Auth API Error:', error);
    
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({
        success: false,
        error: 'Erreur serveur',
        message: error.message
      })
    };
  }
};
