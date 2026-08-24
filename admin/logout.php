<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AdminAuth;
use App\Security\Logger;

$pdo       = getDB();
$adminAuth = new AdminAuth($pdo);
$logger    = new Logger($pdo);

if ($adminAuth->check()) {
    $user = $adminAuth->user();
    $logger->audit(
        Logger::ACTION_LOGOUT,
        'admin_user',
        $user['id'] ?? null,
        null,
        null,
        $user['id'] ?? null,
        'success'
    );
    $adminAuth->logout();
}

safe_redirect('/admin/login.php');
