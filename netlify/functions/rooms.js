/**
 * Netlify Function: Rooms API
 * Gère la récupération des chambres disponibles
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

// Default rooms data (fallback si pas de Supabase configuré)
const DEFAULT_ROOMS = [
  {
    id: 1,
    name: 'Suite Présidentielle Tanganyika Vue Lac',
    category: 'Suite',
    description: 'Vue féerique sur le Lac Tanganyika à Bujumbura. Terrasse panoramique avec jacuzzi privé, service majordome 24h/24, minibar premium et équipements haute technologie.',
    price_per_night_bif: 3900000,
    capacity_adults: 3,
    capacity_children: 2,
    surface_m2: 115,
    photo_main: '/assets/images/suite_presidentielle.jpg',
    photos: ['/assets/images/suite_presidentielle.jpg', '/assets/images/hero_hotel.jpg'],
    amenities: ['Jacuzzi privé', 'Terrasse panoramique 40m²', 'Majordome 24h/24', 'Vue lac directe', 'Minibar premium', 'Smart TV 65"', 'Bureau exécutif', 'Dressing walk-in', 'Salle de bain marbre'],
    is_active: true,
    sort_order: 1
  },
  {
    id: 2,
    name: 'Chambre Deluxe Plage & Jardins',
    category: 'Deluxe',
    description: 'Finitions en bois précieux burundais, terrasse ombragée et vue jardins verdoyants. Ambiance chaleureuse et raffinée.',
    price_per_night_bif: 2280000,
    capacity_adults: 2,
    capacity_children: 1,
    surface_m2: 50,
    photo_main: '/assets/images/hero_hotel.jpg',
    photos: ['/assets/images/hero_hotel.jpg'],
    amenities: ['Terrasse privée', 'Vue jardin tropical', 'Climatisation', 'WiFi haut débit', 'Coffre-fort', 'Minibar', 'TV satellite', 'Salle de bain italienne'],
    is_active: true,
    sort_order: 2
  },
  {
    id: 3,
    name: 'Chambre Executive Jardin & Spa',
    category: 'Executive',
    description: 'Accès direct au Spa holistique et espaces de relaxation privatifs avec vue tropicale. Parfait pour détente et bien-être.',
    price_per_night_bif: 2700000,
    capacity_adults: 2,
    capacity_children: 1,
    surface_m2: 65,
    photo_main: '/assets/images/spa_piscine.jpg',
    photos: ['/assets/images/spa_piscine.jpg'],
    amenities: ['Accès spa inclus', 'Balcon privé', 'Bureau de travail', 'Salle de bain marbre', 'Peignoirs & chaussons', 'Machine Nespresso', 'Smart TV 50"', 'Coffre-fort digital'],
    is_active: true,
    sort_order: 3
  },
  {
    id: 4,
    name: 'Villa Familiale Lac Tanganyika',
    category: 'Villa',
    description: 'Villa indépendante avec 3 chambres, salon privé, cuisine équipée et jardin privatif. Idéale pour familles et groupes.',
    price_per_night_bif: 5500000,
    capacity_adults: 6,
    capacity_children: 4,
    surface_m2: 180,
    photo_main: '/assets/images/hero_hotel.jpg',
    photos: ['/assets/images/hero_hotel.jpg'],
    amenities: ['3 chambres', 'Salon privé', 'Cuisine équipée', 'Jardin privatif 100m²', 'Piscine privée', 'Service butler', 'Parking privé 2 places', 'BBQ extérieur'],
    is_active: true,
    sort_order: 4
  }
];

exports.handler = async (event, context) => {
  const origin = event.headers.origin || event.headers.Origin || '';
  const allowedOrigin = ALLOWED_ORIGINS.includes(origin) ? origin : ALLOWED_ORIGINS[0];

  const headers = {
    'Access-Control-Allow-Origin': allowedOrigin,
    'Access-Control-Allow-Headers': 'Content-Type, Authorization',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    'Vary': 'Origin',
    'Content-Type': 'application/json'
  };

  // Handle OPTIONS request (CORS preflight)
  if (event.httpMethod === 'OPTIONS') {
    return { statusCode: 200, headers, body: '' };
  }

  // Only allow GET requests
  if (event.httpMethod !== 'GET') {
    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ error: 'Method not allowed' })
    };
  }

  try {
    let rooms = DEFAULT_ROOMS;

    // Si Supabase est configuré, récupérer depuis la base de données
    if (supabaseUrl && supabaseKey) {
      try {
        const supabase = createClient(supabaseUrl, supabaseKey);
        const { data, error } = await supabase
          .from('rooms')
          .select('*, room_categories(name)')
          .eq('is_active', true)
          .order('sort_order');

        if (!error && data) {
          rooms = data.map(room => ({
            ...room,
            category: room.room_categories?.name || room.category
          }));
        }
      } catch (dbError) {
        console.error('Database error, using default rooms:', dbError);
        // Continue with default rooms
      }
    }

    // Apply filters from query parameters
    const params = event.queryStringParameters || {};
    
    if (params.category) {
      rooms = rooms.filter(r => r.category.toLowerCase() === params.category.toLowerCase());
    }

    if (params.min_price) {
      rooms = rooms.filter(r => r.price_per_night_bif >= parseInt(params.min_price));
    }

    if (params.max_price) {
      rooms = rooms.filter(r => r.price_per_night_bif <= parseInt(params.max_price));
    }

    if (params.min_capacity) {
      rooms = rooms.filter(r => r.capacity_adults >= parseInt(params.min_capacity));
    }

    // Return rooms data
    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({
        success: true,
        rooms: rooms,
        count: rooms.length,
        exchange_rate: parseInt(process.env.DEFAULT_EXCHANGE_RATE) || 6000,
        timestamp: new Date().toISOString()
      })
    };

  } catch (error) {
    console.error('Rooms API Error:', error);
    
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
