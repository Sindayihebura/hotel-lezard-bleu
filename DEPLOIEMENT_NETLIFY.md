# Guide de déploiement — Hôtel Le Lézard Bleu sur Netlify

## Ce qui a été préparé automatiquement

| Fichier | Correction apportée |
|---|---|
| `.netlify/netlify.toml` | Chemins Windows absolus → chemins relatifs |
| `netlify.toml` | Commande build : `chmod +x ...` → `node generate-env-config.js` |
| `generate-env-config.js` | Nouveau script Node.js (remplace le .sh, compatible Windows/Linux) |
| `package.json` | Version Supabase épinglée, scripts build/dev/deploy ajoutés |
| `dist/package.json` | Simplifié (supprimé netlify-cli inutile dans dist/) |
| `dist/env-config.template.js` | Ajout de DEFAULT_EXCHANGE_RATE |
| `.gitignore` | Nettoyé et complété |

---

## Étapes pour déployer sur Netlify

### 1. Installer Node.js (si pas encore fait)

Télécharger et installer Node.js 18 LTS depuis :
👉 https://nodejs.org/dist/v18.20.4/node-v18.20.4-x64.msi

Vérifier après installation (dans un nouveau terminal) :
```
node --version   # doit afficher v18.x.x
npm --version    # doit afficher 9.x ou 10.x
```

### 2. Installer les dépendances Node.js localement

Ouvrir un terminal dans `e:\Projet_Hotel` et exécuter :
```
npm install
```

Cela génère/met à jour `package-lock.json` (à committer avec le code).

### 3. Tester le script de build localement

```
node generate-env-config.js
```

Vous devez voir :
```
🔧 Génération de dist/env-config.js...
✅ dist/env-config.js généré avec succès
```

### 4. Committer tous les fichiers modifiés

```
git add .
git commit -m "chore: prepare for Netlify deployment"
git push
```

> ⚠️  Vérifier avant de pousser que `.env` n'est PAS dans les fichiers à committer :
> ```
> git status
> ```

### 5. Connecter le dépôt à Netlify

1. Aller sur https://app.netlify.com
2. Cliquer **Add new site → Import an existing project**
3. Choisir **GitHub** (ou GitLab/Bitbucket selon votre hébergeur git)
4. Sélectionner le dépôt `Projet_Hotel`
5. Paramètres de build Netlify :

   | Champ | Valeur |
   |---|---|
   | Base directory | *(laisser vide)* |
   | Build command | `node generate-env-config.js` |
   | Publish directory | `dist` |
   | Functions directory | `netlify/functions` |

### 6. Configurer les variables d'environnement sur Netlify

Dans **Site Settings → Environment Variables**, ajouter :

| Variable | Valeur |
|---|---|
| `SUPABASE_URL` | `https://rwzzpzzwkutpwcqllqzt.supabase.co` |
| `SUPABASE_ANON_KEY` | `sb_publishable_LbZweT_3THf2p34VHi_iuA_vJo5UcR1` |
| `DEFAULT_EXCHANGE_RATE` | `6000` |
| `NODE_VERSION` | `18` |

> Ces valeurs sont aussi dans `.env.example` pour référence.

### 7. Lancer le premier déploiement

Cliquer **Deploy site** dans le dashboard Netlify.

Suivre les logs de build. Le build doit afficher :
```
🔧 Génération de dist/env-config.js...
✅ dist/env-config.js généré avec succès
```

### 8. Vérifier après déploiement

Tester ces URLs sur votre site Netlify (ex: `https://lelezardbleu.netlify.app`) :

- [ ] Page d'accueil : `https://lelezardbleu.netlify.app/`
- [ ] Chambres : `https://lelezardbleu.netlify.app/chambres`
- [ ] Réservation : `https://lelezardbleu.netlify.app/reservation`
- [ ] API Chambres : `https://lelezardbleu.netlify.app/api/rooms`
- [ ] API Auth : `https://lelezardbleu.netlify.app/api/auth` (doit renvoyer JSON)
- [ ] Contact : `https://lelezardbleu.netlify.app/contact`
- [ ] Login : `https://lelezardbleu.netlify.app/login`

---

## Architecture du déploiement

```
GitHub / GitLab
     │
     ▼
Netlify Build
  ├── npm install           (installe @supabase/supabase-js)
  ├── node generate-env-config.js  (génère dist/env-config.js)
  └── publish dist/         (site statique HTML/CSS/JS)

Netlify Functions (netlify/functions/*.js)
  ├── auth.js        → /api/auth/*
  ├── rooms.js       → /api/rooms
  ├── bookings.js    → /api/bookings
  ├── availability.js→ /api/availability
  ├── contact.js     → /api/contact
  ├── payments.js    → /api/payments
  └── reviews.js     → /api/reviews

Base de données
  └── Supabase (PostgreSQL cloud)
      ├── Tables : rooms, bookings, customers, reviews
      └── Auth : Supabase Auth (JWT)
```

---

## Problèmes fréquents

### Build échoue : "Cannot find module '@supabase/supabase-js'"
→ Vérifier que `package.json` à la racine liste `@supabase/supabase-js` en `dependencies`
→ Sur Netlify, `npm install` doit être exécuté avant le build command

### Les fonctions renvoient une erreur CORS
→ Vérifier que l'URL de votre site Netlify est dans la liste `ALLOWED_ORIGINS` de chaque fichier `netlify/functions/*.js`
→ Remplacer `https://lelezardbleu.netlify.app` par votre URL réelle si différente

### Les chambres n'apparaissent pas (page vide)
→ Vérifier les variables `SUPABASE_URL` et `SUPABASE_ANON_KEY` dans Netlify Dashboard
→ Si Supabase n'est pas configuré, les fonctions fonctionnent en mode démo avec des données statiques

### Erreur 404 sur `/chambres` ou `/reservation`
→ Vérifier que `dist/_redirects` est présent et contient les règles de réécriture
→ Le fichier `dist/_redirects` est déjà configuré correctement

### Reset password ne fonctionne pas
→ Dans Supabase Dashboard → Authentication → URL Configuration, ajouter votre URL Netlify comme **Site URL** et comme **Redirect URL** : `https://lelezardbleu.netlify.app/reset-password`
