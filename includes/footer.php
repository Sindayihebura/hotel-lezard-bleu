  <!-- FOOTER SECTION -->
  <footer class="footer-section">
    <div class="container">
      <div class="footer-grid">
        
        <!-- Brand Info -->
        <div class="footer-col">
          <div class="nav-logo" style="margin-bottom: 1.25rem;">
            <div class="nav-logo-icon">L</div>
            <div class="nav-logo-text">
              <span class="nav-brand-title">LE LÉZARD BLEU</span>
              <span class="nav-brand-subtitle">Hôtel & Spa ★★★★★</span>
            </div>
          </div>
          <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
            L'excellence hôtelière réinventée. Un sanctuaire d'exception offrant un luxe intemporel, un spa d'exception et une gastronomie raffinée.
          </p>
        </div>

        <!-- Quick Links -->
        <div class="footer-col">
          <h4>Navigation</h4>
          <ul class="footer-links">
            <li><a href="presentation.php">Notre Histoire & Valeurs</a></li>
            <li><a href="chambres.php">Nos Chambres & Suites</a></li>
            <li><a href="services.php">Restaurant Gastronomique</a></li>
            <li><a href="services.php#spa">Sanctuaire Spa & Bien-être</a></li>
            <li><a href="conferences.php">Espaces Séminaires & Mariages</a></li>
            <li><a href="offres.php">Offres Spéciales & Privilèges</a></li>
          </ul>
        </div>

        <!-- Contact Info -->
        <div class="footer-col">
          <h4>Conciergerie & Contact</h4>
          <div class="footer-contact-item">
            <span class="footer-contact-icon">📍</span>
            <span>Avenue de la Plage, Bord du Lac Tanganyika, Bujumbura, Burundi</span>
          </div>
          <div class="footer-contact-item">
            <span class="footer-contact-icon">📞</span>
            <span>+257 22 00 00 00 (Disponibilité 24/7)</span>
          </div>
          <div class="footer-contact-item">
            <span class="footer-contact-icon">✉️</span>
            <span>reservation@lezardbleu-hotel.bi</span>
          </div>
          <div class="footer-contact-item">
            <span class="footer-contact-icon">💬</span>
            <span>WhatsApp : +257 79 00 00 00</span>
          </div>
        </div>

        <!-- Newsletter -->
        <div class="footer-col">
          <h4>Newsletter Privilège</h4>
          <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1rem;">
            Inscrivez-vous pour recevoir en avant-première nos offres exclusives et invitations privées.
          </p>
          <form onsubmit="event.preventDefault(); if(window.showToast) window.showToast('Merci ! Vous êtes désormais inscrit à notre newsletter privilège.'); this.reset();">
            <div style="display: flex; gap: 0.5rem;">
              <input type="email" placeholder="Votre adresse e-mail" required class="form-input" style="font-size: 0.85rem; padding: 0.65rem 0.85rem;">
              <button type="submit" class="btn btn-gold" style="padding: 0.65rem 1rem;">OK</button>
            </div>
          </form>
        </div>

      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom">
        <div>
          © <?php echo date('Y'); ?> Hôtel Le Lézard Bleu & Spa — Bujumbura, Burundi. Tous droits réservés.
        </div>
        <div style="display: flex; gap: 1.5rem;">
          <a href="/contact.php">Mentions Légales</a>
          <a href="/contact.php">Politique de Confidentialité</a>
          <a href="/contact.php">CGV Réservations</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Scripts -->
  <script src="/assets/js/main.js"></script>
  <script src="/assets/js/booking.js"></script>
</body>
</html>
