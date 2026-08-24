# Déployer sur Netlify — Guide Simple
## Hôtel Le Lézard Bleu & Spa

---

## ÉTAPE 1 — Créer la base de données Supabase (GRATUIT)

1. Va sur **https://supabase.com** → créer un compte gratuit
2. Clique **New Project** → nom : `hotel-lezard-bleu`
3. Va dans **SQL Editor** → copie-colle tout le contenu de `migrations/001_initial_schema.sql` → clique **Run**
4. Va dans **Settings → API** → copie :
   - **Project URL** (ex: `https://xxx.supabase.co`)
   - **anon public key** (ex: `eyJhbGci...`)

---

## ÉTAPE 2 — Mettre le projet sur GitHub

1. Va sur **https://github.com** → crée un compte
2. Clique **New repository** → nom : `hotel-lezard-bleu` → **Public**
3. Télécharge **GitHub Desktop** → https://desktop.github.com
4. **Add existing repository** → sélectionne `d:\Projet_Hotel`
5. **Publish repository**

---

## ÉTAPE 3 — Déployer sur Netlify

1. Va sur **https://netlify.com** → **Sign up with GitHub**
2. Clique **Add new site** → **Import from Git** → sélectionne `hotel-lezard-bleu`
3. **Build settings** :
   - Build command : `npm install`
   - Publish directory : `.`
4. Clique **Deploy site**

---

## ÉTAPE 4 — Configurer les variables d'environnement

Dans Netlify → **Site Settings → Environment Variables** → ajoute :

| Variable | Valeur |
|----------|--------|
| `SUPABASE_URL` | L'URL copiée depuis Supabase |
| `SUPABASE_ANON_KEY` | La clé copiée depuis Supabase |
| `DEFAULT_EXCHANGE_RATE` | `6000` |

---

## ÉTAPE 5 — Mettre à jour index.html

Ouvre `index.html` et remplace ces 2 lignes :
```javascript
const SUPABASE_URL = 'REMPLACER_PAR_TON_URL_SUPABASE';
const SUPABASE_KEY = 'REMPLACER_PAR_TA_CLE_SUPABASE';
```

Par tes vraies valeurs Supabase.

---

## Résultat

Netlify te donne un lien comme :
```
https://hotel-lezard-bleu.netlify.app
```

**Ton site est visible partout dans le monde.**
