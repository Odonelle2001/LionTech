<?php
/* ============================================================
   AjouterBussiness.php — LionRDV Admin
   Sauvegarde les données dans /LionRDV/data/[slug].json
   Utulisateur.php lit ce fichier pour afficher la page client
============================================================ */
$currentPage   = 'add_business';
$businessCount = 6;
$qrCount       = 3;
$alertCount    = 2;

/* ── DOSSIER DATA ─────────────────────────────────────────
   Crée C:\Xampp\htdocs\LionRDV\data\ si il n'existe pas
──────────────────────────────────────────────────────────── */
$data_dir = dirname(__DIR__) . '/data';
if (!is_dir($data_dir)) {
  mkdir($data_dir, 0755, true);
}

/* ── THEMES MAP ───────────────────────────────────────────
   Convertit la couleur choisie en couleur de fond
──────────────────────────────────────────────────────────── */
$theme_bg_map = [
  '#D4447A' => '#FFF0F8',
  '#0A0A0A' => '#FAFAF8',
  '#0EA5E9' => '#F0F9FF',
  '#059669' => '#F0FDF4',
  '#E07B39' => '#FFF8F3',
  '#7C3AED' => '#F5F3FF',
  '#DC2626' => '#FFF5F5',
  '#1B4332' => '#F9F6F0',
];

$save_success = false;
$save_error   = '';
$saved_slug   = '';

/* ── MODE ÉDITION ─────────────────────────────────────────
   Si ?edit=slug dans l'URL, charge les données existantes
──────────────────────────────────────────────────────────── */
$edit_slug = trim($_GET['edit'] ?? '');
$edit_mode = !empty($edit_slug);
$prefill   = [];

if ($edit_mode) {
  $edit_file = $data_dir . '/' . $edit_slug . '.json';
  if (file_exists($edit_file)) {
    $prefill = json_decode(file_get_contents($edit_file), true) ?? [];
  }
}

function pre($key, $default = '') {
  global $prefill;
  $val = $prefill[$key] ?? $default;
  return htmlspecialchars((string)$val, ENT_QUOTES);
}

/* ── HANDLE FORM SUBMISSION ───────────────────────────────
   Quand l'admin clique "Créer le compte" ou "Enregistrer"
──────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {

  /* Slug : on nettoie le sous-domaine entré par l'admin */
  $slug = strtolower(trim($_POST['subdomain'] ?? ''));
  $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
  $slug = trim($slug, '-');
  if (empty($slug)) $slug = 'business-' . time();

  /* Initiales à partir du nom */
  $name     = trim($_POST['business_name'] ?? '');
  $words    = explode(' ', $name);
  $initials = '';
  foreach (array_slice($words, 0, 2) as $w) {
    $initials .= mb_strtoupper(mb_substr($w, 0, 1));
  }

  /* Couleur thème et fond correspondant */
  $theme_color = $_POST['primary_color'] ?? '#D4447A';
  $theme_bg    = $theme_bg_map[$theme_color] ?? '#FFF0F8';

  /* Services — l'admin peut en ajouter via clientLion plus tard
     Pour l'instant on garde les services vides (remplis par le propriétaire) */
  $services_existing = $prefill['services'] ?? [];

  /* Disponibilités — défaut 7 jours, propriétaire peut modifier */
  $availability_existing = $prefill['availability'] ?? [
    ['day'=>'Lundi',    'day_en'=>'Monday',    'open'=>true,  'start'=>'08:00','end'=>'18:00'],
    ['day'=>'Mardi',    'day_en'=>'Tuesday',   'open'=>true,  'start'=>'08:00','end'=>'18:00'],
    ['day'=>'Mercredi', 'day_en'=>'Wednesday', 'open'=>true,  'start'=>'08:00','end'=>'18:00'],
    ['day'=>'Jeudi',    'day_en'=>'Thursday',  'open'=>true,  'start'=>'08:00','end'=>'18:00'],
    ['day'=>'Vendredi', 'day_en'=>'Friday',    'open'=>true,  'start'=>'08:00','end'=>'19:00'],
    ['day'=>'Samedi',   'day_en'=>'Saturday',  'open'=>true,  'start'=>'09:00','end'=>'17:00'],
    ['day'=>'Dimanche', 'day_en'=>'Sunday',    'open'=>false, 'start'=>'',     'end'=>''],
  ];

  /* Handle logo upload */
  $logo_path = $prefill['logo'] ?? '';
  if (!empty($_FILES['logo']['tmp_name'])) {
    $upload_dir = dirname(__DIR__) . '/uploads/' . $slug . '/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $ext      = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $filename = 'logo.' . $ext;
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $filename)) {
      $logo_path = 'uploads/' . $slug . '/' . $filename;
    }
  }

  /* Assemble business data */
  $business = [
    'name'             => $name,
    'slug'             => $slug,
    'initials'         => $initials,
    'type'             => trim($_POST['business_type_label'] ?? $_POST['business_type'] ?? ''),
    'description'      => trim($_POST['description'] ?? ''),
    'city'             => trim($_POST['city'] ?? '') . (trim($_POST['quarter'] ?? '') ? ', ' . trim($_POST['quarter']) : ''),
    'whatsapp'         => '+237' . preg_replace('/\D/', '', $_POST['whatsapp'] ?? ''),
    'rating'           => (float)($prefill['rating'] ?? 0),
    'review_count'     => (int)($prefill['review_count'] ?? 0),
    'logo'             => $logo_path,
    'cover_photo'      => $prefill['cover_photo'] ?? '',
    'gallery'          => $prefill['gallery'] ?? [],

    /* Thème */
    'theme_color'      => $theme_color,
    'theme_bg'         => $theme_bg,
    'theme_name'       => $_POST['theme_preset'] ?? 'Élégant',
    'navbar_style'     => $_POST['navbar_style'] ?? 'light',
    'footer_style'     => $_POST['footer_style'] ?? 'minimal',
    'show_biz_logo'    => isset($_POST['show_business_logo']),
    'show_lt_logo'     => isset($_POST['show_liontech_logo']),
    'lt_footer_only'   => isset($_POST['show_liontech_footer_only']),

    /* Couleurs */
    'primary_color'    => $_POST['primary_color']    ?? '#D4447A',
    'secondary_color'  => $_POST['secondary_color']  ?? '#0A0A0A',
    'button_color'     => $_POST['button_color']     ?? '#D4447A',
    'text_color'       => $_POST['text_color']       ?? '#222222',
    'background_color' => $_POST['background_color'] ?? '#ffffff',
    'border_color'     => $_POST['border_color']     ?? '#e5e7eb',

    /* Langue */
    'language'         => $_POST['site_language'] ?? 'fr',

    /* Services — remplis par le propriétaire dans clientLion */
    'show_prices'      => isset($_POST['show_prices']),
    'services'         => $services_existing,

    /* Disponibilités — modifiables par le propriétaire dans clientLion */
    'availability'     => $availability_existing,

    /* Compte */
    'owner_email'      => trim($_POST['login_email'] ?? ''),
    'owner_password'   => trim($_POST['temp_password'] ?? ''),
    'plan'             => $_POST['plan'] ?? 'basic',
    'booking_style'    => $_POST['booking_style'] ?? 'individual',
    'internal_notes'   => trim($_POST['internal_notes'] ?? ''),
    'created_at'       => $prefill['created_at'] ?? date('Y-m-d H:i:s'),
    'updated_at'       => date('Y-m-d H:i:s'),
  ];

  /* Save to JSON file */
  $json_file = $data_dir . '/' . $slug . '.json';
  $written   = file_put_contents($json_file, json_encode($business, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

  if ($written !== false) {
    $save_success = true;
    $saved_slug   = $slug;
    $prefill      = $business; /* update prefill for display */
  } else {
    $save_error = 'Erreur : impossible d\'écrire dans le dossier data/. Vérifiez les permissions XAMPP.';
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $edit_mode ? 'Modifier un business' : 'Ajouter un business' ?> — LionRDV</title>
  <link rel="stylesheet" href="../sidebar.css">
  <link rel="stylesheet" href="AjouterBussiness.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app-layout">

  <?php include '../sidebar.php'; ?>

  <main class="addbiz-main">

    <!-- ── TOP BAR ── -->
    <header class="addbiz-topbar">
      <div class="addbiz-topbar-left">
        <h1><?= $edit_mode ? 'Modifier le business' : 'Ajouter un business' ?></h1>
        <p><?= $edit_mode
          ? 'Modification de <strong>' . htmlspecialchars($prefill['name'] ?? $edit_slug) . '</strong>'
          : 'Créer un nouveau client sur la plateforme LionRDV' ?></p>
      </div>
      <div class="addbiz-topbar-actions">
        <a href="../RSVAdmin.php" class="btn-outline-dark">
          <i class="fa-solid fa-arrow-left"></i>
          Retour
        </a>
        <button type="submit" form="biz-form" class="btn-gold-dark" id="create-btn">
          <i class="fa-solid fa-<?= $edit_mode ? 'floppy-disk' : 'check' ?>"></i>
          <?= $edit_mode ? 'Enregistrer' : 'Créer le compte' ?>
        </button>
      </div>
    </header>

    <?php if ($save_error): ?>
    <div style="margin:16px 28px;padding:12px 16px;background:#FEF2F2;border:1px solid #FECACA;border-radius:10px;color:#DC2626;font-size:13px;">
      <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($save_error) ?>
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════
         MAIN FORM — enctype for file upload
    ══════════════════════════════════════ -->
    <form id="biz-form"
          method="POST"
          action="AjouterBussiness.php<?= $edit_mode ? '?edit=' . urlencode($edit_slug) : '' ?>"
          enctype="multipart/form-data">

      <input type="hidden" name="form_submitted" value="1">

      <div class="addbiz-content">
        <section class="addbiz-form-panel">

          <!-- ── SECTION 1 : Informations ── -->
          <div class="form-card">
            <div class="form-card-header">
              <div class="step-badge">1</div>
              <div>
                <h2>Informations du business</h2>
                <p>Nom, lien, contact, localisation, description et logo</p>
              </div>
            </div>
            <div class="form-grid">

              <div class="form-group">
                <label for="business_name">Nom du business *</label>
                <input type="text" id="business_name" name="business_name"
                       placeholder="Ex: Nora Beauty"
                       value="<?= pre('name') ?>"
                       required>
              </div>

              <div class="form-group">
                <label for="subdomain">Sous-domaine *</label>
                <div class="input-prefix">
                  <span>lionrdv.cm/</span>
                  <input type="text" id="subdomain" name="subdomain"
                         placeholder="nora-beauty"
                         value="<?= pre('slug') ?>"
                         required>
                </div>
              </div>

              <div class="form-group">
                <label for="whatsapp">WhatsApp *</label>
                <div class="input-prefix">
                  <span>+237</span>
                  <input type="text" id="whatsapp" name="whatsapp"
                         placeholder="6XX XXX XXX"
                         value="<?= pre('whatsapp') ?>">
                </div>
              </div>

              <div class="form-group">
                <label for="owner_email">Email propriétaire</label>
                <input type="email" id="owner_email" name="owner_email"
                       placeholder="owner@email.com"
                       value="<?= pre('owner_email') ?>">
              </div>

              <div class="form-group">
                <label for="city">Ville</label>
                <input type="text" id="city" name="city"
                       placeholder="Yaoundé"
                       value="<?= pre('city') ?>">
              </div>

              <div class="form-group">
                <label for="quarter">Quartier</label>
                <input type="text" id="quarter" name="quarter"
                       placeholder="Bastos"
                       value="<?= pre('quarter') ?>">
              </div>

              <div class="form-group full-width">
                <label for="description">Description courte</label>
                <textarea id="description" name="description" rows="4"
                          placeholder="Petite description visible sur la page de réservation..."><?= pre('description') ?></textarea>
              </div>

              <div class="form-group full-width">
                <label for="logo">Logo du business</label>
                <?php if (!empty($prefill['logo'])): ?>
                  <div style="margin-bottom:8px;display:flex;align-items:center;gap:10px;">
                    <img src="../<?= htmlspecialchars($prefill['logo']) ?>"
                         style="width:50px;height:50px;border-radius:10px;object-fit:cover;border:1px solid #E2DDD4;">
                    <span style="font-size:11px;color:#7A7570;">Logo actuel — upload pour remplacer</span>
                  </div>
                <?php endif; ?>
                <input type="file" id="logo" name="logo"
                       accept="image/*" onchange="previewLogo(this)">
              </div>

              <!-- CONNEXION BOX -->
              <div class="form-group full-width">
                <label>Accès propriétaire — Connexion</label>
                <div class="owner-access-box">
                  <div class="owner-access-info">
                    <div class="owner-access-icon">
                      <i class="fa-solid fa-lock"></i>
                    </div>
                    <div>
                      <div class="owner-access-title">Connexion espace propriétaire</div>
                      <div class="owner-access-sub">Ce bouton apparaîtra sur la page du business après création</div>
                    </div>
                  </div>
                  <a href="/LionRDV/Clien%20de%20LionTech/ClientLion.php"
                     target="_blank"
                     class="owner-connexion-btn">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Connexion
                  </a>
                </div>
              </div>

            </div>
          </div>

          <!-- ── SECTION 2 : Type de business ── -->
          <div class="form-card">
            <div class="form-card-header">
              <div class="step-badge">2</div>
              <div>
                <h2>Type de business</h2>
                <p>Sélectionnez le type pour personnaliser la réservation</p>
              </div>
            </div>

            <div class="business-type-grid">
              <label class="type-option active"><input type="radio" name="business_type" value="salon" checked><span>💅 Salon de beauté</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="restaurant"><span>🍽️ Restaurant</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="hotel"><span>🏨 Hôtellerie</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="medical"><span>🩺 Clinique / Médical</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="barber"><span>✂️ Barbier</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="fitness"><span>🏋️ Sport & fitness</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="photo"><span>📸 Photographie</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="law"><span>⚖️ Avocat / Cabinet</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="coach"><span>🎯 Coach</span></label>
              <label class="type-option"><input type="radio" name="business_type" value="other"><span>📝 Autre</span></label>
            </div>

            <!-- Hidden field to store the type label for saving -->
            <input type="hidden" id="business_type_label" name="business_type_label" value="Salon de beauté">

            <div class="type-services-box" id="typeServicesBox">
              <label for="business_category_select">Catégories proposées</label>
              <select id="business_category_select" name="business_category_select">
                <option value="">-- Sélectionnez une catégorie --</option>
              </select>
            </div>

            <div class="custom-type-box" id="customTypeBox" style="display:none;">
              <label for="custom_business_name">Nom du type de business</label>
              <input type="text" id="custom_business_name" name="custom_business_name"
                     placeholder="Ex: Studio Podcast"
                     value="<?= pre('custom_type') ?>">
              <label for="custom_business_categories">Catégories / services à proposer</label>
              <textarea id="custom_business_categories" name="custom_business_categories" rows="4"
                        placeholder="Ex: Enregistrement, Mixage, Podcast vidéo, Location studio"></textarea>
            </div>

            <div class="form-group top-space">
              <label for="other_type">Si autre, précisez</label>
              <input type="text" id="other_type" name="other_type"
                     placeholder="Ex: Studio Podcast">
            </div>
          </div>

          <!-- ── SECTION 3 : Style de réservation ── -->
          <section class="form-section">
            <div class="section-number">3</div>
            <div class="section-content">
              <h3>Style de réservation</h3>
              <p>Choisissez comment ce business reçoit les réservations</p>
              <div class="booking-style-grid">
                <label class="booking-option active"><input type="radio" name="booking_style" value="individual" checked><span>👤 Individuelle</span></label>
                <label class="booking-option"><input type="radio" name="booking_style" value="multiple"><span>👥 Multiple</span></label>
                <label class="booking-option"><input type="radio" name="booking_style" value="employee"><span>🧑‍💼 Par employé</span></label>
                <label class="booking-option"><input type="radio" name="booking_style" value="capacity"><span>🏷️ Par capacité</span></label>
                <label class="booking-option"><input type="radio" name="booking_style" value="request"><span>📩 Sur demande</span></label>
              </div>
              <div id="bookingStyleFields" class="booking-style-fields">
                <div class="booking-fields-group" data-style="individual">
                  <label for="slot_duration_individual">Durée par créneau</label>
                  <select id="slot_duration_individual" name="slot_duration_individual">
                    <option value="15">15 min</option>
                    <option value="30" selected>30 min</option>
                    <option value="45">45 min</option>
                    <option value="60">1 heure</option>
                    <option value="90">1h30</option>
                    <option value="120">2 heures</option>
                  </select>
                </div>
                <div class="booking-fields-group" data-style="multiple" style="display:none;">
                  <label for="max_parallel_bookings">Nombre maximum de réservations simultanées</label>
                  <input type="number" id="max_parallel_bookings" name="max_parallel_bookings" min="1" value="3">
                  <label for="slot_duration_multiple">Durée par créneau</label>
                  <select id="slot_duration_multiple" name="slot_duration_multiple">
                    <option value="15">15 min</option><option value="30" selected>30 min</option>
                    <option value="45">45 min</option><option value="60">1 heure</option>
                    <option value="90">1h30</option><option value="120">2 heures</option>
                  </select>
                </div>
                <div class="booking-fields-group" data-style="employee" style="display:none;">
                  <label for="employee_count">Nombre d'employés</label>
                  <input type="number" id="employee_count" name="employee_count" min="1" value="3">
                  <label for="client_choose_employee">Le client peut choisir l'employé ?</label>
                  <select id="client_choose_employee" name="client_choose_employee">
                    <option value="yes" selected>Oui</option><option value="no">Non</option>
                  </select>
                  <label for="slot_duration_employee">Durée par créneau</label>
                  <select id="slot_duration_employee" name="slot_duration_employee">
                    <option value="15">15 min</option><option value="30" selected>30 min</option>
                    <option value="45">45 min</option><option value="60">1 heure</option>
                    <option value="90">1h30</option><option value="120">2 heures</option>
                  </select>
                </div>
                <div class="booking-fields-group" data-style="capacity" style="display:none;">
                  <label for="max_capacity_per_slot">Capacité maximale par créneau</label>
                  <input type="number" id="max_capacity_per_slot" name="max_capacity_per_slot" min="1" value="10">
                  <label for="capacity_label">Type de capacité</label>
                  <select id="capacity_label" name="capacity_label">
                    <option value="places">Places</option><option value="tables">Tables</option>
                    <option value="rooms">Chambres</option><option value="people">Personnes</option>
                  </select>
                </div>
                <div class="booking-fields-group" data-style="request" style="display:none;">
                  <label for="manual_validation">Validation des réservations</label>
                  <select id="manual_validation" name="manual_validation">
                    <option value="manual" selected>Validation manuelle</option>
                    <option value="auto">Validation automatique</option>
                  </select>
                  <label for="request_note">Message d'information</label>
                  <textarea id="request_note" name="request_note" rows="3"
                            placeholder="Ex: Votre demande sera confirmée par le business après vérification."></textarea>
                </div>
              </div>
            </div>
          </section>

          <!-- ── SECTION 4 : Thème, design & branding ── -->
          <div class="form-card">
            <div class="form-card-header">
              <div class="step-badge">4</div>
              <div>
                <h2>Thème, design & branding</h2>
                <p>Configurez le style visuel, les couleurs, les logos et la langue du site</p>
              </div>
            </div>

            <div class="branding-block">
              <h3>Thème prédéfini</h3>
              <div class="theme-preset-grid">
                <label class="theme-card active"><input type="radio" name="theme_preset" value="elegant" checked><div class="theme-preview elegant-preview"></div><span>✨ Élégant</span></label>
                <label class="theme-card"><input type="radio" name="theme_preset" value="minimal"><div class="theme-preview minimal-preview"></div><span>🧼 Minimal</span></label>
                <label class="theme-card"><input type="radio" name="theme_preset" value="luxe"><div class="theme-preview luxe-preview"></div><span>👑 Luxe</span></label>
                <label class="theme-card"><input type="radio" name="theme_preset" value="modern"><div class="theme-preview modern-preview"></div><span>🚀 Moderne</span></label>
                <label class="theme-card"><input type="radio" name="theme_preset" value="nature"><div class="theme-preview nature-preview"></div><span>🌿 Nature</span></label>
                <label class="theme-card"><input type="radio" name="theme_preset" value="dark"><div class="theme-preview dark-preview"></div><span>🌙 Sombre</span></label>
              </div>
            </div>

            <div class="branding-block">
              <h3>Couleurs</h3>
              <div class="color-settings-grid">
                <div class="color-field"><label for="primary_color">Couleur principale</label><div class="color-input-wrap"><input type="color" id="primary_color" name="primary_color" value="<?= pre('primary_color','#d4af37') ?>"><input type="text" id="primary_color_text" value="<?= pre('primary_color','#d4af37') ?>"></div></div>
                <div class="color-field"><label for="secondary_color">Couleur secondaire</label><div class="color-input-wrap"><input type="color" id="secondary_color" name="secondary_color" value="<?= pre('secondary_color','#111111') ?>"><input type="text" id="secondary_color_text" value="<?= pre('secondary_color','#111111') ?>"></div></div>
                <div class="color-field"><label for="button_color">Couleur des boutons</label><div class="color-input-wrap"><input type="color" id="button_color" name="button_color" value="<?= pre('button_color','#d4af37') ?>"><input type="text" id="button_color_text" value="<?= pre('button_color','#d4af37') ?>"></div></div>
                <div class="color-field"><label for="text_color">Couleur du texte</label><div class="color-input-wrap"><input type="color" id="text_color" name="text_color" value="<?= pre('text_color','#222222') ?>"><input type="text" id="text_color_text" value="<?= pre('text_color','#222222') ?>"></div></div>
                <div class="color-field"><label for="background_color">Couleur de fond</label><div class="color-input-wrap"><input type="color" id="background_color" name="background_color" value="<?= pre('background_color','#ffffff') ?>"><input type="text" id="background_color_text" value="<?= pre('background_color','#ffffff') ?>"></div></div>
                <div class="color-field"><label for="border_color">Couleur des bordures</label><div class="color-input-wrap"><input type="color" id="border_color" name="border_color" value="<?= pre('border_color','#e5e7eb') ?>"><input type="text" id="border_color_text" value="<?= pre('border_color','#e5e7eb') ?>"></div></div>
              </div>
            </div>

            <div class="branding-block">
              <h3>Design du fond</h3>
              <div class="design-grid">
                <label class="design-option active"><input type="radio" name="background_style" value="solid" checked><span>⬜ Fond uni</span></label>
                <label class="design-option"><input type="radio" name="background_style" value="gradient"><span>🌈 Dégradé</span></label>
                <label class="design-option"><input type="radio" name="background_style" value="pattern"><span>🧩 Motif léger</span></label>
                <label class="design-option"><input type="radio" name="background_style" value="texture"><span>🎨 Texture</span></label>
                <label class="design-option"><input type="radio" name="background_style" value="image"><span>🖼️ Image</span></label>
              </div>
              <div id="backgroundUploadWrap" style="display:none;margin-top:12px;">
                <label for="background_image">Image de fond</label>
                <input type="file" id="background_image" name="background_image" accept="image/*">
              </div>
              <div id="customCssWrap" style="margin-top:12px;">
                <label for="custom_css_design">CSS design personnalisé</label>
                <textarea id="custom_css_design" name="custom_css_design" rows="4"
                          placeholder="Ex: background: linear-gradient(135deg, #f8f1d4, #ffffff);"></textarea>
              </div>
            </div>

            <div class="branding-block">
              <h3>Navbar & footer</h3>
              <div class="branding-layout-grid">
                <div><label for="navbar_style">Style de navbar</label>
                  <select id="navbar_style" name="navbar_style">
                    <option value="light" <?= pre('navbar_style') === 'light' ? 'selected' : '' ?>>Claire</option>
                    <option value="dark"  <?= pre('navbar_style') === 'dark'  ? 'selected' : '' ?>>Sombre</option>
                    <option value="transparent" <?= pre('navbar_style') === 'transparent' ? 'selected' : '' ?>>Transparente</option>
                    <option value="boxed" <?= pre('navbar_style') === 'boxed' ? 'selected' : '' ?>>Encadrée</option>
                  </select>
                </div>
                <div><label for="footer_style">Style de footer</label>
                  <select id="footer_style" name="footer_style">
                    <option value="light"   <?= pre('footer_style') === 'light'   ? 'selected' : '' ?>>Clair</option>
                    <option value="dark"    <?= pre('footer_style') === 'dark'    ? 'selected' : '' ?>>Sombre</option>
                    <option value="minimal" <?= pre('footer_style') === 'minimal' ? 'selected' : '' ?>>Minimal</option>
                    <option value="rich"    <?= pre('footer_style') === 'rich'    ? 'selected' : '' ?>>Complet</option>
                  </select>
                </div>
                <div><label for="business_logo_position">Position logo business</label>
                  <select id="business_logo_position" name="business_logo_position">
                    <option value="left">Gauche</option><option value="center">Centre</option><option value="right">Droite</option>
                  </select>
                </div>
                <div><label for="business_logo_size">Taille logo business</label>
                  <select id="business_logo_size" name="business_logo_size">
                    <option value="small">Petit</option><option value="medium" selected>Moyen</option><option value="large">Grand</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="branding-block">
              <h3>Gestion des logos</h3>
              <div class="toggle-grid">
                <label class="toggle-item"><input type="checkbox" name="show_business_logo" <?= !empty($prefill['show_biz_logo']) ? 'checked' : 'checked' ?>><span>Afficher le logo du business</span></label>
                <label class="toggle-item"><input type="checkbox" name="show_liontech_logo" <?= !empty($prefill['show_lt_logo']) ? 'checked' : 'checked' ?>><span>Afficher le logo LionTech</span></label>
                <label class="toggle-item"><input type="checkbox" name="show_liontech_footer_only" <?= !empty($prefill['lt_footer_only']) ? 'checked' : '' ?>><span>Afficher LionTech uniquement dans le footer</span></label>
                <label class="toggle-item"><input type="checkbox" name="show_prices" <?= !empty($prefill['show_prices']) ? 'checked' : 'checked' ?>><span>Afficher les prix des services</span></label>
              </div>
            </div>

            <div class="branding-block">
              <h3>Langue du site</h3>
              <div class="language-grid">
                <label class="language-option <?= (pre('language','fr') === 'fr') ? 'active' : '' ?>"><input type="radio" name="site_language" value="fr" <?= (pre('language','fr') === 'fr') ? 'checked' : '' ?>><span>🇫🇷 Français</span></label>
                <label class="language-option <?= (pre('language') === 'en') ? 'active' : '' ?>"><input type="radio" name="site_language" value="en" <?= (pre('language') === 'en') ? 'checked' : '' ?>><span>🇬🇧 English</span></label>
                <label class="language-option <?= (pre('language') === 'bilingual') ? 'active' : '' ?>"><input type="radio" name="site_language" value="bilingual" <?= (pre('language') === 'bilingual') ? 'checked' : '' ?>><span>🌍 Bilingue FR/EN</span></label>
              </div>
            </div>
          </div>

          <!-- ── SECTION 5 : Compte propriétaire ── -->
          <div class="form-card">
            <div class="form-card-header">
              <div class="step-badge">5</div>
              <div>
                <h2>Compte d'accès propriétaire</h2>
                <p>Créer le compte utilisé par le propriétaire du business</p>
              </div>
            </div>
            <div class="form-grid">
              <div class="form-group">
                <label for="login_email">Email de connexion</label>
                <input type="email" id="login_email" name="login_email"
                       placeholder="nora@email.com"
                       value="<?= pre('owner_email') ?>">
              </div>
              <div class="form-group">
                <label for="temp_password">Mot de passe temporaire</label>
                <div class="password-row">
                  <input type="text" id="temp_password" name="temp_password"
                         placeholder="Temporaire123"
                         value="<?= pre('owner_password') ?>">
                  <button type="button" class="small-generate-btn">Générer</button>
                </div>
              </div>
              <div class="form-group full-width">
                <label>Plan d'abonnement</label>
                <div class="plan-options">
                  <label class="plan-card <?= pre('plan') === 'basic' ? 'active' : '' ?>">
                    <input type="radio" name="plan" value="basic" <?= pre('plan') === 'basic' ? 'checked' : '' ?>>
                    <span class="plan-name">Basic</span>
                    <span class="plan-price">10 000 FCFA</span>
                  </label>
                  <label class="plan-card <?= (pre('plan','standard') === 'standard') ? 'active' : '' ?>">
                    <input type="radio" name="plan" value="standard" <?= (pre('plan','standard') === 'standard') ? 'checked' : '' ?>>
                    <span class="plan-name">Standard</span>
                    <span class="plan-price">15 000 FCFA</span>
                  </label>
                  <label class="plan-card <?= pre('plan') === 'premium' ? 'active' : '' ?>">
                    <input type="radio" name="plan" value="premium" <?= pre('plan') === 'premium' ? 'checked' : '' ?>>
                    <span class="plan-name">Premium</span>
                    <span class="plan-price">20 000 FCFA</span>
                  </label>
                </div>
              </div>
              <div class="form-group full-width">
                <label for="internal_notes">Notes internes</label>
                <textarea id="internal_notes" name="internal_notes" rows="4"
                          placeholder="Visible uniquement par LionTech..."><?= pre('internal_notes') ?></textarea>
              </div>
            </div>
          </div>

          <!-- ── OUTPUT BOX — apparaît après soumission ── -->
          <?php if ($save_success): ?>
          <div class="out-box show" id="out-box">
            <div class="out-top">
              <i class="fa-solid fa-circle-check"></i>
              <?= $edit_mode ? 'Business mis à jour avec succès !' : 'Compte créé avec succès !' ?>
            </div>
            <div class="out-label">Lien de réservation :</div>
            <div class="out-link" id="out-link">lionrdv.cm/<?= htmlspecialchars($saved_slug) ?></div>
            <div class="out-btns">
              <button type="button" class="ob ob-gold" onclick="copyLink()">
                <i class="fa-regular fa-copy"></i> Copier le lien
              </button>
              <a href="/LionRDV/Utulisateur.php?slug=<?= urlencode($saved_slug) ?>"
                 target="_blank" class="ob ob-ghost">
                <i class="fa-solid fa-eye"></i> Voir la page client
              </a>
            </div>
            <div class="out-credentials">
              <div class="out-cred-title">Identifiants propriétaire</div>
              <div class="out-cred-row"><span>URL connexion</span><strong>/LionRDV/Clien de LionTech/ClientLion.php</strong></div>
              <div class="out-cred-row"><span>Email</span><strong><?= htmlspecialchars($prefill['owner_email'] ?? '') ?></strong></div>
              <div class="out-cred-row"><span>Mot de passe</span><strong><?= htmlspecialchars($prefill['owner_password'] ?? '') ?></strong></div>
            </div>
            <div class="out-business-preview">
              <div class="out-preview-label">Aperçu page business</div>
              <div class="out-preview-card">
                <div class="out-preview-logo-wrap">
                  <?php if (!empty($prefill['logo'])): ?>
                    <img src="../<?= htmlspecialchars($prefill['logo']) ?>"
                         id="out-logo-preview" style="display:block;">
                  <?php else: ?>
                    <div id="out-logo-placeholder" class="out-logo-placeholder">
                      <i class="fa-solid fa-image"></i>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="out-preview-biz-name"><?= htmlspecialchars($prefill['name'] ?? '') ?></div>
                <div class="out-preview-biz-sub">lionrdv.cm/<?= htmlspecialchars($saved_slug) ?></div>
                <a href="/LionRDV/Clien%20de%20LionTech/ClientLion.php"
                   target="_blank" class="out-connexion-btn">
                  <i class="fa-solid fa-right-to-bracket"></i> Connexion
                </a>
                <div class="out-preview-note">
                  <i class="fa-solid fa-circle-info"></i>
                  Ce bouton est visible par le propriétaire sur sa page publique
                </div>
              </div>
            </div>
          </div>
          <?php else: ?>
          <div class="out-box" id="out-box">
            <div class="out-top"><i class="fa-solid fa-circle-check"></i> Compte créé avec succès !</div>
            <div class="out-label">Lien de réservation :</div>
            <div class="out-link" id="out-link">lionrdv.cm/nora-beauty</div>
            <div class="out-btns">
              <button type="button" class="ob ob-gold" onclick="copyLink()"><i class="fa-regular fa-copy"></i> Copier le lien</button>
              <button type="button" class="ob ob-ghost"><i class="fa-solid fa-qrcode"></i> Télécharger QR</button>
              <button type="button" class="ob ob-ghost"><i class="fa-brands fa-whatsapp"></i> WhatsApp</button>
            </div>
            <div class="out-credentials">
              <div class="out-cred-title">Identifiants propriétaire</div>
              <div class="out-cred-row"><span>Email</span><strong id="out-email">—</strong></div>
              <div class="out-cred-row"><span>Mot de passe</span><strong id="out-password">—</strong></div>
            </div>
            <div class="out-business-preview">
              <div class="out-preview-label">Aperçu page business</div>
              <div class="out-preview-card">
                <div class="out-preview-logo-wrap">
                  <img id="out-logo-preview" src="" alt="Logo" style="display:none;">
                  <div id="out-logo-placeholder" class="out-logo-placeholder"><i class="fa-solid fa-image"></i></div>
                </div>
                <div class="out-preview-biz-name" id="out-biz-name">Nom du business</div>
                <div class="out-preview-biz-sub" id="out-biz-sub">lionrdv.cm/slug</div>
                <a href="/LionRDV/Clien%20de%20LionTech/ClientLion.php" target="_blank" class="out-connexion-btn">
                  <i class="fa-solid fa-right-to-bracket"></i> Connexion
                </a>
                <div class="out-preview-note"><i class="fa-solid fa-circle-info"></i> Ce bouton est visible par le propriétaire sur sa page publique</div>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </section>

        <!-- ── RIGHT PREVIEW PANEL ── -->
        <aside class="addbiz-preview-panel">
          <div class="preview-top-actions">
            <a href="../RSVAdmin.php" class="preview-btn dark">
              <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
            <button type="submit" form="biz-form" class="preview-btn solid">
              <?= $edit_mode ? 'Enregistrer' : 'Confirmer & créer' ?>
            </button>
          </div>
          <div class="preview-toolbar">
            <div class="lang-switch">
              <span>LANGUE</span>
              <button type="button" class="lang-btn active">FR</button>
              <button type="button" class="lang-btn">EN</button>
            </div>
            <div class="theme-switch">
              <span>THÈME</span>
              <div class="theme-preview-dots">
                <span class="theme-dot pink active"></span>
                <span class="theme-dot black"></span>
                <span class="theme-dot blue"></span>
                <span class="theme-dot green"></span>
                <span class="theme-dot orange"></span>
                <span class="theme-dot purple"></span>
                <span class="theme-dot red"></span>
                <span class="theme-dot darkgreen"></span>
              </div>
            </div>
          </div>
          <div class="preview-business-tags">
            <span class="preview-tag">● Nora Beauty · Salon de beauté</span>
            <span class="preview-tag">lionrdv.cm/nora-beauty</span>
          </div>
          <div class="preview-card-row">
            <div class="preview-info-card">
              <div class="preview-info-icon gold"><i class="fa-regular fa-user"></i></div>
              <h3>Vue Client (Propriétaire du salon)</h3>
              <p>Ce que voit Nora quand elle se connecte à son dashboard.</p>
              <span class="mini-pill">Accès via Connexion</span>
            </div>
            <div class="preview-info-card">
              <div class="preview-info-icon gray"><i class="fa-solid fa-mobile-screen"></i></div>
              <h3>Vue Customer (Le client qui réserve)</h3>
              <p>Ce que voit la personne qui scanne le QR code ou ouvre le lien.</p>
              <span class="mini-pill">Accès via QR / lien</span>
            </div>
          </div>
          <div class="preview-section-title">
            <span class="preview-badge">CLIENT</span>
            <div>
              <h3>Dashboard propriétaire</h3>
              <p>Se connecte via lionrdv.cm/login</p>
            </div>
          </div>
          <div class="phone-preview-row">
            <div class="phone-mockup">
              <div class="phone-screen pink-theme">
                <div class="phone-header">Nora Beauty</div>
                <div class="phone-body">
                  <div class="phone-stat-row"><div class="phone-stat">8</div><div class="phone-stat">47K</div><div class="phone-stat">4.9</div></div>
                  <div class="phone-card">Planning du jour</div>
                  <div class="phone-card">09h30 - Awa Tchoupo</div>
                  <div class="phone-card">11h00 - Carine Bebe</div>
                  <div class="phone-card">08h00 - Marie N.</div>
                </div>
              </div>
              <p class="phone-caption">Dashboard quotidien avec planning et statistiques</p>
            </div>
            <div class="phone-mockup">
              <div class="phone-screen pink-theme">
                <div class="phone-header">Nora Beauty</div>
                <div class="phone-body">
                  <div class="phone-card">Disponibilités</div>
                  <div class="phone-card">Lundi 08h00 - 18h00</div>
                  <div class="phone-card">Mardi 08h00 - 18h00</div>
                  <div class="phone-card">Employés actifs: 4</div>
                </div>
              </div>
              <p class="phone-caption">Gestion des horaires, jours et employés</p>
            </div>
          </div>
          <div class="preview-section-title lower">
            <span class="preview-badge secondary">CUSTOMER</span>
            <div>
              <h3>Page de réservation — ce que voit le client final</h3>
              <p>Accessible via QR code ou lien</p>
            </div>
          </div>
          <div class="phone-preview-row">
            <div class="phone-mockup">
              <div class="phone-screen pink-theme">
                <div class="phone-header">Nora Beauty</div>
                <div class="phone-body">
                  <div class="phone-card">Choisissez une catégorie</div>
                  <div class="phone-card">Cheveux · Ongles · Maquillage</div>
                  <div class="phone-card">Coupe & Brushing - 2 500 F</div>
                  <div class="phone-card">Lissage - 9 000 F</div>
                </div>
              </div>
              <p class="phone-caption">Catégorie et sélection du service</p>
            </div>
            <div class="phone-mockup">
              <div class="phone-screen pink-theme">
                <div class="phone-header">Choisissez un créneau</div>
                <div class="phone-body">
                  <div class="phone-card">Mar 09 - 10h00</div>
                  <div class="phone-card">Service: Coupe & Brushing</div>
                  <div class="phone-card">Total: 2 500 FCFA</div>
                  <div class="confirm-box">Confirmer la RDV</div>
                </div>
              </div>
              <p class="phone-caption">Date, heure, paiement et confirmation</p>
            </div>
          </div>
        </aside>

      </div>
    </form>
  </main>
</div>

<script src="../RSVAdmin.js"></script>
<script>
/* Update business_type_label hidden field when type changes */
document.querySelectorAll('input[name="business_type"]').forEach(function(radio) {
  radio.addEventListener('change', function() {
    var labels = {
      salon:'Salon de beauté', restaurant:'Restaurant', hotel:'Hôtellerie',
      medical:'Clinique / Médical', barber:'Barbier', fitness:'Sport & Fitness',
      photo:'Photographie', law:'Avocat / Cabinet', coach:'Coach', other:'Autre'
    };
    var lbl = document.getElementById('business_type_label');
    if (lbl) lbl.value = labels[this.value] || this.value;
  });
});

function previewLogo(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var img = document.getElementById('out-logo-preview');
      var ph  = document.getElementById('out-logo-placeholder');
      if (img) { img.src = e.target.result; img.style.display = 'block'; }
      if (ph)  { ph.style.display = 'none'; }
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function copyLink() {
  var link = document.getElementById('out-link');
  if (!link) return;
  if (navigator.clipboard) {
    navigator.clipboard.writeText('https://' + link.textContent.trim())
      .then(function() { alert('✓ Lien copié !'); });
  }
}
</script>
</body>
</html>