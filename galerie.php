<?php
declare(strict_types=1);
define('PAGE_TITLE', 'Galerie Photos | Hôtel Le Lézard Bleu Bujumbura Lac Tanganyika');
require_once __DIR__ . '/includes/header.php';

$photos = [
    ['src'=>'assets/images/hero_hotel.jpg',         'alt'=>'Vue aérienne Hôtel Le Lézard Bleu, Bujumbura',          'cat'=>'Extérieur'],
    ['src'=>'assets/images/suite_presidentielle.jpg','alt'=>'Suite Présidentielle Vue Lac Tanganyika',               'cat'=>'Suites'],
    ['src'=>'assets/images/spa_piscine.jpg',         'alt'=>'Spa & Piscine à Débordement, Lac Tanganyika',          'cat'=>'Spa & Piscine'],
    ['src'=>'assets/images/restaurant_gourmet.jpg',  'alt'=>'Restaurant Gastronomique L\'Étoile Bleue, Bujumbura',  'cat'=>'Restaurant'],
    ['src'=>'assets/images/hero_hotel.jpg',          'alt'=>'Jardins Tropicaux, Hôtel Le Lézard Bleu',              'cat'=>'Jardins'],
    ['src'=>'assets/images/suite_presidentielle.jpg','alt'=>'Suite Deluxe Vue Lac, Chambre de Luxe Bujumbura',      'cat'=>'Suites'],
    ['src'=>'assets/images/spa_piscine.jpg',         'alt'=>'Soin Holistique Spa Tanganyika',                       'cat'=>'Spa & Piscine'],
    ['src'=>'assets/images/restaurant_gourmet.jpg',  'alt'=>'Dîner au Bord du Lac Tanganyika',                     'cat'=>'Restaurant'],
    ['src'=>'assets/images/hero_hotel.jpg',          'alt'=>'Plage Privée Lac Tanganyika, Bujumbura Burundi',       'cat'=>'Extérieur'],
];
$cats = array_unique(array_column($photos,'cat'));
?>
<section style="position:relative;padding:7rem 0 3rem;background:linear-gradient(to bottom,rgba(7,12,20,.85),rgba(7,12,20,.95)),url('assets/images/hero_hotel.jpg') center/cover;text-align:center;">
  <div class="container">
    <span class="section-subtitle">DÉCOUVREZ LE LÉZARD BLEU</span>
    <h1 class="section-title">Galerie Photos</h1>
    <p class="section-desc">Lac Tanganyika, Bujumbura, Burundi — Un cadre d'exception.</p>
  </div>
</section>

<section class="section-padding">
  <div class="container">
    <!-- Filtres -->
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;justify-content:center;margin-bottom:2.5rem;">
      <button onclick="filterGallery('all')" class="btn btn-gold btn-filter active" style="padding:.5rem 1.25rem;font-size:.85rem;">Tout</button>
      <?php foreach($cats as $cat):?>
      <button onclick="filterGallery('<?php echo e($cat);?>')" class="btn btn-outline-gold btn-filter" style="padding:.5rem 1.25rem;font-size:.85rem;"><?php echo e($cat);?></button>
      <?php endforeach;?>
    </div>

    <!-- Grille photos -->
    <div id="galleryGrid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1rem;">
      <?php foreach($photos as $p):?>
      <div class="gallery-item" data-cat="<?php echo e($p['cat']);?>" style="overflow:hidden;border-radius:var(--radius-md);border:1px solid var(--border-gold);position:relative;aspect-ratio:4/3;cursor:pointer;">
        <img src="<?php echo e($p['src']);?>" alt="<?php echo e($p['alt']);?>"
             style="width:100%;height:100%;object-fit:cover;transition:transform .4s;" loading="lazy"
             onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
        <div style="position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(7,12,20,.85));padding:.75rem 1rem;">
          <span style="font-size:.75rem;color:var(--accent-gold-primary);background:rgba(212,175,55,.15);padding:.15rem .5rem;border-radius:4px;"><?php echo e($p['cat']);?></span>
        </div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<script>
function filterGallery(cat) {
    document.querySelectorAll('.gallery-item').forEach(el => {
        el.style.display = (cat === 'all' || el.dataset.cat === cat) ? '' : 'none';
    });
    document.querySelectorAll('.btn-filter').forEach(btn => {
        btn.classList.toggle('btn-gold', btn.textContent.trim() === (cat === 'all' ? 'Tout' : cat));
        btn.classList.toggle('btn-outline-gold', btn.textContent.trim() !== (cat === 'all' ? 'Tout' : cat));
    });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
