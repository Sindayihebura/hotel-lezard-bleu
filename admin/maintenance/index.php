<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard;
$pdo=getDB(); $guard=new RbacGuard($pdo); $guard->requireAuth('/admin/login.php'); $guard->requirePermission('rooms.manage');
$adminUser=(new AdminAuth($pdo))->user();
$blocks=$pdo->query("SELECT rb.*,r.name AS room_name FROM room_blocks rb JOIN rooms r ON r.id=rb.room_id ORDER BY rb.start_date DESC LIMIT 50")->fetchAll();
$rooms=$pdo->query("SELECT id,name,room_number FROM rooms WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$success=''; $errors=[];
if($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()){
    $action=$_POST['action']??'';
    if($action==='add'){
        $roomId=(int)($_POST['room_id']??0); $start=$_POST['start_date']??''; $end=$_POST['end_date']??''; $reason=$_POST['reason']??'maintenance'; $notes=trim($_POST['notes']??'');
        if(!$roomId||!$start||!$end) $errors[]='Chambre et dates obligatoires.';
        elseif($end<=$start) $errors[]='La date de fin doit être après le début.';
        else {
            $pdo->prepare("INSERT INTO room_blocks (room_id,reason,notes,start_date,end_date,created_by) VALUES(:r,:reason,:n,:s,:e,:b)")->execute([':r'=>$roomId,':reason'=>$reason,':n'=>$notes,':s'=>$start,':e'=>$end,':b'=>$adminUser['id']]);
            $success='Blocage créé.';
        }
    } elseif($action==='resolve'){
        $bid=(int)($_POST['block_id']??0);
        $pdo->prepare("UPDATE room_blocks SET resolved_at=UTC_TIMESTAMP() WHERE id=:id")->execute([':id'=>$bid]);
        $success='Blocage résolu.';
    }
    $blocks=$pdo->query("SELECT rb.*,r.name AS room_name FROM room_blocks rb JOIN rooms r ON r.id=rb.room_id ORDER BY rb.start_date DESC LIMIT 50")->fetchAll();
}
$csrfField=CsrfGuard::field();
define('PAGE_TITLE','Maintenance — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header"><h1 class="admin-page-title">Maintenance & Blocages</h1><a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a></header>
<?php if($success):?><div style="background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php echo e($success);?></div><?php endif;?>
<?php if($errors):?><div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php foreach($errors as $e) echo e($e).'<br>';?></div><?php endif;?>
<div class="admin-form-card" style="margin-bottom:1.5rem;">
  <div class="admin-form-title">Nouveau Blocage</div>
  <form method="POST"><input type="hidden" name="action" value="add"><?php echo $csrfField;?>
  <div class="admin-form-grid">
    <div><label class="admin-form-label">Chambre</label>
      <select name="room_id" class="admin-form-select">
        <?php foreach($rooms as $r):?><option value="<?php echo $r['id'];?>"><?php echo e($r['name']);?> <?php echo $r['room_number']?'(#'.$r['room_number'].')':'';?></option><?php endforeach;?>
      </select>
    </div>
    <div><label class="admin-form-label">Raison</label>
      <select name="reason" class="admin-form-select">
        <option value="maintenance">Maintenance</option><option value="cleaning">Nettoyage profond</option>
        <option value="renovation">Rénovation</option><option value="reserved_vip">Réservé VIP</option><option value="other">Autre</option>
      </select>
    </div>
    <div><label class="admin-form-label">Du</label><input name="start_date" type="date" class="admin-form-input" required></div>
    <div><label class="admin-form-label">Au</label><input name="end_date"   type="date" class="admin-form-input" required></div>
  </div>
  <div style="margin-top:1rem;"><label class="admin-form-label">Notes</label><input name="notes" class="admin-form-input" maxlength="255"></div>
  <button type="submit" class="btn-admin-sm" style="margin-top:1rem;background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;padding:.65rem 1.5rem;">Créer Blocage</button>
  </form>
</div>
<div class="admin-table-wrapper">
<table class="admin-table">
<thead><tr><th>Chambre</th><th>Raison</th><th>Du</th><th>Au</th><th>Notes</th><th>Résolu</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($blocks as $b):?>
<tr>
  <td style="font-size:.85rem;"><?php echo e($b['room_name']);?></td>
  <td><span class="badge badge-warning" style="font-size:.75rem;"><?php echo e(ucfirst($b['reason']));?></span></td>
  <td style="font-size:.85rem;"><?php echo e(format_date_fr($b['start_date']));?></td>
  <td style="font-size:.85rem;"><?php echo e(format_date_fr($b['end_date']));?></td>
  <td style="font-size:.8rem;color:var(--admin-muted);"><?php echo e($b['notes']??'');?></td>
  <td><?php echo $b['resolved_at']?'<span class="badge badge-success">Résolu</span>':'<span class="badge badge-warning">En cours</span>';?></td>
  <td>
    <?php if(!$b['resolved_at']):?>
    <form method="POST" style="display:inline"><?php echo $csrfField;?>
      <input type="hidden" name="action" value="resolve"><input type="hidden" name="block_id" value="<?php echo $b['id'];?>">
      <button type="submit" class="btn-admin-sm" style="color:#22c55e;font-size:.75rem;">✓ Résoudre</button>
    </form>
    <?php endif;?>
  </td>
</tr>
<?php endforeach;?>
</tbody>
</table>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
