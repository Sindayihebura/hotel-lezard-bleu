<?php
declare(strict_types=1);
http_response_code(404);
require_once dirname(__DIR__,'2') . '/config/bootstrap.php';
define('PAGE_TITLE','404 — Page Introuvable');
require_once dirname(__DIR__,'2') . '/includes/header.php';
?>
<section style="min-height:80vh;display:flex;align-items:center;justify-content:center;background:var(--bg-dark-main);text-align:center;padding:2rem;">
  <div>
    <div style="font-size:5rem;font-weight:900;color:var(--accent-gold-primary);">404</div>
    <h1 style="color:var(--text-light-primary);font-size:1.5rem;margin:1rem 0;">Page Introuvable</h1>
    <p style="color:var(--text-muted);margin-bottom:2rem;">Cette page n'existe pas ou a été déplacée.</p>
    <a href="/index.php" class="btn btn-gold" style="padding:.85rem 2rem;">Retour à l'Accueil</a>
  </div>
</section>
<?php require_once dirname(__DIR__,'2') . '/includes/footer.php'; ?>
