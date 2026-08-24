# Guide de Déploiement — Hôtel Le Lézard Bleu & Spa
## Bujumbura, Burundi | PHP 8.2 + MySQL + Cloudflare

---

## 1. HÉBERGEMENT RECOMMANDÉ (gratuit ou très peu cher)

### Option A — InfinityFree (Gratuit)
- **URL** : https://infinityfree.com
- PHP 8.x + MySQL + cPanel + SSL Let's Encrypt
- **Limites** : 5 Go stockage, pas de cron (utiliser cron-job.org)
- Bon pour démo/test — **pas recommandé pour production**

### Option B — Hostinger (Payant — recommandé production)
- **URL** : https://www.hostinger.com
- ~$3/mois · PHP 8.2 · MySQL 8 · cPanel · SSL gratuit · cron
- Meilleur rapport qualité/prix pour la région Afrique

### Option C — 000WebHost (Gratuit, Hostinger)
- **URL** : https://www.000webhost.com
- PHP 8.x + MySQL gratuit, limites de bande passante

### Option D — Render.com (Gratuit, Docker)
- **URL** : https://render.com
- PHP via Docker — nécessite un Dockerfile (voir section 6)

---

## 2. CONFIGURATION CLOUDFLARE (DNS + HTTPS + CDN)

### Étape 1 — Créer un compte Cloudflare
1. Aller sur https://cloudflare.com → **Créer un compte gratuit**
2. Ajouter votre domaine (ex: `lezardbleu-hotel.bi`)

### Étape 2 — Configurer les DNS
Dans le tableau de bord Cloudflare → **DNS** :

```
Type    Nom     Contenu                Proxy
A       @       IP de votre hébergeur  ✅ Proxied (orange)
A       www     IP de votre hébergeur  ✅ Proxied (orange)
CNAME   mail    votre-serveur-mail     ❌ DNS only (gris)
```

### Étape 3 — SSL/HTTPS obligatoire
- Cloudflare → **SSL/TLS** → Mode : **Full (strict)**
- Activer **Always Use HTTPS** → ON
- Activer **HSTS** → Enable (max-age 6 months)
- Activer **Automatic HTTPS Rewrites** → ON

### Étape 4 — Règles de sécurité Cloudflare
- **Firewall** → Activer **Bot Fight Mode**
- **Rate Limiting** (Plan Free) : 10 req/10s par IP
- **Page Rules** → Cache Level: Standard pour `/assets/*`
- **Page Rules** → Security Level: High pour `/admin/*`

### Étape 5 — Optimisations Cloudflare
- **Speed** → Auto Minify : ✅ JavaScript ✅ CSS ✅ HTML
- **Speed** → Brotli : ✅ ON
- **Caching** → Browser Cache TTL : 1 day pour les assets

---

## 3. DÉPLOIEMENT PAS À PAS

### Étape 1 — Préparer les fichiers

```bash
# Sur votre machine locale
# 1. Aller dans le dossier projet
cd d:\Projet_Hotel

# 2. Copier .env.example en .env et remplir les valeurs
cp .env.example .env
# Modifier .env avec vos vraies valeurs de DB, email, etc.

# 3. NE JAMAIS uploader .env via FTP (le faire via cPanel File Manager)
```

### Étape 2 — Structure à uploader sur le serveur

```
Sur l'hébergeur, le document root doit pointer sur /public_html/
Uploader TOUT le projet dans un dossier, exemple :

/home/user/
├── Projet_Hotel/          ← TOUT le projet ici
│   ├── app/
│   ├── config/
│   ├── admin/
│   ├── api/
│   ├── migrations/
│   ├── public/            ← CECI devient le document root
│   ├── .env               ← JAMAIS exposé sur le web
│   └── ...
└── public_html/           ← Document root Apache
    └── → symlink vers Projet_Hotel/public/
        OU copie du contenu de public/
```

**Sur hébergement partagé sans accès SSH :**
- Uploader tout le projet dans `/public_html/hotel/`
- Le dossier `public/` est accessible via `votresite.com/`
- Les dossiers `app/`, `config/`, `storage/` sont **au-dessus** de `public_html`

### Étape 3 — Upload via FTP/cPanel

**Connexion FTP :**
```
Hôte    : ftp.votredomaine.com
Login   : votrelogin FTP
Port    : 21 (FTP) ou 22 (SFTP)
```

**Ordre d'upload :**
1. Dossiers : `app/`, `config/`, `migrations/`, `resources/`, `storage/`
2. Fichiers racine : `.htaccess`, `composer.json`
3. Dossier `public/` → contenu dans `public_html/`
4. Dossier `admin/` → dans `public_html/admin/`
5. Dossier `assets/` → dans `public_html/assets/`
6. **NE PAS** uploader : `.env`, `.gitignore`, `vendor/`, `tests/`, `node_modules/`

### Étape 4 — Créer la base de données

**Dans cPanel → Bases de données MySQL :**
1. Créer une base : `hotel_lezardbleu`
2. Créer un utilisateur : `lezard_user`
3. Attribuer tous les privilèges
4. Importer `migrations/001_initial_schema.sql` via phpMyAdmin
5. Importer `migrations/trigger_bookings.sql` séparément via phpMyAdmin

### Étape 5 — Configurer .env sur le serveur

**Dans cPanel → File Manager :**
1. Créer le fichier `.env` à la racine du projet
2. Remplir avec vos vraies valeurs :

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://votre-domaine.bi

DB_HOST=localhost
DB_DATABASE=hotel_lezardbleu
DB_USERNAME=lezard_user
DB_PASSWORD=VotreMotDePasseDB

SESSION_SECURE=true
DEFAULT_EXCHANGE_RATE=6000
```

### Étape 6 — Créer le super admin

**Via cPanel → Terminal (si disponible) :**
```bash
php /chemin/vers/projet/migrations/002_seed_admin.php
```

**Si pas de terminal :**
Créer un fichier `setup_admin.php` temporaire dans `public_html/` :
```php
<?php
// SUPPRIMER CE FICHIER IMMÉDIATEMENT APRÈS UTILISATION
define('CRON_ALLOWED', true);
require_once '../config/bootstrap.php';
require_once '../config/database.php';
$pdo = getDB();
$hash = password_hash('VotreMotDePasse!2026', PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO admin_users (role_id,first_name,last_name,email,password_hash,is_active) VALUES(1,'Admin','Hotel','admin@lezardbleu-hotel.bi',:h,1)")
    ->execute([':h'=>$hash]);
echo 'Admin créé ! SUPPRIMER CE FICHIER MAINTENANT.';
```
Accéder à `https://votresite.com/setup_admin.php` **une seule fois** puis **supprimer immédiatement**.

### Étape 7 — Configurer les crons

**Dans cPanel → Cron Jobs :**

| Fréquence | Commande |
|-----------|----------|
| `*/5 * * * *` | `php /home/user/Projet_Hotel/cron/expire_payments.php` |
| `*/5 * * * *` | `php /home/user/Projet_Hotel/cron/send_notifications.php` |

**Alternative gratuite — cron-job.org :**
1. Créer un compte sur https://cron-job.org
2. Créer une tâche pointant sur `https://votresite.com/cron/expire_payments.php`
3. Ajouter `define('CRON_ALLOWED', true);` en commentaire et sécuriser avec un token secret

---

## 4. VÉRIFICATION POST-DÉPLOIEMENT

```
✅ https://votresite.com → Page d'accueil visible
✅ https://votresite.com/admin/login.php → Page de connexion admin
✅ https://votresite.com/public/connexion.php → Connexion client
✅ https://votresite.com/api/v1/rooms → JSON retourné
✅ Cadenas HTTPS dans le navigateur
✅ Redirection HTTP → HTTPS automatique
✅ Taux de change visible sur la page d'accueil
✅ Formulaire de réservation fonctionnel
✅ Connexion admin avec le compte créé
```

---

## 5. CONFIGURATION APACHE (.htaccess déjà inclus)

Le fichier `public/.htaccess` est déjà configuré. Vérifier que `mod_rewrite` est activé sur l'hébergeur.

Si erreur 500 sur `.htaccess`, désactiver temporairement les règles de réécriture :
```apache
# Commenter temporairement pour diagnostiquer
# RewriteEngine On
```

---

## 6. DOCKERFILE (pour Render.com / hébergement Docker)

```dockerfile
FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql intl
RUN a2enmod rewrite

COPY . /var/www/html/
COPY public/.htaccess /var/www/html/.htaccess

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' \
    /etc/apache2/sites-available/000-default.conf

RUN chmod -R 755 /var/www/html/storage/
EXPOSE 80
```

---

## 7. VARIABLES IMPORTANTES À NE JAMAIS OUBLIER

| Variable | Description |
|----------|-------------|
| `APP_DEBUG=false` | **OBLIGATOIRE** en production |
| `SESSION_SECURE=true` | Nécessite HTTPS actif |
| `DB_PASSWORD` | Mot de passe fort (16+ chars) |
| `.env` non exposé | Vérifier avec `curl votresite.com/.env` → doit retourner 403 |

---

## 8. TEST DE SÉCURITÉ FINAL

Avant de lancer publiquement, tester :
```bash
# Ces URLs doivent toutes retourner 403 ou 404 :
curl https://votresite.com/.env
curl https://votresite.com/config/database.php
curl https://votresite.com/storage/logs/app.log
curl https://votresite.com/migrations/001_initial_schema.sql
```
