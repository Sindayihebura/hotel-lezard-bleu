<?php

declare(strict_types=1);

/**
 * Administration — Page de connexion
 * Hôtel Le Lézard Bleu & Spa
 */

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AdminAuth;
use App\Security\CsrfGuard;

// Si déjà connecté, rediriger vers dashboard
$pdo = getDB();
if ($pdo !== null) {
    $adminAuth = new AdminAuth($pdo);
    if ($adminAuth->check()) {
        safe_redirect('/admin/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CsrfGuard::verifyRequest()) {
        $error = 'Jeton de sécurité invalide. Veuillez recharger la page.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($pdo !== null) {
            $adminAuth = new AdminAuth($pdo);
            $result = $adminAuth->attempt($email, $password);

            if ($result['success']) {
                safe_redirect('/admin/dashboard.php');
            } else {
                // Message générique — ne pas révéler si email ou mot de passe
                $error = 'Identifiants invalides ou compte bloqué.';
            }
        }
    }
}

$csrfField = CsrfGuard::field();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — Le Lézard Bleu</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #070C14; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .admin-login-card {
            background: var(--bg-dark-card, #0F1826);
            border: 1px solid var(--border-gold, #D4AF37);
            border-radius: 12px;
            padding: 3rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .admin-login-title { color: #D4AF37; font-size: 1.5rem; margin-bottom: 0.25rem; }
        .admin-login-sub { color: #8899aa; font-size: 0.85rem; margin-bottom: 2rem; }
        .form-label { display: block; color: #c0c8d0; font-size: 0.85rem; margin-bottom: 0.4rem; }
        .form-input {
            width: 100%; background: rgba(7,12,20,0.7); border: 1px solid #1e2d40;
            color: #e0e8f0; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.95rem;
            margin-bottom: 1.25rem; box-sizing: border-box;
        }
        .form-input:focus { outline: none; border-color: #D4AF37; }
        .btn-admin-login {
            width: 100%; background: linear-gradient(135deg, #D4AF37, #B8960C);
            color: #070C14; border: none; padding: 0.9rem; border-radius: 8px;
            font-weight: 700; font-size: 1rem; cursor: pointer;
        }
        .error-msg { background: rgba(220,38,38,0.15); border: 1px solid #dc2626;
            color: #fca5a5; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="admin-login-card">
        <div style="text-align:center; margin-bottom:1.5rem;">
            <div style="font-size:2rem; color:#D4AF37;">🏨</div>
        </div>
        <h1 class="admin-login-title" style="text-align:center;">Administration</h1>
        <p class="admin-login-sub" style="text-align:center;">Hôtel Le Lézard Bleu & Spa<br>Bujumbura, Burundi</p>

        <?php if ($error !== ''): ?>
            <div class="error-msg"><?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/login.php" autocomplete="off">
            <?php echo $csrfField; ?>

            <label class="form-label" for="email">Adresse e-mail</label>
            <input type="email" id="email" name="email" class="form-input"
                   autocomplete="username" required maxlength="150"
                   placeholder="admin@lezardbleu-hotel.bi">

            <label class="form-label" for="password">Mot de passe</label>
            <input type="password" id="password" name="password" class="form-input"
                   autocomplete="current-password" required maxlength="128">

            <button type="submit" class="btn-admin-login">
                Se Connecter
            </button>
        </form>

        <p style="text-align:center; margin-top:1.5rem; font-size:0.75rem; color:#445566;">
            Accès réservé au personnel autorisé.<br>
            Toutes les connexions sont journalisées.
        </p>
    </div>
</body>
</html>
