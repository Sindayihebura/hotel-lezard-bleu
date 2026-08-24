<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AuthService;

$pdo   = getDB();
$token = trim($_GET['token'] ?? '');
$ok    = false;

if ($token && strlen($token) === 64 && ctype_xdigit($token) && $pdo) {
    $auth = new AuthService($pdo);
    $ok   = $auth->verifyEmail($token);
}

define('PAGE_TITLE','Vérification e-mail — Le Lézard Bleu');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section style="min-height:80vh;display:flex;align-items:center;padding:5rem 1rem;background:var(--bg-dark-main);">
<div class="container" style="max-width:420px;margin:0 auto;text-align:center;">
  <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:3rem;">
    <?php if ($ok): ?>
      <div style="font-size:3rem;margin-bottom:1rem;">✅</div>
      <h1 style="color:var(--text-light-primary);font-size:1.5rem;margin-bottom:.75rem;">E-mail vérifié !</h1>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;">Votre adresse a été confirmée. Vous pouvez vous connecter.</p>
      <a href="/public/connexion.php?verified=1" class="btn btn-gold" style="padding:.85rem 2rem;">Se Connecter</a>
    <?php else: ?>
      <div style="font-size:3rem;margin-bottom:1rem;">❌</div>
      <h1 style="color:var(--text-light-primary);font-size:1.5rem;margin-bottom:.75rem;">Lien invalide</h1>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;">Ce lien est invalide ou a expiré. Connectez-vous pour renvoyer un lien.</p>
      <a href="/public/connexion.php" class="btn btn-gold" style="padding:.85rem 2rem;">Connexion</a>
    <?php endif; ?>
  </div>
</div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
