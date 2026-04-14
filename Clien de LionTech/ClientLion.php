<?php
session_start();

/* ============================================================
   ClientLion.php — LionRDV Owner Dashboard
   Nouveautés : upload galerie + save disponibilités → JSON
============================================================ */

/* ── DATA DIR ──────────────────────────────────────────── */
$data_dir = 'C:/Xampp/htdocs/LionRDV/data';

/* ── MOCK AUTH ─────────────────────────────────────────── */
$demo_owners = [
  'nora@beauty.cm' => [
    'password'    => 'Lion2026!',
    'name'        => 'Nora Beauty',
    'initials'    => 'NB',
    'type'        => 'Salon de beauté',
    'location'    => 'Bastos, Yaoundé',
    'theme_color' => '#D4447A',
    'theme_bg'    => '#FFF0F8',
    'plan'        => 'Standard',
    'slug'        => 'nora-beauty',
    'whatsapp'    => '+237699001122',
  ],
];

$error  = '';
$logged = isset($_SESSION['owner_logged']) && $_SESSION['owner_logged'] === true;
$owner  = $logged ? $_SESSION['owner_data'] : null;

/* ── HELPER: load/save business JSON ───────────────────── */
function load_business($slug, $data_dir) {
  $file = $data_dir . '/' . $slug . '.json';
  if (!file_exists($file)) return [];
  return json_decode(file_get_contents($file), true) ?? [];
}

function save_business($slug, $data, $data_dir) {
  if (!is_dir($data_dir)) mkdir($data_dir, 0755, true);
  $file = $data_dir . '/' . $slug . '.json';
  /* Merge with existing so we don't overwrite other fields */
  $existing = [];
  if (file_exists($file)) {
    $existing = json_decode(file_get_contents($file), true) ?? [];
  }
  $merged = array_merge($existing, $data);
  return file_put_contents($file, json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/* ── HANDLE ALL POST ACTIONS ────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  /* LOGIN */
  if ($_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $pwd   = trim($_POST['password'] ?? '');
    if (isset($demo_owners[$email]) && $demo_owners[$email]['password'] === $pwd) {
      $_SESSION['owner_logged'] = true;
      $_SESSION['owner_data']   = $demo_owners[$email];
      $_SESSION['owner_email']  = $email;
      header('Location: ClientLion.php');
      exit;
    } else {
      $error = 'Email ou mot de passe incorrect.';
    }
  }

  /* LOGOUT */
  if ($_POST['action'] === 'logout') {
    session_destroy();
    header('Location: ClientLion.php');
    exit;
  }

  /* ── SAVE AVAILABILITY ──────────────────────────────────
     Called when owner clicks "Enregistrer les disponibilités"
  ─────────────────────────────────────────────────────── */
  if ($_POST['action'] === 'save_availability' && $logged) {
    $slug = $owner['slug'];

    $days_fr = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
    $days_en = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

    $availability = [];
    foreach ($days_en as $i => $day_en) {
      $key   = strtolower($day_en);
      $open  = isset($_POST['open_' . $key]) && $_POST['open_' . $key] === '1';
      $start = $_POST['start_' . $key] ?? '08:00';
      $end   = $_POST['end_' . $key]   ?? '18:00';
      $availability[] = [
        'day'    => $days_fr[$i],
        'day_en' => $day_en,
        'open'   => $open,
        'start'  => $open ? $start : '',
        'end'    => $open ? $end   : '',
      ];
    }

    $ok = save_business($slug, ['availability' => $availability], $data_dir);
    $_SESSION['flash'] = $ok
      ? ['type'=>'success','msg'=>'Disponibilités enregistrées !']
      : ['type'=>'error',  'msg'=>'Erreur lors de la sauvegarde.'];

    header('Location: ClientLion.php#avail');
    exit;
  }

  /* ── UPLOAD GALLERY PHOTOS ──────────────────────────────
     Called when owner uploads photos in Mon profil
  ─────────────────────────────────────────────────────── */
  if ($_POST['action'] === 'upload_gallery' && $logged) {
    $slug       = $owner['slug'];
    $upload_dir = 'C:/Xampp/htdocs/LionRDV/uploads/' . $slug . '/gallery/';

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    /* Load existing gallery */
    $biz     = load_business($slug, $data_dir);
    $gallery = $biz['gallery'] ?? [];

    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    $uploaded = 0;

    if (!empty($_FILES['gallery_photos']['tmp_name'])) {
      $files = $_FILES['gallery_photos'];
      $count = is_array($files['tmp_name']) ? count($files['tmp_name']) : 1;

      for ($i = 0; $i < $count; $i++) {
        $tmp  = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
        $name = is_array($files['name'])     ? $files['name'][$i]     : $files['name'];
        $type = is_array($files['type'])     ? $files['type'][$i]     : $files['type'];
        $err  = is_array($files['error'])    ? $files['error'][$i]    : $files['error'];

        if ($err !== UPLOAD_ERR_OK) continue;
        if (!in_array($type, $allowed))    continue;

        $ext      = pathinfo($name, PATHINFO_EXTENSION);
        $filename = 'photo_' . time() . '_' . $i . '.' . $ext;
        $dest     = $upload_dir . $filename;

        if (move_uploaded_file($tmp, $dest)) {
          $gallery[] = [
            'path' => 'uploads/' . $slug . '/gallery/' . $filename,
            'alt'  => $owner['name'],
          ];
          $uploaded++;
        }
      }
    }

    $ok = save_business($slug, ['gallery' => $gallery], $data_dir);
    $_SESSION['flash'] = $ok && $uploaded > 0
      ? ['type'=>'success','msg'=> $uploaded . ' photo(s) ajoutée(s) à la galerie !']
      : ['type'=>'error',  'msg'=>'Aucune photo uploadée.'];

    header('Location: ClientLion.php#profile');
    exit;
  }

  /* ── DELETE GALLERY PHOTO ───────────────────────────────
     Called when owner clicks delete on a photo
  ─────────────────────────────────────────────────────── */
  if ($_POST['action'] === 'delete_photo' && $logged) {
    $slug      = $owner['slug'];
    $del_path  = $_POST['photo_path'] ?? '';

    $biz     = load_business($slug, $data_dir);
    $gallery = $biz['gallery'] ?? [];

    /* Remove from array */
    $gallery = array_filter($gallery, fn($p) => $p['path'] !== $del_path);
    $gallery = array_values($gallery);

    /* Delete file from disk */
    $full_path = 'C:/Xampp/htdocs/LionRDV/' . $del_path;
    if (file_exists($full_path)) unlink($full_path);

    save_business($slug, ['gallery' => $gallery], $data_dir);
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Photo supprimée.'];
    header('Location: ClientLion.php#profile');
    exit;
  }
}

/* ── FLASH MESSAGE ──────────────────────────────────────── */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ── LOAD BUSINESS DATA FROM JSON ───────────────────────── */
$biz_data     = [];
$biz_gallery  = [];
$biz_avail    = [];

if ($logged) {
  $biz_data    = load_business($owner['slug'], $data_dir);
  $biz_gallery = $biz_data['gallery']      ?? [];
  $biz_avail   = $biz_data['availability'] ?? [];
}

/* ── MOCK DATA ──────────────────────────────────────────── */
$rdv_list = [
  ['id'=>1,'time'=>'09h30','date'=>'Aujourd\'hui','name'=>'Awa Tchoupo',  'service'=>'Pose ongles',      'duration'=>'1h',    'phone'=>'+237690001111','status'=>'en_cours',  'amount'=>5000],
  ['id'=>2,'time'=>'11h00','date'=>'Aujourd\'hui','name'=>'Carine Bebe',  'service'=>'Lissage',          'duration'=>'2h',    'phone'=>'+237690002222','status'=>'confirme',  'amount'=>9000],
  ['id'=>3,'time'=>'14h30','date'=>'Aujourd\'hui','name'=>'Marie Nguele', 'service'=>'Coupe & Brushing', 'duration'=>'45min', 'phone'=>'+237690003333','status'=>'confirme',  'amount'=>2500],
  ['id'=>4,'time'=>'16h00','date'=>'Aujourd\'hui','name'=>'Sylvie Ateba', 'service'=>'Maquillage',       'duration'=>'1h30',  'phone'=>'+237690004444','status'=>'en_attente','amount'=>7000],
  ['id'=>5,'time'=>'09h00','date'=>'Demain',       'name'=>'Pauline Biya','service'=>'Coupe',            'duration'=>'45min', 'phone'=>'+237690005555','status'=>'confirme',  'amount'=>2500],
  ['id'=>6,'time'=>'10h30','date'=>'Demain',       'name'=>'Ruth Fouda',  'service'=>'Pose ongles',      'duration'=>'1h',    'phone'=>'+237690006666','status'=>'confirme',  'amount'=>5000],
  ['id'=>7,'time'=>'13h00','date'=>'Jeudi 11',     'name'=>'Sandrine Kom','service'=>'Lissage',          'duration'=>'2h',    'phone'=>'+237690007777','status'=>'confirme',  'amount'=>9000],
  ['id'=>8,'time'=>'15h00','date'=>'Vendredi 12',  'name'=>'Hortense Eto','service'=>'Maquillage',       'duration'=>'1h30',  'phone'=>'+237690008888','status'=>'confirme',  'amount'=>7000],
];

$services = [
  ['id'=>1,'name'=>'Coupe & Brushing',  'duration'=>'45 min','price'=>2500, 'color'=>'#D4447A'],
  ['id'=>2,'name'=>'Lissage brésilien', 'duration'=>'2h',    'price'=>9000, 'color'=>'#E07B39'],
  ['id'=>3,'name'=>'Pose ongles gel',   'duration'=>'1h',    'price'=>5000, 'color'=>'#7C3AED'],
  ['id'=>4,'name'=>'Maquillage complet','duration'=>'1h30',  'price'=>7000, 'color'=>'#0EA5E9'],
  ['id'=>5,'name'=>'Tresses africaines','duration'=>'3h',    'price'=>12000,'color'=>'#059669'],
];

/* Default availability if no JSON yet */
$availability = !empty($biz_avail) ? $biz_avail : [
  ['day'=>'Lundi',    'day_en'=>'Monday',    'open'=>true,  'start'=>'08:00','end'=>'18:00'],
  ['day'=>'Mardi',    'day_en'=>'Tuesday',   'open'=>true,  'start'=>'08:00','end'=>'18:00'],
  ['day'=>'Mercredi', 'day_en'=>'Wednesday', 'open'=>true,  'start'=>'08:00','end'=>'18:00'],
  ['day'=>'Jeudi',    'day_en'=>'Thursday',  'open'=>true,  'start'=>'08:00','end'=>'18:00'],
  ['day'=>'Vendredi', 'day_en'=>'Friday',    'open'=>true,  'start'=>'08:00','end'=>'19:00'],
  ['day'=>'Samedi',   'day_en'=>'Saturday',  'open'=>true,  'start'=>'09:00','end'=>'17:00'],
  ['day'=>'Dimanche', 'day_en'=>'Sunday',    'open'=>false, 'start'=>'',     'end'=>''],
];

$stats = [
  'today_rdv'  => 8,
  'month_rdv'  => 47,
  'month_fcfa' => 47000,
  'rating'     => 4.9,
  'views'      => 142,
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LionRDV — Espace Propriétaire</title>
  <link rel="stylesheet" href="Client.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="<?= $logged ? 'is-dashboard' : 'is-login' ?>">

<?php if ($flash): ?>
<div id="php-flash"
     data-type="<?= $flash['type'] ?>"
     data-msg="<?= htmlspecialchars($flash['msg']) ?>"></div>
<?php endif; ?>

<?php if (!$logged): ?>
<!-- ═══════════════════════════════════════════
     LOGIN PAGE
═══════════════════════════════════════════ -->
<div class="login-shell">

  <div class="login-left">
    <div class="ll-content">
      <div class="ll-logo-block">
        <img src="liontech-logo.jpg" alt="LIONTECH Logo" class="logo-img">
        <div class="ll-name">Lion<span>RDV</span></div>
        <div class="ll-tagline">by LionTech</div>
      </div>
      <div class="ll-features">
        <div class="ll-feat"><div class="ll-feat-icon"><i class="fa-regular fa-calendar-check"></i></div><div class="ll-feat-text">Gérez vos réservations en temps réel</div></div>
        <div class="ll-feat"><div class="ll-feat-icon"><i class="fa-regular fa-clock"></i></div><div class="ll-feat-text">Définissez vos disponibilités facilement</div></div>
        <div class="ll-feat"><div class="ll-feat-icon"><i class="fa-solid fa-qrcode"></i></div><div class="ll-feat-text">Partagez votre lien QR avec vos clients</div></div>
        <div class="ll-feat"><div class="ll-feat-icon"><i class="fa-solid fa-chart-line"></i></div><div class="ll-feat-text">Suivez vos revenus et statistiques</div></div>
      </div>
      <div class="ll-footer">© 2026 LionTech — Tous droits réservés</div>
    </div>
  </div>

  <div class="login-right">
    <div class="login-form-wrap">
      <div class="lf-lang-bar">
        <button class="lf-lb active" onclick="setLang('fr', this)">FR</button>
        <button class="lf-lb" onclick="setLang('en', this)">EN</button>
      </div>
      <div class="lf-header">
        <h1 class="lf-welcome" data-fr="Bienvenue 👋" data-en="Welcome 👋">Bienvenue 👋</h1>
        <p class="lf-sub" data-fr="Connectez-vous à votre espace propriétaire" data-en="Sign in to your owner dashboard">Connectez-vous à votre espace propriétaire</p>
      </div>
      <?php if ($error): ?>
        <div class="lf-error"><i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form class="lf-form" method="POST" action="ClientLion.php">
        <input type="hidden" name="action" value="login">
        <div class="lf-field">
          <label data-fr="Adresse email" data-en="Email address">Adresse email</label>
          <input type="email" name="email" placeholder="nora@example.com" value="nora@beauty.cm" required autocomplete="email">
        </div>
        <div class="lf-field">
          <label data-fr="Mot de passe" data-en="Password">Mot de passe</label>
          <div class="lf-pwd-wrap">
            <input type="password" name="password" id="lf-pwd" placeholder="••••••••••" value="Lion2026!" required autocomplete="current-password">
            <button type="button" class="lf-eye" onclick="togglePwd()"><i class="fa-regular fa-eye" id="eye-icon"></i></button>
          </div>
        </div>
        <div class="lf-row">
          <label class="lf-remember">
            <input type="checkbox" name="remember">
            <span data-fr="Se souvenir de moi" data-en="Remember me">Se souvenir de moi</span>
          </label>
          <button type="button" class="lf-forgot" data-fr="Mot de passe oublié ?" data-en="Forgot password?">Mot de passe oublié ?</button>
        </div>
        <button type="submit" class="lf-submit">
          <span class="lf-submit-dot"></span>
          <span data-fr="Se connecter" data-en="Sign in">Se connecter</span>
        </button>
      </form>
      <div class="lf-divider"><span data-fr="besoin d'aide ?" data-en="need help?">besoin d'aide ?</span></div>
      <div class="lf-help">
        <span data-fr="Contactez LionTech sur" data-en="Contact LionTech on">Contactez LionTech sur</span>
        <a href="https://wa.me/237690000000" target="_blank">WhatsApp</a>
        <span data-fr="ou par" data-en="or by">ou par</span>
        <a href="mailto:support@liontech.cm">email</a>
      </div>
      <div class="lf-powered">
        <div class="lf-powered-mark">LT</div>
        <span>Propulsé par <strong>LionTech</strong> · LionRDV Platform</span>
      </div>
    </div>
  </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════════════════════
     OWNER DASHBOARD
═══════════════════════════════════════════ -->
<div class="dash-shell">

  <!-- SIDEBAR -->
  <aside class="dash-sidebar">
    <div class="ds-brand">
      <div class="ds-biz-row">
        <div class="ds-biz-logo" style="background:<?= htmlspecialchars($owner['theme_color']) ?>;">
          <?= htmlspecialchars($owner['initials']) ?>
        </div>
        <div class="ds-biz-info">
          <div class="ds-biz-name"><?= htmlspecialchars($owner['name']) ?></div>
          <div class="ds-biz-type"><?= htmlspecialchars($owner['type']) ?></div>
        </div>
      </div>
      <div class="ds-powered">
        <div class="ds-powered-mark">LT</div>
        <span>Propulsé par <strong>LionRDV</strong></span>
      </div>
    </div>
    <nav class="ds-nav">
      <div class="ds-nav-lbl" data-fr="Principal" data-en="Main">Principal</div>
      <a class="ds-nav-item active" data-page="dashboard" onclick="showPage('dashboard',this)">
        <i class="fa-solid fa-table-cells-large"></i>
        <span data-fr="Dashboard" data-en="Dashboard">Dashboard</span>
      </a>
      <a class="ds-nav-item" data-page="rdv" onclick="showPage('rdv',this)">
        <i class="fa-regular fa-calendar-check"></i>
        <span data-fr="Mes RDV" data-en="My bookings">Mes RDV</span>
        <span class="ds-badge" style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"><?= count($rdv_list) ?></span>
      </a>
      <a class="ds-nav-item" data-page="avail" onclick="showPage('avail',this)">
        <i class="fa-regular fa-clock"></i>
        <span data-fr="Disponibilités" data-en="Availability">Disponibilités</span>
      </a>
      <a class="ds-nav-item" data-page="services" onclick="showPage('services',this)">
        <i class="fa-solid fa-list-ul"></i>
        <span data-fr="Mes services" data-en="My services">Mes services</span>
      </a>
      <a class="ds-nav-item" data-page="qr" onclick="showPage('qr',this)">
        <i class="fa-solid fa-qrcode"></i>
        <span data-fr="QR Code / Mon lien" data-en="QR Code / My link">QR Code / Mon lien</span>
      </a>
      <a class="ds-nav-item" data-page="profile" onclick="showPage('profile',this)">
        <i class="fa-regular fa-user"></i>
        <span data-fr="Mon profil" data-en="My profile">Mon profil</span>
      </a>
    </nav>
    <div class="ds-footer">
      <div class="ds-footer-av" style="background:<?= htmlspecialchars($owner['theme_color']) ?>;">
        <?= htmlspecialchars($owner['initials']) ?>
      </div>
      <div class="ds-footer-info">
        <div class="ds-footer-name"><?= htmlspecialchars($owner['name']) ?></div>
        <div class="ds-footer-role" data-fr="Propriétaire" data-en="Owner">Propriétaire</div>
      </div>
      <form method="POST" style="margin-left:auto;">
        <input type="hidden" name="action" value="logout">
        <button type="submit" class="ds-logout" title="Se déconnecter">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
        </button>
      </form>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="dash-main">
    <header class="dash-topbar">
      <div class="dt-left">
        <div class="dt-title" id="page-title" data-fr="Dashboard" data-en="Dashboard">Dashboard</div>
        <div class="dt-sub" id="page-sub">
          <?= date('l j F Y') ?> · <span data-fr="Bonne journée" data-en="Good day">Bonne journée</span>, <?= htmlspecialchars(explode(' ', $owner['name'])[0]) ?> 👋
        </div>
      </div>
      <div class="dt-right">
        <button class="lang-btn active" onclick="setLang('fr',this)">FR</button>
        <button class="lang-btn" onclick="setLang('en',this)">EN</button>
        <div class="dt-notif">
          <i class="fa-regular fa-bell"></i>
          <span class="dt-notif-dot"></span>
        </div>
      </div>
    </header>

    <!-- ══ DASHBOARD ══ -->
    <div class="page active" id="page-dashboard">
      <div class="page-content">
        <div class="kpi-row">
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon" style="background:<?= htmlspecialchars($owner['theme_bg']) ?>;"><i class="fa-regular fa-calendar-check" style="color:<?= htmlspecialchars($owner['theme_color']) ?>;"></i></div>
              <div class="kpi-change up">+3</div>
            </div>
            <div class="kpi-value" style="color:<?= htmlspecialchars($owner['theme_color']) ?>;"><?= $stats['today_rdv'] ?></div>
            <div class="kpi-label" data-fr="Aujourd'hui" data-en="Today">Aujourd'hui</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon" style="background:#FAFAF8;border:1px solid var(--border);"><i class="fa-regular fa-calendar" style="color:var(--black);"></i></div>
              <div class="kpi-change up" data-fr="Ce mois" data-en="This month">Ce mois</div>
            </div>
            <div class="kpi-value"><?= $stats['month_rdv'] ?></div>
            <div class="kpi-label" data-fr="Total RDV" data-en="Total bookings">Total RDV</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon" style="background:var(--gold-light);"><i class="fa-solid fa-chart-line" style="color:#78560A;"></i></div>
              <div class="kpi-change up">+22%</div>
            </div>
            <div class="kpi-value"><?= number_format($stats['month_fcfa']) ?></div>
            <div class="kpi-label" data-fr="FCFA ce mois" data-en="FCFA this month">FCFA ce mois</div>
          </div>
          <div class="kpi-card">
            <div class="kpi-top">
              <div class="kpi-icon" style="background:#ECFDF5;"><i class="fa-solid fa-star" style="color:#059669;"></i></div>
              <div class="kpi-change up">+0.2</div>
            </div>
            <div class="kpi-value"><?= $stats['rating'] ?></div>
            <div class="kpi-label" data-fr="Note moyenne" data-en="Avg. rating">Note moyenne</div>
          </div>
        </div>
        <div class="two-col">
          <div class="content-card">
            <div class="card-head">
              <div class="card-title">
                <i class="fa-regular fa-calendar-check"></i>
                <span data-fr="RDV du jour" data-en="Today's bookings">RDV du jour</span>
                <span class="card-badge" style="background:<?= htmlspecialchars($owner['theme_bg']) ?>;color:<?= htmlspecialchars($owner['theme_color']) ?>;"><?= $stats['today_rdv'] ?></span>
              </div>
              <button class="card-action" onclick="showPage('rdv',document.querySelector('[data-page=rdv]'))" data-fr="Voir tout →" data-en="See all →">Voir tout →</button>
            </div>
            <?php
            $status_map = [
              'en_cours'   => ['class'=>'st-now',  'fr'=>'En cours',   'en'=>'In progress'],
              'confirme'   => ['class'=>'st-ok',   'fr'=>'Confirmé',   'en'=>'Confirmed'],
              'en_attente' => ['class'=>'st-pend', 'fr'=>'En attente', 'en'=>'Pending'],
            ];
            foreach (array_slice($rdv_list, 0, 4) as $rdv):
              $st = $status_map[$rdv['status']] ?? $status_map['confirme'];
            ?>
            <div class="rdv-row">
              <div class="rdv-time-block">
                <div class="rdv-time"><?= htmlspecialchars($rdv['time']) ?></div>
                <div class="rdv-date-lbl"><?= htmlspecialchars($rdv['date']) ?></div>
              </div>
              <div class="rdv-bar" style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"></div>
              <div class="rdv-info">
                <div class="rdv-name"><?= htmlspecialchars($rdv['name']) ?></div>
                <div class="rdv-svc"><?= htmlspecialchars($rdv['service']) ?> · <?= htmlspecialchars($rdv['duration']) ?></div>
              </div>
              <div class="rdv-status <?= $st['class'] ?>" data-fr="<?= $st['fr'] ?>" data-en="<?= $st['en'] ?>"><?= $st['fr'] ?></div>
              <div class="rdv-actions">
                <a href="https://wa.me/<?= preg_replace('/\D/','',$rdv['phone']) ?>?text=Bonjour+<?= urlencode($rdv['name']) ?>+votre+RDV+est+confirmé" target="_blank" class="rdv-btn wa" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                <a href="tel:<?= htmlspecialchars($rdv['phone']) ?>" class="rdv-btn call" title="Appeler"><i class="fa-solid fa-phone"></i></a>
                <button class="rdv-btn cancel" onclick="cancelRdv(<?= $rdv['id'] ?>, '<?= htmlspecialchars($rdv['name']) ?>')" title="Annuler"><i class="fa-solid fa-xmark"></i></button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <div style="display:flex;flex-direction:column;gap:12px;">
            <div class="content-card">
              <div class="card-head"><div class="card-title"><i class="fa-solid fa-chart-pie"></i> <span data-fr="Statistiques" data-en="Statistics">Statistiques</span></div></div>
              <div class="mini-stats">
                <div class="ms-item"><div><div class="ms-label" data-fr="RDV ce mois" data-en="Bookings this month">RDV ce mois</div><div class="ms-bar-wrap"><div class="ms-bar" style="width:78%;background:<?= htmlspecialchars($owner['theme_color']) ?>;"></div></div></div><div class="ms-value"><?= $stats['month_rdv'] ?></div></div>
                <div class="ms-item"><div><div class="ms-label" data-fr="Vues du profil" data-en="Profile views">Vues du profil</div><div class="ms-bar-wrap"><div class="ms-bar" style="width:60%;background:var(--gold);"></div></div></div><div class="ms-value"><?= $stats['views'] ?></div></div>
                <div class="ms-item"><div><div class="ms-label" data-fr="Taux de confirmation" data-en="Confirmation rate">Taux de confirmation</div><div class="ms-bar-wrap"><div class="ms-bar" style="width:92%;background:#059669;"></div></div></div><div class="ms-value">92%</div></div>
              </div>
            </div>
            <div class="content-card">
              <div class="card-head"><div class="card-title"><i class="fa-regular fa-calendar"></i> <span data-fr="Demain" data-en="Tomorrow">Demain</span></div></div>
              <?php foreach (array_filter($rdv_list, fn($r) => $r['date'] === 'Demain') as $rdv): ?>
              <div class="today-row">
                <div class="today-dot" style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"></div>
                <div class="today-name"><?= htmlspecialchars($rdv['name']) ?></div>
                <div class="today-svc"><?= htmlspecialchars($rdv['service']) ?></div>
                <div class="today-time"><?= htmlspecialchars($rdv['time']) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ MES RDV ══ -->
    <div class="page" id="page-rdv">
      <div class="page-content">
        <div class="content-card">
          <div class="card-head">
            <div class="card-title">
              <i class="fa-regular fa-calendar-check"></i>
              <span data-fr="Toutes mes réservations" data-en="All my bookings">Toutes mes réservations</span>
              <span class="card-badge" style="background:<?= htmlspecialchars($owner['theme_bg']) ?>;color:<?= htmlspecialchars($owner['theme_color']) ?>;"><?= count($rdv_list) ?></span>
            </div>
            <div class="rdv-filter-tabs">
              <button class="filter-tab active" data-fr="Tout" data-en="All">Tout</button>
              <button class="filter-tab" data-fr="Aujourd'hui" data-en="Today">Aujourd'hui</button>
              <button class="filter-tab" data-fr="Demain" data-en="Tomorrow">Demain</button>
              <button class="filter-tab" data-fr="À venir" data-en="Upcoming">À venir</button>
            </div>
          </div>
          <?php foreach ($rdv_list as $rdv):
            $st = $status_map[$rdv['status']] ?? $status_map['confirme'];
          ?>
          <div class="rdv-row">
            <div class="rdv-time-block">
              <div class="rdv-time"><?= htmlspecialchars($rdv['time']) ?></div>
              <div class="rdv-date-lbl"><?= htmlspecialchars($rdv['date']) ?></div>
            </div>
            <div class="rdv-bar" style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"></div>
            <div class="rdv-info">
              <div class="rdv-name"><?= htmlspecialchars($rdv['name']) ?></div>
              <div class="rdv-svc"><?= htmlspecialchars($rdv['service']) ?> · <?= htmlspecialchars($rdv['duration']) ?></div>
              <div class="rdv-phone"><i class="fa-solid fa-phone" style="font-size:9px;"></i> <?= htmlspecialchars($rdv['phone']) ?></div>
            </div>
            <div class="rdv-amount"><?= number_format($rdv['amount']) ?> F</div>
            <div class="rdv-status <?= $st['class'] ?>" data-fr="<?= $st['fr'] ?>" data-en="<?= $st['en'] ?>"><?= $st['fr'] ?></div>
            <div class="rdv-actions">
              <a href="https://wa.me/<?= preg_replace('/\D/','',$rdv['phone']) ?>?text=Bonjour+<?= urlencode($rdv['name']) ?>+votre+RDV+<?= urlencode($rdv['service']) ?>+est+confirmé" target="_blank" class="rdv-btn wa"><i class="fa-brands fa-whatsapp"></i></a>
              <a href="tel:<?= htmlspecialchars($rdv['phone']) ?>" class="rdv-btn call"><i class="fa-solid fa-phone"></i></a>
              <button class="rdv-btn cancel" onclick="cancelRdv(<?= $rdv['id'] ?>, '<?= htmlspecialchars($rdv['name']) ?>')"><i class="fa-solid fa-xmark"></i></button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- ══ DISPONIBILITÉS ══ -->
    <div class="page" id="page-avail">
      <div class="avail-layout">
        <div class="avail-form-col">
          <div class="content-card">
            <div class="card-head">
              <div class="card-title"><i class="fa-regular fa-clock"></i> <span data-fr="Mes disponibilités" data-en="My availability">Mes disponibilités</span></div>
            </div>

            <!-- ★ REAL FORM — saves to JSON ★ -->
            <form method="POST" action="ClientLion.php#avail" id="avail-form">
              <input type="hidden" name="action" value="save_availability">

              <div class="avail-settings">
                <div class="avail-setting-row">
                  <div class="avail-setting-info">
                    <div class="avail-setting-title" data-fr="Double réservation" data-en="Double booking">Double réservation</div>
                    <div class="avail-setting-sub" data-fr="Accepter plusieurs clients au même créneau" data-en="Accept multiple clients at the same slot">Accepter plusieurs clients au même créneau</div>
                  </div>
                  <div class="toggle on" onclick="this.classList.toggle('on')"><div class="toggle-knob"></div></div>
                </div>
                <div class="avail-setting-row">
                  <div class="avail-setting-info">
                    <div class="avail-setting-title" data-fr="Durée des créneaux" data-en="Slot duration">Durée des créneaux</div>
                    <div class="avail-setting-sub" data-fr="Intervalle entre chaque réservation" data-en="Interval between bookings">Intervalle entre chaque réservation</div>
                  </div>
                  <select class="slot-select">
                    <option>15 min</option><option>30 min</option>
                    <option selected>45 min</option><option>1 heure</option>
                  </select>
                </div>
              </div>

              <div class="days-list">
                <?php foreach ($availability as $day):
                  $key = strtolower($day['day_en']);
                ?>
                <div class="day-row <?= !$day['open'] ? 'closed' : '' ?>" id="day-<?= $key ?>">
                  <!-- Hidden input: 1 = open, 0 = closed — updated by JS toggle -->
                  <input type="hidden" name="open_<?= $key ?>" class="day-open-input"
                         value="<?= $day['open'] ? '1' : '0' ?>">

                  <div class="day-toggle <?= $day['open'] ? 'on' : 'off' ?>"
                       onclick="toggleDay(this)">
                    <div class="toggle-knob"></div>
                  </div>

                  <div class="day-name"
                       data-fr="<?= $day['day'] ?>"
                       data-en="<?= $day['day_en'] ?>">
                    <?= $day['day'] ?>
                  </div>

                  <div class="day-times">
                    <input type="time"
                           name="start_<?= $key ?>"
                           value="<?= $day['start'] ?: '08:00' ?>"
                           class="time-input"
                           onchange="updatePreview()">
                    <span class="time-sep" data-fr="à" data-en="to">à</span>
                    <input type="time"
                           name="end_<?= $key ?>"
                           value="<?= $day['end'] ?: '18:00' ?>"
                           class="time-input"
                           onchange="updatePreview()">
                  </div>

                  <?php if (!$day['open']): ?>
                  <div class="day-closed-lbl" data-fr="Fermé" data-en="Closed">Fermé</div>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
              </div>

              <div class="avail-card-footer">
                <button type="submit" class="save-btn"
                        style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"
                        data-fr="Enregistrer les disponibilités"
                        data-en="Save availability">
                  Enregistrer les disponibilités
                </button>
              </div>
            </form>
            <!-- /avail-form -->

          </div>
        </div>

        <!-- Phone preview -->
        <div class="avail-preview-col">
          <div class="avail-preview-label" data-fr="Aperçu — vue client" data-en="Preview — customer view">Aperçu — vue client</div>
          <div class="phone-frame">
            <div class="phone-notch"></div>
            <div class="phone-screen" style="background:<?= htmlspecialchars($owner['theme_bg']) ?>;">
              <div class="ph-header" style="background:<?= htmlspecialchars($owner['theme_color']) ?>;">
                <div class="ph-biz-name"><?= htmlspecialchars($owner['name']) ?></div>
                <div class="ph-avail-title" data-fr="Nos horaires" data-en="Our hours">Nos horaires</div>
              </div>
              <div class="ph-body">
                <?php foreach ($availability as $day): ?>
                <div class="ph-day <?= !$day['open'] ? 'ph-day-closed' : '' ?>">
                  <span class="ph-day-name"
                        data-fr="<?= substr($day['day'],0,3) ?>"
                        data-en="<?= substr($day['day_en'],0,3) ?>">
                    <?= substr($day['day'],0,3) ?>
                  </span>
                  <?php if ($day['open']): ?>
                    <span class="ph-day-time"><?= $day['start'] ?> – <?= $day['end'] ?></span>
                  <?php else: ?>
                    <span class="ph-day-closed-txt" data-fr="Fermé" data-en="Closed">Fermé</span>
                  <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <div class="ph-book-btn"
                     style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"
                     data-fr="Prendre un RDV"
                     data-en="Book appointment">Prendre un RDV</div>
              </div>
              <div class="ph-footer">
                <span data-fr="Propulsé par" data-en="Powered by">Propulsé par</span>
                <strong style="color:var(--gold);">LionRDV</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ MES SERVICES ══ -->
    <div class="page" id="page-services">
      <div class="page-content">
        <div class="content-card">
          <div class="card-head">
            <div class="card-title"><i class="fa-solid fa-list-ul"></i> <span data-fr="Mes services" data-en="My services">Mes services</span></div>
            <button class="card-action" onclick="showAddService()" data-fr="+ Ajouter" data-en="+ Add">+ Ajouter</button>
          </div>
          <?php foreach ($services as $svc): ?>
          <div class="svc-row">
            <div class="svc-color-bar" style="background:<?= htmlspecialchars($svc['color']) ?>;"></div>
            <div class="svc-info">
              <div class="svc-name"><?= htmlspecialchars($svc['name']) ?></div>
              <div class="svc-detail"><?= htmlspecialchars($svc['duration']) ?></div>
            </div>
            <div class="svc-price"><?= number_format($svc['price']) ?> F</div>
            <div class="svc-actions">
              <button class="svc-btn" data-fr="Modifier" data-en="Edit">Modifier</button>
              <button class="svc-btn danger" data-fr="Supprimer" data-en="Delete">Supprimer</button>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="svc-add-row" onclick="showAddService()">
            <i class="fa-solid fa-plus" style="color:<?= htmlspecialchars($owner['theme_color']) ?>;"></i>
            <span style="color:<?= htmlspecialchars($owner['theme_color']) ?>;"
                  data-fr="Ajouter un service" data-en="Add a service">Ajouter un service</span>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ QR CODE ══ -->
    <div class="page" id="page-qr">
      <div class="page-content">
        <div class="qr-centered">
          <div class="content-card qr-card">
            <div class="card-head">
              <div class="card-title"><i class="fa-solid fa-qrcode"></i> <span data-fr="Mon lien de réservation" data-en="My booking link">Mon lien de réservation</span></div>
            </div>
            <div class="qr-body">
              <div class="qr-box">
                <svg viewBox="0 0 100 100" width="140" height="140" xmlns="http://www.w3.org/2000/svg" style="border-radius:8px;background:#fff;padding:8px;">
                  <rect width="100" height="100" fill="white"/>
                  <rect x="5" y="5" width="28" height="28" rx="3" fill="#0A0A0A"/><rect x="8" y="8" width="22" height="22" rx="2" fill="white"/><rect x="12" y="12" width="14" height="14" rx="1" fill="#0A0A0A"/>
                  <rect x="67" y="5" width="28" height="28" rx="3" fill="#0A0A0A"/><rect x="70" y="8" width="22" height="22" rx="2" fill="white"/><rect x="74" y="12" width="14" height="14" rx="1" fill="#0A0A0A"/>
                  <rect x="5" y="67" width="28" height="28" rx="3" fill="#0A0A0A"/><rect x="8" y="70" width="22" height="22" rx="2" fill="white"/><rect x="12" y="74" width="14" height="14" rx="1" fill="#0A0A0A"/>
                  <rect x="40" y="5" width="5" height="5" fill="#0A0A0A"/><rect x="48" y="5" width="5" height="5" fill="#0A0A0A"/>
                  <rect x="40" y="40" width="5" height="5" fill="#0A0A0A"/><rect x="50" y="40" width="5" height="5" fill="#0A0A0A"/><rect x="60" y="40" width="5" height="5" fill="#0A0A0A"/>
                  <rect x="5" y="40" width="5" height="5" fill="#0A0A0A"/><rect x="15" y="40" width="5" height="5" fill="#0A0A0A"/><rect x="25" y="40" width="5" height="5" fill="#0A0A0A"/>
                  <rect x="70" y="40" width="5" height="5" fill="#0A0A0A"/><rect x="80" y="40" width="5" height="5" fill="#0A0A0A"/><rect x="90" y="40" width="5" height="5" fill="#0A0A0A"/>
                  <rect x="40" y="70" width="5" height="5" fill="#0A0A0A"/><rect x="55" y="75" width="5" height="5" fill="#0A0A0A"/>
                  <rect x="43" y="43" width="14" height="14" rx="3" fill="<?= htmlspecialchars($owner['theme_color']) ?>"/>
                  <text x="50" y="53" text-anchor="middle" font-family="Arial" font-size="7" font-weight="bold" fill="white">LT</text>
                </svg>
              </div>
              <div class="qr-link">lionrdv.cm/<?= htmlspecialchars($owner['slug']) ?></div>
              <div class="qr-btns">
                <button class="qr-btn black" onclick="copyLink()"><i class="fa-regular fa-copy"></i> <span data-fr="Copier le lien" data-en="Copy link">Copier le lien</span></button>
                <button class="qr-btn outline"><i class="fa-solid fa-download"></i> <span data-fr="QR Code" data-en="QR Code">QR Code</span></button>
                <a href="https://wa.me/?text=Réservez+chez+<?= urlencode($owner['name']) ?>+:+https://lionrdv.cm/<?= urlencode($owner['slug']) ?>" target="_blank" class="qr-btn wa"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
              </div>
              <div class="qr-stats">
                <div class="qs-card"><div class="qs-val"><?= $stats['views'] ?></div><div class="qs-lbl" data-fr="Vues ce mois" data-en="Views this month">Vues ce mois</div></div>
                <div class="qs-card"><div class="qs-val"><?= $stats['month_rdv'] ?></div><div class="qs-lbl" data-fr="RDV pris" data-en="Bookings taken">RDV pris</div></div>
                <div class="qs-card"><div class="qs-val">92%</div><div class="qs-lbl" data-fr="Taux conversion" data-en="Conversion rate">Taux conversion</div></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ══ MON PROFIL ══ -->
    <div class="page" id="page-profile">
      <div class="page-content">
        <div class="profile-grid">

          <!-- Business info -->
          <div class="content-card">
            <div class="card-head">
              <div class="card-title"><i class="fa-regular fa-building"></i> <span data-fr="Informations du business" data-en="Business information">Informations du business</span></div>
            </div>
            <div class="profile-form">

              <!-- Logo -->
              <div class="logo-upload-section">
                <div class="biz-logo-preview"
                     style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"
                     onclick="document.getElementById('logo-file').click()">
                  <span><?= htmlspecialchars($owner['initials']) ?></span>
                  <div class="logo-overlay"><i class="fa-solid fa-camera"></i></div>
                </div>
                <input type="file" id="logo-file" accept="image/*" hidden>
                <div class="logo-info">
                  <div class="logo-info-title" data-fr="Logo du business" data-en="Business logo">Logo du business</div>
                  <div class="logo-info-sub" data-fr="Cliquez pour changer" data-en="Click to change">Cliquez pour changer</div>
                  <div class="lt-badge">
                    <div class="lt-badge-mark">LT</div>
                    <span data-fr="LionTech apparaîtra aussi sur votre page" data-en="LionTech will also appear on your page">LionTech apparaîtra aussi sur votre page</span>
                  </div>
                </div>
              </div>

              <!-- ★ GALLERY UPLOAD SECTION ★ -->
              <div class="gallery-upload-section">
                <div class="gallery-upload-header">
                  <div class="gallery-upload-title">
                    <i class="fa-regular fa-images" style="color:<?= htmlspecialchars($owner['theme_color']) ?>;"></i>
                    <span data-fr="Photos de la galerie" data-en="Gallery photos">Photos de la galerie</span>
                  </div>
                  <div class="gallery-upload-sub"
                       data-fr="Ces photos apparaissent sur votre page publique"
                       data-en="These photos appear on your public page">
                    Ces photos apparaissent sur votre page publique
                  </div>
                </div>

                <!-- Existing photos -->
                <?php if (!empty($biz_gallery)): ?>
                <div class="gallery-existing">
                  <?php foreach ($biz_gallery as $photo): ?>
                  <div class="gallery-thumb-wrap">
                    <img src="../../<?= htmlspecialchars($photo['path']) ?>"
                         alt="<?= htmlspecialchars($photo['alt'] ?? '') ?>"
                         class="gallery-thumb">
                    <form method="POST" action="ClientLion.php#profile"
                          class="gallery-delete-form">
                      <input type="hidden" name="action" value="delete_photo">
                      <input type="hidden" name="photo_path"
                             value="<?= htmlspecialchars($photo['path']) ?>">
                      <button type="submit" class="gallery-delete-btn"
                              onclick="return confirm('Supprimer cette photo ?')"
                              title="Supprimer">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </form>
                  </div>
                  <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="gallery-empty"
                     data-fr="Aucune photo pour l'instant"
                     data-en="No photos yet">
                  <i class="fa-regular fa-image" style="font-size:28px;color:<?= htmlspecialchars($owner['theme_color']) ?>;opacity:0.4;margin-bottom:8px;"></i>
                  <span>Aucune photo pour l'instant</span>
                </div>
                <?php endif; ?>

                <!-- Upload form -->
                <form method="POST"
                      action="ClientLion.php#profile"
                      enctype="multipart/form-data"
                      id="gallery-form">
                  <input type="hidden" name="action" value="upload_gallery">

                  <label class="gallery-upload-btn"
                         for="gallery-photos"
                         style="border-color:<?= htmlspecialchars($owner['theme_color']) ?>;color:<?= htmlspecialchars($owner['theme_color']) ?>;">
                    <i class="fa-solid fa-plus"></i>
                    <span data-fr="Ajouter des photos" data-en="Add photos">Ajouter des photos</span>
                    <input type="file"
                           id="gallery-photos"
                           name="gallery_photos[]"
                           accept="image/*"
                           multiple
                           hidden
                           onchange="previewGalleryPhotos(this)">
                  </label>

                  <!-- Preview before upload -->
                  <div id="gallery-preview-row" class="gallery-preview-row" style="display:none;"></div>

                  <button type="submit"
                          id="gallery-save-btn"
                          class="gallery-save-btn"
                          style="background:<?= htmlspecialchars($owner['theme_color']) ?>;display:none;"
                          data-fr="Enregistrer les photos"
                          data-en="Save photos">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Enregistrer les photos
                  </button>
                </form>
              </div>
              <!-- /gallery-upload-section -->

              <!-- Profile fields -->
              <div class="pf-row">
                <div class="pf-field">
                  <label data-fr="Nom du business" data-en="Business name">Nom du business</label>
                  <input type="text" value="<?= htmlspecialchars($owner['name']) ?>">
                </div>
                <div class="pf-field">
                  <label data-fr="Type" data-en="Type">Type</label>
                  <input type="text" value="<?= htmlspecialchars($owner['type']) ?>">
                </div>
              </div>
              <div class="pf-row">
                <div class="pf-field">
                  <label data-fr="Ville / Quartier" data-en="City / Area">Ville / Quartier</label>
                  <input type="text" value="<?= htmlspecialchars($owner['location']) ?>">
                </div>
                <div class="pf-field">
                  <label>WhatsApp</label>
                  <input type="text" value="<?= htmlspecialchars($owner['whatsapp']) ?>">
                </div>
              </div>
              <div class="pf-field full">
                <label data-fr="Description" data-en="Description">Description</label>
                <textarea rows="3" placeholder="Description visible sur votre page de réservation..."></textarea>
              </div>
            </div>
          </div>

          <!-- Theme -->
          <div class="content-card">
            <div class="card-head">
              <div class="card-title"><i class="fa-solid fa-palette"></i> <span data-fr="Thème & couleurs" data-en="Theme & colors">Thème & couleurs</span></div>
            </div>
            <div class="profile-form">
              <div class="theme-current">
                <div class="theme-current-dot" id="current-color-dot" style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"></div>
                <span data-fr="Couleur principale" data-en="Main color">Couleur principale</span>
                <strong id="current-color-name">Rose Beauté</strong>
              </div>
              <div class="theme-dots-row">
                <div class="theme-cdot active" style="background:#D4447A;" onclick="selectTheme(this,'#D4447A','#FFF0F8','Rose Beauté')"></div>
                <div class="theme-cdot" style="background:#0A0A0A;" onclick="selectTheme(this,'#0A0A0A','#FAFAF8','Noir & Or')"></div>
                <div class="theme-cdot" style="background:#0EA5E9;" onclick="selectTheme(this,'#0EA5E9','#F0F9FF','Bleu Médical')"></div>
                <div class="theme-cdot" style="background:#059669;" onclick="selectTheme(this,'#059669','#F0FDF4','Vert Santé')"></div>
                <div class="theme-cdot" style="background:#E07B39;" onclick="selectTheme(this,'#E07B39','#FFF8F3','Orange Chaud')"></div>
                <div class="theme-cdot" style="background:#7C3AED;" onclick="selectTheme(this,'#7C3AED','#F5F3FF','Violet Pro')"></div>
                <div class="theme-cdot" style="background:#DC2626;" onclick="selectTheme(this,'#DC2626','#FFF5F5','Rouge Élégant')"></div>
                <div class="theme-cdot" style="background:#1B4332;" onclick="selectTheme(this,'#1B4332','#F9F6F0','Vert & Or')"></div>
              </div>
              <div class="pf-field">
                <label data-fr="Aperçu bouton client" data-en="Customer button preview">Aperçu bouton client</label>
                <div class="btn-preview" id="btn-preview"
                     style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"
                     data-fr="Prendre un RDV" data-en="Book appointment">Prendre un RDV</div>
              </div>
              <div class="lt-info-box">
                <div class="lt-info-mark">LT</div>
                <div>
                  <div class="lt-info-title" data-fr="Branding LionTech inclus" data-en="LionTech branding included">Branding LionTech inclus</div>
                  <div class="lt-info-sub" data-fr="Le logo LionTech apparaîtra toujours sur votre page client." data-en="LionTech logo will always appear on your client page.">Le logo LionTech apparaîtra toujours sur votre page client.</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Account -->
          <div class="content-card" style="grid-column:1/-1;">
            <div class="card-head">
              <div class="card-title"><i class="fa-regular fa-user"></i> <span data-fr="Compte" data-en="Account">Compte</span></div>
            </div>
            <div class="profile-form">
              <div class="pf-row">
                <div class="pf-field">
                  <label data-fr="Email de connexion" data-en="Login email">Email de connexion</label>
                  <input type="email" value="<?= htmlspecialchars($_SESSION['owner_email'] ?? '') ?>">
                </div>
                <div class="pf-field">
                  <label data-fr="Nouveau mot de passe" data-en="New password">Nouveau mot de passe</label>
                  <input type="password" placeholder="••••••••••">
                </div>
                <div class="pf-field">
                  <label data-fr="Plan actuel" data-en="Current plan">Plan actuel</label>
                  <div class="plan-pill"><?= htmlspecialchars($owner['plan']) ?> — 10 000 FCFA/mois</div>
                </div>
              </div>
              <button class="save-btn"
                      style="background:<?= htmlspecialchars($owner['theme_color']) ?>;"
                      data-fr="Enregistrer les modifications"
                      data-en="Save changes">
                Enregistrer les modifications
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>

  </main>
</div>

<!-- CANCEL MODAL -->
<div class="modal-overlay" id="cancel-modal">
  <div class="modal">
    <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <h2 class="modal-title" data-fr="Annuler ce RDV ?" data-en="Cancel this booking?">Annuler ce RDV ?</h2>
    <p class="modal-sub">Êtes-vous sûr de vouloir annuler le RDV de <strong id="modal-name"></strong> ?</p>
    <div class="modal-actions">
      <button class="modal-btn cancel-btn" onclick="closeModal()" data-fr="Non, garder" data-en="No, keep">Non, garder</button>
      <button class="modal-btn confirm-btn" id="modal-confirm" data-fr="Oui, annuler" data-en="Yes, cancel">Oui, annuler</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<?php endif; ?>

<script src="ClientLion.js"></script>
<script>
/* ── Show PHP flash message as toast on page load ── */
document.addEventListener('DOMContentLoaded', function() {
  var flash = document.getElementById('php-flash');
  if (flash) {
    showToast(flash.dataset.msg, flash.dataset.type === 'success' ? 'success' : 'danger');
  }
});

/* ── Gallery photo preview before upload ── */
function previewGalleryPhotos(input) {
  var row     = document.getElementById('gallery-preview-row');
  var saveBtn = document.getElementById('gallery-save-btn');
  if (!row || !input.files || !input.files.length) return;

  row.innerHTML = '';
  row.style.display = 'flex';
  saveBtn.style.display = 'flex';

  Array.from(input.files).forEach(function(file) {
    var reader = new FileReader();
    reader.onload = function(e) {
      var img = document.createElement('img');
      img.src = e.target.result;
      img.className = 'gallery-preview-thumb';
      row.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

/* ── Toggle day open/closed — updates hidden input ── */
function toggleDay(btn) {
  var isOn = btn.classList.contains('on');
  btn.classList.toggle('on',  !isOn);
  btn.classList.toggle('off',  isOn);
  var row       = btn.closest('.day-row');
  var hidden    = row.querySelector('.day-open-input');
  var closedLbl = row.querySelector('.day-closed-lbl');
  row.classList.toggle('closed', isOn);
  if (hidden)    hidden.value = isOn ? '0' : '1';
  if (closedLbl) closedLbl.style.display = isOn ? '' : 'none';
  updatePreview();
}
</script>
</body>
</html>