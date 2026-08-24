/**
 * Netlify Function: Payments API
 * Gère les paiements via Lumicash, EcoCash, cartes bancaires, etc.
 */

const { createClient } = require('@supabase/supabase-js');

const supabaseUrl = process.env.SUPABASE_URL;
const supabaseKey = process.env.SUPABASE_ANON_KEY;

// Configuration des gateways de paiement
const PAYMENT_GATEWAYS = {
  lumicash: {
    name: 'Lumicash',
    enabled: true,
    currencies: ['BIF'],
    instructions: 'Composez *144# puis suivez les instructions pour payer'
  },
  ecocash: {
    name: 'EcoCash',
    enabled: true,
    currencies: ['BIF'],
    instructions: 'Composez *150# puis suivez les instructions pour payer'
  },
  bank_transfer: {
    name: 'Virement Bancaire',
    enabled: true,
    currencies: ['BIF', 'USD'],
    instructions: 'Effectuez un virement vers notre compte bancaire'
  },
  card: {
    name: 'Carte Bancaire',
    enabled: true,
    currencies: ['USD'],
    instructions: 'Paiement sécurisé par carte Visa/MasterCard'
  },
  cash: {
    name: 'Espèces sur Place',
    enabled: true,
    currencies: ['BIF', 'USD'],
    instructions: 'Payez à votre arrivée à la réception'
  }
};

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
    'Vary': 'Origin',
    'Access-Control-Allow-Headers': 'Content-Type, Authorization',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    'Content-Type': 'application/json'
  };

  if (event.httpMethod === 'OPTIONS') {
    return { statusCode: 200, headers, body: '' };
  }

  try {
    const body = event.httpMethod === 'POST' ? JSON.parse(event.body) : null;
    const action = body?.action || event.queryStringParameters?.action;

    // GET: Liste des méthodes de paiement disponibles
    if (event.httpMethod === 'GET' && !action) {
      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          payment_methods: PAYMENT_GATEWAYS,
          exchange_rate: parseInt(process.env.DEFAULT_EXCHANGE_RATE) || 6000
        })
      };
    }

    // POST: Initier un paiement
    if (action === 'initiate') {
      const { bookingReference, paymentMethod, amount, currency } = body;

      if (!bookingReference || !paymentMethod || !amount) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Paramètres manquants (bookingReference, paymentMethod, amount)'
          })
        };
      }

      // Vérifier que la méthode de paiement est supportée
      if (!PAYMENT_GATEWAYS[paymentMethod]) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Méthode de paiement non supportée'
          })
        };
      }

      // Générer un ID de transaction unique
      const transactionId = 'TXN' + Date.now() + Math.random().toString(36).substring(2, 8).toUpperCase();

      // Créer l'enregistrement de paiement
      const paymentData = {
        transaction_id: transactionId,
        booking_reference: bookingReference,
        payment_method: paymentMethod,
        amount: amount,
        currency: currency || 'BIF',
        status: 'pending',
        created_at: new Date().toISOString()
      };

      // Traiter selon la méthode de paiement
      let paymentResponse;

      switch (paymentMethod) {
        case 'lumicash':
          paymentResponse = await processLumicash(paymentData);
          break;
        case 'ecocash':
          paymentResponse = await processEcocash(paymentData);
          break;
        case 'bank_transfer':
          paymentResponse = await processBankTransfer(paymentData);
          break;
        case 'card':
          paymentResponse = await processCard(paymentData);
          break;
        case 'cash':
          paymentResponse = await processCash(paymentData);
          break;
        default:
          paymentResponse = {
            success: true,
            status: 'pending',
            message: 'Paiement en attente de confirmation'
          };
      }

      // Sauvegarder dans Supabase si configuré
      if (supabaseUrl && supabaseKey) {
        try {
          const supabase = createClient(supabaseUrl, supabaseKey);
          
          // Créer l'enregistrement de paiement
          await supabase.from('payments').insert([paymentData]);

          // Mettre à jour la réservation
          await supabase
            .from('bookings')
            .update({ 
              transaction_id: transactionId,
              payment_status: paymentResponse.status
            })
            .eq('booking_reference', bookingReference);
        } catch (dbError) {
          console.error('Database error:', dbError);
        }
      }

      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          transaction_id: transactionId,
          payment_method: paymentMethod,
          ...paymentResponse
        })
      };
    }

    // POST: Vérifier le statut d'un paiement
    if (action === 'verify') {
      const { transactionId, bookingReference } = body;

      if (!transactionId && !bookingReference) {
        return {
          statusCode: 400,
          headers,
          body: JSON.stringify({
            success: false,
            error: 'Transaction ID ou Booking Reference requis'
          })
        };
      }

      if (supabaseUrl && supabaseKey) {
        try {
          const supabase = createClient(supabaseUrl, supabaseKey);
          
          let query = supabase.from('payments').select('*');
          
          if (transactionId) {
            query = query.eq('transaction_id', transactionId);
          } else {
            query = query.eq('booking_reference', bookingReference);
          }
          
          const { data, error } = await query.single();

          if (error) throw error;

          return {
            statusCode: 200,
            headers,
            body: JSON.stringify({
              success: true,
              payment: data
            })
          };
        } catch (dbError) {
          console.error('Database error:', dbError);
        }
      }

      // Mode démo sans base de données
      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          payment: {
            transaction_id: transactionId,
            status: 'pending',
            message: 'Mode démo - Vérification simulée'
          }
        })
      };
    }

    // POST: Webhook de confirmation (appelé par les systèmes de paiement)
    if (action === 'webhook') {
      const { transactionId, status, provider } = body;

      console.log('Payment webhook received:', { transactionId, status, provider });

      if (supabaseUrl && supabaseKey) {
        try {
          const supabase = createClient(supabaseUrl, supabaseKey);
          
          // Mettre à jour le statut du paiement
          await supabase
            .from('payments')
            .update({ 
              status: status,
              confirmed_at: new Date().toISOString()
            })
            .eq('transaction_id', transactionId);

          // Mettre à jour la réservation
          const { data: payment } = await supabase
            .from('payments')
            .select('booking_reference')
            .eq('transaction_id', transactionId)
            .single();

          if (payment) {
            await supabase
              .from('bookings')
              .update({ 
                payment_status: status === 'success' ? 'paid' : status,
                payment_date: status === 'success' ? new Date().toISOString() : null
              })
              .eq('booking_reference', payment.booking_reference);
          }
        } catch (dbError) {
          console.error('Database error:', dbError);
        }
      }

      return {
        statusCode: 200,
        headers,
        body: JSON.stringify({
          success: true,
          message: 'Webhook processed'
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
    console.error('Payment API Error:', error);
    
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

// ====================================================================
// HANDLERS SPÉCIFIQUES PAR MÉTHODE DE PAIEMENT
// ====================================================================

async function processLumicash(paymentData) {
  // TODO: Intégrer avec l'API Lumicash réelle
  // Pour le moment, retourne une instruction pour paiement manuel
  
  const merchantCode = process.env.LUMICASH_MERCHANT_CODE || '12345';
  
  return {
    success: true,
    status: 'pending',
    message: 'Instructions de paiement Lumicash',
    instructions: {
      step1: 'Composez *144# sur votre téléphone',
      step2: 'Choisissez "Payer un marchand"',
      step3: `Entrez le code marchand : ${merchantCode}`,
      step4: `Montant : ${paymentData.amount} ${paymentData.currency}`,
      step5: `Référence : ${paymentData.booking_reference}`
    },
    merchant_code: merchantCode,
    expires_at: new Date(Date.now() + 30 * 60 * 1000).toISOString() // 30 minutes
  };
}

async function processEcocash(paymentData) {
  // TODO: Intégrer avec l'API EcoCash réelle
  
  const merchantNumber = process.env.ECOCASH_MERCHANT_NUMBER || '79000000';
  
  return {
    success: true,
    status: 'pending',
    message: 'Instructions de paiement EcoCash',
    instructions: {
      step1: 'Composez *150# sur votre téléphone',
      step2: 'Choisissez "Payer un marchand"',
      step3: `Numéro marchand : ${merchantNumber}`,
      step4: `Montant : ${paymentData.amount} ${paymentData.currency}`,
      step5: `Référence : ${paymentData.booking_reference}`
    },
    merchant_number: merchantNumber,
    expires_at: new Date(Date.now() + 30 * 60 * 1000).toISOString()
  };
}

async function processBankTransfer(paymentData) {
  // Instructions de virement bancaire
  
  return {
    success: true,
    status: 'pending',
    message: 'Instructions de virement bancaire',
    bank_details: {
      bank_name: 'Banque Commerciale du Burundi (BCB)',
      account_holder: 'Hôtel Le Lézard Bleu SARL',
      account_number: 'BI43 1000 1234 5678 9012 3456',
      swift_bic: 'BCBUBIBJ',
      reference: paymentData.booking_reference,
      amount: `${paymentData.amount} ${paymentData.currency}`
    },
    instructions: {
      step1: 'Effectuez un virement vers le compte ci-dessus',
      step2: 'Utilisez la référence de réservation comme référence de virement',
      step3: 'Envoyez-nous une copie du reçu par email : finance@lezardbleu-hotel.bi',
      step4: 'Nous confirmerons votre réservation dès réception du paiement (24-48h)'
    }
  };
}

async function processCard(paymentData) {
  // TODO: Intégrer avec Stripe ou autre processeur de cartes
  
  const stripePublicKey = process.env.STRIPE_PUBLIC_KEY;
  
  if (!stripePublicKey) {
    return {
      success: true,
      status: 'pending',
      message: 'Paiement par carte temporairement indisponible',
      alternative: 'Veuillez utiliser une autre méthode de paiement'
    };
  }

  return {
    success: true,
    status: 'pending',
    message: 'Paiement par carte',
    stripe_public_key: stripePublicKey,
    amount: paymentData.amount,
    currency: paymentData.currency.toLowerCase(),
    description: `Réservation ${paymentData.booking_reference}`,
    redirect_url: `${process.env.URL}/payment/complete?transaction_id=${paymentData.transaction_id}`
  };
}

async function processCash(paymentData) {
  // Paiement en espèces à l'arrivée
  
  return {
    success: true,
    status: 'pending_arrival',
    message: 'Paiement en espèces à l\'arrivée',
    instructions: {
      step1: 'Votre réservation est confirmée',
      step2: 'Vous pourrez payer à votre arrivée à la réception',
      step3: `Montant à régler : ${paymentData.amount} ${paymentData.currency}`,
      step4: 'Nous acceptons BIF et USD en espèces',
      step5: 'Cartes bancaires également acceptées sur place'
    },
    note: 'Un dépôt de garantie peut être demandé à l\'arrivée'
  };
}
