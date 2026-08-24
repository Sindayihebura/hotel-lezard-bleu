<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard; use App\Payments\CurrencyService;
$pdo=getDB(); $guard=new RbacGuard($pdo); $guard->requireAuth('/admin/login.php'); $guard->requirePermission('offers.manage');
$adminUser=(new AdminAuth($pdo))->user();
$offers=$pdo->query("SELECT * FROM offers ORDER BY is_active DESC,created_at DESC")->fetchAll();
$currency=new CurrencyService($pdo);
$success='';
if($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()){
    $action=$_POST['action']??'';
    if($action==='toggle'){
        $oid=(int)($_POST['offer_id']??0);
        $ss=$pdo->prepare("SELECT is_active FROM offers WHERE id=:id"); $ss->execute([':id'=>$oid]); $cur=(int)$ss->fetchColumn();
        $pdo->prepare("UPDATE offers SET is_active=:a WHERE id=:id")->execute([':a'=>$cur?0:1,':id'=>$oid]);
        $success='Statut mis à jour.';
    } elseif($action==='add'){
        $title=trim($_POST['title']??''); $code=strtoupper(trim($_POST['code']??'')); $type=$_POST['discount_type']??'percent'; $val=(int)($_POST['discount_value']??0); $minN=(int)($_POST['min_nights']??1); $maxU=(int)($_POST['max_uses']??0); $vFrom=$_POST['valid_from']??null; $vTo=$_POST['valid_to']??null;
        if($title && $val>0){
            $pdo->prepare("INSERT INTO offers (title,code,discount_type,discount_value,min_nights,max_uses,valid_from,valid_to,is_active,description) VALUES(:t,:c,:dt,:dv,:mn,:mu,:vf,:vt,1,:d)")
                ->execute([':t'=>$title,':c'=>$code?:null,':dt'=>$type,':dv'=>$val,':mn'=>$minN,':mu'=>$maxU?:null,':vf'=>$vFrom?:null,':vt'=>$vTo?:null,':d'=>trim($_POST['description']??'')]);
            $success='Offre créée.';
        }
    }
    $offers=$pdo->query("SELECT * FROM offers ORDER BY is_active DESC,created_at DESC")->fetchAll();
}
$csrfField=CsrfGuard::field();
define('PAGE_TITLE','Offres & Codes Promo — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header"><h1 class="admin-page-title">Offres & Codes Promo</h1><a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a></header>
<?php if($success):?><div style="background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php echo e($success);?></div><?php endif;?>
<div class="admin-form-card" style="margin-bottom:1.5rem;">
  <div class="admin-form-title">Nouvelle Offre</div>
  <form method="POST"><input type="hidden" name="action" value="add"><?php echo $csrfField;?>
  <div class="admin-form-grid">
    <div><label class="admin-form-label">Titre *</label><input name="title" class="admin-form-input" required maxlength="150"></div>
    <div><label class="admin-form-label">Code promo (optionnel)</label><input name="code" class="admin-form-input" maxlength="30" style="text-transform:uppercase;" placeholder="EX: BUJUMBURA20"></div>
    <div><label class="admin-form-label">Type de remise</label>
      <select name="discount_type" class="admin-form-select"><option value="percent">Pourcentage (%)</option><option value="fixed_bif">Montant fixe (BIF)</option></select>
    </div>
    <div><label class="admin-form-label">Valeur *</label><input name="discount_value" type="number" min="1" class="admin-form-input" required></div>
    <div><label class="admin-form-label">Nuits minimum</label><input name="min_nights" type="number" min="1" value="1" class="admin-form-input"></div>
    <div><label class="admin-form-label">Utilisations max (0=illimité)</label><input name="max_uses" type="number" min="0" value="0" class="admin-form-input"></div>
    <div><label class="admin-form-label">Valide du</label><input name="valid_from" type="date" class="admin-form-input"></div>
    <div><label class="admin-form-label">Au</label><input name="valid_to" type="date" class="admin-form-input"></div>
  </div>
  <div style="margin-top:1rem;"><label class="admin-form-label">Description</label><textarea name="description" class="admin-form-textarea" rows="2" style="width:100%;"></textarea></div>
  <button type="submit" class="btn-admin-sm" style="margin-top:1rem;background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;padding:.65rem 1.5rem;">Créer</button>
  </form>
</div>
<div class="admin-table-wrapper">
<table class="admin-table">
<thead><tr><th>Titre</th><th>Code</th><th>Remise</th><th>Utilisé</th><th>Validité</th><th>Statut</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($offers as $o):?>
<tr>
  <td style="font-size:.85rem;font-weight:600;"><?php echo e($o['title']);?></td>
  <td><?php if($o['code']):?><code style="background:rgba(212,175,55,.1);color:var(--admin-gold);padding:.15rem .4rem;border-radius:4px;font-size:.8rem;"><?php echo e($o['code']);?></code><?php else:?>—<?php endif;?></td>
  <td style="font-size:.85rem;"><?php echo $o['discount_type']==='percent'?$o['discount_value'].'%':$currency->formatBif((int)$o['discount_value']);?></td>
  <td style="font-size:.85rem;"><?php echo $o['uses_count'].($o['max_uses']?'/'.$o['max_uses']:'');?></td>
  <td style="font-size:.8rem;color:var(--admin-muted);"><?php echo ($o['valid_from']?date('d/m/Y',strtotime($o['valid_from'])):'-').' → '.($o['valid_to']?date('d/m/Y',strtotime($o['valid_to'])):'-');?></td>
  <td><span class="badge <?php echo $o['is_active']?'badge-success':'badge-muted';?>"><?php echo $o['is_active']?'Active':'Inactive';?></span></td>
  <td>
    <form method="POST" style="display:inline"><?php echo $csrfField;?>
      <input type="hidden" name="action" value="toggle"><input type="hidden" name="offer_id" value="<?php echo $o['id'];?>">
      <button type="submit" class="btn-admin-sm" style="color:<?php echo $o['is_active']?'#ef4444':'#22c55e';?>;"><?php echo $o['is_active']?'Désactiver':'Activer';?></button>
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
