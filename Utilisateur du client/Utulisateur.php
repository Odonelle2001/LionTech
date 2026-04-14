<?php
/* ============================================================
   Utulisateur.php — LionRDV Customer Page
   URL: localhost/LionRDV/Utulisateur.php?slug=nora-beauty
   Lit les données depuis /LionRDV/data/[slug].json
   Ce fichier est créé par AjouterBussiness.php
============================================================ */

$slug = trim($_GET['slug'] ?? '');

/* Sécuriser le slug — seulement lettres, chiffres, tirets */
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

if (empty($slug)) {
  http_response_code(400);
  die('<h1 style="font-family:sans-serif;text-align:center;margin-top:80px;">Slug manquant — URL invalide</h1>');
}

/* ── LIRE LE FICHIER JSON ─────────────────────────────────
   Créé par AjouterBussiness.php dans /LionRDV/data/
──────────────────────────────────────────────────────────── */
$data_dir  = dirname(__DIR__) . '/data';
$json_file = $data_dir . '/' . $slug . '.json';

if (!file_exists($json_file)) {
  http_response_code(404);
  die('
    <div style="font-family:sans-serif;text-align:center;margin-top:80px;color:#7A7570;">
      <div style="font-size:48px;margin-bottom:16px;">🔍</div>
      <h1 style="color:#0A0A0A;margin-bottom:8px;">Business introuvable</h1>
      <p>Aucun business trouvé pour <strong>' . htmlspecialchars($slug) . '</strong></p>
      <p style="font-size:13px;margin-top:8px;">Créez ce business depuis le <a href="/LionRDV/RSVAdmin.php" style="color:#C9A84C;">dashboard admin</a>.</p>
    </div>
  ');
}

$b = json_decode(file_get_contents($json_file), true);

if (!$b) {
  http_response_code(500);
  die('<h1 style="font-family:sans-serif;text-align:center;margin-top:80px;">Erreur de lecture du fichier business</h1>');
}

/* ── VALEURS PAR DÉFAUT (sécurité si champ manquant) ─────── */
$b = array_merge([
  'name'             => 'Business',
  'slug'             => $slug,
  'initials'         => 'B',
  'type'             => '',
  'description'      => '',
  'city'             => '',
  'whatsapp'         => '',
  'rating'           => 0,
  'review_count'     => 0,
  'logo'             => '',
  'cover_photo'      => '',
  'gallery'          => [],
  'theme_color'      => '#C9A84C',
  'theme_bg'         => '#FFF9EE',
  'navbar_style'     => 'light',
  'footer_style'     => 'minimal',
  'show_biz_logo'    => true,
  'show_lt_logo'     => true,
  'lt_footer_only'   => false,
  'primary_color'    => '#C9A84C',
  'secondary_color'  => '#0A0A0A',
  'button_color'     => '#C9A84C',
  'text_color'       => '#222222',
  'background_color' => '#ffffff',
  'border_color'     => '#e5e7eb',
  'language'         => 'fr',
  'show_prices'      => true,
  'services'         => [],
  'availability'     => [
    ['day'=>'Lundi',    'day_en'=>'Monday',    'open'=>true,  'start'=>'08:00','end'=>'18:00'],
    ['day'=>'Mardi',    'day_en'=>'Tuesday',   'open'=>true,  'start'=>'08:00','end'=>'18:00'],
    ['day'=>'Mercredi', 'day_en'=>'Wednesday', 'open'=>true,  'start'=>'08:00','end'=>'18:00'],
    ['day'=>'Jeudi',    'day_en'=>'Thursday',  'open'=>true,  'start'=>'08:00','end'=>'18:00'],
    ['day'=>'Vendredi', 'day_en'=>'Friday',    'open'=>true,  'start'=>'08:00','end'=>'19:00'],
    ['day'=>'Samedi',   'day_en'=>'Saturday',  'open'=>true,  'start'=>'09:00','end'=>'17:00'],
    ['day'=>'Dimanche', 'day_en'=>'Sunday',    'open'=>false, 'start'=>'',     'end'=>''],
  ],
  'plan' => 'basic',
], $b);

/* ── HELPERS ─────────────────────────────────────────────── */
function hex_to_rgba($hex, $alpha = 1) {
  $hex = ltrim($hex, '#');
  if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
  [$r,$g,$bv] = array_map('hexdec', str_split($hex, 2));
  return "rgba($r,$g,$bv,$alpha)";
}

function is_open_now($availability) {
  $day_map = ['Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];
  $today   = (int)date('w');
  foreach ($availability as $slot) {
    $slot_day = $day_map[$slot['day_en']] ?? -1;
    if ($slot_day === $today && $slot['open']) {
      $now   = (int)date('Hi');
      $start = (int)str_replace(':', '', $slot['start']);
      $end   = (int)str_replace(':', '', $slot['end']);
      return $now >= $start && $now < $end;
    }
  }
  return false;
}

function closing_time($availability) {
  $day_map = ['Sunday'=>0,'Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6];
  $today   = (int)date('w');
  foreach ($availability as $slot) {
    $slot_day = $day_map[$slot['day_en']] ?? -1;
    if ($slot_day === $today && $slot['open']) return $slot['end'];
  }
  return null;
}

$is_open      = is_open_now($b['availability']);
$closes_at    = closing_time($b['availability']);
$default_lang = $b['language'] === 'en' ? 'en' : 'fr';

/* ── NAVBAR & FOOTER styles ──────────────────────────────── */
$navbar_bg     = $b['navbar_style'] === 'dark'        ? $b['secondary_color'] : '#ffffff';
$navbar_color  = $b['navbar_style'] === 'dark'        ? '#ffffff'             : $b['text_color'];
$navbar_border = $b['navbar_style'] === 'transparent' ? 'transparent'         : $b['border_color'];
$footer_bg     = $b['footer_style'] === 'dark'        ? $b['secondary_color'] : '#ffffff';
$footer_color  = $b['footer_style'] === 'dark'        ? 'rgba(255,255,255,0.5)' : '#7A7570';

/* Logo path — prepend ../ to get from LionRDV root */
$logo_src  = !empty($b['logo'])        ? '../' . $b['logo']        : '';
$cover_src = !empty($b['cover_photo']) ? '../' . $b['cover_photo'] : '';
?>
<!DOCTYPE html>
<html lang="<?= $default_lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($b['name']) ?> — LionRDV</title>
  <meta name="description" content="<?= htmlspecialchars($b['description']) ?>">
  <link rel="stylesheet" href="Utulisateur.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- ── CSS variables — driven 100% by admin settings ── -->
  <style>
    :root {
      --brand:       <?= htmlspecialchars($b['theme_color']) ?>;
      --brand-bg:    <?= htmlspecialchars($b['theme_bg']) ?>;
      --brand-rgba:  <?= hex_to_rgba($b['theme_color'], 0.10) ?>;
      --btn-color:   <?= htmlspecialchars($b['button_color']) ?>;
      --primary:     <?= htmlspecialchars($b['primary_color']) ?>;
      --secondary:   <?= htmlspecialchars($b['secondary_color']) ?>;
      --text-color:  <?= htmlspecialchars($b['text_color']) ?>;
      --page-bg:     <?= htmlspecialchars($b['background_color']) ?>;
      --border-col:  <?= htmlspecialchars($b['border_color']) ?>;
      --navbar-bg:   <?= $navbar_bg ?>;
      --navbar-text: <?= $navbar_color ?>;
      --navbar-border: <?= $navbar_border ?>;
      --footer-bg:   <?= $footer_bg ?>;
      --footer-text: <?= $footer_color ?>;
    }
  </style>
</head>
<body style="background:<?= htmlspecialchars($b['background_color']) ?>;">

<!-- ══════════════════════════════════════
     NAVBAR
══════════════════════════════════════ -->
<nav class="cl-navbar" style="background:var(--navbar-bg);border-bottom-color:var(--navbar-border);">
  <div class="cl-navbar-inner">

    <div class="cl-nav-left">
      <?php if ($b['show_biz_logo']): ?>
        <?php if (!empty($logo_src) && file_exists(dirname(__DIR__) . '/' . $b['logo'])): ?>
          <img src="<?= htmlspecialchars($logo_src) ?>"
               alt="<?= htmlspecialchars($b['name']) ?>"
               class="cl-nav-logo-img">
        <?php else: ?>
          <div class="cl-nav-initials" style="background:var(--brand);">
            <?= htmlspecialchars($b['initials']) ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="cl-nav-biz-info">
        <div class="cl-nav-biz-name" style="color:var(--navbar-text);">
          <?= htmlspecialchars($b['name']) ?>
        </div>
        <div class="cl-nav-biz-type"><?= htmlspecialchars($b['type']) ?></div>
      </div>
    </div>

    <div class="cl-nav-right">
      <?php if ($b['language'] === 'bilingual'): ?>
      <div class="cl-lang-toggle">
        <button class="cl-lang-btn <?= $default_lang === 'fr' ? 'active' : '' ?>"
                onclick="setLang('fr',this)">FR</button>
        <button class="cl-lang-btn <?= $default_lang === 'en' ? 'active' : '' ?>"
                onclick="setLang('en',this)">EN</button>
      </div>
      <?php endif; ?>

      <?php if ($b['show_lt_logo'] && !$b['lt_footer_only']): ?>
      <div class="cl-lt-badge">
        <div class="cl-lt-mark">LT</div>
        <span class="cl-lt-text">LionRDV</span>
      </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ══════════════════════════════════════
     HERO
══════════════════════════════════════ -->
<div class="cl-hero">
  <?php if (!empty($cover_src) && file_exists(dirname(__DIR__) . '/' . $b['cover_photo'])): ?>
    <img src="<?= htmlspecialchars($cover_src) ?>"
         alt="<?= htmlspecialchars($b['name']) ?>"
         class="cl-hero-img">
  <?php else: ?>
    <div class="cl-hero-placeholder"
         style="background:var(--brand);">
      <i class="fa-regular fa-image cl-hero-ph-icon"></i>
      <span class="cl-hero-ph-text"
            data-fr="Photo de couverture"
            data-en="Cover photo">Photo de couverture</span>
    </div>
  <?php endif; ?>

  <div class="cl-hero-overlay"></div>

  <div class="cl-hero-info">
    <div class="cl-hero-name"><?= htmlspecialchars($b['name']) ?></div>
    <?php if ($b['rating'] > 0): ?>
    <div class="cl-hero-rating">
      <i class="fa-solid fa-star cl-star"></i>
      <span><?= $b['rating'] ?></span>
      <span class="cl-review-count"
            data-fr="(<?= $b['review_count'] ?> avis)"
            data-en="(<?= $b['review_count'] ?> reviews)">
        (<?= $b['review_count'] ?> avis)
      </span>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ══════════════════════════════════════
     STATUS BAR
══════════════════════════════════════ -->
<div class="cl-status-bar">
  <?php if ($is_open): ?>
    <div class="cl-status-open">
      <div class="cl-status-dot" style="background:#059669;"></div>
      <span data-fr="Ouvert maintenant" data-en="Open now">Ouvert maintenant</span>
    </div>
    <?php if ($closes_at): ?>
    <div class="cl-status-hours"
         data-fr="Ferme à <?= $closes_at ?>"
         data-en="Closes at <?= $closes_at ?>">
      Ferme à <?= $closes_at ?>
    </div>
    <?php endif; ?>
  <?php else: ?>
    <div class="cl-status-open">
      <div class="cl-status-dot" style="background:#DC2626;"></div>
      <span data-fr="Fermé" data-en="Closed">Fermé</span>
    </div>
  <?php endif; ?>
</div>

<!-- ══════════════════════════════════════
     PRENDRE UN RDV — section inline
══════════════════════════════════════ -->
<div class="cl-rdv-section">
  <div class="cl-rdv-section-inner">
    <div class="cl-rdv-section-text">
      <div class="cl-rdv-section-title"
           data-fr="Prêt à réserver ?"
           data-en="Ready to book?">Prêt à réserver ?</div>
      <div class="cl-rdv-section-sub"
           data-fr="Choisissez votre créneau en quelques secondes"
           data-en="Pick your slot in seconds">
        Choisissez votre créneau en quelques secondes
      </div>
    </div>
    <button class="cl-rdv-btn-inline"
            style="background:var(--btn-color);"
            onclick="openRdv()">
      <i class="fa-regular fa-calendar-check"></i>
      <span data-fr="Prendre un RDV" data-en="Book now">Prendre un RDV</span>
    </button>
  </div>
</div>

<!-- ══════════════════════════════════════
     GALERIE PHOTOS
══════════════════════════════════════ -->
<section class="cl-section">
  <div class="cl-section-title"
       data-fr="Galerie photos"
       data-en="Photo gallery">Galerie photos</div>

  <div class="cl-gallery">
    <?php if (!empty($b['gallery'])): ?>
      <?php foreach (array_slice($b['gallery'], 0, 5) as $i => $photo): ?>
        <div class="cl-gallery-item <?= $i === 0 ? 'cl-gallery-wide' : '' ?>">
          <img src="../<?= htmlspecialchars($photo['path']) ?>"
               alt="<?= htmlspecialchars($photo['alt'] ?? $b['name']) ?>"
               loading="lazy">
        </div>
      <?php endforeach; ?>
      <?php if (count($b['gallery']) > 5): ?>
        <div class="cl-gallery-more">+<?= count($b['gallery']) - 5 ?> photos</div>
      <?php endif; ?>
    <?php else: ?>
      <!-- Placeholders — remplis par le propriétaire depuis clientLion -->
      <div class="cl-gallery-item cl-gallery-wide cl-gallery-ph"
           style="background:<?= hex_to_rgba($b['theme_color'],0.12) ?>;">
        <i class="fa-regular fa-image"
           style="color:<?= htmlspecialchars($b['theme_color']) ?>;font-size:28px;"></i>
      </div>
      <?php for ($i = 0; $i < 4; $i++): ?>
        <div class="cl-gallery-item cl-gallery-ph"
             style="background:<?= hex_to_rgba($b['theme_color'], 0.06 + $i * 0.02) ?>;">
          <i class="fa-regular fa-image"
             style="color:<?= htmlspecialchars($b['theme_color']) ?>;font-size:18px;opacity:0.5;"></i>
        </div>
      <?php endfor; ?>
      <div class="cl-gallery-more cl-gallery-add-hint"
           data-fr="Photos ajoutées par le propriétaire"
           data-en="Photos added by owner">
        Photos ajoutées par le propriétaire
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══════════════════════════════════════
     À PROPOS
══════════════════════════════════════ -->
<section class="cl-section">
  <div class="cl-section-title"
       data-fr="À propos"
       data-en="About us">À propos</div>
  <div class="cl-about-text"
       data-fr="<?= htmlspecialchars($b['description']) ?>"
       data-en="<?= htmlspecialchars($b['description']) ?>">
    <?= htmlspecialchars($b['description']) ?>
  </div>
  <button class="cl-read-more"
          style="color:var(--brand);"
          onclick="toggleAbout(this)"
          data-fr="Lire la suite →"
          data-en="Read more →">Lire la suite →
  </button>
</section>

<!-- ══════════════════════════════════════
     NOS SERVICES
══════════════════════════════════════ -->
<section class="cl-section">
  <div class="cl-section-title"
       data-fr="Nos services"
       data-en="Our services">Nos services</div>

  <?php if (!empty($b['services'])): ?>
    <div class="cl-services-list">
      <?php foreach ($b['services'] as $svc): ?>
      <div class="cl-service-item" onclick="openRdv()">
        <div class="cl-svc-bar"
             style="background:<?= htmlspecialchars($svc['color'] ?? $b['theme_color']) ?>;"></div>
        <div class="cl-svc-info">
          <div class="cl-svc-name"
               data-fr="<?= htmlspecialchars($svc['name'] ?? '') ?>"
               data-en="<?= htmlspecialchars($svc['name_en'] ?? $svc['name'] ?? '') ?>">
            <?= htmlspecialchars($svc['name'] ?? '') ?>
          </div>
          <div class="cl-svc-duration"><?= htmlspecialchars($svc['duration'] ?? '') ?></div>
        </div>
        <?php if ($b['show_prices'] && isset($svc['price'])): ?>
          <div class="cl-svc-price"><?= number_format($svc['price']) ?> F</div>
        <?php endif; ?>
        <div class="cl-svc-arrow">
          <i class="fa-solid fa-chevron-right"></i>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="cl-services-placeholder">
      <i class="fa-solid fa-list-ul"
         style="color:var(--brand);font-size:24px;margin-bottom:8px;"></i>
      <div data-fr="Les services seront affichés ici"
           data-en="Services will appear here">
        Les services seront affichés ici
      </div>
    </div>
  <?php endif; ?>
</section>

<!-- ══════════════════════════════════════
     CONTACT WHATSAPP
══════════════════════════════════════ -->
<section class="cl-section">
  <div class="cl-section-title"
       data-fr="Contact"
       data-en="Contact">Contact</div>

  <div class="cl-contact-card">
    <a href="https://wa.me/<?= preg_replace('/\D/','',$b['whatsapp']) ?>?text=Bonjour+<?= urlencode($b['name']) ?>+je+voudrais+prendre+un+RDV"
       target="_blank"
       class="cl-wa-btn">
      <i class="fa-brands fa-whatsapp cl-wa-icon"></i>
      <span data-fr="Contacter sur WhatsApp"
            data-en="Contact on WhatsApp">Contacter sur WhatsApp</span>
    </a>
    <div class="cl-contact-row">
      <span data-fr="Ville" data-en="City">Ville</span>
      <span><?= htmlspecialchars($b['city']) ?></span>
    </div>
    <div class="cl-contact-row">
      <span data-fr="Type" data-en="Type">Type</span>
      <span><?= htmlspecialchars($b['type']) ?></span>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════
     FOOTER LIONTECH
══════════════════════════════════════ -->
<footer class="cl-footer"
        style="background:var(--footer-bg);border-top-color:var(--border-col);">
  <?php if ($b['show_lt_logo']): ?>
  <div class="cl-footer-lt">
    <div class="cl-footer-lt-mark">LT</div>
    <span class="cl-footer-lt-text" style="color:var(--footer-text);">
      Propulsé par <strong style="color:#C9A84C;">LionTech</strong> · LionRDV
    </span>
  </div>
  <?php endif; ?>
  <div class="cl-footer-copy" style="color:var(--footer-text);">
    © 2026 <?= htmlspecialchars($b['name']) ?> · lionrdv.cm/<?= htmlspecialchars($b['slug']) ?>
  </div>
</footer>

<!-- ══════════════════════════════════════
     STICKY RDV BUTTON
══════════════════════════════════════ -->
<div class="cl-sticky-rdv">
  <button class="cl-sticky-btn"
          style="background:var(--btn-color);"
          onclick="openRdv()">
    <i class="fa-regular fa-calendar-check"></i>
    <span data-fr="Prendre un RDV"
          data-en="Book an appointment">Prendre un RDV</span>
  </button>
</div>

<!-- ══════════════════════════════════════
     MODAL RDV — horaires + WhatsApp
══════════════════════════════════════ -->
<div class="cl-modal-overlay" id="rdv-modal" onclick="closeRdv(event)">
  <div class="cl-modal">

    <div class="cl-modal-header" style="background:var(--brand);">
      <div class="cl-modal-title"
           data-fr="Prendre un RDV"
           data-en="Book an appointment">Prendre un RDV</div>
      <div class="cl-modal-sub"><?= htmlspecialchars($b['name']) ?></div>
      <button class="cl-modal-close" onclick="closeRdv()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="cl-modal-body">
      <div class="cl-modal-section-title"
           data-fr="Nos horaires"
           data-en="Our hours">Nos horaires</div>

      <div class="cl-hours-list">
        <?php foreach ($b['availability'] as $day): ?>
        <div class="cl-hours-row <?= !$day['open'] ? 'cl-hours-closed' : '' ?>">
          <span class="cl-hours-day"
                data-fr="<?= $day['day'] ?>"
                data-en="<?= $day['day_en'] ?>">
            <?= $day['day'] ?>
          </span>
          <?php if ($day['open']): ?>
            <span class="cl-hours-time"><?= $day['start'] ?> – <?= $day['end'] ?></span>
          <?php else: ?>
            <span class="cl-hours-ferme"
                  data-fr="Fermé"
                  data-en="Closed">Fermé</span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="cl-modal-cta">
        <div class="cl-modal-cta-text"
             data-fr="Réservez directement via WhatsApp"
             data-en="Book directly via WhatsApp">
          Réservez directement via WhatsApp
        </div>
        <a href="https://wa.me/<?= preg_replace('/\D/','',$b['whatsapp']) ?>?text=Bonjour+je+voudrais+prendre+un+RDV+chez+<?= urlencode($b['name']) ?>"
           target="_blank"
           class="cl-modal-wa-btn">
          <i class="fa-brands fa-whatsapp"></i>
          <span data-fr="Réserver sur WhatsApp"
                data-en="Book on WhatsApp">Réserver sur WhatsApp</span>
        </a>
      </div>
    </div>
  </div>
</div>

<script>
var BUSINESS = {
  name:    <?= json_encode($b['name']) ?>,
  slug:    <?= json_encode($b['slug']) ?>,
  lang:    <?= json_encode($b['language']) ?>,
  defLang: <?= json_encode($default_lang) ?>,
  whatsapp:<?= json_encode($b['whatsapp']) ?>
};
</script>
<script src="Utulisateur.js"></script>
</body>
</html>