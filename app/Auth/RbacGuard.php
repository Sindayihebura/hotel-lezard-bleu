<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;
use App\Security\Logger;

/**
 * RbacGuard — Contrôle d'accès basé sur les rôles
 * Hôtel Le Lézard Bleu & Spa
 *
 * Usage dans les pages/contrôleurs admin :
 *
 *   $guard = new RbacGuard($pdo);
 *   $guard->requireAuth();
 *   $guard->requirePermission('payments.refund');
 *
 * Usage dans l'API REST :
 *
 *   $guard->requirePermissionOrAbort('reservations.view');
 *
 * IMPORTANT : Le contrôle de permission est TOUJOURS côté serveur.
 * Masquer un bouton dans l'interface ne constitue PAS une sécurité.
 */
class RbacGuard
{
    private ?PDO    $pdo;
    private Logger  $logger;
    private AdminAuth $adminAuth;

    // Matrice complète des permissions par rôle (cache mémoire)
    private static array $permissionCache = [];

    public function __construct(?PDO $pdo)
    {
        $this->pdo       = $pdo;
        $this->logger    = new Logger($pdo);
        $this->adminAuth = new AdminAuth($pdo);
    }

    // ── Guards de page (redirect) ─────────────────────────────────────

    /**
     * Exiger une connexion admin — redirige vers login si absent.
     */
    public function requireAuth(string $loginUrl = '/admin/login.php'): void
    {
        if (!$this->adminAuth->check()) {
            safe_redirect($loginUrl);
        }
    }

    /**
     * Exiger une permission — redirige vers une page 403 ou json error.
     */
    public function requirePermission(string $permission, bool $jsonResponse = false): void
    {
        $this->requireAuth();

        if (!$this->adminAuth->can($permission)) {
            $this->logAccessDenied($permission);

            if ($jsonResponse || $this->isApiRequest()) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false, 'data' => null,
                    'error'   => ['code' => 'FORBIDDEN', 'message' => 'Accès refusé.'],
                    'meta'    => ['request_id' => request_id()],
                ]);
                exit;
            }

            http_response_code(403);
            include base_path('public/errors/403.php');
            exit;
        }
    }

    /**
     * Vérifier une permission sans terminer l'exécution.
     */
    public function can(string $permission): bool
    {
        if (!$this->adminAuth->check()) {
            return false;
        }
        return $this->adminAuth->can($permission);
    }

    // ── Guards API (json abort) ───────────────────────────────────────

    /**
     * Pour les endpoints API : exiger auth + permission, sinon JSON 403.
     */
    public function requirePermissionOrAbort(string $permission): void
    {
        $this->requirePermission($permission, true);
    }

    /**
     * Exiger uniquement l'auth admin pour l'API, sinon JSON 401.
     */
    public function requireAdminOrAbort(): void
    {
        if (!$this->adminAuth->check()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false, 'data' => null,
                'error'   => ['code' => 'UNAUTHORIZED', 'message' => 'Authentification requise.'],
                'meta'    => ['request_id' => request_id()],
            ]);
            exit;
        }
    }

    // ── Contrôle BOLA (Object-Level Authorization) ────────────────────

    /**
     * Vérifier qu'un client peut accéder à sa propre réservation.
     * Un client ne peut pas voir la réservation d'un autre client.
     *
     * @param int $bookingCustomerId customer_id stocké dans la réservation
     * @param int $requesterId       customer_id du demandeur (session)
     */
    public function assertCustomerOwnsBooking(int $bookingCustomerId, int $requesterId): void
    {
        if ($bookingCustomerId !== $requesterId) {
            $this->logger->audit(
                Logger::ACTION_ACCESS_DENIED, 'booking', null,
                null, ['owner' => $bookingCustomerId, 'requester' => $requesterId, 'ip' => client_ip()],
                null, 'failure', 'BOLA : tentative accès réservation tierce'
            );
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false, 'data' => null,
                'error'   => ['code' => 'FORBIDDEN', 'message' => 'Accès refusé.'],
                'meta'    => ['request_id' => request_id()],
            ]);
            exit;
        }
    }

    /**
     * Vérifier qu'un admin peut accéder à un objet d'un autre tenant
     * (protection aussi côté admin — OWASP A1).
     */
    public function assertAdminCanAccessBooking(int $bookingId): void
    {
        // Dans cet hôtel mono-tenant, tous les admins accèdent à toutes les réservations
        // mais doivent avoir la permission reservations.view
        $this->requirePermission('reservations.view', true);
    }

    // ── Helpers privés ────────────────────────────────────────────────

    private function logAccessDenied(string $permission): void
    {
        $adminId = $_SESSION['admin_id'] ?? null;
        $this->logger->audit(
            Logger::ACTION_ACCESS_DENIED, 'permission', null,
            null, [
                'required_permission' => $permission,
                'admin_role'          => $_SESSION['admin_role'] ?? 'unknown',
                'ip'                  => client_ip(),
                'uri'                 => $_SERVER['REQUEST_URI'] ?? '',
            ],
            $adminId ? (int) $adminId : null,
            'failure', "Permission refusée : {$permission}"
        );
    }

    private function isApiRequest(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $uri    = $_SERVER['REQUEST_URI'] ?? '';
        return str_contains($accept, 'application/json') || str_contains($uri, '/api/');
    }
}
