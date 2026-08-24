<?php
declare(strict_types=1);

use App\Payments\PaymentService;
use App\Repositories\PaymentRepository;
use App\Repositories\BookingRepository;
use App\Auth\RbacGuard;
use App\Http\Request;

/** POST /api/v1/payments/initiate */
function handleInitiatePayment(?PDO $pdo, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $body       = $req->jsonBody();
    $bookingId  = (int)($body['booking_id']  ?? 0);
    $provider   = trim($body['provider']     ?? 'manual');
    $method     = trim($body['method']       ?? 'manual');

    if (!$bookingId) { echo json_error('MISSING_PARAMS','booking_id obligatoire.',422); return; }

    // Vérifier que le demandeur est propriétaire ou admin
    $bookings = new BookingRepository($pdo);
    $booking  = $bookings->findById($bookingId);
    if (!$booking) { echo json_error('NOT_FOUND','Réservation introuvable.',404); return; }

    if (!empty($_SESSION['customer_auth'])) {
        if ((int)$booking['customer_id'] !== (int)$_SESSION['customer_id']) {
            echo json_error('FORBIDDEN','Accès refusé.',403); return;
        }
    }

    // Whitelist providers
    $allowed = ['manual','cash_bif','cash_usd','bank_local','lumicash','ecocash','easypay','paypal'];
    if (!in_array($provider, $allowed, true)) {
        echo json_error('INVALID_PROVIDER','Fournisseur de paiement non supporté.',422); return;
    }

    $service = new PaymentService($pdo);
    $result  = $service->initiatePayment($bookingId, $provider, $method, $body);

    if (!$result['success']) {
        echo json_error('PAYMENT_FAILED', $result['error'] ?? 'Erreur paiement.', 422); return;
    }

    echo json_success([
        'payment_id'   => $result['payment_id'],
        'expires_at'   => $result['expires_at'],
        'redirect_url' => $result['redirect_url'],
        'status'       => 'pending_customer',
    ]);
}

/** GET /api/v1/payments/{id} */
function handleGetPayment(?PDO $pdo, int $id, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $repo    = new PaymentRepository($pdo);
    $payment = $repo->findById($id);
    if (!$payment) { echo json_error('NOT_FOUND','Paiement introuvable.',404); return; }

    // BOLA : vérifier que le paiement appartient au client connecté
    if (!empty($_SESSION['customer_auth'])) {
        $bookings = new BookingRepository($pdo);
        $booking  = $bookings->findById((int)$payment['booking_id']);
        if (!$booking || (int)$booking['customer_id'] !== (int)$_SESSION['customer_id']) {
            echo json_error('FORBIDDEN','Accès refusé.',403); return;
        }
    }

    // Ne jamais exposer les données sensibles fournisseur au client
    $safe = [
        'id'             => $payment['id'],
        'booking_id'     => $payment['booking_id'],
        'provider'       => $payment['provider'],
        'payment_method' => $payment['payment_method'],
        'amount_bif'     => $payment['amount_bif'],
        'currency'       => $payment['currency_charged'],
        'status'         => $payment['payment_status'],
        'expires_at'     => $payment['expires_at'],
        'confirmed_at'   => $payment['confirmed_at'],
        'created_at'     => $payment['created_at'],
    ];
    echo json_success($safe);
}

/** POST /api/v1/payments/{id}/refund  (admin) */
function handleRefundPayment(?PDO $pdo, int $id, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    // Permission obligatoire
    $guard = new RbacGuard($pdo);
    $guard->requirePermissionOrAbort('payments.refund');

    $body      = $req->jsonBody();
    $amountBif = (int)($body['amount_bif'] ?? 0);
    $reason    = trim($body['reason'] ?? '');
    $adminId   = (int)($_SESSION['admin_id'] ?? 0);

    if ($amountBif <= 0) { echo json_error('INVALID_AMOUNT','Montant de remboursement invalide.',422); return; }
    if (!$reason) { echo json_error('MISSING_REASON','Raison du remboursement obligatoire.',422); return; }

    $service = new PaymentService($pdo);
    $result  = $service->initiateRefund($id, $amountBif, $reason, $adminId);

    if (!$result['success']) { echo json_error('REFUND_FAILED', $result['error'], 422); return; }

    echo json_success(['payment_id' => $id, 'status' => 'refund_initiated']);
}
