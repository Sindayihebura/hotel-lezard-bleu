<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('audit.view');
$page=max(1,(int)($_GET['page']??1)); $limit=50; $offset=($page-1)*$limit;
$action=trim($_GET['action']??''); $result=trim($_GET['result']??'');
$where=[]; $params=[];
if($action){ $where[]="a.action LIKE :action"; $params[':action']="%$action%"; }
if($result){ $where[]="a.result=:result"; $params[':result']=$result; }
$whereSQL=$where?'WHERE '.implode(' AND ',$where):'';
$stmt=$pdo->prepare("SELECT a.*,CONCAT(u.first_name,' ',u.last_name) AS admin_name FROM audit_logs a LEFT JOIN admin_users u ON u.id=a.admin_user_id $whereSQL ORDER BY a.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params); $logs=$stmt->fetchAll();
$total=(int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
define('PAGE_TITLE','Logs d\'Audit — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header"><h1 class="admin-page-title">Logs d'Audit (<?php echo $total;?> entrées)</h1><a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a></header>
<form method="GET" style="display:flex;gap:.75rem;margin-bottom:1.5rem;flex-wrap:wrap;">
  <input type="text" name="action" value="<?php echo e($action);?>" placeholder="Filtrer par action…" class="admin-form-input" style="width:220px;padding:.5rem .75rem;font-size:.85rem;">
  <select name="result" class="admin-form-select" style="padding:.5rem .75rem;font-size:.85rem;">
    <option value="">Tous résultats</option>
    <option value="success" <?php echo $result==='success'?'selected':'';?>>Succès</option>
    <option value="failure" <?php echo $result==='failure'?'selected':'';?>>Échec</option>
  </select>
  <button type="submit" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;">Filtrer</button>
  <a href="/admin/audit-logs/" class="btn-admin-sm">Réinitialiser</a>
</form>
<div class="admin-table-wrapper">
<table class="admin-table">
<thead><tr><th>Date (UTC)</th><th>Action</th><th>Ressource</th><th>Utilisateur</th><th>IP</th><th>Résultat</th><th>Détails</th></tr></thead>
<tbody>
<?php if(empty($logs)):?>
<tr><td colspan="7" style="text-align:center;color:var(--admin-muted);padding:2rem;">Aucun log.</td></tr>
<?php else: foreach($logs as $l):?>
<tr>
  <td style="font-size:.78rem;white-space:nowrap;"><?php echo date('d/m/Y H:i:s',strtotime($l['created_at']));?></td>
  <td><code style="background:rgba(212,175,55,.1);color:var(--admin-gold);padding:.15rem .4rem;border-radius:4px;font-size:.78rem;"><?php echo e($l['action']);?></code></td>
  <td style="font-size:.8rem;"><?php echo e($l['resource_type']??'—');?> <?php echo $l['resource_id']?'#'.$l['resource_id']:'';?></td>
  <td style="font-size:.8rem;"><?php echo e($l['admin_name']??'Client');?></td>
  <td style="font-size:.78rem;font-family:monospace;"><?php echo e($l['ip_address']);?></td>
  <td><span class="badge <?php echo $l['result']==='success'?'badge-success':'badge-danger';?>"><?php echo $l['result']==='success'?'OK':'Échec';?></span></td>
  <td style="font-size:.78rem;color:var(--admin-muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
    <?php if($l['failure_reason']) echo e($l['failure_reason']); elseif($l['new_values']) echo e(substr($l['new_values'],0,80));?>
  </td>
</tr>
<?php endforeach; endif;?>
</tbody>
</table>
</div>
<?php $pages=(int)ceil($total/$limit); if($pages>1):?>
<div style="display:flex;gap:.5rem;margin-top:1.25rem;justify-content:center;">
  <?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++):?>
  <a href="?page=<?php echo $i;?>&action=<?php echo urlencode($action);?>&result=<?php echo $result;?>" class="btn-admin-sm" style="<?php echo $i===$page?'background:var(--admin-gold);color:#070C14;':'';?>"><?php echo $i;?></a>
  <?php endfor;?>
</div>
<?php endif;?>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
