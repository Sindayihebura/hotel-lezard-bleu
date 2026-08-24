<?php
if (!defined('PAGE_TITLE')) {
    define('PAGE_TITLE', 'Le Lézard Bleu | Hôtel & Spa 5 Stars Luxury Resort Bujumbura');
}
// Bootstrap + DB si pas encore chargés
if (!function_exists('env')) {
    require_once __DIR__ . '/../config/bootstrap.php';
}
require_once __DIR__ . '/../config/db.php';

$activeCurrency   = $_SESSION['currency'] ?? 'BIF';
$isLoggedIn       = !empty($_SESSION['customer_auth']);
$activeAdminLogin = !empty($_SESSION['admin_auth']);
$currentPage      = basename($_SERVER['PHP_SELF']);

// Détecter si on est dans /public/ ou à la racine pour les URLs
$inPublic = (strpos($_SERVER['PHP_SELF'], '/public/') !== false);
$baseUrl  = $inPublic ? '..' : '.';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars(PAGE_TITLE); ?></title>
  
  <!-- SEO & Open Graph Meta Tags -->
  <meta name="description" content="Découvrez Le Lézard Bleu & Spa à Bujumbura (Burundi). Hôtel 5 étoiles au bord du Lac Tanganyika avec suites de luxe, restaurant gastronomique, spa et salles de conférence.">
  <meta name="keywords" content="hôtel bujumbura, hôtel de luxe burundi, lac tanganyika, suites bujumbura, lumicash, ecocash, hôtel 5 étoiles burundi">
  <meta property="og:title" content="<?php echo htmlspecialchars(PAGE_TITLE); ?>">
  <meta property="og:description" content="Une expérience hôtelière 5 étoiles inoubliable à Bujumbura au bord du Lac Tanganyika.">
  <meta property="og:type" content="website">
  <meta property="og:image" content="assets/images/hero_tanganyika.jpg">

  <!-- Schema.org JSON-LD (Burundi Hotel) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Hotel",
    "name": "Hôtel Le Lézard Bleu & Spa Bujumbura",
    "description": "Hôtel de luxe 5 étoiles au bord du Lac Tanganyika à Bujumbura, Burundi.",
    "starRating": {
      "@type": "Rating",
      "ratingValue": "5"
    },
    "priceRange": "BIF / USD",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "Avenue du Lac, Bord du Lac Tanganyika",
      "addressLocality": "Bujumbura",
      "addressCountry": "BI"
    },
    "telephone": "+257 22 00 00 00",
    "image": "assets/images/hero_tanganyika.jpg"
  }
  </script>

  <!-- Custom Luxury Stylesheet -->
  <link rel="stylesheet" href="<?php echo $baseUrl; ?>/assets/css/style.css">
</head>
<body>

  <!-- HEADER NAVBAR -->
  <header class="header-navbar">
    <div class="container nav-container">
      <a href="<?php echo $baseUrl; ?>/index.php" class="nav-logo">
        <div class="nav-logo-icon">L</div>
        <div class="nav-logo-text">
          <span class="nav-brand-title">LE LÉZARD BLEU</span>
          <span class="nav-brand-subtitle">Bujumbura • Burundi ★★★★★</span>
        </div>
      </a>

      <!-- Desktop Navigation -->
      <nav>
        <ul class="nav-menu">
          <li><a href="<?php echo $baseUrl; ?>/index.php" class="nav-link <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Accueil</a></li>
          <li><a href="<?php echo $baseUrl; ?>/presentation.php" class="nav-link <?php echo ($currentPage == 'presentation.php') ? 'active' : ''; ?>">Présentation</a></li>
          <li><a href="<?php echo $baseUrl; ?>/chambres.php" class="nav-link <?php echo ($currentPage == 'chambres.php') ? 'active' : ''; ?>">Suites & Villas</a></li>
          <li><a href="<?php echo $baseUrl; ?>/reservation.php" class="nav-link <?php echo ($currentPage == 'reservation.php') ? 'active' : ''; ?>">Réservation</a></li>
          <li><a href="<?php echo $baseUrl; ?>/galerie.php" class="nav-link <?php echo ($currentPage == 'galerie.php') ? 'active' : ''; ?>">Galerie</a></li>
          <li><a href="<?php echo $baseUrl; ?>/services.php" class="nav-link <?php echo ($currentPage == 'services.php') ? 'active' : ''; ?>">Services & Resto</a></li>
          <li><a href="<?php echo $baseUrl; ?>/conferences.php" class="nav-link <?php echo ($currentPage == 'conferences.php') ? 'active' : ''; ?>">Conférences</a></li>
          <li><a href="<?php echo $baseUrl; ?>/offres.php" class="nav-link <?php echo ($currentPage == 'offres.php') ? 'active' : ''; ?>">Offres</a></li>
          <li><a href="<?php echo $baseUrl; ?>/contact.php" class="nav-link <?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>
        </ul>
      </nav>

      <!-- Currency Selector Switcher & CTA -->
      <div style="display: flex; align-items: center; gap: 0.85rem;">
        
        <!-- Toggle Devise BIF / USD -->
        <div class="currency-switcher">
          <button type="button" class="currency-btn <?php echo ($activeCurrency === 'BIF') ? 'active' : ''; ?>" data-currency="BIF">
            🇧🇮 BIF
          </button>
          <button type="button" class="currency-btn <?php echo ($activeCurrency === 'USD') ? 'active' : ''; ?>" data-currency="USD">
            💵 USD ($)
          </button>
        </div>

        <a href="<?php echo $baseUrl; ?>/reservation.php" class="btn btn-gold" style="padding: 0.65rem 1.25rem;">Réserver</a>
        <?php if ($isLoggedIn): ?>
          <a href="<?php echo $baseUrl; ?>/public/mon-compte.php" class="btn btn-outline-gold" style="padding:0.65rem 1rem;font-size:0.85rem;">Mon Compte</a>
        <?php else: ?>
          <a href="<?php echo $baseUrl; ?>/public/connexion.php" class="btn btn-outline-gold" style="padding:0.65rem 1rem;font-size:0.85rem;">Connexion</a>
        <?php endif; ?>
        <button id="mobileToggle" class="mobile-toggle" aria-label="Menu Mobile">☰</button>
      </div>
    </div>
  </header>

  <!-- MOBILE NAVIGATION DRAWER -->
  <aside id="mobileDrawer" class="mobile-drawer">
    <button id="drawerClose" class="drawer-close" aria-label="Fermer">✕</button>
    <div style="margin-bottom: 1.5rem; text-align: center;">
      <span style="font-size: 0.8rem; color: var(--accent-gold-primary); text-transform: uppercase;">Devise d'affichage :</span>
      <div class="currency-switcher" style="margin-top: 0.5rem; justify-content: center;">
        <button type="button" class="currency-btn <?php echo ($activeCurrency === 'BIF') ? 'active' : ''; ?>" data-currency="BIF">
          🇧🇮 BIF
        </button>
        <button type="button" class="currency-btn <?php echo ($activeCurrency === 'USD') ? 'active' : ''; ?>" data-currency="USD">
          💵 USD ($)
        </button>
      </div>
    </div>
    <ul class="drawer-menu">
      <li><a href="<?php echo $baseUrl; ?>/index.php" class="drawer-link">Accueil</a></li>
      <li><a href="<?php echo $baseUrl; ?>/presentation.php" class="drawer-link">Présentation</a></li>
      <li><a href="<?php echo $baseUrl; ?>/chambres.php" class="drawer-link">Suites & Villas</a></li>
      <li><a href="<?php echo $baseUrl; ?>/reservation.php" class="drawer-link">Réservation en Ligne</a></li>
      <li><a href="<?php echo $baseUrl; ?>/galerie.php" class="drawer-link">Galerie Photos</a></li>
      <li><a href="<?php echo $baseUrl; ?>/services.php" class="drawer-link">Services & Restaurant</a></li>
      <li><a href="<?php echo $baseUrl; ?>/conferences.php" class="drawer-link">Salles & Événements</a></li>
      <li><a href="<?php echo $baseUrl; ?>/offres.php" class="drawer-link">Offres Spéciales</a></li>
      <li><a href="<?php echo $baseUrl; ?>/contact.php" class="drawer-link">Avis & Contact</a></li>
      <?php if ($isLoggedIn): ?>
        <li><a href="<?php echo $baseUrl; ?>/public/mon-compte.php" class="drawer-link" style="color:var(--accent-gold-primary)">Mon Compte</a></li>
        <li><a href="<?php echo $baseUrl; ?>/public/deconnexion.php" class="drawer-link">Déconnexion</a></li>
      <?php else: ?>
        <li><a href="<?php echo $baseUrl; ?>/public/connexion.php" class="drawer-link" style="color:var(--accent-gold-primary)">Connexion</a></li>
        <li><a href="<?php echo $baseUrl; ?>/public/inscription.php" class="drawer-link">Créer un compte</a></li>
      <?php endif; ?>
    </ul>
  </aside>
