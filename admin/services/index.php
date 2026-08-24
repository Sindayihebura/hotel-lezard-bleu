<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard; use App\Payments\CurrencyService;
$pdo=getDB(); $guard=new RbacGuard($pdo); $guard->requireAuth('/admin/login.php'); $guard->requirePermission('services.manage');
$adminUser=(new AdminAuth($pdo))->user();
$services=$pdo->query("SELECT * FROM services ORDER BY category,sort_order,id")->fetchAll();
$currency=new CurrencyService($pdo);
$success='';
if($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()){
    $action=$_POST['action']??'';
    if($action==='toggle'){
        $sid=(int)($_POST['service_id']??0);
        $cur=(int)$pdo->prepare("SELECT is_active FROM services WHERE id=:id")->execute([':id'=>$sid])?($ss=$pdo->prepare("SELECT is_active FROM services WHERE id=:id") ?: 0):0;
        $ss=$pdo->prepare("SELECT is_active FROM services WHERE id=:id"); $ss->execute([':id'=>$sid]); $cur=(int)$ss->fetchColumn();
        $pdo->prepare("UPDATE services SET is_active=:a WHERE id=:id")->execute([':a'=>$cur?0:1,':id'=>$sid]);
        $success='Statut mis à jour.';
    } elseif($action==='add'){
        $title=trim($_POST['title']??''); $cat=trim($_POST['category']??'other'); $price=(int)($_POST['price']??0); $unit=trim($_POST['unit']??'par personne'); $desc=trim($_POST['description']??'');
        if($title && $price>0){
            $pdo->prepare("INSERT INTO services (category,title,description,price_bif,price_unit,is_active) VALUES(:c,:t,:d,:p,:u,1)")->execute([':c'=>$cat,':t'=>$title,':d'=>$desc,':p'=>$price,':u'=>$unit]);
            $success='Service ajouté.';
        }
    }
    $services=$pdo->query("SELECT * FROM services ORDER BY category,sort_order,id")->fetchAll();
}
$csrfField=CsrfGuard::field();
define('PAGE_TITLE','Services — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header"><h1 class="admin-page-title">Services</h1><a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a></header>
<?php if($success):?><div style="background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php echo e($success);?></div><?php endif;?>
<!-- Ajouter service -->
<div class="admin-form-card" style="margin-bottom:1.5rem;">
  <div class="admin-form-title">Nouveau Service</div>
  <form method="POST"><input type="hidden" name="action" value="add"><?php echo $csrfField;?>
  <div class="admin-form-grid">
    <div><label class="admin-form-label">Titre</label><input name="title" class="admin-form-input" required maxlength="150"></div>
    <div><label class="admin-form-label">Catégorie</label>
      <select name="category" class="admin-form-select">
        <?php foreach(['spa'=>'Spa & Bien-être','restaurant'=>'Restaurant','transport'=>'Transport','loisirs'=>'Loisirs & Excursions','conferences'=>'Conférences','events'=>'Événements','other'=>'Autre'] as $v=>$l):?>
          <option value="<?php echo $v;?>"><?php echo $l;?></option>
        <?php endforeach;?>
      </select>
    </div>
    <div><label class="admin-form-label">Prix (BIF)</label><input name="price" type="number" min="0" class="admin-form-input" required></div>
    <div><label class="admin-form-label">Unité</label><input name="unit" class="admin-form-input" value="par personne" maxlength="50"></div>
  </div>
  <div style="margin-top:1rem;"><label class="admin-form-label">Description</label><textarea name="description" class="admin-form-textarea" rows="2" style="width:100%;"></textarea></div>
  <button type="submit" class="btn-admin-sm" style="margin-top:1rem;background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;padding:.65rem 1.5rem;">Ajouter</button>
  </form>
</div>
<div class="admin-table-wrapper">
<table class="admin-table">
<thead><tr><th>Service</th><th>Catégorie</th><th>Prix</th><th>Unité</th><th>Statut</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($services as $s):?>
<tr>
  <td style="font-weight:600;font-size:.85rem;"><?php echo e($s['title']);?></td>
  <td style="font-size:.85rem;"><?php echo e(ucfirst($s['category']));?></td>
  <td style="font-size:.85rem;"><?php echo $currency->formatBif((int)$s['price_bif']);?></td>
  <td style="font-size:.8rem;color:var(--admin-muted);"><?php echo e($s['price_unit']);?></td>
  <td><span class="badge <?php echo $s['is_active']?'badge-success':'badge-muted';?>"><?php echo $s['is_active']?'Actif':'Inactif';?></span></td>
  <td>
    <form method="POST" style="display:inline"><?php echo $csrfField;?>
      <input type="hidden" name="action" value="toggle"><input type="hidden" name="service_id" value="<?php echo $s['id'];?>">
      <button type="submit" class="btn-admin-sm" style="color:<?php echo $s['is_active']?'#ef4444':'#22c55e';?>;"><?php echo $s['is_active']?'Désactiver':'Activer';?></button>
    </form>
  </td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
