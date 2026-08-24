<?php
declare(strict_types=1);
define('PAGE_TITLE', 'Présentation | Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi');
require_once __DIR__ . '/includes/header.php';
?>
<section style="position:relative;padding:7rem 0 3rem;background:linear-gradient(to bottom,rgba(7,12,20,.85),rgba(7,12,20,.95)),url('assets/images/hero_hotel.jpg') center/cover;text-align:center;">
  <div class="container">
    <span class="section-subtitle">BIENVENUE AU CŒUR DU BURUNDI</span>
    <h1 class="section-title">L'Hôtel Le Lézard Bleu & Spa</h1>
    <p class="section-desc">Un sanctuaire de luxe sur les rives du Lac Tanganyika, Bujumbura.</p>
  </div>
</section>

<section class="section-padding">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:3.5rem;align-items:center;margin-bottom:5rem;">
      <div>
        <span class="section-subtitle">NOTRE HISTOIRE</span>
        <h2 class="section-title">Un Hôtel Né du Lac Tanganyika</h2>
        <p style="font-size:1.05rem;line-height:1.8;margin-bottom:1.25rem;">
          Fondé au cœur de Bujumbura, l'Hôtel Le Lézard Bleu & Spa est né d'une vision : offrir au Burundi un établissement hôtelier de standard international, ancré dans la culture locale et ouvert sur la splendeur du Lac Tanganyika — l'un des plus grands et profonds lacs du monde.
        </p>
        <p style="color:var(--text-muted);line-height:1.8;margin-bottom:2rem;">
          Chaque détail — des suites panoramiques aux jardins tropicaux, du restaurant gastronomique aux soins du spa — a été conçu pour créer une expérience unique, alliant chaleur burundaise et raffinement international.
        </p>
        <a href="reservation.php" class="btn btn-gold" style="padding:.85rem 2rem;">Réserver Votre Séjour</a>
      </div>
      <div>
        <img src="assets/images/hero_hotel.jpg" alt="Hôtel Le Lézard Bleu, Bujumbura, Burundi" style="width:100%;border-radius:var(--radius-md);border:1px solid var(--border-gold);box-shadow:var(--shadow-lux);">
      </div>
    </div>

    <!-- Valeurs -->
    <div style="text-align:center;margin-bottom:4rem;">
      <span class="section-subtitle">NOS VALEURS</span>
      <h2 class="section-title">Ce Qui Nous Distingue</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:2rem;margin-bottom:5rem;">
      <?php foreach([
        ['🌊','Vue Lac Tanganyika','Emplacement unique en bord de lac avec accès direct à la plage privée.'],
        ['🇧🇮','Hospitalité Burundaise','Accueil chaleureux, personnel multilingue (fr/en/rn), service personnalisé.'],
        ['💳','Paiements Locaux & Internationaux','Lumicash, EcoCash, banques locales, VISA, USD, BIF — à votre convenance.'],
        ['🌿','Développement Durable','Produits locaux, jardin bio, réduction de l\'empreinte carbone.'],
        ['🎪','Événements Sur Mesure','Mariages, séminaires, conférences et célébrations privées en bord de lac.'],
        ['🔒','Sécurité & Confidentialité','Enceinte sécurisée 24h/24, parking surveillé, données clients protégées.'],
      ] as [$ico,$titre,$desc]):?>
      <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-md);padding:2rem;text-align:center;">
        <div style="font-size:2.5rem;color:var(--accent-gold-primary);margin-bottom:1rem;"><?php echo $ico;?></div>
        <h3 style="color:var(--text-light-primary);font-family:var(--font-heading);margin-bottom:.75rem;font-size:1.1rem;"><?php echo e($titre);?></h3>
        <p style="color:var(--text-muted);font-size:.875rem;line-height:1.6;"><?php echo e($desc);?></p>
      </div>
      <?php endforeach;?>
    </div>

    <!-- Chiffres clés -->
    <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:3rem;text-align:center;">
      <h2 class="section-title" style="margin-bottom:2.5rem;">Le Lézard Bleu en Chiffres</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:2rem;">
        <?php foreach([
          ['4','Suites & Villas'],
          ['2','Restaurants'],
          ['1','Spa Holistique'],
          ['24/7','Conciergerie'],
          ['100%','Vue Lac'],
          ['6','Modes de Paiement'],
        ] as [$n,$l]):?>
        <div>
          <div style="font-family:var(--font-heading);font-size:2.5rem;color:var(--accent-gold-primary);font-weight:700;"><?php echo e($n);?></div>
          <div style="font-size:.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;"><?php echo e($l);?></div>
        </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
