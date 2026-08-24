<?php
declare(strict_types=1);

use App\Booking\BookingService;
use App\Repositories\BookingRepository;
use App\Security\CsrfGuard;
use App\Http\Request;

/** POST /api/v1/bookings */
function handleCreateBooking(?PDO $pdo, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }
    if ($req->method() !== 'POST') { echo json_error('METHOD_NOT_ALLOWED','Méthode non autorisée.',405); return; }

    // CSRF pour les requêtes non-JSON depuis formulaire
    if (!$req->isJsonRequest()) {
        if (!CsrfGuard::verifyRequest()) {
            echo json_error('CSRF_INVALID','Jeton de sécurité invalide.',403); return;
        }
    }

    $body       = $req->all();
    $customerId = !empty($_SESSION['customer_auth']) ? (int)$_SESSION['customer_id'] : null;

    $service = new BookingService($pdo);
    $result  = $service->create($body, $customerId);

    if (!$result['success']) {
        echo json_error('BOOKING_FAILED', $result['error'] ?? 'Erreur de réservation.', 422); return;
    }

    http_response_code(201);
    echo json_success([
        'booking_id' => $result['booking_id'],
        'reference'  => $result['reference'],
        'total_bif'  => $result['totals']['total_bif'],
        'total_usd_cents' => $result['totals']['total_usd_cents'],
        'exchange_rate'   => $result['totals']['rate'],
        'status'     => 'provisional',
    ]);
}

/** GET /api/v1/bookings/{reference} */
function handleGetBooking(?PDO $pdo, string $reference, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $repo    = new BookingRepository($pdo);
    $ref     = strtoupper(trim($reference));
    $booking = null;

    // BOLA : client ne voit que ses propres réservations
    if (!empty($_SESSION['customer_auth'])) {
        $booking = $repo->findByReferenceForCustomer($ref, (int)$_SESSION['customer_id']);
    } else {
        // Invité : accès par référence + email (vérification légère)
        $email = strtolower(trim($req->query('email','')));
        if ($email) {
            $b = $repo->findByReference($ref);
            if ($b && strtolower($b['guest_email']) === $email) $booking = $b;
        }
    }

    if (!$booking) {
        echo json_error('NOT_FOUND','Réservation introuvable.',404); return;
    }

    // Retirer les champs internes sensibles
    unset($booking['notes_admin'], $booking['price_snapshot_json']);
    echo json_success($booking);
}

/** POST /api/v1/bookings/{reference}/cancel */
function handleCancelBooking(?PDO $pdo, string $reference, Request $req): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $repo = new BookingRepository($pdo);
    $ref  = strtoupper(trim($reference));

    // BOLA : vérification propriétaire
    if (empty($_SESSION['customer_auth'])) {
        echo json_error('UNAUTHORIZED','Authentification requise.',401); return;
    }
    $booking = $repo->findByReferenceForCustomer($ref, (int)$_SESSION['customer_id']);
    if (!$booking) {
        echo json_error('NOT_FOUND','Réservation introuvable.',404); return;
    }
    if (!in_array($booking['statut'], ['provisional','confirmed'], true)) {
        echo json_error('CANNOT_CANCEL','Cette réservation ne peut plus être annulée.',422); return;
    }

    $service = new BookingService($pdo);
    $reason  = trim($req->str('reason','Annulation client'));
    $ok = $service->cancel((int)$booking['id'], $reason, 'customer');

    if (!$ok) { echo json_error('CANCEL_FAILED','Échec de l\'annulation.',500); return; }

    echo json_success(['reference' => $ref, 'status' => 'cancelled']);
}
