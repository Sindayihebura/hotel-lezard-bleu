# ⚡ Déploiement Rapide - 30 Minutes
## Hôtel Le Lézard Bleu — Site Web Complet

Suivez ces 4 étapes simples pour mettre votre site en ligne.

---

## ✅ Checklist Avant de Commencer

Assurez-vous d'avoir :
- [ ] Un ordinateur avec accès Internet
- [ ] Une adresse email valide
- [ ] 30 minutes de temps libre

Tout le reste est **GRATUIT** ! ✨

---

## 📝 Étape 1 : Supabase (10 min)

### A. Créer le compte
1. Allez sur **https://supabase.com**
2. Cliquez **Start your project** → Créez un compte
3. Cliquez **New project**
   - Name: `hotel-lezard-bleu`
   - Password: Créez un mot de passe fort
   - Region: **Europe (Frankfurt)**
   - Plan: **Free**
4. Cliquez **Create new project** → Attendez 2 min

### B. Créer les tables
1. Menu gauche → **SQL Editor** → **New query**
2. Copiez TOUT le contenu du fichier `database/supabase-schema.sql`
3. Collez dans l'éditeur et cliquez **Run**
4. Vous devriez voir "Success"

### C. Récupérer les clés
1. Menu gauche → **Settings** → **API**
2. Notez ces valeurs quelque part :
```
URL: https://xxxxx.supabase.co
Key: eyJhbGciOi...
```

✅ Base de données créée !

---

## 📝 Étape 2 : GitHub (5 min)

### A. Créer le compte
1. Allez sur **https://github.com** → **Sign up**
2. Créez votre compte gratuitement

### B. Installer GitHub Desktop
1. Téléchargez : **https://desktop.github.com**
2. Installez et lancez l'application
3. Connectez-vous avec votre compte GitHub

### C. Publier le code
1. Dans GitHub Desktop :
   - **File** → **Add local repository**
   - Sélectionnez le dossier **dist** de ce projet
2. Si demandé, cliquez **Create repository**
   - Name: `hotel-lezard-bleu`
3. Cliquez **Publish repository**
   - Décochez "Keep this code private"
   - Cliquez **Publish**

✅ Code publié sur GitHub !

---

## 📝 Étape 3 : Netlify (10 min)

### A. Créer le compte
1. Allez sur **https://netlify.com**
2. Cliquez **Sign up** → **Continue with GitHub**
3. Autorisez Netlify

### B. Déployer
1. Cliquez **Add new site** → **Import an existing project**
2. Cliquez **GitHub** → Sélectionnez **hotel-lezard-bleu**
3. Configuration :
   - Build command: `echo "Static site"`
   - Publish directory: `.`
4. Cliquez **Deploy site**
5. Attendez 1-2 minutes

✅ Site en ligne ! 🎉

---

## 📝 Étape 4 : Configuration (5 min)

### A. Ajouter les variables
1. Dans Netlify, votre site → **Site settings**
2. Menu gauche → **Environment variables**
3. Cliquez **Add a variable** pour chacune :

| Key | Value |
|-----|-------|
| `SUPABASE_URL` | Votre URL Supabase de l'étape 1 |
| `SUPABASE_ANON_KEY` | Votre clé Supabase de l'étape 1 |
| `DEFAULT_EXCHANGE_RATE` | `6000` |

### B. Redéployer
1. Allez dans **Deploys**
2. Cliquez **Trigger deploy** → **Deploy site**
3. Attendez 1-2 minutes

✅ Configuration terminée !

---

## 🎉 C'est Fini !

Votre site est maintenant en ligne :

```
🌍 https://votre-site.netlify.app
```

### Testez votre site :
- ✅ Page d'accueil
- ✅ Voir les chambres
- ✅ Faire une réservation test
- ✅ Envoyer un message de contact

---

## 📱 Personnaliser l'URL (Optionnel)

Pour avoir une meilleure adresse :

1. Dans Netlify → **Site settings** → **General**
2. Cliquez **Change site name**
3. Entrez : `lezardbleu` ou `hotel-lezard-bleu`
4. Votre nouveau lien : `https://lezardbleu.netlify.app`

---

## 🔄 Mettre à Jour le Site

Pour modifier le contenu :

1. Éditez les fichiers HTML dans le dossier `dist`
2. Dans GitHub Desktop :
   - Écrivez un message (ex: "Mise à jour tarifs")
   - Cliquez **Commit to main**
   - Cliquez **Push origin**
3. Netlify redéploie automatiquement (2 min)

---

## 💡 Astuces Rapides

### Ajouter/Modifier des Chambres
- Allez sur Supabase → **Table Editor** → **rooms**
- Modifiez directement dans le tableau
- Changements immédiats !

### Voir les Réservations
- Allez sur Supabase → **Table Editor** → **bookings**
- Toutes vos réservations en temps réel

### Voir les Messages
- Supabase → **Table Editor** → **contact_messages**

---

## ❓ Problèmes Courants

### "Site ne charge pas"
→ Vérifiez que vous avez bien ajouté les 3 variables d'environnement

### "Chambres vides"
→ Vérifiez que le script SQL a bien été exécuté dans Supabase

### "Formulaire ne marche pas"
→ Appuyez sur F12 dans le navigateur pour voir les erreurs

---

## 📞 Besoin d'Aide ?

- 📧 Email : support@lezardbleu-hotel.bi
- 📱 Téléphone : +257 22 00 00 00
- 💬 WhatsApp : +257 79 00 00 00

---

## 📚 Documentation Complète

Pour plus de détails, consultez :
- `GUIDE_DEPLOIEMENT.md` - Guide complet illustré
- `README.md` - Documentation technique
- `.env.example` - Variables d'environnement

---

## 🎯 Prochaines Étapes

Une fois votre site en ligne :

1. **Testez tout** : réservations, formulaires, paiements
2. **Ajoutez vos vraies photos** dans le dossier `assets/images/`
3. **Personnalisez les tarifs** dans Supabase → table `rooms`
4. **Configurez un domaine personnalisé** (optionnel)
5. **Partagez votre site** sur vos réseaux sociaux !

---

**Félicitations ! Votre hôtel a maintenant un site web professionnel ! 🏨✨**

*Guide créé pour Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi*
