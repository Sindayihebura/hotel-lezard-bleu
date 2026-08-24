<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Protection CSRF — Hôtel Le Lézard Bleu & Spa
 *
 * Génère et vérifie les tokens CSRF.
 * Combiné avec les cookies SameSite pour une double protection.
 */
class CsrfGuard
{
    private const TOKEN_LENGTH = 32; // bytes → 64 hex chars

    /**
     * Générer (ou réutiliser) le token CSRF de la session.
     */
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Vérifier un token soumis (résistant aux timing attacks).
     */
    public static function verify(string $submittedToken): bool
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        if ($submittedToken === '') {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $submittedToken);
    }

    /**
     * Vérifier depuis POST ou header X-CSRF-Token.
     * Retourne false si invalide (ne termine pas l'exécution — laisser au contrôleur).
     */
    public static function verifyRequest(): bool
    {
        $token = $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';
        return self::verify($token);
    }

    /**
     * Émettre le champ HTML hidden pour les formulaires.
     */
    public static function field(): string
    {
        $token = self::token();
        $escaped = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $escaped . '">';
    }

    /**
     * Rotation du token (après actions sensibles : changement de mot de passe, etc.).
     */
    public static function rotate(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        return $_SESSION['csrf_token'];
    }
}
