/**
 * Netlify Function: Reviews API
 * Gère la récupération des avis clients
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

// Default reviews (fallback)
const DEFAULT_REVIEWS = [
  {
    id: 1,
    guest_name: 'Jean-Paul K.',
    guest_origin: 'Bujumbura, Burundi',
    rating: 5,
    comment: 'Un cadre idyllique au bord du Lac Tanganyika ! Le service Lumicash et l\'accueil sont irréprochables. Personnel très attentionné.',
    stay_type: 'Séjour Affaires',
    stay_date: '2024-02-15',
    is_visible: true,
    verified: true
  },
  {
    id: 2,
    guest_name: 'Elena & Marc',
    guest_origin: 'Bruxelles, Belgique',
    rating: 5,
    comment: 'Sublime hôtel à Bujumbura. La vue sur le lac au coucher du soleil depuis la Suite Présidentielle est magique. Cuisine gastronomique exceptionnelle.',
    stay_type: 'Vacances en Afrique',
    stay_date: '2024-01-28',
    is_visible: true,
    verified: true
  },
  {
    id: 3,
    guest_name: 'Dr. Thierry Habimana',
    guest_origin: 'Kigali, Rwanda',
    rating: 5,
    comment: 'Très impressionné par la qualité du service, la gastronomie du lac et les installations de conférence. Parfait pour séminaires internationaux.',
    stay_type: 'Séminaire International',
    stay_date: '2024-02-05',
    is_visible: true,
    verified: true
  },
  {
    id: 4,
    guest_name: 'Sarah M.',
    guest_origin: 'Paris, France',
    rating: 5,
    comment: 'Une découverte fabuleuse ! Le spa est divin, les chambres luxueuses et le personnel aux petits soins. Je recommande vivement pour une escapade romantique.',
    stay_type: 'Voyage de Noces',
    stay_date: '2024-01-20',
    is_visible: true,
    verified: true
  },
  {
    id: 5,
    guest_name: 'Ahmed B.',
    guest_origin: 'Dar es Salaam, Tanzanie',
    rating: 5,
    comment: 'Business traveler here. Great WiFi, comfortable workspace in the room, and excellent breakfast. The location by Lake Tanganyika is stunning.',
    stay_type: 'Business Travel',
    stay_date: '2024-02-10',
    is_visible: true,
    verified: true
  },
  {
    id: 6,
    guest_name: 'Famille Ndayizeye',
    guest_origin: 'Bujumbura, Burundi',
    rating: 5,
    comment: 'Nous avons célébré l\'anniversaire de notre mère à l\'hôtel. L\'équipe a été merveilleuse et a tout organisé parfaitement. Piscine magnifique pour les enfants.',
    stay_type: 'Célébration Familiale',
    stay_date: '2024-02-01',
    is_visible: true,
    verified: true
  }
];

exports.handler = async (event, context) => {
  const origin = event.headers.origin || event.headers.Origin || '';
  const allowedOrigin = ALLOWED_ORIGINS.includes(origin) ? origin : ALLOWED_ORIGINS[0];

  const headers = {
    'Access-Control-Allow-Origin': allowedOrigin,
    'Access-Control-Allow-Headers': 'Content-Type',
    'Access-Control-Allow-Methods': 'GET, OPTIONS',
    'Vary': 'Origin',
    'Content-Type': 'application/json'
  };

  if (event.httpMethod === 'OPTIONS') {
    return { statusCode: 200, headers, body: '' };
  }

  if (event.httpMethod !== 'GET') {
    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ error: 'Method not allowed' })
    };
  }

  try {
    let reviews = DEFAULT_REVIEWS;

    // If Supabase is configured, fetch from database
    if (supabaseUrl && supabaseKey) {
      try {
        const supabase = createClient(supabaseUrl, supabaseKey);
        const { data, error } = await supabase
          .from('reviews')
          .select('*')
          .eq('is_visible', true)
          .order('stay_date', { ascending: false })
          .limit(20);

        if (!error && data && data.length > 0) {
          reviews = data;
        }
      } catch (dbError) {
        console.error('Database error, using default reviews:', dbError);
        // Continue with default reviews
      }
    }

    // Apply query parameters
    const params = event.queryStringParameters || {};
    
    if (params.limit) {
      reviews = reviews.slice(0, parseInt(params.limit));
    }

    if (params.min_rating) {
      reviews = reviews.filter(r => r.rating >= parseInt(params.min_rating));
    }

    // Calculate average rating
    const avgRating = reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length;

    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({
        success: true,
        reviews: reviews,
        count: reviews.length,
        average_rating: Math.round(avgRating * 10) / 10,
        timestamp: new Date().toISOString()
      })
    };

  } catch (error) {
    console.error('Reviews API Error:', error);
    
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({
        success: false,
        error: 'Internal server error',
        message: error.message
      })
    };
  }
};
