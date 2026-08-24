<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/config/bootstrap.php';
require_once dirname(__DIR__,2).'/config/database.php';
use App\Auth\RbacGuard; use App\Security\Logger;
$pdo=getDB(); $guard=new RbacGuard($pdo);
$guard->requireAuth('/admin/login.php'); $guard->requirePermission('rooms.manage');
$id=(int)($_GET['id']??0);
if($id && $pdo){
    $stmt=$pdo->prepare("SELECT is_active FROM rooms WHERE id=:id"); $stmt->execute([':id'=>$id]);
    $row=$stmt->fetch();
    if($row){
        $new = $row['is_active'] ? 0 : 1;
        $pdo->prepare("UPDATE rooms SET is_active=:a WHERE id=:id")->execute([':a'=>$new,':id'=>$id]);
        (new Logger($pdo))->audit('rooms.toggle','room',$id,['is_active'=>$row['is_active']],['is_active'=>$new],(int)($_SESSION['admin_id']??0));
    }
}
header('Location:/admin/rooms/'); exit;
