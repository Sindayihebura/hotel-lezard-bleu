<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_once dirname(__DIR__) . '/config/database.php';

use App\Auth\AuthService;

$pdo  = getDB();
$auth = new AuthService($pdo);
$auth->logout();
safe_redirect('/public/connexion.php');
