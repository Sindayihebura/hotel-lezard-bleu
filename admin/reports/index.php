<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Payments\CurrencyService;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('reports.view');
$adminUser=(new AdminAuth($pdo))->user();
$month=date('Y-m'); $from=trim($_GET['from']??date('Y-m-01')); $to=trim($_GET['to']??date('Y-m-t'));
// Sanitize dates
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$from)) $from=date('Y-m-01');
if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$to))   $to=date('Y-m-t');
$currency=new CurrencyService($pdo);
// KPIs
$revBif=(int)$pdo->prepare("SELECT COALESCE(SUM(total_bif),0) FROM bookings WHERE payment_status='paid' AND date_arrivee BETWEEN :f AND :t")->execute([':f'=>$from,':t'=>$to]) ? ($ss=$pdo->prepare("SELECT COALESCE(SUM(total_bif),0) FROM bookings WHERE payment_status='paid' AND date_arrivee BETWEEN :f AND :t") ?: 0) : 0;
$ss=$pdo->prepare("SELECT COALESCE(SUM(total_bif),0) FROM bookings WHERE payment_status='paid' AND date_arrivee BETWEEN :f AND :t"); $ss->execute([':f'=>$from,':t'=>$to]); $revBif=(int)$ss->fetchColumn();
$s=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE date_arrivee BETWEEN :f AND :t"); $s->execute([':f'=>$from,':t'=>$to]); $nbRes=(int)$s->fetchColumn();
$s=$pdo->prepare("SELECT COUNT(*) FROM bookings WHERE statut='cancelled' AND date_arrivee BETWEEN :f AND :t"); $s->execute([':f'=>$from,':t'=>$to]); $nbCancel=(int)$s->fetchColumn();
$s=$pdo->prepare("SELECT COUNT(DISTINCT customer_id) FROM bookings WHERE date_arrivee BETWEEN :f AND :t AND customer_id IS NOT NULL"); $s->execute([':f'=>$from,':t'=>$to]); $nbClients=(int)$s->fetchColumn();
// Top chambres
$topRooms=$pdo->prepare("SELECT r.name,COUNT(b.id) AS nb,SUM(b.total_bif) AS rev FROM bookings b JOIN rooms r ON r.id=b.room_id WHERE b.payment_status='paid' AND b.date_arrivee BETWEEN :f AND :t GROUP BY r.id ORDER BY rev DESC LIMIT 5"); $topRooms->execute([':f'=>$from,':t'=>$to]); $topRooms=$topRooms->fetchAll();
// Par statut
$byStatus=$pdo->prepare("SELECT statut,COUNT(*) AS nb FROM bookings WHERE date_arrivee BETWEEN :f AND :t GROUP BY statut ORDER BY nb DESC"); $byStatus->execute([':f'=>$from,':t'=>$to]); $byStatus=$byStatus->fetchAll();
define('PAGE_TITLE','Rapports — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header"><h1 class="admin-page-title">Rapports & Statistiques</h1><a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a></header>
<!-- Période -->
<form method="GET" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:2rem;background:var(--admin-card);padding:1.25rem;border-radius:var(--radius-sm);border:1px solid var(--admin-border);">
  <div><label class="admin-form-label">Du</label><input type="date" name="from" value="<?php echo $from;?>" class="admin-form-input" style="padding:.5rem .75rem;font-size:.85rem;"></div>
  <div><label class="admin-form-label">Au</label><input type="date" name="to" value="<?php echo $to;?>" class="admin-form-input" style="padding:.5rem .75rem;font-size:.85rem;"></div>
  <button type="submit" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;margin-bottom:0;">Générer</button>
</form>
<!-- KPIs -->
<div class="admin-kpi-grid" style="margin-bottom:2rem;">
  <div class="kpi-card kpi-gold"><div class="kpi-icon">💰</div><div class="kpi-value"><?php echo $currency->formatBif($revBif);?></div><div class="kpi-label">Revenus Période</div></div>
  <div class="kpi-card"><div class="kpi-icon">📋</div><div class="kpi-value"><?php echo $nbRes;?></div><div class="kpi-label">Réservations</div></div>
  <div class="kpi-card <?php echo $nbCancel>0?'kpi-warning':'';?>"><div class="kpi-icon">❌</div><div class="kpi-value"><?php echo $nbCancel;?></div><div class="kpi-label">Annulations</div></div>
  <div class="kpi-card"><div class="kpi-icon">👥</div><div class="kpi-value"><?php echo $nbClients;?></div><div class="kpi-label">Clients Uniques</div></div>
  <div class="kpi-card"><div class="kpi-icon">📊</div><div class="kpi-value"><?php echo $nbRes>0?round(100-($nbCancel/$nbRes*100),1).'%':'—';?></div><div class="kpi-label">Taux de Confirmation</div></div>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
  <!-- Top chambres -->
  <div class="admin-form-card">
    <div class="admin-form-title">Top Chambres (par revenus)</div>
    <?php if(empty($topRooms)):?><p style="color:var(--admin-muted);font-size:.85rem;">Aucune donnée.</p>
    <?php else: foreach($topRooms as $r):?>
    <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--admin-border);font-size:.85rem;">
      <span style="color:var(--admin-text);"><?php echo e($r['name']);?></span>
      <span style="color:var(--admin-gold);"><?php echo $currency->formatBif((int)$r['rev']);?> (<?php echo $r['nb'];?> séj.)</span>
    </div>
    <?php endforeach; endif;?>
  </div>
  <!-- Par statut -->
  <div class="admin-form-card">
    <div class="admin-form-title">Réservations par Statut</div>
    <?php $labels=['provisional'=>'Provisoires','confirmed'=>'Confirmées','checked_in'=>'En cours','checked_out'=>'Terminées','cancelled'=>'Annulées','no_show'=>'No Show'];
    foreach($byStatus as $s):?>
    <div style="display:flex;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--admin-border);font-size:.85rem;">
      <span style="color:var(--admin-text);"><?php echo $labels[$s['statut']]??$s['statut'];?></span>
      <span style="font-weight:600;color:var(--admin-gold);"><?php echo $s['nb'];?></span>
    </div>
    <?php endforeach;?>
  </div>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
