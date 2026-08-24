<?php

declare(strict_types=1);

/**
 * Seed 002 — Création du super admin initial
 * Hôtel Le Lézard Bleu & Spa
 *
 * USAGE CLI UNIQUEMENT :
 *   php migrations/002_seed_admin.php
 *
 * Ce script est à exécuter une seule fois lors de l'installation.
 * Il demande interactivement le mot de passe pour ne jamais le logger.
 * Ne jamais committer de mots de passe dans ce fichier.
 */

if (PHP_SAPI !== 'cli') {
    exit('CLI uniquement.');
}

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

$pdo = \Config\Database::getInstance();
if ($pdo === null) {
    echo "Erreur : impossible de se connecter à la base de données.\n";
    exit(1);
}

echo "\n=== Création du Super Admin — Hôtel Le Lézard Bleu ===\n\n";

// Saisie interactive sécurisée
$firstName = trim(readline("Prénom : "));
$lastName  = trim(readline("Nom    : "));
$email     = trim(readline("Email  : "));

// Masquer la saisie du mot de passe (Linux/Mac)
if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
    // Windows : pas de stty, on avertit
    echo "Mot de passe (affiché en clair sur Windows — utilisez un terminal sécurisé) : ";
    $password = trim(fgets(STDIN));
} else {
    echo "Mot de passe (masqué) : ";
    system('stty -echo');
    $password = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
}

// Validation
if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    echo "Tous les champs sont obligatoires.\n";
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Adresse e-mail invalide.\n";
    exit(1);
}

$minLength = (int) env('PASSWORD_MIN_LENGTH', '10');
if (strlen($password) < $minLength) {
    echo "Le mot de passe doit contenir au moins {$minLength} caractères.\n";
    exit(1);
}

// Vérifier que l'email n'existe pas déjà
$stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
if ($stmt->fetchColumn()) {
    echo "Un compte avec cet email existe déjà.\n";
    exit(1);
}

// Hacher le mot de passe
$cost = (int) env('BCRYPT_COST', '12');
$hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => $cost]);
// Effacer le mot de passe de la mémoire
$password = '';

// Rôle super_admin = id 1
$stmt = $pdo->prepare("
    INSERT INTO admin_users
      (role_id, first_name, last_name, email, password_hash, is_active, password_changed_at)
    VALUES
      (1, :fn, :ln, :email, :hash, 1, UTC_TIMESTAMP())
");
$stmt->execute([
    ':fn'    => $firstName,
    ':ln'    => $lastName,
    ':email' => $email,
    ':hash'  => $hash,
]);

$newId = $pdo->lastInsertId();
echo "\n✓ Super Admin créé avec succès (ID #{$newId})\n";
echo "  Email : {$email}\n";
echo "  Rôle  : super_admin\n\n";
echo "Connectez-vous maintenant sur : /admin/login.php\n\n";
