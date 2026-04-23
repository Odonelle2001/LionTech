<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../db.php';

$currentPage = 'add_business';

/* ── Correspondance couleur principale → couleur de fond légère ── */
$theme_bg_map = [
  '#D4447A' => '#FFF0F8', '#0A0A0A' => '#FAFAF8', '#0EA5E9' => '#F0F9FF',
  '#059669' => '#F0FDF4', '#E07B39' => '#FFF8F3', '#7C3AED' => '#F5F3FF',
  '#DC2626' => '#FFF5F5', '#1B4332' => '#F9F6F0', '#C9A84C' => '#FFF9EE',
];

$save_success = false;
$save_error   = '';
$saved_slug   = '';
$prefill      = [];

/* ── Helpers PHP ── */
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pre(string $k, string $d = ''): string { global $prefill; return h((string)($prefill[$k] ?? $d)); }

function make_init(string $n): string {
  $i = '';
  foreach (array_slice(preg_split('/\s+/', trim($n)), 0, 2) as $w)
    if ($w !== '') $i .= strtoupper(substr($w, 0, 1));
  return $i ?: 'B';
}
function cslug(string $s, string $fb = ''): string {
  $s = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9\-]/', '-', strtolower(trim($s)))), '-');
  if ($s === '' && $fb !== '') $s = cslug($fb);
  return $s ?: 'business-' . time();
}
function uslug(PDO $p, string $base): string {
  $slug = $base; $i = 1;
  while (true) {
    $st = $p->prepare("SELECT id FROM businesses WHERE slug=?");
    $st->execute([$slug]);
    if (!$st->fetch()) return $slug;
    $slug = $base . '-' . $i++;
  }
}
function save_logo(array $f, string $slug): ?string {
  if (empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) return null;
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'])) return null;
  $dir = dirname(__DIR__) . '/uploads/' . $slug . '/';
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $dest = $dir . 'logo.' . $ext;
  return move_uploaded_file($f['tmp_name'], $dest) ? 'uploads/' . $slug . '/logo.' . $ext : null;
}
function save_avatar(array $f, string $slug): ?string {
  if (empty($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) return null;
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'])) return null;
  $dir = dirname(__DIR__) . '/uploads/' . $slug . '/';
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $dest = $dir . 'avatar.' . $ext;
  return move_uploaded_file($f['tmp_name'], $dest) ? 'uploads/' . $slug . '/avatar.' . $ext : null;
}
function ins_avail(PDO $p, int $id): void {
  $days = [
    ['Dimanche','Sunday',0,0,null,null],
    ['Lundi','Monday',1,1,'08:00:00','18:00:00'],
    ['Mardi','Tuesday',2,1,'08:00:00','18:00:00'],
    ['Mercredi','Wednesday',3,1,'08:00:00','18:00:00'],
    ['Jeudi','Thursday',4,1,'08:00:00','18:00:00'],
    ['Vendredi','Friday',5,1,'08:00:00','19:00:00'],
    ['Samedi','Saturday',6,1,'09:00:00','17:00:00'],
  ];
  $st = $p->prepare("INSERT INTO availability (business_id,day_name,day_en,day_index,is_open,open_time,close_time) VALUES (?,?,?,?,?,?,?)");
  foreach ($days as $d) $st->execute([$id, ...$d]);
}
function tlabel(string $v, string $o = ''): string {
  return [
    'salon'      => 'Salon de beauté', 'restaurant' => 'Restaurant',
    'hotel'      => 'Hôtellerie',      'medical'    => 'Clinique / Médical',
    'barber'     => 'Barbier',         'fitness'    => 'Sport & Fitness',
    'photo'      => 'Photographie',    'law'        => 'Avocat / Cabinet',
    'coach'      => 'Coach',           'other'      => trim($o) ?: 'Autre',
  ][$v] ?? $v;
}

/* ── Sauvegarde du formulaire ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_submitted'])) {
  try {
    $pdo->beginTransaction();

    $name = trim($_POST['business_name'] ?? '');
    if ($name === '') throw new Exception('Le nom du business est obligatoire.');

    $slug = uslug($pdo, cslug($_POST['subdomain'] ?? '', $name));
    $tc   = $_POST['primary_color'] ?? '#C9A84C';
    $tbg  = $theme_bg_map[$tc] ?? '#FFF9EE';
    $logo = save_logo($_FILES['logo'] ?? [], $slug);
    $avatar = save_avatar($_FILES['avatar_photo'] ?? [], $slug);
    $bs   = $_POST['booking_style'] ?? 'individual';
    $emp  = max(1, (int)($_POST['employee_count'] ?? 1));
    $slot = (int)($_POST['slot_duration_' . $bs] ?? 45);
    $wa_owner = preg_replace('/\D/', '', $_POST['owner_whatsapp'] ?? '');
    $pwd  = trim($_POST['temp_password'] ?? '');

    $pdo->prepare("INSERT INTO businesses
      (slug,name,initials,type,description,city,neighborhood,whatsapp,logo,avatar_photo,
       theme_color,theme_bg,primary_color,secondary_color,button_color,
       text_color,background_color,border_color,navbar_style,footer_style,
       show_biz_logo,show_lt_logo,lt_footer_only,language,booking_style,
       employee_count,slot_duration,show_prices,show_connexion_btn,
       svc_display_style,gal_display_mode,gal_max_photos,
       about_position,global_font,global_font_size,btn_style,
       bg_texture,plan,status,internal_notes)
      VALUES
      (:slug,:name,:init,:type,:desc,:city,:qtr,:wa,:logo,:avatar,
       :tc,:tbg,:pc,:sc,:bc,:txtc,:bgc,:bdc,:ns,:fs,
       :sbl,:sll,:lfo,:lang,:bs,:emp,:slot,:sp,:scb,
       :sds,:gdm,:gmp,
       :apos,:gfont,:gfsz,:btnst,
       :bgtex,:plan,'new',:notes)")
    ->execute([
      ':slug'  => $slug, ':name' => $name, ':init' => make_init($name),
      ':type'  => tlabel($_POST['business_type'] ?? 'salon', $_POST['other_type'] ?? ''),
      ':desc'  => trim($_POST['description'] ?? ''),
      ':city'  => trim($_POST['city'] ?? ''), ':qtr' => trim($_POST['quarter'] ?? ''),
      ':wa'    => preg_replace('/\D/', '', $_POST['whatsapp'] ?? ''),
      ':logo'  => $logo, ':avatar' => $avatar,
      ':tc'    => $tc, ':tbg' => $tbg, ':pc' => $tc,
      ':sc'    => $_POST['secondary_color']  ?? '#0A0A0A',
      ':bc'    => $_POST['button_color']     ?? $tc,
      ':txtc'  => $_POST['text_color']       ?? '#222222',
      ':bgc'   => $_POST['background_color'] ?? '#ffffff',
      ':bdc'   => $_POST['border_color']     ?? '#e5e7eb',
      ':ns'    => $_POST['navbar_style']     ?? 'light',
      ':fs'    => $_POST['footer_style']     ?? 'minimal',
      ':sbl'   => isset($_POST['show_business_logo'])        ? 1 : 0,
      ':sll'   => isset($_POST['show_liontech_logo'])        ? 1 : 0,
      ':lfo'   => isset($_POST['show_liontech_footer_only']) ? 1 : 0,
      ':lang'  => $_POST['site_language']  ?? 'fr',
      ':bs'    => $bs, ':emp' => $emp, ':slot' => $slot,
      ':sp'    => isset($_POST['show_prices'])        ? 1 : 0,
      ':scb'   => isset($_POST['show_connexion_btn']) ? 1 : 0,
      ':sds'   => $_POST['svc_display_style']  ?? 'list',
      ':gdm'   => $_POST['gal_display_mode']   ?? 'grid',
      ':gmp'   => (int)($_POST['gal_max_photos'] ?? 9),
      ':apos'  => $_POST['about_position']  ?? 'after',
      ':gfont' => $_POST['global_font']     ?? 'system-ui',
      ':gfsz'  => $_POST['global_font_size'] ?? '1rem',
      ':btnst' => $_POST['btn_style']       ?? 'filled',
      ':bgtex' => $_POST['bg_texture']      ?? 'none',
      ':plan'  => $_POST['plan']            ?? 'basic',
      ':notes' => trim($_POST['internal_notes'] ?? ''),
    ]);

    $bizId = (int)$pdo->lastInsertId();

    /* Créer le compte propriétaire avec WhatsApp comme identifiant */
    if ($wa_owner && $pwd) {
      $pdo->prepare("INSERT INTO owners (business_id, whatsapp, password_hash, name) VALUES (?,?,?,?)")
          ->execute([$bizId, $wa_owner, password_hash($pwd, PASSWORD_DEFAULT), $name]);
    }

    ins_avail($pdo, $bizId);
    $pdo->commit();

    $save_success = true;
    $saved_slug   = $slug;
    $prefill = ['name' => $name, 'slug' => $slug, 'owner_whatsapp' => $wa_owner, 'owner_password' => $pwd];

  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $save_error = $e->getMessage();
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ajouter un business — LionRDV</title>
  <link rel="stylesheet" href="../sidebar.css">
  <link rel="stylesheet" href="AjouterBussiness.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app-layout">
  <?php include __DIR__ . '/../sidebar.php'; ?>

  <main class="ab-main">

    <!-- TOP BAR -->
    <header class="ab-topbar">
      <div class="ab-topbar-left">
        <div class="ab-brand">
          <span class="ab-lion">🦁</span>
          <span class="ab-brand-name">LionRDV</span>
          <span class="ab-brand-badge">by LionTech</span>
        </div>
        <p class="ab-topbar-sub">Créez la page du commerce en quelques minutes</p>
      </div>
      <div class="ab-topbar-actions">
        <a href="../RSVAdmin.php" class="ab-btn-back">
          <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
        <button type="submit" form="biz-form" class="ab-btn-create">
          <i class="fa-solid fa-check"></i> Créer le compte client
        </button>
      </div>
    </header>

    <?php if ($save_error): ?>
    <div class="ab-error-bar">
      <i class="fa-solid fa-circle-exclamation"></i> <?= h($save_error) ?>
    </div>
    <?php endif; ?>

    <!-- BUILDER LAYOUT: 40% form | 60% preview -->
    <div class="ab-builder">

      <!-- ═══════ FORM COLUMN ═══════ -->
      <div class="ab-form-col" id="ab-form-col">
        <form id="biz-form" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="form_submitted" value="1">

          <!-- ── S1: Informations ── -->
          <div class="ab-card ab-sec" id="sec-info">
            <div class="ab-card-head">
              <div class="ab-step">1</div>
              <div>
                <h2 class="ab-card-title">Informations</h2>
                <p class="ab-card-sub">Nom, téléphone et adresse</p>
              </div>
            </div>
            <div class="ab-grid">
              <div class="ab-field">
                <label class="ab-label">Nom du commerce *</label>
                <input class="ab-input" type="text" id="inp-name" name="business_name" placeholder="Ex: Barbershop Elite" required oninput="syncPreview()">
              </div>
              <div class="ab-field">
                <label class="ab-label">Sous-domaine *</label>
                <div class="ab-prefix">
                  <span>lionrdv.cm/</span>
                  <input type="text" name="subdomain" placeholder="barbershop-elite" required oninput="syncPreview()">
                </div>
              </div>
              <div class="ab-field">
                <label class="ab-label">WhatsApp du commerce</label>
                <div class="ab-prefix"><span>+237</span><input type="tel" id="inp-wa" name="whatsapp" placeholder="6XX XXX XXX"></div>
              </div>
              <div class="ab-field">
                <label class="ab-label">Ville</label>
                <input class="ab-input" type="text" id="inp-city" name="city" placeholder="Douala" oninput="syncPreview()">
              </div>
              <div class="ab-field">
                <label class="ab-label">Quartier</label>
                <input class="ab-input" type="text" name="quarter" placeholder="Akwa">
              </div>
              <div class="ab-field ab-field-full">
                <label class="ab-label">Description (À propos)</label>
                <textarea class="ab-textarea" id="inp-about" name="description" rows="3" placeholder="Votre barbier de confiance depuis 2018..." oninput="syncPreview()"></textarea>
              </div>
            </div>
          </div>

          <!-- ── S2: Langue ── -->
          <div class="ab-card ab-sec" id="sec-lang">
            <div class="ab-card-head">
              <div class="ab-step">2</div>
              <div>
                <h2 class="ab-card-title">Langue du site</h2>
                <p class="ab-card-sub">Français, Anglais ou Bilingue</p>
              </div>
            </div>
            <div class="ab-lang-grid">
              <label class="ab-lang-opt ab-lang-on">
                <input type="radio" name="site_language" value="fr" checked>
                <span class="ab-lang-flag">🇫🇷</span>
                <span class="ab-lang-nm">Français</span>
              </label>
              <label class="ab-lang-opt">
                <input type="radio" name="site_language" value="en">
                <span class="ab-lang-flag">🇬🇧</span>
                <span class="ab-lang-nm">English</span>
              </label>
              <label class="ab-lang-opt">
                <input type="radio" name="site_language" value="bilingual">
                <span class="ab-lang-flag">🌐</span>
                <span class="ab-lang-nm">Bilingue (FR)</span>
              </label>
            </div>
          </div>

          <!-- ── S3: Couleur du thème ── -->
          <div class="ab-card ab-sec" id="sec-colors">
            <div class="ab-card-head">
              <div class="ab-step">3</div>
              <div>
                <h2 class="ab-card-title">Couleur du thème</h2>
                <p class="ab-card-sub">Couleur principale de la page</p>
              </div>
            </div>
            <!-- Palette prédéfinie -->
            <div class="ab-clr-dots" id="ab-clr-dots">
              <button type="button" class="ab-clr-dot" style="background:#9B59B6" data-color="#9B59B6" onclick="setColor('#9B59B6',this)"></button>
              <button type="button" class="ab-clr-dot ab-clr-on" style="background:#C9A84C" data-color="#C9A84C" onclick="setColor('#C9A84C',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#3498DB" data-color="#3498DB" onclick="setColor('#3498DB',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#1ABC9C" data-color="#1ABC9C" onclick="setColor('#1ABC9C',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#E74C3C" data-color="#E74C3C" onclick="setColor('#E74C3C',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#E91E8C" data-color="#E91E8C" onclick="setColor('#E91E8C',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#E67E22" data-color="#E67E22" onclick="setColor('#E67E22',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#607D8B" data-color="#607D8B" onclick="setColor('#607D8B',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#1A237E" data-color="#1A237E" onclick="setColor('#1A237E',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#009688" data-color="#009688" onclick="setColor('#009688',this)"></button>
              <button type="button" class="ab-clr-dot" style="background:#7B1FA2" data-color="#7B1FA2" onclick="setColor('#7B1FA2',this)"></button>
            </div>
            <!-- Couleurs sauvegardées -->
            <div class="ab-label" style="margin-top:.75rem;">Mes couleurs sauvegardées</div>
            <div class="ab-saved-colors" id="ab-saved-colors">
              <span class="ab-saved-empty">Aucune couleur sauvegardée</span>
            </div>
            <!-- Ajout couleur personnalisée -->
            <div class="ab-clr-add">
              <input type="color" id="ab-clr-picker" value="#C9A84C" oninput="document.getElementById('ab-clr-hex').value=this.value">
              <input class="ab-clr-hex" id="ab-clr-hex" value="#C9A84C" maxlength="7" oninput="document.getElementById('ab-clr-picker').value=this.value">
              <button type="button" class="ab-clr-save-btn" onclick="saveColor()">+ Sauvegarder</button>
            </div>
            <input type="hidden" id="primary_color" name="primary_color" value="#C9A84C">
            <p class="ab-hint">Double-cliquez sur une couleur sauvegardée pour la supprimer</p>
          </div>

          <!-- ── S4: Logo, Couverture & Navbar ── -->
          <div class="ab-card ab-sec" id="sec-cover">
            <div class="ab-card-head">
              <div class="ab-step">4</div>
              <div>
                <h2 class="ab-card-title">Couverture — Overlay & Navbar</h2>
                <p class="ab-card-sub">Logo, photo, avatar, éléments visibles</p>
              </div>
            </div>
            <div class="ab-grid">
              <!-- Logo -->
              <div class="ab-field">
                <label class="ab-label">Logo navbar</label>
                <div class="ab-upload" onclick="document.getElementById('logo-f').click()">
                  <div class="ab-upload-thumb" id="logo-thumb">
                    <img id="logo-img" alt="">
                    <i class="fa-regular fa-image ab-upload-icon"></i>
                  </div>
                  <div>
                    <div class="ab-upload-title">Uploader le logo</div>
                    <div class="ab-upload-sub">JPG, PNG, WEBP</div>
                  </div>
                </div>
                <input type="file" id="logo-f" name="logo" accept="image/*" onchange="handleLogo(this)">
              </div>
              <!-- Cover -->
              <div class="ab-field">
                <label class="ab-label">Photo de couverture</label>
                <div class="ab-upload" onclick="document.getElementById('cover-f').click()">
                  <div class="ab-upload-thumb" id="cover-thumb">
                    <img id="cover-img" alt="">
                    <i class="fa-regular fa-image ab-upload-icon"></i>
                  </div>
                  <div>
                    <div class="ab-upload-title">Photo de couverture</div>
                    <div class="ab-upload-sub">Si vide → couleur principale</div>
                  </div>
                </div>
                <input type="file" id="cover-f" name="cover_photo" accept="image/*" onchange="handleCover(this)">
              </div>
            </div>

            <!-- Avatar propriétaire -->
            <div class="ab-toggle-row">
              <span class="ab-toggle-lbl">Avatar propriétaire sur la couverture</span>
              <label class="ab-toggle">
                <input type="checkbox" id="tog-avatar" name="show_avatar" onchange="toggleAvatar(this)">
                <span class="ab-toggle-knob"></span>
              </label>
            </div>
            <div id="avatar-wrap" style="display:none;margin-top:.5rem;">
              <div class="ab-upload" onclick="document.getElementById('avatar-f').click()">
                <div class="ab-upload-thumb ab-upload-round" id="avatar-thumb">
                  <img id="avatar-img" alt="">
                  <i class="fa-solid fa-user ab-upload-icon"></i>
                </div>
                <div>
                  <div class="ab-upload-title">Photo de profil propriétaire</div>
                  <div class="ab-upload-sub">Petit rond en bas à droite de la couverture</div>
                </div>
              </div>
              <input type="file" id="avatar-f" name="avatar_photo" accept="image/*" onchange="handleAvatar(this)">
            </div>

            <!-- Éléments overlay -->
            <div class="ab-label" style="margin-top:.75rem;">Éléments visibles sur la couverture</div>
            <?php
            $overlayItems = [
              ['Nom du commerce',    'ov_name'],
              ['Type de commerce',   'ov_type'],
              ['Adresse',            'ov_address'],
              ['Contact WhatsApp',   'ov_whatsapp'],
            ];
            foreach ($overlayItems as [$lbl, $name]): ?>
            <div class="ab-ov-item">
              <span class="ab-ov-lbl"><?= $lbl ?></span>
              <div class="ab-ov-pos">
                <button type="button" class="ab-pos-btn ab-pos-on" data-field="<?= $name ?>" onclick="selPos(this,'centre')">Centre</button>
                <button type="button" class="ab-pos-btn" data-field="<?= $name ?>" onclick="selPos(this,'bas')">Bas</button>
                <button type="button" class="ab-pos-btn" data-field="<?= $name ?>" onclick="selPos(this,'masqué')">Masqué</button>
              </div>
              <input type="hidden" name="<?= $name ?>" value="centre">
            </div>
            <?php endforeach; ?>

            <!-- Navbar options -->
            <div class="ab-label" style="margin-top:.75rem;">Navbar</div>
            <div class="ab-grid">
              <div class="ab-field">
                <label class="ab-label">Style navbar</label>
                <select class="ab-select" name="navbar_style" onchange="syncPreview()">
                  <option value="light">Claire (fond blanc)</option>
                  <option value="dark">Sombre</option>
                  <option value="transparent">Transparente</option>
                </select>
              </div>
            </div>
            <div class="ab-toggle-row">
              <span class="ab-toggle-lbl">Afficher le logo dans la navbar</span>
              <label class="ab-toggle"><input type="checkbox" name="show_business_logo" checked onchange="syncPreview()"><span class="ab-toggle-knob"></span></label>
            </div>
            <div class="ab-toggle-row">
              <span class="ab-toggle-lbl">Badge LionTech visible</span>
              <label class="ab-toggle"><input type="checkbox" name="show_liontech_logo" checked onchange="syncPreview()"><span class="ab-toggle-knob"></span></label>
            </div>
            <!-- BOUTON CONNEXION -->
            <div class="ab-toggle-row">
              <span class="ab-toggle-lbl">Bouton Connexion dans la navbar</span>
              <label class="ab-toggle"><input type="checkbox" id="tog-conn" name="show_connexion_btn" onchange="toggleConnBtn(this)"><span class="ab-toggle-knob"></span></label>
            </div>
          </div>

          <!-- ── S5: Typographie globale ── -->
          <div class="ab-card ab-sec" id="sec-typo">
            <div class="ab-card-head">
              <div class="ab-step">5</div>
              <div>
                <h2 class="ab-card-title">Typographie globale</h2>
                <p class="ab-card-sub">Police, taille et couleur sur tout le site</p>
              </div>
            </div>
            <!-- Polices prédéfinies -->
            <div class="ab-label">Police principale</div>
            <div class="ab-font-grid" id="ab-font-grid">
              <button type="button" class="ab-font-opt ab-font-on" onclick="selFont(this,'system-ui')">
                <span class="ab-font-sample" style="font-family:system-ui;">Aa</span>
                <span class="ab-font-nm">Système</span>
              </button>
              <button type="button" class="ab-font-opt" onclick="selFont(this,'Georgia, serif')">
                <span class="ab-font-sample" style="font-family:Georgia,serif;">Aa</span>
                <span class="ab-font-nm">Serif</span>
              </button>
              <button type="button" class="ab-font-opt" onclick="selFont(this,'monospace')">
                <span class="ab-font-sample" style="font-family:monospace;">Aa</span>
                <span class="ab-font-nm">Mono</span>
              </button>
              <button type="button" class="ab-font-opt" onclick="selFont(this,'cursive')">
                <span class="ab-font-sample" style="font-family:cursive;">Aa</span>
                <span class="ab-font-nm">Script</span>
              </button>
            </div>
            <input type="hidden" id="global_font" name="global_font" value="system-ui">
            <!-- Police Google Fonts personnalisée -->
            <button type="button" class="ab-add-custom" onclick="toggleCustomFont()">
              <span class="ab-plus">+</span> Ajouter ma propre police (Google Fonts)
            </button>
            <div class="ab-custom-row" id="custom-font-row">
              <input class="ab-input" id="custom-font-inp" placeholder="Ex: Playfair Display">
              <button type="button" class="ab-btn-apply" onclick="applyCustomFont()">Appliquer</button>
            </div>
            <!-- Taille globale -->
            <div class="ab-grid" style="margin-top:.75rem;">
              <div class="ab-field">
                <label class="ab-label">Taille du texte</label>
                <div class="ab-size-grid" id="ab-size-grid">
                  <button type="button" class="ab-sz" onclick="selSize(this,'0.85rem')">XS</button>
                  <button type="button" class="ab-sz ab-sz-on" onclick="selSize(this,'1rem')">M</button>
                  <button type="button" class="ab-sz" onclick="selSize(this,'1.125rem')">L</button>
                  <button type="button" class="ab-sz" onclick="selSize(this,'1.25rem')">XL</button>
                </div>
                <input type="hidden" id="global_font_size" name="global_font_size" value="1rem">
              </div>
              <div class="ab-field">
                <label class="ab-label">Poids du texte</label>
                <div class="ab-size-grid" id="ab-weight-grid">
                  <button type="button" class="ab-sz ab-sz-on" style="font-weight:400;" onclick="selWeight(this,'400')">Normal</button>
                  <button type="button" class="ab-sz" style="font-weight:700;" onclick="selWeight(this,'700')">Gras</button>
                </div>
                <input type="hidden" id="global_font_weight" name="global_font_weight" value="400">
              </div>
            </div>
            <!-- Couleurs de texte -->
            <div class="ab-grid" style="margin-top:.75rem;">
              <div class="ab-field">
                <label class="ab-label">Couleur du texte</label>
                <div class="ab-clr-row">
                  <input type="color" class="ab-clr-sw" id="txt-clr" name="text_color" value="#111111" oninput="document.getElementById('txt-clr-hex').value=this.value;syncPreview()">
                  <input class="ab-input ab-mono-inp" id="txt-clr-hex" value="#111111" maxlength="7" oninput="document.getElementById('txt-clr').value=this.value;syncPreview()">
                </div>
              </div>
              <div class="ab-field">
                <label class="ab-label">Couleur des titres</label>
                <div class="ab-clr-row">
                  <input type="color" class="ab-clr-sw" id="ttl-clr" name="title_color" value="#000000">
                  <input class="ab-input ab-mono-inp" value="#000000" maxlength="7" oninput="document.getElementById('ttl-clr').value=this.value">
                </div>
              </div>
            </div>
          </div>

          <!-- ── S6: Fond & Texture ── -->
          <div class="ab-card ab-sec" id="sec-bg">
            <div class="ab-card-head">
              <div class="ab-step">6</div>
              <div>
                <h2 class="ab-card-title">Fond & texture</h2>
                <p class="ab-card-sub">Rend chaque page unique</p>
              </div>
            </div>
            <div class="ab-bg-grid" id="ab-bg-grid">
              <button type="button" class="ab-bg-opt ab-bg-on" data-tex="none" data-bg="#ffffff" onclick="selBg(this)" style="background:#fff;border-color:#C9A84C;">
                <span class="ab-bg-lbl">Uni</span>
              </button>
              <button type="button" class="ab-bg-opt" data-tex="dots" data-bg="#ffffff" onclick="selBg(this)"
                style="background-image:radial-gradient(#bbb 1px,transparent 1px);background-size:6px 6px;background-color:#fff;">
                <span class="ab-bg-lbl">Points</span>
              </button>
              <button type="button" class="ab-bg-opt" data-tex="lines" data-bg="#ffffff" onclick="selBg(this)"
                style="background:repeating-linear-gradient(45deg,#e0e0e0 0,#e0e0e0 1px,#fff 0,#fff 9px);">
                <span class="ab-bg-lbl">Lignes</span>
              </button>
              <button type="button" class="ab-bg-opt" data-tex="grid" data-bg="#ffffff" onclick="selBg(this)"
                style="background:repeating-linear-gradient(0deg,#eee 0,#eee 1px,transparent 0,transparent 14px),repeating-linear-gradient(90deg,#eee 0,#eee 1px,transparent 0,transparent 14px);background-color:#fff;">
                <span class="ab-bg-lbl">Grille</span>
              </button>
              <button type="button" class="ab-bg-opt" data-tex="none" data-bg="#FFF8EE" onclick="selBg(this)" style="background:#FFF8EE;">
                <span class="ab-bg-lbl">Chaud</span>
              </button>
              <button type="button" class="ab-bg-opt" data-tex="none" data-bg="#F0F9FF" onclick="selBg(this)" style="background:#F0F9FF;">
                <span class="ab-bg-lbl">Froid</span>
              </button>
              <button type="button" class="ab-bg-opt" data-tex="none" data-bg="#F0FDF4" onclick="selBg(this)" style="background:#F0FDF4;">
                <span class="ab-bg-lbl">Menthe</span>
              </button>
              <button type="button" class="ab-bg-opt" data-tex="none" data-bg="#FDF4FF" onclick="selBg(this)" style="background:#FDF4FF;">
                <span class="ab-bg-lbl">Lilas</span>
              </button>
            </div>
            <input type="hidden" id="bg_texture" name="bg_texture" value="none">
            <input type="hidden" id="background_color" name="background_color" value="#ffffff">
            <!-- Fond CSS personnalisé -->
            <button type="button" class="ab-add-custom" onclick="toggleCustomBg()">
              <span class="ab-plus">+</span> Ajouter mon propre fond
            </button>
            <div class="ab-custom-row" id="custom-bg-row">
              <input class="ab-input" id="custom-bg-inp" placeholder="Ex: #FFF3E0 ou repeating-linear-gradient(...)">
              <button type="button" class="ab-btn-apply" onclick="applyCustomBg()">OK</button>
            </div>
            <!-- Footer -->
            <div class="ab-field" style="margin-top:.75rem;">
              <label class="ab-label">Style footer</label>
              <select class="ab-select" name="footer_style" onchange="syncPreview()">
                <option value="minimal">Minimal</option>
                <option value="dark">Sombre</option>
                <option value="branded">Coloré</option>
                <option value="rich">Complet</option>
              </select>
            </div>
          </div>

          <!-- ── S7: Horaires ── -->
          <div class="ab-card ab-sec" id="sec-hours">
            <div class="ab-card-head">
              <div class="ab-step">7</div>
              <div>
                <h2 class="ab-card-title">Horaires d'ouverture</h2>
                <p class="ab-card-sub">Par jour — modifiable par le propriétaire</p>
              </div>
            </div>
            <?php
            $days = [
              ['Lundi','monday','08:00','18:00',true],
              ['Mardi','tuesday','08:00','18:00',true],
              ['Mercredi','wednesday','08:00','18:00',true],
              ['Jeudi','thursday','08:00','18:00',true],
              ['Vendredi','friday','08:00','18:00',true],
              ['Samedi','saturday','09:00','14:00',true],
              ['Dimanche','sunday','','',false],
            ];
            foreach ($days as [$fr,$en,$open,$close,$isOpen]): ?>
            <div class="ab-day-row" id="day-<?= $en ?>">
              <label class="ab-day-tog">
                <input type="checkbox" name="day_open_<?= $en ?>" <?= $isOpen ? 'checked' : '' ?> onchange="togDay('<?= $en ?>',this.checked)">
                <span class="ab-day-tog-knob"></span>
              </label>
              <span class="ab-day-nm <?= !$isOpen ? 'ab-day-off' : '' ?>"><?= $fr ?></span>
              <?php if ($isOpen): ?>
              <input class="ab-time-inp" type="time" name="open_<?= $en ?>" value="<?= $open ?>">
              <span class="ab-day-sep">–</span>
              <input class="ab-time-inp" type="time" name="close_<?= $en ?>" value="<?= $close ?>">
              <?php else: ?>
              <span class="ab-day-ferme">Fermé</span>
              <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <p class="ab-hint">Le propriétaire peut modifier les horaires depuis son espace</p>
            <div class="ab-label" style="margin-top:.75rem;">Style d'affichage des horaires</div>
            <div class="ab-hor-style-grid" id="ab-hor-grid">
              <button type="button" class="ab-hor-opt ab-hor-on" onclick="selHorStyle(this,'list')">Liste</button>
              <button type="button" class="ab-hor-opt" onclick="selHorStyle(this,'cards')">Cartes</button>
              <button type="button" class="ab-hor-opt" onclick="selHorStyle(this,'badges')">Badges</button>
            </div>
            <input type="hidden" id="hor_style" name="hor_style" value="list">
          </div>

          <!-- ── S8: Galerie ── -->
          <div class="ab-card ab-sec" id="sec-gallery">
            <div class="ab-card-head">
              <div class="ab-step">8</div>
              <div>
                <h2 class="ab-card-title">Galerie Photos</h2>
                <p class="ab-card-sub">Apparence et limites définies par l'admin</p>
              </div>
            </div>
            <div class="ab-field">
              <label class="ab-label">Nombre max de photos (pour le propriétaire)</label>
              <div class="ab-gal-max" id="ab-gal-max">
                <button type="button" class="ab-gmb" onclick="selGalMax(this,3)">3</button>
                <button type="button" class="ab-gmb" onclick="selGalMax(this,6)">6</button>
                <button type="button" class="ab-gmb ab-gmb-on" onclick="selGalMax(this,9)">9</button>
                <button type="button" class="ab-gmb" onclick="selGalMax(this,12)">12</button>
                <button type="button" class="ab-gmb" onclick="selGalMax(this,24)">24</button>
              </div>
              <input type="hidden" id="gal_max_photos" name="gal_max_photos" value="9">
            </div>
            <div class="ab-field">
              <label class="ab-label">Mode d'affichage</label>
              <div class="ab-gal-modes" id="ab-gal-modes">
                <button type="button" class="ab-gm-opt ab-gm-on" onclick="selGalMode(this,'grid')">
                  <span class="ab-gm-ico">▦</span>
                  <div><div class="ab-gm-nm">Grille</div><div class="ab-gm-sub">Grande + petites</div></div>
                </button>
                <button type="button" class="ab-gm-opt" onclick="selGalMode(this,'slideshow')">
                  <span class="ab-gm-ico">▶</span>
                  <div><div class="ab-gm-nm">Diaporama</div><div class="ab-gm-sub">Défilement auto</div></div>
                </button>
                <button type="button" class="ab-gm-opt" onclick="selGalMode(this,'portrait')">
                  <span class="ab-gm-ico">🖼</span>
                  <div><div class="ab-gm-nm">Portrait</div><div class="ab-gm-sub">Format vertical</div></div>
                </button>
                <button type="button" class="ab-gm-opt" onclick="selGalMode(this,'circle')">
                  <span class="ab-gm-ico">⭕</span>
                  <div><div class="ab-gm-nm">Cercle</div><div class="ab-gm-sub">Photos rondes</div></div>
                </button>
                <button type="button" class="ab-gm-opt" onclick="selGalMode(this,'square')">
                  <span class="ab-gm-ico">■</span>
                  <div><div class="ab-gm-nm">Carré</div><div class="ab-gm-sub">Format carré</div></div>
                </button>
              </div>
              <input type="hidden" id="gal_display_mode" name="gal_display_mode" value="grid">
            </div>
            <div class="ab-toggle-row">
              <span class="ab-toggle-lbl">Bordure sur les photos</span>
              <label class="ab-toggle"><input type="checkbox" name="gal_border"><span class="ab-toggle-knob"></span></label>
            </div>
          </div>

          <!-- ── S9: Style des Services ── -->
          <div class="ab-card ab-sec" id="sec-svc">
            <div class="ab-card-head">
              <div class="ab-step">9</div>
              <div>
                <h2 class="ab-card-title">Style des Services</h2>
                <p class="ab-card-sub">Comment les services s'affichent</p>
              </div>
            </div>
            <div class="ab-svc-styles">
              <button type="button" class="ab-ss-item" onclick="selSvcStyle(this,'cards')">
                <span class="ab-ss-ico">🃏</span>
                <div><div class="ab-ss-nm">Cards</div><div class="ab-ss-sub">Grille de cartes</div></div>
              </button>
              <button type="button" class="ab-ss-item" onclick="selSvcStyle(this,'tabs')">
                <span class="ab-ss-ico">📑</span>
                <div><div class="ab-ss-nm">Onglets</div><div class="ab-ss-sub">Navigation par onglets</div></div>
              </button>
              <button type="button" class="ab-ss-item" onclick="selSvcStyle(this,'buttons')">
                <span class="ab-ss-ico">🔘</span>
                <div><div class="ab-ss-nm">Boutons</div><div class="ab-ss-sub">Liste de boutons</div></div>
              </button>
              <button type="button" class="ab-ss-item" onclick="selSvcStyle(this,'text')">
                <span class="ab-ss-ico">📝</span>
                <div><div class="ab-ss-nm">Texte simple</div><div class="ab-ss-sub">Liste avec puces</div></div>
              </button>
              <button type="button" class="ab-ss-item ab-ss-on" onclick="selSvcStyle(this,'pills')">
                <span class="ab-ss-ico">💊</span>
                <div><div class="ab-ss-nm">Pills</div><div class="ab-ss-sub">Tags arrondis colorés</div></div>
              </button>
            </div>
            <input type="hidden" id="svc_display_style" name="svc_display_style" value="pills">
          </div>

          <!-- ── S10: À propos ── -->
          <div class="ab-card ab-sec" id="sec-about">
            <div class="ab-card-head">
              <div class="ab-step">10</div>
              <div>
                <h2 class="ab-card-title">À propos</h2>
                <p class="ab-card-sub">Style de la section description</p>
              </div>
            </div>
            <div class="ab-grid">
              <div class="ab-field">
                <label class="ab-label">Police (override section)</label>
                <select class="ab-select" name="about_font" onchange="syncPreview()">
                  <option value="">Même que le site</option>
                  <option value="Georgia,serif">Serif élégant</option>
                  <option value="cursive">Script</option>
                  <option value="monospace">Mono</option>
                </select>
              </div>
              <div class="ab-field">
                <label class="ab-label">Taille du texte</label>
                <select class="ab-select" name="about_font_size" onchange="syncPreview()">
                  <option value="1rem">Normale (1rem)</option>
                  <option value="1.125rem">Grande (1.125rem)</option>
                  <option value="1.25rem">Très grande (1.25rem)</option>
                </select>
              </div>
              <div class="ab-field">
                <label class="ab-label">Couleur du texte À propos</label>
                <div class="ab-clr-row">
                  <input type="color" class="ab-clr-sw" name="about_text_color" value="#444444">
                  <input class="ab-input ab-mono-inp" value="#444444" maxlength="7">
                </div>
              </div>
              <div class="ab-field">
                <label class="ab-label">Position</label>
                <select class="ab-select" name="about_position" id="about-pos" onchange="syncPreview()">
                  <option value="before">Avant le bouton RDV</option>
                  <option value="after" selected>Après le bouton RDV</option>
                </select>
              </div>
            </div>
          </div>

          <!-- ── S11: Bouton RDV ── -->
          <div class="ab-card ab-sec" id="sec-btn">
            <div class="ab-card-head">
              <div class="ab-step">11</div>
              <div>
                <h2 class="ab-card-title">Style du bouton RDV</h2>
                <p class="ab-card-sub">Forme et texte du bouton principal</p>
              </div>
            </div>
            <div class="ab-btn-styles" id="ab-btn-grid">
              <button type="button" class="ab-bs-opt ab-bs-on" onclick="selBtnStyle(this,'filled','0.5rem')">
                <div class="ab-bs-preview" style="background:#C9A84C;border-radius:0.5rem;">RDV</div>
                <div class="ab-bs-nm">Plein</div>
              </button>
              <button type="button" class="ab-bs-opt" onclick="selBtnStyle(this,'pill','999px')">
                <div class="ab-bs-preview" style="background:#C9A84C;border-radius:999px;">RDV</div>
                <div class="ab-bs-nm">Pill</div>
              </button>
              <button type="button" class="ab-bs-opt" onclick="selBtnStyle(this,'outline','0.5rem')">
                <div class="ab-bs-preview" style="background:transparent;border:2px solid #C9A84C;border-radius:0.5rem;color:#C9A84C;">RDV</div>
                <div class="ab-bs-nm">Contour</div>
              </button>
              <button type="button" class="ab-bs-opt" onclick="selBtnStyle(this,'square','0.25rem')">
                <div class="ab-bs-preview" style="background:#C9A84C;border-radius:0.25rem;">RDV</div>
                <div class="ab-bs-nm">Carré</div>
              </button>
              <button type="button" class="ab-bs-opt" onclick="selBtnStyle(this,'soft','0.5rem')">
                <div class="ab-bs-preview" style="background:rgba(201,168,76,0.15);border:1px solid rgba(201,168,76,0.4);border-radius:0.5rem;color:#C9A84C;">RDV</div>
                <div class="ab-bs-nm">Doux</div>
              </button>
              <button type="button" class="ab-bs-opt" onclick="selBtnStyle(this,'dark','0.5rem')">
                <div class="ab-bs-preview" style="background:#0A0A0A;border-radius:0.5rem;color:#C9A84C;">RDV</div>
                <div class="ab-bs-nm">Sombre</div>
              </button>
            </div>
            <input type="hidden" id="btn_style" name="btn_style" value="filled">
            <div class="ab-field" style="margin-top:.75rem;">
              <label class="ab-label">Texte du bouton</label>
              <input class="ab-input" id="inp-btn-txt" name="btn_text" value="Prendre rendez-vous" oninput="syncPreview()">
            </div>
            <div class="ab-field">
              <label class="ab-label">Couleurs des boutons secondaires</label>
              <div class="ab-clr-row">
                <input type="color" class="ab-clr-sw" name="button_color" id="btn-clr" value="#C9A84C" oninput="syncPreview()">
                <input class="ab-input ab-mono-inp" value="#C9A84C" maxlength="7" oninput="document.getElementById('btn-clr').value=this.value;syncPreview()">
              </div>
            </div>
          </div>

          <!-- ── S12: Ordre des sections ── -->
          <div class="ab-card ab-sec" id="sec-order">
            <div class="ab-card-head">
              <div class="ab-step">12</div>
              <div>
                <h2 class="ab-card-title">Ordre des sections</h2>
                <p class="ab-card-sub">Cliquez deux numéros pour les échanger</p>
              </div>
            </div>
            <p class="ab-hint" style="margin-bottom:.5rem;">Sélectionnez un numéro, puis un autre pour les échanger de place</p>
            <div class="ab-so-list" id="ab-so-list"></div>
          </div>

          <!-- ── S13: Compte propriétaire ── -->
          <div class="ab-card ab-sec" id="sec-account">
            <div class="ab-card-head">
              <div class="ab-step">13</div>
              <div>
                <h2 class="ab-card-title">Compte propriétaire</h2>
                <p class="ab-card-sub">WhatsApp + mot de passe temporaire</p>
              </div>
            </div>
            <div class="ab-grid">
              <div class="ab-field ab-field-full">
                <label class="ab-label">Numéro WhatsApp (identifiant de connexion)</label>
                <div class="ab-prefix"><span>+237</span><input type="tel" name="owner_whatsapp" placeholder="6XX XXX XXX"></div>
                <p class="ab-hint">Pas d'email requis — le propriétaire se connecte avec son numéro</p>
              </div>
              <div class="ab-field ab-field-full">
                <label class="ab-label">Mot de passe temporaire</label>
                <div class="ab-pwd-row">
                  <input class="ab-input" type="text" id="pwd-inp" name="temp_password" placeholder="Lion2026!">
                  <button type="button" class="ab-gen-btn" onclick="genPwd()">Générer</button>
                </div>
                <p class="ab-hint">Le propriétaire devra le changer à la première connexion</p>
              </div>
              <div class="ab-field ab-field-full">
                <label class="ab-label">Plan d'abonnement</label>
                <div class="ab-plan-grid" id="ab-plan-grid">
                  <button type="button" class="ab-plan-opt" onclick="selPlan(this,'basic')">
                    <div class="ab-plan-nm">Basic</div><div class="ab-plan-pr">10 000 F/mois</div>
                  </button>
                  <button type="button" class="ab-plan-opt ab-plan-on" onclick="selPlan(this,'standard')">
                    <div class="ab-plan-nm">Standard</div><div class="ab-plan-pr">15 000 F/mois</div>
                  </button>
                  <button type="button" class="ab-plan-opt" onclick="selPlan(this,'premium')">
                    <div class="ab-plan-nm">Premium</div><div class="ab-plan-pr">20 000 F/mois</div>
                  </button>
                </div>
                <input type="hidden" id="plan-inp" name="plan" value="standard">
              </div>
              <div class="ab-field ab-field-full">
                <label class="ab-label">Notes internes (LionTech uniquement)</label>
                <textarea class="ab-textarea" name="internal_notes" rows="3" placeholder="Visible uniquement par LionTech..."></textarea>
              </div>
            </div>
          </div>

          <!-- SUCCESS BOX -->
          <?php if ($save_success): ?>
          <div class="ab-suc-box">
            <div class="ab-suc-head"><i class="fa-solid fa-circle-check"></i> Business créé avec succès !</div>
            <div class="ab-suc-row">Lien : <strong>lionrdv.cm/<?= h($saved_slug) ?></strong></div>
            <div class="ab-suc-row">WhatsApp : <strong><?= h($prefill['owner_whatsapp'] ?? '') ?></strong></div>
            <div class="ab-suc-row">Mot de passe : <strong><?= h($prefill['owner_password'] ?? '') ?></strong></div>
            <div class="ab-suc-btns">
              <a href="/LionRDV/Utilisateur%20du%20client/Utulisateur.php?slug=<?= urlencode($saved_slug) ?>"
                 target="_blank" class="ab-suc-btn ab-suc-dark">
                <i class="fa-solid fa-eye"></i> Voir la page
              </a>
              <a href="../RSVAdmin.php" class="ab-suc-btn ab-suc-gold">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
              </a>
            </div>
          </div>
          <?php endif; ?>

          <!-- SUBMIT -->
          <div class="ab-submit-wrap">
            <button type="submit" class="ab-btn-final">
              <i class="fa-solid fa-check"></i> Créer le compte business
            </button>
          </div>

        </form>
      </div>
      <!-- /form-col -->

      <!-- ═══════ PREVIEW COLUMN ═══════ -->
      <div class="ab-preview-col">
        <div class="ab-prev-bar">
          <span class="ab-prev-title">
            <i class="fa-solid fa-eye"></i> Aperçu live
          </span>
          <div class="ab-dev-btns">
            <button class="ab-dev-btn ab-dev-on" id="btn-mob" onclick="setDevice('mobile')">
              <i class="fa-solid fa-mobile-screen"></i> Mobile
            </button>
            <button class="ab-dev-btn" id="btn-desk" onclick="setDevice('desktop')">
              <i class="fa-solid fa-desktop"></i> Desktop
            </button>
          </div>
        </div>
        <div class="ab-prev-wrap" id="ab-prev-wrap">
          <div class="ab-phone-shell" id="ab-phone-shell">
            <iframe
              id="preview-iframe"
              src="/LionRDV/Utilisateur%20du%20client/Utulisateur.php?preview=1"
              title="Aperçu live de la page client">
            </iframe>
          </div>
        </div>
      </div>
      <!-- /preview-col -->

    </div>
    <!-- /builder -->

  </main>
</div>

<script src="../RSVAdmin.js"></script>
<script>
/* ============================================================
   AjouterBussiness.js — Live Preview Engine
   Envoie les données du formulaire à l'iframe via postMessage
   L'iframe (Utulisateur.php?preview=1) reçoit et met à jour
============================================================ */
const iframe  = document.getElementById('preview-iframe');
const formCol = document.getElementById('ab-form-col');

let currentColor   = '#C9A84C';
let savedColors    = [];
let btnStyle       = 'filled';
let btnRadius      = '0.5rem';
let galMode        = 'grid';
let svcStyle       = 'pills';
let horStyle       = 'list';
let sections       = [
  {id:'rdv',      nm:'Rendez-vous',  ico:'📅'},
  {id:'services', nm:'Nos Services', ico:'🍽'},
  {id:'horaires', nm:'Horaires',     ico:'🕐'},
  {id:'about',    nm:'À propos',     ico:'ℹ'},
  {id:'gallery',  nm:'Galerie',      ico:'📷'},
  {id:'contact',  nm:'Contact',      ico:'📍'},
];
let soSel = -1;

/* ── Charger les couleurs sauvegardées depuis sessionStorage ── */
try {
  const sc = sessionStorage.getItem('lionrdv_saved_colors');
  if (sc) { savedColors = JSON.parse(sc); renderSavedColors(); }
} catch(e) {}

/* ── Construire le payload à envoyer à l'iframe ── */
function buildPayload() {
  const d = {};

  /* ── Champs texte / select / hidden ── */
  const fields = [
    'business_name','site_language','navbar_style','footer_style',
    'global_font','global_font_size','global_font_weight',
    'text_color','btn_style','bg_texture','background_color',
    'about_position','about_font','about_font_size','about_text_color',
    'svc_display_style','gal_display_mode','gal_max_photos','btn_text',
    'city','quarter','button_color',
  ];
  fields.forEach(id => {
    const el = document.getElementById(id) || document.querySelector('[name="'+id+'"]');
    if (el) d[id] = el.value;
  });

  /* ── Couleur principale (variable JS, toujours à jour) ── */
  d.primary_color = currentColor;

  /* ── Variables JS pour galerie, services, boutons ── */
  d.btn_style      = btnStyle;
  d.btn_radius     = btnRadius;
  d.gal_mode       = galMode;       /* mode galerie sélectionné */
  d.svc_style      = svcStyle;      /* style services sélectionné */
  d.hor_style      = horStyle;
  d.sections_order = sections.map(s => s.id);

  /* ── Business type : lire le radio COCHÉ, pas le premier ── */
  const checkedType = document.querySelector('[name="business_type"]:checked');
  if (checkedType) d.business_type = checkedType.value;

  /* ── Checkboxes : lire .checked et convertir en 0/1 ── */
  d.show_connexion_btn  = document.getElementById('tog-conn')?.checked    ? 1 : 0;
  d.show_avatar         = document.getElementById('tog-avatar')?.checked  ? 1 : 0;
  d.show_liontech_logo  = document.querySelector('[name="show_liontech_logo"]')?.checked ? 1 : 0;
  d.show_business_logo  = document.querySelector('[name="show_business_logo"]')?.checked ? 1 : 0;

  /* ── Position À propos ── */
  d.about_pos = document.getElementById('about-pos')?.value || 'after';

  return d;
}

function sendPreview() {
  try { iframe.contentWindow.postMessage({type:'PREVIEW_UPDATE', data: buildPayload()}, '*'); } catch(e) {}
}

let debTimer = null;
function syncPreview() { clearTimeout(debTimer); debTimer = setTimeout(sendPreview, 120); }

/* ── Écouter les clics de section depuis l'iframe ── */
window.addEventListener('message', e => {
  if (!e.data || e.data.type !== 'SECTION_CLICK') return;
  const map = {
    navbar:'sec-cover', hero:'sec-cover', colors:'sec-colors',
    services:'sec-svc', gallery:'sec-gallery', footer:'sec-bg',
    contact:'sec-info', info:'sec-info', about:'sec-about',
    logo:'sec-cover', rdv:'sec-btn',
  };
  const secId = map[e.data.section] || 'sec-' + e.data.section;
  const el = document.getElementById(secId);
  if (!el) return;
  formCol.scrollTo({top: el.offsetTop - 10, behavior: 'smooth'});
  document.querySelectorAll('.ab-card.ab-hl').forEach(x => x.classList.remove('ab-hl'));
  el.classList.add('ab-hl');
  setTimeout(() => el.classList.remove('ab-hl'), 1800);
});

/* ── Couleurs ── */
function setColor(c, dot) {
  currentColor = c;
  document.getElementById('primary_color').value = c;
  document.getElementById('ab-clr-picker').value = c;
  document.getElementById('ab-clr-hex').value = c;
  document.querySelectorAll('.ab-clr-dot').forEach(d => d.classList.remove('ab-clr-on'));
  if (dot) dot.classList.add('ab-clr-on');
  sendPreview();
}
function saveColor() {
  const c = document.getElementById('ab-clr-hex').value.trim();
  if (!c.match(/^#[0-9A-Fa-f]{3,6}$/)) return;
  if (savedColors.includes(c)) { setColor(c, null); return; }
  savedColors.push(c);
  try { sessionStorage.setItem('lionrdv_saved_colors', JSON.stringify(savedColors)); } catch(e) {}
  renderSavedColors();
  setColor(c, null);
}
function renderSavedColors() {
  const box = document.getElementById('ab-saved-colors');
  if (!savedColors.length) { box.innerHTML = '<span class="ab-saved-empty">Aucune couleur sauvegardée</span>'; return; }
  box.innerHTML = savedColors.map((c, i) =>
    `<button type="button" class="ab-saved-dot" style="background:${c};"
      onclick="setColor('${c}',null)"
      ondblclick="delSaved(${i})" title="${c} · double-clic pour supprimer"></button>`
  ).join('');
}
function delSaved(i) {
  savedColors.splice(i, 1);
  try { sessionStorage.setItem('lionrdv_saved_colors', JSON.stringify(savedColors)); } catch(e) {}
  renderSavedColors();
}

/* ── Bouton Connexion ── */
function toggleConnBtn(el) {
  try { iframe.contentWindow.postMessage({type:'PREVIEW_CONN', show: el.checked}, '*'); } catch(e) {}
  sendPreview();
}

/* ── Avatar ── */
function toggleAvatar(el) {
  document.getElementById('avatar-wrap').style.display = el.checked ? 'block' : 'none';
  sendPreview();
}

/* ── Upload fichiers ── */
function handleLogo(input) {
  const file = input.files[0]; if (!file) return;
  const r = new FileReader();
  r.onload = e => {
    const img = document.getElementById('logo-img');
    img.src = e.target.result; img.style.display = 'block';
    document.getElementById('logo-thumb').querySelector('i').style.display = 'none';
    try { iframe.contentWindow.postMessage({type:'PREVIEW_LOGO', src: e.target.result}, '*'); } catch(ex) {}
  };
  r.readAsDataURL(file);
}
function handleCover(input) {
  const file = input.files[0]; if (!file) return;
  const r = new FileReader();
  r.onload = e => {
    const img = document.getElementById('cover-img');
    img.src = e.target.result; img.style.display = 'block';
    document.getElementById('cover-thumb').querySelector('i').style.display = 'none';
    try { iframe.contentWindow.postMessage({type:'PREVIEW_COVER', src: e.target.result}, '*'); } catch(ex) {}
  };
  r.readAsDataURL(file);
}
function handleAvatar(input) {
  const file = input.files[0]; if (!file) return;
  const r = new FileReader();
  r.onload = e => {
    const img = document.getElementById('avatar-img');
    img.src = e.target.result; img.style.display = 'block';
    document.getElementById('avatar-thumb').querySelector('i').style.display = 'none';
    try { iframe.contentWindow.postMessage({type:'PREVIEW_AVATAR', src: e.target.result}, '*'); } catch(ex) {}
  };
  r.readAsDataURL(file);
}

/* ── Position overlay ── */
function selPos(btn, pos) {
  const row = btn.closest('.ab-ov-pos');
  row.querySelectorAll('.ab-pos-btn').forEach(b => b.classList.remove('ab-pos-on'));
  btn.classList.add('ab-pos-on');
  const field = btn.dataset.field;
  const inp = document.querySelector('input[name="'+field+'"]');
  if (inp) inp.value = pos;
}

/* ── Police ── */
function selFont(btn, font) {
  document.querySelectorAll('.ab-font-opt').forEach(b => b.classList.remove('ab-font-on'));
  btn.classList.add('ab-font-on');
  document.getElementById('global_font').value = font;
  try { iframe.contentWindow.postMessage({type:'PREVIEW_FONT', font}, '*'); } catch(e) {}
}
function toggleCustomFont() { document.getElementById('custom-font-row').classList.toggle('ab-show'); }
function applyCustomFont() {
  const f = document.getElementById('custom-font-inp').value.trim();
  if (!f) return;
  document.getElementById('global_font').value = f;
  try { iframe.contentWindow.postMessage({type:'PREVIEW_FONT', font: `'${f}', system-ui`}, '*'); } catch(e) {}
}

/* ── Taille et poids ── */
function selSize(btn, size) {
  document.querySelectorAll('#ab-size-grid .ab-sz').forEach(b => b.classList.remove('ab-sz-on'));
  btn.classList.add('ab-sz-on');
  document.getElementById('global_font_size').value = size;
  sendPreview();
}
function selWeight(btn, weight) {
  document.querySelectorAll('#ab-weight-grid .ab-sz').forEach(b => b.classList.remove('ab-sz-on'));
  btn.classList.add('ab-sz-on');
  document.getElementById('global_font_weight').value = weight;
}

/* ── Fond ── */
function selBg(btn) {
  document.querySelectorAll('.ab-bg-opt').forEach(b => { b.classList.remove('ab-bg-on'); b.style.borderColor = 'transparent'; });
  btn.classList.add('ab-bg-on'); btn.style.borderColor = '#C9A84C';
  /* Mettre à jour les inputs hidden AVANT d'envoyer le preview */
  document.getElementById('bg_texture').value = btn.dataset.tex;
  document.getElementById('background_color').value = btn.dataset.bg;
  /* Envoyer PREVIEW_BG pour application immédiate du fond */
  try { iframe.contentWindow.postMessage({type:'PREVIEW_BG', tex: btn.dataset.tex, bg: btn.dataset.bg}, '*'); } catch(e) {}
  /* Envoyer aussi PREVIEW_UPDATE pour re-rendre galerie/services avec la nouvelle couleur */
  sendPreview();
}
function toggleCustomBg() { document.getElementById('custom-bg-row').classList.toggle('ab-show'); }
function applyCustomBg() {
  const v = document.getElementById('custom-bg-inp').value.trim();
  if (!v) return;
  document.querySelectorAll('.ab-bg-opt').forEach(b => { b.classList.remove('ab-bg-on'); b.style.borderColor = 'transparent'; });
  try { iframe.contentWindow.postMessage({type:'PREVIEW_BG_CUSTOM', css: v}, '*'); } catch(e) {}
}

/* ── Horaires ── */
function togDay(en, open) {
  const row = document.getElementById('day-' + en);
  const nm = row?.querySelector('.ab-day-nm');
  const inputs = row?.querySelectorAll('.ab-time-inp');
  const ferme = row?.querySelector('.ab-day-ferme');
  if (!row) return;
  if (nm) nm.classList.toggle('ab-day-off', !open);
  if (inputs) inputs.forEach(i => i.style.display = open ? '' : 'none');
  if (open && ferme) ferme.style.display = 'none';
  else if (!open && ferme) ferme.style.display = '';
}
function selHorStyle(btn, style) {
  document.querySelectorAll('#ab-hor-grid .ab-hor-opt').forEach(b => b.classList.remove('ab-hor-on'));
  btn.classList.add('ab-hor-on');
  document.getElementById('hor_style').value = style;
  horStyle = style; sendPreview();
}

/* ── Galerie ── */
function selGalMax(btn, n) {
  document.querySelectorAll('#ab-gal-max .ab-gmb').forEach(b => b.classList.remove('ab-gmb-on'));
  btn.classList.add('ab-gmb-on');
  document.getElementById('gal_max_photos').value = n;
}
function selGalMode(btn, mode) {
  document.querySelectorAll('#ab-gal-modes .ab-gm-opt').forEach(b => b.classList.remove('ab-gm-on'));
  btn.classList.add('ab-gm-on');
  document.getElementById('gal_display_mode').value = mode;
  galMode = mode; sendPreview();
}

/* ── Services ── */
function selSvcStyle(btn, style) {
  document.querySelectorAll('.ab-ss-item').forEach(b => b.classList.remove('ab-ss-on'));
  btn.classList.add('ab-ss-on');
  document.getElementById('svc_display_style').value = style;
  svcStyle = style; sendPreview();
}

/* ── Bouton RDV ── */
function selBtnStyle(btn, style, radius) {
  document.querySelectorAll('#ab-btn-grid .ab-bs-opt').forEach(b => b.classList.remove('ab-bs-on'));
  btn.classList.add('ab-bs-on');
  document.getElementById('btn_style').value = style;
  btnStyle = style; btnRadius = radius; sendPreview();
}

/* ── Ordre des sections ── */
function initOrder() {
  const list = document.getElementById('ab-so-list');
  list.innerHTML = sections.map((s, i) =>
    `<div class="ab-so-item" data-idx="${i}" onclick="clickOrder(this)">
       <div class="ab-so-num">${i+1}</div>
       <span class="ab-so-ico">${s.ico}</span>
       <span class="ab-so-nm">${s.nm}</span>
     </div>`
  ).join('');
}
function clickOrder(el) {
  const idx = parseInt(el.dataset.idx);
  if (soSel === -1) { soSel = idx; el.classList.add('ab-so-sel'); }
  else if (soSel === idx) { soSel = -1; el.classList.remove('ab-so-sel'); }
  else {
    const tmp = sections[soSel]; sections[soSel] = sections[idx]; sections[idx] = tmp;
    soSel = -1; initOrder(); sendPreview();
  }
}

/* ── Plan ── */
function selPlan(btn, plan) {
  document.querySelectorAll('#ab-plan-grid .ab-plan-opt').forEach(b => b.classList.remove('ab-plan-on'));
  btn.classList.add('ab-plan-on');
  document.getElementById('plan-inp').value = plan;
}

/* ── Génération mot de passe ── */
function genPwd() {
  const c = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#';
  let p = '';
  for (let i = 0; i < 10; i++) p += c[Math.floor(Math.random() * c.length)];
  document.getElementById('pwd-inp').value = p;
}

/* ── Device toggle ── */
function setDevice(mode) {
  const shell = document.getElementById('ab-phone-shell');
  const wrap  = document.getElementById('ab-prev-wrap');
  const mb    = document.getElementById('btn-mob');
  const db    = document.getElementById('btn-desk');
  if (mode === 'desktop') {
    shell.classList.add('ab-desktop'); wrap.classList.add('ab-desk-mode');
    mb.classList.remove('ab-dev-on'); db.classList.add('ab-dev-on');
  } else {
    shell.classList.remove('ab-desktop'); wrap.classList.remove('ab-desk-mode');
    mb.classList.add('ab-dev-on'); db.classList.remove('ab-dev-on');
  }
}

/* ── Écouter tous les inputs du formulaire ── */
document.getElementById('biz-form').querySelectorAll('input:not([type=file]):not([type=color]),textarea,select').forEach(el => {
  el.addEventListener('input',  () => syncPreview());
  el.addEventListener('change', () => sendPreview());
});
document.getElementById('biz-form').querySelectorAll('input[type=color]').forEach(el => {
  el.addEventListener('input', () => sendPreview());
});

/* ── Langue — changer l'apparence des options ── */
document.querySelectorAll('.ab-lang-opt input').forEach(radio => {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.ab-lang-opt').forEach(o => o.classList.remove('ab-lang-on'));
    this.closest('.ab-lang-opt').classList.add('ab-lang-on');
    syncPreview();
  });
});

/* ── Envoyer quand iframe charge ── */
iframe.addEventListener('load', () => setTimeout(sendPreview, 150));
window.addEventListener('load', () => { initOrder(); setTimeout(sendPreview, 400); });
</script>
</body>
</html>