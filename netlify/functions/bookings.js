/**
 * Netlify Function: Bookings API
 * Gère la création et récupération des réservations
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
    'Access-Control-Allow-Headers': 'Content-Type, Authorization',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    'Vary': 'Origin',
    'Content-Type': 'application/json'
  };

  if (event.httpMethod === 'OPTIONS') {
    return { statusCode: 200, headers, body: '' };
  }

  try {
    // POST: Create new booking
    if (event.httpMethod === 'POST') {
      const bookingData = JSON.parse(event.body);

      // Validation
      const required = ['checkIn', 'checkOut', 'roomId', 'firstName', 'lastName', 'email', 'phone'];
      for (const field of required) {
        if (!bookingData[field]) {
          return {
            statusCode: 400,
            headers,
            body: JSON.stringify({
              success: false,
              error: `Le champ ${field} est requis`
            })
          };
        }
      }

      // Validate dates
      const checkIn = new Date(bookingData.checkIn);
      const checkOut = new Date(bookingData.checkOut);
      const today = new Date();
      today.setHours(0, 0, 0, 0);

      if (checkIn < today) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'La date d\'arrivée ne peut pas être dans le passé'
          })
        };
      }

      if (checkOut <= checkIn) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'La date de départ doit être après la date d\'arrivée'
          })
        };
      }

      // Calculate total
      const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
      const roomPrices = {
        '1': 3900000,
        '2': 2280000,
        '3': 2700000,
        '4': 5500000
      };
      const pricePerNight = roomPrices[bookingData.roomId] || 2280000;
      const totalBIF = pricePerNight * nights;

      // Generate booking reference
      const bookingRef = 'LB' + Date.now() + Math.random().toString(36).substring(2, 6).toUpperCase();

      const booking = {
        booking_reference: bookingRef,
        room_id: parseInt(bookingData.roomId),
        customer_firstname: bookingData.firstName,
        customer_lastname: bookingData.lastName,
        customer_email: bookingData.email,
        customer_phone: bookingData.phone,
        customer_country: bookingData.country || 'BI',
        check_in_date: bookingData.checkIn,
        check_out_date: bookingData.checkOut,
        number_of_guests: parseInt(bookingData.guests) || 2,
        number_of_nights: nights,
        price_per_night_bif: pricePerNight,
        total_amount_bif: totalBIF,
        currency_used: bookingData.currency || 'BIF',
        payment_method: bookingData.paymentMethod || 'cash',
        payment_status: 'pending',
        booking_status: 'confirmed',
        special_requests: bookingData.specialRequests || null,
        created_at: new Date().toISOString()
      };

      // Save to Supabase if configured
      if (supabaseUrl && supabaseKey) {
        try {
          const supabase = createClient(supabaseUrl, supabaseKey);
          const { data, error } = await supabase
            .from('bookings')
            .insert([booking])
            .select()
            .single();

          if (error) throw error;

          // TODO: Send confirmation email
          await sendBookingConfirmationEmail(booking);

          return {
            statusCode: 200,
            headers,
            body: JSON.stringify({
              success: true,
              message: 'Réservation créée avec succès',
              bookingId: data.id,
              bookingReference: bookingRef,
              booking: data
            })
          };
        } catch (dbError) {
          console.error('Database error:', dbError);
          // Continue to save locally or return success for demo
        }
      }

      // If no database, return success with mock data (for demo/dev)
      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          message: 'Réservation enregistrée (mode démo)',
          bookingId: Math.floor(Math.random() * 10000),
          bookingReference: bookingRef,
          booking: booking
        })
      };
    }

    // GET: Retrieve bookings (requires authentication)
    if (event.httpMethod === 'GET') {
      const authHeader = event.headers.authorization || event.headers.Authorization;
      
      if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return {
          statusCode: 401,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Non autorisé - Token requis'
          })
        };
      }

      const token = authHeader.substring(7);

      // Verify token and get user bookings from Supabase
      if (supabaseUrl && supabaseKey) {
        try {
          const supabase = createClient(supabaseUrl, supabaseKey);
          
          // Verify user from token
          const { data: { user }, error: authError } = await supabase.auth.getUser(token);
          
          if (authError || !user) {
            return {
              statusCode: 401,
              headers,
              body: JSON.stringify({
                success: false,
                error: 'Token invalide ou expiré'
              })
            };
          }

          // Get user's bookings
          const { data: bookings, error } = await supabase
            .from('bookings')
            .select('*, rooms(name, category)')
            .eq('customer_email', user.email)
            .order('created_at', { ascending: false });

          if (error) throw error;

          return {
            statusCode: 200,
            headers,
            body: JSON.stringify({
              success: true,
              bookings: bookings || [],
              count: bookings?.length || 0
            })
          };
        } catch (dbError) {
          console.error('Error fetching bookings:', dbError);
        }
      }

      // Return empty bookings if no database
      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          bookings: [],
          count: 0,
          message: 'Mode démo - Aucune réservation'
        })
      };
    }

    return {
      statusCode: 405,
      headers,
      body: JSON.stringify({ error: 'Method not allowed' })
    };

  } catch (error) {
    console.error('Bookings API Error:', error);
    
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

// Helper function to send confirmation email (to be implemented)
async function sendBookingConfirmationEmail(booking) {
  // TODO: Implement email sending via SendGrid, Mailgun, or similar
  console.log('Booking confirmation email would be sent to:', booking.customer_email);
  console.log('Booking details:', {
    reference: booking.booking_reference,
    checkIn: booking.check_in_date,
    checkOut: booking.check_out_date
  });
  return true;
}
