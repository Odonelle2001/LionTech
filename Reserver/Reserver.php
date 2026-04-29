<?php
/* ============================================================
   reserver.php — LionRDV Booking Page
   URL: localhost/LionRDV/reserver.php?slug=nora-beauty
   - Lit les données du business depuis data/[slug].json
   - Sauvegarde les RDV dans data/[slug]-rdv.json
   - Identification client : WhatsApp + Nom (pas de compte)
   - Annulation possible jusqu'à 2h avant le RDV
   - localStorage côté client pour retrouver le RDV sans internet
============================================================ */

require_once __DIR__ . '/../config.php';
$data_dir = DATA_DIR;
$slug     = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));

if (empty($slug)) {
  http_response_code(400);
  die('<h1 style="font-family:sans-serif;text-align:center;margin-top:80px;">URL invalide</h1>');
}

/* ── LOAD BUSINESS DATA ──────────────────────────────────── */
$biz_file = $data_dir . '/' . $slug . '.json';
if (!file_exists($biz_file)) {
  http_response_code(404);
  die('<div style="font-family:sans-serif;text-align:center;margin-top:80px;">
    <div style="font-size:48px;">🔍</div>
    <h1>Business introuvable</h1>
    <p>Le lien est invalide ou le business n\'existe pas encore.</p>
  </div>');
}
$b = json_decode(file_get_contents($biz_file), true);
if (!$b) die('<h1 style="font-family:sans-serif;text-align:center;margin-top:80px;">Erreur de lecture</h1>');

/* ── DEFAULTS ────────────────────────────────────────────── */
$b = array_merge([
  'name'           => 'Business',
  'slug'           => $slug,
  'initials'       => 'B',
  'type'           => '',
  'whatsapp'       => '',
  'theme_color'    => '#C9A84C',
  'theme_bg'       => '#FFF9EE',
  'button_color'   => '#C9A84C',
  'primary_color'  => '#C9A84C',
  'secondary_color'=> '#0A0A0A',
  'text_color'     => '#222222',
  'background_color'=>'#ffffff',
  'border_color'   => '#e5e7eb',
  'language'       => 'fr',
  'show_prices'    => true,
  'services'       => [],
  'availability'   => [],
  'booking_style'  => 'individual',
  'employee_count' => 1,
], $b);

/* ── LOAD RDV FILE ───────────────────────────────────────── */
$rdv_file = $data_dir . '/' . $slug . '-rdv.json';
$all_rdv  = [];
if (file_exists($rdv_file)) {
  $all_rdv = json_decode(file_get_contents($rdv_file), true) ?? [];
}

/* ── HELPERS ─────────────────────────────────────────────── */
function save_rdv_file($file, $rdv_list) {
  file_put_contents($file, json_encode(array_values($rdv_list), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function hex_to_rgba($hex, $alpha = 1) {
  $hex = ltrim($hex, '#');
  if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
  [$r,$g,$bv] = array_map('hexdec', str_split($hex, 2));
  return "rgba($r,$g,$bv,$alpha)";
}

/* deadline = RDV datetime minus 2 hours */
function cancellation_deadline($rdv_date, $rdv_time) {
  $dt = DateTime::createFromFormat('Y-m-d H:i', $rdv_date . ' ' . $rdv_time);
  if (!$dt) return null;
  $dt->modify('-2 hours');
  return $dt;
}

function is_cancellable($rdv_date, $rdv_time) {
  $deadline = cancellation_deadline($rdv_date, $rdv_time);
  if (!$deadline) return false;
  return new DateTime() < $deadline;
}

/* ── HANDLE POST ACTIONS ─────────────────────────────────── */
$response = ['ok' => false, 'msg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  /* ── SAVE NEW RDV ── */
  if ($action === 'book') {
    $prenom  = trim($_POST['prenom']   ?? '');
    $nom     = trim($_POST['nom']      ?? '');
    $wa      = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
    $date    = trim($_POST['rdv_date'] ?? '');
    $time    = trim($_POST['rdv_time'] ?? '');
    $services= $_POST['services']      ?? [];

    if (!$prenom || !$nom || !$wa || !$date || !$time || empty($services)) {
      http_response_code(400);
      echo json_encode(['ok'=>false,'msg'=>'Tous les champs sont requis.']);
      exit;
    }

    /* Check slot availability — count existing RDV for this slot */
    $employees  = max(1, (int)($b['employee_count'] ?? 1));
    $slot_count = 0;
    foreach ($all_rdv as $r) {
      if ($r['date'] === $date && $r['time'] === $time && $r['status'] === 'confirmed') {
        $slot_count++;
      }
    }
    if ($slot_count >= $employees) {
      echo json_encode(['ok'=>false,'msg'=>'Ce créneau est complet. Choisissez un autre.']);
      exit;
    }

    /* Build RDV record */
    $deadline = cancellation_deadline($date, $time);
    $rdv = [
      'id'            => uniqid('rdv_'),
      'slug'          => $slug,
      'prenom'        => $prenom,
      'nom'           => $nom,
      'whatsapp'      => $wa,
      'date'          => $date,
      'time'          => $time,
      'services'      => $services,
      'status'        => 'confirmed',
      'cancel_before' => $deadline ? $deadline->format('Y-m-d H:i') : '',
      'created_at'    => date('Y-m-d H:i:s'),
    ];

    $all_rdv[] = $rdv;
    save_rdv_file($rdv_file, $all_rdv);

    echo json_encode(['ok'=>true, 'rdv'=>$rdv]);
    exit;
  }

  /* ── LOOKUP RDV ── */
  if ($action === 'lookup') {
    $wa  = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
    $nom = strtolower(trim($_POST['nom'] ?? ''));

    $found = [];
    foreach ($all_rdv as $r) {
      if (preg_replace('/\D/','',$r['whatsapp']) === $wa &&
          strtolower($r['nom']) === $nom &&
          $r['status'] === 'confirmed') {
        $r['cancellable'] = is_cancellable($r['date'], $r['time']);
        $dl = cancellation_deadline($r['date'], $r['time']);
        $r['cancel_before_fmt'] = $dl ? $dl->format('d/m/Y à H\hi') : '';
        $found[] = $r;
      }
    }

    echo json_encode(['ok'=>true, 'rdvs'=>$found]);
    exit;
  }

  /* ── CANCEL RDV ── */
  if ($action === 'cancel') {
    $rdv_id = trim($_POST['rdv_id'] ?? '');
    $wa     = preg_replace('/\D/', '', $_POST['whatsapp'] ?? '');
    $nom    = strtolower(trim($_POST['nom'] ?? ''));

    $cancelled = false;
    foreach ($all_rdv as &$r) {
      if ($r['id'] === $rdv_id &&
          preg_replace('/\D/','',$r['whatsapp']) === $wa &&
          strtolower($r['nom']) === $nom) {
        if (!is_cancellable($r['date'], $r['time'])) {
          echo json_encode(['ok'=>false,'msg'=>'Délai d\'annulation dépassé.']);
          exit;
        }
        $r['status']      = 'cancelled';
        $r['cancelled_at']= date('Y-m-d H:i:s');
        $cancelled = true;
        break;
      }
    }
    unset($r);

    if ($cancelled) {
      save_rdv_file($rdv_file, $all_rdv);
      echo json_encode(['ok'=>true]);
    } else {
      echo json_encode(['ok'=>false,'msg'=>'RDV introuvable.']);
    }
    exit;
  }

  /* ── GET TAKEN SLOTS FOR A DATE ── */
  if ($action === 'get_slots') {
    $date      = trim($_POST['date'] ?? '');
    $employees = max(1, (int)($b['employee_count'] ?? 1));
    $taken     = [];

    foreach ($all_rdv as $r) {
      if ($r['date'] === $date && $r['status'] === 'confirmed') {
        $taken[$r['time']] = ($taken[$r['time']] ?? 0) + 1;
      }
    }

    $full = [];
    foreach ($taken as $time => $count) {
      if ($count >= $employees) $full[] = $time;
    }

    echo json_encode(['ok'=>true,'taken'=>$full]);
    exit;
  }
}

/* ── AVAILABILITY: open days + hours ────────────────────── */
$avail_map = [];
foreach ($b['availability'] as $day) {
  $avail_map[$day['day_en']] = $day;
}

/* Generate time slots from open/close with 45min intervals */
function generate_slots($start, $end, $interval_min = 45) {
  if (!$start || !$end) return [];
  $slots = [];
  $cur   = strtotime('2000-01-01 ' . $start);
  $end_t = strtotime('2000-01-01 ' . $end);
  while ($cur < $end_t) {
    $slots[] = date('H:i', $cur);
    $cur += $interval_min * 60;
  }
  return $slots;
}

/* Pass data to JS */
$js_biz = json_encode([
  'slug'         => $b['slug'],
  'name'         => $b['name'],
  'whatsapp'     => $b['whatsapp'],
  'theme_color'  => $b['theme_color'],
  'button_color' => $b['button_color'],
  'services'     => $b['services'],
  'availability' => $b['availability'],
  'show_prices'  => $b['show_prices'],
  'language'     => $b['language'],
  'booking_style'=> $b['booking_style'],
  'employees'    => max(1, (int)($b['employee_count'] ?? 1)),
]);

$default_lang = $b['language'] === 'en' ? 'en' : 'fr';
?>
<!DOCTYPE html>
<html lang="<?= $default_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réserver — <?= htmlspecialchars($b['name']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="Reserver.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/responsive.css">
  <style>
    :root {
      --brand:      <?= htmlspecialchars($b['theme_color']) ?>;
      --brand-bg:   <?= hex_to_rgba($b['theme_color'], 0.09) ?>;
      --brand-light:<?= htmlspecialchars($b['theme_bg'] ?? '#FFF9EE') ?>;
      --btn:        <?= htmlspecialchars($b['button_color']) ?>;
      --page-bg:    <?= htmlspecialchars($b['background_color']) ?>;
      --text:       <?= htmlspecialchars($b['text_color']) ?>;
      --border:     <?= htmlspecialchars($b['border_color']) ?>;
    }
  </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav class="rsv-nav">
  <div class="rsv-nav-inner">
    <div class="rsv-nav-left">
      <?php if (!empty($b['logo']) && media_exists($b['logo'])): ?>
        <img src="<?= BASE_URL ?>/<?= htmlspecialchars(ltrim($b['logo'], '/')) ?>" class="rsv-nav-logo" alt="logo">
      <?php else: ?>
        <div class="rsv-nav-init" style="background:var(--brand);">
          <?= htmlspecialchars($b['initials']) ?>
        </div>
      <?php endif; ?>
      <div>
        <div class="rsv-nav-name"><?= htmlspecialchars($b['name']) ?></div>
        <div class="rsv-nav-sub"
             data-fr="Prendre un rendez-vous"
             data-en="Book an appointment">Prendre un rendez-vous</div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/Utilisateur%20du%20client/Utulisateur.php?slug=<?= urlencode($slug) ?>" class="rsv-back-btn">
      <i class="fa-solid fa-arrow-left"></i>
      <span data-fr="Retour" data-en="Back">Retour</span>
    </a>
  </div>
</nav>

<!-- ══ MAIN ══ -->
<div class="rsv-main" id="rsv-main">

  <!-- ── SECTION 0: INFO BANNER + LOOKUP ── -->
  <div class="rsv-section">

    <div class="rsv-info-banner">
      <div class="rsv-info-title">
        <i class="fa-solid fa-circle-info"></i>
        <span data-fr="Comment ça marche" data-en="How it works">Comment ça marche</span>
      </div>
      <p data-fr="Remplissez le formulaire et confirmez votre RDV. Pour <strong>retrouver ou annuler</strong> votre rendez-vous, entrez simplement votre <strong>numéro WhatsApp et nom</strong>. <strong>Annulation gratuite jusqu'à 2h avant votre RDV.</strong>"
         data-en="Fill in the form and confirm your booking. To <strong>find or cancel</strong> your appointment, just enter your <strong>WhatsApp number and name</strong>. <strong>Free cancellation up to 2 hours before your appointment.</strong>">
        Remplissez le formulaire et confirmez votre RDV. Pour <strong>retrouver ou annuler</strong> votre rendez-vous, entrez simplement votre <strong>numéro WhatsApp et nom</strong>. <strong>Annulation gratuite jusqu'à 2h avant votre RDV.</strong>
      </p>
    </div>

    <div class="rsv-divider">
      <span data-fr="Vous avez déjà un RDV ?" data-en="Already have a booking?">Vous avez déjà un RDV ?</span>
    </div>

    <div class="rsv-lookup-box">
      <div class="rsv-lookup-title"
           data-fr="Retrouver mon RDV"
           data-en="Find my booking">Retrouver mon RDV</div>
      <div class="rsv-lookup-sub"
           data-fr="Entrez votre WhatsApp et nom pour retrouver ou annuler"
           data-en="Enter your WhatsApp and name to find or cancel">
        Entrez votre WhatsApp et nom pour retrouver ou annuler
      </div>
      <div class="rsv-wa-input-wrap">
        <span>+237</span>
        <input type="tel" id="lookup-wa" placeholder="6XX XXX XXX" maxlength="9">
      </div>
      <input type="text" class="rsv-input" id="lookup-nom"
             placeholder="Votre nom" data-ph-fr="Votre nom" data-ph-en="Your name">
      <button class="rsv-lookup-btn" onclick="doLookup()">
        <i class="fa-solid fa-magnifying-glass"></i>
        <span data-fr="Rechercher mon RDV" data-en="Find my booking">Rechercher mon RDV</span>
      </button>
      <div id="lookup-result" style="display:none;margin-top:12px;"></div>
    </div>

    <div class="rsv-divider">
      <span data-fr="ou faites un nouveau RDV" data-en="or make a new booking">ou faites un nouveau RDV</span>
    </div>
  </div>

  <!-- ── SECTION 1: SERVICES ── -->
  <div class="rsv-section">
    <div class="rsv-section-title">
      <i class="fa-solid fa-list-ul" style="color:var(--brand);font-size:13px;"></i>
      <span data-fr="Choisissez un service" data-en="Choose a service">Choisissez un service</span>
      <span class="rsv-badge" id="svc-badge" style="display:none;"></span>
    </div>

    <?php if (!empty($b['services'])): ?>
    <div class="rsv-svc-grid" id="svc-grid">
      <?php foreach ($b['services'] as $svc): ?>
      <div class="rsv-svc-item"
           data-name="<?= htmlspecialchars($svc['name']) ?>"
           data-price="<?= (int)($svc['price'] ?? 0) ?>"
           onclick="toggleSvc(this)">
        <div class="rsv-svc-cb">
          <i class="fa-solid fa-check"></i>
        </div>
        <div class="rsv-svc-info">
          <div class="rsv-svc-name"><?= htmlspecialchars($svc['name']) ?></div>
          <div class="rsv-svc-dur"><?= htmlspecialchars($svc['duration'] ?? '') ?></div>
        </div>
        <?php if ($b['show_prices'] && isset($svc['price'])): ?>
        <div class="rsv-svc-price"><?= number_format($svc['price']) ?> F</div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="rsv-empty-msg"
         data-fr="Aucun service configuré pour ce business."
         data-en="No services configured for this business.">
      Aucun service configuré pour ce business.
    </div>
    <?php endif; ?>
  </div>

  <!-- ── SECTION 2: CALENDAR ── -->
  <div class="rsv-section">
    <div class="rsv-section-title">
      <i class="fa-regular fa-calendar" style="color:var(--brand);font-size:13px;"></i>
      <span data-fr="Choisissez une date" data-en="Choose a date">Choisissez une date</span>
      <span class="rsv-badge" id="date-badge" style="display:none;"></span>
    </div>

    <div class="rsv-cal-header">
      <button class="rsv-cal-nav" onclick="changeMonth(-1)">&#8249;</button>
      <div class="rsv-cal-month" id="cal-month-lbl"></div>
      <button class="rsv-cal-nav" onclick="changeMonth(1)">&#8250;</button>
    </div>
    <div class="rsv-cal-grid" id="cal-grid">
      <div class="rsv-cal-dow" data-fr="Lun" data-en="Mon">Lun</div>
      <div class="rsv-cal-dow" data-fr="Mar" data-en="Tue">Mar</div>
      <div class="rsv-cal-dow" data-fr="Mer" data-en="Wed">Mer</div>
      <div class="rsv-cal-dow" data-fr="Jeu" data-en="Thu">Jeu</div>
      <div class="rsv-cal-dow" data-fr="Ven" data-en="Fri">Ven</div>
      <div class="rsv-cal-dow" data-fr="Sam" data-en="Sat">Sam</div>
      <div class="rsv-cal-dow" data-fr="Dim" data-en="Sun">Dim</div>
    </div>
  </div>

  <!-- ── SECTION 3: TIME SLOTS ── -->
  <div class="rsv-section" id="slots-section">
    <div class="rsv-section-title">
      <i class="fa-regular fa-clock" style="color:var(--brand);font-size:13px;"></i>
      <span data-fr="Créneaux disponibles" data-en="Available slots">Créneaux disponibles</span>
      <span class="rsv-badge" id="slot-day-lbl" style="display:none;"></span>
    </div>
    <div class="rsv-no-slots" id="no-slots-msg"
         data-fr="Sélectionnez un jour pour voir les créneaux"
         data-en="Select a day to see available slots">
      Sélectionnez un jour pour voir les créneaux
    </div>
    <div class="rsv-slots-grid" id="slots-grid" style="display:none;"></div>
    <div class="rsv-slots-legend">
      <span><span class="rsv-leg-dot taken"></span><span data-fr="Pris" data-en="Taken">Pris</span></span>
      <span><span class="rsv-leg-dot avail"></span><span data-fr="Disponible" data-en="Available">Disponible</span></span>
      <span><span class="rsv-leg-dot selected"></span><span data-fr="Sélectionné" data-en="Selected">Sélectionné</span></span>
    </div>
  </div>

  <!-- ── SECTION 4: CLIENT INFO ── -->
  <div class="rsv-section">
    <div class="rsv-section-title">
      <i class="fa-regular fa-user" style="color:var(--brand);font-size:13px;"></i>
      <span data-fr="Vos informations" data-en="Your information">Vos informations</span>
    </div>

    <div class="rsv-field">
      <label data-fr="Prénom *" data-en="First name *">Prénom *</label>
      <input type="text" id="inp-prenom" class="rsv-input"
             placeholder="Ex: Marie" data-ph-fr="Ex: Marie" data-ph-en="Ex: Marie">
    </div>
    <div class="rsv-field">
      <label data-fr="Nom *" data-en="Last name *">Nom *</label>
      <input type="text" id="inp-nom" class="rsv-input"
             placeholder="Ex: Nguele" data-ph-fr="Ex: Nguele" data-ph-en="Ex: Nguele">
    </div>
    <div class="rsv-field">
      <label data-fr="Numéro WhatsApp *" data-en="WhatsApp number *">Numéro WhatsApp *</label>
      <div class="rsv-wa-input-wrap">
        <span>+237</span>
        <input type="tel" id="inp-wa" placeholder="6XX XXX XXX" maxlength="9">
      </div>
    </div>

    <div class="rsv-wa-note">
      <i class="fa-brands fa-whatsapp" style="color:#25D366;font-size:13px;"></i>
      <span data-fr="Votre WhatsApp + nom vous permettront de retrouver et annuler votre RDV. Pas besoin de les noter."
            data-en="Your WhatsApp + name will allow you to find and cancel your booking. No need to write them down.">
        Votre WhatsApp + nom vous permettront de retrouver et annuler votre RDV. Pas besoin de les noter.
      </span>
    </div>
  </div>

  <!-- ── SECTION 5: SUMMARY + CONFIRM ── -->
  <div class="rsv-section">
    <div class="rsv-summary" id="rdv-summary">
      <div class="rsv-sum-row"><span data-fr="Service" data-en="Service">Service</span><span id="s-svc">—</span></div>
      <div class="rsv-sum-row"><span data-fr="Date" data-en="Date">Date</span><span id="s-date">—</span></div>
      <div class="rsv-sum-row"><span data-fr="Heure" data-en="Time">Heure</span><span id="s-heure">—</span></div>
      <div class="rsv-sum-row total"><span>Total</span><span id="s-total">—</span></div>
    </div>
    <button class="rsv-confirm-btn" id="confirm-btn"
            style="background:var(--btn);"
            onclick="submitRdv()">
      <i class="fa-solid fa-check"></i>
      <span data-fr="Confirmer mon RDV" data-en="Confirm my booking">Confirmer mon RDV</span>
    </button>
    <div id="form-error" class="rsv-error" style="display:none;"></div>
  </div>

</div><!-- /rsv-main -->

<!-- ══ CONFIRMATION SCREEN ══ -->
<div class="rsv-confirm-screen" id="confirm-screen" style="display:none;">

  <div class="rsv-success-icon">
    <i class="fa-solid fa-check"></i>
  </div>
  <div class="rsv-confirm-title"
       data-fr="RDV confirmé !"
       data-en="Booking confirmed!">RDV confirmé !</div>
  <div class="rsv-confirm-sub"
       data-fr="Pour retrouver ou annuler, entrez votre WhatsApp + nom"
       data-en="To find or cancel, enter your WhatsApp + name">
    Pour retrouver ou annuler, entrez votre WhatsApp + nom
  </div>

  <div class="rsv-id-box">
    <div class="rsv-id-label"
         data-fr="Votre identifiant RDV"
         data-en="Your booking ID">Votre identifiant RDV</div>
    <div class="rsv-id-wa" id="conf-wa"></div>
    <div class="rsv-id-nom" id="conf-nom"></div>
    <div class="rsv-id-note"
         data-fr="Retournez sur cette page et entrez ce numéro + nom pour retrouver ou annuler votre RDV"
         data-en="Return to this page and enter this number + name to find or cancel your booking">
      Retournez sur cette page et entrez ce numéro + nom pour retrouver ou annuler votre RDV
    </div>
  </div>

  <div class="rsv-deadline-note" id="conf-deadline"></div>

  <div class="rsv-summary" id="conf-summary"></div>

  <button class="rsv-wa-confirm-btn" id="conf-wa-btn">
    <i class="fa-brands fa-whatsapp"></i>
    <span data-fr="Envoyer la confirmation sur WhatsApp"
          data-en="Send confirmation on WhatsApp">Envoyer la confirmation sur WhatsApp</span>
  </button>

  <button class="rsv-ghost-btn" onclick="resetForm()"
          data-fr="Prendre un autre RDV"
          data-en="Book another appointment">Prendre un autre RDV</button>

</div>

<!-- ══ FOOTER ══ -->
<footer class="rsv-footer">
  <span data-fr="Propulsé par" data-en="Powered by">Propulsé par</span>
  <strong>LionTech</strong> · LionRDV
</footer>

<!-- ══ DATA TO JS ══ -->
<script>
var BIZ = <?= $js_biz ?>;
var SLUG = <?= json_encode($slug) ?>;
var DEFAULT_LANG = <?= json_encode($default_lang) ?>;
</script>
<script src="Reserver.js"></script>
</body>
</html>