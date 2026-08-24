<?php
declare(strict_types=1);
define('PAGE_TITLE', 'Conférences & Séminaires | Hôtel Le Lézard Bleu Bujumbura');
require_once __DIR__ . '/includes/header.php';
$csrfToken = generate_csrf_token();
?>
<section style="position:relative;padding:7rem 0 3rem;background:linear-gradient(to bottom,rgba(7,12,20,.85),rgba(7,12,20,.95)),url('assets/images/hero_hotel.jpg') center/cover;text-align:center;">
  <div class="container">
    <span class="section-subtitle">ÉVÉNEMENTS PROFESSIONNELS À BUJUMBURA</span>
    <h1 class="section-title">Conférences, Séminaires & Mariages</h1>
    <p class="section-desc">Des espaces modulables au bord du Lac Tanganyika pour vos événements d'entreprise et cérémonies.</p>
  </div>
</section>

<section class="section-padding">
  <div class="container">

    <!-- Espaces -->
    <div style="text-align:center;margin-bottom:3rem;">
      <span class="section-subtitle">NOS ESPACES ÉVÉNEMENTIELS</span>
      <h2 class="section-title">Des Salles Adaptées à Chaque Événement</h2>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2rem;margin-bottom:5rem;">
      <?php foreach([
        ['🏛️','Salle Tanganyika','Grande salle plénière avec vue lac. Jusqu\'à 200 personnes. Équipement audiovisuel complet.','200 personnes','Plénière'],
        ['🤝','Salle Kirundo','Salle de réunion executive. Idéale pour conseils d\'administration et comités de direction.','30 personnes','Boardroom'],
        ['💒','Espace Jardin Lac','Espace extérieur aménagé pour mariages et cocktails en plein air au bord de l\'eau.','300 personnes','Cocktail & Mariage'],
        ['💻','Salle Bujumbura','Salle de formation équipée de postes informatiques et connexion Wi-Fi dédiée.','50 personnes','Formation'],
      ] as [$ico,$nom,$desc,$cap,$type]):?>
      <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-md);padding:2rem;">
        <div style="font-size:2.5rem;color:var(--accent-gold-primary);margin-bottom:1rem;"><?php echo $ico;?></div>
        <h3 style="color:var(--text-light-primary);font-family:var(--font-heading);font-size:1.2rem;margin-bottom:.5rem;"><?php echo e($nom);?></h3>
        <span style="background:rgba(212,175,55,.12);color:var(--accent-gold-primary);font-size:.7rem;font-weight:600;padding:.2rem .6rem;border-radius:4px;margin-bottom:.75rem;display:inline-block;"><?php echo e($type);?></span>
        <p style="color:var(--text-muted);font-size:.875rem;line-height:1.6;margin-bottom:.75rem;"><?php echo e($desc);?></p>
        <div style="color:var(--accent-gold-primary);font-size:.85rem;font-weight:600;">👥 <?php echo e($cap);?></div>
      </div>
      <?php endforeach;?>
    </div>

    <!-- Services inclus -->
    <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:3rem;margin-bottom:4rem;">
      <h2 class="section-title" style="font-size:1.75rem;margin-bottom:2rem;">Services Inclus</h2>
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
        <?php foreach(['Projecteur & écran 4K','Système audio professionnel','Wi-Fi haut débit dédié','Tableau blanc interactif','Café/thé en continu','Pause-déjeuner gastronomique','Parking sécurisé','Accueil bilingue fr/en'] as $s):?>
        <div style="display:flex;align-items:center;gap:.6rem;padding:.75rem;background:rgba(7,12,20,.4);border-radius:8px;">
          <span style="color:var(--accent-gold-primary);">✓</span>
          <span style="color:var(--text-light-secondary);font-size:.875rem;"><?php echo e($s);?></span>
        </div>
        <?php endforeach;?>
      </div>
    </div>

    <!-- Formulaire devis -->
    <div style="background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:3rem;box-shadow:var(--shadow-lux);">
      <h2 style="color:var(--text-light-primary);font-size:1.75rem;margin-bottom:.5rem;">Demande de Devis</h2>
      <p style="color:var(--text-muted);margin-bottom:2rem;">Réponse sous 2 heures par notre équipe événementielle.</p>

      <div id="confMsg" style="display:none;background:rgba(34,197,94,.1);border:1px solid #22c55e;color:#86efac;padding:1rem;border-radius:8px;margin-bottom:1.5rem;">
        ✓ Votre demande a été envoyée ! Nous vous contactons très prochainement.
      </div>

      <form id="conferenceForm">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken);?>">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;margin-bottom:1.25rem;">
          <div><label class="form-label">Société / Organisation</label><input type="text" name="company" class="form-input" maxlength="150"></div>
          <div><label class="form-label">Nom du Contact *</label><input type="text" name="contact_name" class="form-input" required maxlength="150"></div>
          <div><label class="form-label">Email *</label><input type="email" name="email" class="form-input" required maxlength="150"></div>
          <div><label class="form-label">Téléphone *</label><input type="tel" name="phone" class="form-input" required maxlength="20" placeholder="+257 79 00 00 00"></div>
          <div><label class="form-label">Type d'Événement *</label>
            <select name="event_type" class="form-select" required>
              <option value="">-- Choisir --</option>
              <option>Séminaire d'entreprise</option><option>Conférence internationale</option>
              <option>Conseil d'administration</option><option>Formation professionnelle</option>
              <option>Mariage</option><option>Cocktail & Réception</option><option>Autre</option>
            </select>
          </div>
          <div><label class="form-label">Nombre de Participants *</label><input type="number" name="nb_participants" class="form-input" required min="5" max="300"></div>
          <div><label class="form-label">Date Prévue *</label><input type="date" name="event_date" class="form-input" required></div>
          <div><label class="form-label">Durée (jours) *</label><input type="number" name="duration_days" class="form-input" required min="1" value="1"></div>
        </div>
        <div style="margin-bottom:1.5rem;">
          <label class="form-label">Besoins Spécifiques</label>
          <textarea name="message" class="form-input" rows="4" maxlength="2000" placeholder="Décrivez votre événement, besoins audiovisuels, restauration souhaitée…"></textarea>
        </div>
        <button type="submit" class="btn btn-gold" style="padding:1rem 3rem;font-size:1rem;">
          Envoyer ma Demande de Devis ⟶
        </button>
      </form>
    </div>
  </div>
</section>

<script>
document.getElementById('conferenceForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true; btn.textContent = 'Envoi…';
    try {
        const resp = await fetch('/api/send_contact.php', {
            method:'POST', body: new FormData(this)
        });
        const data = await resp.json();
        if(data.success || data.data?.sent) {
            document.getElementById('confMsg').style.display='block';
            this.reset();
        } else if(window.showToast) {
            window.showToast(data.error?.message || 'Erreur envoi.','error');
        }
    } catch(err) {
        if(window.showToast) window.showToast('Erreur réseau.','error');
    }
    btn.disabled=false; btn.textContent='Envoyer ma Demande de Devis ⟶';
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
