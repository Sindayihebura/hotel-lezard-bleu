<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Payments\CurrencyService;
$pdo=getDB(); $guard=new RbacGuard($pdo); $guard->requireAuth('/admin/login.php');
$guard->requirePermission('rooms.manage'); $adminUser=(new AdminAuth($pdo))->user();
$rooms=[]; $cats=[];
if($pdo){
    $stmt=$pdo->query("SELECT r.*,rc.name AS cat FROM rooms r JOIN room_categories rc ON rc.id=r.category_id ORDER BY r.sort_order,r.id");
    $rooms=$stmt->fetchAll();
    $stmt=$pdo->query("SELECT * FROM room_categories ORDER BY sort_order"); $cats=$stmt->fetchAll();
}
$currency=new CurrencyService($pdo);
define('PAGE_TITLE','Chambres — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header">
  <h1 class="admin-page-title">Chambres & Suites</h1>
  <div class="admin-header-right">
    <a href="/admin/rooms/edit.php" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;">+ Ajouter</a>
    <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
  </div>
</header>
<div class="admin-table-wrapper">
<table class="admin-table">
<thead><tr><th>Photo</th><th>Chambre</th><th>Catégorie</th><th>Capacité</th><th>Prix / nuit</th><th>Statut</th><th>Actions</th></tr></thead>
<tbody>
<?php if(empty($rooms)):?>
<tr><td colspan="7" style="text-align:center;color:var(--admin-muted);padding:2rem;">Aucune chambre.</td></tr>
<?php else: foreach($rooms as $r):?>
<tr>
  <td><img src="/<?php echo e($r['photo_main']);?>" style="width:60px;height:40px;object-fit:cover;border-radius:6px;" alt=""></td>
  <td><strong style="color:var(--admin-gold);"><?php echo e($r['name']);?></strong><br><span style="color:var(--admin-muted);font-size:.75rem;">#<?php echo $r['room_number']??$r['id'];?></span></td>
  <td style="font-size:.85rem;"><?php echo e($r['cat']);?></td>
  <td style="font-size:.85rem;text-align:center;"><?php echo $r['capacity_adults'];?> adultes</td>
  <td style="font-size:.85rem;"><?php echo $currency->formatBif((int)$r['price_per_night_bif']);?></td>
  <td><span class="badge <?php echo $r['is_active']?'badge-success':'badge-muted';?>"><?php echo $r['is_active']?'Active':'Inactive';?></span></td>
  <td style="display:flex;gap:.4rem;flex-wrap:wrap;">
    <a href="/admin/rooms/edit.php?id=<?php echo $r['id'];?>" class="btn-admin-sm">Modifier</a>
    <a href="/admin/rooms/toggle.php?id=<?php echo $r['id'];?>" class="btn-admin-sm" style="color:<?php echo $r['is_active']?'#ef4444':'#22c55e';?>;">
      <?php echo $r['is_active']?'Désactiver':'Activer';?>
    </a>
  </td>
</tr>
<?php endforeach; endif;?>
</tbody>
</table>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
