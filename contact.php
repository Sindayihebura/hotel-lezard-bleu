<?php
define('PAGE_TITLE', 'Contact & Conciergerie | Hôtel Le Lézard Bleu Bujumbura');
require_once __DIR__ . '/includes/header.php';
?>

<!-- BANNER -->
<section style="position: relative; padding: 7rem 0 3rem 0; background: linear-gradient(to bottom, rgba(7,12,20,0.85), rgba(7,12,20,0.95)), url('assets/images/hero_tanganyika.jpg') center/cover; text-align: center;">
  <div class="container">
    <span class="section-subtitle">À VOTRE ÉCOUTE 24H/24 À BUJUMBURA</span>
    <h1 class="section-title">Contact & Conciergerie</h1>
    <p class="section-desc">
      Notre équipe à Bujumbura se tient à votre disposition pour faciliter vos réservations et transferts.
    </p>
  </div>
</section>

<!-- CONTACT DETAILS & FORM -->
<section class="section-padding">
  <div class="container">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3.5rem;">
      
      <!-- Contact Information Cards -->
      <div>
        <span class="section-subtitle">NOUS REJOINDRE</span>
        <h2 class="section-title" style="font-size: 2rem;">Accès & Contacts au Burundi</h2>
        <p style="color: var(--text-muted); margin-bottom: 2rem;">
          Situé idéalement sur la corniche du Lac Tanganyika avec un accès rapide depuis l'aéroport Melchior Ndadaye.
        </p>

        <div style="display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2.5rem;">
          <div style="background: var(--bg-dark-card); border: 1px solid var(--border-light); padding: 1.5rem; border-radius: var(--radius-md); display: flex; gap: 1.25rem; align-items: flex-start;">
            <span style="font-size: 1.75rem; color: var(--accent-gold-primary);">📍</span>
            <div>
              <h4 style="color: var(--text-light-primary); font-size: 1.1rem; margin-bottom: 0.25rem;">Adresse Officielle</h4>
              <p style="color: var(--text-muted); font-size: 0.95rem;">Avenue du Lac, Bord du Lac Tanganyika, Bujumbura, Burundi</p>
            </div>
          </div>

          <div style="background: var(--bg-dark-card); border: 1px solid var(--border-light); padding: 1.5rem; border-radius: var(--radius-md); display: flex; gap: 1.25rem; align-items: flex-start;">
            <span style="font-size: 1.75rem; color: var(--accent-gold-primary);">📞</span>
            <div>
              <h4 style="color: var(--text-light-primary); font-size: 1.1rem; margin-bottom: 0.25rem;">Téléphone & WhatsApp Conciergerie</h4>
              <p style="color: var(--text-muted); font-size: 0.95rem;">+257 22 00 00 00 / +257 79 00 00 00 (Ligne directe 24h/24)</p>
            </div>
          </div>

          <div style="background: var(--bg-dark-card); border: 1px solid var(--border-light); padding: 1.5rem; border-radius: var(--radius-md); display: flex; gap: 1.25rem; align-items: flex-start;">
            <span style="font-size: 1.75rem; color: var(--accent-gold-primary);">✉️</span>
            <div>
              <h4 style="color: var(--text-light-primary); font-size: 1.1rem; margin-bottom: 0.25rem;">Courrier Électronique</h4>
              <p style="color: var(--text-muted); font-size: 0.95rem;">reservation@lezardbleu-hotel.bi</p>
            </div>
          </div>
        </div>

        <!-- Google Maps Embed Placeholder (Bujumbura Lakefront) -->
        <div style="border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border-gold); height: 260px; box-shadow: var(--shadow-lux);">
          <iframe 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31853.945207797746!2d29.351656!3d-3.3822!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19c142c6767425ed%3A0xb35a0d1645e7f22!2sBujumbura%2C%20Burundi!5e0!3m2!1sfr!2sfr!4v1700000000000" 
            width="100%" 
            height="100%" 
            style="border:0; filter: invert(90%) hue-rotate(180deg);" 
            allowfullscreen="" 
            loading="lazy">
          </iframe>
        </div>
      </div>

      <!-- Functional PHP Contact Form -->
      <div style="background: var(--bg-dark-card); border: 1px solid var(--border-gold); border-radius: var(--radius-lg); padding: 3rem; box-shadow: var(--shadow-lux);">
        <h2 style="font-size: 1.75rem; color: var(--text-light-primary); margin-bottom: 0.5rem;">Envoyez-nous un Message</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem;">
          Notre conciergerie à Bujumbura vous répondra sous 2 heures.
        </p>

        <form id="contactForm">
          <input type="hidden" name="action" value="contact">
          <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

          <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="contact_nom" class="form-label">Votre Nom & Prénom *</label>
            <input type="text" id="contact_nom" name="nom" required class="form-input" placeholder="ex: Jean-Marie Ndayishimiye">
          </div>

          <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="contact_email" class="form-label">Votre Adresse E-mail *</label>
            <input type="email" id="contact_email" name="email" required class="form-input" placeholder="ex: jm.ndayishimiye@example.bi">
          </div>

          <div class="form-group" style="margin-bottom: 1.25rem;">
            <label for="contact_sujet" class="form-label">Sujet de Votre Demande *</label>
            <select id="contact_sujet" name="sujet" class="form-select">
              <option value="Renseignements Réservation">Renseignements Réservation</option>
              <option value="Paiement Lumicash / EcoCash">Paiement Lumicash / EcoCash / Banque</option>
              <option value="Demande de transfert VIP Aéroport">Demande de transfert VIP Aéroport</option>
              <option value="Réservation Table Restaurant">Réservation Table Restaurant</option>
              <option value="Séminaire B2B / Mariage">Séminaire B2B / Mariage</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom: 2rem;">
            <label for="contact_message" class="form-label">Votre Message *</label>
            <textarea id="contact_message" name="message" rows="5" required class="form-input" placeholder="Dites-nous comment nous pouvons vous servir..."></textarea>
          </div>

          <button type="submit" class="btn btn-gold" style="width: 100%; padding: 1.1rem;">
            Envoyer Mon Message ✉️
          </button>
        </form>
      </div>

    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const formData = new FormData(contactForm);

      try {
        const response = await fetch('/api/send_contact.php', {
          method: 'POST',
          body: formData
        });
        const data = await response.json();
        if (data.success || (data.data && data.data.sent)) {
          if (window.showToast) window.showToast('Votre message a été envoyé. Merci !', 'success');
          contactForm.reset();
        } else {
          const msg = data.error?.message || data.message || 'Erreur d\'envoi.';
          if (window.showToast) window.showToast(msg, 'error');
        }
      } catch (err) {
        if (window.showToast) window.showToast('Erreur d\'envoi du message.', 'error');
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
