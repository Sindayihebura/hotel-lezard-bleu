<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/config/bootstrap.php';
require_once dirname(__DIR__,2) . '/config/database.php';

use App\Auth\AdminAuth;
use App\Auth\RbacGuard;
use App\Repositories\BookingRepository;
use App\Payments\CurrencyService;

$pdo   = getDB();
$guard = new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php');
$guard->requirePermission('reservations.view');
$adminUser = (new AdminAuth($pdo))->user();

$page    = max(1,(int)($_GET['page'] ?? 1));
$limit   = 25;
$offset  = ($page - 1) * $limit;

$filters = array_filter([
    'statut'   => $_GET['statut']   ?? '',
    'search'   => $_GET['q']        ?? '',
    'date_from'=> $_GET['date_from']?? '',
    'date_to'  => $_GET['date_to']  ?? '',
],'strlen');

$repo     = new BookingRepository($pdo);
$bookings = $repo->searchAdmin($filters, $limit, $offset);
$total    = $repo->countAdmin($filters);
$pages    = max(1, (int)ceil($total/$limit));
$currency = new CurrencyService($pdo);

define('PAGE_TITLE','Réservations — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE; ?></title>
<meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="admin-main">
  <header class="admin-header">
    <h1 class="admin-page-title">Réservations</h1>
    <div class="admin-header-right">
      <?php if ($guard->can('reservations.create')): ?>
        <a href="/admin/reservations/create.php" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;">+ Nouvelle</a>
      <?php endif; ?>
      <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
    </div>
  </header>

  <!-- Filtres -->
  <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:flex-end;">
    <input type="text" name="q" value="<?php echo e($_GET['q']??''); ?>"
           placeholder="Référence, nom, email…" class="admin-form-input" style="width:220px;padding:.5rem .75rem;font-size:.85rem;">
    <select name="statut" class="admin-form-select" style="padding:.5rem .75rem;font-size:.85rem;">
      <option value="">Tous les statuts</option>
      <?php foreach(['provisional'=>'Provisoire','confirmed'=>'Confirmé','checked_in'=>'En cours','checked_out'=>'Terminé','cancelled'=>'Annulé','no_show'=>'No Show'] as $v=>$l): ?>
        <option value="<?php echo $v; ?>" <?php echo (($_GET['statut']??'')===$v)?'selected':''; ?>><?php echo $l; ?></option>
      <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?php echo e($_GET['date_from']??''); ?>" class="admin-form-input" style="padding:.5rem .75rem;font-size:.85rem;">
    <input type="date" name="date_to"   value="<?php echo e($_GET['date_to']??''); ?>"   class="admin-form-input" style="padding:.5rem .75rem;font-size:.85rem;">
    <button type="submit" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;">Filtrer</button>
    <a href="/admin/reservations/" class="btn-admin-sm">Réinitialiser</a>
  </form>

  <!-- Total -->
  <p style="color:var(--admin-muted);font-size:.8rem;margin-bottom:1rem;"><?php echo $total; ?> réservation(s) trouvée(s)</p>

  <div class="admin-table-wrapper">
    <table class="admin-table">
      <thead><tr>
        <th>Référence</th><th>Client</th><th>Chambre</th>
        <th>Arrivée</th><th>Nuits</th><th>Total</th>
        <th>Paiement</th><th>Statut</th><th>Actions</th>
      </tr></thead>
      <tbody>
      <?php if(empty($bookings)): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--admin-muted);padding:2rem;">Aucune réservation trouvée.</td></tr>
      <?php else: foreach($bookings as $b):
        $statutClass = ['confirmed'=>'badge-success','provisional'=>'badge-warning','cancelled'=>'badge-danger','checked_in'=>'badge-info','checked_out'=>'badge-muted','no_show'=>'badge-danger'];
        $payClass    = ['paid'=>'badge-success','unpaid'=>'badge-warning','partial'=>'badge-warning','refunded'=>'badge-muted'];
      ?>
      <tr>
        <td><a href="/admin/reservations/view.php?id=<?php echo $b['id']; ?>" style="color:var(--admin-gold);font-family:monospace;font-size:.8rem;"><?php echo e($b['reference']); ?></a></td>
        <td style="font-size:.85rem;"><?php echo e($b['guest_first_name'].' '.$b['guest_last_name']); ?><br><span style="color:var(--admin-muted);font-size:.75rem;"><?php echo e($b['guest_email']); ?></span></td>
        <td style="font-size:.85rem;"><?php echo e($b['room_name']); ?></td>
        <td style="font-size:.85rem;"><?php echo e(format_date_fr($b['date_arrivee'])); ?></td>
        <td style="text-align:center;"><?php echo $b['nb_nights']; ?></td>
        <td style="font-size:.85rem;"><?php echo $currency->formatBif((int)$b['total_bif']); ?></td>
        <td><span class="badge <?php echo $payClass[$b['payment_status']]??'badge-muted'; ?>"><?php echo e(ucfirst($b['payment_status'])); ?></span></td>
        <td><span class="badge <?php echo $statutClass[$b['statut']]??'badge-muted'; ?>"><?php echo e(ucfirst($b['statut'])); ?></span></td>
        <td>
          <a href="/admin/reservations/view.php?id=<?php echo $b['id']; ?>" class="btn-admin-sm">Voir</a>
          <?php if($guard->can('reservations.update') && $b['statut']==='provisional'): ?>
            <a href="/admin/reservations/action.php?id=<?php echo $b['id']; ?>&action=confirm" class="btn-admin-sm" style="color:#22c55e;border-color:#22c55e;">Confirmer</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if($pages > 1): ?>
  <div style="display:flex;gap:.5rem;margin-top:1.25rem;justify-content:center;">
    <?php for($i=max(1,$page-2); $i<=min($pages,$page+2); $i++): ?>
      <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET,['page'=>1])); ?>"
         class="btn-admin-sm" style="<?php echo $i===$page?'background:var(--admin-gold);color:#070C14;':''; ?>"><?php echo $i; ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
