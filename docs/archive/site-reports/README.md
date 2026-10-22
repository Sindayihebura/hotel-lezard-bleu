# 🏨 Hôtel Le Lézard Bleu & Spa — Site Web Moderne

Site web JAMstack complet pour l'Hôtel Le Lézard Bleu à Bujumbura, Burundi.

## ✨ Fonctionnalités

- 🎨 Design luxueux et responsive (mobile-first)
- 🌍 Multi-devises (BIF / USD) avec conversion en temps réel
- 🛏️ Système de réservation en ligne
- 💳 Support de paiements multiples (Lumicash, EcoCash, cartes bancaires)
- 👤 Authentification utilisateur avec Supabase Auth
- 📧 Formulaire de contact
- ⭐ Système d'avis clients
- 🔒 Sécurité renforcée avec HTTPS et headers de sécurité
- ⚡ Performance optimisée (images lazy-loading, cache, CDN)

## 🚀 Déploiement sur Netlify

### Prérequis

1. Compte GitHub (gratuit) — https://github.com
2. Compte Netlify (gratuit) — https://netlify.com
3. Compte Supabase (gratuit) — https://supabase.com

### Étape 1 : Configuration Supabase (Base de données)

1. Créez un compte sur https://supabase.com
2. Créez un nouveau projet : **hotel-lezard-bleu**
3. Allez dans **SQL Editor** et exécutez ce script :

```sql
-- Créer la table des chambres
CREATE TABLE rooms (
  id BIGSERIAL PRIMARY KEY,
  name TEXT NOT NULL,
  category TEXT NOT NULL,
  description TEXT,
  price_per_night_bif INTEGER NOT NULL,
  capacity_adults INTEGER DEFAULT 2,
  capacity_children INTEGER DEFAULT 0,
  surface_m2 INTEGER,
  photo_main TEXT,
  photos TEXT[],
  amenities TEXT[],
  is_active BOOLEAN DEFAULT TRUE,
  sort_order INTEGER DEFAULT 0,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Créer la table des réservations
CREATE TABLE bookings (
  id BIGSERIAL PRIMARY KEY,
  booking_reference TEXT UNIQUE NOT NULL,
  room_id BIGINT REFERENCES rooms(id),
  customer_firstname TEXT NOT NULL,
  customer_lastname TEXT NOT NULL,
  customer_email TEXT NOT NULL,
  customer_phone TEXT,
  customer_country TEXT DEFAULT 'BI',
  check_in_date DATE NOT NULL,
  check_out_date DATE NOT NULL,
  number_of_guests INTEGER DEFAULT 2,
  number_of_nights INTEGER NOT NULL,
  price_per_night_bif INTEGER NOT NULL,
  total_amount_bif INTEGER NOT NULL,
  currency_used TEXT DEFAULT 'BIF',
  payment_method TEXT,
  payment_status TEXT DEFAULT 'pending',
  booking_status TEXT DEFAULT 'confirmed',
  special_requests TEXT,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Créer la table des clients
CREATE TABLE customers (
  id BIGSERIAL PRIMARY KEY,
  email TEXT UNIQUE NOT NULL,
  first_name TEXT,
  last_name TEXT,
  phone TEXT,
  country TEXT,
  auth_user_id UUID,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Créer la table des avis
CREATE TABLE reviews (
  id BIGSERIAL PRIMARY KEY,
  guest_name TEXT NOT NULL,
  guest_origin TEXT,
  rating INTEGER CHECK (rating >= 1 AND rating <= 5),
  comment TEXT,
  stay_type TEXT,
  stay_date DATE,
  is_visible BOOLEAN DEFAULT TRUE,
  verified BOOLEAN DEFAULT FALSE,
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Créer la table des messages de contact
CREATE TABLE contact_messages (
  id BIGSERIAL PRIMARY KEY,
  name TEXT NOT NULL,
  email TEXT NOT NULL,
  phone TEXT,
  subject TEXT,
  message TEXT NOT NULL,
  status TEXT DEFAULT 'new',
  created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Insérer des données de démonstration
INSERT INTO rooms (name, category, description, price_per_night_bif, capacity_adults, surface_m2, photo_main, amenities, sort_order) VALUES
('Suite Présidentielle Tanganyika Vue Lac', 'Suite', 'Vue féerique sur le Lac Tanganyika à Bujumbura. Terrasse panoramique, jacuzzi, majordome 24h/24.', 3900000, 3, 115, '/assets/images/suite_presidentielle.jpg', ARRAY['Jacuzzi privé', 'Terrasse panoramique', 'Majordome 24h/24'], 1),
('Chambre Deluxe Plage & Jardins', 'Deluxe', 'Finitions en bois précieux burundais, terrasse ombragée et vue jardins verdoyants.', 2280000, 2, 50, '/assets/images/hero_hotel.jpg', ARRAY['Terrasse privée', 'Vue jardin', 'Climatisation'], 2),
('Chambre Executive Jardin & Spa', 'Executive', 'Accès direct au Spa holistique et espaces de relaxation privatifs avec vue tropicale.', 2700000, 2, 65, '/assets/images/spa_piscine.jpg', ARRAY['Accès spa', 'Balcon privé', 'Bureau'], 3);

INSERT INTO reviews (guest_name, guest_origin, rating, comment, stay_type, stay_date, is_visible, verified) VALUES
('Jean-Paul K.', 'Bujumbura, Burundi', 5, 'Un cadre idyllique au bord du Lac Tanganyika ! Le service Lumicash et l''accueil sont irréprochables.', 'Séjour Affaires', '2024-02-15', TRUE, TRUE),
('Elena & Marc', 'Bruxelles, Belgique', 5, 'Sublime hôtel à Bujumbura. La vue sur le lac au coucher du soleil depuis la Suite Présidentielle est magique.', 'Vacances en Afrique', '2024-01-28', TRUE, TRUE),
('Dr. Thierry Habimana', 'Kigali, Rwanda', 5, 'Très impressionné par la qualité du service, la gastronomie du lac et les installations de conférence.', 'Séminaire International', '2024-02-05', TRUE, TRUE);
```

4. Allez dans **Settings → API** et copiez :
   - **Project URL** (ex: `https://xxxxx.supabase.co`)
   - **anon public** key (commence par `eyJhbGci...`)

### Étape 2 : Publier sur GitHub

1. Installez **GitHub Desktop** : https://desktop.github.com
2. Ouvrez GitHub Desktop
3. Cliquez **File → Add local repository**
4. Sélectionnez le dossier `dist` de ce projet
5. Cliquez **Publish repository**
   - Nom : `hotel-lezard-bleu`
   - Cochez **Public** (pour Netlify gratuit)
6. Cliquez **Publish repository**

### Étape 3 : Déployer sur Netlify

1. Allez sur https://netlify.com
2. Cliquez **Sign up** et connectez-vous avec GitHub
3. Cliquez **Add new site → Import an existing project**
4. Sélectionnez **GitHub**
5. Cherchez et sélectionnez `hotel-lezard-bleu`
6. Configuration du build :
   - **Build command:** `npm install`
   - **Publish directory:** `dist`
7. Cliquez **Deploy site**

### Étape 4 : Configurer les variables d'environnement

1. Dans Netlify, allez dans **Site settings → Environment variables**
2. Ajoutez ces variables :

| Variable | Valeur | Description |
|----------|--------|-------------|
| `SUPABASE_URL` | Votre URL Supabase | Ex: `https://xxxxx.supabase.co` |
| `SUPABASE_ANON_KEY` | Votre clé Supabase | La longue clé qui commence par `eyJhbGci...` |
| `DEFAULT_EXCHANGE_RATE` | `6000` | Taux de change USD → BIF |

3. Cliquez **Save**
4. Allez dans **Deploys** et cliquez **Trigger deploy**

### Étape 5 : Configurer le domaine personnalisé (Optionnel)

1. Dans Netlify, allez dans **Domain management**
2. Cliquez **Add custom domain**
3. Entrez votre domaine (ex: `lezardbleu-hotel.bi`)
4. Suivez les instructions pour configurer les DNS

## 🎯 Votre site est maintenant en ligne !

Netlify vous donnera un lien comme : `https://hotel-lezard-bleu.netlify.app`

Votre site est :
- ✅ En ligne 24/7
- ✅ Sécurisé avec HTTPS
- ✅ Rapide avec CDN mondial
- ✅ Sauvegardé automatiquement

## 📱 Tester le site

### Pages principales :
- **Accueil** : `/index.html`
- **Réservation** : `/reservation.html`
- **Chambres** : `/chambres.html`
- **Contact** : `/contact.html`

### APIs disponibles :
- `GET /api/rooms` - Liste des chambres
- `POST /api/bookings` - Créer une réservation
- `GET /api/reviews` - Avis clients
- `POST /api/contact` - Formulaire de contact
- `POST /api/auth` - Authentification

## 🛠️ Développement local

Pour tester en local :

```bash
# Installer les dépendances
npm install

# Installer Netlify CLI
npm install -g netlify-cli

# Démarrer le serveur de développement
netlify dev
```

Le site sera accessible sur `http://localhost:8888`

## 📊 Fonctionnalités techniques

- **Frontend** : HTML5, CSS3, JavaScript ES6+
- **Backend** : Netlify Functions (Node.js serverless)
- **Base de données** : Supabase (PostgreSQL)
- **Authentification** : Supabase Auth
- **Hébergement** : Netlify CDN
- **Sécurité** : HTTPS, CSP headers, XSS protection

## 💡 Support

Pour toute question :
- 📧 Email : support@lezardbleu-hotel.bi
- 📞 Téléphone : +257 22 00 00 00
- 💬 WhatsApp : +257 79 00 00 00

## 📄 Licence

© 2024 Hôtel Le Lézard Bleu & Spa. Tous droits réservés.
