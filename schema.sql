-- ====================================================================
-- Script SQL d'initialisation - Hôtel Le Lézard Bleu Bujumbura (Burundi)
-- Multi-devises (BIF / USD) & Paiements Locaux (Lumicash, EcoCash, Banques)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `hotel_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_db`;

-- --------------------------------------------------------
-- 1. Table : parametres (Configuration générale & Taux de change BIF/USD)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `parametres`;

CREATE TABLE `parametres` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cle` VARCHAR(50) NOT NULL UNIQUE,
  `valeur` VARCHAR(255) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `date_modification` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion du taux de change par défaut : 1 USD = 6 000 BIF
INSERT INTO `parametres` (`cle`, `valeur`, `description`) VALUES
('taux_usd_bif', '6000', 'Taux de conversion : 1 USD en Francs Burundais (BIF)'),
('nom_hotel', 'Hôtel Le Lézard Bleu & Spa Bujumbura', 'Nom officiel de l\'établissement'),
('devise_defaut', 'BIF', 'Devise par défaut du système');

-- --------------------------------------------------------
-- 2. Table : chambres
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reservations`;
DROP TABLE IF EXISTS `chambres`;

CREATE TABLE `chambres` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `categorie` ENUM('Suite', 'Deluxe', 'Executive', 'Villa') NOT NULL DEFAULT 'Deluxe',
  `description` TEXT NOT NULL,
  `prix_nuit_bif` DECIMAL(12, 2) NOT NULL,
  `capacite` INT NOT NULL DEFAULT 2,
  `surface_m2` INT NOT NULL DEFAULT 45,
  `equipements` TEXT NOT NULL,
  `photo_url` VARCHAR(255) NOT NULL,
  `ordre` INT DEFAULT 0,
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des chambres avec tarification en Francs Burundais (BIF)
INSERT INTO `chambres` (`id`, `nom`, `slug`, `categorie`, `description`, `prix_nuit_bif`, `capacite`, `surface_m2`, `equipements`, `photo_url`, `ordre`) VALUES
(1, 'Suite Présidentielle Tanganyika Vue Lac', 'suite-presidentielle-tanganyika', 'Suite', 'Une suite somptueuse au bord du Lac Tanganyika à Bujumbura, terrasse panoramique, baignoire jacuzzi en marbre, salon privé et service majordome 24h/24.', 3900000.00, 3, 115, 'Lit King-Size, Terrasse Privée sur le Lac, Wi-Fi 6, Jacuzzi, Majordome 24/7, Nespresso, Smart TV 65" 4K', 'assets/images/suite_bujumbura.jpg', 1),
(2, 'Chambre Deluxe Plage & Jardins de Bujumbura', 'deluxe-plage-bujumbura', 'Deluxe', 'Baignée de la lumière tropicale du lac, finitions en bois précieux burundais, terrasse ombragée et vue directe sur les jardins verdoyants et la piscine.', 2280000.00, 2, 50, 'Lit King-Size, Terrasse Ombree, Wi-Fi Premium, Climatisation Tropicale, Coffre-fort, Douche a l\'Italienne', 'assets/images/hero_tanganyika.jpg', 2),
(3, 'Chambre Executive Jardin & Spa', 'executive-jardin-spa', 'Executive', 'Nichée dans la végétation luxuriante des collines de Bujumbura avec un accès direct au Spa holistique et aux espaces de relaxation privatifs.', 2700000.00, 2, 65, 'Lit King-Size, Accès Spa Illimité, Terrasse Privative, Wi-Fi 6, Station Bluetooth, Peignoirs en soie', 'assets/images/spa_piscine.jpg', 3),
(4, 'Villa Privée Tanganyika Plunge Pool', 'villa-privee-tanganyika', 'Villa', 'Le sommet de l\'hôtellerie burundaise. Villa indépendante au bord de l\'eau avec piscine privée chauffée, solarium et majordome dédié.', 5520000.00, 4, 160, 'Piscine Privée Chauffée, 2 Lits King-Size, Service en Villa 24/7, Système Audio Bang & Olufsen, Transfert VIP Aéroport', 'assets/images/suite_presidentielle.jpg', 4);

-- --------------------------------------------------------
-- 3. Table : reservations (Modifiée avec devises & modes de paiement Burundi)
-- --------------------------------------------------------
CREATE TABLE `reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chambre_id` INT NOT NULL,
  `reference` VARCHAR(20) NOT NULL UNIQUE,
  `nom_client` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telephone` VARCHAR(30) NOT NULL,
  `date_arrivee` DATE NOT NULL,
  `date_depart` DATE NOT NULL,
  `nb_personnes` INT NOT NULL DEFAULT 1,
  `devise_choisie` ENUM('BIF', 'USD') NOT NULL DEFAULT 'BIF',
  `mode_paiement` ENUM('Liquide_BIF', 'Liquide_USD', 'Lumicash', 'EcoCash', 'Banque_Locale', 'VISA', 'MasterCard', 'PayPal') NOT NULL DEFAULT 'Liquide_BIF',
  `banque_nom` VARCHAR(100) DEFAULT 'N/A',
  `telephone_mobile_money` VARCHAR(30) DEFAULT NULL,
  `montant_total_bif` DECIMAL(12, 2) NOT NULL,
  `montant_total_usd` DECIMAL(10, 2) NOT NULL,
  `options_supplementaires` TEXT NULL,
  `statut` ENUM('confirmee', 'en_attente', 'annulee') NOT NULL DEFAULT 'confirmee',
  `date_creation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`chambre_id`) REFERENCES `chambres`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exemples de réservations
INSERT INTO `reservations` (`id`, `chambre_id`, `reference`, `nom_client`, `email`, `telephone`, `date_arrivee`, `date_depart`, `nb_personnes`, `devise_choisie`, `mode_paiement`, `banque_nom`, `telephone_mobile_money`, `montant_total_bif`, `montant_total_usd`, `options_supplementaires`, `statut`) VALUES
(1, 1, 'RES-BDI-9812', 'Jean-Marie Ndayishimiye', 'jm.ndayishimiye@example.bi', '+257 79 12 34 56', '2026-08-10', '2026-08-14', 2, 'BIF', 'Lumicash', 'N/A', '+257 68 12 34 56', 15600000.00, 2600.00, 'Petit-déjeuner inclus, Transfert Aéroport Melchior Ndadaye', 'confirmee'),
(2, 2, 'RES-BDI-4410', 'David Miller', 'd.miller@example.com', '+1 555 019 2831', '2026-08-15', '2026-08-18', 2, 'USD', 'VISA', 'N/A', NULL, 6840000.00, 1140.00, 'Accès Spa & Massage Duo', 'confirmee');

-- --------------------------------------------------------
-- 4. Table : services
-- --------------------------------------------------------
DROP TABLE IF EXISTS `services`;

CREATE TABLE `services` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `categorie` ENUM('Bien-être & Spa', 'Gastronomie', 'Loisirs & Conciergerie', 'Transport') NOT NULL,
  `titre` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `prix_bif` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `unite` VARCHAR(50) DEFAULT 'par personne',
  `image` VARCHAR(255) NOT NULL,
  `icone` VARCHAR(50) NOT NULL DEFAULT 'star'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `services` (`id`, `categorie`, `titre`, `description`, `prix_bif`, `unite`, `image`, `icone`) VALUES
(1, 'Bien-être & Spa', 'Rituel Spa Tanganyika & Soin Holistique', '90 minutes de détente absolue avec huiles essentielles locales et massage aux pierres chaudes du lac.', 840000.00, 'par séance', 'assets/images/spa_piscine.jpg', 'spa'),
(2, 'Gastronomie', 'Dîner Gastronomique du Lac (5 Services)', 'Menu signature par notre Chef avec poissons frais du Lac Tanganyika (Capitaine & Sangala) et accords crus régionaux.', 1080000.00, 'par personne', 'assets/images/restaurant_gourmet.jpg', 'utensils'),
(3, 'Transport', 'Navette VTC Aéroport Melchior Ndadaye (Bujumbura)', 'Accueil personnalisé et transfert sécurisé en véhicule SUV climatisé avec chauffeur bilingue.', 180000.00, 'par trajet', 'assets/images/hero_tanganyika.jpg', 'car'),
(4, 'Loisirs & Conciergerie', 'Excursion Privée en Bateau sur le Lac Tanganyika', 'Croisière exclusive au coucher du soleil avec rafraîchissements et champagne à bord.', 1500000.00, 'la demi-journée', 'assets/images/hero_tanganyika.jpg', 'anchor');

-- --------------------------------------------------------
-- 5. Table : offres
-- --------------------------------------------------------
DROP TABLE IF EXISTS `offres`;

CREATE TABLE `offres` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titre` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `reduction_pct` INT DEFAULT 0,
  `code_promo` VARCHAR(30) DEFAULT NULL,
  `prix_pack_bif` DECIMAL(12, 2) DEFAULT NULL,
  `image` VARCHAR(255) NOT NULL,
  `date_fin` DATE DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `offres` (`id`, `titre`, `description`, `reduction_pct`, `code_promo`, `prix_pack_bif`, `image`, `date_fin`) VALUES
(1, 'Escapade Romantique Lac Tanganyika', '2 nuits en Suite, bouteille de champagne, petit-déjeuner au lit et massage duo au Spa.', 20, 'TANGANYIKA20', 5940000.00, 'assets/images/spa_piscine.jpg', '2026-12-31'),
(2, 'Forfait Découverte Gastronomique', '1 nuit en Chambre Deluxe Vue Lac + Dîner Gastronomique 5 services pour 2 personnes.', 15, 'BUJUMBURA15', 3120000.00, 'assets/images/restaurant_gourmet.jpg', '2026-11-30');

-- --------------------------------------------------------
-- 6. Table : avis
-- --------------------------------------------------------
DROP TABLE IF EXISTS `avis`;

CREATE TABLE `avis` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom_client` VARCHAR(100) NOT NULL,
  `origine` VARCHAR(100) DEFAULT 'Bujumbura, Burundi',
  `note` INT NOT NULL DEFAULT 5,
  `commentaire` TEXT NOT NULL,
  `type_sejour` VARCHAR(50) DEFAULT 'Séjour Romantique',
  `date_avis` DATE DEFAULT (CURRENT_DATE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `avis` (`id`, `nom_client`, `origine`, `note`, `commentaire`, `type_sejour`, `date_avis`) VALUES
(1, 'Jean-Paul K.', 'Bujumbura, Burundi', 5, 'Un cadre idyllique au bord du Lac Tanganyika ! Le service Lumicash et l\'accueil de l\'équipe sont irréprochables.', 'Séjour Affaires', '2026-06-20'),
(2, 'Elena & Marc', 'Bruxelles, Belgique', 5, 'Sublime hôtel à Bujumbura. La vue sur le lac au coucher du soleil depuis la Suite Présidentielle est magique.', 'Vacances en Afrique', '2026-07-12');

-- --------------------------------------------------------
-- 7. Table : messages_contact
-- --------------------------------------------------------
DROP TABLE IF EXISTS `messages_contact`;

CREATE TABLE `messages_contact` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telephone` VARCHAR(30) DEFAULT NULL,
  `sujet` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `date_envoi` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- 8. Table : demandes_devis (Séminaires & Mariages à Bujumbura)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `demandes_devis`;

CREATE TABLE `demandes_devis` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `societe` VARCHAR(150) DEFAULT NULL,
  `nom_contact` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `telephone` VARCHAR(30) NOT NULL,
  `type_evenement` VARCHAR(100) NOT NULL,
  `nb_participants` INT NOT NULL,
  `date_evenement` DATE NOT NULL,
  `message` TEXT DEFAULT NULL,
  `date_demande` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
