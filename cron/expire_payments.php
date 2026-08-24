<?php
declare(strict_types=1);
/**
 * Cron job — Expiration des paiements en attente
 * Hôtel Le Lézard Bleu & Spa, Bujumbura, Burundi
 *
 * À configurer dans crontab (serveur Linux) :
 *   * /5 * * * * php /var/www/lezardbleu/cron/expire_payments.php >> /var/log/cron_expire.log 2>&1
 *
 * Sur hébergement partagé (Hostinger, InfinityFree, etc.) :
 *   Utiliser le gestionnaire de tâches cron de cPanel
 *   Commande : php /home/user/public_html/cron/expire_payments.php
 *   Fréquence : toutes les 5 minutes
 */
if (PHP_SAPI !== 'cli' && !defined('CRON_ALLOWED')) {
    http_response_code(403);
    exit('Accès refusé.');
}

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Payments\PaymentService;
use App\Security\Logger;

$pdo = getDB();
if (!$pdo) {
    echo '[' . date('Y-m-d H:i:s') . '] ERREUR : base de données inaccessible.' . PHP_EOL;
    exit(1);
}

$start   = microtime(true);
$service = new PaymentService($pdo);
$logger  = new Logger($pdo);

echo '[' . date('Y-m-d H:i:s') . '] Lancement expire_payments...' . PHP_EOL;

try {
    $count = $service->expireStalePayments();
    echo '[' . date('Y-m-d H:i:s') . '] ' . $count . ' paiement(s) expiré(s).' . PHP_EOL;

    if ($count > 0) {
        $logger->info("Cron expire_payments : {$count} paiements expirés");
    }
} catch (\Throwable $e) {
    echo '[' . date('Y-m-d H:i:s') . '] ERREUR : ' . $e->getMessage() . PHP_EOL;
    $logger->error('Cron expire_payments erreur', ['error' => $e->getMessage()]);
}

$elapsed = round((microtime(true) - $start) * 1000, 1);
echo '[' . date('Y-m-d H:i:s') . '] Terminé en ' . $elapsed . 'ms.' . PHP_EOL;
