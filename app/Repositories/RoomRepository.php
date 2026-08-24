<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * RoomRepository — Accès aux données chambres
 * Hôtel Le Lézard Bleu & Spa
 */
class RoomRepository extends BaseRepository
{
    protected string $table = 'rooms';

    protected function getAllowedColumns(): array
    {
        return [
            'id','category_id','room_number','slug','name','description',
            'price_per_night_bif','capacity_adults','capacity_children',
            'surface_m2','floor','view','photo_main','photos_json',
            'amenities_json','is_active','sort_order',
        ];
    }

    // ── Requêtes métier ───────────────────────────────────────────────

    /** Toutes les chambres actives ordonnées. */
    public function findActive(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, rc.name AS category_name
            FROM rooms r
            JOIN room_categories rc ON rc.id = r.category_id
            WHERE r.is_active = 1
            ORDER BY r.sort_order ASC, r.id ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /** Chambres disponibles pour une période donnée et une capacité minimale. */
    public function findAvailable(string $dateArrivee, string $dateDepart, int $nbAdults = 1): array
    {
        // Requête de disponibilité — double protection contre les doublons :
        // 1. Réservations confirmées/en cours
        // 2. Blocages de maintenance
        $stmt = $this->pdo->prepare("
            SELECT r.*, rc.name AS category_name
            FROM rooms r
            JOIN room_categories rc ON rc.id = r.category_id
            WHERE r.is_active = 1
              AND r.capacity_adults >= :nb_adults
              AND r.id NOT IN (
                -- Chambres réservées sur la période
                SELECT b.room_id FROM bookings b
                WHERE b.statut NOT IN ('cancelled', 'no_show')
                  AND b.date_arrivee < :dep1
                  AND b.date_depart  > :arr1
              )
              AND r.id NOT IN (
                -- Chambres bloquées (maintenance)
                SELECT rb.room_id FROM room_blocks rb
                WHERE rb.resolved_at IS NULL
                  AND rb.start_date < :dep2
                  AND rb.end_date   > :arr2
              )
            ORDER BY r.sort_order ASC
        ");
        $stmt->execute([
            ':nb_adults' => $nbAdults,
            ':arr1'      => $dateArrivee,
            ':dep1'      => $dateDepart,
            ':arr2'      => $dateArrivee,
            ':dep2'      => $dateDepart,
        ]);
        return $stmt->fetchAll();
    }

    /** Trouver par slug. */
    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, rc.name AS category_name
            FROM rooms r
            JOIN room_categories rc ON rc.id = r.category_id
            WHERE r.slug = :slug AND r.is_active = 1
            LIMIT 1
        ");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Vérifier qu'une chambre est bien disponible sur une période (pour BOLA). */
    public function isAvailable(int $roomId, string $dateArrivee, string $dateDepart, ?int $excludeBookingId = null): bool
    {
        $excludeSql = $excludeBookingId !== null
            ? "AND b.id != :excl"
            : "";

        $sql = "
            SELECT COUNT(*) FROM bookings b
            WHERE b.room_id = :room_id
              AND b.statut NOT IN ('cancelled', 'no_show')
              AND b.date_arrivee < :dep
              AND b.date_depart  > :arr
              {$excludeSql}
        ";
        $stmt = $this->pdo->prepare($sql);
        $params = [
            ':room_id' => $roomId,
            ':arr'     => $dateArrivee,
            ':dep'     => $dateDepart,
        ];
        if ($excludeBookingId !== null) {
            $params[':excl'] = $excludeBookingId;
        }
        $stmt->execute($params);
        return ((int) $stmt->fetchColumn()) === 0;
    }

    /** Traduction d'une chambre dans une locale. */
    public function getTranslation(int $roomId, string $locale): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT name, description FROM room_translations
            WHERE room_id = :room_id AND locale = :locale
            LIMIT 1
        ");
        $stmt->execute([':room_id' => $roomId, ':locale' => $locale]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** Mettre à jour le statut actif d'une chambre. */
    public function setActive(int $roomId, bool $active): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE rooms SET is_active = :active WHERE id = :id"
        );
        $stmt->execute([':active' => (int) $active, ':id' => $roomId]);
    }
}
