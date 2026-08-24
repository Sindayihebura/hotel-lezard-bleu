# Modèle Relationnel — Hôtel Le Lézard Bleu & Spa
## Bujumbura, Burundi | Version 1.0

---

## Principes de stockage des montants

| Devise | Type SQL      | Unité              | Exemple          |
|--------|---------------|--------------------|------------------|
| BIF    | `BIGINT`      | Francs entiers     | 3 900 000 BIF    |
| USD    | `BIGINT`      | Centimes (× 100)  | 65000 = $650.00  |
| Taux   | `DECIMAL(18,6)` | 1 USD = X BIF    | 6000.000000      |

> **Règle absolue** : jamais `FLOAT` ni `DOUBLE` pour les montants.

---

## Diagramme des relations principales

```
roles (1) ──────────── (N) role_permissions (N) ──── (1) permissions
  │
  └─ (1) ──── (N) admin_users

customers (1) ──────── (N) bookings (N) ──── (1) rooms
                              │                      │
                              │              room_categories (1) ─ (N) rooms
                              │
                        booking_services (N) ──── (1) services
                              │
                        payments (1..N)
                              │
                        refunds (0..N)

bookings (1) ──── (1) invoices
bookings (1) ──── (0..1) reviews

exchange_rates ──── exchange_rate_history

notification_queue (async)
audit_logs (append-only)
webhook_events (anti-rejeu)
```

---

## Tables par bloc

### BLOC 1 — Sécurité
| Table                | Rôle                                          |
|----------------------|-----------------------------------------------|
| `rate_limits`        | Rate limiting par clé (IP, route, user)       |
| `email_verifications`| Tokens de vérification d'email                |
| `password_resets`    | Tokens de réinitialisation de mot de passe    |
| `login_attempts`     | Historique tentatives de connexion            |

### BLOC 2 — Utilisateurs
| Table             | Rôle                                             |
|-------------------|--------------------------------------------------|
| `roles`           | 6 rôles : super_admin → service_agent            |
| `permissions`     | 19 permissions granulaires                       |
| `role_permissions`| Table pivot rôles ↔ permissions                  |
| `admin_users`     | Personnel hôtelier avec MFA optionnel            |
| `customers`       | Clients (compte complet ou invité)               |

### BLOC 3 — Multilingue
| Table                  | Rôle                                       |
|------------------------|--------------------------------------------|
| `supported_languages`  | fr, en, rn (Kirundi)                       |
| `translations`         | Traductions dynamiques administrables      |
| `room_translations`    | Nom + description chambre par locale       |
| `service_translations` | Titre + description service par locale     |
| `offer_translations`   | Titre + description offre par locale       |
| `page_translations`    | Contenu SEO et pages statiques             |

### BLOC 4 — Devises
| Table                  | Rôle                                       |
|------------------------|--------------------------------------------|
| `currencies`           | BIF (primaire) et USD                      |
| `exchange_rates`       | Taux actifs (un seul actif à la fois)      |
| `exchange_rate_history`| Journal immuable des changements de taux   |

### BLOC 5 — Hébergement
| Table              | Rôle                                           |
|--------------------|------------------------------------------------|
| `room_categories`  | Suite, Deluxe, Executive, Villa               |
| `rooms`            | Chambres avec prix BIGINT et JSON équipements  |
| `room_translations`| Traductions par chambre                       |
| `room_blocks`      | Blocages maintenance/nettoyage                 |

### BLOC 6 — Réservations
| Table              | Rôle                                           |
|--------------------|------------------------------------------------|
| `bookings`         | Réservation complète, prix figés, statuts      |
| `booking_services` | Services additionnels liés à une réservation   |

**Statuts bookings** : `provisional → confirmed → checked_in → checked_out`
Chemins alternatifs : `cancelled`, `no_show`

### BLOC 7 — Paiements
| Table             | Rôle                                            |
|-------------------|-------------------------------------------------|
| `payments`        | Transactions avec idempotency_key unique        |
| `refunds`         | Remboursements au taux contractuel d'origine    |
| `webhook_events`  | Anti-rejeu des webhooks (provider + event_id)   |

**Statuts payments** :
`initiated → pending_customer → processing → successful`
Chemins alternatifs : `failed`, `expired`, `cancelled`, `provider_unavailable`, `manual_review`, `refunded`

### BLOC 8 — Services & Offres
| Table                       | Rôle                                   |
|-----------------------------|----------------------------------------|
| `services`                  | Spa, restaurant, transport, loisirs…   |
| `service_translations`      | Traductions des services               |
| `offers`                    | Codes promo, remises %/fixes           |
| `offer_translations`        | Traductions des offres                 |
| `reviews`                   | Avis clients (modération avant affichage) |

### BLOC 9 — Pages & Contact
| Table                       | Rôle                                   |
|-----------------------------|----------------------------------------|
| `page_translations`         | SEO et contenus de pages               |
| `messages_contact`          | Formulaire de contact                  |
| `conference_quote_requests` | Devis séminaires/mariages              |

### BLOC 10 — Logs & Facturation
| Table                  | Rôle                                       |
|------------------------|--------------------------------------------|
| `audit_logs`           | Piste d'audit immuable, append-only        |
| `invoices`             | Factures liées aux réservations            |
| `notification_queue`   | File d'envoi email/SMS/WhatsApp asynchrone |

### BLOC 11 — Paramètres
| Table        | Rôle                                             |
|--------------|--------------------------------------------------|
| `parametres` | Configuration hôtel (taux, horaires, politiques) |

---

## Index critiques (performance)

- `bookings` : (`room_id`, `date_arrivee`, `date_depart`) — requête disponibilité
- `bookings` : (`statut`, `date_arrivee`) — arrivées du jour
- `payments` : (`payment_status`) — paiements en attente
- `payments` : (`provider_reference`) — lookup webhook
- `webhook_events` : (`provider`, `event_id`) UNIQUE — anti-rejeu
- `audit_logs` : (`action`, `created_at`) — filtrage par action
- `rate_limits` : (`rate_key`, `expires_at`) — lookup rapide

---

## Vues
| Vue                  | Rôle                                  |
|----------------------|---------------------------------------|
| `v_room_availability`| Données des chambres actives optimisées|
