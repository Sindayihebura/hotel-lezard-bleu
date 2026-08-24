# Hôtel Le Lézard Bleu & Spa
## Plateforme Hôtelière — Bujumbura, Burundi | Lac Tanganyika

---

## Stack Technique
- **PHP 8.2+** · PSR-4 · Composer
- **MySQL 8** / MariaDB · PDO · BIGINT pour les montants
- **HTML5** · CSS3 · JavaScript ES2022 · Fetch API
- **Sécurité** : OWASP API Top 10 · CSRF · RBAC · Audit logs

## Installation Rapide

```bash
# 1. Copier la configuration
cp .env.example .env
# Remplir DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 2. Installer les dépendances
composer install

# 3. Créer la base de données
mysql -u root -p < migrations/001_initial_schema.sql
mysql -u root -p hotel_lezardbleu < migrations/trigger_bookings.sql

# 4. Créer le super admin
php migrations/002_seed_admin.php

# 5. Lancer (dev)
php -S localhost:8000 -t public/
```

## Accès
| URL | Description |
|-----|-------------|
| `http://localhost:8000` | Site public |
| `http://localhost:8000/../admin/login.php` | Administration |
| `http://localhost:8000/../public/connexion.php` | Espace client |
| `GET /api/v1/rooms` | API chambres |

## Devises
- **BIF** (principal) — stocké en `BIGINT`
- **USD** (secondaire) — stocké en `BIGINT` centimes
- Taux : `DECIMAL(18,6)` — jamais de `float`

## Paiements Burundi
- Espèces BIF / USD · Lumicash · EcoCash · Banques locales

## Déploiement
Voir **DEPLOIEMENT.md** pour les instructions complètes + Cloudflare.

## Sécurité
Voir **SECURITY_CHECKLIST.md** avant toute mise en production.

## Tests
```bash
composer test           # Tous les tests
composer test-unit      # Tests unitaires
composer test-security  # Tests sécurité
composer test-api       # Tests API
```
