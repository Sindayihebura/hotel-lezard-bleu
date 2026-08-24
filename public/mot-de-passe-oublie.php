<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AuthService;
use App\Security\CsrfGuard;

$pdo  = getDB();
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (CsrfGuard::verifyRequest()) {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $pdo !== null) {
            $auth = new AuthService($pdo);
            $auth->requestPasswordReset($email, client_ip());
        }
        // Toujours afficher "succès" — ne pas révéler si l'email existe
        $sent = true;
    }
}

$csrfField = CsrfGuard::field();
define('PAGE_TITLE', 'Mot de passe oublié — Le Lézard Bleu');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section style="min-height: 80vh; display:flex; align-items:center; padding:5rem 1rem; background:var(--bg-dark-main);">
  <div class="container" style="max-width:420px; margin:0 auto;">

    <div style="text-align:center; margin-bottom:2rem;">
      <span class="section-subtitle">SÉCURITÉ</span>
      <h1 class="section-title" style="font-size:2rem;">Mot de passe oublié</h1>
    </div>

    <div style="background:var(--bg-dark-card); border:1px solid var(--border-gold); border-radius:var(--radius-lg); padding:2.5rem;">

      <?php if ($sent): ?>
        <div style="text-align:center; padding:1rem 0;">
          <div style="font-size:2.5rem; margin-bottom:1rem;">📧</div>
          <h2 style="color:var(--text-light-primary); margin-bottom:0.75rem; font-size:1.3rem;">E-mail envoyé</h2>
          <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
            Si un compte existe pour cette adresse, un lien de réinitialisation
            valable <strong>1 heure</strong> vous a été envoyé.
          </p>
          <a href="/public/connexion.php" class="btn btn-gold" style="padding:0.8rem 2rem;">
            Retour à la Connexion
          </a>
        </div>
      <?php else: ?>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.75rem;">
          Entrez votre adresse e-mail et nous vous enverrons un lien pour
          réinitialiser votre mot de passe.
        </p>
        <form method="POST" action="/public/mot-de-passe-oublie.php">
          <?php echo $csrfField; ?>
          <div class="form-group" style="margin-bottom:1.5rem;">
            <label for="email" class="form-label">Adresse e-mail</label>
            <input type="email" id="email" name="email" class="form-input"
                   required maxlength="150" autocomplete="email"
                   placeholder="votre@email.com">
          </div>
          <button type="submit" class="btn btn-gold" style="width:100%; padding:1rem;">
            Envoyer le lien
          </button>
        </form>
        <p style="text-align:center; margin-top:1.5rem;">
          <a href="/public/connexion.php" style="color:var(--accent-gold-primary); font-size:0.85rem;">
            ← Retour à la connexion
          </a>
        </p>
      <?php endif; ?>

    </div>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
