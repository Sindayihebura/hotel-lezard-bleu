<?php
declare(strict_types=1);
define('PAGE_TITLE', 'Réservation en Ligne | Hôtel Le Lézard Bleu Bujumbura');
require_once __DIR__ . '/includes/header.php';

$preselectedRoomId = isset($_GET['room_id']) ? (int)$_GET['room_id'] : 0;
$dateArrivee = isset($_GET['date_arrivee']) ? preg_replace('/[^0-9\-]/','',$_GET['date_arrivee']) : date('Y-m-d');
$dateDepart  = isset($_GET['date_depart'])  ? preg_replace('/[^0-9\-]/','',$_GET['date_depart'])  : date('Y-m-d', strtotime('+1 day'));
$nbPersonnes = isset($_GET['nb_personnes']) ? max(1,min(4,(int)$_GET['nb_personnes'])) : 2;
$csrfToken   = generate_csrf_token();
?>
<section style="position:relative;padding:7rem 0 3rem;background:linear-gradient(to bottom,rgba(7,12,20,.85),rgba(7,12,20,.95)),url('assets/images/hero_hotel.jpg') center/cover;text-align:center;">
  <div class="container">
    <span class="section-subtitle">MEILLEUR TARIF GARANTI — BIF & USD</span>
    <h1 class="section-title">Réservation de Votre Séjour</h1>
    <p class="section-desc">Réservez au bord du Lac Tanganyika, Bujumbura, Burundi.</p>
  </div>
</section>

<section class="section-padding">
  <div class="container">

    <!-- Indicateurs d'étapes -->
    <div style="display:flex;justify-content:center;align-items:center;gap:1.5rem;margin-bottom:3rem;flex-wrap:wrap;">
      <?php foreach(['1'=>'Dates','2'=>'Suite','3'=>'Paiement'] as $n=>$l): ?>
        <div style="display:flex;align-items:center;gap:.5rem;color:var(--<?php echo $n==='1'?'accent-gold-primary':'text-muted';?>);font-weight:700;">
          <span style="width:32px;height:32px;border-radius:50%;background:<?php echo $n==='1'?'var(--accent-gold-primary)':'transparent';?>;border:1px solid var(--accent-gold-primary);color:<?php echo $n==='1'?'#070C14':'var(--accent-gold-primary)';?>;display:inline-flex;align-items:center;justify-content:center;"><?php echo $n;?></span>
          <span><?php echo $l;?></span>
        </div>
        <?php if($n<'3') echo '<span style="color:var(--border-gold);">⟶</span>'; ?>
      <?php endforeach;?>
    </div>

    <!-- ÉTAPE 1 : Dates -->
    <div id="bookingStep1" style="max-width:800px;margin:0 auto;background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:3rem;box-shadow:var(--shadow-lux);">
      <h2 style="font-size:1.5rem;margin-bottom:1.5rem;text-align:center;color:var(--text-light-primary);">1. Choisissez vos Dates</h2>
      <form id="bookingModuleForm">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;margin-bottom:1.5rem;">
          <div class="form-group">
            <label for="date_arrivee" class="form-label">📅 Date d'arrivée</label>
            <input type="date" id="date_arrivee" name="date_arrivee" value="<?php echo e($dateArrivee);?>" class="form-input" required>
          </div>
          <div class="form-group">
            <label for="date_depart" class="form-label">📅 Date de départ</label>
            <input type="date" id="date_depart" name="date_depart" value="<?php echo e($dateDepart);?>" class="form-input" required>
          </div>
          <div class="form-group">
            <label for="nb_personnes" class="form-label">👥 Adultes</label>
            <select id="nb_personnes" name="nb_personnes" class="form-select">
              <?php for($i=1;$i<=4;$i++): ?>
              <option value="<?php echo $i;?>" <?php echo $nbPersonnes===$i?'selected':'';?>><?php echo $i;?> personne<?php echo $i>1?'s':'';?></option>
              <?php endfor;?>
            </select>
          </div>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(7,12,20,.5);padding:1.25rem 1.5rem;border-radius:var(--radius-sm);border:1px solid var(--border-light);margin-bottom:2rem;">
          <span style="color:var(--text-muted);">Durée du séjour :</span>
          <span style="font-family:var(--font-heading);color:var(--accent-gold-primary);font-size:1.25rem;font-weight:700;"><span id="nightsCount">1</span> Nuit(s)</span>
        </div>
        <button type="submit" class="btn btn-gold" style="width:100%;padding:1.1rem;font-size:1rem;">
          Voir les Suites Disponibles ⟶
        </button>
      </form>
    </div>

    <!-- ÉTAPE 2 : Choix chambre -->
    <div id="bookingStep2" style="display:none;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
        <div><span class="section-subtitle">ÉTAPE 2 SUR 3</span><h2 style="font-size:2rem;color:var(--text-light-primary);">Sélectionnez Votre Suite</h2></div>
        <button type="button" class="btn btn-outline-gold"
          onclick="document.getElementById('bookingStep2').style.display='none';document.getElementById('bookingStep1').style.display='block';"
          style="font-size:.8rem;padding:.6rem 1.2rem;">⟵ Modifier</button>
      </div>
      <div id="availableRoomsContainer" class="grid-cards"></div>
    </div>

    <!-- ÉTAPE 3 : Formulaire client + paiement -->
    <div id="bookingStep3" style="display:none;max-width:900px;margin:0 auto;background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:3rem;box-shadow:var(--shadow-lux);">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;border-bottom:1px solid var(--border-light);padding-bottom:1rem;flex-wrap:wrap;gap:1rem;">
        <div><span class="section-subtitle">ÉTAPE 3 SUR 3</span><h2 style="font-size:1.75rem;color:var(--text-light-primary);">Paiement & Confirmation</h2></div>
        <div style="text-align:right;">
          <span style="font-size:.8rem;color:var(--text-muted);">TOTAL ESTIMÉ</span>
          <div id="totalAmountDisplay" data-price-bif="0" style="font-family:var(--font-heading);font-size:1.8rem;color:var(--accent-gold-primary);font-weight:700;">0 BIF</div>
        </div>
      </div>

      <form id="finalBookingForm">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken);?>">

        <!-- Résumé suite -->
        <div style="background:rgba(7,12,20,.5);border:1px solid var(--border-gold);padding:1.25rem 1.5rem;border-radius:var(--radius-sm);margin-bottom:2rem;">
          <div style="font-family:var(--font-heading);color:var(--accent-gold-primary);font-size:1.1rem;margin-bottom:.25rem;">
            Suite : <span id="summaryRoomName" style="color:var(--text-light-primary);"></span>
          </div>
          <p style="font-size:.85rem;color:var(--text-muted);">Annulation gratuite jusqu'à 48h avant l'arrivée. Confirmation par email.</p>
        </div>

        <!-- Services optionnels -->
        <h3 style="font-size:1.1rem;color:var(--accent-gold-primary);margin-bottom:1rem;font-family:var(--font-heading);">✨ Services Optionnels</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;margin-bottom:2.5rem;">
          <label style="background:rgba(7,12,20,.6);border:1px solid var(--border-light);padding:1rem;border-radius:var(--radius-sm);display:flex;align-items:center;gap:.75rem;cursor:pointer;">
            <input type="checkbox" name="services[]" value="3" data-price-bif="180000" class="addon-checkbox">
            <div>
              <div style="font-weight:600;color:var(--text-light-primary);font-size:.95rem;">🚌 Transfert Aéroport Ndadaye</div>
              <div style="font-size:.8rem;color:var(--accent-gold-primary);">+180 000 FBu / trajet</div>
            </div>
          </label>
          <label style="background:rgba(7,12,20,.6);border:1px solid var(--border-light);padding:1rem;border-radius:var(--radius-sm);display:flex;align-items:center;gap:.75rem;cursor:pointer;">
            <input type="checkbox" name="services[]" value="1" data-price-bif="840000" class="addon-checkbox">
            <div>
              <div style="font-weight:600;color:var(--text-light-primary);font-size:.95rem;">🧖 Rituel Spa Tanganyika</div>
              <div style="font-size:.8rem;color:var(--accent-gold-primary);">+840 000 FBu / séance</div>
            </div>
          </label>
          <label style="background:rgba(7,12,20,.6);border:1px solid var(--border-light);padding:1rem;border-radius:var(--radius-sm);display:flex;align-items:center;gap:.75rem;cursor:pointer;">
            <input type="checkbox" name="services[]" value="4" data-price-bif="1500000" class="addon-checkbox">
            <div>
              <div style="font-weight:600;color:var(--text-light-primary);font-size:.95rem;">⛵ Excursion Bateau Lac</div>
              <div style="font-size:.8rem;color:var(--accent-gold-primary);">+1 500 000 FBu / demi-journée</div>
            </div>
          </label>
        </div>

        <!-- Coordonnées -->
        <h3 style="font-size:1.1rem;color:var(--accent-gold-primary);margin-bottom:1rem;font-family:var(--font-heading);">👤 Vos Coordonnées</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.25rem;margin-bottom:2rem;">
          <div class="form-group">
            <label for="guest_first_name" class="form-label">Prénom *</label>
            <input type="text" id="guest_first_name" name="guest_first_name" required maxlength="80" class="form-input" placeholder="Jean-Marie">
          </div>
          <div class="form-group">
            <label for="guest_last_name" class="form-label">Nom *</label>
            <input type="text" id="guest_last_name" name="guest_last_name" required maxlength="80" class="form-input" placeholder="Ndayishimiye">
          </div>
          <div class="form-group">
            <label for="guest_email" class="form-label">E-mail *</label>
            <input type="email" id="guest_email" name="guest_email" required maxlength="150" class="form-input" placeholder="jm@example.bi">
          </div>
          <div class="form-group">
            <label for="guest_phone" class="form-label">Téléphone *</label>
            <input type="tel" id="guest_phone" name="guest_phone" required maxlength="20" class="form-input" placeholder="+257 79 00 00 00">
          </div>
        </div>
        <div class="form-group" style="margin-bottom:2rem;">
          <label for="special_requests" class="form-label">Demandes spéciales (optionnel)</label>
          <textarea id="special_requests" name="special_requests" rows="3" maxlength="1000" class="form-input" placeholder="Chambre haute, allergie, anniversaire…"></textarea>
        </div>

        <!-- Mode de paiement -->
        <h3 style="font-size:1.1rem;color:var(--accent-gold-primary);margin-bottom:1rem;font-family:var(--font-heading);">💳 Mode de Paiement</h3>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:.75rem;margin-bottom:2rem;">
          <?php
          $pmodes = [
            'cash_bif'   => ['💵','Espèces BIF','À l\'arrivée'],
            'cash_usd'   => ['💵','Espèces USD','À l\'arrivée'],
            'lumicash'   => ['📱','Lumicash','Lumitel Mobile Money'],
            'ecocash'    => ['📱','EcoCash','Econet Leo Burundi'],
            'bank_local' => ['🏦','Virement Bancaire','Banques du Burundi'],
          ];
          $first = true;
          foreach($pmodes as $v => [$ico,$label,$sub]):
          ?>
          <label style="background:rgba(7,12,20,.6);border:1px solid var(--border-light);padding:.9rem 1rem;border-radius:var(--radius-sm);display:flex;align-items:center;gap:.6rem;cursor:pointer;">
            <input type="radio" name="mode_paiement" value="<?php echo e($v);?>" <?php echo $first?'checked':'';?>>
            <div>
              <div style="font-weight:600;color:var(--text-light-primary);font-size:.9rem;"><?php echo $ico.' '.e($label);?></div>
              <div style="font-size:.75rem;color:var(--text-muted);"><?php echo e($sub);?></div>
            </div>
          </label>
          <?php $first=false; endforeach;?>
        </div>

        <!-- Numéro mobile money (conditionnel) -->
        <div id="fieldMobileMoney" style="display:none;margin-bottom:1.5rem;">
          <label for="mobile_number" class="form-label">Numéro Mobile Money *</label>
          <input type="tel" id="mobile_number" name="mobile_number" maxlength="20" class="form-input" placeholder="+257 79 00 00 00">
        </div>

        <!-- Code promo -->
        <div style="display:flex;gap:.75rem;margin-bottom:2rem;align-items:flex-end;">
          <div class="form-group" style="flex:1;margin:0;">
            <label for="offer_code" class="form-label">Code Promo (optionnel)</label>
            <input type="text" id="offer_code" name="offer_code" maxlength="30" class="form-input" placeholder="ex: TANGANYIKA20" style="text-transform:uppercase;">
          </div>
        </div>

        <button type="submit" class="btn btn-gold" style="width:100%;padding:1.2rem;font-size:1.05rem;">
          ✅ Confirmer ma Réservation
        </button>
        <p style="font-size:.75rem;color:var(--text-muted);text-align:center;margin-top:1rem;">
          En confirmant, vous acceptez nos CGV. Paiement sécurisé. Aucune donnée carte stockée.
        </p>
      </form>
    </div>

    <!-- CONFIRMATION -->
    <div id="bookingConfirmationScreen" style="display:none;max-width:700px;margin:0 auto;text-align:center;background:var(--bg-dark-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);padding:3rem;box-shadow:var(--shadow-lux);">
      <div style="font-size:3rem;margin-bottom:1rem;color:var(--accent-gold-primary);">✅</div>
      <h2 style="color:var(--text-light-primary);font-size:1.75rem;margin-bottom:.5rem;">Réservation Enregistrée !</h2>
      <p style="color:var(--text-muted);margin-bottom:1.5rem;">Un email de confirmation vous a été envoyé.</p>
      <div style="background:rgba(7,12,20,.6);border:1px solid var(--border-gold);border-radius:var(--radius-sm);padding:1.5rem;margin-bottom:2rem;text-align:left;">
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.06);">
          <span style="color:var(--text-muted);font-size:.85rem;">Référence</span>
          <span id="confRefCode" style="color:var(--accent-gold-primary);font-family:monospace;font-weight:700;"></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.06);">
          <span style="color:var(--text-muted);font-size:.85rem;">Suite</span>
          <span id="confRoomName" style="color:var(--text-light-primary);font-weight:600;"></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.06);">
          <span style="color:var(--text-muted);font-size:.85rem;">Dates</span>
          <span id="confDates" style="color:var(--text-light-primary);"></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;">
          <span style="color:var(--text-muted);font-size:.85rem;">Montant</span>
          <span id="confTotal" style="color:var(--accent-gold-primary);font-weight:700;font-size:1.1rem;"></span>
        </div>
      </div>
      <a href="<?php echo isset($_SESSION['customer_auth']) ? '/public/mes-reservations.php' : '/public/connexion.php'; ?>" class="btn btn-gold" style="padding:.85rem 2rem;">
        <?php echo isset($_SESSION['customer_auth']) ? 'Voir mes Réservations' : 'Créer un Compte Client'; ?>
      </a>
    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
