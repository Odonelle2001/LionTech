<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$unread = countUnread();
$currentAdmin = getCurrentAdmin();
$initials = 'A';
if (!empty($currentAdmin['full_name'])) {
    $parts = explode(' ', $currentAdmin['full_name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[count($parts)-1]) ? substr($parts[count($parts)-1], 0, 1) : ''));
} elseif (!empty($currentAdmin['username'])) {
    $initials = strtoupper(substr($currentAdmin['username'], 0, 1));
}
$bp = BASE_PATH;
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <img src="<?= $bp ?>/liontech-logo.jpg" alt="LIONTECH Logo" class="sidebar-logo-img">
    <div class="sidebar-logo-text">
      <div class="logo-name">LIONTECH</div>
      <div class="logo-sub">Administration</div>
    </div>
  </div>

  <div class="admin-info-box">
    <div class="admin-greeting">Bonjour, <?= htmlspecialchars($currentAdmin['full_name'] ?? $currentAdmin['username'] ?? 'Admin') ?></div>
    <div class="admin-last-login">
      <?php if ($currentAdmin['last_login']): ?>
        Dernière connexion: <?= date('d/m/Y H:i', strtotime($currentAdmin['last_login'])) ?>
      <?php else: ?>
        Première connexion
      <?php endif; ?>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-title">Menu Principal</div>

    <a href="dashboard.php" class="nav-item <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fas fa-home"></i> Tableau de bord
    </a>
    <a href="projects.php" class="nav-item <?= $currentPage === 'projects.php' ? 'active' : '' ?>">
      <i class="fas fa-laptop-code"></i> Réalisations
    </a>
    <a href="team.php" class="nav-item <?= $currentPage === 'team.php' ? 'active' : '' ?>">
      <i class="fas fa-users"></i> Équipe
    </a>
    <a href="messages.php" class="nav-item <?= $currentPage === 'messages.php' ? 'active' : '' ?>">
      <i class="fas fa-envelope"></i> Messages
      <?php if ($unread > 0): ?>
        <span class="nav-badge"><?= $unread ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section-title" style="margin-top:16px;">Configuration</div>

    <a href="admins.php" class="nav-item <?= $currentPage === 'admins.php' ? 'active' : '' ?>">
      <i class="fas fa-user-shield"></i> Administrateurs
    </a>
    <a href="settings.php" class="nav-item <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
      <i class="fas fa-cog"></i> Paramètres
    </a>
    <a href="<?= $bp ?>/" target="_blank" class="nav-item">
      <i class="fas fa-external-link-alt"></i> Voir le site
    </a>

    <a href="logout.php" class="nav-item logout">
      <i class="fas fa-sign-out-alt"></i> Se déconnecter
    </a>
  </nav>

  <div class="sidebar-footer">
    <p class="help-text">LIONTECH &copy; 2026 — v1.0</p>
  </div>
</aside>
