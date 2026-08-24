<?php

declare(strict_types=1);

namespace App\Rooms;

use PDO;
use App\Repositories\RoomRepository;
use App\Security\Logger;

/**
 * RoomService — Gestion des chambres et maintenance
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 */
class RoomService
{
    private PDO            $pdo;
    private RoomRepository $rooms;
    private Logger         $logger;

    public function __construct(PDO $pdo)
    {
        $this->pdo    = $pdo;
        $this->rooms  = new RoomRepository($pdo);
        $this->logger = new Logger($pdo);
    }

    /**
     * Obtenir toutes les chambres actives avec traduction.
     */
    public function getActiveRooms(string $locale = 'fr'): array
    {
        $rooms = $this->rooms->findActive();
        foreach ($rooms as &$room) {
            $tr = $this->rooms->getTranslation((int)$room['id'], $locale);
            if ($tr) {
                $room['name']        = $tr['name'];
                $room['description'] = $tr['description'];
            }
            $room['amenities'] = json_decode($room['amenities_json'] ?? '[]', true) ?? [];
            $room['photos']    = json_decode($room['photos_json']    ?? '[]', true) ?? [];
            unset($room['amenities_json'], $room['photos_json']);
        }
        return $rooms;
    }

    /**
     * Obtenir une chambre par ID avec traduction.
     */
    public function getRoomById(int $id, string $locale = 'fr'): ?array
    {
        $room = $this->rooms->findById($id);
        if (!$room || !(bool)$room['is_active']) {
            return null;
        }
        $tr = $this->rooms->getTranslation($id, $locale);
        if ($tr) {
            $room['name']        = $tr['name'];
            $room['description'] = $tr['description'];
        }
        $room['amenities'] = json_decode($room['amenities_json'] ?? '[]', true) ?? [];
        $room['photos']    = json_decode($room['photos_json']    ?? '[]', true) ?? [];
        return $room;
    }

    /**
     * Mettre à jour le prix d'une chambre.
     * Log systématique du changement de prix.
     */
    public function updatePrice(int $roomId, int $newPriceBif, int $adminId): bool
    {
        $room = $this->rooms->findById($roomId);
        if (!$room) {
            return false;
        }

        $oldPrice = (int)$room['price_per_night_bif'];
        $stmt     = $this->pdo->prepare(
            "UPDATE rooms SET price_per_night_bif = :p, updated_at = UTC_TIMESTAMP() WHERE id = :id"
        );
        $stmt->execute([':p' => $newPriceBif, ':id' => $roomId]);

        $this->logger->audit(
            Logger::ACTION_PRICE_CHANGED, 'room', $roomId,
            ['price_per_night_bif' => $oldPrice],
            ['price_per_night_bif' => $newPriceBif],
            $adminId
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Créer un blocage de maintenance.
     */
    public function blockRoom(int $roomId, string $from, string $to, string $reason, int $adminId, string $notes = ''): bool
    {
        if ($to <= $from) {
            return false;
        }
        $this->pdo->prepare("
            INSERT INTO room_blocks (room_id, reason, notes, start_date, end_date, created_by)
            VALUES (:room_id, :reason, :notes, :from, :to, :admin)
        ")->execute([
            ':room_id' => $roomId,
            ':reason'  => $reason,
            ':notes'   => $notes,
            ':from'    => $from,
            ':to'      => $to,
            ':admin'   => $adminId,
        ]);
        return true;
    }

    /**
     * Résoudre un blocage.
     */
    public function resolveBlock(int $blockId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE room_blocks SET resolved_at = UTC_TIMESTAMP() WHERE id = :id AND resolved_at IS NULL"
        );
        $stmt->execute([':id' => $blockId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Planning d'occupation pour les 30 prochains jours.
     */
    public function getOccupancyCalendar(int $days = 30): array
    {
        $today = date('Y-m-d');
        $end   = date('Y-m-d', strtotime("+{$days} days"));

        $stmt = $this->pdo->prepare("
            SELECT b.room_id, b.date_arrivee, b.date_depart, b.reference,
                   b.guest_first_name, b.guest_last_name, b.statut
            FROM bookings b
            WHERE b.statut NOT IN ('cancelled','no_show')
              AND b.date_depart > :today
              AND b.date_arrivee < :end
            ORDER BY b.date_arrivee ASC
        ");
        $stmt->execute([':today' => $today, ':end' => $end]);
        $bookings = $stmt->fetchAll();

        // Indexer par room_id pour affichage calendrier
        $calendar = [];
        foreach ($bookings as $b) {
            $calendar[$b['room_id']][] = $b;
        }

        return [
            'from'     => $today,
            'to'       => $end,
            'rooms'    => $this->rooms->findActive(),
            'bookings' => $calendar,
        ];
    }
}
