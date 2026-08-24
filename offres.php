<?php
declare(strict_types=1);
define('PAGE_TITLE', 'Offres Spéciales | Hôtel Le Lézard Bleu Bujumbura');
require_once __DIR__ . '/includes/header.php';

$offers = [];
$pdo    = getDB();
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM offers WHERE is_active=1 AND (valid_to IS NULL OR valid_to>=CURDATE()) ORDER BY id DESC");
        $offers = $stmt ? $stmt->fetchAll() : [];
    } catch (\PDOException $e) {
        error_log("Offers query: ".$e->getMessage());
    }
}
if (empty($offers)) {
    $offers = [
        ['id'=>1,'title'=>'Escapade Romantique Lac Tanganyika','description'=>'2 nuits en Suite, champagne, petit-déjeuner au lit et massage duo.','code'=>'TANGANYIKA20','discount_type'=>'percent','discount_value'=>20,'min_nights'=>2,'valid_to'=>date('Y-12-31'),'photo'=>'assets/images/spa_piscine.jpg'],
        ['id'=>2,'title'=>'Forfait Découverte Gastronomique','description'=>'1 nuit Chambre Deluxe + Dîner Gastronomique 5 services pour 2 personnes.','code'=>'BUJUMBURA15','discount_type'=>'percent','discount_value'=>15,'min_nights'=>1,'valid_to'=>date('Y').'-11-30','photo'=>'assets/images/restaurant_gourmet.jpg'],
    ];
}
?>
<section style="position:relative;padding:7rem 0 3rem;background:linear-gradient(to bottom,rgba(7,12,20,.85),rgba(7,12,20,.95)),url('assets/images/hero_hotel.jpg') center/cover;text-align:center;">
  <div class="container">
    <span class="section-subtitle">TARIFS EXCLUSIFS</span>
    <h1 class="section-title">Offres Spéciales & Forfaits</h1>
    <p class="section-desc">Profitez de nos meilleures offres au bord du Lac Tanganyika, Bujumbura.</p>
  </div>
</section>

<section class="section-padding">
  <div class="container">
    <div class="grid-cards">
      <?php foreach($offers as $o):
        $discount = $o['discount_type']==='percent' ? $o['discount_value'].'%' : format_bif((int)$o['discount_value']);
      ?>
      <div class="card-luxury">
        <?php if(!empty($o['photo'])): ?>
        <div class="card-img-wrapper">
          <img src="<?php echo e($o['photo']);?>" alt="<?php echo e($o['title']);?>" class="card-img" loading="lazy">
          <span class="card-badge">-<?php echo e($discount);?></span>
        </div>
        <?php endif;?>
        <div class="card-body">
          <h3 class="card-title"><?php echo e($o['title']);?></h3>
          <p class="card-text"><?php echo e($o['description']);?></p>
          <div style="margin:1rem 0;padding:.75rem 1rem;background:rgba(212,175,55,.08);border:1px solid rgba(212,175,55,.2);border-radius:8px;">
            <?php if(!empty($o['code'])): ?>
            <div style="font-size:.8rem;color:var(--text-muted);margin-bottom:.25rem;">Code promo :</div>
            <div style="font-family:monospace;font-size:1.2rem;color:var(--accent-gold-primary);font-weight:700;letter-spacing:.15em;"><?php echo e($o['code']);?></div>
            <?php endif;?>
            <div style="font-size:.8rem;color:var(--text-muted);margin-top:.5rem;">
              Remise : <strong style="color:var(--accent-gold-primary);"><?php echo e($discount);?></strong>
              · Min. <?php echo (int)$o['min_nights'];?> nuit<?php echo $o['min_nights']>1?'s':'';?>
              <?php if(!empty($o['valid_to'])): ?> · Jusqu'au <?php echo date('d/m/Y',strtotime($o['valid_to']));?><?php endif;?>
            </div>
          </div>
          <div class="card-footer">
            <a href="reservation.php<?php echo !empty($o['code'])?'?offer_code='.urlencode($o['code']):'';?>" class="btn btn-gold" style="width:100%;text-align:center;padding:.75rem;">
              Réserver avec cette Offre
            </a>
          </div>
        </div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
