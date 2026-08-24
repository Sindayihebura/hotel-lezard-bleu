<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AuthService;
use App\Security\CsrfGuard;
use App\Validators\AuthValidator;

$pdo    = getDB();
$token  = trim($_GET['token'] ?? '');
$error  = '';
$ok     = false;

// Vérifier que le token a le bon format
if (!$token || strlen($token) !== 64 || !ctype_xdigit($token)) {
    $token = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token) {
    if (!CsrfGuard::verifyRequest()) {
        $error = 'Jeton de sécurité invalide. Rechargez la page.';
    } else {
        $minLen    = (int)env('PASSWORD_MIN_LENGTH','10');
        $validator = new AuthValidator($minLen);
        $data      = ['token' => $token, 'password' => $_POST['password'] ?? '', 'password_confirm' => $_POST['password_confirm'] ?? ''];
        if (!$validator->validatePasswordReset($data)) {
            $error = $validator->firstError();
        } elseif ($pdo) {
            $auth   = new AuthService($pdo);
            $result = $auth->resetPassword($token, $data['password'], client_ip());
            if ($result['success']) {
                $ok = true;
            } else {
                $error = $result['error'] === 'invalid_token'
                    ? 'Lien invalide ou expiré. Demandez un nouveau lien.'
                    : 'Erreur. Veuillez réessayer.';
            }
        }
    }
}

$minLen    = (int)env('PASSWORD_MIN_LENGTH','10');
$csrfField = CsrfGuard::field();
define('PAGE_TITLE','Réinitialiser le mot de passe — Le Lézard Bleu');
require_once dirname(__DIR__) . '/includes/header.php';
?>
<section style="min-height:80vh;display:flex;align-items:center;padding:5rem 1rem;background:var(--bg-dark-main);">
<div class="container" style="max-width:420px;margin:0 auto;">
  <div style="text-align:center;margin-bottom:2rem;">
    <span class="section-subtitle">SÉCURITÉ</span>
    <h1 class="section-title" style="font-size:2rem;">Nouveau Mot de Passe</h1>
  </div>
  <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:2.5rem;">
    <?php if (!$token): ?>
      <div style="text-align:center;color:#fca5a5;">Lien invalide ou manquant. <a href="/public/mot-de-passe-oublie.php" style="color:var(--accent-gold-primary)">Demander un nouveau lien.</a></div>
    <?php elseif ($ok): ?>
      <div style="text-align:center;">
        <div style="font-size:2.5rem;margin-bottom:1rem;">✓</div>
        <h2 style="color:var(--text-light-primary);font-size:1.3rem;margin-bottom:.75rem;">Mot de passe modifié !</h2>
        <a href="/public/connexion.php?reset=1" class="btn btn-gold" style="padding:.85rem 2rem;">Se Connecter</a>
      </div>
    <?php else: ?>
      <?php if ($error): ?>
        <div style="background:rgba(220,38,38,.12);border:1px solid #dc2626;color:#fca5a5;padding:.9rem 1.25rem;border-radius:8px;margin-bottom:1.5rem;font-size:.9rem;">⚠️ <?php echo e($error); ?></div>
      <?php endif; ?>
      <form method="POST" action="/public/reinitialiser-mot-de-passe.php?token=<?php echo urlencode($token); ?>">
        <?php echo $csrfField; ?>
        <div class="form-group" style="margin-bottom:1.25rem;">
          <label class="form-label">Nouveau mot de passe (min. <?php echo $minLen; ?> caractères)</label>
          <input type="password" name="password" class="form-input" required maxlength="128" autocomplete="new-password">
        </div>
        <div class="form-group" style="margin-bottom:1.75rem;">
          <label class="form-label">Confirmer le mot de passe</label>
          <input type="password" name="password_confirm" class="form-input" required maxlength="128" autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-gold" style="width:100%;padding:1rem;">Enregistrer</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</section>
<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
