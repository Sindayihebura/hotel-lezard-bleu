<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * CustomerRepository — Accès aux données clients
 * Hôtel Le Lézard Bleu & Spa
 */
class CustomerRepository extends BaseRepository
{
    protected string $table = 'customers';

    protected function getAllowedColumns(): array
    {
        return [
            'first_name','last_name','email','password_hash','phone',
            'country_code','preferred_locale','preferred_currency',
            'email_verified_at','is_active','is_guest','newsletter_consent',
            'special_requests','notes_admin','last_login_at','password_changed_at',
        ];
    }

    /** Trouver par email (pour la connexion — retourne aussi le hash). */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM customers WHERE email = :email AND is_active = 1 LIMIT 1
        ");
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Trouver par ID en excluant le hash de mot de passe (pour l'API). */
    public function findByIdSafe(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, first_name, last_name, email, phone,
                   country_code, preferred_locale, preferred_currency,
                   email_verified_at, is_active, newsletter_consent,
                   last_login_at, created_at
            FROM customers WHERE id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Mettre à jour la date de dernière connexion. */
    public function touchLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE customers SET last_login_at = UTC_TIMESTAMP() WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
    }

    /** Marquer l'email comme vérifié. */
    public function markEmailVerified(int $id): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE customers
            SET email_verified_at = UTC_TIMESTAMP()
            WHERE id = :id AND email_verified_at IS NULL
        ");
        $stmt->execute([':id' => $id]);
    }

    /** Mettre à jour le mot de passe et révoquer les sessions. */
    public function updatePassword(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE customers
            SET password_hash = :hash, password_changed_at = UTC_TIMESTAMP()
            WHERE id = :id
        ");
        $stmt->execute([':hash' => $hash, ':id' => $id]);
    }

    /** Profil public mis à jour par le client (whitelist stricte). */
    public function updateProfile(int $id, array $data): bool
    {
        // Champs que le client peut modifier lui-même
        $allowed = ['first_name','last_name','phone','country_code',
                    'preferred_locale','preferred_currency','newsletter_consent','special_requests'];
        $data = array_intersect_key($data, array_flip($allowed));
        if (empty($data)) {
            return false;
        }
        $this->update($id, $data);
        return true;
    }

    /** Compter les clients pour le rapport. */
    public function countByCountry(): array
    {
        $stmt = $this->pdo->query("
            SELECT country_code, COUNT(*) AS total
            FROM customers
            WHERE is_active = 1 AND is_guest = 0
            GROUP BY country_code
            ORDER BY total DESC
            LIMIT 20
        ");
        return $stmt ? $stmt->fetchAll() : [];
    }
}
