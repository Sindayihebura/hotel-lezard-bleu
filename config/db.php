<?php
/**
 * config/db.php — Couche de compatibilité legacy
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 *
 * Ce fichier est conservé pour compatibilité avec les pages PHP racine
 * (index.php, chambres.php, reservation.php…) qui l'incluent via header.php.
 * La logique réelle est dans config/database.php (PSR-4, .env).
 *
 * NE PAS mettre de secrets en dur ici. Tout passe par .env.
 */

// ── Bootstrap complet si pas déjà chargé ─────────────────────────────────
if (!function_exists('env')) {
    require_once __DIR__ . '/bootstrap.php';
}
if (!function_exists('getDB') || getDB() === null) {
    require_once __DIR__ . '/database.php';
}

// ── Gestion devise via GET (legacy endpoint) ──────────────────────────────
// main.js appelle /config/db.php?set_currency=USD
if (isset($_GET['set_currency'])) {
    $c = strtoupper(trim((string)$_GET['set_currency']));
    $supported = explode(',', env('SUPPORTED_CURRENCIES', 'BIF,USD'));
    if (in_array($c, $supported, true)) {
        $_SESSION['currency'] = $c;
    }
    // Répondre JSON si appelé en AJAX, sinon rien
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json');
        echo json_encode(['currency' => $_SESSION['currency']]);
        exit;
    }
}

// ── Garantir que les fonctions globales existent ──────────────────────────
// (Définies dans app/helpers.php via bootstrap, mais on ajoute des guards)
if (!function_exists('format_currency')) {
    function format_currency($amount_bif, $targetCurrency = null, $rate = null) {
        if ($targetCurrency === null) $targetCurrency = $_SESSION['currency'] ?? 'BIF';
        if ($rate === null)          $rate = get_exchange_rate();
        if ($targetCurrency === 'USD') {
            $usd = $rate > 0 ? round($amount_bif / $rate, 2) : 0;
            return '$ ' . number_format($usd, 2, '.', ' ');
        }
        return number_format((int)$amount_bif, 0, ',', ' ') . ' BIF';
    }
}
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        if (is_array($data)) return array_map('sanitize_input', $data);
        return htmlspecialchars(trim(stripslashes((string)$data)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
    }
}
if (!function_exists('format_date_fr')) {
    function format_date_fr($dateStr) {
        if (!$dateStr) return '';
        $ts = strtotime($dateStr);
        if (!$ts) return $dateStr;
        $mois = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        return date('j',$ts).' '.$mois[date('n',$ts)-1].' '.date('Y',$ts);
    }
}
