<?php
declare(strict_types=1);
define('PAGE_TITLE', 'Services & Restaurant | Hôtel Le Lézard Bleu Bujumbura');
require_once __DIR__ . '/includes/header.php';

// Données menu
$menuItems = [
    'entrees' => [
        ['nom'=>'Carpaccio de Capitaine du Lac Tanganyika','desc'=>'Zestes de citron vert, huile d\'olive et baies roses.','prix_bif'=>250000],
        ['nom'=>'Foie Gras Poêlé aux Figues de Bujumbura','desc'=>'Chutney de figues locales et brioche feuilletée tiède.','prix_bif'=>228000],
    ],
    'plats' => [
        ['nom'=>'Filet de Sangala Grillé au Feu de Bois','desc'=>'Poisson frais du Lac Tanganyika, mousseline de banane plantain.','prix_bif'=>390000],
        ['nom'=>'Pièce de Bœuf Wagyu aux Épices Burundaises','desc'=>'Jus corsé, gratin de patates douces et légumes du potager bio.','prix_bif'=>510000],
    ],
    'desserts' => [
        ['nom'=>'Café Gourmand Pure Origine Kayanza','desc'=>'Trois créations au chocolat Valrhona et vanille.','prix_bif'=>144000],
        ['nom'=>'Tartelette aux Agrumes du Lac','desc'=>'Crème de citron vert, meringue flambée.','prix_bif'=>132000],
    ],
];

// Services depuis DB
$servicesDB = [];
$pdo = getDB();
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order,id");
        $servicesDB = $stmt ? $stmt->fetchAll() : [];
    } catch (\PDOException $e) {
        error_log("Services query: ".$e->getMessage());
    }
}
?>

<!-- BANNER -->
<section style="position:relative;padding:7rem 0 3rem;background:linear-gradient(to bottom,rgba(7,12,20,.85),rgba(7,12,20,.95)),url('assets/images/restaurant_gourmet.jpg') center/cover;text-align:center;">
  <div class="container">
    <span class="section-subtitle">ART CULINAIRE DU LAC & BIEN-ÊTRE</span>
    <h1 class="section-title">Services & Restaurant Gastronomique</h1>
    <p class="section-desc">Une alliance entre les trésors culinaires du Burundi et la haute gastronomie internationale.</p>
  </div>
</section>

<!-- SERVICES GRID depuis DB -->
<?php if (!empty($servicesDB)): ?>
<section class="section-padding">
  <div class="container">
    <span class="section-subtitle" style="display:block;text-align:center;">NOS PRESTATIONS</span>
    <h2 class="section-title" style="text-align:center;">Services d'Exception</h2>
    <div class="grid-cards" style="margin-top:3rem;">
      <?php foreach($servicesDB as $s):?>
      <div class="card-luxury" style="padding:2rem;">
        <?php if($s['photo']) echo '<img src="'.e($s['photo']).'" alt="" style="width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:1rem;">'; ?>
        <h3 class="card-title" style="font-size:1.1rem;"><?php echo e($s['title']);?></h3>
        <p class="card-text" style="font-size:.875rem;"><?php echo e($s['description']);?></p>
        <div style="margin-top:1rem;color:var(--accent-gold-primary);font-weight:700;">
          <?php echo format_bif((int)$s['price_bif']);?>
          <span style="font-size:.75rem;color:var(--text-muted);"> / <?php echo e($s['price_unit']);?></span>
        </div>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>
<?php else: ?>
<!-- Fallback statique si DB vide -->
<section class="section-padding">
  <div class="container">
    <h2 class="section-title" style="text-align:center;">Services Inclus & Privilèges</h2>
    <div class="grid-cards" style="margin-top:3rem;">
      <?php foreach([['📶','Wi-Fi 6 Inclus','Connexion haut débit partout dans l\'hôtel.'],['🏊','Piscine & Plage Lac Tanganyika','Bassin à débordement et cabanas privatives.'],['🚁','Transfert Aéroport Ndadaye','Prise en charge VIP en SUV climatisé.'],['🧖','Spa & Hammam','Soins holistiques avec huiles essentielles locales.'],['🍳','Petit-Déjeuner Gastronomique','Buffet complet avec produits frais du Burundi.'],['⛵','Excursions sur le Lac','Croisière au coucher du soleil avec rafraîchissements.']] as [$ico,$titre,$desc]):?>
      <div class="card-luxury" style="padding:2rem;">
        <div style="font-size:2.5rem;color:var(--accent-gold-primary);margin-bottom:1rem;"><?php echo $ico;?></div>
        <h3 class="card-title"><?php echo e($titre);?></h3>
        <p class="card-text"><?php echo e($desc);?></p>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>
<?php endif;?>

<!-- RESTAURANT -->
<section class="section-padding" style="background:var(--bg-dark-main);">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:3.5rem;align-items:center;margin-bottom:4rem;">
      <div>
        <span class="section-subtitle">HAUTE GASTRONOMIE</span>
        <h2 class="section-title">Restaurant L'Étoile Bleue</h2>
        <p style="font-size:1.05rem;margin-bottom:1.25rem;">Surplombant le Lac Tanganyika, notre chef sublime les poissons locaux (Capitaine, Sangala) et les produits du terroir burundais.</p>
        <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);padding:1.5rem;border-radius:var(--radius-md);margin-bottom:1.5rem;">
          <h4 style="color:var(--accent-gold-primary);margin-bottom:.5rem;">🕒 Horaires</h4>
          <p style="font-size:.95rem;color:var(--text-light-secondary);margin-bottom:.25rem;"><strong>Déjeuner :</strong> 12h00 – 14h30</p>
          <p style="font-size:.95rem;color:var(--text-light-secondary);"><strong>Dîner :</strong> 19h30 – 22h30</p>
        </div>
        <a href="reservation.php" class="btn btn-gold" style="padding:.8rem 2rem;">Réserver une Table</a>
      </div>
      <div style="border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--border-gold);box-shadow:var(--shadow-lux);">
        <img src="assets/images/restaurant_gourmet.jpg" alt="Restaurant Gastronomique Bujumbura" style="width:100%;height:350px;object-fit:cover;">
      </div>
    </div>

    <!-- Carte -->
    <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:3rem;box-shadow:var(--shadow-lux);">
      <h3 class="section-title" style="text-align:center;font-size:2rem;margin-bottom:2rem;">Aperçu de la Carte</h3>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2.5rem;">
        <?php foreach(['entrees'=>'Entrées Raffinées','plats'=>'Plats Signatures du Lac','desserts'=>'Desserts & Cafés'] as $cat=>$titre):?>
        <div>
          <h4 style="color:var(--accent-gold-primary);font-family:var(--font-heading);font-size:1.2rem;border-bottom:1px solid var(--border-gold);padding-bottom:.5rem;margin-bottom:1.25rem;"><?php echo e($titre);?></h4>
          <?php foreach($menuItems[$cat] as $item):?>
          <div style="margin-bottom:1.25rem;">
            <div style="display:flex;justify-content:space-between;font-weight:600;color:var(--text-light-primary);">
              <span><?php echo e($item['nom']);?></span>
              <span class="text-gold" data-price-bif="<?php echo (int)$item['prix_bif'];?>"><?php echo format_currency((int)$item['prix_bif']);?></span>
            </div>
            <p style="font-size:.85rem;color:var(--text-muted);margin-top:.25rem;"><?php echo e($item['desc']);?></p>
          </div>
          <?php endforeach;?>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
