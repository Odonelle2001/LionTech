<?php
$currentPage = $currentPage ?? '';
$businessCount = $businessCount ?? 6;
$qrCount = $qrCount ?? 3;
$alertCount = $alertCount ?? 2;
?>

<link rel="stylesheet" href="sidebar.css">

<aside class="sidebar">
  <div class="brand">
    <img src="liontech-logo.jpg" alt="LIONTECH Logo" class="logo-img">
    <div class="brand-text">
      <h1>Lion<span>RDV</span></h1>
      <p>by LionTech</p>
    </div>
  </div>

  <div class="sidebar-section">
    <p class="section-label">PRINCIPAL</p>

      <a href="RSVAdmin.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
    <i class="fa-solid fa-table-cells-large"></i>
    <span>Dashboard</span>
</a>


    <a href="businesses.php" class="nav-link <?= $currentPage === 'businesses' ? 'active' : '' ?>">
      <i class="fa-regular fa-id-card"></i>
      <span>Businesses</span>
      <span class="pill gold"><?= htmlspecialchars((string)$businessCount) ?></span>
    </a>

    <a href="/LionRDV/AjouterBussiness/AjouterBussiness.php">
  <button class="add-btn">
    <i class="fa-solid fa-plus"></i>
    <span>Ajouter un business</span>
  </button>
</a>

    <a href="revenus.php" class="nav-link <?= $currentPage === 'revenus' ? 'active' : '' ?>">
      <i class="fa-solid fa-chart-line"></i>
      <span>Revenus</span>
    </a>
  </div>

  <div class="sidebar-section">
    <p class="section-label">GESTION</p>

    <a href="rdv.php" class="nav-link <?= $currentPage === 'rdv' ? 'active' : '' ?>">
      <i class="fa-regular fa-calendar-check"></i>
      <span>Tous les RDV</span>
    </a>

    <a href="abonnements.php" class="nav-link <?= $currentPage === 'abonnements' ? 'active' : '' ?>">
      <i class="fa-regular fa-clock"></i>
      <span>Abonnements</span>
    </a>

    <a href="qrcodes.php" class="nav-link <?= $currentPage === 'qrcodes' ? 'active' : '' ?>">
      <i class="fa-solid fa-qrcode"></i>
      <span>QR Codes</span>
      <span class="pill blue"><?= htmlspecialchars((string)$qrCount) ?></span>
    </a>
  </div>

  <div class="sidebar-section">
    <p class="section-label">SYSTÈME</p>

    <a href="alertes.php" class="nav-link <?= $currentPage === 'alertes' ? 'active' : '' ?>">
      <i class="fa-regular fa-bell"></i>
      <span>Alertes</span>
      <span class="pill red"><?= htmlspecialchars((string)$alertCount) ?></span>
    </a>

    <a href="parametres.php" class="nav-link <?= $currentPage === 'parametres' ? 'active' : '' ?>">
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
