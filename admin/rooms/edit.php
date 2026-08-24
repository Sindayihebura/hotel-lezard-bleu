<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('rooms.manage');
$adminUser=(new AdminAuth($pdo))->user();
$id=(int)($_GET['id']??0); $room=null; $errors=[]; $success='';
$cats=$pdo->query("SELECT * FROM room_categories ORDER BY sort_order")->fetchAll();
if($id) $room=$pdo->prepare("SELECT * FROM rooms WHERE id=:id")->execute([':id'=>$id]) ? ($stmt=$pdo->prepare("SELECT * FROM rooms WHERE id=:id") ?: null) : null;
if($id){ $stmt=$pdo->prepare("SELECT * FROM rooms WHERE id=:id"); $stmt->execute([':id'=>$id]); $room=$stmt->fetch(); }
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!CsrfGuard::verifyRequest()){ $errors[]='Jeton invalide.'; }
    else {
        $name     = trim($_POST['name']??'');
        $desc     = trim($_POST['description']??'');
        $price    = (int)str_replace([' ',','],'',$_POST['price']??'0');
        $cap      = max(1,(int)($_POST['capacity_adults']??2));
        $surface  = max(10,(int)($_POST['surface_m2']??30));
        $cat      = (int)($_POST['category_id']??1);
        $view     = trim($_POST['view']??'');
        $active   = isset($_POST['is_active'])?1:0;
        $sort     = (int)($_POST['sort_order']??0);
        $photo    = trim($_POST['photo_main']??'assets/images/hero_hotel.jpg');
        if(strlen($name)<2) $errors[]='Nom obligatoire.';
        if($price<=0) $errors[]='Prix invalide.';
        if(empty($errors)){
            $slug = strtolower(preg_replace('/[^a-z0-9]+/','-',iconv('UTF-8','ASCII//TRANSLIT',$name)));
            if($id){
                $pdo->prepare("UPDATE rooms SET name=:n,description=:d,price_per_night_bif=:p,capacity_adults=:c,surface_m2=:s,category_id=:cat,view=:v,is_active=:a,sort_order=:so,photo_main=:ph,updated_at=UTC_TIMESTAMP() WHERE id=:id")
                    ->execute([':n'=>$name,':d'=>$desc,':p'=>$price,':c'=>$cap,':s'=>$surface,':cat'=>$cat,':v'=>$view,':a'=>$active,':so'=>$sort,':ph'=>$photo,':id'=>$id]);
                $success='Chambre modifiée.';
            } else {
                $pdo->prepare("INSERT INTO rooms (category_id,slug,name,description,price_per_night_bif,capacity_adults,surface_m2,view,photo_main,is_active,sort_order) VALUES(:cat,:slug,:n,:d,:p,:c,:s,:v,:ph,:a,:so)")
                    ->execute([':cat'=>$cat,':slug'=>$slug.'-'.time(),':n'=>$name,':d'=>$desc,':p'=>$price,':c'=>$cap,':s'=>$surface,':v'=>$view,':ph'=>$photo,':a'=>$active,':so'=>$sort]);
                header('Location:/admin/rooms/?added=1'); exit;
            }
        }
    }
}
$csrfField=CsrfGuard::field();
define('PAGE_TITLE',($id?'Modifier':'Ajouter').' Chambre — Admin');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header">
  <h1 class="admin-page-title"><a href="/admin/rooms/" style="color:var(--admin-muted);font-weight:400;font-size:.9rem;">Chambres</a> → <?php echo $id?'Modifier':'Nouvelle Chambre';?></h1>
  <a href="/admin/logout.php" class="btn-admin-sm">Déconnexion</a>
</header>
<?php if($success):?><div style="background:rgba(34,197,94,.12);border:1px solid #22c55e;color:#86efac;padding:.9rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;"><?php echo e($success);?></div><?php endif;?>
<?php if($errors):?><div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5;padding:.9rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;"><?php foreach($errors as $e) echo e($e).'<br>';?></div><?php endif;?>
<form method="POST" class="admin-form-card">
<?php echo $csrfField;?>
<div class="admin-form-title"><?php echo $id?'Modifier la Chambre':'Nouvelle Chambre';?></div>
<div class="admin-form-grid">
  <div><label class="admin-form-label">Nom *</label>
    <input name="name" class="admin-form-input" required maxlength="150" value="<?php echo e($room['name']??'');?>"></div>
  <div><label class="admin-form-label">Catégorie *</label>
    <select name="category_id" class="admin-form-select">
      <?php foreach($cats as $c):?><option value="<?php echo $c['id'];?>" <?php echo ($room['category_id']??1)==$c['id']?'selected':'';?>><?php echo e($c['name']);?></option><?php endforeach;?>
    </select></div>
  <div><label class="admin-form-label">Prix / nuit (BIF) *</label>
    <input name="price" type="number" min="1" class="admin-form-input" value="<?php echo $room['price_per_night_bif']??'';?>"></div>
  <div><label class="admin-form-label">Capacité adultes</label>
    <input name="capacity_adults" type="number" min="1" max="10" class="admin-form-input" value="<?php echo $room['capacity_adults']??2;?>"></div>
  <div><label class="admin-form-label">Surface (m²)</label>
    <input name="surface_m2" type="number" min="10" class="admin-form-input" value="<?php echo $room['surface_m2']??30;?>"></div>
  <div><label class="admin-form-label">Vue</label>
    <input name="view" class="admin-form-input" maxlength="80" value="<?php echo e($room['view']??'');?>"></div>
  <div><label class="admin-form-label">Photo principale (chemin relatif)</label>
    <input name="photo_main" class="admin-form-input" value="<?php echo e($room['photo_main']??'assets/images/hero_hotel.jpg');?>"></div>
  <div><label class="admin-form-label">Ordre d'affichage</label>
    <input name="sort_order" type="number" class="admin-form-input" value="<?php echo $room['sort_order']??0;?>"></div>
</div>
<div style="margin-top:1.25rem;">
  <label class="admin-form-label">Description</label>
  <textarea name="description" class="admin-form-textarea" rows="4" style="width:100%;"><?php echo e($room['description']??'');?></textarea>
</div>
<label style="display:flex;align-items:center;gap:.75rem;margin-top:1rem;cursor:pointer;">
  <input type="checkbox" name="is_active" value="1" <?php echo ($room['is_active']??1)?'checked':'';?>>
  <span class="admin-form-label" style="margin:0;">Chambre active (visible en ligne)</span>
</label>
<div style="margin-top:1.75rem;display:flex;gap:1rem;">
  <button type="submit" class="btn-admin-sm" style="background:var(--admin-gold);color:#070C14;border:none;cursor:pointer;padding:.75rem 2rem;font-size:.9rem;">
    <?php echo $id?'Enregistrer':'Créer la Chambre';?>
  </button>
  <a href="/admin/rooms/" class="btn-admin-sm">Annuler</a>
</div>
</form>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
