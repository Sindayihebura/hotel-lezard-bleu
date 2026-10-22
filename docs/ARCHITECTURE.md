# Architecture du site — Hôtel Le Lézard Bleu & Spa

## Vue d'ensemble

Site statique (HTML, CSS, JavaScript) publié par Netlify depuis `dist/`.
Les données dynamiques passent par des Netlify Functions (`netlify/functions/`) qui
s'appuient sur Supabase (PostgreSQL + Auth).

```
Navigateur
   │
   ├── Pages statiques (dist/*.html)
   │      ├── assets/js/main.js          UI globale (devise, menu, toasts)
   │      ├── assets/js/forms.js         validation et accessibilité des champs
   │      ├── assets/js/supabase-auth.js authentification cliente
   │      ├── assets/js/app.js           API + catalogue + réservations
   │      └── assets/js/pages/*.js       logique d'une page
   │
   ├── /api/*  ──►  Netlify Functions ──► Supabase (Postgres, Auth)
   │                 rooms, availability, bookings, contact,
   │                 auth, payments, reviews, newsletter
   │
   └── Supabase Auth (e-mail / Google) — SDK local dist/vendor/supabase.js
```

## Chargement des scripts

Chaque page charge, dans cet ordre, en fin de `<head>` :

1. `env-config.js` — configuration publique (URL + clé anon Supabase)
2. `vendor/supabase.js` — SDK Supabase (UMD, servi depuis le site)
3. `forms.js` — système de champs (indépendant)
4. `main.js` — interface globale
5. `supabase-auth.js` — auth (dépend de `forms.js` et du SDK)
6. `app.js` — logique métier (dépend de `main.js` et `supabase-auth.js`)

Puis, en fin de `<body>`, le script propre à la page (`assets/js/pages/…`).

Aucun module ES ni import distant n'est nécessaire : le site fonctionne sans CDN.

## Système de formulaires

`forms.js` expose `window.Forms` :

| Méthode | Rôle |
| --- | --- |
| `attach(form, { onSubmit })` | branche la validation, l'état de chargement et le submit |
| `validate(form)` | valide tous les champs, affiche messages + résumé |
| `validateField(field)` | renvoie un message d'erreur ou `null` |
| `setFieldError` / `clearFieldError` | gestion d'erreur unitaire (attributs ARIA inclus) |
| `setLoading` / `clearLoading` | bouton en attente sans double envoi |
| `read(form)` | objet clé/valeur nettoyé |
| `notify(message, type)` | toast global |

Règles lues sur le DOM : `required`, `type`, `minlength`, `min`, `max`,
`data-rule`, `data-match`, `data-min="today"`, `data-after`, `data-strength`.

## États d'indisponibilité (mode démonstration)

`app.js` distingue :

- erreur métier (HTTP 400/409, JSON) → message affiché à l'utilisateur ;
- service absent (404/405/501, réponse HTML, réseau coupé) → `ServiceUnavailableError`.

Dans le second cas :

- réservation → enregistrée dans `localStorage` (`hotel_demo_bookings`, préfixe `DEMO`) ;
- contact / newsletter → conservés localement et signalés comme démonstration ;
- l'interface affiche un bandeau explicite et les coordonnées de la réception.

Aucun succès n'est simulé sans en informer l'utilisateur.

## Authentification

- `supabase-auth.js` crée le client uniquement si `window.supabase` et la configuration
  sont présents.
- En cas d'échec, l'objet `SupabaseAuth` reste exporté : les pages affichent un message
  clair au lieu de planter (`TypeError` silencieux auparavant).
- Les erreurs Supabase sont traduites en français (identifiants invalides, e-mail non
  confirmé, mot de passe faible, limite d'envoi, réseau…).
- Session stockée sous `hotel_auth_token` (localStorage), flux PKCE.

## Mise en page

`tools/normalize_layout.py` est la source unique de l'en-tête, du menu mobile et du
pied de page. Il injecte également les scripts communs et les métadonnées (icône,
couleur de thème). Toute modification de navigation se fait dans ce script, puis :

```bash
python3 tools/normalize_layout.py
```

## Qualité

| Commande | Vérification |
| --- | --- |
| `npm run check` | liens/ressources, identifiants dupliqués, étiquettes, scripts communs |
| `npm run check:browser` | chargement des pages dans jsdom, globals, sélecteurs clés |
| `npm run test:flows` | connexion, validation, devise, réservation, contact |

`tools/serve.js` sert le site en local sans dépendance (URLs propres, 404, no-store).
