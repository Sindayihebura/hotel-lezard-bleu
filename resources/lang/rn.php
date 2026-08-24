<?php

declare(strict_types=1);

/**
 * Traductions Kirundi — Hôtel Le Lézard Bleu & Spa
 * Langue : Kirundi (rn) — Langue nationale du Burundi
 *
 * Note : Traductions de référence à valider par un locuteur natif
 * avant mise en production.
 */

return [
    // ── Navigation ────────────────────────────────────────────────────────
    'nav.home'          => 'Ahabanza',
    'nav.presentation'  => 'Ivyerekeye',
    'nav.rooms'         => 'Amahewa & Ivillas',
    'nav.reservation'   => 'Bukira',
    'nav.gallery'       => 'Amafoto',
    'nav.services'      => 'Serivisi',
    'nav.conferences'   => 'Inama',
    'nav.offers'        => 'Amategeko',
    'nav.contact'       => 'Tumanahane',
    'nav.login'         => 'Injira',
    'nav.register'      => 'Iyandikishe',
    'nav.my_account'    => 'Konti Yanje',
    'nav.logout'        => 'Sohoka',

    // ── Général ───────────────────────────────────────────────────────────
    'app.name'          => 'Hôtel Le Lézard Bleu & Spa',
    'app.tagline'       => 'Ubuziranenge bw\'Ubutunzi ku Nkengero ya Lac Tanganyika',
    'app.location'      => 'Bujumbura, Uburundi',
    'btn.book_now'      => 'Buka Ubu',
    'btn.discover'      => 'Menya Hoteli',
    'btn.see_all'       => 'Raba Vyose',
    'btn.send'          => 'Rungika',
    'btn.confirm'       => 'Emeza',
    'btn.cancel'        => 'Siga',
    'btn.back'          => 'Garuka',
    'btn.print'         => 'Capa',
    'btn.download'      => 'Manura',

    // ── Réservation ───────────────────────────────────────────────────────
    'booking.checkin'       => 'Itariki y\'Inyuriro',
    'booking.checkout'      => 'Itariki y\'Isohokero',
    'booking.guests'        => 'Abantu',
    'booking.nights'        => 'Ijoro/Amajoro',
    'booking.rooms'         => 'Amahewa Aboneka',
    'booking.select_room'   => 'Toranira iki Cibo',
    'booking.selected'      => 'Toranye ✓',
    'booking.per_night'     => 'ku joro',
    'booking.total'         => 'Igiterane Cose',
    'booking.reference'     => 'Inomero yo Kubuka',
    'booking.confirmed'     => 'Kubuka Kwemejwe!',
    'booking.cancellation'  => 'Gusiga bidashora ku masaha :hours imbere yo kuza',
    'booking.guest_name'    => 'Izina n\'Irindi zina',
    'booking.email'         => 'Aderesi ya E-mail',
    'booking.phone'         => 'Nimero ya Telefone',
    'booking.payment_method'=> 'Uburyo bwo Kwishura',

    // ── Paiements ─────────────────────────────────────────────────────────
    'payment.cash_bif'      => 'Amafaranga BIF (ku kwinjira)',
    'payment.cash_usd'      => 'Amafaranga USD (ku kwinjira)',
    'payment.lumicash'      => 'Lumicash (Lumitel)',
    'payment.ecocash'       => 'EcoCash / PesaFlash (Econet Leo)',
    'payment.bank_local'    => 'Banki yo mu Burundi',
    'payment.visa'          => 'Ikarita VISA / MasterCard',
    'payment.paypal'        => 'PayPal',
    'payment.mobile_phone'  => 'Nimero ya Mobile Money',
    'payment.bank_select'   => 'Toranira Banki yawe',
    'payment.rate_used'     => 'Igipimo: USD 1 = BIF :rate',
    'payment.pending'       => 'Igengeshumwa ry\'Ubwishure',
    'payment.successful'    => 'Ubwishure Bwemejwe',
    'payment.failed'        => 'Ubwishure Bwananiwe',
    'payment.expired'       => 'Ubwishure Bwarangiye',

    // ── Services ──────────────────────────────────────────────────────────
    'services.spa'          => 'Spa & Ubuzima Bwiza',
    'services.restaurant'   => 'Restaurant',
    'services.breakfast'    => 'Ifunguro rya Mugitondo',
    'services.transfer'     => 'Gutwara ku Kiraro ca Ndadaye',
    'services.pool'         => 'Akabande & Inkengero ya Lac Tanganyika',
    'services.conference'   => 'Inzu z\'Inama',
    'services.excursion'    => 'Ingendo kuri Lac',
    'services.wedding'      => 'Imiyago & Ibirori',
    'services.per_person'   => 'ku muntu',
    'services.per_session'  => 'ku seance',
    'services.per_trip'     => 'ku rugendo',

    // ── Erreurs ───────────────────────────────────────────────────────────
    'error.required'        => 'Iri soko rirabuza.',
    'error.invalid_email'   => 'Aderesi ya e-mail ntiyemera.',
    'error.invalid_phone'   => 'Nimero ya telefone ntiyemera.',
    'error.invalid_date'    => 'Itariki ntiyemera.',
    'error.dates_order'     => 'Itariki y\'isohokero igomba kuba nyuma y\'inyuriro.',
    'error.room_unavailable'=> 'Iki cibo ntikiboneka ku matariki ayo.',
    'error.booking_failed'  => 'Kubuka kwananiwe. Gerageza ukundi.',
    'error.payment_failed'  => 'Ubwishure bwananiwe. Reba amakuru yawe.',
    'error.session_expired' => 'Igihe cawe casozwe. Injira ukundi.',
    'error.access_denied'   => 'Ntibemerewe kwinjira.',
    'error.not_found'       => 'Urupapuro ntiruboneka.',
    'error.server_error'    => 'Ikosa ryo mu ntara. Gerageza ukundi.',
    'error.rate_limit'      => 'Ugerageje incuro nyinshi. Subira mu minuta :minutes.',
    'error.csrf'            => 'Ikimenyetso c\'umutekano ntiyemera. Subira urupapuro.',

    // ── Authentification ──────────────────────────────────────────────────
    'auth.login'            => 'Injira',
    'auth.register'         => 'Fungura Konti',
    'auth.logout'           => 'Sohoka',
    'auth.email'            => 'Aderesi ya E-mail',
    'auth.password'         => 'Ijambo ry\'Ibanga',
    'auth.password_confirm' => 'Emeza Ijambo ry\'Ibanga',
    'auth.forgot_password'  => 'Wibagiye ijambo ry\'ibanga?',
    'auth.reset_password'   => 'Subura Ijambo ry\'Ibanga',
    'auth.remember_me'      => 'Ndibutse',
    'auth.login_success'    => 'Winjiye neza. Murakaza neza!',
    'auth.login_failed'     => 'Email canke ijambo ry\'ibanga si ryo.',
    'auth.account_locked'   => 'Konti yarahinzwe. Gerageza mu minuta :minutes.',
    'auth.email_verify_sent'=> 'Inyandiko yo kwemeza yatumwe kuri aderesi yawe.',
    'auth.email_verified'   => 'Aderesi yawe ya e-mail yemejwe.',
    'auth.register_success' => 'Konti ifunguwe neza!',
    'auth.password_changed' => 'Ijambo ry\'ibanga ryahindutse neza.',
    'auth.password_min'     => 'Ijambo ry\'ibanga rigomba kuba nibura inyuguti :min.',
    'auth.password_mismatch'=> 'Amagambo y\'ibanga ntahukanye.',

    // ── Compte client ─────────────────────────────────────────────────────
    'account.my_bookings'   => 'Ibukwa Ryanje',
    'account.my_invoices'   => 'Inyemeza Zacu',
    'account.my_profile'    => 'Umwirondoro Wanje',
    'account.my_preferences'=> 'Ivyo Nkunda',
    'account.no_bookings'   => 'Nta kubuka ufise ubu.',

    // ── Devise ────────────────────────────────────────────────────────────
    'currency.bif'          => 'Amafaranga ya Burundi (BIF)',
    'currency.usd'          => 'Amadolari y\'Amerika (USD)',
    'currency.switch'       => 'Hindura Amafaranga',
    'currency.rate'         => 'Igipimo: USD 1 = BIF :rate',
];
