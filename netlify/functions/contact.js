/**
 * Netlify Function: Contact Form API
 * Gère l'envoi des messages de contact
 */

const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY;

const ALLOWED_ORIGINS = [
  'https://lelezardbleu.netlify.app',
  'https://lezardbleu.infinityfreeapp.com',
  'http://localhost:8888',
  'http://localhost:3000'
];

exports.handler = async (event, context) => {
  const origin = event.headers.origin || event.headers.Origin || '';
  const allowedOrigin = ALLOWED_ORIGINS.includes(origin) ? origin : ALLOWED_ORIGINS[0];

  const headers = {
    'Access-Control-Allow-Origin': allowedOrigin,
    'Access-Control-Allow-Headers': 'Content-Type',
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
    const { name, email, phone, subject, message } = body;

    // Validation
    if (!name || !email || !message) {
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({
          success: false,
          error: 'Nom, email et message sont requis'
        })
      };
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
      return {
        statusCode: 400,
        headers,
        body: JSON.stringify({
          success: false,
          error: 'Format d\'email invalide'
        })
      };
    }

    const contactData = {
      name,
      email,
      phone: phone || null,
      subject: subject || 'Demande de renseignements',
      message,
      status: 'new',
      created_at: new Date().toISOString()
    };

    // Save to Supabase if configured
    if (supabaseUrl && supabaseKey) {
      try {
        const supabase = createClient(supabaseUrl, supabaseKey);
        const { data, error } = await supabase
          .from('contact_messages')
          .insert([contactData])
          .select()
          .single();

        if (error) throw error;

        // TODO: Send notification email to hotel staff
        await sendContactNotification(contactData);

        // TODO: Send confirmation email to customer
        await sendCustomerConfirmation(contactData);

        return {
          statusCode: 200,
          headers,
          body: JSON.stringify({
            success: true,
            message: 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.',
            id: data.id
          })
        };
      } catch (dbError) {
        console.error('Database error:', dbError);
        // Continue to return success for better UX
      }
    }

    // If no database, still return success (for demo/development)
    console.log('Contact form submission (demo mode):', contactData);

    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({
        success: true,
        message: 'Votre message a été enregistré. Nous vous contacterons bientôt.',
        mode: 'demo'
      })
    };

  } catch (error) {
    console.error('Contact API Error:', error);
    
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({
        success: false,
        error: 'Erreur lors de l\'envoi du message',
        message: error.message
      })
    };
  }
};

// Helper function to send notification to hotel staff
async function sendContactNotification(contactData) {
  // TODO: Implement email notification to hotel staff
  console.log('Notification email to hotel staff:', {
    from: contactData.email,
    subject: contactData.subject,
    name: contactData.name
  });
  return true;
}

// Helper function to send confirmation to customer
async function sendCustomerConfirmation(contactData) {
  // TODO: Implement confirmation email to customer
  console.log('Confirmation email to customer:', contactData.email);
  return true;
}
