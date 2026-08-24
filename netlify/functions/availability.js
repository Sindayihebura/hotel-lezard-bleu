// Netlify Function — GET /api/availability
const { createClient } = require('@supabase/supabase-js');

const ALLOWED_ORIGINS = [
  'https://lelezardbleu.netlify.app',
  'https://lezardbleu.infinityfreeapp.com',
  'http://localhost:8888',
  'http://localhost:3000'
];

exports.handler = async (event) => {
  const origin = event.headers.origin || event.headers.Origin || '';
  const allowedOrigin = ALLOWED_ORIGINS.includes(origin) ? origin : ALLOWED_ORIGINS[0];

  const headers = {
    'Content-Type': 'application/json',
    'Access-Control-Allow-Origin': allowedOrigin,
    'Access-Control-Allow-Headers': 'Content-Type',
    'Access-Control-Allow-Methods': 'GET, OPTIONS',
    'Vary': 'Origin'
  };

  if (event.httpMethod === 'OPTIONS') return { statusCode: 204, headers, body: '' };

  const { checkin, checkout, adults = 1 } = event.queryStringParameters || {};

  if (!checkin || !checkout) {
    return {
      statusCode: 422,
      headers,
      body: JSON.stringify({ success: false, error: { message: 'checkin et checkout obligatoires.' } }),
    };
  }

  if (checkout <= checkin) {
    return {
      statusCode: 422,
      headers,
      body: JSON.stringify({ success: false, error: { message: 'Le départ doit être après l\'arrivée.' } }),
    };
  }

  try {
    const supabase = createClient(process.env.SUPABASE_URL, process.env.SUPABASE_ANON_KEY);

    // Trouver les chambres occupées sur cette période
    const { data: occupied } = await supabase
      .from('bookings')
      .select('room_id')
      .not('statut', 'in', '("cancelled","no_show")')
      .lt('date_arrivee', checkout)
      .gt('date_depart', checkin);

    const occupiedIds = (occupied || []).map(b => b.room_id);

    // Trouver les chambres disponibles
    let query = supabase
      .from('rooms')
      .select('*, room_categories(name)')
      .eq('is_active', true)
      .gte('capacity_adults', parseInt(adults));

    if (occupiedIds.length > 0) {
      query = query.not('id', 'in', `(${occupiedIds.join(',')})`);
    }

    const { data: rooms, error } = await query.order('sort_order');
    if (error) throw error;

    const rate = parseFloat(process.env.DEFAULT_EXCHANGE_RATE || '6000');
    const nights = Math.ceil((new Date(checkout) - new Date(checkin)) / 86400000);

    const result = (rooms || []).map(r => ({
      id:                  r.id,
      name:                r.name,
      category:            r.room_categories?.name || '',
      description:         r.description,
      capacity_adults:     r.capacity_adults,
      surface_m2:          r.surface_m2,
      view:                r.view,
      photo_main:          r.photo_main,
      amenities:           r.amenities_json || [],
      price_per_night_bif: r.price_per_night_bif,
      total_bif:           r.price_per_night_bif * nights,
      price_formatted_bif: new Intl.NumberFormat('fr-BI').format(r.price_per_night_bif) + ' FBu',
    }));

    return {
      statusCode: 200,
      headers,
      body: JSON.stringify({
        success: true,
        data: result,
        meta: { checkin, checkout, nights, adults: parseInt(adults), exchange_rate: rate },
      }),
    };

  } catch (err) {
    return {
      statusCode: 500,
      headers,
      body: JSON.stringify({ success: false, error: { message: 'Erreur serveur.' } }),
    };
  }
};
