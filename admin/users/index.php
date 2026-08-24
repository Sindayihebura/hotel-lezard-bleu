<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('users.manage');
$adminUser=(new AdminAuth($pdo))->user();
$users=$pdo->query("SELECT u.*,r.name AS role_name,r.label AS role_label FROM admin_users u JOIN roles r ON r.id=u.role_id ORDER BY u.created_at DESC")->fetchAll();
$roles=$pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
$errors=[]; $success='';
if($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()){
    $action=trim($_POST['action']??'');
    if($action==='create'){
        $fn=trim($_POST['first_name']??''); $ln=trim($_POST['last_name']??'');
        $em=strtolower(trim($_POST['email']??'')); $role=(int)($_POST['role_id']??0); $pw=$_POST['password']??'';
        if(strlen($pw)<10) $errors[]='Mot de passe min. 10 caractères.';
        elseif(!filter_var($em,FILTER_VALIDATE_EMAIL)) $errors[]='Email invalide.';
        else {
            $pdo->prepare("INSERT INTO admin_users (role_id,first_name,last_name,email,password_hash,is_active) VALUES(:r,:fn,:ln,:e,:h,1)")
                ->execute([':r'=>$role,':fn'=>$fn,':ln'=>$ln,':e'=>$em,':h'=>password_hash($pw,PASSWORD_DEFAULT,['cost'=>12])]);
            $success='Utilisateur créé.';
        }
    } elseif($action==='toggle'){
        $uid=(int)($_POST['user_id']??0);
        if($uid!==$adminUser['id']){
            $cur=(int)$pdo->prepare("SELECT is_active FROM admin_users WHERE id=:id")->execute([':id'=>$uid]) ? ($ss=$pdo->prepare("SELECT is_active FROM admin_users WHERE id=:id") ?: 0) : 0;
            $ss=$pdo->prepare("SELECT is_active FROM admin_users WHERE id=:id"); $ss->execute([':id'=>$uid]); $cur=(int)$ss->fetchColumn();
            $pdo->prepare("UPDATE admin_users SET is_active=:a WHERE id=:id")->execute([':a'=>$cur?0:1,':id'=>$uid]);
            $success='Statut mis à jour.';
        }
    }
    $users=$pdo->query("SELECT u.*,r.name AS role_name,r.label AS role_label FROM admin_users u JOIN roles r ON r.id=u.role_id ORDER BY u.created_at DESC")->fetchAll();
}
$csrfField=CsrfGuard::field();
define('PAGE_TITLE','Utilisateurs Admin — Administration');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header"><h1 class="admin-page-title">Utilisateurs & Rôles</h1><a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a></header>
<?php if($success):?><div style="background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php echo e($success);?></div><?php endif;?>
<?php if($errors):?><div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php foreach($errors as $e) echo e($e).'<br>';?></div><?php endif;?>
<!-- Créer utilisateur -->
<div class="admin-form-card" style="margin-bottom:1.5rem;">
  <div class="admin-form-title">Nouvel Utilisateur Admin</div>
  <form method="POST"><input type="hidden" name="action" value="create"><?php echo $csrfField;?>
  <div class="admin-form-grid">
    <div><label class="admin-form-label">Prénom</label><input name="first_name" class="admin-form-input" required></div>
    <div><label class="admin-form-label">Nom</label><input name="last_name" class="admin-form-input" required></div>
    <div><label class="admin-form-label">Email</label><input name="email" type="email" class="admin-form-input" required></div>
    <div><label class="admin-form-label">Rôle</label>
      <select name="role_id" class="admin-form-select">
        <?php foreach($roles as $r):?><option value="<?php echo $r['id'];?>"><?php echo e($r['label']);?></option><?php endforeach;?>
      </select>
    </div>
    <div><label class="admin-form-label">Mot de passe (min. 10 car.)</label><input name="password" type="password" class="admin-form-input" required minlength="10"></div>
  </div>
  <button type="submit" class="btn-admin-sm" style="margin-top:1rem;background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;padding:.65rem 1.5rem;">Créer</button>
  </form>
</div>
<!-- Liste -->
<div class="admin-table-wrapper">
<table class="admin-table">
<thead><tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Dernière connexion</th><th>Statut</th><th>Action</th></tr></thead>
<tbody>
<?php foreach($users as $u):?>
<tr>
  <td style="font-weight:600;font-size:.85rem;"><?php echo e($u['first_name'].' '.$u['last_name']);?> <?php if($u['id']==$adminUser['id']):?><span style="font-size:.7rem;color:var(--admin-muted);">(vous)</span><?php endif;?></td>
  <td style="font-size:.8rem;"><?php echo e($u['email']);?></td>
  <td><span class="badge badge-gold" style="font-size:.7rem;"><?php echo e($u['role_label']);?></span></td>
  <td style="font-size:.8rem;"><?php echo $u['last_login_at']?date('d/m/Y H:i',strtotime($u['last_login_at'])):'Jamais';?></td>
  <td><span class="badge <?php echo $u['is_active']?'badge-success':'badge-danger';?>"><?php echo $u['is_active']?'Actif':'Suspendu';?></span></td>
  <td>
    <?php if($u['id']!==$adminUser['id']):?>
    <form method="POST" style="display:inline"><?php echo $csrfField;?>
      <input type="hidden" name="action" value="toggle">
      <input type="hidden" name="user_id" value="<?php echo $u['id'];?>">
      <button type="submit" class="btn-admin-sm" style="color:<?php echo $u['is_active']?'#ef4444':'#22c55e';?>;" onclick="return confirm('Confirmer ?')">
        <?php echo $u['is_active']?'Suspendre':'Activer';?>
      </button>
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
