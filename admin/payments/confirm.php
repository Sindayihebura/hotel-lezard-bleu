<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard; use App\Payments\PaymentService;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('payments.confirm');
$adminUser=(new AdminAuth($pdo))->user();
$id=(int)($_GET['id']??0);
if(!$id){header('Location:/admin/payments/');exit;}
$stmt=$pdo->prepare("SELECT p.*,b.reference FROM payments p JOIN bookings b ON b.id=p.booking_id WHERE p.id=:id");
$stmt->execute([':id'=>$id]); $payment=$stmt->fetch();
if(!$payment){header('Location:/admin/payments/');exit;}
$error=''; $success='';
if($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()){
    $service=new PaymentService($pdo);
    $ok=$service->confirmManual($id,(int)$adminUser['id'],trim($_POST['notes']??''));
    if($ok){ header('Location:/admin/reservations/view.php?id='.$payment['booking_id'].'&confirmed=1'); exit; }
    else $error='Confirmation échouée.';
}
define('PAGE_TITLE','Confirmer Paiement — Admin');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header">
  <h1 class="admin-page-title">Confirmer Paiement #<?php echo $id;?></h1>
  <a href="/admin/payments/" class="btn-admin-sm">← Retour</a>
</header>
<?php if($error):?><div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php echo e($error);?></div><?php endif;?>
<div class="admin-form-card" style="max-width:500px;">
  <div class="admin-form-title">Confirmation de Paiement Manuel</div>
  <p style="color:var(--admin-muted);font-size:.85rem;margin-bottom:1.25rem;">
    Vous allez confirmer le paiement de la réservation <strong style="color:var(--admin-gold);"><?php echo e($payment['reference']);?></strong>.<br>
    Montant : <strong><?php echo number_format((int)$payment['amount_bif'],0,',',' ');?> BIF</strong><br>
    Fournisseur : <strong><?php echo e(ucfirst($payment['provider']));?></strong>
  </p>
  <form method="POST">
    <?php echo CsrfGuard::field();?>
    <div style="margin-bottom:1.25rem;">
      <label class="admin-form-label">Notes (optionnel)</label>
      <input name="notes" class="admin-form-input" placeholder="Ex: Reçu espèces à la réception le 16/08/2026" maxlength="255">
    </div>
    <button type="submit" class="btn-admin-sm" style="background:#22c55e;color:#000;border:none;cursor:pointer;padding:.75rem 2rem;"
            onclick="return confirm('Confirmer ce paiement ?')">✓ Confirmer le Paiement</button>
    <a href="/admin/payments/" class="btn-admin-sm" style="margin-left:.75rem;">Annuler</a>
  </form>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
