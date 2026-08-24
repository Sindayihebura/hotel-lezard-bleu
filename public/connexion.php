<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AuthService;
use App\Security\CsrfGuard;
use App\Validators\AuthValidator;

$pdo = getDB();

// Déjà connecté → espace client
if (!empty($_SESSION['customer_auth'])) {
    safe_redirect('/public/mon-compte.php');
}

$error    = '';
$success  = '';
$redirect = filter_var($_GET['redirect'] ?? '', FILTER_SANITIZE_URL);
// Valider que le redirect est bien interne (anti open-redirect)
if ($redirect !== '' && !preg_match('#^/#', $redirect)) {
    $redirect = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!CsrfGuard::verifyRequest()) {
        $error = 'Jeton de sécurité invalide. Rechargez la page.';
    } else {
        $validator = new AuthValidator();
        $data = [
            'email'    => trim($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
        ];

        if (!$validator->validateLogin($data)) {
            $error = $validator->firstError();
        } elseif ($pdo !== null) {
            $auth   = new AuthService($pdo);
            $result = $auth->attempt($data['email'], $data['password'], client_ip());

            if ($result['success']) {
                // Régénération déjà faite dans AuthService
                $dest = $redirect ?: '/public/mon-compte.php';
                safe_redirect($dest);
            } else {
                $error = match ($result['error']) {
                    'locked'   => 'Trop de tentatives. Veuillez patienter ' . $result['lockout_minutes'] . ' minutes.',
                    'inactive' => 'Ce compte est désactivé. Contactez la réception.',
                    default    => 'Identifiants incorrects.', // Message générique intentionnel
                };
            }
        }
    }
}

$csrfField = CsrfGuard::field();
define('PAGE_TITLE', 'Connexion — Le Lézard Bleu Hôtel & Spa');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section style="min-height: 80vh; display: flex; align-items: center; padding: 5rem 1rem; background: var(--bg-dark-main);">
  <div class="container" style="max-width: 460px; margin: 0 auto;">

    <div style="text-align: center; margin-bottom: 2rem;">
      <span class="section-subtitle">ESPACE CLIENT</span>
      <h1 class="section-title" style="font-size: 2rem;">Connexion</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Accédez à vos réservations et factures
      </p>
    </div>

    <div style="background: var(--bg-dark-card); border: 1px solid var(--border-gold); border-radius: var(--radius-lg); padding: 2.5rem; box-shadow: var(--shadow-lux);">

      <?php if ($error !== ''): ?>
        <div style="background: rgba(220,38,38,0.12); border: 1px solid #dc2626; color: #fca5a5;
                    padding: 0.9rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem;">
          ⚠️ <?php echo e($error); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($_GET['verified'])): ?>
        <div style="background: rgba(34,197,94,0.12); border: 1px solid #22c55e; color: #86efac;
                    padding: 0.9rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem;">
          ✓ E-mail vérifié avec succès. Vous pouvez vous connecter.
        </div>
      <?php endif; ?>

      <?php if (!empty($_GET['reset'])): ?>
        <div style="background: rgba(34,197,94,0.12); border: 1px solid #22c55e; color: #86efac;
                    padding: 0.9rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem;">
          ✓ Mot de passe réinitialisé. Connectez-vous avec votre nouveau mot de passe.
        </div>
      <?php endif; ?>

      <form method="POST" action="/public/connexion.php<?php echo $redirect ? '?redirect=' . urlencode($redirect) : ''; ?>"
            autocomplete="off" novalidate>
        <?php echo $csrfField; ?>

        <div class="form-group" style="margin-bottom: 1.25rem;">
          <label for="email" class="form-label">Adresse e-mail</label>
          <input type="email" id="email" name="email" class="form-input"
                 autocomplete="username" required maxlength="150"
                 value="<?php echo e($_POST['email'] ?? ''); ?>"
                 placeholder="votre@email.com">
        </div>

        <div class="form-group" style="margin-bottom: 0.75rem;">
          <label for="password" class="form-label">Mot de passe</label>
          <input type="password" id="password" name="password" class="form-input"
                 autocomplete="current-password" required maxlength="128">
        </div>

        <div style="text-align: right; margin-bottom: 1.75rem;">
          <a href="/public/mot-de-passe-oublie.php" style="font-size: 0.8rem; color: var(--accent-gold-primary);">
            Mot de passe oublié ?
          </a>
        </div>

        <button type="submit" class="btn btn-gold" style="width: 100%; padding: 1rem; font-size: 1rem;">
          Se Connecter
        </button>
      </form>

      <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.75rem; border-top: 1px solid var(--border-light);">
        <p style="color: var(--text-muted); font-size: 0.9rem;">
          Pas encore de compte ?
          <a href="/public/inscription.php" style="color: var(--accent-gold-primary); font-weight: 600;">
            Créer un compte
          </a>
        </p>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.5rem;">
          Ou réservez en tant qu'<a href="/reservation.php" style="color: var(--accent-gold-primary);">invité</a>
        </p>
      </div>

    </div>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
