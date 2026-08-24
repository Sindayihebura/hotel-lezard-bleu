<?php
declare(strict_types=1);

use App\Repositories\RoomRepository;

/**
 * Handler GET /api/v1/rooms et GET /api/v1/rooms/{id}
 */
function handleGetRooms(?PDO $pdo): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $repo   = new RoomRepository($pdo);
    $locale = isset($_SESSION['locale']) ? $_SESSION['locale'] : 'fr';
    $rooms  = $repo->findActive();

    foreach ($rooms as &$r) {
        $tr = $repo->getTranslation((int)$r['id'], $locale);
        if ($tr) {
            $r['name']        = $tr['name'];
            $r['description'] = $tr['description'];
        }
        $r['amenities'] = json_decode($r['amenities_json'] ?? '[]', true) ?? [];
        $r['photos']    = json_decode($r['photos_json']    ?? '[]', true) ?? [];
        unset($r['amenities_json'], $r['photos_json'], $r['updated_at']);
    }
    unset($r);

    echo json_success($rooms, ['total' => count($rooms)]);
}

function handleGetRoom(?PDO $pdo, int $id): void
{
    if (!$pdo) { echo json_error('DB_ERROR','Base de données indisponible.',503); return; }

    $repo = new RoomRepository($pdo);
    $room = $repo->findById($id);

    if (!$room || !(bool)$room['is_active']) {
        echo json_error('NOT_FOUND','Chambre introuvable.',404); return;
    }

    $locale = isset($_SESSION['locale']) ? $_SESSION['locale'] : 'fr';
    $tr     = $repo->getTranslation($id, $locale);
    if ($tr) {
        $room['name']        = $tr['name'];
        $room['description'] = $tr['description'];
    }
    $room['amenities'] = json_decode($room['amenities_json'] ?? '[]', true) ?? [];
    $room['photos']    = json_decode($room['photos_json']    ?? '[]', true) ?? [];
    unset($room['amenities_json'], $room['photos_json'], $room['updated_at']);

    echo json_success($room);
}
