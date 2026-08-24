<?php
declare(strict_types=1);
define('PAGE_TITLE', 'Le Lézard Bleu | Hôtel & Spa 5 Étoiles — Bujumbura, Lac Tanganyika, Burundi');
require_once __DIR__ . '/includes/header.php';

$pdo          = getDB();
$chambresPhares = [];
$avisClients    = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT r.*, rc.name AS categorie
            FROM rooms r
            JOIN room_categories rc ON rc.id = r.category_id
            WHERE r.is_active = 1
            ORDER BY r.sort_order ASC LIMIT 3
        ");
        $chambresPhares = $stmt ? $stmt->fetchAll() : [];
    } catch (\PDOException $e) { error_log("Index rooms: ".$e->getMessage()); }
    try {
        $stmt = $pdo->query("SELECT * FROM reviews WHERE is_visible=1 ORDER BY id DESC LIMIT 3");
        $avisClients = $stmt ? $stmt->fetchAll() : [];
    } catch (\PDOException $e) { error_log("Index reviews: ".$e->getMessage()); }
}

if (empty($chambresPhares)) {
    $chambresPhares = [
        ['id'=>1,'name'=>'Suite Présidentielle Tanganyika Vue Lac','categorie'=>'Suite','description'=>'Vue féerique sur le Lac Tanganyika à Bujumbura. Terrasse panoramique, jacuzzi, majordome 24h/24.','price_per_night_bif'=>3900000,'capacity_adults'=>3,'surface_m2'=>115,'photo_main'=>'assets/images/suite_presidentielle.jpg'],
        ['id'=>2,'name'=>'Chambre Deluxe Plage & Jardins','categorie'=>'Deluxe','description'=>'Finitions en bois précieux burundais, terrasse ombragée et vue jardins verdoyants.','price_per_night_bif'=>2280000,'capacity_adults'=>2,'surface_m2'=>50,'photo_main'=>'assets/images/hero_hotel.jpg'],
        ['id'=>3,'name'=>'Chambre Executive Jardin & Spa','categorie'=>'Executive','description'=>'Accès direct au Spa holistique et espaces de relaxation privatifs avec vue tropicale.','price_per_night_bif'=>2700000,'capacity_adults'=>2,'surface_m2'=>65,'photo_main'=>'assets/images/spa_piscine.jpg'],
    ];
}

if (empty($avisClients)) {
    $avisClients = [
        ['guest_name'=>'Jean-Paul K.','guest_origin'=>'Bujumbura, Burundi','rating'=>5,'comment'=>'Un cadre idyllique au bord du Lac Tanganyika ! Le service Lumicash et l\'accueil sont irréprochables.','stay_type'=>'Séjour Affaires'],
        ['guest_name'=>'Elena & Marc','guest_origin'=>'Bruxelles, Belgique','rating'=>5,'comment'=>'Sublime hôtel à Bujumbura. La vue sur le lac au coucher du soleil depuis la Suite Présidentielle est magique.','stay_type'=>'Vacances en Afrique'],
        ['guest_name'=>'Dr. Thierry Habimana','guest_origin'=>'Kigali, Rwanda','rating'=>5,'comment'=>'Très impressionné par la qualité du service, la gastronomie du lac et les installations de conférence.','stay_type'=>'Séminaire International'],
    ];
}
?>

<!-- HERO -->
<section class="hero-section">
  <img src="assets/images/hero_hotel.jpg" alt="Hôtel Le Lézard Bleu Bujumbura Lac Tanganyika" class="hero-bg-image">
  <div class="hero-overlay"></div>
  <div class="container hero-content">
    <div class="hero-stars">★★★★★</div>
    <h1 class="hero-title">L'Excellence Hôtelière au Bord du <span>Lac Tanganyika</span></h1>
    <p class="hero-subtitle">
      Bienvenue à l'Hôtel Le Lézard Bleu & Spa à Bujumbura, Burundi. Suites de prestige, haute gastronomie et paiements en BIF & USD (Lumicash, EcoCash, Banques locales, Visa/PayPal).
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="reservation.php" class="btn btn-gold">Réserver Votre Séjour</a>
      <a href="presentation.php" class="btn btn-outline-gold">Découvrir L'Hôtel</a>
    </div>
    <!-- Barre de recherche rapide -->
    <div class="booking-widget-bar">
      <form id="heroQuickSearchForm" action="reservation.php" method="GET" class="booking-widget-form">
        <div class="form-group">
          <label for="hero_arr" class="form-label">📅 Arrivée</label>
          <input type="date" id="hero_arr" name="date_arrivee" class="form-input" required>
        </div>
        <div class="form-group">
          <label for="hero_dep" class="form-label">📅 Départ</label>
          <input type="date" id="hero_dep" name="date_depart" class="form-input" required>
        </div>
        <div class="form-group">
          <label for="hero_pers" class="form-label">👥 Personnes</label>
          <select id="hero_pers" name="nb_personnes" class="form-select">
            <option value="1">1 Personne</option>
            <option value="2" selected>2 Personnes</option>
            <option value="3">3 Personnes</option>
            <option value="4">4 Personnes &amp; Suite</option>
          </select>
        </div>
        <button type="submit" class="btn btn-gold" style="height:52px;white-space:nowrap;">
          🔍 Disponibilités &amp; Tarifs
        </button>
      </form>
    </div>
  </div>
</section>

<!-- PRÉSENTATION -->
<section class="section-padding" style="background:var(--bg-dark-main);">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:3.5rem;align-items:center;">
      <div>
        <span class="section-subtitle">BIENVENUE AU BURUNDI</span>
        <h2 class="section-title">Le Sanctuaire de Bujumbura</h2>
        <p style="margin-bottom:1.25rem;font-size:1.05rem;">
          Niché sur les rives paisibles du Lac Tanganyika avec une vue majestueuse sur les collines de Bujumbura, Le Lézard Bleu incarne la rencontre entre l'hospitalité burundaise chaleureuse et les standards 5 étoiles internationaux.
        </p>
        <p style="color:var(--text-muted);margin-bottom:2rem;">
          Nous acceptons les paiements en <strong>Francs Burundais (BIF)</strong> et <strong>Dollars Américains (USD)</strong> via <strong>Lumicash</strong>, <strong>EcoCash</strong>, les <strong>Banques du Burundi</strong> et <strong>Cartes Visa/PayPal</strong>.
        </p>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1.5rem;text-align:center;border-top:1px solid var(--border-gold);padding-top:1.5rem;">
          <div>
            <div style="font-family:var(--font-heading);font-size:1.5rem;color:var(--accent-gold-primary);font-weight:700;">Lac Tanganyika</div>
            <div style="font-size:.8rem;color:var(--text-muted);">Accès Direct Plage</div>
          </div>
          <div>
            <div style="font-family:var(--font-heading);font-size:1.5rem;color:var(--accent-gold-primary);font-weight:700;">1 USD = 6k</div>
            <div style="font-size:.8rem;color:var(--text-muted);">Taux BIF Dynamique</div>
          </div>
          <div>
            <div style="font-family:var(--font-heading);font-size:1.5rem;color:var(--accent-gold-primary);font-weight:700;">Lumicash</div>
            <div style="font-size:.8rem;color:var(--text-muted);">Mobile Money Inclus</div>
          </div>
        </div>
      </div>
      <div>
        <img src="assets/images/spa_piscine.jpg" alt="Piscine & Spa Hôtel Le Lézard Bleu Bujumbura"
             style="border-radius:var(--radius-md);border:1px solid var(--border-gold);box-shadow:var(--shadow-lux);width:100%;">
      </div>
    </div>
  </div>
</section>

<!-- CHAMBRES -->
<section class="section-padding">
  <div class="container text-center">
    <span class="section-subtitle">VOS NUITS DE RÊVE À BUJUMBURA</span>
    <h2 class="section-title">Chambres &amp; Suites d'Exception</h2>
    <p class="section-desc">Basculez la devise (BIF / USD) en haut de page pour voir le tarif converti instantanément.</p>
    <div class="grid-cards">
      <?php foreach ($chambresPhares as $c):
        $priceBif = (int)($c['price_per_night_bif'] ?? 0);
        $nom      = $c['name']      ?? '';
        $cat      = $c['categorie'] ?? '';
        $desc     = $c['description'] ?? '';
        $cap      = (int)($c['capacity_adults'] ?? 2);
        $surf     = (int)($c['surface_m2'] ?? 30);
        $photo    = $c['photo_main'] ?? 'assets/images/hero_hotel.jpg';
      ?>
        <div class="card-luxury">
          <div class="card-img-wrapper">
            <img src="<?php echo e($photo);?>" alt="<?php echo e($nom);?>" class="card-img" loading="lazy">
            <span class="card-badge"><?php echo e($cat);?></span>
          </div>
          <div class="card-body">
            <h3 class="card-title"><?php echo e($nom);?></h3>
            <p class="card-text"><?php echo e(mb_substr($desc,0,120,'UTF-8'));?>…</p>
            <div class="card-specs">
              <div class="card-spec-item">👥 <?php echo $cap;?> pers.</div>
              <div class="card-spec-item">📐 <?php echo $surf;?> m²</div>
              <div class="card-spec-item">🌅 Vue Lac</div>
            </div>
            <div class="card-footer">
              <div class="card-price">
                <span class="card-price-amount" data-price-bif="<?php echo $priceBif;?>">
                  <?php echo format_currency($priceBif);?>
                </span>
                <span class="card-price-unit">par nuitée</span>
              </div>
              <a href="reservation.php?room_id=<?php echo (int)$c['id'];?>" class="btn btn-gold" style="padding:.65rem 1.25rem;font-size:.8rem;">Réserver</a>
            </div>
          </div>
        </div>
      <?php endforeach;?>
    </div>
    <div style="margin-top:3rem;">
      <a href="chambres.php" class="btn btn-outline-gold">Voir Toutes Nos Suites</a>
    </div>
  </div>
</section>

<!-- PAIEMENTS -->
<section class="section-padding" style="background:var(--bg-dark-main);">
  <div class="container text-center">
    <span class="section-subtitle">PAIEMENT SIMPLIFIÉ</span>
    <h2 class="section-title">Payez en BIF ou USD</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.5rem;text-align:left;margin-top:2rem;">
      <?php foreach([
        ['💵','Espèces sur Place','BIF ou USD à la réception à votre arrivée.'],
        ['📱','Mobile Money','Lumicash (Lumitel) et EcoCash (Econet Leo Burundi).'],
        ['🏦','Banques du Burundi','BANCOBU, BCB, IBB, ECOBANK, CRDB, FINBANK, BGF, BHB.'],
        ['💳','International','Visa, MasterCard et PayPal pour les visiteurs étrangers.'],
      ] as [$ico,$titre,$desc]):?>
      <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);padding:1.5rem;border-radius:var(--radius-md);">
        <div style="font-size:2rem;margin-bottom:.5rem;"><?php echo $ico;?></div>
        <h4 style="color:var(--accent-gold-primary);font-family:var(--font-heading);margin-bottom:.5rem;"><?php echo e($titre);?></h4>
        <p style="font-size:.85rem;color:var(--text-muted);"><?php echo e($desc);?></p>
      </div>
      <?php endforeach;?>
    </div>
  </div>
</section>

<!-- AVIS -->
<section class="section-padding">
  <div class="container text-center">
    <span class="section-subtitle">TÉMOIGNAGES</span>
    <h2 class="section-title">Avis Vérifiés</h2>
    <div class="reviews-grid">
      <?php foreach ($avisClients as $avis):
        $nomClient = $avis['guest_name']   ?? $avis['nom_client']  ?? 'Client';
        $origine   = $avis['guest_origin'] ?? $avis['origine']     ?? 'Burundi';
        $note      = (int)($avis['rating'] ?? $avis['note']        ?? 5);
        $comment   = $avis['comment']      ?? $avis['commentaire'] ?? '';
        $sejour    = $avis['stay_type']    ?? $avis['type_sejour'] ?? 'Client Vérifié';
      ?>
        <div class="review-card">
          <span class="review-quote-icon">"</span>
          <div class="review-stars"><?php for($i=0;$i<$note;$i++) echo '★';?></div>
          <p class="review-comment">"<?php echo e($comment);?>"</p>
          <div class="review-author">
            <div class="review-avatar"><?php echo e(strtoupper(mb_substr($nomClient,0,1,'UTF-8')));?></div>
            <div class="review-author-info">
              <h4><?php echo e($nomClient);?></h4>
              <p><?php echo e($sejour);?> · <?php echo e($origine);?></p>
            </div>
          </div>
        </div>
      <?php endforeach;?>
    </div>
    <div style="margin-top:3rem;">
      <a href="reservation.php" class="btn btn-gold" style="padding:1rem 3rem;">Réserver Votre Séjour</a>
    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const arr = document.getElementById('hero_arr');
  const dep = document.getElementById('hero_dep');
  if (arr && dep) {
    const today    = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const fmt = d => d.toISOString().split('T')[0];
    arr.value = fmt(today);
    arr.min   = fmt(today);
    dep.value = fmt(tomorrow);
    dep.min   = fmt(tomorrow);
    arr.addEventListener('change', () => {
      const next = new Date(arr.value);
      next.setDate(next.getDate() + 1);
      if (!dep.value || dep.value <= arr.value) dep.value = fmt(next);
      dep.min = fmt(next);
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
