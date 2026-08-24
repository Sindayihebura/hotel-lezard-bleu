<?php
declare(strict_types=1);
http_response_code(403);
$isApi = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
if ($isApi) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'data'=>null,'error'=>['code'=>'FORBIDDEN','message'=>'Accès refusé.'],'meta'=>[]]);
    exit;
}
require_once dirname(__DIR__,'2') . '/config/bootstrap.php';
define('PAGE_TITLE','403 — Accès Refusé');
require_once dirname(__DIR__,'2') . '/includes/header.php';
?>
<section style="min-height:80vh;display:flex;align-items:center;justify-content:center;background:var(--bg-dark-main);text-align:center;padding:2rem;">
  <div>
    <div style="font-size:5rem;font-weight:900;color:var(--accent-gold-primary);">403</div>
    <h1 style="color:var(--text-light-primary);font-size:1.5rem;margin:1rem 0;">Accès Refusé</h1>
    <p style="color:var(--text-muted);margin-bottom:2rem;">Vous n'avez pas la permission d'accéder à cette page.</p>
    <a href="/index.php" class="btn btn-gold" style="padding:.85rem 2rem;">Retour à l'Accueil</a>
  </div>
</section>
<?php require_once dirname(__DIR__,'2') . '/includes/footer.php'; ?>
