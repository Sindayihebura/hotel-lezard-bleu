#!/usr/bin/env python3
"""
normalize_layout.py — Harmonise l'en-tête, le menu mobile, le pied de page
et le chargement des scripts sur toutes les pages statiques de dist/.

Usage : python3 tools/normalize_layout.py
"""
import re
import pathlib

ROOT = pathlib.Path(__file__).resolve().parent.parent
DIST = ROOT / "dist"

NAV_ITEMS = [
    ("/index.html", "Accueil"),
    ("/presentation.html", "Hôtel"),
    ("/chambres.html", "Suites"),
    ("/reservation.html", "Réserver"),
    ("/galerie.html", "Galerie"),
    ("/services.html", "Services"),
    ("/conferences.html", "Conférences"),
    ("/offres.html", "Offres"),
    ("/contact.html", "Contact"),
]

DRAWER_ITEMS = [
    ("/index.html", "Accueil"),
    ("/presentation.html", "Présentation"),
    ("/chambres.html", "Suites & Villas"),
    ("/reservation.html", "Réservation en ligne"),
    ("/galerie.html", "Galerie photos"),
    ("/services.html", "Services & Restaurant"),
    ("/conferences.html", "Conférences & Événements"),
    ("/offres.html", "Offres spéciales"),
    ("/contact.html", "Contact & Accès"),
    ("/account.html", "Mon compte"),
]

HEADER = """  <!-- EN-TÊTE -->
  <header class="header-navbar">
    <div class="container nav-container">
      <a href="/index.html" class="nav-logo" aria-label="Le Lézard Bleu — Accueil">
        <span class="nav-logo-icon" aria-hidden="true">L</span>
        <span class="nav-logo-text">
          <span class="nav-brand-title">LE LÉZARD BLEU</span>
          <span class="nav-brand-subtitle">Bujumbura ★★★★★</span>
        </span>
      </a>

      <nav class="nav-desktop" aria-label="Navigation principale">
        <ul class="nav-menu">
{nav}
        </ul>
      </nav>

      <div class="nav-actions">
        <div class="currency-switcher" role="group" aria-label="Devise d'affichage">
          <button type="button" class="currency-btn" data-currency="BIF" aria-pressed="true">BIF</button>
          <button type="button" class="currency-btn" data-currency="USD" aria-pressed="false">USD</button>
        </div>
        <a href="/reservation.html" class="btn btn-gold nav-cta">Réserver</a>
        <a href="/login.html" class="btn btn-outline-gold nav-auth" id="authButton">Connexion</a>
        <button id="mobileToggle" class="mobile-toggle" type="button"
                aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="mobileDrawer">☰</button>
      </div>
    </div>
  </header>

  <!-- MENU MOBILE -->
  <aside id="mobileDrawer" class="mobile-drawer" aria-label="Menu mobile" aria-hidden="true">
    <button id="drawerClose" class="drawer-close" type="button" aria-label="Fermer le menu">✕</button>

    <div class="drawer-currency">
      <span class="drawer-currency-label">Devise d'affichage</span>
      <div class="currency-switcher" role="group" aria-label="Devise d'affichage (menu)">
        <button type="button" class="currency-btn" data-currency="BIF">🇧🇮 BIF</button>
        <button type="button" class="currency-btn" data-currency="USD">💵 USD</button>
      </div>
    </div>

    <div class="drawer-nav">
      <ul class="drawer-menu">
{drawer}
        <li><a href="/login.html" class="drawer-link" id="mobileAuthLink">Connexion</a></li>
      </ul>
    </div>

    <div class="drawer-footer">
      <a href="/reservation.html" class="btn btn-gold">Réserver maintenant</a>
      <a href="tel:+25722000000" class="drawer-link" style="font-size:0.95rem;">📞 +257 22 00 00 00</a>
      <a href="mailto:reservation@lezardbleu-hotel.bi" class="drawer-link" style="font-size:0.95rem;">✉️ reservation@lezardbleu-hotel.bi</a>
    </div>
  </aside>
  <div id="drawerBackdrop" class="drawer-backdrop" aria-hidden="true"></div>
""".format(
    nav="\n".join(
        f'          <li><a href="{href}" class="nav-link">{label}</a></li>'
        for href, label in NAV_ITEMS
    ),
    drawer="\n".join(
        f'        <li><a href="{href}" class="drawer-link">{label}</a></li>'
        for href, label in DRAWER_ITEMS
    ),
)

FOOTER = """  <!-- PIED DE PAGE -->
  <footer class="footer-section">
    <div class="container">
      <div class="footer-grid">

        <div class="footer-col">
          <div class="nav-logo" style="margin-bottom: 1.25rem;">
            <span class="nav-logo-icon" aria-hidden="true">L</span>
            <span class="nav-logo-text">
              <span class="nav-brand-title">LE LÉZARD BLEU</span>
              <span class="nav-brand-subtitle">Hôtel &amp; Spa ★★★★★</span>
            </span>
          </div>
          <p style="color: var(--text-muted); font-size: 0.92rem; margin-bottom: 1.25rem;">
            L'excellence hôtelière au bord du Lac Tanganyika : suites de prestige,
            gastronomie, spa et espaces de conférence à Bujumbura.
          </p>
          <p style="color: var(--text-muted); font-size: 0.82rem;">
            Paiements : BIF &amp; USD · Lumicash · EcoCash · Visa · Virement bancaire
          </p>
        </div>

        <div class="footer-col">
          <h4>Navigation</h4>
          <ul class="footer-links">
            <li><a href="/presentation.html">L'hôtel &amp; ses valeurs</a></li>
            <li><a href="/chambres.html">Chambres &amp; suites</a></li>
            <li><a href="/reservation.html">Réservation en ligne</a></li>
            <li><a href="/services.html">Restaurant &amp; spa</a></li>
            <li><a href="/conferences.html">Séminaires &amp; mariages</a></li>
            <li><a href="/offres.html">Offres &amp; privilèges</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h4>Conciergerie</h4>
          <div class="footer-contact-item">
            <span class="footer-contact-icon" aria-hidden="true">📍</span>
            <span>Avenue de la Plage, bord du Lac Tanganyika, Bujumbura, Burundi</span>
          </div>
          <div class="footer-contact-item">
            <span class="footer-contact-icon" aria-hidden="true">📞</span>
            <span><a href="tel:+25722000000">+257 22 00 00 00</a> · 24h/24</span>
          </div>
          <div class="footer-contact-item">
            <span class="footer-contact-icon" aria-hidden="true">✉️</span>
            <span><a href="mailto:reservation@lezardbleu-hotel.bi">reservation@lezardbleu-hotel.bi</a></span>
          </div>
          <div class="footer-contact-item">
            <span class="footer-contact-icon" aria-hidden="true">💬</span>
            <span>WhatsApp : <a href="https://wa.me/25779000000" rel="noopener">+257 79 00 00 00</a></span>
          </div>
        </div>

        <div class="footer-col">
          <h4>Newsletter Privilège</h4>
          <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1rem;">
            Offres exclusives et invitations privées, deux fois par mois. Pas de spam.
          </p>
          <form id="newsletterForm" action="#" method="post">
            <label for="newsletterEmail" class="sr-only">Votre adresse e-mail</label>
            <div style="display: flex; gap: 0.5rem; align-items: stretch;">
              <input type="email" id="newsletterEmail" name="email" class="form-input"
                     placeholder="votre@email.com" autocomplete="email" required
                     style="font-size: 0.85rem; padding: 0.65rem 0.85rem; min-height: 44px;">
              <button type="submit" class="btn btn-gold" style="padding: 0.65rem 1.1rem;">OK</button>
            </div>
            <p class="form-hint" style="margin-top: 0.6rem;">
              En vous inscrivant, vous acceptez notre
              <a href="/legal.html#confidentialite" style="color: var(--accent-gold-primary);">politique de confidentialité</a>.
            </p>
          </form>
        </div>

      </div>

      <div class="footer-bottom">
        <div>© <span id="currentYear"></span> Hôtel Le Lézard Bleu &amp; Spa — Bujumbura, Burundi. Tous droits réservés.</div>
        <div style="display: flex; gap: 1.25rem; flex-wrap: wrap;">
          <a href="/legal.html#mentions">Mentions légales</a>
          <a href="/legal.html#confidentialite">Confidentialité</a>
          <a href="/legal.html#cgv">CGV</a>
          <a href="/contact.html">Contact</a>
        </div>
      </div>
    </div>
  </footer>
"""

SCRIPTS = """  <!-- Scripts communs (ordre de chargement important) -->
  <script src="/env-config.js"></script>
  <script src="/vendor/supabase.js"></script>
  <script src="/assets/js/forms.js"></script>
  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/supabase-auth.js"></script>
  <script src="/assets/js/app.js"></script>
"""


def normalize(path: pathlib.Path) -> None:
    html = path.read_text(encoding="utf-8")
    original = html

    # 1. En-tête + menu mobile
    aside_re = re.compile(r"\s*<aside id=\"mobileDrawer\".*?</aside>", re.S)
    html = aside_re.sub("", html, count=1)
    html = re.sub(r"\s*<div id=\"drawerBackdrop\"[^>]*></div>", "", html)
    html = re.sub(
        r"<header class=\"header-navbar\">.*?</header>",
        lambda _m: HEADER,
        html,
        count=1,
        flags=re.S,
    )

    # 2. Pied de page
    footer_re = re.compile(r"<footer[^>]*>.*?</footer>", re.S)
    if footer_re.search(html):
        html = footer_re.sub(lambda _m: FOOTER, html, count=1)
    else:
        html = html.replace("</body>", FOOTER + "\n</body>", 1)

    # 3. Scripts communs : suppression des anciennes références puis injection dans <head>
    for pattern in [
        r"\s*<script src=\"/env-config\.js\"></script>",
        r"\s*<script src=\"/vendor/supabase\.js\"></script>",
        r"\s*<script src=\"/assets/js/forms\.js\"></script>",
        r"\s*<script src=\"/assets/js/main\.js\"></script>",
        r"\s*<script src=\"/assets/js/supabase-auth\.js\"></script>",
        r"\s*<script src=\"/assets/js/app\.js\"></script>",
    ]:
        html = re.sub(pattern, "", html)

    # Retire les scripts inline devenus obsolètes (module + top-level await)
    html = re.sub(r"\s*<script type=\"module\" src=\"/assets/js/supabase-auth\.js\"></script>", "", html)

    # 4. Métadonnées communes (icône, couleur de thème)
    if 'rel="icon"' not in html:
        html = html.replace(
            "</head>",
            '  <link rel="icon" href="/favicon.svg" type="image/svg+xml">\n'
            '  <meta name="theme-color" content="#0E1626">\n</head>',
            1,
        )

    if SCRIPTS not in html:
        html = html.replace("</head>", SCRIPTS + "</head>", 1)

    # Nettoyage des commentaires de section dupliqués
    html = re.sub(r"(<!-- PIED DE PAGE -->\s*){2,}", "<!-- PIED DE PAGE -->\n", html)
    html = re.sub(r"(<!-- EN-TÊTE -->\s*){2,}", "<!-- EN-TÊTE -->\n", html)
    html = re.sub(r"(<!-- (?:MENU MOBILE|Mobile Drawer) -->\s*){1,}", "", html)
    html = re.sub(r"<!-- (?:HEADER NAVBAR|Header|Footer) -->\s*", "", html)

    if html != original:
        path.write_text(html, encoding="utf-8")
        print(f"✓ {path.name}")


if __name__ == "__main__":
    for page in sorted(DIST.glob("*.html")):
        normalize(page)
