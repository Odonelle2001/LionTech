<?php
/* ============================================================
   ClientLion.php — Espace Propriétaire LionRDV
   Connexion : WhatsApp + mot de passe (pas d'email)
   Lié à : businesses + owners + services + availability + gallery + reservations
   ============================================================ */
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../db.php';

/* ── helpers ────────────────────────────────────────────── */
function h(?string $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function toSlug(string $s): string { return preg_replace('/-+/','-',preg_replace('/[^a-z0-9\-]/','',strtolower(trim(str_replace(' ','-',$s))))); }

/* ── session state ──────────────────────────────────────── */
$logged        = !empty($_SESSION['cl_owner_id']);
$owner_id      = (int)($_SESSION['cl_owner_id'] ?? 0);
$biz_id        = (int)($_SESSION['cl_biz_id']   ?? 0);
$must_onboard  = !empty($_SESSION['cl_onboard']);   /* onboarding en cours */
$onboard_step  = (int)($_SESSION['cl_onboard_step'] ?? 1);
$error         = '';
$success       = '';

/* ── upload helpers ─────────────────────────────────────── */
function upload_file(array $file, string $slug, string $subdir, string $prefix): ?string {
  if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
  $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'])) return null;
  $dir = dirname(__DIR__) . '/uploads/' . $slug . '/' . $subdir . '/';
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $name = $prefix . '_' . time() . '.' . $ext;
  return move_uploaded_file($file['tmp_name'], $dir . $name) ? 'uploads/' . $slug . '/' . $subdir . '/' . $name : null;
}

/* ── load owner + business from DB ─────────────────────── */
function load_owner_biz(PDO $pdo, int $owner_id): ?array {
  $st = $pdo->prepare(
    "SELECT o.*, b.id AS biz_id, b.slug, b.name AS biz_name, b.initials,
            b.type AS biz_type, b.theme_color, b.button_color, b.plan,
            b.whatsapp AS biz_wa, b.city, b.neighborhood,
            b.gal_max_photos, b.gal_display_mode, b.show_connexion_btn,
            b.svc_display_style, b.language, b.global_font
     FROM owners o JOIN businesses b ON b.id = o.business_id
     WHERE o.id = ?"
  );
  $st->execute([$owner_id]);
  return $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

/* ════════════════════════════════════════════════════════
   HANDLE ALL POST ACTIONS
════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
  $action = $_POST['action'];

  /* ── LOGIN ──────────────────────────────────────────── */
  if ($action === 'login') {
    $wa  = preg_replace('/\D/', '', trim($_POST['whatsapp'] ?? ''));
    $pwd = trim($_POST['password'] ?? '');
    $st  = $pdo->prepare("SELECT * FROM owners WHERE whatsapp = ?");
    $st->execute([$wa]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row && password_verify($pwd, $row['password_hash'])) {
      $_SESSION['cl_owner_id'] = $row['id'];
      $_SESSION['cl_biz_id']   = $row['business_id'];
      if (!empty($row['must_change_pwd'])) {
        $_SESSION['cl_onboard']      = true;
        $_SESSION['cl_onboard_step'] = 1;
      }
      header('Location: ClientLion.php'); exit;
    } else {
      $error = 'Numéro WhatsApp ou mot de passe incorrect.';
    }
  }

  /* ── LOGOUT ─────────────────────────────────────────── */
  if ($action === 'logout' && $logged) {
    session_destroy(); header('Location: ClientLion.php'); exit;
  }

  /* From here all actions require login */
  if ($logged) {
    $data = load_owner_biz($pdo, $owner_id);
    $bid  = $data['biz_id'] ?? $biz_id;
    $slug = $data['slug'] ?? '';

    /* ── ONBOARD STEP 1: change password ───────────────── */
    if ($action === 'onboard_password') {
      $new  = trim($_POST['new_password'] ?? '');
      $conf = trim($_POST['confirm_password'] ?? '');
      if (strlen($new) < 8) { $error = 'Le mot de passe doit faire au moins 8 caractères.'; }
      elseif ($new !== $conf) { $error = 'Les mots de passe ne correspondent pas.'; }
      else {
        $pdo->prepare("UPDATE owners SET password_hash=?, must_change_pwd=0 WHERE id=?")
            ->execute([password_hash($new, PASSWORD_DEFAULT), $owner_id]);
        $_SESSION['cl_onboard_step'] = 2;
        $success = 'Mot de passe mis à jour.';
      }
    }

    /* ── ONBOARD STEP 2: save availability ─────────────── */
    if ($action === 'onboard_availability' || $action === 'save_availability') {
      $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
      $pdo->prepare("DELETE FROM availability WHERE business_id=?")->execute([$bid]);
      $fr_names = ['monday'=>'Lundi','tuesday'=>'Mardi','wednesday'=>'Mercredi',
                   'thursday'=>'Jeudi','friday'=>'Vendredi','saturday'=>'Samedi','sunday'=>'Dimanche'];
      $en_names = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday',
                   'thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
      $day_index = ['sunday'=>0,'monday'=>1,'tuesday'=>2,'wednesday'=>3,'thursday'=>4,'friday'=>5,'saturday'=>6];
      $st = $pdo->prepare("INSERT INTO availability (business_id,day_name,day_en,day_index,is_open,open_time,close_time) VALUES (?,?,?,?,?,?,?)");
      foreach ($days as $d) {
        $open  = !empty($_POST['day_open_'.$d]) ? 1 : 0;
        $ot    = $open ? ($_POST['open_'.$d]  ?? null) : null;
        $ct    = $open ? ($_POST['close_'.$d] ?? null) : null;
        $st->execute([$bid, $fr_names[$d], $en_names[$d], $day_index[$d], $open, $ot, $ct]);
      }
      if ($action === 'onboard_availability') { $_SESSION['cl_onboard_step'] = 3; }
      $success = 'Disponibilités enregistrées.';
    }

    /* ── ONBOARD STEP 3: save services ─────────────────── */
    if ($action === 'onboard_services' || $action === 'save_services') {
      $ids = $_POST['svc_id'] ?? [];
      foreach ($ids as $sid) {
        $sid = (int)$sid;
        $nm  = trim($_POST['svc_name_'.$sid]  ?? '');
        $dur = trim($_POST['svc_dur_'.$sid]   ?? '');
        $pr  = (int)preg_replace('/\D/','',$_POST['svc_price_'.$sid] ?? '0');
        $desc= trim($_POST['svc_desc_'.$sid]  ?? '');
        $col = trim($_POST['svc_color_'.$sid] ?? '#C9A84C');
        $act = !empty($_POST['svc_active_'.$sid]) ? 1 : 0;
        $pdo->prepare("UPDATE services SET name=?,duration=?,price=?,description=?,color=?,active=? WHERE id=? AND business_id=?")
            ->execute([$nm,$dur,$pr,$desc,$col,$act,$sid,$bid]);
      }
      if ($action === 'onboard_services') { $_SESSION['cl_onboard_step'] = 4; }
      $success = 'Services enregistrés.';
    }

    /* ── ONBOARD STEP 4: upload gallery ─────────────────── */
    if ($action === 'onboard_gallery' || $action === 'upload_gallery') {
      $max    = (int)($data['gal_max_photos'] ?? 9);
      $count_st = $pdo->prepare("SELECT COUNT(*) FROM gallery WHERE business_id=?");
      $count_st->execute([$bid]);
      $current = (int)$count_st->fetchColumn();
      $files   = $_FILES['gallery_photos'] ?? [];
      $uploaded = 0;
      if (!empty($files['tmp_name'])) {
        $names = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
        $orignames = is_array($files['name']) ? $files['name'] : [$files['name']];
        foreach ($names as $i => $tmp) {
          if ($current + $uploaded >= $max) break;
          $fake = ['tmp_name'=>$tmp,'name'=>$orignames[$i]];
          $path = upload_file($fake, $slug, 'gallery', 'gal');
          if ($path) {
            $pdo->prepare("INSERT INTO gallery (business_id,path,display_order) VALUES (?,?,?)")
                ->execute([$bid, $path, $current+$uploaded]);
            $uploaded++;
          }
        }
      }
      if ($action === 'onboard_gallery') { $_SESSION['cl_onboard_step'] = 5; }
      $success = $uploaded . ' photo(s) ajoutée(s).';
    }

    /* ── ONBOARD STEP 5: profile + finish ───────────────── */
    if ($action === 'onboard_profile' || $action === 'save_profile') {
      $name = trim($_POST['owner_name'] ?? '');
      /* avatar upload */
      $avatar_path = null;
      if (!empty($_FILES['avatar']['tmp_name'])) {
        $avatar_path = upload_file($_FILES['avatar'], $slug, '', 'avatar');
        if ($avatar_path) {
          $pdo->prepare("UPDATE businesses SET avatar_photo=? WHERE id=?")->execute([$avatar_path,$bid]);
        }
      }
      if ($name) $pdo->prepare("UPDATE owners SET name=? WHERE id=?")->execute([$name,$owner_id]);
      /* cover if given access */
      if (!empty($_FILES['cover']['tmp_name'])) {
        $cp = upload_file($_FILES['cover'], $slug, '', 'cover');
        if ($cp) $pdo->prepare("UPDATE businesses SET cover_photo=? WHERE id=?")->execute([$cp,$bid]);
      }
      /* dark mode pref */
      $dark = !empty($_POST['dark_mode']) ? 1 : 0;
      $pdo->prepare("UPDATE owners SET dark_mode=? WHERE id=?")->execute([$dark,$owner_id]);
      if ($action === 'onboard_profile') {
        unset($_SESSION['cl_onboard'], $_SESSION['cl_onboard_step']);
        $success = 'Profil complet. Bienvenue sur votre espace !';
      } else {
        $success = 'Profil mis à jour.';
      }
    }

    /* ── DELETE GALLERY PHOTO ───────────────────────────── */
    if ($action === 'delete_gallery') {
      $gid = (int)($_POST['gallery_id'] ?? 0);
      $st  = $pdo->prepare("SELECT path FROM gallery WHERE id=? AND business_id=?");
      $st->execute([$gid, $bid]);
      $row2 = $st->fetch();
      if ($row2) {
        $full = dirname(__DIR__) . '/' . $row2['path'];
        if (file_exists($full)) unlink($full);
        $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$gid]);
      }
      $success = 'Photo supprimée.';
    }

    /* ── CANCEL RESERVATION ─────────────────────────────── */
    if ($action === 'cancel_reservation') {
      $rid = (int)($_POST['reservation_id'] ?? 0);
      $pdo->prepare("UPDATE reservations SET status='cancelled', cancelled_at=NOW() WHERE id=? AND business_id=?")
          ->execute([$rid, $bid]);
      $success = 'RDV annulé.';
    }

    /* ── SAVE THEME ─────────────────────────────────────── */
    if ($action === 'save_theme') {
      $dark = !empty($_POST['dark_mode']) ? 1 : 0;
      $lang = $_POST['lang'] ?? 'fr';
      $pdo->prepare("UPDATE owners SET dark_mode=?, language_pref=? WHERE id=?")
          ->execute([$dark, $lang, $owner_id]);
      $success = 'Préférences enregistrées.';
    }
  }
}

/* ── reload session data after post ────────────────────── */
if ($logged) {
  $ownerData = load_owner_biz($pdo, $owner_id);
  if (!$ownerData) { session_destroy(); header('Location: ClientLion.php'); exit; }
  $biz_id   = $ownerData['biz_id'];
  $slug     = $ownerData['slug'];
  $col      = $ownerData['theme_color'] ?? '#C9A84C';
  $dark_mode = !empty($ownerData['dark_mode']);
  $lang_pref = $ownerData['language_pref'] ?? 'fr';

  /* load services */
  $services = $pdo->prepare("SELECT * FROM services WHERE business_id=? ORDER BY display_order,id");
  $services->execute([$biz_id]);
  $services = $services->fetchAll(PDO::FETCH_ASSOC);

  /* load availability */
  $avail = $pdo->prepare("SELECT * FROM availability WHERE business_id=? ORDER BY day_index");
  $avail->execute([$biz_id]);
  $avail = $avail->fetchAll(PDO::FETCH_ASSOC);

  /* load gallery */
  $gallery = $pdo->prepare("SELECT * FROM gallery WHERE business_id=? ORDER BY display_order,id");
  $gallery->execute([$biz_id]);
  $gallery = $gallery->fetchAll(PDO::FETCH_ASSOC);

  /* load reservations (last 30 days + upcoming) */
  $reservations = $pdo->prepare(
    "SELECT r.*, GROUP_CONCAT(rs.service_name SEPARATOR ', ') AS services_list
     FROM reservations r
     LEFT JOIN reservation_services rs ON rs.reservation_id = r.id
     WHERE r.business_id=? AND r.rdv_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY r.id ORDER BY r.rdv_date DESC, r.rdv_time DESC LIMIT 60"
  );
  $reservations->execute([$biz_id]);
  $reservations = $reservations->fetchAll(PDO::FETCH_ASSOC);

  /* stats */
  $stats = $pdo->prepare(
    "SELECT
       COUNT(*) AS total_month,
       SUM(CASE WHEN status='confirmed' OR status='completed' THEN 1 ELSE 0 END) AS confirmed,
       SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
       SUM(CASE WHEN rdv_date >= CURDATE() AND rdv_date < DATE_ADD(CURDATE(),INTERVAL 7 DAY) AND status='confirmed' THEN 1 ELSE 0 END) AS upcoming_week
     FROM reservations WHERE business_id=? AND rdv_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01')"
  );
  $stats->execute([$biz_id]);
  $stats = $stats->fetch(PDO::FETCH_ASSOC);

  $cancel_rate = $stats['total_month'] > 0 ? round($stats['cancelled'] / $stats['total_month'] * 100) : 0;

  /* revenue estimate */
  $rev = $pdo->prepare(
    "SELECT COALESCE(SUM(s.price),0) AS total
     FROM reservations r
     JOIN reservation_services rs ON rs.reservation_id=r.id
     JOIN services s ON s.name=rs.service_name AND s.business_id=r.business_id
     WHERE r.business_id=? AND r.rdv_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01') AND r.status != 'cancelled'"
  );
  $rev->execute([$biz_id]);
  $revenue = (int)$rev->fetchColumn();

  /* week chart */
  $week = $pdo->prepare(
    "SELECT DAYOFWEEK(rdv_date) AS dow, COUNT(*) AS cnt
     FROM reservations WHERE business_id=? AND rdv_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status != 'cancelled'
     GROUP BY dow"
  );
  $week->execute([$biz_id]);
  $week_map = []; foreach($week->fetchAll(PDO::FETCH_ASSOC) as $r) $week_map[$r['dow']] = $r['cnt'];
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= $logged && $dark_mode ? 'dark' : 'light' ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $logged ? h($ownerData['biz_name']).' — ' : '' ?>Mon Espace · LionRDV</title>
  <link rel="stylesheet" href="clientLion.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php /* ═══════════════════════ LOGIN PAGE ═══════════════════════ */ ?>
<?php if (!$logged): ?>
<div class="cl-login-wrap">
  <div class="cl-login-box">
    <div class="cl-login-brand">
      <span class="cl-login-lion">🦁</span>
      <span class="cl-login-name">LionRDV</span>
    </div>
    <h1 class="cl-login-title">Espace propriétaire</h1>
    <p class="cl-login-sub">Connectez-vous avec votre numéro WhatsApp</p>
    <?php if ($error): ?>
    <div class="cl-alert cl-alert-error"><?= h($error) ?></div>
    <?php endif; ?>
    <form method="POST" class="cl-login-form">
      <input type="hidden" name="action" value="login">
      <div class="cl-form-group">
        <label class="cl-label">Numéro WhatsApp</label>
        <div class="cl-prefix-wrap">
          <span class="cl-prefix">+237</span>
          <input class="cl-input" type="tel" name="whatsapp" placeholder="6XX XXX XXX" required autofocus>
        </div>
      </div>
      <div class="cl-form-group">
        <label class="cl-label">Mot de passe</label>
        <input class="cl-input" type="password" name="password" placeholder="Votre mot de passe" required>
      </div>
      <button type="submit" class="cl-btn-primary cl-btn-full">Se connecter</button>
    </form>
    <div class="cl-login-footer">Propulsé par <strong>LionTech</strong> · LionRDV</div>
  </div>
</div>

<?php /* ═══════════════════════ ONBOARDING ═══════════════════════ */
elseif ($must_onboard): ?>
<div class="cl-onboard-wrap">
  <div class="cl-onboard-box">
    <!-- Steps indicator -->
    <div class="cl-onboard-steps">
      <?php for ($s = 1; $s <= 5; $s++): ?>
      <div class="cl-ob-step <?= $s < $onboard_step ? 'done' : ($s == $onboard_step ? 'active' : '') ?>">
        <div class="cl-ob-dot"><?= $s < $onboard_step ? '✓' : $s ?></div>
        <div class="cl-ob-lbl"><?= ['','Mot de passe','Disponibilités','Services','Galerie','Profil'][$s] ?></div>
      </div>
      <?php if ($s < 5): ?><div class="cl-ob-line <?= $s < $onboard_step ? 'done' : '' ?>"></div><?php endif; ?>
      <?php endfor; ?>
    </div>

    <?php if ($error): ?><div class="cl-alert cl-alert-error"><?= h($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="cl-alert cl-alert-success"><?= h($success) ?></div><?php endif; ?>

    <?php /* STEP 1: PASSWORD */ if ($onboard_step === 1): ?>
    <div class="cl-ob-content">
      <div class="cl-ob-icon">🔐</div>
      <h2 class="cl-ob-title">Créez votre mot de passe</h2>
      <p class="cl-ob-sub">Vous utilisez un mot de passe temporaire. Choisissez un mot de passe sécurisé pour protéger votre compte.</p>
      <form method="POST" class="cl-ob-form">
        <input type="hidden" name="action" value="onboard_password">
        <div class="cl-form-group">
          <label class="cl-label">Nouveau mot de passe (min. 8 caractères)</label>
          <input class="cl-input" type="password" name="new_password" id="new-pwd" placeholder="Nouveau mot de passe" required oninput="checkPwdStrength(this.value)">
          <div class="cl-pwd-bar"><div class="cl-pwd-fill" id="pwd-fill"></div></div>
          <div class="cl-pwd-hint" id="pwd-hint">Entrez votre mot de passe</div>
        </div>
        <div class="cl-form-group">
          <label class="cl-label">Confirmer le mot de passe</label>
          <input class="cl-input" type="password" name="confirm_password" placeholder="Confirmer" required>
        </div>
        <button type="submit" class="cl-btn-primary cl-btn-full">Suivant →</button>
      </form>
    </div>

    <?php /* STEP 2: AVAILABILITY */ elseif ($onboard_step === 2): ?>
    <div class="cl-ob-content">
      <div class="cl-ob-icon">📅</div>
      <h2 class="cl-ob-title">Vos disponibilités</h2>
      <p class="cl-ob-sub">Définissez vos jours et horaires d'ouverture. Vous pourrez les modifier à tout moment.</p>
      <form method="POST" class="cl-ob-form">
        <input type="hidden" name="action" value="onboard_availability">
        <?php
        $default_days = [
          ['monday','Lundi',true,'08:00','18:00'],['tuesday','Mardi',true,'08:00','18:00'],
          ['wednesday','Mercredi',true,'08:00','18:00'],['thursday','Jeudi',true,'08:00','18:00'],
          ['friday','Vendredi',true,'08:00','19:00'],['saturday','Samedi',true,'09:00','17:00'],
          ['sunday','Dimanche',false,'',''],
        ];
        foreach ($default_days as [$ek,$fr,$open,$ot,$ct]):
        ?>
        <div class="cl-day-row" id="row-<?= $ek ?>">
          <label class="cl-day-tog">
            <input type="checkbox" name="day_open_<?= $ek ?>" <?= $open?'checked':'' ?> onchange="togDay('<?= $ek ?>',this.checked)">
            <span class="cl-day-knob"></span>
          </label>
          <span class="cl-day-nm <?= !$open?'cl-day-off':'' ?>" id="dnm-<?= $ek ?>"><?= $fr ?></span>
          <div class="cl-day-times" id="dtimes-<?= $ek ?>" <?= !$open?'style="display:none"':'' ?>>
            <input class="cl-time-inp" type="time" name="open_<?= $ek ?>" value="<?= $ot ?>">
            <span class="cl-day-sep">–</span>
            <input class="cl-time-inp" type="time" name="close_<?= $ek ?>" value="<?= $ct ?>">
          </div>
          <?php if (!$open): ?><span class="cl-day-closed" id="dclosed-<?= $ek ?>">Fermé</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="cl-btn-primary cl-btn-full">Suivant →</button>
      </form>
    </div>

    <?php /* STEP 3: SERVICES */ elseif ($onboard_step === 3): ?>
    <div class="cl-ob-content">
      <div class="cl-ob-icon">⭐</div>
      <h2 class="cl-ob-title">Vos services</h2>
      <p class="cl-ob-sub">Ces services ont été créés selon votre type de commerce. Modifiez les noms, prix et durées.</p>
      <form method="POST" class="cl-ob-form">
        <input type="hidden" name="action" value="onboard_services">
        <?php foreach ($services as $svc): ?>
        <div class="cl-svc-card">
          <input type="hidden" name="svc_id[]" value="<?= $svc['id'] ?>">
          <div class="cl-svc-color-row">
            <input type="color" class="cl-clr-sw" name="svc_color_<?= $svc['id'] ?>" value="<?= h($svc['color']) ?>">
            <input class="cl-input" type="text" name="svc_name_<?= $svc['id'] ?>" value="<?= h($svc['name']) ?>" placeholder="Nom du service">
            <label class="cl-svc-tog">
              <input type="checkbox" name="svc_active_<?= $svc['id'] ?>" <?= $svc['active']?'checked':'' ?>>
              <span class="cl-svc-knob"></span>
            </label>
          </div>
          <div class="cl-svc-row2">
            <div class="cl-form-group">
              <label class="cl-label">Durée</label>
              <input class="cl-input" type="text" name="svc_dur_<?= $svc['id'] ?>" value="<?= h($svc['duration']) ?>" placeholder="ex: 45 min">
            </div>
            <div class="cl-form-group">
              <label class="cl-label">Prix (FCFA)</label>
              <input class="cl-input" type="number" name="svc_price_<?= $svc['id'] ?>" value="<?= h($svc['price']) ?>" placeholder="2500">
            </div>
          </div>
          <div class="cl-form-group">
            <label class="cl-label">Description courte</label>
            <input class="cl-input" type="text" name="svc_desc_<?= $svc['id'] ?>" value="<?= h($svc['description'] ?? '') ?>" placeholder="Description visible par les clients">
          </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="cl-btn-primary cl-btn-full">Suivant →</button>
      </form>
    </div>

    <?php /* STEP 4: GALLERY */ elseif ($onboard_step === 4): ?>
    <div class="cl-ob-content">
      <div class="cl-ob-icon">📸</div>
      <h2 class="cl-ob-title">Votre galerie photos</h2>
      <p class="cl-ob-sub">Ajoutez des photos de votre commerce. Maximum <?= (int)($ownerData['gal_max_photos'] ?? 9) ?> photos.</p>
      <form method="POST" enctype="multipart/form-data" class="cl-ob-form">
        <input type="hidden" name="action" value="onboard_gallery">
        <div class="cl-gallery-upload-zone" onclick="document.getElementById('gal-inp').click()">
          <svg style="width:2rem;height:2rem;stroke:var(--brand);fill:none;stroke-width:1.5;stroke-linecap:round;" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          <div class="cl-guz-title">Cliquez ou glissez vos photos ici</div>
          <div class="cl-guz-sub">JPG, PNG, WEBP · Plusieurs fichiers acceptés</div>
          <input type="file" id="gal-inp" name="gallery_photos[]" accept="image/*" multiple style="display:none" onchange="previewGallery(this)">
        </div>
        <div class="cl-gal-preview-grid" id="gal-preview-grid"></div>
        <button type="submit" class="cl-btn-primary cl-btn-full" style="margin-top:1rem;">Suivant →</button>
      </form>
      <button type="button" class="cl-btn-secondary cl-btn-full" onclick="skipStep()">Passer cette étape →</button>
    </div>

    <?php /* STEP 5: PROFILE */ else: ?>
    <div class="cl-ob-content">
      <div class="cl-ob-icon">👤</div>
      <h2 class="cl-ob-title">Votre profil</h2>
      <p class="cl-ob-sub">Ajoutez votre photo de profil et personnalisez votre espace.</p>
      <form method="POST" enctype="multipart/form-data" class="cl-ob-form">
        <input type="hidden" name="action" value="onboard_profile">
        <div class="cl-avatar-upload" onclick="document.getElementById('av-inp').click()">
          <div class="cl-avatar-circle" id="av-circle">
            <span id="av-initials"><?= h($ownerData['initials']) ?></span>
            <img id="av-img" src="" alt="" style="display:none;">
          </div>
          <div class="cl-avatar-hint">Cliquez pour ajouter votre photo de profil</div>
          <input type="file" id="av-inp" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this)">
        </div>
        <div class="cl-form-group">
          <label class="cl-label">Nom affiché</label>
          <input class="cl-input" type="text" name="owner_name" value="<?= h($ownerData['biz_name']) ?>" placeholder="Votre nom ou nom du commerce">
        </div>
        <div class="cl-form-group">
          <label class="cl-label">Langue du dashboard</label>
          <select class="cl-select" name="language_pref">
            <option value="fr">Français</option>
            <option value="en">English</option>
          </select>
        </div>
        <div class="cl-toggle-row">
          <span class="cl-toggle-lbl">Thème sombre</span>
          <label class="cl-toggle"><input type="checkbox" name="dark_mode"><span class="cl-toggle-knob"></span></label>
        </div>
        <button type="submit" class="cl-btn-primary cl-btn-full" style="margin-top:1rem;">Terminer la configuration ✓</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php /* ═══════════════════════ DASHBOARD ═══════════════════════ */
else:
$col = $ownerData['theme_color'] ?? '#C9A84C';
// derive rgb for rgba usage
$cr = hexdec(substr($col,1,2)); $cg = hexdec(substr($col,3,2)); $cb = hexdec(substr($col,5,2));
?>
<div class="cl-app" id="cl-app">

  <!-- ── SIDEBAR ─────────────────────────────────────────── -->
  <aside class="cl-sidebar" id="cl-sidebar">
    <div class="cl-sb-brand">
      <div class="cl-sb-avatar" style="background:<?= h($col) ?>;"><?= h($ownerData['initials']) ?></div>
      <div class="cl-sb-info">
        <div class="cl-sb-name"><?= h($ownerData['biz_name']) ?></div>
        <div class="cl-sb-plan"><?= h(ucfirst($ownerData['plan'])) ?></div>
      </div>
    </div>

    <nav class="cl-nav">
      <div class="cl-nav-section" data-fr="Principal" data-en="Main">Principal</div>
      <a class="cl-nav-item active" href="#" onclick="goPage('dashboard',this)" data-fr="Dashboard" data-en="Dashboard">
        <svg class="cl-nav-ico" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        <span>Dashboard</span>
      </a>
      <a class="cl-nav-item" href="#" onclick="goPage('rdv',this)" data-fr="Mes RDV" data-en="My Bookings">
        <svg class="cl-nav-ico" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        <span data-fr="Mes RDV" data-en="My Bookings">Mes RDV</span>
        <?php $upcoming_count = count(array_filter($reservations,fn($r)=>$r['rdv_date']>=date('Y-m-d')&&$r['status']==='confirmed'));
        if ($upcoming_count): ?><span class="cl-nav-badge"><?= $upcoming_count ?></span><?php endif; ?>
      </a>
      <a class="cl-nav-item" href="#" onclick="goPage('upcoming',this)" data-fr="À venir" data-en="Upcoming">
        <svg class="cl-nav-ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        <span data-fr="À venir" data-en="Upcoming">À venir</span>
      </a>
      <a class="cl-nav-item" href="#" onclick="goPage('whatsapp',this)" data-fr="Messages WA" data-en="WA Messages">
        <svg class="cl-nav-ico" viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        <span data-fr="Messages WA" data-en="WA Messages">Messages WA</span>
      </a>
      <div class="cl-nav-sep"></div>
      <div class="cl-nav-section" data-fr="Gestion" data-en="Management">Gestion</div>
      <a class="cl-nav-item" href="#" onclick="goPage('hours',this)" data-fr="Disponibilités" data-en="Availability">
        <svg class="cl-nav-ico" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        <span data-fr="Disponibilités" data-en="Availability">Disponibilités</span>
      </a>
      <a class="cl-nav-item" href="#" onclick="goPage('services',this)" data-fr="Mes services" data-en="My services">
        <svg class="cl-nav-ico" viewBox="0 0 24 24"><path d="M12 2l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z"/></svg>
        <span data-fr="Mes services" data-en="My services">Mes services</span>
      </a>
      <a class="cl-nav-item" href="#" onclick="goPage('gallery',this)" data-fr="Galerie" data-en="Gallery">
        <svg class="cl-nav-ico" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
        <span data-fr="Galerie" data-en="Gallery">Galerie</span>
      </a>
      <div class="cl-nav-sep"></div>
      <div class="cl-nav-section" data-fr="Compte" data-en="Account">Compte</div>
      <a class="cl-nav-item" href="#" onclick="goPage('profile',this)" data-fr="Mon profil" data-en="My Profile">
        <svg class="cl-nav-ico" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
        <span data-fr="Mon profil" data-en="My Profile">Mon profil</span>
      </a>
      <form method="POST" style="margin-top:auto;">
        <input type="hidden" name="action" value="logout">
        <button type="submit" class="cl-nav-item cl-nav-logout">
          <svg class="cl-nav-ico" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
          <span data-fr="Déconnexion" data-en="Logout">Déconnexion</span>
        </button>
      </form>
    </nav>
  </aside>

  <!-- ── TOPBAR MOBILE ────────────────────────────────────── -->
  <header class="cl-topbar">
    <button class="cl-menu-btn" onclick="toggleSidebar()">
      <svg style="width:1.25rem;height:1.25rem;stroke:var(--cl-text);fill:none;stroke-width:2;stroke-linecap:round;" viewBox="0 0 24 24"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
    </button>
    <div class="cl-topbar-title" id="cl-page-title">Dashboard</div>
    <div class="cl-topbar-r">
      <button class="cl-lang-btn <?= $lang_pref==='fr'?'on':'' ?>" onclick="setLang('fr',this)">FR</button>
      <button class="cl-lang-btn <?= $lang_pref==='en'?'on':'' ?>" onclick="setLang('en',this)">EN</button>
      <a href="/LionRDV/Utilisateur%20du%20client/Utulisateur.php?slug=<?= urlencode($slug) ?>" target="_blank" class="cl-view-page-btn" title="Voir ma page publique">
        <svg style="width:1rem;height:1rem;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>
      </a>
    </div>
  </header>

  <!-- ── MAIN CONTENT ─────────────────────────────────────── -->
  <main class="cl-main" id="cl-main">
    <!-- Style brand CSS variable for this business -->
    <style>:root{--brand:<?= h($col) ?>;--brand-rgb:<?= $cr ?>,<?= $cg ?>,<?= $cb ?>;}</style>

    <?php if ($success): ?><div class="cl-alert cl-alert-success cl-alert-floating"><?= h($success) ?></div><?php endif; ?>

    <!-- ══════════ PAGE: DASHBOARD ══════════ -->
    <div class="cl-page" id="page-dashboard">
      <div class="cl-page-header">
        <h1 class="cl-page-title" data-fr="Tableau de bord" data-en="Dashboard">Tableau de bord</h1>
        <p class="cl-page-sub">Bonjour <?= h($ownerData['biz_name']) ?> · <?= date('d F Y') ?></p>
      </div>

      <!-- Metric cards -->
      <div class="cl-metrics">
        <div class="cl-metric">
          <div class="cl-metric-lbl" data-fr="RDV ce mois" data-en="Bookings this month">RDV ce mois</div>
          <div class="cl-metric-val"><?= (int)$stats['total_month'] ?></div>
          <div class="cl-metric-sub cl-metric-up">↑ <?= (int)$stats['confirmed'] ?> confirmés</div>
        </div>
        <div class="cl-metric">
          <div class="cl-metric-lbl" data-fr="Revenus (FCFA)" data-en="Revenue (FCFA)">Revenus (FCFA)</div>
          <div class="cl-metric-val"><?= number_format($revenue,0,',',' ') ?></div>
          <div class="cl-metric-sub">Ce mois</div>
        </div>
        <div class="cl-metric">
          <div class="cl-metric-lbl" data-fr="Taux annulation" data-en="Cancellation rate">Taux annulation</div>
          <div class="cl-metric-val"><?= $cancel_rate ?>%</div>
          <div class="cl-metric-sub <?= $cancel_rate < 10 ? 'cl-metric-up' : 'cl-metric-dn' ?>">
            <?= $cancel_rate < 10 ? '↓ Bon taux' : '↑ Élevé' ?>
          </div>
        </div>
        <div class="cl-metric">
          <div class="cl-metric-lbl" data-fr="Cette semaine" data-en="This week">Cette semaine</div>
          <div class="cl-metric-val"><?= (int)$stats['upcoming_week'] ?></div>
          <div class="cl-metric-sub">RDV à venir</div>
        </div>
      </div>

      <div class="cl-dash-grid">
        <!-- Week chart -->
        <div class="cl-card">
          <div class="cl-card-head">
            <div class="cl-card-title" data-fr="RDV par jour (7 jours)" data-en="Bookings by day">RDV par jour (7 jours)</div>
          </div>
          <div class="cl-mini-chart">
            <?php
            $days_chart = [2=>'Lun',3=>'Mar',4=>'Mer',5=>'Jeu',6=>'Ven',7=>'Sam',1=>'Dim'];
            $max_cnt = max(array_values($week_map) ?: [1]);
            foreach ($days_chart as $dow => $lbl):
              $cnt = $week_map[$dow] ?? 0;
              $h_pct = $max_cnt > 0 ? round($cnt / $max_cnt * 70) : 4;
            ?>
            <div class="cl-bar-wrap">
              <div class="cl-bar" style="height:<?= max(4,$h_pct) ?>px;" title="<?= $cnt ?> RDV"></div>
              <div class="cl-bar-lbl"><?= $lbl ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Upcoming -->
        <div class="cl-card">
          <div class="cl-card-head">
            <div class="cl-card-title" data-fr="Prochains RDV" data-en="Upcoming">Prochains RDV</div>
            <button class="cl-card-action" onclick="goPage('upcoming',null)">Voir tout</button>
          </div>
          <?php
          $ups = array_filter($reservations, fn($r) => $r['rdv_date'] >= date('Y-m-d') && $r['status']==='confirmed');
          $ups = array_slice($ups, 0, 4);
          foreach ($ups as $r): ?>
          <div class="cl-upcoming-item">
            <div class="cl-up-dot" style="background:var(--brand);"></div>
            <div class="cl-up-info">
              <div class="cl-up-nm"><?= h($r['prenom'].' '.$r['nom']) ?></div>
              <div class="cl-up-svc"><?= h($r['services_list'] ?? '') ?></div>
            </div>
            <div class="cl-up-time"><?= date('H:i', strtotime($r['rdv_time'])) ?></div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($ups)): ?>
          <div class="cl-empty-state" data-fr="Aucun RDV à venir" data-en="No upcoming bookings">Aucun RDV à venir</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Popular services -->
      <div class="cl-card">
        <div class="cl-card-head">
          <div class="cl-card-title" data-fr="Services actifs" data-en="Active services">Services actifs</div>
        </div>
        <?php foreach (array_filter($services, fn($s)=>$s['active']) as $svc): ?>
        <div class="cl-svc-stat-row">
          <div class="cl-svc-bar-accent" style="background:<?= h($svc['color']) ?>;"></div>
          <div class="cl-svc-stat-nm"><?= h($svc['name']) ?></div>
          <div class="cl-svc-stat-dur"><?= h($svc['duration']) ?></div>
          <div class="cl-svc-stat-pr"><?= number_format((int)$svc['price'],0,',',' ') ?> F</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══════════ PAGE: MES RDV ══════════ -->
    <div class="cl-page hidden" id="page-rdv">
      <div class="cl-page-header">
        <h1 class="cl-page-title" data-fr="Mes RDV" data-en="My Bookings">Mes RDV</h1>
        <p class="cl-page-sub" data-fr="Gérez, annulez, contactez vos clients" data-en="Manage, cancel, contact clients">Gérez, annulez, contactez vos clients</p>
      </div>
      <div class="cl-card">
        <div class="cl-rdv-filters">
          <button class="cl-filter-btn active" onclick="filterRdv('all',this)" data-fr="Tous" data-en="All">Tous</button>
          <button class="cl-filter-btn" onclick="filterRdv('confirmed',this)" data-fr="Confirmés" data-en="Confirmed">Confirmés</button>
          <button class="cl-filter-btn" onclick="filterRdv('cancelled',this)" data-fr="Annulés" data-en="Cancelled">Annulés</button>
          <button class="cl-filter-btn" onclick="filterRdv('completed',this)" data-fr="Terminés" data-en="Completed">Terminés</button>
        </div>
        <?php if (empty($reservations)): ?>
        <div class="cl-empty-state">Aucun rendez-vous pour le moment</div>
        <?php endif; ?>
        <?php foreach ($reservations as $r):
          $dateObj = new DateTime($r['rdv_date']);
          $statusMap = ['confirmed'=>'Confirmé','cancelled'=>'Annulé','completed'=>'Terminé','no_show'=>'Absent'];
          $statusCls  = ['confirmed'=>'ok','cancelled'=>'cancel','completed'=>'done','no_show'=>'cancel'];
        ?>
        <div class="cl-rdv-item" data-status="<?= $r['status'] ?>">
          <div class="cl-rdv-date">
            <div class="cl-rdv-day"><?= $dateObj->format('d') ?></div>
            <div class="cl-rdv-mon"><?= mb_strtoupper(mb_substr(strftime('%b',$dateObj->getTimestamp()),0,3)) ?></div>
          </div>
          <div class="cl-rdv-info">
            <div class="cl-rdv-name"><?= h($r['prenom'].' '.$r['nom']) ?></div>
            <div class="cl-rdv-svc"><?= h($r['services_list'] ?? 'Service') ?></div>
          </div>
          <div class="cl-rdv-time"><?= date('H:i', strtotime($r['rdv_time'])) ?></div>
          <span class="cl-rdv-badge <?= $statusCls[$r['status']] ?? 'ok' ?>"><?= $statusMap[$r['status']] ?? $r['status'] ?></span>
          <div class="cl-rdv-actions">
            <a class="cl-rdv-btn cl-rdv-wa" href="https://wa.me/237<?= $r['whatsapp'] ?>?text=<?= urlencode('Bonjour '.$r['prenom'].', concernant votre RDV du '.date('d/m', strtotime($r['rdv_date'])).' à '.date('H:i',strtotime($r['rdv_time']))) ?>" target="_blank">WA</a>
            <?php if ($r['status'] === 'confirmed'): ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="cancel_reservation">
              <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
              <button type="submit" class="cl-rdv-btn cl-rdv-cancel" onclick="return confirm('Annuler ce RDV ?')">Annuler</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══════════ PAGE: À VENIR ══════════ -->
    <div class="cl-page hidden" id="page-upcoming">
      <div class="cl-page-header">
        <h1 class="cl-page-title" data-fr="Prochains rendez-vous" data-en="Upcoming appointments">Prochains rendez-vous</h1>
        <p class="cl-page-sub">Les 14 prochains jours</p>
      </div>
      <div class="cl-card">
        <?php
        $upcoming = array_filter($reservations, fn($r) => $r['rdv_date'] >= date('Y-m-d') && $r['status']==='confirmed');
        usort($upcoming, fn($a,$b) => strcmp($a['rdv_date'].$a['rdv_time'], $b['rdv_date'].$b['rdv_time']));
        $cur_date = '';
        foreach ($upcoming as $r):
          $d = date('l d F Y', strtotime($r['rdv_date']));
          if ($d !== $cur_date): $cur_date = $d; ?>
          <div class="cl-upcoming-date-sep"><?= $d ?></div>
          <?php endif; ?>
          <div class="cl-upcoming-item">
            <div class="cl-up-dot" style="background:var(--brand);"></div>
            <div class="cl-up-info">
              <div class="cl-up-nm"><?= h($r['prenom'].' '.$r['nom']) ?></div>
              <div class="cl-up-svc"><?= h($r['services_list'] ?? '') ?></div>
            </div>
            <div class="cl-up-time"><?= date('H:i', strtotime($r['rdv_time'])) ?></div>
            <a class="cl-rdv-btn cl-rdv-wa" href="https://wa.me/237<?= $r['whatsapp'] ?>?text=<?= urlencode('Bonjour '.$r['prenom'].', rappel pour votre RDV de demain à '.date('H:i',strtotime($r['rdv_time']))) ?>" target="_blank">WA</a>
          </div>
        <?php endforeach; ?>
        <?php if (empty($upcoming)): ?><div class="cl-empty-state">Aucun RDV à venir</div><?php endif; ?>
      </div>
    </div>

    <!-- ══════════ PAGE: WHATSAPP ══════════ -->
    <div class="cl-page hidden" id="page-whatsapp">
      <div class="cl-page-header">
        <h1 class="cl-page-title" data-fr="Messages WhatsApp" data-en="WhatsApp Messages">Messages WhatsApp</h1>
        <p class="cl-page-sub" data-fr="Envoyez un message pré-rédigé à vos clients" data-en="Send pre-written messages to clients">Envoyez un message pré-rédigé à vos clients</p>
      </div>
      <div class="cl-card">
        <div class="cl-form-group">
          <label class="cl-label" data-fr="Choisir un client" data-en="Select a client">Choisir un client</label>
          <select class="cl-select" id="wa-client-select" onchange="updateWaTemplates()">
            <option value="">— Sélectionnez un client —</option>
            <?php foreach ($reservations as $r): if ($r['status']==='confirmed'): ?>
            <option value="<?= h($r['whatsapp']) ?>" data-name="<?= h($r['prenom']) ?>" data-date="<?= h(date('d/m',strtotime($r['rdv_date']))) ?>" data-time="<?= h(date('H:i',strtotime($r['rdv_time']))) ?>" data-svc="<?= h($r['services_list']??'') ?>">
              <?= h($r['prenom'].' '.$r['nom']) ?> · <?= h(date('d/m',strtotime($r['rdv_date']))) ?> <?= h(date('H:i',strtotime($r['rdv_time']))) ?>
            </option>
            <?php endif; endforeach; ?>
          </select>
        </div>
      </div>
      <div id="wa-templates">
        <?php
        $biz_nm = h($ownerData['biz_name']);
        $templates = [
          ['🔔','Rappel de RDV','Rappel','Bonjour {name} ! Rappel pour votre RDV chez '.$biz_nm.' le {date} à {time} pour {svc}. À bientôt !'],
          ['✅','Confirmation','Confirmation','Bonjour {name} ! Votre RDV est confirmé chez '.$biz_nm.' le {date} à {time} pour {svc}. Besoin de modifier ? Répondez à ce message.'],
          ['❌','Annulation','Annulation','Bonjour {name}, nous devons malheureusement annuler votre RDV du {date}. Nous vous contacterons pour le reprogrammer. Désolé(e) pour la gêne.'],
          ['⭐','Demande d\'avis','Avis','Bonjour {name} ! Merci pour votre visite chez '.$biz_nm.' ! Votre avis nous aide à nous améliorer. Avez-vous été satisfait(e) ?'],
          ['🎁','Offre spéciale','Offre','Bonjour {name} ! '.$biz_nm.' vous fait une offre exclusive. Réservez avant fin du mois et bénéficiez d\'une réduction.'],
        ];
        foreach ($templates as [$ico, $nm, $short, $msg]): ?>
        <div class="cl-wa-card">
          <div class="cl-wa-head">
            <span class="cl-wa-ico"><?= $ico ?></span>
            <div class="cl-wa-nm"><?= $nm ?></div>
          </div>
          <div class="cl-wa-msg" data-template="<?= h($msg) ?>"><?= h($msg) ?></div>
          <a class="cl-wa-btn" href="#" id="wa-send-<?= $short ?>" onclick="sendWa('<?= h($msg) ?>',this)">
            <svg style="width:1rem;height:1rem;fill:#fff;flex-shrink:0;" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51a9.9 9.9 0 00-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.122.554 4.118 1.524 5.847L.057 23.882l6.197-1.624A11.937 11.937 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.894a9.877 9.877 0 01-5.031-1.378l-.361-.214-3.741.981.998-3.648-.235-.374A9.857 9.857 0 012.103 12C2.103 6.57 6.57 2.103 12 2.103 17.43 2.103 21.897 6.57 21.897 12c0 5.43-4.467 9.894-9.897 9.894z"/></svg>
            Envoyer via WhatsApp
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══════════ PAGE: DISPONIBILITÉS ══════════ -->
    <div class="cl-page hidden" id="page-hours">
      <div class="cl-page-header">
        <h1 class="cl-page-title" data-fr="Disponibilités" data-en="Availability">Disponibilités</h1>
        <p class="cl-page-sub" data-fr="Modifiez vos horaires d'ouverture" data-en="Edit your opening hours">Modifiez vos horaires d'ouverture</p>
      </div>
      <form method="POST" class="cl-card">
        <input type="hidden" name="action" value="save_availability">
        <?php
        $avail_map = [];
        foreach ($avail as $a) $avail_map[strtolower($a['day_en'])] = $a;
        $days_list = [['monday','Lundi'],['tuesday','Mardi'],['wednesday','Mercredi'],
                      ['thursday','Jeudi'],['friday','Vendredi'],['saturday','Samedi'],['sunday','Dimanche']];
        foreach ($days_list as [$ek,$fr]):
          $a    = $avail_map[$ek] ?? ['is_open'=>0,'open_time'=>'08:00','close_time'=>'18:00'];
          $open = (int)$a['is_open'];
        ?>
        <div class="cl-day-row" id="row-<?= $ek ?>">
          <label class="cl-day-tog">
            <input type="checkbox" name="day_open_<?= $ek ?>" <?= $open?'checked':'' ?> onchange="togDay('<?= $ek ?>',this.checked)">
            <span class="cl-day-knob"></span>
          </label>
          <span class="cl-day-nm <?= !$open?'cl-day-off':'' ?>" id="dnm-<?= $ek ?>"><?= $fr ?></span>
          <div class="cl-day-times" id="dtimes-<?= $ek ?>" <?= !$open?'style="display:none"':'' ?>>
            <input class="cl-time-inp" type="time" name="open_<?= $ek ?>" value="<?= h(substr($a['open_time']??'08:00',0,5)) ?>">
            <span class="cl-day-sep">–</span>
            <input class="cl-time-inp" type="time" name="close_<?= $ek ?>" value="<?= h(substr($a['close_time']??'18:00',0,5)) ?>">
          </div>
          <?php if (!$open): ?><span class="cl-day-closed" id="dclosed-<?= $ek ?>">Fermé</span><?php endif; ?>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="cl-btn-primary" style="margin-top:1rem;">Enregistrer les horaires</button>
      </form>
    </div>

    <!-- ══════════ PAGE: MES SERVICES ══════════ -->
    <div class="cl-page hidden" id="page-services">
      <div class="cl-page-header">
        <h1 class="cl-page-title" data-fr="Mes services" data-en="My services">Mes services</h1>
        <p class="cl-page-sub" data-fr="Modifiez nom, durée, prix, description et couleur" data-en="Edit name, duration, price, description and color">Modifiez nom, durée, prix, description et couleur</p>
      </div>
      <form method="POST" class="cl-card">
        <input type="hidden" name="action" value="save_services">
        <?php foreach ($services as $svc): ?>
        <div class="cl-svc-card <?= !$svc['active']?'cl-svc-inactive':'' ?>" id="svc-<?= $svc['id'] ?>">
          <input type="hidden" name="svc_id[]" value="<?= $svc['id'] ?>">
          <!-- Row 1: color + name + toggle -->
          <div class="cl-svc-color-row">
            <div class="cl-svc-bar-wrap">
              <div class="cl-svc-bar-prev" id="bar-<?= $svc['id'] ?>" style="background:<?= h($svc['color']) ?>;"></div>
              <input type="color" class="cl-clr-sw" name="svc_color_<?= $svc['id'] ?>" value="<?= h($svc['color']) ?>" oninput="document.getElementById('bar-<?= $svc['id'] ?>').style.background=this.value">
            </div>
            <input class="cl-input cl-svc-nm-inp" type="text" name="svc_name_<?= $svc['id'] ?>" value="<?= h($svc['name']) ?>" placeholder="Nom du service">
            <label class="cl-svc-tog">
              <input type="checkbox" name="svc_active_<?= $svc['id'] ?>" <?= $svc['active']?'checked':'' ?> onchange="toggleSvcCard(<?= $svc['id'] ?>,this.checked)">
              <span class="cl-svc-knob"></span>
            </label>
          </div>
          <!-- Row 2: duration + price -->
          <div class="cl-svc-row2">
            <div class="cl-form-group">
              <label class="cl-label" data-fr="Durée" data-en="Duration">Durée</label>
              <input class="cl-input" type="text" name="svc_dur_<?= $svc['id'] ?>" value="<?= h($svc['duration']) ?>" placeholder="45 min">
            </div>
            <div class="cl-form-group">
              <label class="cl-label" data-fr="Prix (FCFA)" data-en="Price (FCFA)">Prix (FCFA)</label>
              <input class="cl-input" type="number" name="svc_price_<?= $svc['id'] ?>" value="<?= h($svc['price']) ?>">
            </div>
          </div>
          <!-- Row 3: description -->
          <div class="cl-form-group">
            <label class="cl-label" data-fr="Description courte" data-en="Short description">Description courte</label>
            <input class="cl-input" type="text" name="svc_desc_<?= $svc['id'] ?>" value="<?= h($svc['description'] ?? '') ?>" placeholder="Description visible par les clients">
          </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="cl-btn-primary" style="margin-top:1rem;">Enregistrer tout</button>
      </form>
    </div>

    <!-- ══════════ PAGE: GALERIE ══════════ -->
    <div class="cl-page hidden" id="page-gallery">
      <div class="cl-page-header">
        <h1 class="cl-page-title" data-fr="Galerie photos" data-en="Photo Gallery">Galerie photos</h1>
        <?php $gal_max = (int)($ownerData['gal_max_photos'] ?? 9); $gal_count = count($gallery); ?>
        <p class="cl-page-sub"><?= $gal_count ?> / <?= $gal_max ?> photos</p>
      </div>
      <!-- Upload zone -->
      <?php if ($gal_count < $gal_max): ?>
      <form method="POST" enctype="multipart/form-data" class="cl-card">
        <input type="hidden" name="action" value="upload_gallery">
        <div class="cl-gallery-upload-zone" onclick="document.getElementById('gal-main-inp').click()">
          <svg style="width:2rem;height:2rem;stroke:var(--brand);fill:none;stroke-width:1.5;stroke-linecap:round;" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
          <div class="cl-guz-title" data-fr="Cliquez pour ajouter des photos" data-en="Click to add photos">Cliquez pour ajouter des photos</div>
          <div class="cl-guz-sub">JPG, PNG, WEBP · <?= $gal_max - $gal_count ?> emplacements restants</div>
          <input type="file" id="gal-main-inp" name="gallery_photos[]" accept="image/*" multiple style="display:none" onchange="this.form.submit()">
        </div>
        <!-- progress bar -->
        <div style="margin-top:.75rem;height:6px;background:var(--cl-border);border-radius:3px;overflow:hidden;">
          <div style="width:<?= round($gal_count/$gal_max*100) ?>%;height:100%;background:var(--brand);border-radius:3px;transition:width .3s;"></div>
        </div>
      </form>
      <?php endif; ?>
      <!-- Gallery grid -->
      <div class="cl-card">
        <div class="cl-gal-grid">
          <?php foreach ($gallery as $img): ?>
          <div class="cl-gal-item">
            <img src="/LionRDV/<?= h($img['path']) ?>" alt="Photo galerie" loading="lazy">
            <div class="cl-gal-overlay">
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="delete_gallery">
                <input type="hidden" name="gallery_id" value="<?= $img['id'] ?>">
                <button type="submit" class="cl-gal-del-btn" onclick="return confirm('Supprimer cette photo ?')">Supprimer</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
          <?php for ($i = $gal_count; $i < $gal_max; $i++): ?>
          <div class="cl-gal-empty">
            <svg style="width:1.5rem;height:1.5rem;stroke:var(--cl-border-strong);fill:none;stroke-width:1.5;stroke-linecap:round;" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
          <?php endfor; ?>
        </div>
      </div>
    </div>

    <!-- ══════════ PAGE: PROFIL ══════════ -->
    <div class="cl-page hidden" id="page-profile">
      <div class="cl-page-header">
        <h1 class="cl-page-title" data-fr="Mon profil" data-en="My Profile">Mon profil</h1>
        <p class="cl-page-sub" data-fr="Photo, nom, préférences, thème" data-en="Photo, name, preferences, theme">Photo, nom, préférences, thème</p>
      </div>
      <form method="POST" enctype="multipart/form-data" class="cl-card">
        <input type="hidden" name="action" value="save_profile">
        <!-- Avatar -->
        <div class="cl-avatar-upload" onclick="document.getElementById('av-main-inp').click()">
          <div class="cl-avatar-circle" style="background:<?= h($col) ?>;">
            <?php if (!empty($ownerData['avatar_photo'])): ?>
            <img src="/LionRDV/<?= h($ownerData['avatar_photo']) ?>" alt="Avatar" id="av-main-img">
            <?php else: ?>
            <span id="av-main-initials"><?= h($ownerData['initials']) ?></span>
            <img id="av-main-img" src="" alt="" style="display:none;">
            <?php endif; ?>
          </div>
          <div class="cl-avatar-hint" data-fr="Cliquez pour changer votre photo" data-en="Click to change your photo">Cliquez pour changer votre photo</div>
          <input type="file" id="av-main-inp" name="avatar" accept="image/*" style="display:none" onchange="previewAvatar(this,'av-main-img','av-main-initials')">
        </div>
        <div class="cl-form-group">
          <label class="cl-label" data-fr="Nom affiché" data-en="Display name">Nom affiché</label>
          <input class="cl-input" type="text" name="owner_name" value="<?= h($ownerData['name'] ?? $ownerData['biz_name']) ?>">
        </div>
        <div class="cl-form-group">
          <label class="cl-label">WhatsApp (identifiant — non modifiable)</label>
          <input class="cl-input" type="text" value="+237 <?= h($ownerData['whatsapp']) ?>" readonly style="opacity:.6;cursor:not-allowed;">
        </div>
        <div class="cl-form-group">
          <label class="cl-label" data-fr="Nouveau mot de passe" data-en="New password">Nouveau mot de passe</label>
          <input class="cl-input" type="password" name="new_password" placeholder="Laisser vide pour ne pas changer">
        </div>
        <!-- Theme + Lang -->
        <div class="cl-profile-prefs">
          <div class="cl-pref-row">
            <span class="cl-pref-lbl" data-fr="Thème sombre" data-en="Dark theme">Thème sombre</span>
            <label class="cl-toggle">
              <input type="checkbox" name="dark_mode" id="dark-mode-tog" <?= $dark_mode?'checked':'' ?> onchange="applyTheme(this.checked)">
              <span class="cl-toggle-knob"></span>
            </label>
          </div>
          <div class="cl-pref-row">
            <span class="cl-pref-lbl" data-fr="Langue" data-en="Language">Langue</span>
            <select class="cl-select cl-select-sm" name="language_pref" onchange="setLang(this.value,null)">
              <option value="fr" <?= $lang_pref==='fr'?'selected':'' ?>>Français</option>
              <option value="en" <?= $lang_pref==='en'?'selected':'' ?>>English</option>
            </select>
          </div>
        </div>
        <!-- Public page link -->
        <div class="cl-public-link">
          <div class="cl-public-url">lionrdv.cm/<?= h($slug) ?></div>
          <a href="/LionRDV/Utilisateur%20du%20client/Utulisateur.php?slug=<?= urlencode($slug) ?>" target="_blank" class="cl-btn-secondary">Voir</a>
          <button type="button" class="cl-btn-secondary" onclick="navigator.clipboard.writeText('lionrdv.cm/<?= h($slug) ?>')">Copier</button>
        </div>
        <button type="submit" class="cl-btn-primary" style="margin-top:1rem;">Enregistrer les modifications</button>
      </form>
    </div>

  </main><!-- /cl-main -->
</div><!-- /cl-app -->
<?php endif; ?>

<script src="clientLion.js"></script>
<script>
/* ── Page navigation ───────────────────────────────────── */
function goPage(id, navEl) {
  document.querySelectorAll('.cl-page').forEach(p => p.classList.add('hidden'));
  document.querySelectorAll('.cl-nav-item').forEach(n => n.classList.remove('active'));
  const page = document.getElementById('page-' + id);
  if (page) page.classList.remove('hidden');
  if (navEl) navEl.classList.add('active');
  const titles = {'dashboard':'Tableau de bord','rdv':'Mes RDV','upcoming':'À venir',
    'whatsapp':'Messages WA','hours':'Disponibilités','services':'Mes services',
    'gallery':'Galerie','profile':'Mon profil'};
  const tb = document.getElementById('cl-page-title');
  if (tb) tb.textContent = titles[id] || id;
  window.scrollTo(0,0);
  return false;
}
/* ── Sidebar mobile toggle ──────────────────────────────── */
function toggleSidebar() {
  document.getElementById('cl-sidebar').classList.toggle('open');
}
/* ── Day toggle ─────────────────────────────────────────── */
function togDay(en, open) {
  const nm = document.getElementById('dnm-'+en);
  const times = document.getElementById('dtimes-'+en);
  let closed = document.getElementById('dclosed-'+en);
  if (nm) nm.classList.toggle('cl-day-off', !open);
  if (times) times.style.display = open ? '' : 'none';
  if (open && closed) closed.remove();
  if (!open && !closed) {
    const s = document.createElement('span');
    s.className = 'cl-day-closed'; s.id = 'dclosed-'+en; s.textContent = 'Fermé';
    document.getElementById('row-'+en)?.appendChild(s);
  }
}
/* ── Password strength ──────────────────────────────────── */
function checkPwdStrength(v) {
  const fill = document.getElementById('pwd-fill');
  const hint = document.getElementById('pwd-hint');
  if (!fill) return;
  const s = v.length < 6 ? 20 : v.length < 8 ? 45 : v.length < 12 ? 70 : 100;
  const [color, label] = s < 45 ? ['#E74C3C','Trop court'] : s < 70 ? ['#E67E22','Moyen'] : ['#059669','Fort'];
  fill.style.width = s + '%'; fill.style.background = color;
  if (hint) { hint.textContent = label; hint.style.color = color; }
}
/* ── WA templates ───────────────────────────────────────── */
function sendWa(template, btn) {
  const sel = document.getElementById('wa-client-select');
  if (!sel || !sel.value) { alert('Veuillez sélectionner un client d\'abord.'); return false; }
  const opt = sel.options[sel.selectedIndex];
  const msg = template
    .replace(/{name}/g, opt.dataset.name || '')
    .replace(/{date}/g, opt.dataset.date || '')
    .replace(/{time}/g, opt.dataset.time || '')
    .replace(/{svc}/g,  opt.dataset.svc  || '');
  btn.href = 'https://wa.me/237' + sel.value + '?text=' + encodeURIComponent(msg);
  return true;
}
/* ── Theme toggle ───────────────────────────────────────── */
function applyTheme(dark) {
  document.documentElement.setAttribute('data-theme', dark ? 'dark' : 'light');
}
/* ── Language ───────────────────────────────────────────── */
function setLang(lang, btn) {
  document.querySelectorAll('.cl-lang-btn').forEach(b => b.classList.remove('on'));
  if (btn) btn.classList.add('on');
  document.querySelectorAll('[data-' + lang + ']').forEach(el => {
    el.textContent = el.getAttribute('data-' + lang);
  });
}
/* ── Service card toggle ────────────────────────────────── */
function toggleSvcCard(id, active) {
  const card = document.getElementById('svc-' + id);
  if (card) card.classList.toggle('cl-svc-inactive', !active);
}
/* ── Avatar preview ─────────────────────────────────────── */
function previewAvatar(input, imgId, initId) {
  const file = input.files[0]; if (!file) return;
  const r = new FileReader();
  r.onload = e => {
    const img = document.getElementById(imgId || 'av-img');
    const ini = document.getElementById(initId || 'av-initials');
    if (img) { img.src = e.target.result; img.style.display = 'block'; }
    if (ini) ini.style.display = 'none';
  };
  r.readAsDataURL(file);
}
/* ── Gallery preview (onboarding) ───────────────────────── */
function previewGallery(input) {
  const grid = document.getElementById('gal-preview-grid'); if (!grid) return;
  grid.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const r = new FileReader();
    r.onload = e => {
      const d = document.createElement('div');
      d.className = 'cl-gal-preview-item';
      d.innerHTML = '<img src="'+e.target.result+'" alt="">';
      grid.appendChild(d);
    };
    r.readAsDataURL(file);
  });
}
/* ── Skip onboarding step ───────────────────────────────── */
function skipStep() {
  const f = document.createElement('form');
  f.method = 'POST';
  f.innerHTML = '<input name="action" value="onboard_gallery"><input name="gallery_photos[]" value="">';
  document.body.appendChild(f); f.submit();
}
/* ── Auto-hide alerts ───────────────────────────────────── */
document.querySelectorAll('.cl-alert-floating').forEach(el => {
  setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 3000);
});
</script>
</body>
</html>