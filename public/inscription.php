<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AuthService;
use App\Security\CsrfGuard;
use App\Validators\AuthValidator;

$pdo = getDB();

if (!empty($_SESSION['customer_auth'])) {
    safe_redirect('/public/mon-compte.php');
}

$errors   = [];
$success  = false;
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!CsrfGuard::verifyRequest()) {
        $errors['csrf'] = 'Jeton de sécurité invalide. Rechargez la page.';
    } else {
        $secCfg    = require base_path('config/security.php');
        $validator = new AuthValidator($secCfg['password']['min_length']);
        $formData  = $_POST;

        if (!$validator->validateRegister($formData)) {
            $errors = $validator->errors();
        } elseif ($pdo !== null) {
            $auth   = new AuthService($pdo);
            $result = $auth->register($formData, client_ip());

            if ($result['success']) {
                $success = true;
            } else {
                $errors['general'] = match ($result['error']) {
                    'rate_limit' => 'Trop de tentatives. Réessayez plus tard.',
                    default      => 'Une erreur est survenue. Veuillez réessayer.',
                };
            }
        }
    }
}

$csrfField = CsrfGuard::field();
$minLen    = (int) env('PASSWORD_MIN_LENGTH', '10');
define('PAGE_TITLE', 'Créer un Compte — Le Lézard Bleu Hôtel & Spa');
require_once dirname(__DIR__) . '/includes/header.php';
?>

<section style="min-height: 80vh; display: flex; align-items: center; padding: 5rem 1rem; background: var(--bg-dark-main);">
  <div class="container" style="max-width: 540px; margin: 0 auto;">

    <div style="text-align: center; margin-bottom: 2rem;">
      <span class="section-subtitle">ESPACE CLIENT</span>
      <h1 class="section-title" style="font-size: 2rem;">Créer un Compte</h1>
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Suivez vos réservations, téléchargez vos factures, gérez vos préférences.
      </p>
    </div>

    <div style="background: var(--bg-dark-card); border: 1px solid var(--border-gold); border-radius: var(--radius-lg); padding: 2.5rem; box-shadow: var(--shadow-lux);">

      <?php if ($success): ?>
        <div style="text-align: center; padding: 1.5rem 0;">
          <div style="font-size: 3rem; color: var(--accent-gold-primary); margin-bottom: 1rem;">✓</div>
          <h2 style="color: var(--text-light-primary); margin-bottom: 0.75rem;">Compte créé !</h2>
          <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
            Un lien de vérification a été envoyé à votre adresse e-mail.<br>
            Vérifiez votre boîte de réception (et les spams).
          </p>
          <a href="/public/connexion.php" class="btn btn-gold" style="padding: 0.85rem 2rem;">
            Se Connecter
          </a>
        </div>
      <?php else: ?>

        <?php if (!empty($errors['general']) || !empty($errors['csrf'])): ?>
          <div style="background: rgba(220,38,38,0.12); border: 1px solid #dc2626; color: #fca5a5;
                      padding: 0.9rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem;">
            ⚠️ <?php echo e($errors['general'] ?? $errors['csrf']); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="/public/inscription.php" autocomplete="off" novalidate>
          <?php echo $csrfField; ?>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div class="form-group">
              <label for="first_name" class="form-label">Prénom *</label>
              <input type="text" id="first_name" name="first_name" class="form-input"
                     required maxlength="80" autocomplete="given-name"
                     value="<?php echo e($formData['first_name'] ?? ''); ?>"
                     style="<?php echo !empty($errors['first_name']) ? 'border-color:#dc2626' : ''; ?>">
              <?php if (!empty($errors['first_name'])): ?>
                <span style="color:#fca5a5;font-size:0.75rem;"><?php echo e($errors['first_name']); ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="last_name" class="form-label">Nom *</label>
              <input type="text" id="last_name" name="last_name" class="form-input"
                     required maxlength="80" autocomplete="family-name"
                     value="<?php echo e($formData['last_name'] ?? ''); ?>"
                     style="<?php echo !empty($errors['last_name']) ? 'border-color:#dc2626' : ''; ?>">
              <?php if (!empty($errors['last_name'])): ?>
                <span style="color:#fca5a5;font-size:0.75rem;"><?php echo e($errors['last_name']); ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="email" class="form-label">Adresse e-mail *</label>
            <input type="email" id="email" name="email" class="form-input"
                   required maxlength="150" autocomplete="email"
                   value="<?php echo e($formData['email'] ?? ''); ?>"
                   style="<?php echo !empty($errors['email']) ? 'border-color:#dc2626' : ''; ?>">
            <?php if (!empty($errors['email'])): ?>
              <span style="color:#fca5a5;font-size:0.75rem;"><?php echo e($errors['email']); ?></span>
            <?php endif; ?>
          </div>

          <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="phone" class="form-label">Téléphone (optionnel)</label>
            <input type="tel" id="phone" name="phone" class="form-input"
                   maxlength="20" autocomplete="tel" placeholder="+257 79 00 00 00"
                   value="<?php echo e($formData['phone'] ?? ''); ?>">
            <?php if (!empty($errors['phone'])): ?>
              <span style="color:#fca5a5;font-size:0.75rem;"><?php echo e($errors['phone']); ?></span>
            <?php endif; ?>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div class="form-group">
              <label for="password" class="form-label">Mot de passe * (min. <?php echo $minLen; ?> car.)</label>
              <input type="password" id="password" name="password" class="form-input"
                     required maxlength="128" autocomplete="new-password"
                     style="<?php echo !empty($errors['password']) ? 'border-color:#dc2626' : ''; ?>">
              <?php if (!empty($errors['password'])): ?>
                <span style="color:#fca5a5;font-size:0.75rem;"><?php echo e($errors['password']); ?></span>
              <?php endif; ?>
            </div>
            <div class="form-group">
              <label for="password_confirm" class="form-label">Confirmer *</label>
              <input type="password" id="password_confirm" name="password_confirm" class="form-input"
                     required maxlength="128" autocomplete="new-password"
                     style="<?php echo !empty($errors['password_confirm']) ? 'border-color:#dc2626' : ''; ?>">
              <?php if (!empty($errors['password_confirm'])): ?>
                <span style="color:#fca5a5;font-size:0.75rem;"><?php echo e($errors['password_confirm']); ?></span>
              <?php endif; ?>
            </div>
          </div>

          <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; margin-bottom: 1.75rem;">
            <input type="checkbox" name="newsletter" value="1"
                   <?php echo !empty($formData['newsletter']) ? 'checked' : ''; ?>
                   style="margin-top: 3px; accent-color: var(--accent-gold-primary);">
            <span style="font-size: 0.8rem; color: var(--text-muted);">
              Je souhaite recevoir les offres exclusives du Lézard Bleu par e-mail.
            </span>
          </label>

          <button type="submit" class="btn btn-gold" style="width: 100%; padding: 1rem; font-size: 1rem;">
            Créer Mon Compte
          </button>

          <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 1rem; text-align: center;">
            En créant un compte, vous acceptez nos
            <a href="#" style="color: var(--accent-gold-primary);">CGV</a> et notre
            <a href="#" style="color: var(--accent-gold-primary);">politique de confidentialité</a>.
          </p>
        </form>

        <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.75rem; border-top: 1px solid var(--border-light);">
          <p style="color: var(--text-muted); font-size: 0.9rem;">
            Déjà un compte ?
            <a href="/public/connexion.php" style="color: var(--accent-gold-primary); font-weight: 600;">
              Se connecter
            </a>
          </p>
        </div>

      <?php endif; ?>
    </div>
  </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
