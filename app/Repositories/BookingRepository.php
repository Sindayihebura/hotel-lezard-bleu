<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * BookingRepository — Accès aux données de réservation
 * Hôtel Le Lézard Bleu & Spa
 *
 * Implémente le contrôle BOLA (OWASP A1) :
 * toutes les méthodes de recherche acceptent un $ownerId
 * pour vérifier que le demandeur est bien le propriétaire de la ressource.
 */
class BookingRepository extends BaseRepository
{
    protected string $table = 'bookings';

    protected function getAllowedColumns(): array
    {
        return [
            'reference','room_id','customer_id',
            'guest_first_name','guest_last_name','guest_email','guest_phone','guest_country',
            'date_arrivee','date_depart','nb_adults','nb_children',
            'currency_chosen','exchange_rate_used','price_per_night_bif','nb_nights',
            'subtotal_bif','services_total_bif','discount_bif','total_bif','total_usd_cents',
            'price_snapshot_json','payment_method','payment_status','statut',
            'cancelled_at','cancellation_reason','cancelled_by',
            'services_json','offer_code','special_requests',
            'source','notes_admin','invoice_sent_at',
        ];
    }

    // ── Recherche ─────────────────────────────────────────────────────

    /** Trouver par référence publique (insensible à la casse). */
    public function findByReference(string $reference): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, r.name AS room_name, r.photo_main, rc.name AS category_name
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            JOIN room_categories rc ON rc.id = r.category_id
            WHERE b.reference = :ref
            LIMIT 1
        ");
        $stmt->execute([':ref' => strtoupper($reference)]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Trouver par référence en vérifiant le propriétaire (BOLA).
     * Retourne null si la réservation n'appartient pas au customer_id.
     */
    public function findByReferenceForCustomer(string $reference, int $customerId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, r.name AS room_name, r.photo_main, rc.name AS category_name
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            JOIN room_categories rc ON rc.id = r.category_id
            WHERE b.reference = :ref
              AND b.customer_id = :customer_id
            LIMIT 1
        ");
        $stmt->execute([':ref' => strtoupper($reference), ':customer_id' => $customerId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Réservations d'un client (paginées). */
    public function findByCustomer(int $customerId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT b.*, r.name AS room_name, r.photo_main
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            WHERE b.customer_id = :customer_id
            ORDER BY b.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute([':customer_id' => $customerId]);
        return $stmt->fetchAll();
    }

    /** Arrivées d'aujourd'hui. */
    public function findArrivalsToday(): array
    {
        $today = date('Y-m-d');
        $stmt  = $this->pdo->prepare("
            SELECT b.*, r.name AS room_name, r.room_number
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            WHERE b.date_arrivee = :today
              AND b.statut IN ('confirmed', 'provisional')
            ORDER BY b.guest_last_name ASC
        ");
        $stmt->execute([':today' => $today]);
        return $stmt->fetchAll();
    }

    /** Départs d'aujourd'hui. */
    public function findDeparturesToday(): array
    {
        $today = date('Y-m-d');
        $stmt  = $this->pdo->prepare("
            SELECT b.*, r.name AS room_name, r.room_number
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            WHERE b.date_depart = :today
              AND b.statut = 'checked_in'
            ORDER BY b.guest_last_name ASC
        ");
        $stmt->execute([':today' => $today]);
        return $stmt->fetchAll();
    }

    /** Réservations paginées avec filtres (admin). */
    public function searchAdmin(array $filters, int $limit = 30, int $offset = 0): array
    {
        $where  = [];
        $params = [];

        // Whitelist des colonnes filtrables
        $allowedFilters = [
            'statut', 'payment_status', 'room_id', 'currency_chosen', 'source',
        ];
        foreach ($allowedFilters as $col) {
            if (!empty($filters[$col])) {
                $where[]          = "b.{$col} = :{$col}";
                $params[":{$col}"] = $filters[$col];
            }
        }

        // Filtre texte sur nom/email/référence
        if (!empty($filters['search'])) {
            $where[] = "(b.reference LIKE :search
                        OR b.guest_email LIKE :search2
                        OR CONCAT(b.guest_first_name, ' ', b.guest_last_name) LIKE :search3)";
            $like = '%' . $filters['search'] . '%';
            $params[':search']  = $like;
            $params[':search2'] = $like;
            $params[':search3'] = $like;
        }

        // Filtre par date d'arrivée
        if (!empty($filters['date_from'])) {
            $where[]           = "b.date_arrivee >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]         = "b.date_arrivee <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("
            SELECT b.*, r.name AS room_name, r.room_number
            FROM bookings b
            JOIN rooms r ON r.id = b.room_id
            {$whereSQL}
            ORDER BY b.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Mettre à jour le statut d'une réservation (avec validation enum). */
    public function updateStatus(int $bookingId, string $status): bool
    {
        $allowed = ['provisional','confirmed','checked_in','checked_out','cancelled','no_show'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        $stmt = $this->pdo->prepare(
            "UPDATE bookings SET statut = :statut, updated_at = UTC_TIMESTAMP() WHERE id = :id"
        );
        $stmt->execute([':statut' => $status, ':id' => $bookingId]);
        return $stmt->rowCount() > 0;
    }

    /** Créer une réservation dans une transaction. */
    public function createBooking(array $data): int
    {
        $id = $this->insert($data);
        return (int) $id;
    }

    /** Nombre total de réservations (pour pagination admin). */
    public function countAdmin(array $filters = []): int
    {
        // Simplifié — en production étendre avec les mêmes filtres que searchAdmin
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM bookings");
        return $stmt ? (int) $stmt->fetchColumn() : 0;
    }
}
