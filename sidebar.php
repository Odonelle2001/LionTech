<?php
require_once __DIR__ . '/config.php';
$currentPage   = $currentPage   ?? '';
$businessCount = $businessCount ?? 6;
$qrCount       = $qrCount       ?? 3;
$alertCount    = $alertCount    ?? 2;
?>

<link rel="stylesheet" href="<?= BASE_URL ?>/sidebar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/responsive.css">

<button class="menu-toggle" id="menuToggle" aria-label="Ouvrir le menu">
  <i class="fa-solid fa-bars"></i>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Fermer le menu">&times;</button>
  <div class="brand">
    <img src="<?= BASE_URL ?>/liontech-logo.jpg" alt="LIONTECH Logo" class="logo-img">
    <div class="brand-text">
      <h1>Lion<span>RDV</span></h1>
      <p>by LionTech</p>
    </div>
  </div>

  <div class="sidebar-section">
    <p class="section-label">PRINCIPAL</p>

    <a href="<?= BASE_URL ?>/RSVAdmin.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
      <i class="fa-solid fa-table-cells-large"></i>
      <span>Dashboard</span>
    </a>

    <a href="<?= BASE_URL ?>/businesses.php" class="nav-link <?= $currentPage === 'businesses' ? 'active' : '' ?>">
      <i class="fa-regular fa-id-card"></i>
      <span>Businesses</span>
      <span class="pill gold"><?= htmlspecialchars((string)$businessCount) ?></span>
    </a>

    <a href="<?= BASE_URL ?>/AjouterBussiness/AjouterBussiness.php">
      <button class="add-btn">
        <i class="fa-solid fa-plus"></i>
        <span>Ajouter un business</span>
      </button>
    </a>

    <a href="<?= BASE_URL ?>/revenus.php" class="nav-link <?= $currentPage === 'revenus' ? 'active' : '' ?>">
      <i class="fa-solid fa-chart-line"></i>
      <span>Revenus</span>
    </a>
  </div>

  <div class="sidebar-section">
    <p class="section-label">GESTION</p>

    <a href="<?= BASE_URL ?>/rdv.php" class="nav-link <?= $currentPage === 'rdv' ? 'active' : '' ?>">
      <i class="fa-regular fa-calendar-check"></i>
      <span>Tous les RDV</span>
    </a>

    <a href="<?= BASE_URL ?>/abonnements.php" class="nav-link <?= $currentPage === 'abonnements' ? 'active' : '' ?>">
      <i class="fa-regular fa-clock"></i>
      <span>Abonnements</span>
    </a>

    <a href="<?= BASE_URL ?>/qrcodes.php" class="nav-link <?= $currentPage === 'qrcodes' ? 'active' : '' ?>">
      <i class="fa-solid fa-qrcode"></i>
      <span>QR Codes</span>
      <span class="pill blue"><?= htmlspecialchars((string)$qrCount) ?></span>
    </a>
  </div>

  <div class="sidebar-section">
    <p class="section-label">SYSTÈME</p>

    <a href="<?= BASE_URL ?>/alertes.php" class="nav-link <?= $currentPage === 'alertes' ? 'active' : '' ?>">
      <i class="fa-regular fa-bell"></i>
      <span>Alertes</span>
      <span class="pill red"><?= htmlspecialchars((string)$alertCount) ?></span>
    </a>

    <a href="<?= BASE_URL ?>/parametres.php" class="nav-link <?= $currentPage === 'parametres' ? 'active' : '' ?>">
      <i class="fa-solid fa-gear"></i>
      <span>Paramètres</span>
    </a>
  </div>

  <div class="sidebar-footer">
    <div class="footer-badge">LT</div>
    <div>
      <h4>LionTech Admin</h4>
      <p>Super Administrateur</p>
    </div>
  </div>
</aside>

<script>
(function(){
  var btn  = document.getElementById('menuToggle');
  var side = document.getElementById('sidebar');
  var ov   = document.getElementById('sidebarOverlay');
  var closeBtn = document.getElementById('sidebarClose');
  if (!btn || !side || !ov) return;
  function close(){
    side.classList.remove('is-open');
    ov.classList.remove('is-open');
    document.body.classList.remove('sidebar-open');
  }
  function open(){
    side.classList.add('is-open');
    ov.classList.add('is-open');
    document.body.classList.add('sidebar-open');
  }
  btn.addEventListener('click', function(){
    side.classList.contains('is-open') ? close() : open();
  });
  ov.addEventListener('click', close);
  if (closeBtn) closeBtn.addEventListener('click', close);
  window.addEventListener('resize', function(){ if(window.innerWidth > 980) close(); });
})();
</script>
