<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Payments\CurrencyService;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('customers.view');
$id=(int)($_GET['id']??0);
if(!$id){header('Location:/admin/customers/');exit;}
$stmt=$pdo->prepare("SELECT id,first_name,last_name,email,phone,country_code,preferred_locale,preferred_currency,email_verified_at,is_active,newsletter_consent,special_requests,last_login_at,created_at FROM customers WHERE id=:id");
$stmt->execute([':id'=>$id]); $c=$stmt->fetch();
if(!$c){header('Location:/admin/customers/');exit;}
$stmt=$pdo->prepare("SELECT b.*,r.name AS room_name FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.customer_id=:cid ORDER BY b.created_at DESC LIMIT 10");
$stmt->execute([':cid'=>$id]); $bookings=$stmt->fetchAll();
$currency=new CurrencyService($pdo);
$totalSpent=(int)$pdo->prepare("SELECT COALESCE(SUM(total_bif),0) FROM bookings WHERE customer_id=:cid AND payment_status='paid'")->execute([':cid'=>$id]) ? ($s=$pdo->prepare("SELECT COALESCE(SUM(total_bif),0) FROM bookings WHERE customer_id=:cid AND payment_status='paid'") ?: 0) : 0;
$s=$pdo->prepare("SELECT COALESCE(SUM(total_bif),0) FROM bookings WHERE customer_id=:cid AND payment_status='paid'"); $s->execute([':cid'=>$id]); $totalSpent=(int)$s->fetchColumn();
define('PAGE_TITLE','Client — Admin');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header">
  <h1 class="admin-page-title">
    <a href="/admin/customers/" style="color:var(--admin-muted);font-weight:400;font-size:.9rem;">Clients</a>
    → <?php echo e($c['first_name'].' '.$c['last_name']);?>
  </h1>
  <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
</header>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">
  <div class="admin-form-card">
    <div class="admin-form-title">Informations Client</div>
    <?php foreach(['Nom'=>$c['first_name'].' '.$c['last_name'],'Email'=>$c['email'],'Téléphone'=>$c['phone']??'—','Pays'=>$c['country_code']??'—','Langue'=>$c['preferred_locale'],'Devise'=>$c['preferred_currency'],'Inscrit le'=>date('d/m/Y',strtotime($c['created_at'])),'Dernière connexion'=>$c['last_login_at']?date('d/m/Y H:i',strtotime($c['last_login_at'])):'Jamais','Email vérifié'=>$c['email_verified_at']?'✅ Oui':'⏳ Non','Newsletter'=>$c['newsletter_consent']?'Oui':'Non','Statut'=>$c['is_active']?'Actif':'Suspendu'] as $l=>$v):?>
    <div style="display:flex;justify-content:space-between;padding:.4rem 0;border-bottom:1px solid var(--admin-border);font-size:.85rem;">
      <span style="color:var(--admin-muted);"><?php echo $l;?></span>
      <span style="color:var(--admin-text);"><?php echo e($v);?></span>
    </div>
    <?php endforeach;?>
  </div>
  <div class="admin-form-card">
    <div class="admin-form-title">Statistiques</div>
    <div style="font-size:1.5rem;font-weight:700;color:var(--admin-gold);margin-bottom:.5rem;"><?php echo $currency->formatBif($totalSpent);?></div>
    <div style="color:var(--admin-muted);font-size:.8rem;margin-bottom:1.5rem;">Total dépensé (réservations payées)</div>
    <div style="font-size:1.5rem;font-weight:700;color:var(--admin-text);margin-bottom:.5rem;"><?php echo count($bookings);?></div>
    <div style="color:var(--admin-muted);font-size:.8rem;">Réservations</div>
    <?php if($c['special_requests']):?>
    <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--admin-border);">
      <div style="color:var(--admin-muted);font-size:.8rem;margin-bottom:.4rem;">Demandes spéciales</div>
      <p style="color:var(--admin-text);font-size:.85rem;"><?php echo e($c['special_requests']);?></p>
    </div>
    <?php endif;?>
  </div>
</div>
<div class="admin-form-card">
  <div class="admin-form-title">Historique des Réservations</div>
  <?php if(empty($bookings)):?><p style="color:var(--admin-muted);font-size:.85rem;">Aucune réservation.</p>
  <?php else:?>
  <table class="admin-table">
    <thead><tr><th>Référence</th><th>Chambre</th><th>Arrivée</th><th>Nuits</th><th>Total</th><th>Statut</th></tr></thead>
    <tbody>
    <?php $sc=['confirmed'=>'badge-success','provisional'=>'badge-warning','cancelled'=>'badge-danger','checked_out'=>'badge-muted'];
    foreach($bookings as $b):?>
    <tr>
      <td><a href="/admin/reservations/view.php?id=<?php echo $b['id'];?>" style="color:var(--admin-gold);font-size:.8rem;font-family:monospace;"><?php echo e($b['reference']);?></a></td>
      <td style="font-size:.85rem;"><?php echo e($b['room_name']);?></td>
      <td style="font-size:.85rem;"><?php echo e(format_date_fr($b['date_arrivee']));?></td>
      <td style="text-align:center;"><?php echo $b['nb_nights'];?></td>
      <td style="font-size:.85rem;"><?php echo $currency->formatBif((int)$b['total_bif']);?></td>
      <td><span class="badge <?php echo $sc[$b['statut']]??'badge-muted';?>"><?php echo e($b['statut']);?></span></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  <?php endif;?>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
