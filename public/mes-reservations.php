<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AuthService;
use App\Repositories\BookingRepository;
use App\Payments\CurrencyService;

$pdo  = getDB();
$auth = new AuthService($pdo);
if (!$auth->check()) { safe_redirect('/public/connexion.php?redirect=/public/mes-reservations.php'); }

$user     = $auth->user();
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 10;
$offset   = ($page - 1) * $limit;
$repo     = new BookingRepository($pdo);
$bookings = $repo->findByCustomer((int)$user['id'], $limit, $offset);
$currency = new CurrencyService($pdo);

define('PAGE_TITLE', 'Mes Réservations — Le Lézard Bleu');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section style="min-height:80vh;padding:5rem 1rem;background:var(--bg-dark-main);">
<div class="container" style="max-width:950px;margin:0 auto;">

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
      <span class="section-subtitle">ESPACE CLIENT</span>
      <h1 class="section-title" style="font-size:1.8rem;margin:0;">Mes Réservations</h1>
    </div>
    <div style="display:flex;gap:.75rem;">
      <a href="/public/mon-compte.php" style="font-size:.85rem;color:var(--accent-gold-primary);">← Mon Compte</a>
      <a href="/reservation.php" class="btn btn-gold" style="padding:.6rem 1.2rem;font-size:.85rem;">Nouvelle Réservation</a>
    </div>
  </div>

  <?php if (empty($bookings)): ?>
    <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-md);padding:3rem;text-align:center;">
      <div style="font-size:2.5rem;margin-bottom:1rem;">📋</div>
      <p style="color:var(--text-muted);">Aucune réservation pour le moment.</p>
      <a href="/reservation.php" class="btn btn-gold" style="margin-top:1.25rem;padding:.75rem 2rem;">Réserver maintenant</a>
    </div>
  <?php else: ?>
    <?php foreach ($bookings as $b):
      $statutColors=['confirmed'=>'#22c55e','provisional'=>'#f59e0b','cancelled'=>'#ef4444','checked_in'=>'#3b82f6','checked_out'=>'#94a3b8'];
      $statutLabels=['confirmed'=>'Confirmée','provisional'=>'En attente de paiement','cancelled'=>'Annulée','checked_in'=>'En cours de séjour','checked_out'=>'Séjour terminé'];
    ?>
    <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-md);padding:1.5rem;margin-bottom:1.25rem;display:grid;grid-template-columns:1fr auto;gap:1rem;align-items:start;">
      <div>
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:.75rem;flex-wrap:wrap;">
          <span style="font-family:monospace;color:var(--accent-gold-primary);font-size:.95rem;font-weight:700;"><?php echo e($b['reference']); ?></span>
          <span style="color:<?php echo $statutColors[$b['statut']]??'#94a3b8'; ?>;font-size:.75rem;font-weight:600;background:rgba(255,255,255,.05);padding:.2rem .6rem;border-radius:20px;">
            <?php echo e($statutLabels[$b['statut']] ?? $b['statut']); ?>
          </span>
        </div>
        <p style="color:var(--text-light-primary);font-weight:600;margin-bottom:.4rem;font-size:1rem;"><?php echo e($b['room_name']); ?></p>
        <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.3rem;">
          📅 <?php echo e(format_date_fr($b['date_arrivee'])); ?> → <?php echo e(format_date_fr($b['date_depart'])); ?>
          (<?php echo e($b['nb_nights']); ?> nuit<?php echo $b['nb_nights']>1?'s':''; ?>)
        </p>
        <p style="color:var(--text-muted);font-size:.85rem;">
          👤 <?php echo e($b['nb_adults']); ?> adulte<?php echo $b['nb_adults']>1?'s':''; ?>
          <?php if ($b['nb_children']>0): ?> · <?php echo e($b['nb_children']); ?> enfant<?php echo $b['nb_children']>1?'s':''; ?><?php endif; ?>
          · <?php echo $currency->formatBif((int)$b['total_bif']); ?>
        </p>
      </div>
      <div style="text-align:right;">
        <?php if (in_array($b['statut'],['provisional','confirmed'])): ?>
          <form method="POST" action="/api/create_booking_cancel.php"
                onsubmit="return confirm('Confirmer l\'annulation ?')"
                style="margin-top:.5rem;">
            <input type="hidden" name="reference" value="<?php echo e($b['reference']); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <button type="submit" style="background:none;border:1px solid #ef4444;color:#ef4444;padding:.4rem .8rem;border-radius:6px;font-size:.75rem;cursor:pointer;">
              Annuler
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Pagination -->
    <?php if (count($bookings) === $limit): ?>
    <div style="text-align:center;margin-top:1.5rem;">
      <a href="?page=<?php echo $page+1; ?>" class="btn btn-outline-gold" style="padding:.6rem 1.5rem;font-size:.85rem;">
        Page suivante →
      </a>
    </div>
    <?php endif; ?>
  <?php endif; ?>

</div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
