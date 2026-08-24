<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/config/bootstrap.php';
require_once dirname(__DIR__,2) . '/config/database.php';

use App\Auth\AdminAuth;
use App\Auth\RbacGuard;
use App\Payments\CurrencyService;

$pdo   = getDB();
$guard = new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php');
$guard->requirePermission('payments.view');
$adminUser = (new AdminAuth($pdo))->user();

$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 25;
$offset = ($page - 1) * $limit;
$status = $_GET['status'] ?? '';

$where  = $status ? "WHERE p.payment_status = :s" : "";
$params = $status ? [':s' => $status] : [];

$stmt = $pdo->prepare("
    SELECT p.*, b.reference, b.guest_first_name, b.guest_last_name
    FROM payments p
    JOIN bookings b ON b.id = p.booking_id
    $where
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

$currency = new CurrencyService($pdo);
define('PAGE_TITLE','Paiements — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE; ?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">
<?php require_once dirname(__DIR__) . '/includes/sidebar.php'; ?>
<main class="admin-main">
  <header class="admin-header">
    <h1 class="admin-page-title">Paiements</h1>
    <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
  </header>

  <!-- Filtres statut -->
  <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
    <?php foreach([''=> 'Tous','pending_customer'=>'En attente','successful'=>'Confirmés','failed'=>'Échoués','expired'=>'Expirés','refunded'=>'Remboursés'] as $v=>$l): ?>
      <a href="?status=<?php echo $v; ?>" class="btn-admin-sm" style="<?php echo $status===$v?'background:var(--admin-gold);color:#070C14;':''; ?>"><?php echo $l; ?></a>
    <?php endforeach; ?>
  </div>

  <div class="admin-table-wrapper">
    <table class="admin-table">
      <thead><tr><th>ID</th><th>Réservation</th><th>Client</th><th>Fournisseur</th><th>Montant</th><th>Statut</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(empty($payments)): ?>
        <tr><td colspan="8" style="text-align:center;color:var(--admin-muted);padding:2rem;">Aucun paiement.</td></tr>
      <?php else: foreach($payments as $p):
        $pClass=['successful'=>'badge-success','pending_customer'=>'badge-warning','processing'=>'badge-info','failed'=>'badge-danger','expired'=>'badge-muted','refunded'=>'badge-muted','cancelled'=>'badge-muted'];
      ?>
        <tr>
          <td style="font-family:monospace;">#<?php echo $p['id']; ?></td>
          <td><a href="/admin/reservations/view.php?id=<?php echo $p['booking_id']; ?>" style="color:var(--admin-gold);font-size:.8rem;"><?php echo e($p['reference']); ?></a></td>
          <td style="font-size:.85rem;"><?php echo e($p['guest_first_name'].' '.$p['guest_last_name']); ?></td>
          <td style="font-size:.85rem;"><?php echo e(ucfirst($p['provider'])); ?></td>
          <td style="font-size:.85rem;"><?php echo $currency->formatBif((int)$p['amount_bif']); ?></td>
          <td><span class="badge <?php echo $pClass[$p['payment_status']]??'badge-muted'; ?>"><?php echo e($p['payment_status']); ?></span></td>
          <td style="font-size:.8rem;"><?php echo date('d/m/Y H:i',strtotime($p['created_at'])); ?></td>
          <td>
            <?php if($guard->can('payments.confirm') && in_array($p['payment_status'],['pending_customer','initiated'])): ?>
              <a href="/admin/payments/confirm.php?id=<?php echo $p['id']; ?>" class="btn-admin-sm" style="color:#22c55e;border-color:#22c55e;font-size:.75rem;">Confirmer</a>
            <?php endif; ?>
            <?php if($guard->can('payments.refund') && $p['payment_status']==='successful'): ?>
              <a href="/admin/payments/refund.php?id=<?php echo $p['id']; ?>" class="btn-admin-sm" style="color:#f59e0b;border-color:#f59e0b;font-size:.75rem;">Rembourser</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
