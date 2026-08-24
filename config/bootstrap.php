<?php

declare(strict_types=1);

/**
 * Bootstrap — Hôtel Le Lézard Bleu & Spa
 * Point d'entrée unique pour toutes les configurations applicatives.
 *
 * Ordre d'initialisation :
 * 1. Chargement .env
 * 2. Fuseau horaire
 * 3. Gestion des erreurs
 * 4. Headers de sécurité
 * 5. Session sécurisée
 * 6. Autoload Composer
 */

// ── Autoload Composer (PSR-4) ──────────────────────────────────────────────
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// ── Chargement .env ────────────────────────────────────────────────────────
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignorer commentaires et lignes vides
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        // Ne pas écraser une variable déjà définie (ex: depuis l'environnement système)
        if (!isset($_ENV[$key]) && !array_key_exists($key, $_SERVER)) {
            putenv("$key=$value");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// ── Fuseau horaire ─────────────────────────────────────────────────────────
date_default_timezone_set(env('APP_TIMEZONE', 'Africa/Bujumbura'));

// ── Rapport d'erreurs ──────────────────────────────────────────────────────
$isDebug = filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN);
$isProduction = (env('APP_ENV', 'development') === 'production');

if ($isProduction || !$isDebug) {
    // Production : ne jamais afficher les erreurs au navigateur
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    ini_set('log_errors', '1');
    ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_errors.log');
} else {
    // Développement uniquement
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// ── Headers de sécurité HTTP (envoyés avant tout output) ──────────────────
if (!headers_sent()) {
    // Forcer HTTPS
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    // Prévenir le sniffing de type MIME
    header('X-Content-Type-Options: nosniff');
    // Empêcher le clickjacking
    header('X-Frame-Options: DENY');
    // Politique de référent
    header('Referrer-Policy: strict-origin-when-cross-origin');
    // Désactiver les fonctionnalités non nécessaires
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    // CSP — adapté selon les besoins (Google Fonts, assets locaux)
    $cspNonce = base64_encode(random_bytes(16));
    define('CSP_NONCE', $cspNonce);
    header(
        "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'nonce-{$cspNonce}'; " .
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
        "font-src 'self' https://fonts.gstatic.com; " .
        "img-src 'self' data: https:; " .
        "connect-src 'self'; " .
        "frame-ancestors 'none'; " .
        "base-uri 'self'; " .
        "form-action 'self';"
    );
    // Supprimer le header PHP exposé
    header_remove('X-Powered-By');
}

// ── Session sécurisée ──────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $sessionName    = env('SESSION_NAME', '__Host-lezard_session');
    $sessionSecure  = filter_var(env('SESSION_SECURE', 'true'), FILTER_VALIDATE_BOOLEAN);
    $sessionLifetime = (int) env('SESSION_LIFETIME', '7200');

    session_name($sessionName);
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie (expire à la fermeture du navigateur)
        'path'     => '/',
        'domain'   => '',   // Vide = domaine courant uniquement
        'secure'   => $sessionSecure,
        'httponly' => true,
        'samesite' => env('SESSION_SAMESITE', 'Lax'),
    ]);
    // Utiliser uniquement les cookies pour l'ID de session (jamais dans l'URL)
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.gc_maxlifetime', (string) $sessionLifetime);

    session_start();

    // Régénérer si nouvelle session (protection fixation)
    if (empty($_SESSION['_initiated'])) {
        session_regenerate_id(true);
        $_SESSION['_initiated'] = true;
        $_SESSION['_created_at'] = time();
    }

    // Expiration de session après inactivité
    if (isset($_SESSION['_last_activity'])) {
        if ((time() - $_SESSION['_last_activity']) > $sessionLifetime) {
            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id(true);
            $_SESSION['_initiated']  = true;
            $_SESSION['_created_at'] = time();
        }
    }
    $_SESSION['_last_activity'] = time();
}

// ── Devise active ──────────────────────────────────────────────────────────
if (!isset($_SESSION['currency'])) {
    $_SESSION['currency'] = env('CURRENCY_DEFAULT', 'BIF');
}
// Changement de devise via GET (sans redirection de page)
if (isset($_GET['set_currency'])) {
    $c = strtoupper(trim((string) $_GET['set_currency']));
    $supported = explode(',', env('SUPPORTED_CURRENCIES', 'BIF,USD'));
    if (in_array($c, $supported, true)) {
        $_SESSION['currency'] = $c;
    }
}

// ── Langue active ──────────────────────────────────────────────────────────
if (!isset($_SESSION['locale'])) {
    $_SESSION['locale'] = env('DEFAULT_LOCALE', 'fr');
}

// ── Helpers globaux ────────────────────────────────────────────────────────

/**
 * Lire une variable d'environnement avec valeur par défaut.
 */
function env(string $key, string $default = ''): string
{
    $val = getenv($key);
    if ($val === false) {
        return $_ENV[$key] ?? $default;
    }
    return $val;
}

/**
 * Chemins de base de l'application.
 */
function base_path(string $path = ''): string
{
    $base = dirname(__DIR__);
    return $path !== '' ? $base . DIRECTORY_SEPARATOR . ltrim($path, '/\\') : $base;
}

function storage_path(string $path = ''): string
{
    return base_path('storage' . ($path !== '' ? '/' . $path : ''));
}

function resource_path(string $path = ''): string
{
    return base_path('resources' . ($path !== '' ? '/' . $path : ''));
}
