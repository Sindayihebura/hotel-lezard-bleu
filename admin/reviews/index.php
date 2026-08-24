<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard;
$pdo=getDB(); $guard=new RbacGuard($pdo); $guard->requireAuth('/admin/login.php'); $guard->requirePermission('offers.manage');
$adminUser=(new AdminAuth($pdo))->user();
$filter=$_GET['filter']??'pending';
$where=$filter==='pending'?'WHERE is_visible=0':($filter==='visible'?'WHERE is_visible=1':'');
$reviews=$pdo->query("SELECT * FROM reviews $where ORDER BY created_at DESC LIMIT 50")->fetchAll();
$success='';
if($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()){
    $rid=(int)($_POST['review_id']??0); $action=$_POST['action']??'';
    if($rid && in_array($action,['approve','hide','flag'])){
        $vals=['approve'=>['is_visible'=>1,'is_flagged'=>0],'hide'=>['is_visible'=>0],'flag'=>['is_flagged'=>1]];
        $sets=[]; $params=[':id'=>$rid,':mod'=>$adminUser['id']];
        foreach($vals[$action] as $k=>$v){$sets[]="`$k`=$v";}
        $pdo->prepare("UPDATE reviews SET ".implode(',',$sets).",moderated_by=:mod,moderated_at=UTC_TIMESTAMP() WHERE id=:id")->execute($params);
        $success='Avis mis à jour.';
        $reviews=$pdo->query("SELECT * FROM reviews $where ORDER BY created_at DESC LIMIT 50")->fetchAll();
    }
}
$csrfField=CsrfGuard::field();
define('PAGE_TITLE','Avis Clients — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header"><h1 class="admin-page-title">Avis Clients</h1><a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a></header>
<?php if($success):?><div style="background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac;padding:.75rem;border-radius:8px;margin-bottom:1.25rem;"><?php echo e($success);?></div><?php endif;?>
<div style="display:flex;gap:.5rem;margin-bottom:1.5rem;">
  <a href="?filter=pending" class="btn-admin-sm" style="<?php echo $filter==='pending'?'background:var(--admin-gold);color:#070C14;':'';?>">⏳ En attente</a>
  <a href="?filter=visible" class="btn-admin-sm" style="<?php echo $filter==='visible'?'background:var(--admin-gold);color:#070C14;':'';?>">✅ Publiés</a>
  <a href="?filter=all"    class="btn-admin-sm" style="<?php echo $filter==='all'?'background:var(--admin-gold);color:#070C14;':'';?>">Tous</a>
</div>
<?php if(empty($reviews)):?><p style="color:var(--admin-muted);font-size:.85rem;">Aucun avis.</p>
<?php else: foreach($reviews as $r):?>
<div style="background:var(--admin-card);border:1px solid var(--admin-border);border-radius:var(--radius-sm);padding:1.25rem;margin-bottom:1rem;">
  <div style="display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:.75rem;">
    <div>
      <strong style="color:var(--admin-text);"><?php echo e($r['guest_name']);?></strong>
      <?php if($r['guest_origin']):?><span style="color:var(--admin-muted);font-size:.8rem;"> — <?php echo e($r['guest_origin']);?></span><?php endif;?>
      <span style="color:#f59e0b;margin-left:.5rem;"><?php echo str_repeat('★',(int)$r['rating']).str_repeat('☆',5-(int)$r['rating']);?></span>
      <div style="color:var(--admin-muted);font-size:.75rem;margin-top:.25rem;"><?php echo date('d/m/Y',strtotime($r['created_at']));?></div>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <form method="POST" style="display:inline"><?php echo $csrfField;?>
        <input type="hidden" name="review_id" value="<?php echo $r['id'];?>">
        <?php if(!$r['is_visible']):?>
          <button name="action" value="approve" class="btn-admin-sm" style="color:#22c55e;border-color:#22c55e;font-size:.75rem;">✓ Approuver</button>
        <?php else:?>
          <button name="action" value="hide" class="btn-admin-sm" style="color:#f59e0b;font-size:.75rem;">Masquer</button>
        <?php endif;?>
        <button name="action" value="flag" class="btn-admin-sm" style="color:#ef4444;font-size:.75rem;">🚩 Signaler</button>
      </form>
    </div>
  </div>
  <p style="color:var(--admin-text);font-size:.875rem;margin-top:.75rem;line-height:1.6;"><?php echo e($r['comment']);?></p>
</div>
<?php endforeach; endif;?>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
