# Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi

Site officiel de l'hôtel : présentation, chambres, réservation en ligne, espace client,
contact et informations légales. Interface en français, devise BIF / USD.

---

## 1. Démarrage rapide

```bash
npm install          # dépendances (Supabase + outils de contrôle)
npm run preview      # http://localhost:8080
```

La prévisualisation sert le dossier `dist/` sur `0.0.0.0:8080` (aucune dépendance au build).
Les fonctions Netlify ne tournent pas en local : le site bascule alors automatiquement en
**mode démonstration** (réservations et messages conservés dans le navigateur), clairement signalé
à l'utilisateur.

Pour tester avec les functions et Supabase :

```bash
npm install -g netlify-cli
netlify dev          # http://localhost:8888
```

## 2. Organisation du dépôt

```
dist/                       ← SITE DÉPLOYÉ (Netlify : publish = dist)
├── *.html                  pages publiques et espace client
├── assets/css/style.css    design system + formulaires + responsive
├── assets/js/
│   ├── main.js             devise, menu mobile, notifications, année
│   ├── forms.js            validation des champs, erreurs accessibles, mot de passe
│   ├── supabase-auth.js    authentification (e-mail, Google, réinitialisation)
│   ├── app.js              API, catalogue des chambres, réservations, contact
│   ├── home.js             recherche rapide + avis (accueil)
│   └── pages/              logique propre à chaque page
├── vendor/supabase.js      SDK Supabase embarqué (aucun CDN, fonctionne hors ligne)
├── assets/images/          photos du site
├── robots.txt / sitemap.xml / favicon.svg
└── _redirects               routes Netlify (API + URLs propres + 404)

netlify/functions/          API serverless (rooms, bookings, contact, auth, newsletter…)
supabase/schema.sql         schéma de base de données + politiques RLS
tools/                      serveur local, contrôles qualité, tests, génération de mise en page
docs/                       documentation technique et archives
migrations/, app/, admin/   application PHP historique (non utilisée par le déploiement statique)
```

**Source de vérité du site : `dist/`.** Le dossier `assets/` à la racine appartient à
l'application PHP historique ; il est synchronisé depuis `dist/` avec
`npm run sync:legacy-assets`.

## 3. Pages

| URL | Contenu |
| --- | --- |
| `/` | Accueil, recherche de disponibilités, chambres, avis |
| `/presentation.html` | L'hôtel, son histoire, ses valeurs |
| `/chambres.html` | Catalogue filtrable (4 catégories) |
| `/reservation.html` | Réservation en 3 étapes avec récapitulatif live |
| `/confirmation.html` | Récapitulatif de la demande et référence |
| `/galerie.html` | Galerie photos |
| `/services.html` | Restaurant, spa, services |
| `/conferences.html` | Séminaires, mariages, événements |
| `/offres.html` | Offres et privilèges |
| `/contact.html` | Coordonnées, accès, formulaire |
| `/legal.html` | Mentions légales, CGV, confidentialité |
| `/login.html`, `/register.html` | Connexion, création de compte |
| `/reset-password.html` | Mot de passe oublié / nouveau mot de passe |
| `/account.html` | Espace client : profil, historique des réservations |
| `/404.html` | Page introuvable |

## 4. Formulaires et champs

Tous les formulaires partagent le même système (`assets/js/forms.js`) :

- validation française champ par champ, **message sous le champ** et **résumé en tête de formulaire** ;
- attributs d'accessibilité (`aria-invalid`, `aria-describedby`, `role="alert"`, focus sur le premier champ en erreur) ;
- champs `date` contraints (aujourd'hui minimum, départ après arrivée), `select` stylés,
  `textarea` redimensionnable, cases à cocher accessibles ;
- œil d'affichage du mot de passe, indicateur de robustesse, confirmation croisée ;
- état de chargement des boutons, protection contre les doubles envois ;
- style uniforme : focus doré, erreurs rouges, autofill lisible, 16 px minimum sur mobile
  (évite le zoom iOS).

Règles disponibles via attributs HTML : `required`, `type=email|tel|date|number`,
`minlength`/`maxlength`, `min`/`max`, `data-rule="name|tel|password|message"`,
`data-match="#autre-champ"`, `data-min="today"`, `data-after="#checkIn"`.

## 5. Réservation et données

1. Les chambres viennent de `/.netlify/functions/rooms` (repli local : `App.Rooms.catalogue()`).
2. La disponibilité est vérifiée avant l'étape 2 (`/api/availability`).
3. La demande est envoyée à `/api/bookings` ; la référence générée est affichée sur la page
   de confirmation et conservée localement.
4. Si l'API est injoignable (prévisualisation statique, panne), la demande est enregistrée
   localement et signalée comme **démonstration** — aucun faux succès silencieux.

Variables Netlify nécessaires (Site settings → Environment variables) :

```
SUPABASE_URL=
SUPABASE_ANON_KEY=
SUPABASE_SERVICE_ROLE_KEY=     # fonctions serveur uniquement (newsletter)
DEFAULT_EXCHANGE_RATE=6000
```

`npm run build` génère `dist/env-config.js` à partir de ces variables
(`generate-env-config.js`).

## 6. Authentification

- SDK Supabase **embarqué** (`dist/vendor/supabase.js`) : plus de dépendance à un CDN.
- E-mail / mot de passe, Google OAuth, réinitialisation par e-mail.
- Si la configuration ou le réseau est absent, les formulaires affichent un message explicite
  et proposent le téléphone de la réception au lieu d'échouer en silence.
- La clé `SUPABASE_SERVICE_ROLE_KEY` ne doit **jamais** apparaître côté client.

## 7. Contrôles qualité

```bash
npm run check           # liens, ressources, identifiants, étiquettes, scripts communs
npm run preview &       # serveur local
npm run check:browser   # chargement réel des pages dans jsdom
npm run test:flows      # parcours : connexion, devise, réservation, contact
```

## 8. Déploiement

Netlify est configuré à la racine (`netlify.toml`) :

- `publish = "dist"`, `functions = "netlify/functions"`
- routes `/api/*` → functions, URLs propres (`/chambres`, `/legal`…), page 404 personnalisée
- en-têtes de sécurité (CSP, HSTS, X-Frame-Options…)

```bash
npm run deploy          # netlify deploy --prod
```

Autres cibles (VPS, Railway, InfinityFree, PHP historique) : voir `docs/` et `DEPLOIEMENT.md`.
L'application PHP de la racine reste disponible mais **n'est pas** déployée par Netlify.

## 9. Documentation

| Fichier | Contenu |
| --- | --- |
| `docs/ARCHITECTURE.md` | Vue technique : pages, scripts, flux de données |
| `DEPLOIEMENT.md` | Déploiement PHP / serveur classique |
| `DEPLOIEMENT_NETLIFY.md`, `NETLIFY_DEPLOY.md` | Déploiement Netlify |
| `AUTHENTIFICATION_SETUP.md`, `GOOGLE_AUTH_SETUP.md` | Configuration Supabase / Google |
| `SECURITY_CHECKLIST.md` | Contrôles avant mise en production |
| `docs/archive/` | Anciens rapports de déploiement (non publiés) |

## 10. Contact établissement

Avenue de la Plage, bord du Lac Tanganyika — Bujumbura, Burundi
Tél. +257 22 00 00 00 · reservation@lezardbleu-hotel.bi

> Les coordonnées, tarifs et visuels de démonstration doivent être remplacés par les
> informations officielles de l'établissement avant mise en production.
