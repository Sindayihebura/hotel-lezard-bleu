<?php
declare(strict_types=1);
require_once dirname(__DIR__,2) . '/config/bootstrap.php';
require_once dirname(__DIR__,2) . '/config/database.php';

use App\Auth\AdminAuth;
use App\Auth\RbacGuard;
use App\Repositories\BookingRepository;
use App\Repositories\PaymentRepository;
use App\Booking\BookingService;
use App\Payments\CurrencyService;
use App\Security\CsrfGuard;

$pdo   = getDB();
$guard = new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php');
$guard->requirePermission('reservations.view');
$adminUser = (new AdminAuth($pdo))->user();
$adminId   = (int)$adminUser['id'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location:/admin/reservations/'); exit; }

$repo     = new BookingRepository($pdo);
$booking  = $repo->findById($id);
if (!$booking) { header('Location:/admin/reservations/'); exit; }

$payRepo  = new PaymentRepository($pdo);
$payments = $payRepo->findByBooking($id);
$currency = new CurrencyService($pdo);

// Traiter les actions POST
$actionMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfGuard::verifyRequest()) {
        $actionMsg = '⚠️ Jeton de sécurité invalide.';
    } else {
        $action  = $_POST['action'] ?? '';
        $service = new BookingService($pdo);
        $ok = match($action) {
            'confirm'   => $guard->can('reservations.update')   ? $service->confirm($id) : false,
            'checkin'   => $guard->can('reservations.checkin')  ? $service->checkin($id,$adminId) : false,
            'checkout'  => $guard->can('reservations.checkout') ? $service->checkout($id,$adminId) : false,
            'cancel'    => $guard->can('reservations.cancel')   ? $service->cancel($id, trim($_POST['reason']??'Annulation admin'), 'admin', $adminId) : false,
            default     => false,
        };
        $actionMsg = $ok ? '✓ Action effectuée avec succès.' : '✗ Échec ou permission insuffisante.';
        $booking   = $repo->findById($id); // Recharger
    }
}

$csrfField = CsrfGuard::field();
define('PAGE_TITLE','Réservation #'.e($booking['reference']).' — Admin');
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
    <h1 class="admin-page-title">
      <a href="/admin/reservations/" style="color:var(--admin-muted);font-size:.9rem;font-weight:400;">Réservations</a>
      → <?php echo e($booking['reference']); ?>
    </h1>
    <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
  </header>

  <?php if ($actionMsg): ?>
    <div style="padding:.9rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;font-size:.9rem;
        background:<?php echo str_starts_with($actionMsg,'✓')?'rgba(34,197,94,.12)':'rgba(239,68,68,.12)'; ?>;
        border:1px solid <?php echo str_starts_with($actionMsg,'✓')?'#22c55e':'#ef4444'; ?>;
        color:<?php echo str_starts_with($actionMsg,'✓')?'#86efac':'#fca5a5'; ?>;">
      <?php echo e($actionMsg); ?>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

    <!-- Infos réservation -->
    <div class="admin-form-card">
      <div class="admin-form-title">Détails de la Réservation</div>
      <?php
      $rows = [
        'Référence'    => $booking['reference'],
        'Chambre'      => $booking['room_name'],
        'Arrivée'      => format_date_fr($booking['date_arrivee']),
        'Départ'       => format_date_fr($booking['date_depart']),
        'Nuits'        => $booking['nb_nights'],
        'Adultes'      => $booking['nb_adults'],
        'Enfants'      => $booking['nb_children'],
        'Total BIF'    => $currency->formatBif((int)$booking['total_bif']),
        'Taux utilisé' => '1 USD = '.number_format((float)$booking['exchange_rate_used']).' BIF',
        'Devise choisie'=> $booking['currency_chosen'],
        'Source'       => $booking['source'],
        'Créé le'      => date('d/m/Y H:i', strtotime($booking['created_at'])),
      ];
      foreach($rows as $lbl=>$val): ?>
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--admin-border);font-size:.85rem;">
          <span style="color:var(--admin-muted);"><?php echo $lbl; ?></span>
          <span style="color:var(--admin-text);font-weight:500;"><?php echo e((string)$val); ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Client + Statuts + Actions -->
    <div>
      <div class="admin-form-card" style="margin-bottom:1.5rem;">
        <div class="admin-form-title">Client</div>
        <?php
        $cRows = [
          'Nom'      => $booking['guest_first_name'].' '.$booking['guest_last_name'],
          'Email'    => $booking['guest_email'],
          'Téléphone'=> $booking['guest_phone'],
        ];
        foreach($cRows as $l=>$v): ?>
          <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid var(--admin-border);font-size:.85rem;">
            <span style="color:var(--admin-muted);"><?php echo $l; ?></span>
            <span style="color:var(--admin-text);"><?php echo e($v); ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Actions -->
      <div class="admin-form-card">
        <div class="admin-form-title">Actions</div>
        <form method="POST" style="display:flex;flex-direction:column;gap:.75rem;">
          <?php echo $csrfField; ?>
          <?php if($booking['statut']==='provisional' && $guard->can('reservations.update')): ?>
            <button name="action" value="confirm" class="btn-admin-sm" style="background:#22c55e;color:#000;border:none;cursor:pointer;padding:.7rem;">✓ Confirmer</button>
          <?php endif; ?>
          <?php if($booking['statut']==='confirmed' && $guard->can('reservations.checkin')): ?>
            <button name="action" value="checkin" class="btn-admin-sm" style="background:#3b82f6;color:#fff;border:none;cursor:pointer;padding:.7rem;">Check-in</button>
          <?php endif; ?>
          <?php if($booking['statut']==='checked_in' && $guard->can('reservations.checkout')): ?>
            <button name="action" value="checkout" class="btn-admin-sm" style="background:var(--admin-gold);color:#000;border:none;cursor:pointer;padding:.7rem;">Check-out</button>
          <?php endif; ?>
          <?php if(in_array($booking['statut'],['provisional','confirmed']) && $guard->can('reservations.cancel')): ?>
            <input type="text" name="reason" placeholder="Raison de l'annulation…" class="admin-form-input" style="padding:.5rem .75rem;font-size:.85rem;">
            <button name="action" value="cancel" class="btn-admin-sm" style="background:#ef4444;color:#fff;border:none;cursor:pointer;padding:.7rem;"
                    onclick="return confirm('Confirmer l\'annulation ?')">✗ Annuler la réservation</button>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>

  <!-- Paiements -->
  <div class="admin-form-card">
    <div class="admin-form-title">Paiements</div>
    <?php if(empty($payments)): ?>
      <p style="color:var(--admin-muted);font-size:.85rem;">Aucun paiement enregistré.</p>
    <?php else: ?>
      <table class="admin-table">
        <thead><tr><th>ID</th><th>Fournisseur</th><th>Montant</th><th>Statut</th><th>Date</th><?php if($guard->can('payments.confirm')): ?><th>Action</th><?php endif; ?></tr></thead>
        <tbody>
        <?php foreach($payments as $p):
          $pClass = ['successful'=>'badge-success','pending_customer'=>'badge-warning','failed'=>'badge-danger','refunded'=>'badge-muted','expired'=>'badge-muted'];
        ?>
          <tr>
            <td style="font-size:.8rem;">#<?php echo $p['id']; ?></td>
            <td style="font-size:.85rem;"><?php echo e(ucfirst($p['provider'])); ?></td>
            <td style="font-size:.85rem;"><?php echo $currency->formatBif((int)$p['amount_bif']); ?></td>
            <td><span class="badge <?php echo $pClass[$p['payment_status']]??'badge-muted'; ?>"><?php echo e($p['payment_status']); ?></span></td>
            <td style="font-size:.8rem;"><?php echo date('d/m/Y H:i',strtotime($p['created_at'])); ?></td>
            <?php if($guard->can('payments.confirm')): ?>
              <td>
                <?php if(in_array($p['payment_status'],['pending_customer','initiated'])): ?>
                  <a href="/admin/payments/confirm.php?id=<?php echo $p['id']; ?>" class="btn-admin-sm" style="color:#22c55e;border-color:#22c55e;">Confirmer</a>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</main>
<script src="/assets/js/admin.js"></script>
</body></html>
