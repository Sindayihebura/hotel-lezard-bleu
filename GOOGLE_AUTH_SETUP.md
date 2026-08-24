# Configuration Google OAuth — Étapes Obligatoires

Le code est corrigé. Il reste 3 étapes à faire dans des interfaces externes.

---

## Étape 1 — Google Cloud Console

1. Va sur https://console.cloud.google.com
2. Crée ou sélectionne un projet
3. Menu **APIs & Services → Credentials**
4. Clique **Create Credentials → OAuth 2.0 Client ID**
5. Application type : **Web application**
6. Authorized redirect URIs — ajoute exactement :
   ```
   https://ztmbqaakfwugizvebscr.supabase.co/auth/v1/callback
   ```
7. Copie le **Client ID** et le **Client Secret**

---

## Étape 2 — Supabase Dashboard

1. Va sur https://supabase.com/dashboard/project/ztmbqaakfwugizvebscr
2. **Authentication → Providers → Google**
3. Active le provider
4. Colle le **Client ID** et **Client Secret** de l'étape 1
5. Va dans **Authentication → URL Configuration** et définis :
   - **Site URL** : `https://lelezardbleu.netlify.app`
   - **Redirect URLs** (ajoute ces 3 lignes) :
     ```
     https://lelezardbleu.netlify.app/account.html
     https://lelezardbleu.netlify.app/reset-password.html
     http://localhost:8888/account.html
     ```
6. Sauvegarde

---

## Étape 3 — Netlify Dashboard

1. Va sur https://app.netlify.com → ton site → **Site Settings → Environment Variables**
2. Ajoute ces 2 variables :

   | Clé | Valeur |
   |-----|--------|
   | `SUPABASE_URL` | `https://ztmbqaakfwugizvebscr.supabase.co` |
   | `SUPABASE_ANON_KEY` | `sb_publishable_4M5nhT9daHi-uf-5WelfUA_o0HE_R6C` |

3. Déclenche un nouveau déploiement (**Deploys → Trigger deploy**)

---

## Résumé des corrections apportées au code

| Fichier | Correction |
|---------|-----------|
| `dist/env-config.js` | Injecté les vraies clés Supabase (plus de placeholders `YOUR_SUPABASE_URL`) |
| `dist/assets/js/supabase-auth.js` | Corrigé l'URL de fallback, exposé `window.__supabaseAuthReady` |
| `dist/login.html` | Corrigé la race condition du chargement du module |
| `dist/register.html` | Corrigé la race condition du chargement du module |
| `dist/reset-password.html` | Corrigé la race condition du chargement du module |
| `dist/account.html` | Corrigé la race condition du chargement du module |
| `netlify/functions/auth.js` | Corrigé CORS wildcard → domaines spécifiques |
| `netlify/functions/auth.js` | Corrigé redirect reset password → `/reset-password.html` |
| `netlify/functions/auth.js` | Corrigé logout → supprimé `supabase.auth.admin.signOut()` invalide |
| `netlify.toml` | Ajouté CSP headers, catch-all redirect, section env vars |
| `.env` | Corrigé espace avant SITE_URL, mis à jour les clés Supabase |
| `generate-env-config.ps1` | Ajouté valeurs par défaut pour dev local |
| `generate-env-config.sh` | Ajouté valeurs par défaut pour build Netlify |
