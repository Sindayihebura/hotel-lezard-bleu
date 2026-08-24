<?php
declare(strict_types=1);

use App\Repositories\CustomerRepository;
use App\Repositories\BookingRepository;
use App\Http\Request;

function requireCustomerAuth(): void
{
    if (empty($_SESSION['customer_auth'])) {
        echo json_error('UNAUTHORIZED','Authentification requise.',401);
        exit;
    }
}

/** GET /api/v1/me */
function handleGetMe(?PDO $pdo, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }
    requireCustomerAuth();
    $repo = new CustomerRepository($pdo);
    $user = $repo->findByIdSafe((int)$_SESSION['customer_id']);
    if (!$user) { echo json_error('NOT_FOUND','Profil introuvable.',404); return; }
    echo json_success($user);
}

/** GET /api/v1/me/bookings */
function handleGetMyBookings(?PDO $pdo, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }
    requireCustomerAuth();
    $page   = max(1, (int)$req->query('page','1'));
    $limit  = 10;
    $offset = ($page - 1) * $limit;
    $repo   = new BookingRepository($pdo);
    $items  = $repo->findByCustomer((int)$_SESSION['customer_id'], $limit, $offset);
    // Retirer champs internes
    foreach ($items as &$b) { unset($b['notes_admin'],$b['price_snapshot_json']); }
    echo json_success($items, ['page' => $page, 'per_page' => $limit]);
}

/** GET /api/v1/me/invoices */
function handleGetMyInvoices(?PDO $pdo, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }
    requireCustomerAuth();
    $stmt = $pdo->prepare("
        SELECT i.* FROM invoices i
        JOIN bookings b ON b.id = i.booking_id
        WHERE b.customer_id = :cid
        ORDER BY i.created_at DESC LIMIT 20
    ");
    $stmt->execute([':cid' => (int)$_SESSION['customer_id']]);
    $items = $stmt->fetchAll();
    // Ne pas exposer le chemin PDF interne
    foreach ($items as &$inv) { unset($inv['pdf_path']); }
    echo json_success($items);
}

/** PATCH /api/v1/me/profile */
function handleUpdateProfile(?PDO $pdo, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }
    requireCustomerAuth();
    $body = $req->jsonBody();
    $repo = new CustomerRepository($pdo);
    $ok   = $repo->updateProfile((int)$_SESSION['customer_id'], $body);
    if (!$ok) { echo json_error('UPDATE_FAILED','Aucune modification valide.',422); return; }
    echo json_success(['updated' => true]);
}
