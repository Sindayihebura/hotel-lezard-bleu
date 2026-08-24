# 🚀 GUIDE DE CONFIGURATION AUTHENTIFICATION
## Hôtel Le Lézard Bleu - Setup Supabase + Google OAuth

Ce guide vous accompagne étape par étape pour activer l'authentification sur votre site.

---

## ⏱️ TEMPS ESTIMÉ : 15-20 MINUTES

---

## 📋 ÉTAPE 1 : SUPABASE - EMAIL PROVIDER (5 min)

### 1.1 Activer l'authentification Email

1. Aller sur [Supabase Dashboard](https://supabase.com/dashboard)
2. Sélectionner votre projet
3. Menu **Authentication** → **Providers**
4. Cliquer sur **Email**
5. Activer les options :
   - ✅ **Enable Email Provider**
   - ✅ **Confirm Email** (recommandé)
   - ✅ **Secure email change** (recommandé)
6. Cliquer **Save**

### 1.2 Configurer SMTP (emails de confirmation)

**Option A : Utiliser le SMTP Supabase (par défaut)**
- Limité à quelques emails par heure
- Parfait pour tester

**Option B : Utiliser votre SMTP (production)**
1. Menu **Project Settings** → **Auth** → **SMTP Settings**
2. Renseigner :
   - SMTP Host (ex: `smtp.gmail.com` pour Gmail)
   - SMTP Port (`587` pour TLS)
   - Username (votre email)
   - Password (mot de passe d'application)
   - Sender Email
   - Sender Name (ex: "Hôtel Le Lézard Bleu")
3. Cliquer **Save**

---

## 🔧 ÉTAPE 2 : GOOGLE CLOUD - OAUTH CLIENT (10 min)

### 2.1 Créer un projet Google Cloud

1. Aller sur [Google Cloud Console](https://console.cloud.google.com/)
2. Cliquer **Select a project** → **New Project**
3. Nom du projet : `Hotel-Lezard-Bleu-Auth`
4. Cliquer **Create**

### 2.2 Configurer l'écran de consentement OAuth

1. Menu **APIs & Services** → **OAuth consent screen**
2. Sélectionner **External**
3. Cliquer **Create**
4. Remplir :
   - **App name** : `Hôtel Le Lézard Bleu`
   - **User support email** : Votre email
   - **Developer contact** : Votre email
5. Cliquer **Save and Continue**
6. **Scopes** : Laisser par défaut (email, profile, openid)
7. Cliquer **Save and Continue**
8. **Test users** : Laisser vide (ou ajouter emails de test)
9. Cliquer **Save and Continue**

### 2.3 Créer les credentials OAuth

1. Menu **APIs & Services** → **Credentials**
2. Cliquer **Create Credentials** → **OAuth client ID**
3. **Application type** : `Web application`
4. **Name** : `Hotel Lezard Bleu Web Client`
5. **Authorized JavaScript origins** :
   ```
   https://votre-domaine.netlify.app
   ```
6. **Authorized redirect URIs** :
   ```
   https://[PROJECT_REF].supabase.co/auth/v1/callback
   ```
   
   **⚠️ Comment trouver [PROJECT_REF] ?**
   - Aller sur votre dashboard Supabase
   - L'URL ressemble à : `https://supabase.com/dashboard/project/xxxproject`
   - `xxxproject` est votre `[PROJECT_REF]`
   - Exemple complet : `https://xxxproject.supabase.co/auth/v1/callback`

7. Cliquer **Create**

### 2.4 Copier les identifiants

Google affiche une popup avec :
- **Client ID** : `123456789-abc.apps.googleusercontent.com`
- **Client Secret** : `GOCSPX-xxx`

**⚠️ IMPORTANT : Ne fermez pas cette fenêtre ! Vous allez copier ces valeurs dans Supabase.**

---

## 🔗 ÉTAPE 3 : SUPABASE - GOOGLE PROVIDER (3 min)

### 3.1 Activer Google OAuth

1. Retour sur [Supabase Dashboard](https://supabase.com/dashboard)
2. Menu **Authentication** → **Providers**
3. Cliquer sur **Google**
4. Activer :
   - ✅ **Enable Google Provider**
5. Coller les valeurs de Google Cloud :
   - **Client ID** : Coller le Client ID
   - **Client Secret** : Coller le Client Secret
6. Cliquer **Save**

**✅ Google OAuth est maintenant configuré dans Supabase !**

---

## 🌐 ÉTAPE 4 : SUPABASE - URL CONFIGURATION (2 min)

### 4.1 Configurer les URLs autorisées

1. Menu **Authentication** → **URL Configuration**
2. **Site URL** :
   ```
   https://votre-domaine.netlify.app
   ```
3. **Redirect URLs** (ajouter toutes ces lignes) :
   ```
   https://votre-domaine.netlify.app/**
   https://*--votre-domaine.netlify.app/**
   http://localhost:8888/**
   ```
4. Cliquer **Save**

**Explication :**
- `/**` : Autorise tous les chemins (`/account.html`, `/reset-password.html`, etc.)
- `*--votre-domaine` : Autorise les deploy previews Netlify
- `localhost:8888` : Développement local avec Netlify CLI

---

## 🔐 ÉTAPE 5 : NETLIFY - VARIABLES D'ENVIRONNEMENT (5 min)

### 5.1 Récupérer les clés Supabase

1. Aller sur [Supabase Dashboard](https://supabase.com/dashboard)
2. Menu **Settings** → **API**
3. Noter :
   - **Project URL** (ex: `https://xxxproject.supabase.co`)
   - **anon public** (commence par `eyJhbGciOiJIUzI1NiIsInR5cCI...`)
   - **service_role** (commence par `eyJhbGciOiJIUzI1NiIsInR5cCI...`)

### 5.2 Ajouter les variables dans Netlify

1. Aller sur [Netlify Dashboard](https://app.netlify.com/)
2. Sélectionner votre site
3. Menu **Site Settings** → **Environment variables**
4. Cliquer **Add a variable** pour chaque :

| Nom | Valeur | Exemple |
|-----|--------|---------|
| `SUPABASE_URL` | Project URL | `https://xxxproject.supabase.co` |
| `SUPABASE_ANON_KEY` | anon public | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...` |
| `SUPABASE_SERVICE_ROLE_KEY` | service_role | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...` |
| `SITE_URL` | URL production | `https://votre-domaine.netlify.app` |

5. Cliquer **Save** pour chaque variable

**⚠️ SÉCURITÉ :**
- `SUPABASE_SERVICE_ROLE_KEY` n'est utilisée QUE par les Netlify Functions (serveur)
- Elle ne sera JAMAIS exposée au navigateur
- Le build Netlify génère automatiquement `env-config.js` avec uniquement les clés publiques

---

## 🚀 ÉTAPE 6 : DÉPLOIEMENT (5 min)

### Option A : Netlify CLI (recommandé)

```bash
# Installer Netlify CLI (si pas déjà fait)
npm install -g netlify-cli

# Connexion
netlify login

# Déploiement production
netlify deploy --prod
```

Le script `generate-env-config.sh` est exécuté automatiquement et crée `env-config.js` avec vos clés publiques.

### Option B : Git + Netlify (automatique)

1. Pousser votre code sur GitHub/GitLab/Bitbucket
2. Connecter le dépôt dans Netlify :
   - **Site Settings** → **Build & Deploy** → **Continuous Deployment**
   - Cliquer **Link site to Git**
   - Sélectionner votre dépôt
3. Chaque `git push` déclenche automatiquement :
   - Génération de `env-config.js`
   - Déploiement des Netlify Functions
   - Publication du site

---

## ✅ ÉTAPE 7 : TESTS (10 min)

### 7.1 Tester l'inscription email

1. Aller sur `https://votre-domaine.netlify.app/register.html`
2. Remplir le formulaire avec un email de test
3. Créer le compte
4. Vérifier la réception de l'email de confirmation
5. Cliquer sur le lien de confirmation
6. ✅ Compte confirmé

### 7.2 Tester la connexion email

1. Aller sur `https://votre-domaine.netlify.app/login.html`
2. Saisir email + mot de passe
3. Cliquer "Se connecter"
4. ✅ Redirection vers `/account.html`
5. Vérifier l'affichage du profil

### 7.3 Tester Google OAuth

1. Aller sur `https://votre-domaine.netlify.app/login.html`
2. Cliquer "Continuer avec Google"
3. Sélectionner un compte Google
4. Autoriser l'application
5. ✅ Redirection vers `/account.html`
6. Vérifier :
   - Avatar Google affiché
   - Nom complet récupéré
   - Badge "Connexion via: Google"

### 7.4 Tester la déconnexion

1. Sur `/account.html`, cliquer "Déconnexion"
2. ✅ Redirection vers `/index.html`
3. Tenter d'accéder à `/account.html`
4. ✅ Redirection vers `/login.html`

### 7.5 Tester reset password

1. Aller sur `/login.html`
2. Cliquer "Mot de passe oublié ?"
3. Saisir votre email
4. Cliquer "Envoyer le lien"
5. Vérifier la réception de l'email
6. Cliquer sur le lien
7. ✅ Redirection vers `/reset-password.html` avec formulaire
8. Saisir nouveau mot de passe
9. Confirmer
10. ✅ Redirection vers `/login.html`

---

## 🛠️ DÉVELOPPEMENT LOCAL

### Avec les vraies clés Supabase

```powershell
# Windows PowerShell
$env:SUPABASE_URL="https://xxxproject.supabase.co"
$env:SUPABASE_ANON_KEY="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."

# Générer env-config.js
.\generate-env-config.ps1

# Lancer Netlify Dev
netlify dev
```

```bash
# Linux / macOS
export SUPABASE_URL="https://xxxproject.supabase.co"
export SUPABASE_ANON_KEY="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."

# Générer env-config.js
chmod +x generate-env-config.sh
./generate-env-config.sh

# Lancer Netlify Dev
netlify dev
```

Le site sera accessible sur `http://localhost:8888`

---

## ❓ PROBLÈMES FRÉQUENTS

### "Configuration Supabase manquante"

**Cause :** `env-config.js` n'a pas été généré.

**Solution :**
1. Vérifier que les variables Netlify sont définies
2. Redéployer le site
3. Vérifier dans les logs de build que `generate-env-config.sh` s'est exécuté

### "Email ou mot de passe incorrect"

**Cause :** Compte pas encore confirmé ou mauvais identifiants.

**Solution :**
1. Vérifier l'email de confirmation Supabase
2. Cliquer sur le lien de confirmation
3. Réessayer la connexion

### "Impossible de se connecter avec Google"

**Cause :** Redirect URI non configuré ou mauvais Client ID.

**Solution :**
1. Vérifier que le Redirect URI dans Google Cloud contient exactement :
   ```
   https://[PROJECT_REF].supabase.co/auth/v1/callback
   ```
2. Vérifier que le Client ID est bien collé dans Supabase
3. Vérifier que le Google Provider est activé dans Supabase

### "Redirection en boucle sur account.html"

**Cause :** Session Supabase expirée ou corrompue.

**Solution :**
1. Ouvrir la console développeur (F12)
2. Aller dans **Application** → **Local Storage**
3. Supprimer la clé `hotel_auth_token`
4. Recharger la page
5. Se reconnecter

---

## 📞 SUPPORT

### Documentation officielle

- [Supabase Auth Docs](https://supabase.com/docs/guides/auth)
- [Google OAuth Setup](https://developers.google.com/identity/protocols/oauth2)
- [Netlify Environment Variables](https://docs.netlify.com/environment-variables/overview/)

### Logs de débogage

Pour activer les logs détaillés dans la console :

```javascript
// Dans la console navigateur (F12)
localStorage.setItem('supabase.auth.debug', 'true')
```

---

## ✅ CHECKLIST FINALE

Avant de mettre en production, vérifiez :

- [ ] Email Provider activé dans Supabase
- [ ] SMTP configuré (Supabase ou externe)
- [ ] Google Provider activé dans Supabase
- [ ] Google Client ID/Secret renseignés
- [ ] Redirect URIs configurés (Google Cloud + Supabase)
- [ ] Variables Netlify définies (4 variables)
- [ ] Site déployé via Netlify CLI ou Git
- [ ] Inscription email testée
- [ ] Connexion email testée
- [ ] Google OAuth testé
- [ ] Reset password testé
- [ ] Déconnexion testée
- [ ] Mobile testé

---

**🎉 Félicitations ! Votre authentification est opérationnelle !**

Vos clients peuvent maintenant créer un compte, se connecter avec Google ou email, et gérer leurs réservations sur `account.html`.
