<?php

declare(strict_types=1);

/**
 * Fonctions helpers globales — Hôtel Le Lézard Bleu & Spa
 * Compatibilité ascendante + utilitaires généraux.
 */

// ── Sécurité & Validation ─────────────────────────────────────────────────

/**
 * Nettoyer une chaîne pour l'affichage HTML (XSS).
 * À utiliser pour toute sortie dans le HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Alias de e() — compatibilité avec l'ancien sanitize_input().
 * Note : ne supprime PAS les balises HTML — utilisé pour l'échappement de sortie.
 */
function sanitize_input(mixed $data): string|array
{
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    return e(trim((string) $data));
}

/**
 * Générer un token CSRF 64 hex chars, stocké en session.
 */
function generate_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier le token CSRF (résistant aux timing attacks).
 */
function verify_csrf_token(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Vérifier CSRF depuis POST, envoyer 403 si invalide.
 */
function require_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verify_csrf_token($token)) {
        http_response_code(403);
        if (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'data'    => null,
                'error'   => ['code' => 'CSRF_INVALID', 'message' => 'Jeton de sécurité invalide.'],
                'meta'    => ['request_id' => request_id()],
            ]);
        } else {
            echo '<p>Erreur de sécurité. Veuillez recharger la page.</p>';
        }
        exit;
    }
}

// ── Requête ───────────────────────────────────────────────────────────────

/**
 * ID unique de requête (pour les logs et les réponses API).
 */
function request_id(): string
{
    static $id = null;
    if ($id === null) {
        $id = bin2hex(random_bytes(8));
    }
    return $id;
}

/**
 * Obtenir l'IP réelle du client (avec protection contre les headers forgés).
 */
function client_ip(): string
{
    // Sur proxy de confiance seulement, lire X-Forwarded-For
    // En l'absence de configuration proxy, on utilise REMOTE_ADDR
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Filtrer et valider
    $filtered = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6);
    return $filtered ?: '0.0.0.0';
}

// ── Devise et formatage ────────────────────────────────────────────────────

/**
 * Obtenir le taux BIF/USD depuis la DB ou la valeur par défaut.
 */
function get_exchange_rate(): float
{
    static $rate = null;
    if ($rate !== null) {
        return $rate;
    }
    $pdo = getDB();
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare("SELECT valeur FROM parametres WHERE cle = 'taux_usd_bif' LIMIT 1");
            $stmt->execute();
            $val = $stmt->fetchColumn();
            if ($val !== false && is_numeric($val) && (float)$val > 0) {
                $rate = (float) $val;
                return $rate;
            }
        } catch (\PDOException $e) {
            error_log('[helpers] get_exchange_rate: ' . $e->getMessage());
        }
    }
    $rate = (float) env('DEFAULT_EXCHANGE_RATE', '6000');
    return $rate;
}

/**
 * Convertir BIF → USD.
 * Stockage : BIF en BIGINT (centimes non utilisés pour BIF).
 * Retourne int (cents USD × 100).
 */
function convert_bif_to_usd_cents(int $amountBif, float $rate): int
{
    if ($rate <= 0) {
        return 0;
    }
    return (int) round(($amountBif / $rate) * 100);
}

/**
 * Convertir USD cents → BIF.
 */
function convert_usd_cents_to_bif(int $usdCents, float $rate): int
{
    return (int) round(($usdCents / 100) * $rate);
}

/**
 * Compatibilité ancienne : convert_bif_to_usd (float → float).
 */
function convert_bif_to_usd(float $amountBif, ?float $rate = null): float
{
    if ($rate === null) {
        $rate = get_exchange_rate();
    }
    return $rate > 0 ? round($amountBif / $rate, 2) : 0.0;
}

/**
 * Compatibilité ancienne : convert_usd_to_bif.
 */
function convert_usd_to_bif(float $amountUsd, ?float $rate = null): float
{
    if ($rate === null) {
        $rate = get_exchange_rate();
    }
    return round($amountUsd * $rate, 2);
}

/**
 * Formater un montant BIF pour l'affichage.
 * Utilise PHP intl si disponible, sinon fallback.
 */
function format_bif(int $amountBif): string
{
    if (extension_loaded('intl')) {
        $locale = $_SESSION['locale'] ?? 'fr';
        $intlLocale = match($locale) {
            'en' => 'en_US',
            'rn' => 'fr_BI',
            default => 'fr_BI',
        };
        $fmt = new \NumberFormatter($intlLocale, \NumberFormatter::DECIMAL);
        $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        return $fmt->format($amountBif) . ' BIF';
    }
    return number_format($amountBif, 0, ',', ' ') . ' BIF';
}

/**
 * Formater un montant USD cents pour l'affichage.
 */
function format_usd(int $usdCents): string
{
    $amount = $usdCents / 100;
    if (extension_loaded('intl')) {
        $fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($amount, 'USD');
    }
    return '$ ' . number_format($amount, 2, '.', ',');
}

/**
 * Formater selon la devise active en session.
 * Compatibilité avec l'ancien format_currency().
 */
function format_currency(float $amountBif, ?string $targetCurrency = null, ?float $rate = null): string
{
    if ($targetCurrency === null) {
        $targetCurrency = $_SESSION['currency'] ?? 'BIF';
    }
    if ($rate === null) {
        $rate = get_exchange_rate();
    }
    if ($targetCurrency === 'USD') {
        $usdCents = convert_bif_to_usd_cents((int) round($amountBif), $rate);
        return format_usd($usdCents);
    }
    return format_bif((int) round($amountBif));
}

// ── Dates ─────────────────────────────────────────────────────────────────

/**
 * Formater une date en français.
 */
function format_date_fr(string $dateStr): string
{
    if ($dateStr === '') {
        return '';
    }
    $ts = strtotime($dateStr);
    if ($ts === false) {
        return $dateStr;
    }
    $mois = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    return date('j', $ts) . ' ' . $mois[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/**
 * Calcul du nombre de nuits entre deux dates.
 */
function calculate_nights(string $checkin, string $checkout): int
{
    $in  = new \DateTimeImmutable($checkin);
    $out = new \DateTimeImmutable($checkout);
    $diff = $in->diff($out);
    return max(1, (int) $diff->days);
}

// ── Réponses JSON standardisées (API) ────────────────────────────────────

/**
 * Construire une réponse JSON succès standardisée.
 */
function json_success(mixed $data = null, array $meta = []): string
{
    return json_encode([
        'success' => true,
        'data'    => $data,
        'error'   => null,
        'meta'    => array_merge(['request_id' => request_id()], $meta),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Construire une réponse JSON erreur standardisée.
 */
function json_error(string $code, string $message, int $httpStatus = 400, array $meta = []): string
{
    http_response_code($httpStatus);
    return json_encode([
        'success' => false,
        'data'    => null,
        'error'   => ['code' => $code, 'message' => $message],
        'meta'    => array_merge(['request_id' => request_id()], $meta),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/**
 * Envoyer une réponse JSON et terminer l'exécution.
 */
function send_json(string $payload): never
{
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo $payload;
    exit;
}

// ── Redirection sécurisée ─────────────────────────────────────────────────

/**
 * Redirection sécurisée (évite les open redirects).
 */
function safe_redirect(string $url): never
{
    // Autoriser uniquement les URLs relatives ou sur le même domaine
    if (!preg_match('#^/#', $url)) {
        $allowed = env('APP_URL', '');
        if ($allowed !== '' && !str_starts_with($url, $allowed)) {
            $url = '/';
        }
    }
    header('Location: ' . $url, true, 302);
    exit;
}

// ── Compatibilité DB ──────────────────────────────────────────────────────
// getDB() est défini dans config/database.php
