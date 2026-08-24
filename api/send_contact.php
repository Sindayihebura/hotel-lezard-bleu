<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_error('METHOD','Méthode non autorisée.',405); exit;
}

use App\Security\CsrfGuard;
use App\Security\RateLimiter;

$pdo = getDB();
if (!$pdo) { echo json_error('DB_ERROR','Service indisponible.',503); exit; }

if (!CsrfGuard::verifyRequest()) {
    echo json_error('CSRF_INVALID','Jeton invalide.',403); exit;
}

// Rate limiting contact : 5 messages / heure
$rl = new RateLimiter($pdo);
$k  = 'contact:' . client_ip();
if ($rl->tooManyAttempts($k, 5, 3600)) {
    echo json_error('RATE_LIMIT','Trop de messages. Attendez une heure.',429); exit;
}

$nom     = trim($_POST['nom']      ?? '');
$email   = trim($_POST['email']    ?? '');
$tel     = trim($_POST['telephone'] ?? '');
$sujet   = trim($_POST['sujet']    ?? 'Demande d\'information');
$message = trim($_POST['message']  ?? '');
$locale  = $_SESSION['locale'] ?? 'fr';

if (strlen($nom) < 2 || strlen($nom) > 150)
    { echo json_error('INVALID','Nom invalide.',422); exit; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    { echo json_error('INVALID','E-mail invalide.',422); exit; }
if (strlen($message) < 10 || strlen($message) > 3000)
    { echo json_error('INVALID','Message invalide (10–3000 caractères).',422); exit; }

$pdo->prepare("
    INSERT INTO messages_contact (nom,email,telephone,sujet,message,locale,ip_address)
    VALUES (:nom,:email,:tel,:sujet,:msg,:locale,:ip)
")->execute([
    ':nom'    => $nom,
    ':email'  => $email,
    ':tel'    => $tel ?: null,
    ':sujet'  => substr($sujet, 0, 150),
    ':msg'    => $message,
    ':locale' => $locale,
    ':ip'     => client_ip(),
]);

$rl->hit($k, 3600);
echo json_success(['sent' => true, 'message' => 'Votre message a été envoyé.']);
