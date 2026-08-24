-- ====================================================================
-- SCHEMA BASE DE DONNÉES SUPABASE
-- Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi
-- ====================================================================

-- Activer les extensions nécessaires
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ====================================================================
-- 1. TABLE DES CATÉGORIES DE CHAMBRES
-- ====================================================================
CREATE TABLE IF NOT EXISTS room_categories (
  id BIGSERIAL PRIMARY KEY,
  name TEXT NOT NULL UNIQUE,
  description TEXT,
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insérer les catégories par défaut
INSERT INTO room_categories (name, description, sort_order) VALUES
('Suite', 'Suites de prestige avec services exclusifs', 1),
('Executive', 'Chambres executive pour professionnels', 2),
('Deluxe', 'Chambres deluxe confortables', 3),
('Villa', 'Villas indépendantes pour familles', 4)
ON CONFLICT (name) DO NOTHING;

-- ====================================================================
-- 2. TABLE DES CHAMBRES
-- ====================================================================
CREATE TABLE IF NOT EXISTS rooms (
  id BIGSERIAL PRIMARY KEY,
  name TEXT NOT NULL,
  category_id BIGINT REFERENCES room_categories(id) ON DELETE SET NULL,
  category TEXT NOT NULL, -- Dénormalisé pour les requêtes
  description TEXT,
  price_per_night_bif INTEGER NOT NULL,
  capacity_adults INTEGER DEFAULT 2,
  capacity_children INTEGER DEFAULT 0,
  surface_m2 INTEGER,
  photo_main TEXT,
  photos TEXT[] DEFAULT ARRAY[]::TEXT[],
  amenities TEXT[] DEFAULT ARRAY[]::TEXT[],
  is_active BOOLEAN DEFAULT TRUE,
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_rooms_active ON rooms(is_active);
CREATE INDEX IF NOT EXISTS idx_rooms_category ON rooms(category);
CREATE INDEX IF NOT EXISTS idx_rooms_price ON rooms(price_per_night_bif);

-- Insérer des chambres de démonstration
INSERT INTO rooms (name, category, description, price_per_night_bif, capacity_adults, capacity_children, surface_m2, photo_main, amenities, sort_order) VALUES
(
  'Suite Présidentielle Tanganyika Vue Lac',
  'Suite',
  'Vue féerique sur le Lac Tanganyika à Bujumbura. Terrasse panoramique avec jacuzzi privé, service majordome 24h/24, minibar premium et équipements haute technologie.',
  3900000,
  3,
  2,
  115,
  '/assets/images/suite_presidentielle.jpg',
  ARRAY['Jacuzzi privé', 'Terrasse panoramique 40m²', 'Majordome 24h/24', 'Vue lac directe', 'Minibar premium', 'Smart TV 65"', 'Bureau exécutif', 'Dressing walk-in', 'Salle de bain marbre'],
  1
),
(
  'Chambre Deluxe Plage & Jardins',
  'Deluxe',
  'Finitions en bois précieux burundais, terrasse ombragée et vue jardins verdoyants. Ambiance chaleureuse et raffinée.',
  2280000,
  2,
  1,
  50,
  '/assets/images/hero_hotel.jpg',
  ARRAY['Terrasse privée', 'Vue jardin tropical', 'Climatisation', 'WiFi haut débit', 'Coffre-fort', 'Minibar', 'TV satellite', 'Salle de bain italienne'],
  2
),
(
  'Chambre Executive Jardin & Spa',
  'Executive',
  'Accès direct au Spa holistique et espaces de relaxation privatifs avec vue tropicale. Parfait pour détente et bien-être.',
  2700000,
  2,
  1,
  65,
  '/assets/images/spa_piscine.jpg',
  ARRAY['Accès spa inclus', 'Balcon privé', 'Bureau de travail', 'Salle de bain marbre', 'Peignoirs & chaussons', 'Machine Nespresso', 'Smart TV 50"', 'Coffre-fort digital'],
  3
),
(
  'Villa Familiale Lac Tanganyika',
  'Villa',
  'Villa indépendante avec 3 chambres, salon privé, cuisine équipée et jardin privatif. Idéale pour familles et groupes.',
  5500000,
  6,
  4,
  180,
  '/assets/images/hero_hotel.jpg',
  ARRAY['3 chambres', 'Salon privé', 'Cuisine équipée', 'Jardin privatif 100m²', 'Piscine privée', 'Service butler', 'Parking privé 2 places', 'BBQ extérieur'],
  4
);

-- ====================================================================
-- 3. TABLE DES CLIENTS
-- ====================================================================
CREATE TABLE IF NOT EXISTS customers (
  id BIGSERIAL PRIMARY KEY,
  auth_user_id UUID, -- Référence à Supabase Auth
  email TEXT UNIQUE NOT NULL,
  first_name TEXT,
  last_name TEXT,
  phone TEXT,
  country TEXT DEFAULT 'BI',
  address TEXT,
  city TEXT,
  postal_code TEXT,
  date_of_birth DATE,
  preferences JSONB DEFAULT '{}'::JSONB,
  loyalty_points INTEGER DEFAULT 0,
  total_bookings INTEGER DEFAULT 0,
  total_spent_bif BIGINT DEFAULT 0,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Index pour les recherches
CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);
CREATE INDEX IF NOT EXISTS idx_customers_auth_user ON customers(auth_user_id);

-- ====================================================================
-- 4. TABLE DES RÉSERVATIONS
-- ====================================================================
CREATE TABLE IF NOT EXISTS bookings (
  id BIGSERIAL PRIMARY KEY,
  booking_reference TEXT UNIQUE NOT NULL,
  room_id BIGINT REFERENCES rooms(id) ON DELETE SET NULL,
  customer_id BIGINT REFERENCES customers(id) ON DELETE SET NULL,
  
  -- Informations client (dénormalisées)
  customer_firstname TEXT NOT NULL,
  customer_lastname TEXT NOT NULL,
  customer_email TEXT NOT NULL,
  customer_phone TEXT,
  customer_country TEXT DEFAULT 'BI',
  
  -- Détails de la réservation
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  number_of_guests INTEGER DEFAULT 2,
  number_of_nights INTEGER NOT NULL,
  
  -- Tarification
  price_per_night_bif INTEGER NOT NULL,
  total_amount_bif INTEGER NOT NULL,
  currency_used TEXT DEFAULT 'BIF',
  exchange_rate_used NUMERIC(10, 2),
  
  -- Paiement
  payment_method TEXT, -- lumicash, ecocash, bank, card, cash
  payment_status TEXT DEFAULT 'pending', -- pending, paid, refunded, failed
  payment_date TIMESTAMP WITH TIME ZONE,
  transaction_id TEXT,
  
  -- Statut
  booking_status TEXT DEFAULT 'confirmed', -- confirmed, cancelled, completed, no_show
  special_requests TEXT,
  
  -- Métadonnées
  source TEXT DEFAULT 'website', -- website, phone, email, admin
  ip_address INET,
  user_agent TEXT,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  cancelled_at TIMESTAMP WITH TIME ZONE,
  
  CONSTRAINT valid_dates CHECK (check_out_date > check_in_date),
  CONSTRAINT valid_nights CHECK (number_of_nights > 0),
  CONSTRAINT valid_amount CHECK (total_amount_bif > 0)
);

-- Index pour optimiser les requêtes
CREATE INDEX IF NOT EXISTS idx_bookings_ref ON bookings(booking_reference);
CREATE INDEX IF NOT EXISTS idx_bookings_dates ON bookings(check_in_date, check_out_date);
CREATE INDEX IF NOT EXISTS idx_bookings_customer_email ON bookings(customer_email);
CREATE INDEX IF NOT EXISTS idx_bookings_status ON bookings(booking_status);
CREATE INDEX IF NOT EXISTS idx_bookings_created ON bookings(created_at DESC);

-- ====================================================================
-- 5. TABLE DES AVIS CLIENTS
-- ====================================================================
CREATE TABLE IF NOT EXISTS reviews (
  id BIGSERIAL PRIMARY KEY,
  booking_id BIGINT REFERENCES bookings(id) ON DELETE SET NULL,
  customer_id BIGINT REFERENCES customers(id) ON DELETE SET NULL,
  
  guest_name TEXT NOT NULL,
  guest_email TEXT,
  guest_origin TEXT,
  
  rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
  comment TEXT NOT NULL,
  stay_type TEXT,
  stay_date DATE,
  
  room_rating INTEGER CHECK (room_rating >= 1 AND room_rating <= 5),
  service_rating INTEGER CHECK (service_rating >= 1 AND service_rating <= 5),
  cleanliness_rating INTEGER CHECK (cleanliness_rating >= 1 AND cleanliness_rating <= 5),
  value_rating INTEGER CHECK (value_rating >= 1 AND value_rating <= 5),
  
  is_visible BOOLEAN DEFAULT FALSE, -- Modération
  verified BOOLEAN DEFAULT FALSE,
  admin_response TEXT,
  
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  published_at TIMESTAMP WITH TIME ZONE
);

-- Index
CREATE INDEX IF NOT EXISTS idx_reviews_visible ON reviews(is_visible);
CREATE INDEX IF NOT EXISTS idx_reviews_rating ON reviews(rating);
CREATE INDEX IF NOT EXISTS idx_reviews_created ON reviews(created_at DESC);

-- Insérer des avis de démonstration
INSERT INTO reviews (guest_name, guest_origin, rating, comment, stay_type, stay_date, is_visible, verified) VALUES
('Jean-Paul K.', 'Bujumbura, Burundi', 5, 'Un cadre idyllique au bord du Lac Tanganyika ! Le service Lumicash et l''accueil sont irréprochables. Personnel très attentionné.', 'Séjour Affaires', '2024-02-15', TRUE, TRUE),
('Elena & Marc', 'Bruxelles, Belgique', 5, 'Sublime hôtel à Bujumbura. La vue sur le lac au coucher du soleil depuis la Suite Présidentielle est magique. Cuisine gastronomique exceptionnelle.', 'Vacances en Afrique', '2024-01-28', TRUE, TRUE),
('Dr. Thierry Habimana', 'Kigali, Rwanda', 5, 'Très impressionné par la qualité du service, la gastronomie du lac et les installations de conférence. Parfait pour séminaires internationaux.', 'Séminaire International', '2024-02-05', TRUE, TRUE),
('Sarah M.', 'Paris, France', 5, 'Une découverte fabuleuse ! Le spa est divin, les chambres luxueuses et le personnel aux petits soins. Je recommande vivement pour une escapade romantique.', 'Voyage de Noces', '2024-01-20', TRUE, TRUE),
('Ahmed B.', 'Dar es Salaam, Tanzanie', 5, 'Business traveler here. Great WiFi, comfortable workspace in the room, and excellent breakfast. The location by Lake Tanganyika is stunning.', 'Business Travel', '2024-02-10', TRUE, TRUE),
('Famille Ndayizeye', 'Bujumbura, Burundi', 5, 'Nous avons célébré l''anniversaire de notre mère à l''hôtel. L''équipe a été merveilleuse et a tout organisé parfaitement. Piscine magnifique pour les enfants.', 'Célébration Familiale', '2024-02-01', TRUE, TRUE);

-- ====================================================================
-- 6. TABLE DES MESSAGES DE CONTACT
-- ====================================================================
CREATE TABLE IF NOT EXISTS contact_messages (
  id BIGSERIAL PRIMARY KEY,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT,
  subject TEXT,
  message TEXT NOT NULL,
  status TEXT DEFAULT 'new', -- new, read, replied, archived
  assigned_to TEXT,
  admin_notes TEXT,
  replied_at TIMESTAMP WITH TIME ZONE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_contact_status ON contact_messages(status);
CREATE INDEX IF NOT EXISTS idx_contact_created ON contact_messages(created_at DESC);

-- ====================================================================
-- 7. TABLE DES OFFRES SPÉCIALES
-- ====================================================================
CREATE TABLE IF NOT EXISTS special_offers (
  id BIGSERIAL PRIMARY KEY,
  title TEXT NOT NULL,
  description TEXT,
  discount_percentage INTEGER,
  discount_amount_bif INTEGER,
  valid_from DATE NOT NULL,
  valid_until DATE NOT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  conditions TEXT,
  promo_code TEXT UNIQUE,
  max_uses INTEGER,
  current_uses INTEGER DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- ====================================================================
-- 8. TABLE DES NEWSLETTERS
-- ====================================================================
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
  id BIGSERIAL PRIMARY KEY,
  email TEXT UNIQUE NOT NULL,
  name TEXT,
  subscribed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
  is_active BOOLEAN DEFAULT TRUE,
  unsubscribed_at TIMESTAMP WITH TIME ZONE
);

CREATE INDEX IF NOT EXISTS idx_newsletter_email ON newsletter_subscribers(email);

-- ====================================================================
-- 9. FONCTIONS & TRIGGERS
-- ====================================================================

-- Fonction pour mettre à jour updated_at automatiquement
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Appliquer le trigger sur les tables nécessaires
CREATE TRIGGER update_rooms_updated_at BEFORE UPDATE ON rooms
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_customers_updated_at BEFORE UPDATE ON customers
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_bookings_updated_at BEFORE UPDATE ON bookings
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_reviews_updated_at BEFORE UPDATE ON reviews
  FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Fonction pour générer une référence de réservation unique
CREATE OR REPLACE FUNCTION generate_booking_reference()
RETURNS TEXT AS $$
DECLARE
  ref TEXT;
  exists BOOLEAN;
BEGIN
  LOOP
    ref := 'LB' || TO_CHAR(NOW(), 'YYMMDD') || '-' || UPPER(SUBSTRING(MD5(RANDOM()::TEXT) FROM 1 FOR 6));
    SELECT EXISTS(SELECT 1 FROM bookings WHERE booking_reference = ref) INTO exists;
    EXIT WHEN NOT exists;
  END LOOP;
  RETURN ref;
END;
$$ LANGUAGE plpgsql;

-- ====================================================================
-- 10. POLITIQUES DE SÉCURITÉ (ROW LEVEL SECURITY)
-- ====================================================================

-- Activer RLS sur les tables sensibles
ALTER TABLE bookings ENABLE ROW LEVEL SECURITY;
ALTER TABLE customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;

-- Politique : Les clients peuvent voir leurs propres réservations
CREATE POLICY "Users can view their own bookings" ON bookings
  FOR SELECT
  USING (auth.uid() = (SELECT auth_user_id FROM customers WHERE customers.email = bookings.customer_email));

-- Politique : Les clients peuvent voir leur propre profil
CREATE POLICY "Users can view their own profile" ON customers
  FOR SELECT
  USING (auth.uid() = auth_user_id);

-- Politique : Tout le monde peut voir les avis publiés
CREATE POLICY "Anyone can view published reviews" ON reviews
  FOR SELECT
  USING (is_visible = TRUE);

-- ====================================================================
-- 11. VUES UTILES
-- ====================================================================

-- Vue : Statistiques des réservations
CREATE OR REPLACE VIEW booking_statistics AS
SELECT
  DATE_TRUNC('month', created_at) AS month,
  COUNT(*) AS total_bookings,
  COUNT(*) FILTER (WHERE booking_status = 'confirmed') AS confirmed_bookings,
  COUNT(*) FILTER (WHERE booking_status = 'cancelled') AS cancelled_bookings,
  SUM(total_amount_bif) AS total_revenue_bif,
  AVG(total_amount_bif) AS avg_booking_value_bif,
  COUNT(DISTINCT customer_email) AS unique_customers
FROM bookings
GROUP BY DATE_TRUNC('month', created_at)
ORDER BY month DESC;

-- Vue : Disponibilité des chambres (simplifiée)
CREATE OR REPLACE VIEW room_availability_summary AS
SELECT
  r.id,
  r.name,
  r.category,
  r.price_per_night_bif,
  COUNT(b.id) AS total_bookings,
  COUNT(b.id) FILTER (WHERE b.booking_status = 'confirmed') AS active_bookings
FROM rooms r
LEFT JOIN bookings b ON r.id = b.room_id
GROUP BY r.id, r.name, r.category, r.price_per_night_bif;

-- ====================================================================
-- FIN DU SCHÉMA
-- ====================================================================

-- Message de confirmation
DO $$
BEGIN
  RAISE NOTICE 'Schema created successfully for Hotel Le Lezard Bleu!';
  RAISE NOTICE 'Next steps:';
  RAISE NOTICE '1. Copy SUPABASE_URL from Settings > API';
  RAISE NOTICE '2. Copy SUPABASE_ANON_KEY from Settings > API';
  RAISE NOTICE '3. Add these to Netlify environment variables';
END $$;
