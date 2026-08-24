<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard;
use App\Booking\BookingService; use App\Repositories\RoomRepository;
use App\Security\CsrfGuard;

$pdo   = getDB();
$guard = new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php');
$guard->requirePermission('reservations.create');
$adminUser = (new AdminAuth($pdo))->user();

$rooms   = (new RoomRepository($pdo))->findActive();
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()) {
    $service = new BookingService($pdo);
    $data    = [
        'date_arrivee'    => trim($_POST['date_arrivee']    ?? ''),
        'date_depart'     => trim($_POST['date_depart']     ?? ''),
        'room_id'         => (int)($_POST['room_id']        ?? 0),
        'nb_adults'       => (int)($_POST['nb_adults']      ?? 1),
        'nb_children'     => (int)($_POST['nb_children']    ?? 0),
        'guest_first_name'=> trim($_POST['guest_first_name']?? ''),
        'guest_last_name' => trim($_POST['guest_last_name'] ?? ''),
        'guest_email'     => trim($_POST['guest_email']     ?? ''),
        'guest_phone'     => trim($_POST['guest_phone']     ?? ''),
        'guest_country'   => trim($_POST['guest_country']   ?? ''),
        'currency_chosen' => trim($_POST['currency_chosen'] ?? 'BIF'),
        'payment_method'  => trim($_POST['payment_method']  ?? 'cash_bif'),
        'special_requests'=> trim($_POST['special_requests']?? ''),
        'source'          => 'phone',
    ];
    $result = $service->create($data);
    if ($result['success']) {
        header('Location:/admin/reservations/view.php?id='.$result['booking_id'].'&created=1');
        exit;
    }
    $errors[] = $result['error'] ?? 'Erreur de création.';
}

$csrfField = CsrfGuard::field();
define('PAGE_TITLE','Nouvelle Réservation — Admin');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header">
  <h1 class="admin-page-title">
    <a href="/admin/reservations/" style="color:var(--admin-muted);font-weight:400;font-size:.9rem;">Réservations</a> → Nouvelle Réservation
  </h1>
  <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
</header>
<?php if($errors):?>
<div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;">
  <?php foreach($errors as $e) echo e($e).'<br>';?>
</div>
<?php endif;?>
<form method="POST" class="admin-form-card">
<?php echo $csrfField;?>
<div class="admin-form-title">Nouvelle Réservation (Téléphone / Walk-in)</div>
<div class="admin-form-grid">
  <div><label class="admin-form-label">Chambre *</label>
    <select name="room_id" class="admin-form-select" required>
      <option value="">-- Choisir --</option>
      <?php foreach($rooms as $r):?>
      <option value="<?php echo $r['id'];?>"><?php echo e($r['name']);?> — <?php echo number_format((int)$r['price_per_night_bif'],0,',',' ');?> BIF/nuit</option>
      <?php endforeach;?>
    </select>
  </div>
  <div><label class="admin-form-label">Arrivée *</label><input name="date_arrivee" type="date" class="admin-form-input" required></div>
  <div><label class="admin-form-label">Départ *</label><input name="date_depart" type="date" class="admin-form-input" required></div>
  <div><label class="admin-form-label">Adultes *</label><input name="nb_adults" type="number" min="1" max="10" value="1" class="admin-form-input" required></div>
  <div><label class="admin-form-label">Enfants</label><input name="nb_children" type="number" min="0" max="10" value="0" class="admin-form-input"></div>
  <div><label class="admin-form-label">Devise</label>
    <select name="currency_chosen" class="admin-form-select">
      <option value="BIF">BIF — Franc Burundais</option><option value="USD">USD — Dollar US</option>
    </select>
  </div>
</div>
<h3 style="color:var(--admin-gold);font-size:.9rem;margin:1.5rem 0 1rem;text-transform:uppercase;letter-spacing:.08em;">Informations Client</h3>
<div class="admin-form-grid">
  <div><label class="admin-form-label">Prénom *</label><input name="guest_first_name" class="admin-form-input" required maxlength="80"></div>
  <div><label class="admin-form-label">Nom *</label><input name="guest_last_name" class="admin-form-input" required maxlength="80"></div>
  <div><label class="admin-form-label">Email *</label><input name="guest_email" type="email" class="admin-form-input" required maxlength="150"></div>
  <div><label class="admin-form-label">Téléphone *</label><input name="guest_phone" type="tel" class="admin-form-input" required maxlength="20" placeholder="+257 79 00 00 00"></div>
  <div><label class="admin-form-label">Pays (code ISO)</label><input name="guest_country" class="admin-form-input" maxlength="2" placeholder="BI"></div>
  <div><label class="admin-form-label">Mode de Paiement</label>
    <select name="payment_method" class="admin-form-select">
      <option value="cash_bif">Espèces BIF</option><option value="cash_usd">Espèces USD</option>
      <option value="lumicash">Lumicash</option><option value="ecocash">EcoCash</option>
      <option value="bank_local">Virement Bancaire</option><option value="manual">Autre</option>
    </select>
  </div>
</div>
<div style="margin-top:1.25rem;">
  <label class="admin-form-label">Demandes Spéciales</label>
  <textarea name="special_requests" class="admin-form-textarea" rows="3" style="width:100%;" maxlength="1000"></textarea>
</div>
<div style="display:flex;gap:1rem;margin-top:1.75rem;">
  <button type="submit" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;padding:.75rem 2.5rem;font-size:.9rem;">
    ✅ Créer la Réservation
  </button>
  <a href="/admin/reservations/" class="btn-admin-sm">Annuler</a>
</div>
</form>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
