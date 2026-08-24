<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\AdminAuth; use App\Auth\RbacGuard;
use App\Booking\BookingService; use App\Security\CsrfGuard;

$pdo   = getDB();
$guard = new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php');
$adminUser = (new AdminAuth($pdo))->user();
$adminId   = (int)$adminUser['id'];

$id     = (int)($_GET['id']     ?? 0);
$action = trim($_GET['action']  ?? '');

if (!$id || !$action) { header('Location:/admin/reservations/'); exit; }

// Token GET sécurisé via session
$token = $_GET['t'] ?? '';
if (!hash_equals($_SESSION['action_token_' . $id] ?? '', $token)) {
    header('Location:/admin/reservations/view.php?id='.$id.'&error=token'); exit;
}
unset($_SESSION['action_token_' . $id]);

$service = new BookingService($pdo);
$ok = false;

switch ($action) {
    case 'confirm':
        $guard->requirePermission('reservations.update');
        $ok = $service->confirm($id);
        break;
    case 'checkin':
        $guard->requirePermission('reservations.checkin');
        $ok = $service->checkin($id, $adminId);
        break;
    case 'checkout':
        $guard->requirePermission('reservations.checkout');
        $ok = $service->checkout($id, $adminId);
        break;
}

header('Location:/admin/reservations/view.php?id='.$id.'&'.($ok?'success=1':'error=1'));
exit;
