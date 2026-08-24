<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\CsrfGuard; use App\Payments\PaymentService;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('payments.refund');
$adminUser=(new AdminAuth($pdo))->user();
$id=(int)($_GET['id']??0);
if(!$id){header('Location:/admin/payments/');exit;}
$stmt=$pdo->prepare("SELECT p.*,b.reference FROM payments p JOIN bookings b ON b.id=p.booking_id WHERE p.id=:id AND p.payment_status='successful'");
$stmt->execute([':id'=>$id]); $payment=$stmt->fetch();
if(!$payment){header('Location:/admin/payments/');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST' && CsrfGuard::verifyRequest()){
    $amount=(int)str_replace([' ',','],'',$_POST['amount_bif']??'0');
    $reason=trim($_POST['reason']??'');
    if($amount<=0||$amount>(int)$payment['amount_bif']) $error='Montant invalide.';
    elseif(strlen($reason)<5) $error='Raison obligatoire (min. 5 car.).';
    else {
        $service=new PaymentService($pdo);
        $result=$service->initiateRefund($id,$amount,$reason,(int)$adminUser['id']);
        if($result['success']){header('Location:/admin/payments/?refunded=1');exit;}
        else $error=$result['error']??'Échec.';
    }
}
define('PAGE_TITLE','Remboursement — Admin');
?><!DOCTYPE html><html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo PAGE_TITLE;?></title><meta name="robots" content="noindex">
<link rel="stylesheet" href="/assets/css/style.css"><link rel="stylesheet" href="/assets/css/admin.css">
</head><body class="admin-body">
<?php require_once dirname(__DIR__).'/includes/sidebar.php';?>
<main class="admin-main">
<header class="admin-header">
  <h1 class="admin-page-title">Remboursement Paiement #<?php echo $id;?></h1>
  <a href="/admin/payments/" class="btn-admin-sm">← Retour</a>
</header>
<?php if($error):?><div style="background:rgba(239,68,68,.12);border:1px solid #ef4444;color:#fca5a5;padding:.9rem;border-radius:8px;margin-bottom:1.5rem;"><?php echo e($error);?></div><?php endif;?>
<div class="admin-form-card" style="max-width:500px;">
  <div class="admin-form-title" style="color:#f59e0b;">⚠️ Remboursement</div>
  <p style="color:var(--admin-muted);font-size:.85rem;margin-bottom:1.25rem;">
    Réservation : <strong style="color:var(--admin-gold);"><?php echo e($payment['reference']);?></strong><br>
    Montant payé : <strong><?php echo number_format((int)$payment['amount_bif'],0,',',' ');?> BIF</strong><br>
    Taux contractuel : <strong>1 USD = <?php echo number_format((float)$payment['exchange_rate'],0);?> BIF</strong>
  </p>
  <form method="POST">
    <?php echo CsrfGuard::field();?>
    <div style="margin-bottom:1.25rem;">
      <label class="admin-form-label">Montant à rembourser (BIF) *</label>
      <input name="amount_bif" type="number" min="1" max="<?php echo $payment['amount_bif'];?>" class="admin-form-input" value="<?php echo $payment['amount_bif'];?>">
      <span style="font-size:.75rem;color:var(--admin-muted);">Max : <?php echo number_format((int)$payment['amount_bif'],0,',',' ');?> BIF</span>
    </div>
    <div style="margin-bottom:1.5rem;">
      <label class="admin-form-label">Raison du remboursement *</label>
      <input name="reason" class="admin-form-input" required minlength="5" maxlength="255" placeholder="Ex: Annulation pour raison médicale">
    </div>
    <button type="submit" class="btn-admin-sm" style="background:#f59e0b;color:#000;border:none;cursor:pointer;padding:.75rem 2rem;"
            onclick="return confirm('Confirmer le remboursement ? Cette action est irréversible.')">Initier le Remboursement</button>
    <a href="/admin/payments/" class="btn-admin-sm" style="margin-left:.75rem;">Annuler</a>
  </form>
</div>
</main>
<script src="/assets/js/admin.js"></script>
</body></html>
