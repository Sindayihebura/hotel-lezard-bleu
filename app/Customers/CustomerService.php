<?php

declare(strict_types=1);

namespace App\Customers;

use PDO;
use App\Repositories\CustomerRepository;
use App\Security\Logger;

/**
 * CustomerService — Gestion des clients
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 */
class CustomerService
{
    private PDO                $pdo;
    private CustomerRepository $customers;
    private Logger             $logger;

    public function __construct(PDO $pdo)
    {
        $this->pdo       = $pdo;
        $this->customers = new CustomerRepository($pdo);
        $this->logger    = new Logger($pdo);
    }

    /**
     * Créer un compte client invité (depuis une réservation).
     */
    public function createGuestAccount(array $data): int
    {
        return (int)$this->customers->insert([
            'first_name'       => trim($data['first_name']  ?? ''),
            'last_name'        => trim($data['last_name']   ?? ''),
            'email'            => strtolower(trim($data['email'] ?? '')),
            'phone'            => trim($data['phone']       ?? '') ?: null,
            'country_code'     => strtoupper(substr(trim($data['country'] ?? ''), 0, 2)) ?: null,
            'preferred_locale' => in_array($data['locale'] ?? 'fr', ['fr','en','rn']) ? $data['locale'] : 'fr',
            'preferred_currency' => 'BIF',
            'is_active'        => 1,
            'is_guest'         => 1,
        ]);
    }

    /**
     * Trouver ou créer un client par email (pour les réservations invités).
     */
    public function findOrCreateByEmail(array $data): int
    {
        $existing = $this->customers->findByEmail(strtolower(trim($data['email'] ?? '')));
        if ($existing) {
            return (int)$existing['id'];
        }
        return $this->createGuestAccount($data);
    }

    /**
     * Historique des réservations d'un client.
     */
    public function getBookingHistory(int $customerId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, r.name AS room_name
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            WHERE b.customer_id = :cid
            ORDER BY b.date_arrivee DESC
            LIMIT 50
        ");
        $stmt->execute([':cid' => $customerId]);
        return $stmt->fetchAll();
    }

    /**
     * Statistiques d'un client.
     */
    public function getCustomerStats(int $customerId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) AS total_bookings,
                COALESCE(SUM(CASE WHEN payment_status='paid' THEN total_bif ELSE 0 END),0) AS total_spent_bif,
                COALESCE(SUM(CASE WHEN statut='cancelled' THEN 1 ELSE 0 END),0) AS cancellations,
                MAX(date_arrivee) AS last_stay
            FROM bookings
            WHERE customer_id = :cid
        ");
        $stmt->execute([':cid' => $customerId]);
        return $stmt->fetch() ?: [];
    }
}
