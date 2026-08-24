<?php
declare(strict_types=1);
http_response_code(500);
$isApi = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
if ($isApi) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'data'=>null,'error'=>['code'=>'SERVER_ERROR','message'=>'Erreur interne du serveur.'],'meta'=>[]]);
    exit;
}
?><!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Erreur — Le Lézard Bleu</title>
<style>body{background:#070C14;color:#e0e8f0;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;}
.code{font-size:5rem;font-weight:900;color:#D4AF37;}h1{font-size:1.5rem;margin:1rem 0;}p{color:#94A3B8;margin-bottom:2rem;}
a{background:#D4AF37;color:#070C14;padding:.85rem 2rem;border-radius:50px;text-decoration:none;font-weight:700;}</style>
</head><body>
<div><div class="code">500</div><h1>Erreur Interne</h1>
<p>Une erreur inattendue est survenue. Nos équipes ont été notifiées.</p>
<a href="/index.php">Retour à l'Accueil</a></div>
</body></html>
