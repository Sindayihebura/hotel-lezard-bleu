<?php

declare(strict_types=1);

/**
 * Routeur API REST v1 — Hôtel Le Lézard Bleu & Spa
 * Toutes les requêtes /api/v1/* arrivent ici via .htaccess
 *
 * Format des réponses :
 * {
 *   "success": true|false,
 *   "data": {...},
 *   "error": {"code": "...", "message": "..."},
 *   "meta": {"request_id": "..."}
 * }
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────
require_once dirname(__DIR__, 3) . '/config/bootstrap.php';
require_once dirname(__DIR__, 3) . '/config/database.php';

use App\Http\Request;
use App\Http\Response;
use App\Security\Logger;

header('Content-Type: application/json; charset=utf-8');
// Désactiver le cache sur les endpoints API
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// ── CORS limité (même domaine en production) ──────────────────────────────
$allowedOrigin = env('APP_URL', '');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($allowedOrigin !== '' && $origin === $allowedOrigin) {
    header('Access-Control-Allow-Origin: ' . $allowedOrigin);
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, Authorization');
    header('Access-Control-Allow-Credentials: true');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Extraction de la route ────────────────────────────────────────────────
$route  = trim($_GET['route'] ?? '', '/');
$method = strtoupper($_SERVER['REQUEST_METHOD']);

$pdo    = getDB();
$logger = new Logger($pdo);
$req    = Request::fromGlobals();

// ── Rate limiting global API ──────────────────────────────────────────────
if ($pdo !== null) {
    $rl = new \App\Security\RateLimiter($pdo);
    $rlKey = 'api:' . client_ip();
    if ($rl->tooManyAttempts($rlKey, 120, 60)) {
        http_response_code(429);
        echo json_error('RATE_LIMIT_EXCEEDED', 'Trop de requêtes. Réessayez dans une minute.');
        exit;
    }
    $rl->hit($rlKey, 60);
}

// ── Segments de la route ─────────────────────────────────────────────────
// Ex: "rooms/5" → ['rooms', '5']
$segments = $route !== '' ? explode('/', $route) : [];
$resource = $segments[0] ?? '';
$id       = isset($segments[1]) ? (int) $segments[1] : null;
$action   = $segments[2] ?? null;

// ── Routage ───────────────────────────────────────────────────────────────
try {
    match (true) {

        // ── GET /rooms ────────────────────────────────────────────────────
        $resource === 'rooms' && $id === null && $method === 'GET' => (function () use ($pdo) {
            require_once __DIR__ . '/handlers/rooms.php';
            handleGetRooms($pdo);
        })(),

        // ── GET /rooms/{id} ───────────────────────────────────────────────
        $resource === 'rooms' && $id !== null && $method === 'GET' => (function () use ($pdo, $id) {
            require_once __DIR__ . '/handlers/rooms.php';
            handleGetRoom($pdo, $id);
        })(),

        // ── GET /availability ─────────────────────────────────────────────
        $resource === 'availability' && $method === 'GET' => (function () use ($pdo, $req) {
            require_once __DIR__ . '/handlers/availability.php';
            handleAvailability($pdo, $req);
        })(),

        // ── POST /booking-quotes ──────────────────────────────────────────
        $resource === 'booking-quotes' && $method === 'POST' => (function () use ($pdo, $req) {
            require_once __DIR__ . '/handlers/booking_quotes.php';
            handleBookingQuote($pdo, $req);
        })(),

        // ── POST /bookings ────────────────────────────────────────────────
        $resource === 'bookings' && $id === null && $method === 'POST' => (function () use ($pdo, $req) {
            require_once __DIR__ . '/handlers/bookings.php';
            handleCreateBooking($pdo, $req);
        })(),

        // ── GET /bookings/{reference} ─────────────────────────────────────
        $resource === 'bookings' && $id !== null && $method === 'GET' => (function () use ($pdo, $segments, $req) {
            require_once __DIR__ . '/handlers/bookings.php';
            handleGetBooking($pdo, $segments[1], $req);
        })(),

        // ── POST /bookings/{reference}/cancel ─────────────────────────────
        $resource === 'bookings' && $action === 'cancel' && $method === 'POST' => (function () use ($pdo, $segments, $req) {
            require_once __DIR__ . '/handlers/bookings.php';
            handleCancelBooking($pdo, $segments[1], $req);
        })(),

        // ── GET /me ───────────────────────────────────────────────────────
        $resource === 'me' && $id === null && $method === 'GET' => (function () use ($pdo, $req) {
            require_once __DIR__ . '/handlers/me.php';
            handleGetMe($pdo, $req);
        })(),

        // ── GET /me/bookings ──────────────────────────────────────────────
        $resource === 'me' && ($segments[1] ?? '') === 'bookings' && $method === 'GET' => (function () use ($pdo, $req) {
            require_once __DIR__ . '/handlers/me.php';
            handleGetMyBookings($pdo, $req);
        })(),

        // ── GET /me/invoices ──────────────────────────────────────────────
        $resource === 'me' && ($segments[1] ?? '') === 'invoices' && $method === 'GET' => (function () use ($pdo, $req) {
            require_once __DIR__ . '/handlers/me.php';
            handleGetMyInvoices($pdo, $req);
        })(),

        // ── PATCH /me/profile ─────────────────────────────────────────────
        $resource === 'me' && ($segments[1] ?? '') === 'profile' && $method === 'PATCH' => (function () use ($pdo, $req) {
            require_once __DIR__ . '/handlers/me.php';
            handleUpdateProfile($pdo, $req);
        })(),

        // ── Admin endpoints ───────────────────────────────────────────────
        $resource === 'admin' => (function () use ($pdo, $req, $segments, $method) {
            require_once __DIR__ . '/handlers/admin.php';
            handleAdminRequest($pdo, $req, $segments, $method);
        })(),

        // ── POST /payments/initiate ───────────────────────────────────────
        $resource === 'payments' && ($segments[1] ?? '') === 'initiate' && $method === 'POST' => (function () use ($pdo, $req) {
            require_once __DIR__ . '/handlers/payments.php';
            handleInitiatePayment($pdo, $req);
        })(),

        // ── GET /payments/{id} ────────────────────────────────────────────
        $resource === 'payments' && $id !== null && $action === null && $method === 'GET' => (function () use ($pdo, $id, $req) {
            require_once __DIR__ . '/handlers/payments.php';
            handleGetPayment($pdo, $id, $req);
        })(),

        // ── Webhooks paiement ─────────────────────────────────────────────
        $resource === 'payments' && ($segments[1] ?? '') === 'webhooks' => (function () use ($pdo, $req, $segments) {
            require_once __DIR__ . '/handlers/webhooks.php';
            handleWebhook($pdo, $req, $segments[2] ?? '');
        })(),

        // ── POST /payments/{id}/refund ────────────────────────────────────
        $resource === 'payments' && $action === 'refund' && $method === 'POST' => (function () use ($pdo, $id, $req) {
            require_once __DIR__ . '/handlers/payments.php';
            handleRefundPayment($pdo, $id, $req);
        })(),

        // ── 404 — Route inconnue ──────────────────────────────────────────
        default => (function () {
            echo json_error('NOT_FOUND', 'Endpoint introuvable.', 404);
        })(),
    };
} catch (\Throwable $e) {
    // En production : ne jamais exposer les détails
    error_log('[API] Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    echo json_error('SERVER_ERROR', 'Erreur interne du serveur.', 500);
}
