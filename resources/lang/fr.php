<?php

declare(strict_types=1);

/**
 * Traductions françaises — Hôtel Le Lézard Bleu & Spa
 * Langue par défaut (fr)
 */

return [
    // ── Navigation ────────────────────────────────────────────────────────
    'nav.home'          => 'Accueil',
    'nav.presentation'  => 'Présentation',
    'nav.rooms'         => 'Suites & Villas',
    'nav.reservation'   => 'Réservation',
    'nav.gallery'       => 'Galerie',
    'nav.services'      => 'Services & Resto',
    'nav.conferences'   => 'Conférences',
    'nav.offers'        => 'Offres',
    'nav.contact'       => 'Contact',
    'nav.login'         => 'Connexion',
    'nav.register'      => 'Inscription',
    'nav.my_account'    => 'Mon Compte',
    'nav.logout'        => 'Déconnexion',

    // ── Général ───────────────────────────────────────────────────────────
    'app.name'          => 'Hôtel Le Lézard Bleu & Spa',
    'app.tagline'       => "L'Excellence Hôtelière au Bord du Lac Tanganyika",
    'app.location'      => 'Bujumbura, Burundi',
    'btn.book_now'      => 'Réserver Maintenant',
    'btn.discover'      => "Découvrir l'Hôtel",
    'btn.see_all'       => 'Voir Tout',
    'btn.send'          => 'Envoyer',
    'btn.confirm'       => 'Confirmer',
    'btn.cancel'        => 'Annuler',
    'btn.back'          => 'Retour',
    'btn.print'         => 'Imprimer',
    'btn.download'      => 'Télécharger',

    // ── Réservation ───────────────────────────────────────────────────────
    'booking.checkin'       => "Date d'arrivée",
    'booking.checkout'      => 'Date de départ',
    'booking.guests'        => 'Personnes',
    'booking.nights'        => 'Nuit(s)',
    'booking.rooms'         => 'Chambres disponibles',
    'booking.select_room'   => 'Sélectionner cette suite',
    'booking.selected'      => 'Sélectionnée ✓',
    'booking.per_night'     => 'par nuitée',
    'booking.total'         => 'Montant total',
    'booking.reference'     => 'Référence de réservation',
    'booking.confirmed'     => 'Réservation Confirmée !',
    'booking.cancellation'  => "Annulation gratuite jusqu'à :hours h avant l'arrivée",
    'booking.guest_name'    => 'Nom et Prénom',
    'booking.email'         => 'Adresse E-mail',
    'booking.phone'         => 'Téléphone',
    'booking.payment_method'=> 'Mode de paiement',

    // ── Paiements ─────────────────────────────────────────────────────────
    'payment.cash_bif'      => 'Espèces BIF (à l\'arrivée)',
    'payment.cash_usd'      => 'Espèces USD (à l\'arrivée)',
    'payment.lumicash'      => 'Lumicash (Lumitel Mobile Money)',
    'payment.ecocash'       => 'EcoCash / PesaFlash (Econet Leo)',
    'payment.bank_local'    => 'Virement Bancaire Burundi',
    'payment.visa'          => 'Carte VISA / MasterCard',
    'payment.paypal'        => 'PayPal',
    'payment.mobile_phone'  => 'Numéro Mobile Money',
    'payment.bank_select'   => 'Choisir votre banque',
    'payment.rate_used'     => 'Taux utilisé : 1 USD = :rate BIF',
    'payment.pending'       => 'En attente de paiement',
    'payment.successful'    => 'Paiement confirmé',
    'payment.failed'        => 'Paiement échoué',
    'payment.expired'       => 'Paiement expiré',

    // ── Services ──────────────────────────────────────────────────────────
    'services.spa'          => 'Spa & Bien-être',
    'services.restaurant'   => 'Restaurant Gastronomique',
    'services.breakfast'    => 'Petit-déjeuner',
    'services.transfer'     => 'Transfert Aéroport Ndadaye',
    'services.pool'         => 'Piscine & Plage Lac Tanganyika',
    'services.conference'   => 'Salles de Conférence',
    'services.excursion'    => 'Excursions sur le Lac',
    'services.wedding'      => 'Mariages & Événements',
    'services.per_person'   => 'par personne',
    'services.per_session'  => 'par séance',
    'services.per_trip'     => 'par trajet',

    // ── Erreurs ───────────────────────────────────────────────────────────
    'error.required'        => 'Ce champ est obligatoire.',
    'error.invalid_email'   => 'Adresse e-mail invalide.',
    'error.invalid_phone'   => 'Numéro de téléphone invalide.',
    'error.invalid_date'    => 'Date invalide.',
    'error.dates_order'     => "La date de départ doit être après l'arrivée.",
    'error.room_unavailable'=> "Cette chambre n'est plus disponible pour ces dates.",
    'error.booking_failed'  => 'Erreur lors de la réservation. Veuillez réessayer.',
    'error.payment_failed'  => 'Le paiement a échoué. Veuillez vérifier vos informations.',
    'error.session_expired' => 'Votre session a expiré. Veuillez vous reconnecter.',
    'error.access_denied'   => 'Accès refusé.',
    'error.not_found'       => 'Page introuvable.',
    'error.server_error'    => 'Erreur interne. Veuillez réessayer.',
    'error.rate_limit'      => 'Trop de tentatives. Réessayez dans :minutes minutes.',
    'error.csrf'            => 'Jeton de sécurité invalide. Rechargez la page.',

    // ── Authentification ──────────────────────────────────────────────────
    'auth.login'            => 'Connexion',
    'auth.register'         => 'Créer un compte',
    'auth.logout'           => 'Déconnexion',
    'auth.email'            => 'Adresse e-mail',
    'auth.password'         => 'Mot de passe',
    'auth.password_confirm' => 'Confirmer le mot de passe',
    'auth.forgot_password'  => 'Mot de passe oublié ?',
    'auth.reset_password'   => 'Réinitialiser le mot de passe',
    'auth.remember_me'      => 'Se souvenir de moi',
    'auth.login_success'    => 'Connexion réussie. Bienvenue !',
    'auth.login_failed'     => 'Email ou mot de passe incorrect.',
    'auth.account_locked'   => 'Compte temporairement bloqué. Réessayez dans :minutes minutes.',
    'auth.email_verify_sent'=> 'Un lien de vérification a été envoyé à votre adresse e-mail.',
    'auth.email_verified'   => 'Votre adresse e-mail a été vérifiée.',
    'auth.register_success' => 'Compte créé avec succès !',
    'auth.password_changed' => 'Mot de passe modifié avec succès.',
    'auth.password_min'     => 'Le mot de passe doit contenir au moins :min caractères.',
    'auth.password_mismatch'=> 'Les mots de passe ne correspondent pas.',

    // ── Compte client ─────────────────────────────────────────────────────
    'account.my_bookings'   => 'Mes Réservations',
    'account.my_invoices'   => 'Mes Factures',
    'account.my_profile'    => 'Mon Profil',
    'account.my_preferences'=> 'Mes Préférences',
    'account.no_bookings'   => "Vous n'avez pas encore de réservation.",

    // ── Devise ────────────────────────────────────────────────────────────
    'currency.bif'          => 'Francs Burundais',
    'currency.usd'          => 'Dollars Américains',
    'currency.switch'       => 'Changer de devise',
    'currency.rate'         => 'Taux : 1 USD = :rate BIF',
];
