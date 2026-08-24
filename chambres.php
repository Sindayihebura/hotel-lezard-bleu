<?php
define('PAGE_TITLE', 'Suites & Villas Vue Lac Tanganyika | Hôtel Le Lézard Bleu 5* Bujumbura');
require_once __DIR__ . '/includes/header.php';

$pdo     = getDB();
$chambres = [];

if ($pdo) {
    try {
        // Nouvelle table rooms (migration 001)
        $stmt = $pdo->query("
            SELECT r.*, rc.name AS categorie
            FROM rooms r
            JOIN room_categories rc ON rc.id = r.category_id
            WHERE r.is_active = 1
            ORDER BY r.sort_order ASC
        ");
        $chambres = $stmt ? $stmt->fetchAll() : [];
    } catch (\PDOException $e) {
        error_log("Rooms query error: " . $e->getMessage());
    }
}

// Fallback si DB pas encore migrée
if (empty($chambres)) {
    $chambres = [
        ['id'=>1,'name'=>'Suite Présidentielle Tanganyika Vue Lac','categorie'=>'Suite','description'=>'Une suite somptueuse au bord du Lac Tanganyika à Bujumbura, terrasse panoramique, baignoire jacuzzi en marbre, salon privé et service majordome 24h/24.','price_per_night_bif'=>3900000,'capacity_adults'=>3,'surface_m2'=>115,'amenities_json'=>'["Lit King-Size","Terrasse Privée Lac","Wi-Fi 6","Jacuzzi","Majordome 24/7","Nespresso","Smart TV 65 pouces 4K"]','photo_main'=>'assets/images/suite_presidentielle.jpg'],
        ['id'=>2,'name'=>'Chambre Deluxe Plage & Jardins de Bujumbura','categorie'=>'Deluxe','description'=>'Baignée de la lumière tropicale du lac, finitions en bois précieux burundais, terrasse ombragée et vue directe sur les jardins verdoyants et la piscine.','price_per_night_bif'=>2280000,'capacity_adults'=>2,'surface_m2'=>50,'amenities_json'=>'["Lit King-Size","Terrasse Ombragée","Wi-Fi Premium","Climatisation","Coffre-fort","Douche Italienne"]','photo_main'=>'assets/images/hero_hotel.jpg'],
        ['id'=>3,'name'=>'Chambre Executive Jardin & Spa','categorie'=>'Executive','description'=>'Nichée dans la végétation luxuriante des collines de Bujumbura avec accès direct au Spa holistique et espaces de relaxation privatifs.','price_per_night_bif'=>2700000,'capacity_adults'=>2,'surface_m2'=>65,'amenities_json'=>'["Lit King-Size","Accès Spa Illimité","Terrasse Privative","Wi-Fi 6","Peignoirs Soie"]','photo_main'=>'assets/images/spa_piscine.jpg'],
        ['id'=>4,'name'=>'Villa Privée Tanganyika Plunge Pool','categorie'=>'Villa','description'=>'Sommet de l\'hôtellerie burundaise. Villa indépendante au bord de l\'eau avec piscine privée chauffée, solarium et majordome dédié.','price_per_night_bif'=>5520000,'capacity_adults'=>4,'surface_m2'=>160,'amenities_json'=>'["Piscine Privée Chauffée","2 Lits King-Size","Service Villa 24/7","Système Audio Premium","Transfert VIP Aéroport"]','photo_main'=>'assets/images/suite_presidentielle.jpg'],
    ];
}

if (empty($chambres)) {
    $chambres = [
        [
            'id' => 1,
            'nom' => 'Suite Présidentielle Tanganyika Vue Lac',
            'categorie' => 'Suite',
            'description' => 'Une suite somptueuse au bord du Lac Tanganyika à Bujumbura, terrasse panoramique, baignoire jacuzzi en marbre, salon privé et service majordome 24h/24.',
            'prix_nuit_bif' => 3900000.00,
            'capacite' => 3,
            'surface_m2' => 115,
            'equipements' => 'Lit King-Size, Terrasse Privée sur le Lac, Wi-Fi 6, Jacuzzi, Majordome 24/7, Nespresso, Smart TV 65" 4K',
            'photo_url' => 'assets/images/suite_bujumbura.jpg'
        ],
        [
            'id' => 2,
            'nom' => 'Chambre Deluxe Plage & Jardins de Bujumbura',
            'categorie' => 'Deluxe',
            'description' => 'Baignée de la lumière tropicale du lac, finitions en bois précieux burundais, terrasse ombragée et vue directe sur les jardins verdoyants et la piscine.',
            'prix_nuit_bif' => 2280000.00,
            'capacite' => 2,
            'surface_m2' => 50,
            'equipements' => 'Lit King-Size, Terrasse Ombree, Wi-Fi Premium, Climatisation Tropicale, Coffre-fort, Douche a l\'Italienne',
            'photo_url' => 'assets/images/hero_tanganyika.jpg'
        ],
        [
            'id' => 3,
            'nom' => 'Chambre Executive Jardin & Spa',
            'categorie' => 'Executive',
            'description' => 'Nichée dans la végétation luxuriante des collines de Bujumbura avec un accès direct au Spa holistique et aux espaces de relaxation privatifs.',
            'prix_nuit_bif' => 2700000.00,
            'capacite' => 2,
            'surface_m2' => 65,
            'equipements' => 'Lit King-Size, Accès Spa Illimité, Terrasse Privative, Wi-Fi 6, Station Bluetooth, Peignoirs en soie',
            'photo_url' => 'assets/images/spa_piscine.jpg'
        ],
        [
            'id' => 4,
            'nom' => 'Villa Privée Tanganyika Plunge Pool',
            'categorie' => 'Villa',
            'description' => 'Le sommet de l\'hôtellerie burundaise. Villa indépendante au bord de l\'eau avec piscine privée chauffée, solarium et majordome dédié.',
            'prix_nuit_bif' => 5520000.00,
            'capacite' => 4,
            'surface_m2' => 160,
            'equipements' => 'Piscine Privée Chauffée, 2 Lits King-Size, Service en Villa 24/7, Système Audio Bang & Olufsen, Transfert VIP Aéroport',
            'photo_url' => 'assets/images/suite_presidentielle.jpg'
        ]
    ];
}
?>

<!-- BANNER -->
<section style="position: relative; padding: 8rem 0 4rem 0; background: linear-gradient(to bottom, rgba(7,12,20,0.85), rgba(7,12,20,0.95)), url('assets/images/suite_bujumbura.jpg') center/cover; text-align: center;">
  <div class="container">
    <span class="section-subtitle">VOS REFUGES DE PRESTIGE À BUJUMBURA</span>
    <h1 class="section-title">Suites, Villas & Hébergements</h1>
    <p class="section-desc">
      Explorez nos logements de haute volée au bord du Lac Tanganyika.
    </p>
  </div>
</section>

<!-- ROOMS LISTING -->
<section class="section-padding">
  <div class="container">
    <div style="display: flex; flex-direction: column; gap: 4rem;">
      <?php foreach ($chambres as $chambre):
        $priceBif  = (int)($chambre['price_per_night_bif'] ?? $chambre['prix_nuit_bif'] ?? 0);
        $nom       = $chambre['name']       ?? $chambre['nom']       ?? '';
        $cat       = $chambre['categorie']  ?? '';
        $desc      = $chambre['description']?? '';
        $cap       = (int)($chambre['capacity_adults'] ?? $chambre['capacite'] ?? 2);
        $surf      = (int)($chambre['surface_m2'] ?? 30);
        $photo     = $chambre['photo_main'] ?? $chambre['photo_url'] ?? 'assets/images/hero_hotel.jpg';
        $amenities = json_decode($chambre['amenities_json'] ?? '[]', true) ?? [];
        // Si pas JSON, essayer l'ancien champ texte
        if (empty($amenities) && !empty($chambre['equipements'])) {
            $amenities = array_map('trim', explode(',', $chambre['equipements']));
        }
      ?>
        <div class="card-luxury" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); border-radius: var(--radius-lg); overflow: hidden;">
          <div class="card-img-wrapper" style="height: 100%; min-height: 320px;">
            <img src="<?php echo e($photo); ?>" alt="<?php echo e($nom); ?>" class="card-img" loading="lazy">
            <span class="card-badge" style="top: 1.5rem; left: 1.5rem;"><?php echo e($cat); ?></span>
          </div>
          <div class="card-body" style="padding: 2.5rem; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
              <h2 class="card-title" style="font-size: 1.75rem; color: var(--text-light-primary);"><?php echo e($nom); ?></h2>
              <p class="card-text" style="font-size: 1.05rem; margin-bottom: 1.5rem;"><?php echo e($desc); ?></p>
              <div class="card-specs" style="margin-bottom: 1.5rem;">
                <div class="card-spec-item">👥 Capacité : <strong><?php echo $cap; ?> pers.</strong></div>
                <div class="card-spec-item">📐 Surface : <strong><?php echo $surf; ?> m²</strong></div>
                <div class="card-spec-item">🌅 Vue Lac Tanganyika</div>
              </div>
              <?php if (!empty($amenities)): ?>
              <div style="margin-bottom: 2rem;">
                <h4 style="font-size: .85rem; color: var(--accent-gold-primary); text-transform: uppercase; letter-spacing: .1em; margin-bottom: .75rem;">Équipements</h4>
                <div style="display: flex; flex-wrap: wrap; gap: .5rem;">
                  <?php foreach ($amenities as $eq): ?>
                    <span style="background: rgba(7,12,20,.6); border: 1px solid var(--border-light); padding: .35rem .75rem; border-radius: var(--radius-sm); font-size: .8rem; color: var(--text-light-secondary);">
                      ✓ <?php echo e(trim($eq)); ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
              <?php endif; ?>
            </div>
            <div class="card-footer" style="border-top: 1px solid var(--border-light); padding-top: 1.5rem;">
              <div class="card-price">
                <span class="card-price-amount" data-price-bif="<?php echo $priceBif; ?>" style="font-size: 1.8rem;">
                  <?php echo format_currency($priceBif); ?>
                </span>
                <span class="card-price-unit">par nuitée</span>
              </div>
              <a href="reservation.php?room_id=<?php echo $chambre['id']; ?>" class="btn btn-gold" style="padding: 0.9rem 2rem;">
                Réserver Cette Suite
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
