<?php
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = [
        'whatsapp_link', 'telegram_link', 'email', 'phone',
        'facebook', 'instagram', 'linkedin', 'github', 'address'
    ];
    foreach ($keys as $key) {
        $value = trim($_POST[$key] ?? '');
        $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) 
                      ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value, updated_at=CURRENT_TIMESTAMP")
           ->execute([$key, $value]);
    }
    $success = 'Paramètres enregistrés avec succès.';
}

$settings = [];
$rows = $db->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
foreach ($rows as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

function sv(array $settings, string $key): string {
    return htmlspecialchars($settings[$key] ?? '');
}

$currentAdmin = getCurrentAdmin();
$initials = strtoupper(substr($currentAdmin['username'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LIONTECH Admin — Paramètres</title>
  <link rel="icon" href="../liontech-logo.jpg" type="image/jpeg">
  <link rel="stylesheet" href="admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="admin-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-content">
    <div class="topbar">
      <div class="topbar-left">
        <button class="theme-toggle-btn" onclick="document.getElementById('sidebar').classList.toggle('mobile-open')" style="border-radius:6px;"><i class="fas fa-bars"></i></button>
        <h1>Paramètres du site</h1>
      </div>
      <div class="topbar-right">
        <div class="admin-avatar"><?= $initials ?></div>
        <button class="theme-toggle-btn" id="themeBtn"><i class="fas fa-moon" id="themeIcon"></i></button>
      </div>
    </div>

    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form method="POST">
        <!-- CONTACT INFO -->
        <div class="admin-card" style="margin-bottom:20px;">
          <div class="admin-card-header">
            <h2><i class="fas fa-address-card" style="color:var(--accent);margin-right:8px;"></i>Informations de contact</h2>
          </div>
          <div class="admin-card-body">
            <div class="form-grid">
              <div class="form-group">
                <label><i class="fas fa-envelope" style="color:var(--accent);"></i> Email de contact</label>
                <input type="email" name="email" value="<?= sv($settings, 'email') ?>" placeholder="contact@liontech.cm">
              </div>
              <div class="form-group">
                <label><i class="fas fa-phone" style="color:var(--accent);"></i> Téléphone</label>
                <input type="text" name="phone" value="<?= sv($settings, 'phone') ?>" placeholder="+237 XXX XXX XXX">
              </div>
              <div class="form-group">
                <label><i class="fab fa-whatsapp" style="color:#25D366;"></i> Lien WhatsApp</label>
                <input type="url" name="whatsapp_link" value="<?= sv($settings, 'whatsapp_link') ?>" placeholder="https://wa.me/237...">
              </div>
              <div class="form-group">
                <label><i class="fab fa-telegram" style="color:#229ED9;"></i> Lien Telegram</label>
                <input type="url" name="telegram_link" value="<?= sv($settings, 'telegram_link') ?>" placeholder="https://t.me/...">
              </div>
              <div class="form-group full">
                <label><i class="fas fa-map-marker-alt" style="color:var(--accent);"></i> Adresse</label>
                <input type="text" name="address" value="<?= sv($settings, 'address') ?>" placeholder="Yaoundé, Cameroun">
              </div>
            </div>
          </div>
        </div>

        <!-- SOCIAL MEDIA -->
        <div class="admin-card" style="margin-bottom:20px;">
          <div class="admin-card-header">
            <h2><i class="fas fa-share-alt" style="color:var(--accent);margin-right:8px;"></i>Réseaux sociaux de LIONTECH</h2>
          </div>
          <div class="admin-card-body">
            <div class="form-grid">
              <div class="form-group">
                <label><i class="fab fa-facebook" style="color:#1877f2;"></i> Facebook</label>
                <input type="url" name="facebook" value="<?= sv($settings, 'facebook') ?>" placeholder="https://facebook.com/...">
              </div>
              <div class="form-group">
                <label><i class="fab fa-instagram" style="color:#e1306c;"></i> Instagram</label>
                <input type="url" name="instagram" value="<?= sv($settings, 'instagram') ?>" placeholder="https://instagram.com/...">
              </div>
              <div class="form-group">
                <label><i class="fab fa-linkedin" style="color:#0077b5;"></i> LinkedIn</label>
                <input type="url" name="linkedin" value="<?= sv($settings, 'linkedin') ?>" placeholder="https://linkedin.com/company/...">
              </div>
              <div class="form-group">
                <label><i class="fab fa-github" style="color:var(--text);"></i> GitHub</label>
                <input type="url" name="github" value="<?= sv($settings, 'github') ?>" placeholder="https://github.com/...">
              </div>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-primary" style="padding:13px 32px;font-size:14px;">
          <i class="fas fa-save"></i> Enregistrer tous les paramètres
        </button>
      </form>
    </div>
  </div>
</div>
<script>
const saved = localStorage.getItem('lt-admin-theme') || 'dark';
document.documentElement.setAttribute('data-theme', saved);
document.getElementById('themeIcon').className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
document.getElementById('themeBtn').addEventListener('click', () => {
  const current = document.documentElement.getAttribute('data-theme');
  const next = current === 'dark' ? 'light' : 'dark';
  document.documentElement.setAttribute('data-theme', next);
  localStorage.setItem('lt-admin-theme', next);
  document.getElementById('themeIcon').className = next === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
});
</script>
</body>
</html>
