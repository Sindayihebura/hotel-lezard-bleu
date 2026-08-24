# Checklist Sécurité — Hôtel Le Lézard Bleu & Spa
## À vérifier avant toute mise en production

---

## ✅ AUTHENTIFICATION & SESSIONS

- [ ] `APP_DEBUG=false` dans `.env` production
- [ ] `SESSION_SECURE=true` (nécessite HTTPS)
- [ ] Cookies : `HttpOnly=true`, `SameSite=Lax`, préfixe `__Host-`
- [ ] `session_regenerate_id(true)` après chaque connexion — ✅ Implémenté
- [ ] Mots de passe : `password_hash(PASSWORD_DEFAULT, cost=12)` — ✅ Implémenté
- [ ] Aucun mot de passe en clair nulle part — ✅ Vérifié
- [ ] Rate limiting login (5 tentatives / 10 min) — ✅ Implémenté
- [ ] Messages d'erreur génériques (ne révèle pas si email existe) — ✅ Implémenté
- [ ] MFA admin configuré et testé
- [ ] Sessions admin expirées après inactivité

## ✅ BASE DE DONNÉES

- [ ] PDO + requêtes préparées PARTOUT — ✅ Implémenté
- [ ] Aucune concaténation de données utilisateur dans SQL — ✅ Vérifié
- [ ] Compte DB avec privilèges minimaux (SELECT, INSERT, UPDATE seulement)
- [ ] Mot de passe DB fort dans `.env` (jamais dans le code)
- [ ] Sauvegardes quotidiennes configurées

## ✅ XSS & CSRF

- [ ] `e()` utilisé sur tout output HTML — ✅ Implémenté
- [ ] Token CSRF sur tous les formulaires POST — ✅ Implémenté
- [ ] CSP header configuré dans bootstrap.php — ✅ Implémenté
- [ ] `X-Content-Type-Options: nosniff` — ✅ Implémenté
- [ ] `X-Frame-Options: DENY` — ✅ Implémenté

## ✅ FICHIERS & ACCÈS

- [ ] `.env` non accessible depuis le web (tester : `curl votresite.com/.env` → 403)
- [ ] Dossier `storage/` non accessible depuis le web
- [ ] Dossier `config/` non accessible depuis le web
- [ ] Dossier `app/` non accessible depuis le web
- [ ] Directory listing désactivé (`Options -Indexes` dans .htaccess — ✅ Implémenté)
- [ ] `PHP_INFO` désactivé en production
- [ ] Fichiers de test supprimés (`setup_admin.php`, etc.)

## ✅ HTTPS & HEADERS

- [ ] HTTPS actif et forcé (Cloudflare + .htaccess — ✅ Implémenté)
- [ ] HSTS configuré (`max-age=31536000` — ✅ Implémenté)
- [ ] Certificat SSL valide et renouvelé automatiquement
- [ ] `X-Powered-By` supprimé — ✅ Implémenté

## ✅ PAIEMENTS

- [ ] Aucune clé API en dur dans le code — ✅ Vérifié
- [ ] Signatures webhook vérifiées — ✅ Implémenté (squelette)
- [ ] Anti-rejeu webhook (provider_event_id UNIQUE) — ✅ Implémenté
- [ ] Idempotency key sur chaque initiation — ✅ Implémenté
- [ ] Montant vérifié côté serveur (jamais depuis navigateur) — ✅ Implémenté
- [ ] Endpoints Lumicash/EcoCash à valider avec doc officielle — ⚠️ @todo

## ✅ DEVISES

- [ ] BIF stocké en BIGINT (jamais float) — ✅ Implémenté
- [ ] USD stocké en BIGINT centimes — ✅ Implémenté
- [ ] Taux figé au moment de la réservation — ✅ Implémenté
- [ ] Taux de change affiché sur la facture — ✅ Implémenté
- [ ] Remboursement au taux contractuel d'origine — ✅ Implémenté

## ✅ LOGS & AUDIT

- [ ] audit_logs configuré et fonctionnel — ✅ Implémenté
- [ ] Aucun mot de passe/token dans les logs — ✅ Implémenté (sanitizeContext)
- [ ] Logs en UTC — ✅ Implémenté
- [ ] Rotation des logs configurée (logrotate ou hébergeur)
- [ ] Alertes brute force actives — ✅ Implémenté

## ✅ RBAC

- [ ] Permissions vérifiées côté serveur sur chaque action — ✅ Implémenté
- [ ] BOLA (accès objet) vérifié — ✅ Implémenté
- [ ] Un client ne peut pas voir la réservation d'un autre — ✅ Implémenté
- [ ] Aucun endpoint admin accessible sans permission — ✅ Implémenté

---

## AVANT LANCEMENT PUBLIC

```
1. [ ] Audit de code (manuel ou outil SAST)
2. [ ] Test de pénétration autorisé
3. [ ] Test de restauration depuis sauvegarde
4. [ ] Vérification que .env est inaccessible
5. [ ] Connexion admin testée + MFA
6. [ ] Réservation complète testée de bout en bout
7. [ ] Paiement manuel testé (espèces)
8. [ ] Crons configurés et testés
```
