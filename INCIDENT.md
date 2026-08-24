# Procédures d'Incident — Hôtel Le Lézard Bleu & Spa

---

## INCIDENT 1 — Paiement bloqué

**Symptôme :** Client a payé mais la réservation reste "provisional"

**Actions :**
1. Aller dans `/admin/payments/` → chercher le paiement
2. Vérifier le statut Lumicash/EcoCash dans leur portail marchand
3. Si paiement confirmé côté opérateur → cliquer **Confirmer** dans l'admin
4. Si doute → passer en `manual_review` et appeler l'opérateur
5. Notifier le client par téléphone/WhatsApp : +257 79 00 00 00

**Prévention :** Cron `expire_payments.php` toutes les 5 minutes

---

## INCIDENT 2 — Tentative d'intrusion

**Symptôme :** Alerte brute force dans `/storage/logs/security_alerts.log`

**Actions immédiates :**
1. Bloquer l'IP dans Cloudflare → **Firewall** → **IP Access Rules** → Block
2. Vérifier les logs : `storage/logs/app.log` et `audit_logs` en DB
3. Vérifier si un compte admin a été compromis → `admin/users/` → suspendre si nécessaire
4. Changer tous les mots de passe admin
5. Activer MFA pour tous les admins

---

## INCIDENT 3 — Base de données inaccessible

**Actions :**
1. Vérifier cPanel → MySQL Databases → statut du serveur
2. Vérifier les paramètres `.env` (DB_HOST, DB_PASSWORD)
3. Contacter le support hébergeur avec le message d'erreur exact
4. Si dépasse 30 minutes → basculer en mode maintenance

**Mode maintenance (temporaire) :**
Créer un fichier `public/maintenance.php` et rediriger via `.htaccess` :
```apache
RewriteRule ^(.*)$ /maintenance.php [L]
```

---

## INCIDENT 4 — Données compromises

**Actions immédiates (dans l'heure) :**
1. Désactiver le site → mode maintenance
2. Changer TOUS les mots de passe DB + admin
3. Révoquer toutes les sessions actives
4. Contacter les clients potentiellement affectés
5. Sauvegarder les logs avant toute action
6. Notifier les autorités compétentes (loi Burundi sur la protection des données)

---

## INCIDENT 5 — Double réservation

**Symptôme :** Deux réservations confirmées pour la même chambre aux mêmes dates

**Actions :**
1. Identifier les deux réservations dans `/admin/reservations/`
2. Contacter les deux clients immédiatement
3. Proposer une chambre alternative ou un remboursement complet
4. Vérifier les logs DB pour comprendre la cause (transaction manquée ?)
5. Signaler comme bug critique si la protection `FOR UPDATE` a échoué

---

## PROCÉDURE DE SAUVEGARDE

**Sauvegarde manuelle via cPanel → Backup :**
1. Sauvegarder la base de données MySQL → exporter en SQL
2. Sauvegarder les fichiers du projet (hors vendor/)
3. Stocker dans `storage/backups/` ET sur un drive externe
4. **Tester la restauration** sur un environnement de staging

**Fréquence recommandée :**
- Base de données : quotidienne (cPanel auto-backup ou script)
- Fichiers : hebdomadaire
- Test de restauration : mensuel

**Script sauvegarde DB (Linux/cron) :**
```bash
# /cron/backup_db.sh
mysqldump -u lezard_user -p hotel_lezardbleu | gzip > /backups/hotel_$(date +%Y%m%d).sql.gz
```
