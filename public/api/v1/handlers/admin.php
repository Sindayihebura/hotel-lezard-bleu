<?php
declare(strict_types=1);

use App\Auth\RbacGuard;
use App\Booking\BookingService;
use App\Repositories\BookingRepository;
use App\Payments\PaymentService;
use App\Http\Request;

/**
 * Handler /api/v1/admin/* — Endpoints administration
 * Toutes les routes exigent une permission vérifiée côté serveur.
 */
function handleAdminRequest(?PDO $pdo, Request $req, array $segments, string $method): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $guard = new RbacGuard($pdo);
    $guard->requireAdminOrAbort();

    // /admin/reservations
    $sub    = $segments[1] ?? '';
    $subId  = isset($segments[2]) ? (int)$segments[2] : null;
    $action = $segments[3] ?? null;

    switch ($sub) {
        case 'reservations':
            if ($method === 'GET' && !$subId) {
                $guard->requirePermissionOrAbort('reservations.view');
                handleAdminListReservations($pdo, $req);
            } elseif ($method === 'PATCH' && $subId) {
                $guard->requirePermissionOrAbort('reservations.update');
                handleAdminUpdateReservation($pdo, $subId, $req);
            } elseif ($method === 'POST' && $subId && $action) {
                handleAdminReservationAction($pdo, $subId, $action, $guard, $req);
            } else {
                echo json_error('NOT_FOUND','Endpoint introuvable.',404);
            }
            break;
        default:
            echo json_error('NOT_FOUND','Endpoint admin introuvable.',404);
    }
}

function handleAdminListReservations(PDO $pdo, Request $req): void
{
    $repo    = new BookingRepository($pdo);
    $page    = max(1,(int)$req->query('page','1'));
    $limit   = min(50, max(5, (int)$req->query('limit','20')));
    $offset  = ($page - 1) * $limit;
    $filters = [
        'statut'         => $req->query('statut',''),
        'payment_status' => $req->query('payment_status',''),
        'search'         => $req->query('q',''),
        'date_from'      => $req->query('date_from',''),
        'date_to'        => $req->query('date_to',''),
    ];
    $items   = $repo->searchAdmin(array_filter($filters,'strlen'), $limit, $offset);
    $total   = $repo->countAdmin();
    foreach ($items as &$b) { unset($b['notes_admin']); }
    echo json_success($items, ['total' => $total, 'page' => $page, 'per_page' => $limit]);
}

function handleAdminUpdateReservation(PDO $pdo, int $id, Request $req): void
{
    $body    = $req->jsonBody();
    $allowed = ['notes_admin','special_requests','source'];
    $data    = array_intersect_key($body, array_flip($allowed));
    if (empty($data)) { echo json_error('EMPTY','Aucune donnée valide.',422); return; }

    $sets   = array_map(fn($k) => "`$k` = :$k", array_keys($data));
    $params = array_combine(array_map(fn($k)=>":$k", array_keys($data)), array_values($data));
    $params[':id'] = $id;
    $pdo->prepare('UPDATE bookings SET '.implode(',',$sets).', updated_at=UTC_TIMESTAMP() WHERE id=:id')->execute($params);
    echo json_success(['updated' => true]);
}

function handleAdminReservationAction(PDO $pdo, int $id, string $action, RbacGuard $guard, Request $req): void
{
    $service  = new BookingService($pdo);
    $adminId  = (int)($_SESSION['admin_id'] ?? 0);

    switch ($action) {
        case 'confirm':
            $guard->requirePermissionOrAbort('reservations.update');
            $ok = $service->confirm($id);
            break;
        case 'check-in':
            $guard->requirePermissionOrAbort('reservations.checkin');
            $ok = $service->checkin($id, $adminId);
            break;
        case 'check-out':
            $guard->requirePermissionOrAbort('reservations.checkout');
            $ok = $service->checkout($id, $adminId);
            break;
        case 'cancel':
            $guard->requirePermissionOrAbort('reservations.cancel');
            $reason = trim($req->str('reason','Annulation administrative'));
            $ok = $service->cancel($id, $reason, 'admin', $adminId);
            break;
        default:
            echo json_error('INVALID_ACTION','Action inconnue.',400); return;
    }

    if (!$ok) { echo json_error('ACTION_FAILED','L\'action a échoué.',422); return; }
    echo json_success(['booking_id' => $id, 'action' => $action, 'success' => true]);
}
