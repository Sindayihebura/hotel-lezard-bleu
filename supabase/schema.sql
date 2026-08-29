-- =====================================================================
-- Schema SQL — Hôtel Le Lézard Bleu & Spa
-- Supabase / PostgreSQL
--
-- Instructions :
--   1. Aller sur https://supabase.com/dashboard/project/rwzzpzzwkutpwcqllqzt
--   2. Menu gauche → SQL Editor
--   3. Cliquer "New query"
--   4. Coller tout ce fichier et cliquer "Run"
-- =====================================================================


-- ─────────────────────────────────────────────────────────────────────
-- 1. CATÉGORIES DE CHAMBRES
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS room_categories (
  id          SERIAL PRIMARY KEY,
  name        VARCHAR(100) NOT NULL UNIQUE,
  description TEXT,
  created_at  TIMESTAMPTZ DEFAULT NOW()
);

INSERT INTO room_categories (name, description) VALUES
  ('Suite',      'Suites de luxe avec vue panoramique'),
  ('Deluxe',     'Chambres Deluxe avec terrasse privée'),
  ('Executive',  'Chambres Executive avec accès spa'),
  ('Villa',      'Villas familiales indépendantes')
ON CONFLICT (name) DO NOTHING;


-- ─────────────────────────────────────────────────────────────────────
-- 2. CHAMBRES
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS rooms (
  id                   SERIAL PRIMARY KEY,
  name                 VARCHAR(200) NOT NULL,
  category_id          INTEGER REFERENCES room_categories(id),
  description          TEXT,
  price_per_night_bif  BIGINT NOT NULL,
  capacity_adults      INTEGER NOT NULL DEFAULT 2,
  capacity_children    INTEGER NOT NULL DEFAULT 0,
  surface_m2           INTEGER,
  view                 VARCHAR(100),
  floor                INTEGER,
  photo_main           VARCHAR(500),
  photos               JSONB DEFAULT '[]',
  amenities_json       JSONB DEFAULT '[]',
  is_active            BOOLEAN DEFAULT TRUE,
  is_available         BOOLEAN DEFAULT TRUE,
  sort_order           INTEGER DEFAULT 0,
  created_at           TIMESTAMPTZ DEFAULT NOW(),
  updated_at           TIMESTAMPTZ DEFAULT NOW()
);

INSERT INTO rooms (name, category_id, description, price_per_night_bif, capacity_adults, capacity_children, surface_m2, view, photo_main, photos, amenities_json, is_active, sort_order)
VALUES
(
  'Suite Présidentielle Tanganyika Vue Lac',
  (SELECT id FROM room_categories WHERE name = 'Suite'),
  'Vue féerique sur le Lac Tanganyika à Bujumbura. Terrasse panoramique avec jacuzzi privé, service majordome 24h/24, minibar premium et équipements haute technologie.',
  3900000, 3, 2, 115, 'Vue Lac Tanganyika',
  '/assets/images/suite_presidentielle.jpg',
  '["/assets/images/suite_presidentielle.jpg", "/assets/images/hero_hotel.jpg"]',
  '["Jacuzzi privé", "Terrasse panoramique 40m²", "Majordome 24h/24", "Vue lac directe", "Minibar premium", "Smart TV 65\"", "Bureau exécutif", "Dressing walk-in", "Salle de bain marbre"]',
  TRUE, 1
),
(
  'Chambre Deluxe Plage & Jardins',
  (SELECT id FROM room_categories WHERE name = 'Deluxe'),
  'Finitions en bois précieux burundais, terrasse ombragée et vue jardins verdoyants. Ambiance chaleureuse et raffinée.',
  2280000, 2, 1, 50, 'Vue Jardin',
  '/assets/images/hero_hotel.jpg',
  '["/assets/images/hero_hotel.jpg"]',
  '["Terrasse privée", "Vue jardin tropical", "Climatisation", "WiFi haut débit", "Coffre-fort", "Minibar", "TV satellite", "Salle de bain italienne"]',
  TRUE, 2
),
(
  'Chambre Executive Jardin & Spa',
  (SELECT id FROM room_categories WHERE name = 'Executive'),
  'Accès direct au Spa holistique et espaces de relaxation privatifs avec vue tropicale. Parfait pour détente et bien-être.',
  2700000, 2, 1, 65, 'Vue Jardin & Spa',
  '/assets/images/spa_piscine.jpg',
  '["/assets/images/spa_piscine.jpg"]',
  '["Accès spa inclus", "Balcon privé", "Bureau de travail", "Salle de bain marbre", "Peignoirs & chaussons", "Machine Nespresso", "Smart TV 50\"", "Coffre-fort digital"]',
  TRUE, 3
),
(
  'Villa Familiale Lac Tanganyika',
  (SELECT id FROM room_categories WHERE name = 'Villa'),
  'Villa indépendante avec 3 chambres, salon privé, cuisine équipée et jardin privatif. Idéale pour familles et groupes.',
  5500000, 6, 4, 180, 'Vue Lac & Jardin Privatif',
  '/assets/images/hero_hotel.jpg',
  '["/assets/images/hero_hotel.jpg"]',
  '["3 chambres", "Salon privé", "Cuisine équipée", "Jardin privatif 100m²", "Piscine privée", "Service butler", "Parking privé 2 places", "BBQ extérieur"]',
  TRUE, 4
)
ON CONFLICT DO NOTHING;


-- ─────────────────────────────────────────────────────────────────────
-- 3. RÉSERVATIONS
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
  id                    SERIAL PRIMARY KEY,
  booking_reference     VARCHAR(50) NOT NULL UNIQUE,
  room_id               INTEGER REFERENCES rooms(id),
  user_id               UUID REFERENCES auth.users(id),

  -- Client
  customer_firstname    VARCHAR(100) NOT NULL,
  customer_lastname     VARCHAR(100) NOT NULL,
  customer_email        VARCHAR(200) NOT NULL,
  customer_phone        VARCHAR(50),
  customer_country      VARCHAR(5) DEFAULT 'BI',

  -- Séjour
  check_in_date         DATE NOT NULL,
  check_out_date        DATE NOT NULL,
  number_of_nights      INTEGER NOT NULL,
  number_of_guests      INTEGER DEFAULT 2,

  -- Tarifs
  price_per_night_bif   BIGINT NOT NULL,
  total_amount_bif      BIGINT NOT NULL,
  currency_used         VARCHAR(5) DEFAULT 'BIF',

  -- Paiement
  payment_method        VARCHAR(50) DEFAULT 'cash',
  payment_status        VARCHAR(50) DEFAULT 'pending',
  transaction_id        VARCHAR(100),
  payment_date          TIMESTAMPTZ,

  -- Statut
  booking_status        VARCHAR(50) DEFAULT 'confirmed',
  statut                VARCHAR(50) GENERATED ALWAYS AS (booking_status) STORED,

  -- Dates colonnes utilisées par availability.js
  date_arrivee          DATE GENERATED ALWAYS AS (check_in_date) STORED,
  date_depart           DATE GENERATED ALWAYS AS (check_out_date) STORED,

  -- Extras
  special_requests      TEXT,
  notes                 TEXT,
  cancelled_at          TIMESTAMPTZ,
  cancel_reason         TEXT,

  created_at            TIMESTAMPTZ DEFAULT NOW(),
  updated_at            TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_bookings_email     ON bookings(customer_email);
CREATE INDEX IF NOT EXISTS idx_bookings_reference ON bookings(booking_reference);
CREATE INDEX IF NOT EXISTS idx_bookings_dates     ON bookings(check_in_date, check_out_date);
CREATE INDEX IF NOT EXISTS idx_bookings_status    ON bookings(booking_status);


-- ─────────────────────────────────────────────────────────────────────
-- 4. PAIEMENTS
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
  id                  SERIAL PRIMARY KEY,
  transaction_id      VARCHAR(100) NOT NULL UNIQUE,
  booking_reference   VARCHAR(50) REFERENCES bookings(booking_reference),
  payment_method      VARCHAR(50) NOT NULL,
  amount              BIGINT NOT NULL,
  currency            VARCHAR(5) DEFAULT 'BIF',
  status              VARCHAR(50) DEFAULT 'pending',
  provider_response   JSONB,
  confirmed_at        TIMESTAMPTZ,
  created_at          TIMESTAMPTZ DEFAULT NOW(),
  updated_at          TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_payments_transaction ON payments(transaction_id);
CREATE INDEX IF NOT EXISTS idx_payments_booking     ON payments(booking_reference);


-- ─────────────────────────────────────────────────────────────────────
-- 5. AVIS CLIENTS
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
  id            SERIAL PRIMARY KEY,
  booking_id    INTEGER REFERENCES bookings(id),
  user_id       UUID REFERENCES auth.users(id),
  guest_name    VARCHAR(200) NOT NULL,
  guest_origin  VARCHAR(200),
  rating        INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment       TEXT NOT NULL,
  stay_type     VARCHAR(100),
  stay_date     DATE,
  is_visible    BOOLEAN DEFAULT TRUE,
  verified      BOOLEAN DEFAULT FALSE,
  created_at    TIMESTAMPTZ DEFAULT NOW()
);

-- Insérer les avis par défaut
INSERT INTO reviews (guest_name, guest_origin, rating, comment, stay_type, stay_date, is_visible, verified)
VALUES
  ('Jean-Paul K.',        'Bujumbura, Burundi',       5, 'Un cadre idyllique au bord du Lac Tanganyika ! Le service Lumicash et l''accueil sont irréprochables. Personnel très attentionné.',                       'Séjour Affaires',         '2024-02-15', TRUE, TRUE),
  ('Elena & Marc',        'Bruxelles, Belgique',       5, 'Sublime hôtel à Bujumbura. La vue sur le lac au coucher du soleil depuis la Suite Présidentielle est magique. Cuisine gastronomique exceptionnelle.',       'Vacances en Afrique',     '2024-01-28', TRUE, TRUE),
  ('Dr. Thierry Habimana','Kigali, Rwanda',            5, 'Très impressionné par la qualité du service, la gastronomie du lac et les installations de conférence. Parfait pour séminaires internationaux.',            'Séminaire International', '2024-02-05', TRUE, TRUE),
  ('Sarah M.',            'Paris, France',             5, 'Une découverte fabuleuse ! Le spa est divin, les chambres luxueuses et le personnel aux petits soins. Je recommande vivement pour une escapade romantique.', 'Voyage de Noces',         '2024-01-20', TRUE, TRUE),
  ('Ahmed B.',            'Dar es Salaam, Tanzanie',   5, 'Business traveler here. Great WiFi, comfortable workspace in the room, and excellent breakfast. The location by Lake Tanganyika is stunning.',               'Business Travel',         '2024-02-10', TRUE, TRUE),
  ('Famille Ndayizeye',   'Bujumbura, Burundi',        5, 'Nous avons célébré l''anniversaire de notre mère à l''hôtel. L''équipe a été merveilleuse et a tout organisé parfaitement. Piscine magnifique pour les enfants.', 'Célébration Familiale', '2024-02-01', TRUE, TRUE)
ON CONFLICT DO NOTHING;


-- ─────────────────────────────────────────────────────────────────────
-- 6. MESSAGES DE CONTACT
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
  id          SERIAL PRIMARY KEY,
  name        VARCHAR(200) NOT NULL,
  email       VARCHAR(200) NOT NULL,
  phone       VARCHAR(50),
  subject     VARCHAR(300) DEFAULT 'Demande de renseignements',
  message     TEXT NOT NULL,
  status      VARCHAR(50) DEFAULT 'new',
  replied_at  TIMESTAMPTZ,
  created_at  TIMESTAMPTZ DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_contact_email  ON contact_messages(email);
CREATE INDEX IF NOT EXISTS idx_contact_status ON contact_messages(status);


-- ─────────────────────────────────────────────────────────────────────
-- 7. PROFILS UTILISATEURS (lié à Supabase Auth)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS profiles (
  id            UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
  firstname     VARCHAR(100),
  lastname      VARCHAR(100),
  phone         VARCHAR(50),
  country       VARCHAR(5) DEFAULT 'BI',
  date_of_birth DATE,
  avatar_url    VARCHAR(500),
  preferences   JSONB DEFAULT '{}',
  created_at    TIMESTAMPTZ DEFAULT NOW(),
  updated_at    TIMESTAMPTZ DEFAULT NOW()
);

-- Trigger : créer un profil automatiquement à l'inscription
CREATE OR REPLACE FUNCTION handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
  INSERT INTO profiles (id, firstname, lastname)
  VALUES (
    NEW.id,
    NEW.raw_user_meta_data->>'firstname',
    NEW.raw_user_meta_data->>'lastname'
  )
  ON CONFLICT (id) DO NOTHING;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
  AFTER INSERT ON auth.users
  FOR EACH ROW EXECUTE FUNCTION handle_new_user();


-- ─────────────────────────────────────────────────────────────────────
-- 8. ROW LEVEL SECURITY (RLS)
-- ─────────────────────────────────────────────────────────────────────

-- rooms : lecture publique
ALTER TABLE rooms ENABLE ROW LEVEL SECURITY;
CREATE POLICY "rooms_public_read" ON rooms
  FOR SELECT USING (is_active = TRUE);

-- room_categories : lecture publique
ALTER TABLE room_categories ENABLE ROW LEVEL SECURITY;
CREATE POLICY "categories_public_read" ON room_categories
  FOR SELECT USING (TRUE);

-- reviews : lecture publique, insertion authentifiée
ALTER TABLE reviews ENABLE ROW LEVEL SECURITY;
CREATE POLICY "reviews_public_read" ON reviews
  FOR SELECT USING (is_visible = TRUE);
CREATE POLICY "reviews_auth_insert" ON reviews
  FOR INSERT WITH CHECK (auth.uid() IS NOT NULL);

-- bookings : l'utilisateur voit ses propres réservations
ALTER TABLE bookings ENABLE ROW LEVEL SECURITY;
CREATE POLICY "bookings_own_read" ON bookings
  FOR SELECT USING (
    auth.uid() = user_id
    OR auth.uid() IS NOT NULL AND customer_email = auth.email()
  );
CREATE POLICY "bookings_insert" ON bookings
  FOR INSERT WITH CHECK (TRUE); -- Permet les réservations sans compte

-- payments : l'utilisateur voit ses propres paiements
ALTER TABLE payments ENABLE ROW LEVEL SECURITY;
CREATE POLICY "payments_own_read" ON payments
  FOR SELECT USING (
    booking_reference IN (
      SELECT booking_reference FROM bookings
      WHERE customer_email = auth.email()
    )
  );
CREATE POLICY "payments_insert" ON payments
  FOR INSERT WITH CHECK (TRUE);

-- contact_messages : insertion publique
ALTER TABLE contact_messages ENABLE ROW LEVEL SECURITY;
CREATE POLICY "contact_insert" ON contact_messages
  FOR INSERT WITH CHECK (TRUE);

-- profiles : chaque utilisateur voit et modifie son propre profil
ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;
CREATE POLICY "profiles_own_read" ON profiles
  FOR SELECT USING (auth.uid() = id);
CREATE POLICY "profiles_own_update" ON profiles
  FOR UPDATE USING (auth.uid() = id);


-- ─────────────────────────────────────────────────────────────────────
-- 9. TRIGGER updated_at automatique
-- ─────────────────────────────────────────────────────────────────────
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER rooms_updated_at    BEFORE UPDATE ON rooms    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER bookings_updated_at BEFORE UPDATE ON bookings FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER payments_updated_at BEFORE UPDATE ON payments FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER profiles_updated_at BEFORE UPDATE ON profiles FOR EACH ROW EXECUTE FUNCTION set_updated_at();
