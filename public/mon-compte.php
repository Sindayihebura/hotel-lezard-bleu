<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AuthService;
use App\Repositories\BookingRepository;
use App\Repositories\CustomerRepository;
use App\Payments\CurrencyService;

$pdo = getDB();
$auth = new AuthService($pdo);
if (!$auth->check()) {
    safe_redirect('/public/connexion.php?redirect=/public/mon-compte.php');
}

$user     = $auth->user();
$bookings = [];
$invoices = [];

if ($pdo) {
    $bRepo    = new BookingRepository($pdo);
    $bookings = $bRepo->findByCustomer((int)$user['id'], 5, 0);
    $stmt     = $pdo->prepare("
        SELECT i.* FROM invoices i
        JOIN bookings b ON b.id = i.booking_id
        WHERE b.customer_id = :cid
        ORDER BY i.created_at DESC LIMIT 5
    ");
    $stmt->execute([':cid' => $user['id']]);
    $invoices = $stmt->fetchAll();
}

$locale   = $_SESSION['locale'] ?? 'fr';
$currency = new CurrencyService($pdo);

define('PAGE_TITLE', 'Mon Compte — Le Lézard Bleu Hôtel & Spa');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section style="min-height:80vh;padding:5rem 1rem;background:var(--bg-dark-main);">
<div class="container" style="max-width:900px;margin:0 auto;">

  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:2.5rem;">
    <div>
      <span class="section-subtitle">ESPACE CLIENT</span>
      <h1 class="section-title" style="font-size:1.8rem;margin:0;">
        Bienvenue, <?php echo e($user['first_name']); ?> !
      </h1>
    </div>
    <a href="/public/deconnexion.php" class="btn btn-outline-gold" style="padding:.6rem 1.2rem;font-size:.85rem;">
      Déconnexion
    </a>
  </div>

  <!-- Infos profil -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2.5rem;">
    <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-md);padding:1.75rem;">
      <h2 style="color:var(--accent-gold-primary);font-size:1rem;margin-bottom:1.25rem;">Mon Profil</h2>
      <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.5rem;">Nom</p>
      <p style="color:var(--text-light-primary);margin-bottom:1rem;"><?php echo e($user['first_name'].' '.$user['last_name']); ?></p>
      <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.5rem;">Email</p>
      <p style="color:var(--text-light-primary);margin-bottom:1rem;"><?php echo e($user['email']); ?></p>
      <?php if ($user['phone']): ?>
      <p style="color:var(--text-muted);font-size:.85rem;margin-bottom:.5rem;">Téléphone</p>
      <p style="color:var(--text-light-primary);"><?php echo e($user['phone']); ?></p>
      <?php endif; ?>
    </div>
    <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-md);padding:1.75rem;">
      <h2 style="color:var(--accent-gold-primary);font-size:1rem;margin-bottom:1.25rem;">Actions Rapides</h2>
      <div style="display:flex;flex-direction:column;gap:.75rem;">
        <a href="/reservation.php" class="btn btn-gold" style="padding:.7rem;font-size:.9rem;text-align:center;">Nouvelle Réservation</a>
        <a href="/public/mes-reservations.php" class="btn btn-outline-gold" style="padding:.7rem;font-size:.9rem;text-align:center;">Mes Réservations</a>
        <a href="/public/mot-de-passe-oublie.php" class="btn btn-outline-gold" style="padding:.7rem;font-size:.9rem;text-align:center;">Changer Mot de Passe</a>
      </div>
    </div>
  </div>

  <!-- Dernières réservations -->
  <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-md);padding:1.75rem;margin-bottom:2rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
      <h2 style="color:var(--accent-gold-primary);font-size:1rem;margin:0;">Dernières Réservations</h2>
      <a href="/public/mes-reservations.php" style="font-size:.8rem;color:var(--accent-gold-primary);">Voir tout →</a>
    </div>
    <?php if (empty($bookings)): ?>
      <p style="color:var(--text-muted);font-size:.9rem;">Aucune réservation pour le moment.</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.85rem;">
          <thead>
            <tr style="border-bottom:1px solid var(--border-gold);">
              <th style="text-align:left;padding:.6rem .5rem;color:var(--text-muted);">Référence</th>
              <th style="text-align:left;padding:.6rem .5rem;color:var(--text-muted);">Chambre</th>
              <th style="text-align:left;padding:.6rem .5rem;color:var(--text-muted);">Arrivée</th>
              <th style="text-align:left;padding:.6rem .5rem;color:var(--text-muted);">Total</th>
              <th style="text-align:left;padding:.6rem .5rem;color:var(--text-muted);">Statut</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
            <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
              <td style="padding:.75rem .5rem;color:var(--accent-gold-primary);font-family:monospace;"><?php echo e($b['reference']); ?></td>
              <td style="padding:.75rem .5rem;color:var(--text-light-primary);"><?php echo e($b['room_name']); ?></td>
              <td style="padding:.75rem .5rem;color:var(--text-light-secondary);"><?php echo e(format_date_fr($b['date_arrivee'])); ?></td>
              <td style="padding:.75rem .5rem;color:var(--text-light-primary);"><?php echo $currency->formatBif((int)$b['total_bif']); ?></td>
              <td style="padding:.75rem .5rem;">
                <?php
                $statutColors = ['confirmed'=>'#22c55e','provisional'=>'#f59e0b','cancelled'=>'#ef4444','checked_in'=>'#3b82f6','checked_out'=>'#94a3b8'];
                $statutLabels = ['confirmed'=>'Confirmé','provisional'=>'En attente','cancelled'=>'Annulé','checked_in'=>'En cours','checked_out'=>'Terminé'];
                $sc = $statutColors[$b['statut']] ?? '#94a3b8';
                $sl = $statutLabels[$b['statut']] ?? $b['statut'];
                ?>
                <span style="color:<?php echo $sc; ?>;font-size:.75rem;font-weight:600;"><?php echo e($sl); ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
