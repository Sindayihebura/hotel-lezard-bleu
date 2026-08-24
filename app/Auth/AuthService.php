<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;
use App\Security\Logger;
use App\Security\RateLimiter;
use App\Repositories\CustomerRepository;

/**
 * AuthService — Authentification clients
 * Hôtel Le Lézard Bleu & Spa
 *
 * Règles :
 * - password_hash / password_verify UNIQUEMENT (bcrypt)
 * - session_regenerate_id(true) après chaque connexion réussie
 * - Blocage temporaire après N tentatives (rate limiter DB)
 * - Messages d'erreur génériques (ne révèle pas si l'email existe)
 * - Aucune donnée sensible dans les logs (mot de passe, token complet)
 * - Révocation de toutes les sessions après changement de mot de passe
 * - Cookie session : Secure + HttpOnly + SameSite (géré dans bootstrap)
 */
class AuthService
{
    private CustomerRepository $customers;
    private Logger             $logger;
    private RateLimiter        $limiter;
    private PDO                $pdo;

    private int    $maxAttempts    = 5;
    private int    $windowSeconds  = 600;   // 10 min
    private int    $lockoutMinutes = 15;
    private int    $passwordMinLen = 10;
    private int    $bcryptCost     = 12;

    public function __construct(PDO $pdo)
    {
        $this->pdo       = $pdo;
        $this->customers = new CustomerRepository($pdo);
        $this->logger    = new Logger($pdo);
        $this->limiter   = new RateLimiter($pdo);

        // Lire depuis config
        $secCfg = require base_path('config/security.php');
        $this->maxAttempts    = $secCfg['login']['max_attempts'];
        $this->windowSeconds  = $secCfg['login']['window_seconds'];
        $this->lockoutMinutes = $secCfg['login']['lockout_minutes'];
        $this->passwordMinLen = $secCfg['password']['min_length'];
        $this->bcryptCost     = $secCfg['password']['cost'];
    }

    // ── Connexion ─────────────────────────────────────────────────────

    /**
     * Tenter une connexion client.
     * Retourne ['success' => bool, 'error' => string|null, 'lockout_minutes' => int]
     */
    public function attempt(string $email, string $password, string $ip): array
    {
        $email = strtolower(trim($email));

        // 1. Rate limiting par IP + email
        $keyIp    = "login:ip:{$ip}";
        $keyEmail = "login:email:{$email}";

        if ($this->limiter->tooManyAttempts($keyIp, $this->maxAttempts, $this->windowSeconds) ||
            $this->limiter->tooManyAttempts($keyEmail, $this->maxAttempts, $this->windowSeconds)) {

            $this->logger->audit(
                Logger::ACTION_BRUTE_FORCE, 'auth', null, null,
                ['email_hash' => hash('sha256', $email), 'ip' => $ip],
                null, 'failure', 'Rate limit dépassé'
            );
            return [
                'success'         => false,
                'error'           => 'locked',
                'lockout_minutes' => $this->lockoutMinutes,
            ];
        }

        // 2. Chercher le client
        $customer = $this->customers->findByEmail($email);

        // 3. Vérifier le mot de passe (timing constant même si l'email n'existe pas)
        $hashToCheck = $customer['password_hash'] ?? '$2y$12$invalidhashtopreventtimingattack....';
        $valid       = password_verify($password, $hashToCheck);

        // 4. Enregistrer la tentative
        $this->recordLoginAttempt($email, $ip, $valid && $customer !== null);

        if (!$valid || $customer === null) {
            $this->limiter->hit($keyIp, $this->windowSeconds);
            $this->limiter->hit($keyEmail, $this->windowSeconds);

            $this->logger->audit(
                Logger::ACTION_LOGIN_FAILED, 'customer', null, null,
                ['email_hash' => hash('sha256', $email), 'ip' => $ip],
                null, 'failure', 'Identifiants invalides'
            );
            return ['success' => false, 'error' => 'invalid', 'lockout_minutes' => 0];
        }

        // 5. Vérifier que le compte est actif
        if (!(bool) $customer['is_active']) {
            return ['success' => false, 'error' => 'inactive', 'lockout_minutes' => 0];
        }

        // 6. Vérifier si l'email est vérifié (optionnel selon config)
        // En phase MVP : connexion autorisée même sans vérification,
        // mais on affiche un rappel.
        $emailVerified = $customer['email_verified_at'] !== null;

        // 7. Rehash si nécessaire (algorithme ou cost mis à jour)
        if (password_needs_rehash($hashToCheck, PASSWORD_DEFAULT, ['cost' => $this->bcryptCost])) {
            $this->customers->updatePassword((int) $customer['id'],
                password_hash($password, PASSWORD_DEFAULT, ['cost' => $this->bcryptCost])
            );
        }

        // 8. Créer la session sécurisée
        $this->createCustomerSession($customer);

        // 9. Réinitialiser le rate limiter
        $this->limiter->reset($keyIp);
        $this->limiter->reset($keyEmail);

        // 10. Mettre à jour last_login
        $this->customers->touchLastLogin((int) $customer['id']);

        // 11. Audit
        $this->logger->audit(
            Logger::ACTION_LOGIN, 'customer', (int) $customer['id'],
            null, ['ip' => $ip, 'email_verified' => $emailVerified],
            null, 'success'
        );

        return [
            'success'        => true,
            'error'          => null,
            'email_verified' => $emailVerified,
            'customer_id'    => (int) $customer['id'],
        ];
    }

    // ── Inscription ───────────────────────────────────────────────────

    /**
     * Inscrire un nouveau client.
     * Retourne ['success' => bool, 'error' => string|null, 'customer_id' => int|null]
     */
    public function register(array $data, string $ip): array
    {
        // Rate limiting inscription
        $key = "register:ip:{$ip}";
        if ($this->limiter->tooManyAttempts($key, 10, 3600)) {
            return ['success' => false, 'error' => 'rate_limit', 'customer_id' => null];
        }

        $email     = strtolower(trim($data['email'] ?? ''));
        $firstName = trim($data['first_name'] ?? '');
        $lastName  = trim($data['last_name'] ?? '');
        $password  = $data['password'] ?? '';
        $confirm   = $data['password_confirm'] ?? '';
        $phone     = trim($data['phone'] ?? '');
        $country   = strtoupper(trim($data['country_code'] ?? ''));
        $locale    = in_array($data['locale'] ?? 'fr', ['fr','en','rn']) ? $data['locale'] : 'fr';

        // Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'invalid_email', 'customer_id' => null];
        }
        if (strlen($firstName) < 2 || strlen($firstName) > 80) {
            return ['success' => false, 'error' => 'invalid_name', 'customer_id' => null];
        }
        if (strlen($password) < $this->passwordMinLen) {
            return ['success' => false, 'error' => 'password_too_short', 'customer_id' => null];
        }
        if ($password !== $confirm) {
            return ['success' => false, 'error' => 'password_mismatch', 'customer_id' => null];
        }

        // Unicité email
        if ($this->customers->findByEmail($email) !== null) {
            // Message générique — ne pas révéler qu'un compte existe
            // On retourne 'success' avec un message neutre (comportement Amazon-like)
            // pour éviter l'énumération d'emails
            $this->limiter->hit($key, 3600);
            // Audit discret
            $this->logger->log(Logger::INFO, 'register_duplicate_email', [
                'email_hash' => hash('sha256', $email),
            ]);
            // On retourne "succès" apparent (email envoyé) mais sans créer de compte
            return ['success' => true, 'error' => null, 'customer_id' => null, 'duplicate' => true];
        }

        // Hacher le mot de passe
        $hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => $this->bcryptCost]);

        // Créer le compte
        $customerId = (int) $this->customers->insert([
            'first_name'         => $firstName,
            'last_name'          => $lastName,
            'email'              => $email,
            'password_hash'      => $hash,
            'phone'              => $phone ?: null,
            'country_code'       => $country ?: null,
            'preferred_locale'   => $locale,
            'preferred_currency' => 'BIF',
            'is_active'          => 1,
            'is_guest'           => 0,
            'newsletter_consent' => (int) !empty($data['newsletter']),
        ]);

        // Générer et envoyer le token de vérification d'email
        $this->sendEmailVerification($customerId, $email, $locale);

        $this->limiter->hit($key, 3600);

        $this->logger->audit(
            Logger::ACTION_REGISTER, 'customer', $customerId,
            null, ['ip' => $ip, 'locale' => $locale],
            null, 'success'
        );

        return ['success' => true, 'error' => null, 'customer_id' => $customerId, 'duplicate' => false];
    }

    // ── Déconnexion ───────────────────────────────────────────────────

    public function logout(): void
    {
        $customerId = $_SESSION['customer_id'] ?? null;

        $this->logger->audit(
            Logger::ACTION_LOGOUT, 'customer', $customerId ? (int) $customerId : null,
            null, null, null, 'success'
        );

        session_unset();
        session_destroy();

        // Démarrer une nouvelle session propre
        session_start();
        session_regenerate_id(true);
    }

    // ── Vérification email ────────────────────────────────────────────

    public function verifyEmail(string $token): bool
    {
        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare("
            SELECT * FROM email_verifications
            WHERE token_hash = :hash
              AND expires_at > UTC_TIMESTAMP()
              AND used_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        // Marquer comme utilisé
        $this->pdo->prepare("
            UPDATE email_verifications SET used_at = UTC_TIMESTAMP() WHERE id = :id
        ")->execute([':id' => $row['id']]);

        $this->customers->markEmailVerified((int) $row['user_id']);

        $this->logger->audit(
            Logger::ACTION_EMAIL_VERIFIED, 'customer', (int) $row['user_id'],
            null, null, null, 'success'
        );

        return true;
    }

    // ── Récupération mot de passe ─────────────────────────────────────

    public function requestPasswordReset(string $email, string $ip): void
    {
        // Rate limiting
        $key = "pwreset:email:{$email}";
        if ($this->limiter->tooManyAttempts($key, 3, 3600)) {
            return; // Silencieux — ne pas révéler
        }

        $customer = $this->customers->findByEmail(strtolower(trim($email)));

        // Toujours répondre "email envoyé" même si le compte n'existe pas
        if ($customer !== null) {
            $token     = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires   = gmdate('Y-m-d H:i:s', time() + 3600);

            $this->pdo->prepare("
                INSERT INTO password_resets (email, token_hash, expires_at, ip_address)
                VALUES (:email, :hash, :expires, :ip)
            ")->execute([
                ':email'   => $email,
                ':hash'    => $tokenHash,
                ':expires' => $expires,
                ':ip'      => $ip,
            ]);

            // Enqueue email de réinitialisation
            $this->enqueueNotification('email', $email, 'password_reset', [
                'token'      => $token,
                'first_name' => $customer['first_name'],
                'expires_at' => $expires,
            ], $customer['preferred_locale'] ?? 'fr');
        }

        $this->limiter->hit($key, 3600);
    }

    public function resetPassword(string $token, string $newPassword, string $ip): array
    {
        if (strlen($newPassword) < $this->passwordMinLen) {
            return ['success' => false, 'error' => 'password_too_short'];
        }

        $tokenHash = hash('sha256', $token);

        $stmt = $this->pdo->prepare("
            SELECT pr.*, c.id AS customer_id
            FROM password_resets pr
            JOIN customers c ON c.email = pr.email
            WHERE pr.token_hash = :hash
              AND pr.expires_at > UTC_TIMESTAMP()
              AND pr.used_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['success' => false, 'error' => 'invalid_token'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT, ['cost' => $this->bcryptCost]);
        $this->customers->updatePassword((int) $row['customer_id'], $hash);

        // Marquer le token comme utilisé
        $this->pdo->prepare("
            UPDATE password_resets SET used_at = UTC_TIMESTAMP() WHERE id = :id
        ")->execute([':id' => $row['id']]);

        // Invalider toutes les sessions actives (révocation)
        // En PHP natif : en production, utiliser un champ session_version
        // incrémenté, vérifié à chaque requête authentifiée.
        $this->invalidateCustomerSessions((int) $row['customer_id']);

        $this->logger->audit(
            Logger::ACTION_PASSWORD_CHANGED, 'customer', (int) $row['customer_id'],
            null, ['ip' => $ip], null, 'success'
        );

        return ['success' => true, 'error' => null];
    }

    // ── Vérification de session ───────────────────────────────────────

    /** Vérifier si un client est connecté et sa session est valide. */
    public function check(): bool
    {
        if (empty($_SESSION['customer_id']) || empty($_SESSION['customer_auth'])) {
            return false;
        }

        // Vérifier que la version de session correspond
        $expected = $this->getSessionVersion((int) $_SESSION['customer_id']);
        if ($expected !== null && ($_SESSION['session_version'] ?? 0) < $expected) {
            // Session révoquée (changement de mot de passe)
            $this->logout();
            return false;
        }

        return true;
    }

    /** Retourner les données du client connecté (sans mot de passe). */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }
        return $this->customers->findByIdSafe((int) $_SESSION['customer_id']);
    }

    /** Forcer la réauthentification avant une action sensible. */
    public function requireReauth(string $password): bool
    {
        if (!$this->check()) {
            return false;
        }
        $customer = $this->customers->findByEmail($_SESSION['customer_email'] ?? '');
        if (!$customer) {
            return false;
        }
        return password_verify($password, $customer['password_hash']);
    }

    // ── Helpers privés ────────────────────────────────────────────────

    private function createCustomerSession(array $customer): void
    {
        // Régénérer l'ID de session avant d'y écrire (protection fixation)
        session_regenerate_id(true);

        $_SESSION['customer_id']       = $customer['id'];
        $_SESSION['customer_email']    = $customer['email'];
        $_SESSION['customer_auth']     = true;
        $_SESSION['customer_locale']   = $customer['preferred_locale'];
        $_SESSION['customer_currency'] = $customer['preferred_currency'];
        $_SESSION['session_version']   = $this->getSessionVersion((int) $customer['id']) ?? 1;
        $_SESSION['auth_time']         = time();
        $_SESSION['locale']            = $customer['preferred_locale'];
        $_SESSION['currency']          = $customer['preferred_currency'];
    }

    private function recordLoginAttempt(string $email, string $ip, bool $success): void
    {
        try {
            $this->pdo->prepare("
                INSERT INTO login_attempts (identifier, ip_address, success)
                VALUES (:id, :ip, :s)
            ")->execute([
                ':id' => hash('sha256', $email), // Ne jamais stocker l'email en clair dans les logs
                ':ip' => $ip,
                ':s'  => (int) $success,
            ]);
        } catch (\PDOException $e) {
            error_log('[Auth] recordLoginAttempt: ' . $e->getMessage());
        }
    }

    private function sendEmailVerification(int $customerId, string $email, string $locale): void
    {
        $token     = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires   = gmdate('Y-m-d H:i:s', time() + 86400); // 24h

        try {
            $this->pdo->prepare("
                INSERT INTO email_verifications (user_id, token_hash, expires_at)
                VALUES (:uid, :hash, :exp)
            ")->execute([':uid' => $customerId, ':hash' => $tokenHash, ':exp' => $expires]);

            $this->enqueueNotification('email', $email, 'email_verification', [
                'token'       => $token,
                'customer_id' => $customerId,
                'expires_at'  => $expires,
            ], $locale);
        } catch (\PDOException $e) {
            error_log('[Auth] sendEmailVerification: ' . $e->getMessage());
        }
    }

    private function enqueueNotification(
        string $type, string $recipient, string $template,
        array $data, string $locale
    ): void {
        try {
            $this->pdo->prepare("
                INSERT INTO notification_queue
                  (type, recipient, template, data_json, locale, status, scheduled_at)
                VALUES (:type, :r, :tpl, :data, :locale, 'pending', UTC_TIMESTAMP())
            ")->execute([
                ':type'   => $type,
                ':r'      => $recipient,
                ':tpl'    => $template,
                ':data'   => json_encode($data),
                ':locale' => $locale,
            ]);
        } catch (\PDOException $e) {
            error_log('[Auth] enqueueNotification: ' . $e->getMessage());
        }
    }

    private function getSessionVersion(int $customerId): ?int
    {
        // Utiliser password_changed_at comme proxy de version de session
        $stmt = $this->pdo->prepare(
            "SELECT UNIX_TIMESTAMP(password_changed_at) FROM customers WHERE id = :id"
        );
        $stmt->execute([':id' => $customerId]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }

    private function invalidateCustomerSessions(int $customerId): void
    {
        // Approche : mettre à jour password_changed_at déclenche une révocation
        // à la prochaine vérification de session (getSessionVersion).
        // En production avec Redis/DB sessions : supprimer les sessions du customer.
        // Ici la mise à jour password_changed_at est faite dans updatePassword().
    }
}
