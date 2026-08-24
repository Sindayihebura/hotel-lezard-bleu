-- ====================================================================
-- Migration 001 — Schéma initial complet
-- Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi
-- Devises : BIF (BIGINT) | USD (BIGINT cents) | Taux DECIMAL(18,6)
-- Encodage : utf8mb4_unicode_ci
-- ====================================================================

CREATE DATABASE IF NOT EXISTS `hotel_lezardbleu`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `hotel_lezardbleu`;

-- ── Désactiver temporairement les FK pour l'installation ──────────────
SET FOREIGN_KEY_CHECKS = 0;

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 1 : SÉCURITÉ — sessions, rate limiting, tokens
-- ══════════════════════════════════════════════════════════════════════

-- ── 1.1 rate_limits ──────────────────────────────────────────────────
DROP TABLE IF EXISTS `rate_limits`;
CREATE TABLE `rate_limits` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `rate_key`   VARCHAR(255)  NOT NULL,
  `expires_at` DATETIME      NOT NULL,
  INDEX idx_rate_key_expires (`rate_key`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 1.2 email_verifications ──────────────────────────────────────────
DROP TABLE IF EXISTS `email_verifications`;
CREATE TABLE `email_verifications` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    BIGINT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255)  NOT NULL,
  `expires_at` DATETIME      NOT NULL,
  `used_at`    DATETIME      NULL,
  `created_at` DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  INDEX idx_token (`token_hash`),
  INDEX idx_user  (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 1.3 password_resets ──────────────────────────────────────────────
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(150)  NOT NULL,
  `token_hash` VARCHAR(255)  NOT NULL,
  `expires_at` DATETIME      NOT NULL,
  `used_at`    DATETIME      NULL,
  `ip_address` VARCHAR(45)   NOT NULL DEFAULT '',
  `created_at` DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  INDEX idx_email  (`email`),
  INDEX idx_token  (`token_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 1.4 login_attempts ───────────────────────────────────────────────
DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `identifier` VARCHAR(255)  NOT NULL COMMENT 'email ou IP',
  `ip_address` VARCHAR(45)   NOT NULL,
  `success`    TINYINT(1)    NOT NULL DEFAULT 0,
  `created_at` DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  INDEX idx_identifier_created (`identifier`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 2 : UTILISATEURS — clients et admins
-- ══════════════════════════════════════════════════════════════════════

-- ── 2.1 roles ────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id`          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(50)   NOT NULL UNIQUE
                COMMENT 'super_admin|hotel_manager|receptionist|accountant|marketing_manager|service_agent',
  `label`       VARCHAR(100)  NOT NULL,
  `description` VARCHAR(255)  NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `label`, `description`) VALUES
('super_admin',       'Super Administrateur',  'Accès total à toutes les fonctionnalités'),
('hotel_manager',     'Directeur Hôtel',       'Gestion opérationnelle complète'),
('receptionist',      'Réceptionniste',        'Réservations, check-in, check-out'),
('accountant',        'Comptable',             'Paiements, rapports financiers'),
('marketing_manager', 'Responsable Marketing', 'Offres, contenu, avis clients'),
('service_agent',     'Agent de Service',      'Services, maintenance, conciergerie');

-- ── 2.2 permissions ──────────────────────────────────────────────────
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id`    SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`  VARCHAR(80)  NOT NULL UNIQUE COMMENT 'ex: reservations.view',
  `label` VARCHAR(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `label`) VALUES
('reservations.view',     'Consulter les réservations'),
('reservations.create',   'Créer une réservation'),
('reservations.update',   'Modifier une réservation'),
('reservations.cancel',   'Annuler une réservation'),
('reservations.checkin',  'Effectuer un check-in'),
('reservations.checkout', 'Effectuer un check-out'),
('payments.view',         'Consulter les paiements'),
('payments.confirm',      'Confirmer un paiement'),
('payments.refund',       'Émettre un remboursement'),
('rooms.manage',          'Gérer les chambres'),
('services.manage',       'Gérer les services'),
('offers.manage',         'Gérer les offres'),
('customers.view',        'Consulter les clients'),
('customers.export',      'Exporter les données clients'),
('reports.view',          'Consulter les rapports'),
('users.manage',          'Gérer les utilisateurs admin'),
('settings.manage',       'Modifier les paramètres'),
('audit.view',            'Consulter les logs d\'audit'),
('translations.manage',   'Gérer les traductions');

-- ── 2.3 role_permissions ─────────────────────────────────────────────
DROP TABLE IF EXISTS `role_permissions`;
CREATE TABLE `role_permissions` (
  `role_id`       TINYINT UNSIGNED  NOT NULL,
  `permission_id` SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`)       REFERENCES `roles`(`id`)       ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attribution des permissions par rôle
-- super_admin : toutes
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- hotel_manager : tout sauf users.manage et audit.view
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions`
WHERE `name` NOT IN ('users.manage');

-- receptionist : réservations (pas cancel) + customers.view + payments.view
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, `id` FROM `permissions`
WHERE `name` IN ('reservations.view','reservations.create','reservations.update',
                 'reservations.checkin','reservations.checkout',
                 'customers.view','payments.view','services.manage');

-- accountant : paiements + rapports + réservations view
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, `id` FROM `permissions`
WHERE `name` IN ('reservations.view','payments.view','payments.confirm',
                 'payments.refund','reports.view','customers.view','customers.export');

-- marketing_manager : offres + avis + traductions + rapports
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, `id` FROM `permissions`
WHERE `name` IN ('offers.manage','translations.manage','reports.view',
                 'customers.view');

-- service_agent : services + reservations view
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, `id` FROM `permissions`
WHERE `name` IN ('services.manage','reservations.view','customers.view');

-- ── 2.4 admin_users ──────────────────────────────────────────────────
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id`                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_id`             TINYINT UNSIGNED  NOT NULL,
  `first_name`          VARCHAR(80)       NOT NULL,
  `last_name`           VARCHAR(80)       NOT NULL,
  `email`               VARCHAR(150)      NOT NULL UNIQUE,
  `password_hash`       VARCHAR(255)      NOT NULL,
  `phone`               VARCHAR(30)       NULL,
  `is_active`           TINYINT(1)        NOT NULL DEFAULT 1,
  `mfa_secret`          VARCHAR(64)       NULL COMMENT 'TOTP secret chiffré',
  `mfa_enabled`         TINYINT(1)        NOT NULL DEFAULT 0,
  `last_login_at`       DATETIME          NULL,
  `last_login_ip`       VARCHAR(45)       NULL,
  `password_changed_at` DATETIME          NULL,
  `created_at`          DATETIME          NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `updated_at`          DATETIME          NOT NULL DEFAULT (UTC_TIMESTAMP()) ON UPDATE (UTC_TIMESTAMP()),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2.5 customers (clients hôtel) ────────────────────────────────────
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id`                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `first_name`          VARCHAR(80)   NOT NULL,
  `last_name`           VARCHAR(80)   NOT NULL,
  `email`               VARCHAR(150)  NOT NULL UNIQUE,
  `password_hash`       VARCHAR(255)  NULL COMMENT 'NULL = compte invité',
  `phone`               VARCHAR(30)   NULL,
  `country_code`        CHAR(2)       NULL COMMENT 'ISO 3166-1 alpha-2',
  `preferred_locale`    CHAR(2)       NOT NULL DEFAULT 'fr',
  `preferred_currency`  CHAR(3)       NOT NULL DEFAULT 'BIF',
  `email_verified_at`   DATETIME      NULL,
  `is_active`           TINYINT(1)    NOT NULL DEFAULT 1,
  `is_guest`            TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = réservation sans compte',
  `newsletter_consent`  TINYINT(1)    NOT NULL DEFAULT 0,
  `special_requests`    TEXT          NULL,
  `notes_admin`         TEXT          NULL,
  `last_login_at`       DATETIME      NULL,
  `password_changed_at` DATETIME      NULL,
  `created_at`          DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `updated_at`          DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()) ON UPDATE (UTC_TIMESTAMP()),
  INDEX idx_email     (`email`),
  INDEX idx_country   (`country_code`),
  INDEX idx_active    (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 3 : MULTILINGUE — langues et traductions
-- ══════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `supported_languages`;
CREATE TABLE `supported_languages` (
  `code`       CHAR(2)      NOT NULL PRIMARY KEY COMMENT 'fr|en|rn',
  `name`       VARCHAR(60)  NOT NULL,
  `name_local` VARCHAR(60)  NOT NULL COMMENT 'Nom dans sa propre langue',
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order` TINYINT      NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `supported_languages` VALUES
('fr', 'Français',  'Français', 1, 1),
('en', 'English',   'English',  1, 2),
('rn', 'Kirundi',   'Ikirundi', 1, 3);

-- Traductions dynamiques (interface + contenu administrable)
DROP TABLE IF EXISTS `translations`;
CREATE TABLE `translations` (
  `id`        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `locale`    CHAR(2)       NOT NULL,
  `key`       VARCHAR(200)  NOT NULL,
  `value`     TEXT          NOT NULL,
  `group`     VARCHAR(50)   NOT NULL DEFAULT 'ui',
  `updated_at` DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()) ON UPDATE (UTC_TIMESTAMP()),
  UNIQUE KEY uq_locale_key (`locale`, `key`),
  INDEX idx_group (`group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 4 : DEVISES & TAUX DE CHANGE
-- ══════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `currencies`;
CREATE TABLE `currencies` (
  `code`       CHAR(3)      NOT NULL PRIMARY KEY COMMENT 'BIF|USD',
  `name`       VARCHAR(60)  NOT NULL,
  `symbol`     VARCHAR(10)  NOT NULL,
  `decimals`   TINYINT      NOT NULL DEFAULT 0 COMMENT 'BIF=0 décimales',
  `is_primary` TINYINT(1)   NOT NULL DEFAULT 0,
  `is_active`  TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `currencies` VALUES
('BIF', 'Franc Burundais',   'FBu', 0, 1, 1),
('USD', 'Dollar Américain',  '$',   2, 0, 1);

-- Taux actifs
DROP TABLE IF EXISTS `exchange_rates`;
CREATE TABLE `exchange_rates` (
  `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `base_currency`  CHAR(3)        NOT NULL DEFAULT 'USD',
  `quote_currency` CHAR(3)        NOT NULL DEFAULT 'BIF',
  `rate`           DECIMAL(18,6)  NOT NULL COMMENT '1 USD = X BIF',
  `source`         VARCHAR(80)    NOT NULL DEFAULT 'manual',
  `effective_from` DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `effective_to`   DATETIME       NULL     COMMENT 'NULL = actif indéfiniment',
  `is_active`      TINYINT(1)     NOT NULL DEFAULT 1,
  `created_by`     BIGINT UNSIGNED NULL,
  `created_at`     DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  UNIQUE KEY uq_base_quote_active (`base_currency`, `quote_currency`, `is_active`),
  INDEX idx_effective (`effective_from`, `effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `exchange_rates` (`base_currency`,`quote_currency`,`rate`,`source`,`is_active`) VALUES
('USD','BIF',6000.000000,'initial_setup',1);

-- Historique des modifications de taux
DROP TABLE IF EXISTS `exchange_rate_history`;
CREATE TABLE `exchange_rate_history` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `old_rate`   DECIMAL(18,6)  NOT NULL,
  `new_rate`   DECIMAL(18,6)  NOT NULL,
  `source`     VARCHAR(80)    NOT NULL,
  `reason`     VARCHAR(255)   NULL,
  `changed_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 5 : HÉBERGEMENT — chambres, catégories, équipements, traductions
-- ══════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `room_categories`;
CREATE TABLE `room_categories` (
  `id`    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`  VARCHAR(50) NOT NULL UNIQUE,
  `sort_order` TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_categories` VALUES (1,'Suite',1),(2,'Deluxe',2),(3,'Executive',3),(4,'Villa',4);

-- ── rooms ─────────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id`     TINYINT UNSIGNED NOT NULL,
  `room_number`     VARCHAR(20)  NULL  COMMENT 'Numéro/nom physique de chambre',
  `slug`            VARCHAR(150) NOT NULL UNIQUE,
  `name`            VARCHAR(150) NOT NULL,
  `description`     TEXT         NOT NULL,
  -- Prix stockés en BIGINT (BIF) — jamais de float
  `price_per_night_bif` BIGINT UNSIGNED NOT NULL COMMENT 'Prix en Francs Burundais',
  `capacity_adults` TINYINT      NOT NULL DEFAULT 2,
  `capacity_children` TINYINT    NOT NULL DEFAULT 0,
  `surface_m2`      SMALLINT     NOT NULL DEFAULT 30,
  `floor`           TINYINT      NULL,
  `view`            VARCHAR(80)  NULL COMMENT 'Vue lac, jardin, piscine…',
  `photo_main`      VARCHAR(255) NOT NULL DEFAULT '',
  `photos_json`     JSON         NULL COMMENT 'Tableau URLs photos supplémentaires',
  `amenities_json`  JSON         NULL COMMENT 'Liste équipements',
  `is_active`       TINYINT(1)   NOT NULL DEFAULT 1,
  `sort_order`      SMALLINT     NOT NULL DEFAULT 0,
  `created_at`      DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `updated_at`      DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()) ON UPDATE (UTC_TIMESTAMP()),
  FOREIGN KEY (`category_id`) REFERENCES `room_categories`(`id`) ON UPDATE CASCADE,
  INDEX idx_active_order (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Traductions par chambre
DROP TABLE IF EXISTS `room_translations`;
CREATE TABLE `room_translations` (
  `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_id`     INT UNSIGNED NOT NULL,
  `locale`      CHAR(2)      NOT NULL,
  `name`        VARCHAR(150) NOT NULL,
  `description` TEXT         NOT NULL,
  UNIQUE KEY uq_room_locale (`room_id`, `locale`),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blocages de maintenance
DROP TABLE IF EXISTS `room_blocks`;
CREATE TABLE `room_blocks` (
  `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `room_id`      INT UNSIGNED NOT NULL,
  `reason`       ENUM('maintenance','cleaning','renovation','reserved_vip','other') NOT NULL DEFAULT 'maintenance',
  `notes`        TEXT         NULL,
  `start_date`   DATE         NOT NULL,
  `end_date`     DATE         NOT NULL,
  `created_by`   BIGINT UNSIGNED NULL,
  `resolved_at`  DATETIME     NULL,
  `cost_bif`     BIGINT       NOT NULL DEFAULT 0,
  `created_at`   DATETIME     NOT NULL DEFAULT (UTC_TIMESTAMP()),
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  INDEX idx_room_dates (`room_id`, `start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales chambres (migration depuis schema.sql existant)
INSERT INTO `rooms`
  (`id`,`category_id`,`room_number`,`slug`,`name`,`description`,
   `price_per_night_bif`,`capacity_adults`,`surface_m2`,`view`,
   `photo_main`,`amenities_json`,`is_active`,`sort_order`)
VALUES
(1, 1, 'PRES-01', 'suite-presidentielle-tanganyika',
 'Suite Présidentielle Tanganyika Vue Lac',
 'Suite somptueuse au bord du Lac Tanganyika à Bujumbura. Terrasse panoramique, baignoire jacuzzi en marbre, salon privé et service majordome 24h/24.',
 3900000, 3, 115, 'Vue Lac Tanganyika', 'assets/images/suite_presidentielle.jpg',
 '["Lit King-Size","Terrasse Privée Lac","Wi-Fi 6","Jacuzzi","Majordome 24/7","Nespresso","Smart TV 65pouces 4K"]',
 1, 1),

(2, 2, 'DEL-01', 'deluxe-plage-bujumbura',
 'Chambre Deluxe Plage & Jardins de Bujumbura',
 'Baignée de la lumière tropicale du lac. Finitions en bois précieux burundais, terrasse ombragée et vue sur les jardins verdoyants et la piscine.',
 2280000, 2, 50, 'Vue Jardin & Piscine', 'assets/images/hero_hotel.jpg',
 '["Lit King-Size","Terrasse Ombragée","Wi-Fi Premium","Climatisation","Coffre-fort","Douche Italienne"]',
 1, 2),

(3, 3, 'EXE-01', 'executive-jardin-spa',
 'Chambre Executive Jardin & Spa',
 'Nichée dans la végétation luxuriante des collines de Bujumbura avec accès direct au Spa holistique et espaces de relaxation privatifs.',
 2700000, 2, 65, 'Vue Jardin Tropical', 'assets/images/spa_piscine.jpg',
 '["Lit King-Size","Accès Spa Illimité","Terrasse Privative","Wi-Fi 6","Station Bluetooth","Peignoirs Soie"]',
 1, 3),

(4, 4, 'VIL-01', 'villa-privee-tanganyika',
 'Villa Privée Tanganyika Plunge Pool',
 'Sommet de l\'hôtellerie burundaise. Villa indépendante au bord de l\'eau avec piscine privée chauffée, solarium et majordome dédié.',
 5520000, 4, 160, 'Vue Lac Privée', 'assets/images/suite_presidentielle.jpg',
 '["Piscine Privée Chauffée","2 Lits King-Size","Service Villa 24/7","Système Audio Premium","Transfert VIP Aéroport"]',
 1, 4);

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 6 : RÉSERVATIONS — bookings complets
-- ══════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id`                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference`           VARCHAR(20)    NOT NULL UNIQUE COMMENT 'RES-BDI-XXXXX',
  `room_id`             INT UNSIGNED   NOT NULL,
  `customer_id`         BIGINT UNSIGNED NULL COMMENT 'NULL = réservation invité',
  -- Snapshot du client au moment de la réservation (données dénormalisées)
  `guest_first_name`    VARCHAR(80)    NOT NULL,
  `guest_last_name`     VARCHAR(80)    NOT NULL,
  `guest_email`         VARCHAR(150)   NOT NULL,
  `guest_phone`         VARCHAR(30)    NOT NULL,
  `guest_country`       CHAR(2)        NULL,
  -- Dates
  `date_arrivee`        DATE           NOT NULL,
  `date_depart`         DATE           NOT NULL,
  `nb_adults`           TINYINT        NOT NULL DEFAULT 1,
  `nb_children`         TINYINT        NOT NULL DEFAULT 0,
  -- Prix — BIGINT jamais float
  `currency_chosen`     CHAR(3)        NOT NULL DEFAULT 'BIF',
  `exchange_rate_used`  DECIMAL(18,6)  NOT NULL COMMENT 'Taux figé au moment de la réservation',
  `price_per_night_bif` BIGINT UNSIGNED NOT NULL COMMENT 'Snapshot du prix nuit',
  `nb_nights`           TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `subtotal_bif`        BIGINT UNSIGNED NOT NULL COMMENT 'prix_nuit * nb_nuits',
  `services_total_bif`  BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `discount_bif`        BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `total_bif`           BIGINT UNSIGNED NOT NULL,
  `total_usd_cents`     BIGINT UNSIGNED NOT NULL COMMENT 'USD en centimes × 100',
  `price_snapshot_json` JSON           NULL COMMENT 'Détail complet du calcul',
  -- Paiement
  `payment_method`      VARCHAR(30)    NOT NULL DEFAULT 'manual',
  `payment_status`      ENUM('unpaid','partial','paid','refunded','dispute') NOT NULL DEFAULT 'unpaid',
  -- Statut de la réservation
  `statut`              ENUM(
    'provisional','confirmed','checked_in','checked_out','cancelled','no_show'
  ) NOT NULL DEFAULT 'provisional',
  `cancelled_at`        DATETIME       NULL,
  `cancellation_reason` VARCHAR(255)   NULL,
  `cancelled_by`        VARCHAR(20)    NULL COMMENT 'customer|admin|system',
  -- Options supplémentaires
  `services_json`       JSON           NULL COMMENT 'Services ajoutés avec prix',
  `offer_code`          VARCHAR(30)    NULL,
  `special_requests`    TEXT           NULL,
  -- Suivi
  `source`              VARCHAR(40)    NOT NULL DEFAULT 'web' COMMENT 'web|phone|walk-in|ota',
  `notes_admin`         TEXT           NULL,
  `invoice_sent_at`     DATETIME       NULL,
  `created_at`          DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `updated_at`          DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()) ON UPDATE (UTC_TIMESTAMP()),
  FOREIGN KEY (`room_id`)     REFERENCES `rooms`(`id`)     ON UPDATE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  INDEX idx_reference       (`reference`),
  INDEX idx_room_dates      (`room_id`, `date_arrivee`, `date_depart`),
  INDEX idx_customer        (`customer_id`),
  INDEX idx_statut_arrivee  (`statut`, `date_arrivee`),
  INDEX idx_statut_depart   (`statut`, `date_depart`),
  INDEX idx_created         (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NOTE : Le trigger ci-dessous doit être exécuté via un client MySQL
-- (phpMyAdmin, CLI mysql) et NON via MigrationRunner.php (PDO ne supporte
-- pas DELIMITER). Le MigrationRunner le détecte et le saute automatiquement.
-- Fichier séparé : migrations/trigger_bookings.sql
-- CREATE TRIGGER trg_bookings_check_dates ... (voir fichier dédié)

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 7 : PAIEMENTS — transactions et webhooks
-- ══════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id`                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id`          BIGINT UNSIGNED NOT NULL,
  `idempotency_key`     VARCHAR(100)  NOT NULL UNIQUE COMMENT 'Anti-double paiement',
  `provider`            VARCHAR(30)   NOT NULL COMMENT 'lumicash|ecocash|easypay|paypal|manual|card',
  `payment_method`      VARCHAR(30)   NOT NULL,
  -- Montants BIGINT
  `amount_bif`          BIGINT UNSIGNED NOT NULL,
  `amount_usd_cents`    BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `exchange_rate`       DECIMAL(18,6) NOT NULL,
  `currency_charged`    CHAR(3)       NOT NULL DEFAULT 'BIF',
  -- Statuts
  `payment_status`      ENUM(
    'initiated','pending_customer','processing','successful',
    'failed','expired','cancelled','provider_unavailable',
    'manual_review','refunded'
  ) NOT NULL DEFAULT 'initiated',
  -- Références externes (jamais données sensibles)
  `provider_reference`  VARCHAR(255)  NULL UNIQUE COMMENT 'ID unique côté fournisseur',
  `provider_event_id`   VARCHAR(255)  NULL       COMMENT 'ID webhook event (anti-rejeu)',
  `mobile_number`       VARCHAR(30)   NULL       COMMENT 'Numéro Mobile Money masqué',
  `bank_name`           VARCHAR(80)   NULL,
  -- Expiration pour Mobile Money
  `expires_at`          DATETIME      NULL,
  -- Métadonnées
  `initiated_at`        DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `confirmed_at`        DATETIME      NULL,
  `failed_at`           DATETIME      NULL,
  `failure_reason`      VARCHAR(255)  NULL,
  `webhook_received_at` DATETIME      NULL,
  `metadata_json`       JSON          NULL COMMENT 'Données fournisseur normalisées',
  `created_at`          DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `updated_at`          DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()) ON UPDATE (UTC_TIMESTAMP()),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON UPDATE CASCADE,
  INDEX idx_booking       (`booking_id`),
  INDEX idx_status        (`payment_status`),
  INDEX idx_provider_ref  (`provider_reference`),
  INDEX idx_event_id      (`provider_event_id`),
  INDEX idx_expires       (`expires_at`),
  INDEX idx_idempotency   (`idempotency_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Remboursements
DROP TABLE IF EXISTS `refunds`;
CREATE TABLE `refunds` (
  `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `payment_id`      BIGINT UNSIGNED NOT NULL,
  `amount_bif`      BIGINT UNSIGNED NOT NULL,
  `amount_usd_cents` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `exchange_rate`   DECIMAL(18,6)  NOT NULL COMMENT 'Taux contractuel d\'origine',
  `reason`          VARCHAR(255)   NOT NULL,
  `status`          ENUM('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `provider_ref`    VARCHAR(255)   NULL,
  `initiated_by`    BIGINT UNSIGNED NOT NULL COMMENT 'admin_user_id',
  `completed_at`    DATETIME       NULL,
  `notes`           TEXT           NULL,
  `created_at`      DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journal des webhooks reçus (anti-rejeu)
DROP TABLE IF EXISTS `webhook_events`;
CREATE TABLE `webhook_events` (
  `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `provider`     VARCHAR(30)   NOT NULL,
  `event_id`     VARCHAR(255)  NOT NULL,
  `payload_hash` VARCHAR(64)   NOT NULL COMMENT 'SHA-256 du payload brut',
  `processed`    TINYINT(1)    NOT NULL DEFAULT 0,
  `processed_at` DATETIME      NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  UNIQUE KEY uq_provider_event (`provider`, `event_id`),
  INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 8 : SERVICES, OFFRES, AVIS
-- ══════════════════════════════════════════════════════════════════════

-- ── services ─────────────────────────────────────────────────────────
DROP TABLE IF EXISTS `services`;
CREATE TABLE `services` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category`    ENUM('spa','restaurant','transport','loisirs','conferences','events','other') NOT NULL,
  `title`       VARCHAR(150)  NOT NULL,
  `description` TEXT          NOT NULL,
  `price_bif`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `price_unit`  VARCHAR(50)   NOT NULL DEFAULT 'par personne',
  `photo`       VARCHAR(255)  NOT NULL DEFAULT '',
  `is_active`   TINYINT(1)    NOT NULL DEFAULT 1,
  `sort_order`  SMALLINT      NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `service_translations`;
CREATE TABLE `service_translations` (
  `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `service_id`  INT UNSIGNED NOT NULL,
  `locale`      CHAR(2)      NOT NULL,
  `title`       VARCHAR(150) NOT NULL,
  `description` TEXT         NOT NULL,
  UNIQUE KEY uq_service_locale (`service_id`, `locale`),
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales services
INSERT INTO `services` (`id`,`category`,`title`,`description`,`price_bif`,`price_unit`,`photo`,`is_active`,`sort_order`) VALUES
(1,'spa','Rituel Spa Tanganyika & Soin Holistique',
 '90 minutes de détente absolue avec huiles essentielles locales et massage aux pierres chaudes du lac.',
 840000,'par séance','assets/images/spa_piscine.jpg',1,1),
(2,'restaurant','Dîner Gastronomique du Lac (5 Services)',
 'Menu signature par notre Chef avec poissons frais du Lac Tanganyika (Capitaine & Sangala) et accords régionaux.',
 1080000,'par personne','assets/images/restaurant_gourmet.jpg',1,2),
(3,'transport','Navette VTC Aéroport Melchior Ndadaye',
 'Accueil personnalisé et transfert sécurisé en véhicule SUV climatisé avec chauffeur bilingue.',
 180000,'par trajet','assets/images/hero_hotel.jpg',1,3),
(4,'loisirs','Excursion Privée en Bateau sur le Lac Tanganyika',
 'Croisière exclusive au coucher du soleil avec rafraîchissements et champagne à bord.',
 1500000,'la demi-journée','assets/images/hero_hotel.jpg',1,4);

-- ── booking_services (services ajoutés à une réservation) ────────────
DROP TABLE IF EXISTS `booking_services`;
CREATE TABLE `booking_services` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` BIGINT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED    NOT NULL,
  `quantity`   TINYINT         NOT NULL DEFAULT 1,
  `unit_price_bif` BIGINT UNSIGNED NOT NULL COMMENT 'Snapshot prix',
  `total_bif`  BIGINT UNSIGNED NOT NULL,
  `notes`      VARCHAR(255)    NULL,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `services`(`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── offers (codes promo, remises) ────────────────────────────────────
DROP TABLE IF EXISTS `offers`;
CREATE TABLE `offers` (
  `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`             VARCHAR(150)  NOT NULL,
  `description`       TEXT          NOT NULL,
  `code`              VARCHAR(30)   NULL UNIQUE,
  `discount_type`     ENUM('percent','fixed_bif','fixed_usd') NOT NULL DEFAULT 'percent',
  `discount_value`    BIGINT UNSIGNED NOT NULL DEFAULT 0
                      COMMENT 'En % ou en BIF/USD selon discount_type',
  `min_nights`        TINYINT       NOT NULL DEFAULT 1,
  `max_uses`          INT           NULL COMMENT 'NULL = illimité',
  `uses_count`        INT           NOT NULL DEFAULT 0,
  `max_uses_per_customer` TINYINT   NOT NULL DEFAULT 1,
  `valid_from`        DATE          NULL,
  `valid_to`          DATE          NULL,
  `applicable_rooms`  JSON          NULL COMMENT 'IDs de chambres, NULL = toutes',
  `is_active`         TINYINT(1)    NOT NULL DEFAULT 1,
  `photo`             VARCHAR(255)  NOT NULL DEFAULT '',
  `created_at`        DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `offer_translations`;
CREATE TABLE `offer_translations` (
  `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `offer_id`    INT UNSIGNED NOT NULL,
  `locale`      CHAR(2)      NOT NULL,
  `title`       VARCHAR(150) NOT NULL,
  `description` TEXT         NOT NULL,
  UNIQUE KEY uq_offer_locale (`offer_id`, `locale`),
  FOREIGN KEY (`offer_id`) REFERENCES `offers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `offers`
  (`id`,`title`,`description`,`code`,`discount_type`,`discount_value`,`min_nights`,`valid_to`,`photo`) VALUES
(1,'Escapade Romantique Lac Tanganyika',
 '2 nuits en Suite, champagne, petit-déjeuner au lit et massage duo au Spa.',
 'TANGANYIKA20','percent',20,2,'2026-12-31','assets/images/spa_piscine.jpg'),
(2,'Forfait Découverte Gastronomique',
 '1 nuit en Chambre Deluxe Vue Lac + Dîner Gastronomique 5 services pour 2.',
 'BUJUMBURA15','percent',15,1,'2026-11-30','assets/images/restaurant_gourmet.jpg');

-- ── reviews (avis clients) ────────────────────────────────────────────
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id`          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id`  BIGINT UNSIGNED NULL,
  `customer_id` BIGINT UNSIGNED NULL,
  `guest_name`  VARCHAR(120)   NOT NULL,
  `guest_origin` VARCHAR(100)  NULL,
  `rating`      TINYINT        NOT NULL DEFAULT 5 COMMENT '1 à 5',
  `comment`     TEXT           NOT NULL,
  `stay_type`   VARCHAR(60)    NULL,
  `locale`      CHAR(2)        NOT NULL DEFAULT 'fr',
  `is_visible`  TINYINT(1)     NOT NULL DEFAULT 0 COMMENT '0 = en attente de modération',
  `is_flagged`  TINYINT(1)     NOT NULL DEFAULT 0,
  `moderated_by` BIGINT UNSIGNED NULL,
  `moderated_at` DATETIME      NULL,
  `created_at`  DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  FOREIGN KEY (`booking_id`)  REFERENCES `bookings`(`id`)  ON DELETE SET NULL,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
  INDEX idx_visible (`is_visible`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Données initiales avis (migration depuis schema.sql existant)
INSERT INTO `reviews` (`guest_name`,`guest_origin`,`rating`,`comment`,`stay_type`,`locale`,`is_visible`) VALUES
('Jean-Paul K.','Bujumbura, Burundi',5,
 'Un cadre idyllique au bord du Lac Tanganyika ! Le service Lumicash et l\'accueil de l\'équipe sont irréprochables.',
 'Séjour Affaires','fr',1),
('Elena & Marc','Bruxelles, Belgique',5,
 'Sublime hôtel à Bujumbura. La vue sur le lac au coucher du soleil depuis la Suite Présidentielle est magique.',
 'Vacances en Afrique','fr',1),
('Dr. Thierry Habimana','Kigali, Rwanda',5,
 'Très impressionné par la qualité du service, la gastronomie du lac et les installations de conférence.',
 'Séminaire International','fr',1);

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 9 : PAGES, CONTACTS, CONFÉRENCES
-- ══════════════════════════════════════════════════════════════════════

-- ── page_translations ─────────────────────────────────────────────────
DROP TABLE IF EXISTS `page_translations`;
CREATE TABLE `page_translations` (
  `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `page_key`     VARCHAR(80)   NOT NULL COMMENT 'ex: page.home.meta_title',
  `locale`       CHAR(2)       NOT NULL,
  `content`      TEXT          NOT NULL,
  `updated_at`   DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()) ON UPDATE (UTC_TIMESTAMP()),
  UNIQUE KEY uq_page_locale (`page_key`, `locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── messages_contact ─────────────────────────────────────────────────
DROP TABLE IF EXISTS `messages_contact`;
CREATE TABLE `messages_contact` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nom`        VARCHAR(150)  NOT NULL,
  `email`      VARCHAR(150)  NOT NULL,
  `telephone`  VARCHAR(30)   NULL,
  `sujet`      VARCHAR(150)  NOT NULL DEFAULT 'Demande d\'information',
  `message`    TEXT          NOT NULL,
  `locale`     CHAR(2)       NOT NULL DEFAULT 'fr',
  `ip_address` VARCHAR(45)   NOT NULL DEFAULT '',
  `is_read`    TINYINT(1)    NOT NULL DEFAULT 0,
  `replied_at` DATETIME      NULL,
  `created_at` DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  INDEX idx_read (`is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── conference_quote_requests ─────────────────────────────────────────
DROP TABLE IF EXISTS `conference_quote_requests`;
CREATE TABLE `conference_quote_requests` (
  `id`              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `company`         VARCHAR(150)  NULL,
  `contact_name`    VARCHAR(150)  NOT NULL,
  `email`           VARCHAR(150)  NOT NULL,
  `phone`           VARCHAR(30)   NOT NULL,
  `event_type`      VARCHAR(100)  NOT NULL,
  `nb_participants` SMALLINT      NOT NULL,
  `event_date`      DATE          NOT NULL,
  `duration_days`   TINYINT       NOT NULL DEFAULT 1,
  `budget_bif`      BIGINT        NULL,
  `message`         TEXT          NULL,
  `status`          ENUM('new','contacted','quoted','confirmed','cancelled') NOT NULL DEFAULT 'new',
  `handled_by`      BIGINT UNSIGNED NULL,
  `locale`          CHAR(2)       NOT NULL DEFAULT 'fr',
  `ip_address`      VARCHAR(45)   NOT NULL DEFAULT '',
  `created_at`      DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP())
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 10 : LOGS ET AUDIT
-- ══════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `admin_user_id`  BIGINT UNSIGNED NULL  COMMENT 'NULL si action cliente',
  `customer_id`    BIGINT UNSIGNED NULL  COMMENT 'NULL si action admin',
  `action`         VARCHAR(80)    NOT NULL COMMENT 'ex: booking.created',
  `resource_type`  VARCHAR(50)    NULL,
  `resource_id`    BIGINT UNSIGNED NULL,
  `old_values`     JSON           NULL  COMMENT 'Valeurs avant modification — secrets masqués',
  `new_values`     JSON           NULL  COMMENT 'Valeurs après modification — secrets masqués',
  `ip_address`     VARCHAR(45)    NOT NULL DEFAULT '',
  `user_agent`     VARCHAR(512)   NULL,
  `request_id`     VARCHAR(16)    NOT NULL COMMENT 'Corrélation entre logs',
  `result`         ENUM('success','failure') NOT NULL DEFAULT 'success',
  `failure_reason` VARCHAR(255)   NULL,
  `created_at`     DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  INDEX idx_admin_user  (`admin_user_id`, `created_at`),
  INDEX idx_customer    (`customer_id`,   `created_at`),
  INDEX idx_action      (`action`,         `created_at`),
  INDEX idx_resource    (`resource_type`,  `resource_id`),
  INDEX idx_created     (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit trail append-only — ne jamais UPDATE ni DELETE';

-- Facturation
DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id`     BIGINT UNSIGNED NOT NULL,
  `invoice_number` VARCHAR(30)    NOT NULL UNIQUE COMMENT 'INV-2026-XXXXX',
  `issued_at`      DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `due_date`       DATE           NULL,
  `total_bif`      BIGINT UNSIGNED NOT NULL,
  `total_usd_cents` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `exchange_rate`  DECIMAL(18,6)  NOT NULL,
  `currency_display` CHAR(3)      NOT NULL DEFAULT 'BIF',
  `status`         ENUM('draft','sent','paid','cancelled') NOT NULL DEFAULT 'draft',
  `pdf_path`       VARCHAR(255)   NULL COMMENT 'Chemin relatif dans storage/private/',
  `locale`         CHAR(2)        NOT NULL DEFAULT 'fr',
  `sent_at`        DATETIME       NULL,
  `paid_at`        DATETIME       NULL,
  `created_at`     DATETIME       NOT NULL DEFAULT (UTC_TIMESTAMP()),
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON UPDATE CASCADE,
  INDEX idx_booking (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 11 : PARAMÈTRES HÔTEL
-- ══════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `parametres`;
CREATE TABLE `parametres` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cle`         VARCHAR(80)   NOT NULL UNIQUE,
  `valeur`      TEXT          NOT NULL,
  `type`        ENUM('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
  `description` VARCHAR(255)  NULL,
  `is_public`   TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = visible côté client',
  `updated_at`  DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()) ON UPDATE (UTC_TIMESTAMP()),
  `updated_by`  BIGINT UNSIGNED NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `parametres` (`cle`,`valeur`,`type`,`description`,`is_public`) VALUES
('taux_usd_bif',          '6000',                     'decimal',  '1 USD = X Francs Burundais',           1),
('nom_hotel',             'Hôtel Le Lézard Bleu & Spa','string',   'Nom officiel de l\'établissement',     1),
('devise_defaut',         'BIF',                      'string',   'Devise par défaut du système',          1),
('telephone_hotel',       '+257 22 00 00 00',          'string',   'Numéro de téléphone principal',        1),
('email_hotel',           'contact@lezardbleu-hotel.bi','string',  'E-mail de contact',                    1),
('whatsapp_hotel',        '+257 79 00 00 00',           'string',  'WhatsApp conciergerie',                1),
('heure_checkin',         '14:00',                    'string',   'Heure de check-in standard',            1),
('heure_checkout',        '11:00',                    'string',   'Heure de check-out standard',           1),
('delai_annulation_h',    '48',                       'integer',  'Délai annulation gratuite en heures',   1),
('commission_ota_pct',    '15',                       'integer',  'Commission OTA en %',                   0),
('tva_pct',               '18',                       'integer',  'TVA applicable en %',                   0),
('max_jours_reservation', '365',                      'integer',  'Horizon max de réservation en jours',   1),
('expiration_paiement_min','30',                      'integer',  'Expiration paiement mobile money (min)',0),
('adresse_hotel',         'Avenue de la Plage, Bord du Lac Tanganyika, Bujumbura, Burundi',
                                                      'string',   'Adresse postale complète',              1);

-- ══════════════════════════════════════════════════════════════════════
-- BLOC 12 : NOTIFICATIONS ASYNCHRONES
-- ══════════════════════════════════════════════════════════════════════

DROP TABLE IF EXISTS `notification_queue`;
CREATE TABLE `notification_queue` (
  `id`           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type`         ENUM('email','sms','whatsapp','push') NOT NULL DEFAULT 'email',
  `recipient`    VARCHAR(150)  NOT NULL COMMENT 'Email ou numéro de téléphone',
  `subject`      VARCHAR(255)  NULL,
  `template`     VARCHAR(80)   NOT NULL,
  `data_json`    JSON          NULL,
  `locale`       CHAR(2)       NOT NULL DEFAULT 'fr',
  `status`       ENUM('pending','sending','sent','failed','cancelled') NOT NULL DEFAULT 'pending',
  `attempts`     TINYINT       NOT NULL DEFAULT 0,
  `max_attempts` TINYINT       NOT NULL DEFAULT 3,
  `scheduled_at` DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  `sent_at`      DATETIME      NULL,
  `error_msg`    VARCHAR(500)  NULL,
  `created_at`   DATETIME      NOT NULL DEFAULT (UTC_TIMESTAMP()),
  INDEX idx_status_scheduled (`status`, `scheduled_at`),
  INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════════════
-- CLÔTURE : réactiver les FK
-- ══════════════════════════════════════════════════════════════════════
SET FOREIGN_KEY_CHECKS = 1;

-- Vue de disponibilité (requête optimisée)
DROP VIEW IF EXISTS `v_room_availability`;
CREATE VIEW `v_room_availability` AS
SELECT
  r.id,
  r.slug,
  r.name,
  r.price_per_night_bif,
  r.capacity_adults,
  r.surface_m2,
  r.photo_main,
  rc.name AS category
FROM rooms r
JOIN room_categories rc ON rc.id = r.category_id
WHERE r.is_active = 1;
