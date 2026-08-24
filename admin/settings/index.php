<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard;
use App\Payments\CurrencyService; use App\Security\Logger;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('settings.manage');
$adminUser=(new AdminAuth($pdo))->user();
$params=[]; $success=''; $errors=[];
$stmt=$pdo->query("SELECT cle,valeur,description FROM parametres WHERE cle IN ('nom_hotel','telephone_hotel','email_hotel','whatsapp_hotel','heure_checkin','heure_checkout','delai_annulation_h','taux_usd_bif','devise_defaut','adresse_hotel') ORDER BY cle");
foreach($stmt->fetchAll() as $r) $params[$r['cle']]=$r;
if($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()){
    $newRate=(float)str_replace(',','.',trim($_POST['taux_usd_bif']??'0'));
    $oldRate=(float)($params['taux_usd_bif']['valeur']??6000);
    // Mettre à jour chaque paramètre
    $updatable=['nom_hotel','telephone_hotel','email_hotel','whatsapp_hotel','heure_checkin','heure_checkout','delai_annulation_h','adresse_hotel'];
    $adminId=(int)$adminUser['id'];
    foreach($updatable as $k){
        if(isset($_POST[$k])){
            $v=trim(strip_tags($_POST[$k]));
            $pdo->prepare("UPDATE parametres SET valeur=:v,updated_by=:u WHERE cle=:k")->execute([':v'=>$v,':u'=>$adminId,':k'=>$k]);
        }
    }
    if($newRate>0 && abs($newRate-$oldRate)>0.001){
        $cs=new CurrencyService($pdo);
        $ok=$cs->updateRate($newRate,$adminId,trim($_POST['rate_reason']??'Modification manuelle'));
        if($ok){
            (new Logger($pdo))->audit(Logger::ACTION_RATE_CHANGED,'exchange_rate',null,['rate'=>$oldRate],['rate'=>$newRate],$adminId);
        } else $errors[]='Impossible de mettre à jour le taux.';
    }
    if(empty($errors)) $success='Paramètres sauvegardés.';
    $stmt=$pdo->query("SELECT cle,valeur,description FROM parametres WHERE cle IN ('nom_hotel','telephone_hotel','email_hotel','whatsapp_hotel','heure_checkin','heure_checkout','delai_annulation_h','taux_usd_bif','devise_defaut','adresse_hotel')");
    foreach($stmt->fetchAll() as $r) $params[$r['cle']]=$r;
}
$csrfField=CsrfGuard::field();
define('PAGE_TITLE','Paramètres — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header"><h1 class="admin-page-title">Paramètres de l'Hôtel</h1><a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a></header>
<?php if($success):?><div style="background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;">✓ <?php echo e($success);?></div><?php endif;?>
<?php if($errors):?><div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php foreach($errors as $e) echo e($e).'<br>';?></div><?php endif;?>
<form method="POST">
<?php echo $csrfField;?>
<div class="admin-form-card" style="margin-bottom:1.5rem;">
  <div class="admin-form-title">Informations de l'Établissement</div>
  <div class="admin-form-grid">
    <?php $fields=['nom_hotel'=>'Nom de l\'hôtel','telephone_hotel'=>'Téléphone','email_hotel'=>'Email de contact','whatsapp_hotel'=>'WhatsApp','adresse_hotel'=>'Adresse','heure_checkin'=>'Heure de check-in','heure_checkout'=>'Heure de check-out','delai_annulation_h'=>'Délai annulation gratuite (heures)'];
    foreach($fields as $k=>$l):?>
    <div><label class="admin-form-label"><?php echo $l;?></label>
      <input name="<?php echo $k;?>" class="admin-form-input" value="<?php echo e($params[$k]['valeur']??'');?>">
    </div>
    <?php endforeach;?>
  </div>
</div>
<div class="admin-form-card" style="margin-bottom:1.5rem;border-color:#f59e0b;">
  <div class="admin-form-title" style="color:#f59e0b;">⚠️ Taux de Change BIF/USD</div>
  <p style="color:var(--admin-muted);font-size:.85rem;margin-bottom:1.25rem;">
    Taux actuel : <strong style="color:var(--admin-gold);">1 USD = <?php echo number_format((float)($params['taux_usd_bif']['valeur']??6000),0,',','.');?> BIF</strong>
    — Toute modification est irréversible et horodatée dans l'historique.
  </p>
  <div class="admin-form-grid">
    <div><label class="admin-form-label">Nouveau taux (1 USD = X BIF)</label>
      <input name="taux_usd_bif" type="number" step="0.01" min="1" class="admin-form-input" value="<?php echo $params['taux_usd_bif']['valeur']??6000;?>">
    </div>
    <div><label class="admin-form-label">Raison du changement</label>
      <input name="rate_reason" class="admin-form-input" placeholder="Ex: Mise à jour BRB 16/08/2026" maxlength="255">
    </div>
  </div>
</div>
<button type="submit" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;padding:.75rem 2rem;font-size:.9rem;">💾 Enregistrer</button>
</form>
<!-- Historique taux -->
<div class="admin-form-card" style="margin-top:1.5rem;">
  <div class="admin-form-title">Historique des Taux de Change</div>
  <?php $hist=$pdo->query("SELECT * FROM exchange_rate_history ORDER BY created_at DESC LIMIT 10")->fetchAll();?>
  <?php if(empty($hist)):?><p style="color:var(--admin-muted);font-size:.85rem;">Aucun historique.</p>
  <?php else:?>
  <table class="admin-table">
    <thead><tr><th>Date</th><th>Ancien taux</th><th>Nouveau taux</th><th>Raison</th></tr></thead>
    <tbody>
    <?php foreach($hist as $h):?>
    <tr>
      <td style="font-size:.8rem;"><?php echo date('d/m/Y H:i',strtotime($h['created_at']));?></td>
      <td>1 USD = <?php echo number_format((float)$h['old_rate'],0,',','.');?> BIF</td>
      <td style="color:var(--admin-gold);">1 USD = <?php echo number_format((float)$h['new_rate'],0,',','.');?> BIF</td>
      <td style="font-size:.85rem;"><?php echo e($h['reason']??'—');?></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table>
  <?php endif;?>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
