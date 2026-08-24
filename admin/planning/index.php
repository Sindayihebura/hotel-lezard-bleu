<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Rooms\RoomService;

$pdo   = getDB();
$guard = new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php');
$guard->requirePermission('reservations.view');
$adminUser = (new AdminAuth($pdo))->user();

$days    = max(7, min(30, (int)($_GET['days'] ?? 14)));
$service = new RoomService($pdo);
$cal     = $service->getOccupancyCalendar($days);
$rooms   = $cal['rooms'];
$bookings= $cal['bookings'];

// Générer les colonnes de dates
$dates = [];
for ($i = 0; $i < $days; $i++) {
    $dates[] = date('Y-m-d', strtotime("+{$i} days"));
}

define('PAGE_TITLE','Planning Hôtelier — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
<style>
.planning-table { width:100%; border-collapse: collapse; font-size:.78rem; }
.planning-table th, .planning-table td { padding:.4rem .5rem; border:1px solid var(--admin-border); text-align:center; white-space:nowrap; }
.planning-table th { background:rgba(212,175,55,.1); color:var(--admin-gold); }
.planning-table td.room-name { text-align:left; font-weight:600; color:var(--admin-text); min-width:160px; }
.cell-occupied { background:rgba(59,130,246,.25); color:#93c5fd; border-radius:4px; }
.cell-checkin  { background:rgba(34,197,94,.25);  color:#86efac; border-radius:4px; }
.cell-checkout { background:rgba(245,158,11,.25); color:#fde68a; border-radius:4px; }
.cell-block    { background:rgba(239,68,68,.2);   color:#fca5a5; border-radius:4px; }
.cell-today    { background:rgba(212,175,55,.08); }
</style>
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header">
  <h1 class="admin-page-title">Planning Hôtelier</h1>
  <div class="admin-header-right">
    <a href="?days=7"  class="btn-admin-sm" style="<?php echo $days===7?'background:var(--admin-gold);color:#070C14;':'';?>">7 jours</a>
    <a href="?days=14" class="btn-admin-sm" style="<?php echo $days===14?'background:var(--admin-gold);color:#070C14;':'';?>">14 jours</a>
    <a href="?days=30" class="btn-admin-sm" style="<?php echo $days===30?'background:var(--admin-gold);color:#070C14;':'';?>">30 jours</a>
    <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
  </div>
</header>

<!-- Légende -->
<div style="display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
  <span style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;"><span class="cell-checkin" style="padding:.2rem .5rem;">▶</span> Arrivée</span>
  <span style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;"><span class="cell-occupied" style="padding:.2rem .5rem;">━</span> En séjour</span>
  <span style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;"><span class="cell-checkout" style="padding:.2rem .5rem;">◀</span> Départ</span>
  <span style="display:flex;align-items:center;gap:.4rem;font-size:.75rem;"><span class="cell-block" style="padding:.2rem .5rem;">✖</span> Maintenance</span>
</div>

<div style="overflow-x:auto;">
<table class="planning-table">
  <thead>
    <tr>
      <th>Chambre</th>
      <?php foreach($dates as $d):
        $isToday = $d === date('Y-m-d');
        $label   = date('j/m', strtotime($d));
        $dayName = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'][(int)date('N',strtotime($d))-1];
      ?>
      <th style="<?php echo $isToday?'background:rgba(212,175,55,.2);color:var(--admin-gold);':'';?>">
        <?php echo $dayName.'<br>'.$label;?>
      </th>
      <?php endforeach;?>
    </tr>
  </thead>
  <tbody>
    <?php foreach($rooms as $room):
      $roomBookings = $bookings[(int)$room['id']] ?? [];
    ?>
    <tr>
      <td class="room-name">
        <?php echo e($room['name']);?>
        <?php if($room['room_number']): ?><span style="color:var(--admin-muted);font-size:.7rem;"> #<?php echo e($room['room_number']);?></span><?php endif;?>
      </td>
      <?php foreach($dates as $d):
        $cellClass = '';
        $cellTitle = '';
        $cellText  = '';
        foreach($roomBookings as $b) {
            if ($d === $b['date_arrivee']) {
                $cellClass = 'cell-checkin';
                $cellTitle = $b['guest_first_name'].' '.$b['guest_last_name'];
                $cellText  = '▶';
                break;
            } elseif ($d === $b['date_depart']) {
                $cellClass = 'cell-checkout';
                $cellText  = '◀';
                break;
            } elseif ($d > $b['date_arrivee'] && $d < $b['date_depart']) {
                $cellClass = 'cell-occupied';
                $cellText  = '━';
                break;
            }
        }
        $isToday = $d === date('Y-m-d');
      ?>
      <td class="<?php echo $isToday?'cell-today':'';?>"
          title="<?php echo e($cellTitle);?>"
          style="<?php echo $cellText?'font-size:.85rem;':'color:rgba(255,255,255,.1);';?>">
        <?php echo $cellText ?: '·';?>
      </td>
      <?php endforeach;?>
    </tr>
    <?php endforeach;?>
  </tbody>
</table>
</div>

<div style="margin-top:1.5rem;display:flex;gap:1rem;">
  <a href="/admin/reservations/create.php" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;">+ Nouvelle Réservation</a>
  <a href="/admin/maintenance/" class="btn-admin-sm">🔧 Maintenance</a>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
