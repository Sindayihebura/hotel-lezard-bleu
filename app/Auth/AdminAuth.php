<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;
use App\Security\Logger;
use App\Security\RateLimiter;

/**
 * AdminAuth — Authentification du personnel hôtelier
 * Hôtel Le Lézard Bleu & Spa
 *
 * Différences vs AuthService (client) :
 * - MFA TOTP obligatoire selon config
 * - Session admin séparée (préfixe admin_*)
 * - Logging plus granulaire avec role
 * - Réauthentification avant actions sensibles (remboursement, export)
 */
class AdminAuth
{
    private ?PDO        $pdo;
    private Logger      $logger;
    private RateLimiter $limiter;

    private int $maxAttempts    = 5;
    private int $windowSeconds  = 600;
    private int $bcryptCost     = 12;

    public function __construct(?PDO $pdo)
    {
        $this->pdo     = $pdo;
        $this->logger  = new Logger($pdo);
        $this->limiter = new RateLimiter($pdo ?? new \PDO('sqlite::memory:'));

        if ($pdo !== null) {
            $secCfg = require base_path('config/security.php');
            $this->maxAttempts   = $secCfg['login']['max_attempts'];
            $this->windowSeconds = $secCfg['login']['window_seconds'];
            $this->bcryptCost    = $secCfg['password']['cost'];
        }
    }

    // ── Connexion ─────────────────────────────────────────────────────

    /**
     * Tenter une connexion admin.
     * @return array{success: bool, error: string|null, requires_mfa: bool}
     */
    public function attempt(string $email, string $password): array
    {
        if ($this->pdo === null) {
            return ['success' => false, 'error' => 'db_unavailable', 'requires_mfa' => false];
        }

        $email = strtolower(trim($email));
        $ip    = client_ip();

        // Rate limiting
        $keyIp    = "admin_login:ip:{$ip}";
        $keyEmail = "admin_login:email:{$email}";

        if ($this->limiter->tooManyAttempts($keyIp, $this->maxAttempts, $this->windowSeconds) ||
            $this->limiter->tooManyAttempts($keyEmail, $this->maxAttempts, $this->windowSeconds)) {

            $this->logger->audit(
                Logger::ACTION_BRUTE_FORCE, 'admin_auth', null, null,
                ['email_hash' => hash('sha256', $email), 'ip' => $ip],
                null, 'failure', 'Admin brute force'
            );
            return ['success' => false, 'error' => 'locked', 'requires_mfa' => false];
        }

        // Chercher l'admin
        $admin = $this->findByEmail($email);
        $hashToCheck = $admin['password_hash'] ?? '$2y$12$invalidhash............................';
        $valid = password_verify($password, $hashToCheck);

        if (!$valid || $admin === null || !(bool) $admin['is_active']) {
            $this->limiter->hit($keyIp, $this->windowSeconds);
            $this->limiter->hit($keyEmail, $this->windowSeconds);

            $this->logger->audit(
                Logger::ACTION_LOGIN_FAILED, 'admin_user', $admin['id'] ?? null,
                null, ['email_hash' => hash('sha256', $email), 'ip' => $ip],
                null, 'failure', 'Identifiants admin invalides'
            );
            return ['success' => false, 'error' => 'invalid', 'requires_mfa' => false];
        }

        // MFA requis ?
        if ((bool) $admin['mfa_enabled']) {
            // Stocker l'état pré-MFA en session
            $_SESSION['admin_pending_mfa'] = $admin['id'];
            $_SESSION['admin_pending_ip']  = $ip;
            session_regenerate_id(true);
            return ['success' => false, 'error' => null, 'requires_mfa' => true];
        }

        // Rehash si nécessaire
        if (password_needs_rehash($hashToCheck, PASSWORD_DEFAULT, ['cost' => $this->bcryptCost])) {
            $this->pdo->prepare(
                "UPDATE admin_users SET password_hash = :h WHERE id = :id"
            )->execute([
                ':h'  => password_hash($password, PASSWORD_DEFAULT, ['cost' => $this->bcryptCost]),
                ':id' => $admin['id'],
            ]);
        }

        $this->createAdminSession($admin, $ip);
        $this->limiter->reset($keyIp);
        $this->limiter->reset($keyEmail);
        $this->updateLastLogin((int) $admin['id'], $ip);

        $this->logger->audit(
            Logger::ACTION_LOGIN, 'admin_user', (int) $admin['id'],
            null, ['ip' => $ip, 'role' => $admin['role_name'] ?? ''],
            (int) $admin['id'], 'success'
        );

        return ['success' => true, 'error' => null, 'requires_mfa' => false];
    }

    // ── Déconnexion ───────────────────────────────────────────────────

    public function logout(): void
    {
        $adminId = $_SESSION['admin_id'] ?? null;

        $this->logger->audit(
            Logger::ACTION_LOGOUT, 'admin_user',
            $adminId ? (int) $adminId : null,
            null, null,
            $adminId ? (int) $adminId : null,
            'success'
        );

        // Supprimer uniquement les clés admin de la session
        foreach (array_keys($_SESSION) as $key) {
            if (str_starts_with($key, 'admin_')) {
                unset($_SESSION[$key]);
            }
        }
        session_regenerate_id(true);
    }

    // ── Vérification ─────────────────────────────────────────────────

    public function check(): bool
    {
        return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_auth']);
    }

    public function user(): ?array
    {
        if (!$this->check() || $this->pdo === null) {
            return null;
        }
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.first_name, u.last_name, u.email, u.phone,
                   u.is_active, u.mfa_enabled, u.last_login_at, u.last_login_ip,
                   r.name AS role, r.label AS role_label
            FROM admin_users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.id = :id AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Vérifier la permission d'un admin sur une action.
     * Toujours côté serveur — ne jamais se fier à un état frontend.
     */
    public function can(string $permission): bool
    {
        if (!$this->check() || $this->pdo === null) {
            return false;
        }

        // Cache session pour éviter une requête DB par vérification
        $cacheKey = 'admin_perms_' . $_SESSION['admin_id'];
        if (empty($_SESSION[$cacheKey])) {
            $_SESSION[$cacheKey] = $this->loadPermissions((int) $_SESSION['admin_id']);
        }

        return in_array($permission, $_SESSION[$cacheKey], true);
    }

    /**
     * Exiger une permission — termine avec 403 si refusé.
     */
    public function requirePermission(string $permission): void
    {
        if (!$this->can($permission)) {
            $adminId = $_SESSION['admin_id'] ?? null;
            $this->logger->audit(
                Logger::ACTION_ACCESS_DENIED, 'permission', null,
                null, ['required' => $permission, 'ip' => client_ip()],
                $adminId ? (int) $adminId : null,
                'failure', "Permission manquante : {$permission}"
            );
            http_response_code(403);
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'data'    => null,
                'error'   => ['code' => 'FORBIDDEN', 'message' => 'Accès refusé.'],
                'meta'    => ['request_id' => request_id()],
            ]);
            exit;
        }
    }

    /**
     * Réauthentifier avant une action sensible (remboursement, export, suppression).
     */
    public function reauth(string $password): bool
    {
        if (!$this->check() || $this->pdo === null) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "SELECT password_hash FROM admin_users WHERE id = :id AND is_active = 1"
        );
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $hash = $stmt->fetchColumn();
        return $hash !== false && password_verify($password, $hash);
    }

    // ── Helpers privés ────────────────────────────────────────────────

    private function findByEmail(string $email): ?array
    {
        if ($this->pdo === null) return null;
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.name AS role_name
            FROM admin_users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.email = :email AND u.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function createAdminSession(array $admin, string $ip): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id']        = $admin['id'];
        $_SESSION['admin_email']     = $admin['email'];
        $_SESSION['admin_role']      = $admin['role_name'];
        $_SESSION['admin_auth']      = true;
        $_SESSION['admin_auth_time'] = time();
        $_SESSION['admin_ip']        = $ip;
        // Invalider le cache des permissions
        unset($_SESSION['admin_perms_' . $admin['id']]);
    }

    private function loadPermissions(int $adminId): array
    {
        if ($this->pdo === null) return [];
        $stmt = $this->pdo->prepare("
            SELECT p.name
            FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            JOIN admin_users u       ON u.role_id = rp.role_id
            WHERE u.id = :admin_id
        ");
        $stmt->execute([':admin_id' => $adminId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
    }

    private function updateLastLogin(int $adminId, string $ip): void
    {
        if ($this->pdo === null) return;
        $this->pdo->prepare("
            UPDATE admin_users
            SET last_login_at = UTC_TIMESTAMP(), last_login_ip = :ip
            WHERE id = :id
        ")->execute([':ip' => $ip, ':id' => $adminId]);
    }
}
