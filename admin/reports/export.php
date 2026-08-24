<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard;
use App\Reports\ReportService; use App\Security\Logger;

$pdo   = getDB();
$guard = new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php');
$guard->requirePermission('reports.view');
$adminUser = (new AdminAuth($pdo))->user();

$from = trim($_GET['from'] ?? date('Y-m-01'));
$to   = trim($_GET['to']   ?? date('Y-m-t'));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-t');

(new Logger($pdo))->audit(Logger::ACTION_EXPORT, 'bookings', null,
    null, ['from' => $from, 'to' => $to, 'format' => 'csv'], (int)$adminUser['id']);

$service = new ReportService($pdo);
$csv     = $service->exportBookingsCsv($from, $to);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="reservations_' . $from . '_' . $to . '.csv"');
header('Cache-Control: no-store, no-cache');
echo $csv;
exit;
