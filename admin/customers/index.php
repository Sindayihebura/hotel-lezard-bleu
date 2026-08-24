<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('customers.view');
$adminUser=(new AdminAuth($pdo))->user();
$page=max(1,(int)($_GET['page']??1)); $limit=25; $offset=($page-1)*$limit;
$q=trim($_GET['q']??''); $where=''; $params=[];
if($q){ $where="WHERE (email LIKE :q OR CONCAT(first_name,' ',last_name) LIKE :q2 OR phone LIKE :q3)";
    $like="%$q%"; $params=[':q'=>$like,':q2'=>$like,':q3'=>$like]; }
$stmt=$pdo->prepare("SELECT id,first_name,last_name,email,phone,country_code,preferred_locale,created_at,email_verified_at,is_active FROM customers $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params); $customers=$stmt->fetchAll();
$total=(int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
define('PAGE_TITLE','Clients — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header">
  <h1 class="admin-page-title">Clients (<?php echo $total;?>)</h1>
  <div class="admin-header-right">
    <?php if($guard->can('customers.export')):?>
    <a href="/admin/customers/export.php" class="btn-admin-sm">📥 Exporter CSV</a>
    <?php endif;?>
    <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
  </div>
</header>
<form method="GET" style="display:flex;gap:.75rem;margin-bottom:1.5rem;">
  <input type="text" name="q" value="<?php echo e($q);?>" placeholder="Nom, email, téléphone…"
         class="admin-form-input" style="width:280px;padding:.5rem .75rem;font-size:.85rem;">
  <button type="submit" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;">Chercher</button>
  <?php if($q):?><a href="/admin/customers/" class="btn-admin-sm">✕ Effacer</a><?php endif;?>
</form>
<div class="admin-table-wrapper">
<table class="admin-table">
<thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Pays</th><th>Inscrit le</th><th>Email vérifié</th><th>Statut</th><th>Actions</th></tr></thead>
<tbody>
<?php if(empty($customers)):?>
<tr><td colspan="8" style="text-align:center;color:var(--admin-muted);padding:2rem;">Aucun client.</td></tr>
<?php else: foreach($customers as $c):?>
<tr>
  <td style="font-weight:600;font-size:.85rem;"><?php echo e($c['first_name'].' '.$c['last_name']);?></td>
  <td style="font-size:.8rem;"><?php echo e($c['email']);?></td>
  <td style="font-size:.85rem;"><?php echo e($c['phone']??'—');?></td>
  <td style="font-size:.85rem;"><?php echo e($c['country_code']??'—');?></td>
  <td style="font-size:.8rem;"><?php echo date('d/m/Y',strtotime($c['created_at']));?></td>
  <td style="text-align:center;"><?php echo $c['email_verified_at']?'✅':'⏳';?></td>
  <td><span class="badge <?php echo $c['is_active']?'badge-success':'badge-danger';?>"><?php echo $c['is_active']?'Actif':'Suspendu';?></span></td>
  <td><a href="/admin/customers/view.php?id=<?php echo $c['id'];?>" class="btn-admin-sm">Voir</a></td>
</tr>
<?php endforeach; endif;?>
</tbody>
</table>
</div>
<?php if(ceil($total/$limit)>1):?>
<div style="display:flex;gap:.5rem;margin-top:1.25rem;justify-content:center;">
  <?php for($i=max(1,$page-2);$i<=min((int)ceil($total/$limit),$page+2);$i++):?>
    <a href="?page=<?php echo $i;?><?php echo $q?"&q=".urlencode($q):'';?>" class="btn-admin-sm"
       style="<?php echo $i===$page?'background:var(--admin-gold);color:#070C14;':'';?>"><?php echo $i;?></a>
  <?php endfor;?>
</div>
<?php endif;?>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
