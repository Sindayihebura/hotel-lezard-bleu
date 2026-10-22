# 🚀 Guide de Déploiement Complet
## Hôtel Le Lézard Bleu & Spa — Site Web JAMstack

Ce guide vous accompagne pas à pas pour mettre votre site en ligne sur Netlify.

---

## 📋 Vue d'Ensemble

Votre nouveau site web est :
- ✅ **100% Moderne** : HTML5, CSS3, JavaScript
- ✅ **Rapide** : Hébergé sur CDN mondial
- ✅ **Sécurisé** : HTTPS automatique
- ✅ **Gratuit** : Netlify + Supabase gratuits
- ✅ **Sans serveur PHP** : Fonctionne partout

---

## 🎯 Étape 1 : Créer votre Base de Données (15 min)

### 1.1 Inscription Supabase

1. Allez sur **https://supabase.com**
2. Cliquez **Start your project**
3. Créez un compte avec votre email ou GitHub
4. Validez votre email

### 1.2 Créer le Projet

1. Une fois connecté, cliquez **New project**
2. Remplissez :
   - **Name** : `hotel-lezard-bleu`
   - **Database Password** : Choisissez un mot de passe fort (notez-le !)
   - **Region** : `Europe (Frankfurt)` ou le plus proche du Burundi
   - **Pricing Plan** : **Free** (gratuit)
3. Cliquez **Create new project**
4. Attendez 2-3 minutes (le temps que la base de données se crée)

### 1.3 Créer les Tables

1. Dans le menu de gauche, cliquez **SQL Editor**
2. Cliquez **New query**
3. Ouvrez le fichier `database/supabase-schema.sql` de ce projet
4. Copiez TOUT le contenu (Ctrl+A puis Ctrl+C)
5. Collez dans l'éditeur SQL de Supabase
6. Cliquez **Run** (en bas à droite)
7. Vous devriez voir "Success. No rows returned"

### 1.4 Récupérer vos Clés API

1. Dans le menu de gauche, cliquez **Settings** (icône d'engrenage)
2. Cliquez **API**
3. Copiez ces deux valeurs (vous en aurez besoin plus tard) :

```
Project URL : https://xxxxxxxxxxxxx.supabase.co
anon public : eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

> ⚠️ **IMPORTANT** : Gardez ces valeurs en sécurité !

---

## 🎯 Étape 2 : Publier sur GitHub (10 min)

### 2.1 Créer un Compte GitHub

1. Allez sur **https://github.com**
2. Cliquez **Sign up**
3. Créez votre compte (gratuit)
4. Validez votre email

### 2.2 Installer GitHub Desktop

1. Téléchargez **GitHub Desktop** : https://desktop.github.com
2. Installez le programme
3. Lancez-le et connectez-vous avec votre compte GitHub

### 2.3 Publier votre Code

1. Dans GitHub Desktop, cliquez **File** → **Add local repository**
2. Cliquez **Choose...** et sélectionnez le dossier `dist` de ce projet
3. Si un message dit "Ce n'est pas un dépôt Git", cliquez **Create a repository**
4. Remplissez :
   - **Name** : `hotel-lezard-bleu`
   - **Description** : `Site web Hôtel Le Lézard Bleu & Spa`
   - Cochez **Initialize with README**
5. Cliquez **Create repository**
6. Cliquez **Publish repository** (en haut)
7. Décochez **Keep this code private** (pour Netlify gratuit)
8. Cliquez **Publish repository**

✅ Votre code est maintenant sur GitHub !

---

## 🎯 Étape 3 : Déployer sur Netlify (15 min)

### 3.1 Créer un Compte Netlify

1. Allez sur **https://netlify.com**
2. Cliquez **Sign up**
3. Choisissez **Continue with GitHub**
4. Autorisez Netlify à accéder à votre GitHub

### 3.2 Déployer le Site

1. Une fois connecté à Netlify, cliquez **Add new site**
2. Cliquez **Import an existing project**
3. Cliquez **GitHub**
4. Cherchez et sélectionnez **hotel-lezard-bleu**
5. Configuration du build :
   - **Branch to deploy** : `main` ou `master`
   - **Base directory** : (laissez vide)
   - **Build command** : `echo "Static site"`
   - **Publish directory** : `.` ou `dist`
6. Cliquez **Deploy site**

⏳ Netlify va maintenant déployer votre site (environ 1-2 minutes)

### 3.3 Votre Site est en Ligne !

Une fois le déploiement terminé, vous verrez :

```
✅ Site is live
https://random-name-123456.netlify.app
```

Cliquez sur le lien pour voir votre site ! 🎉

---

## 🎯 Étape 4 : Configurer les Variables (10 min)

### 4.1 Ajouter Supabase

1. Dans Netlify, allez dans votre site
2. Cliquez **Site settings** (en haut)
3. Dans le menu de gauche, cliquez **Environment variables**
4. Cliquez **Add a variable** et ajoutez :

#### Variable 1 : SUPABASE_URL
- **Key** : `SUPABASE_URL`
- **Value** : Collez votre Project URL de Supabase (étape 1.4)
- Cliquez **Create variable**

#### Variable 2 : SUPABASE_ANON_KEY
- **Key** : `SUPABASE_ANON_KEY`
- **Value** : Collez votre anon public key de Supabase (étape 1.4)
- Cliquez **Create variable**

#### Variable 3 : DEFAULT_EXCHANGE_RATE
- **Key** : `DEFAULT_EXCHANGE_RATE`
- **Value** : `6000`
- Cliquez **Create variable**

### 4.2 Redéployer

1. Allez dans **Deploys** (en haut)
2. Cliquez **Trigger deploy** → **Deploy site**
3. Attendez 1-2 minutes

✅ Votre site est maintenant fonctionnel avec la base de données !

---

## 🎯 Étape 5 : Personnaliser le Domaine (Optionnel)

### 5.1 Changer le Nom Netlify

1. Dans **Site settings** → **General**
2. Cliquez **Change site name**
3. Entrez : `lezardbleu` ou `hotel-lezard-bleu`
4. Votre site sera : `https://lezardbleu.netlify.app`

### 5.2 Ajouter votre Propre Domaine

Si vous avez acheté un domaine (ex: `lezardbleu-hotel.bi`) :

1. Allez dans **Domain management**
2. Cliquez **Add custom domain**
3. Entrez votre domaine
4. Suivez les instructions pour configurer les DNS

---

## ✅ Vérification Finale

Testez ces pages sur votre site :

- [ ] Page d'accueil : `https://votre-site.netlify.app/`
- [ ] Chambres : `/chambres.html`
- [ ] Réservation : `/reservation.html`
- [ ] Contact : `/contact.html`

### Tester les Fonctionnalités

1. **Changement de devise** : Cliquez sur BIF / USD en haut
2. **Formulaire de réservation** : Remplissez une réservation test
3. **Formulaire de contact** : Envoyez un message test

---

## 📱 Partager votre Site

Votre site est accessible depuis n'importe où dans le monde :

```
🌍 URL : https://votre-site.netlify.app
📱 Compatible mobile, tablette, ordinateur
🔒 Sécurisé avec HTTPS
⚡ Rapide avec CDN mondial
```

Partagez-le :
- Sur Facebook, WhatsApp, Instagram
- Imprimez l'URL sur vos cartes de visite
- Ajoutez-le à votre signature email

---

## 🔧 Maintenance

### Mettre à Jour le Site

1. Modifiez les fichiers HTML dans le dossier `dist`
2. Dans GitHub Desktop :
   - Écrivez un message (ex: "Mise à jour tarifs")
   - Cliquez **Commit to main**
   - Cliquez **Push origin**
3. Netlify redéploie automatiquement (2 minutes)

### Modifier les Chambres

Pour ajouter/modifier des chambres :

1. Allez sur Supabase
2. Cliquez **Table Editor** → **rooms**
3. Modifiez directement dans le tableau
4. Les changements sont immédiats !

### Voir les Réservations

1. Allez sur Supabase
2. Cliquez **Table Editor** → **bookings**
3. Vous voyez toutes les réservations en temps réel

---

## 🆘 Aide et Support

### Problèmes Courants

#### "Site ne charge pas"
- Vérifiez que les variables d'environnement sont bien configurées
- Allez dans Netlify → Deploys → vérifiez qu'il n'y a pas d'erreurs

#### "Chambres ne s'affichent pas"
- Vérifiez que le script SQL a bien été exécuté dans Supabase
- Vérifiez dans Supabase → Table Editor que la table `rooms` contient des données

#### "Formulaire ne fonctionne pas"
- Ouvrez la console du navigateur (F12) pour voir les erreurs
- Vérifiez les Netlify Functions dans Netlify → Functions

### Obtenir de l'Aide

- **Documentation Netlify** : https://docs.netlify.com
- **Documentation Supabase** : https://supabase.com/docs
- **Support Email** : Contactez votre développeur

---

## 📊 Statistiques du Site

Netlify vous donne automatiquement :
- Nombre de visiteurs
- Pages les plus vues
- Bande passante utilisée

Allez dans **Analytics** pour voir vos statistiques.

---

## 🎉 Félicitations !

Votre hôtel a maintenant un site web professionnel :

✅ En ligne 24/7
✅ Sécurisé
✅ Rapide
✅ Moderne
✅ Gratuit

**Bon succès avec votre hôtel ! 🏨**

---

## 📞 Contacts Techniques

Pour toute question sur le site :
- Email : support@lezardbleu-hotel.bi
- Téléphone : +257 22 00 00 00

---

*Guide créé pour Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi*
*Dernière mise à jour : 2024*
