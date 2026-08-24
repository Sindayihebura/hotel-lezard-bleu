<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard; use App\Security\Logger;

$pdo   = getDB();
$guard = new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php');
$guard->requirePermission('customers.export');
$adminUser = (new AdminAuth($pdo))->user();

// Log de l'export (action sensible)
(new Logger($pdo))->audit(
    Logger::ACTION_EXPORT, 'customers', null,
    null, ['format' => 'csv', 'max' => 5000],
    (int)$adminUser['id']
);

// Récupérer les clients (sans données sensibles)
$stmt = $pdo->query("
    SELECT id, first_name, last_name, email, phone, country_code,
           preferred_locale, preferred_currency, is_active,
           newsletter_consent, created_at, last_login_at,
           email_verified_at
    FROM customers
    WHERE is_active = 1
    ORDER BY created_at DESC
    LIMIT 5000
");
$customers = $stmt->fetchAll();

// Générer le CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="clients_lezardbleu_' . date('Y-m-d') . '.csv"');
header('Cache-Control: no-store, no-cache');

$output = fopen('php://output', 'w');
// BOM UTF-8 pour Excel
fwrite($output, "\xEF\xBB\xBF");
fputcsv($output, ['ID','Prénom','Nom','Email','Téléphone','Pays','Langue','Devise','Actif','Newsletter','Inscrit le','Dernière connexion','Email vérifié'], ';');

foreach ($customers as $c) {
    fputcsv($output, [
        $c['id'],
        $c['first_name'],
        $c['last_name'],
        $c['email'],
        $c['phone'] ?? '',
        $c['country_code'] ?? '',
        $c['preferred_locale'],
        $c['preferred_currency'],
        $c['is_active'] ? 'Oui' : 'Non',
        $c['newsletter_consent'] ? 'Oui' : 'Non',
        $c['created_at'],
        $c['last_login_at'] ?? '',
        $c['email_verified_at'] ? 'Oui' : 'Non',
    ], ';');
}
fclose($output);
exit;
