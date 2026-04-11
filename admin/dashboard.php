<?php
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$totalProjects = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalMembers = $db->query("SELECT COUNT(*) FROM team_members WHERE active = 1")->fetchColumn();
$totalMessages = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$unreadMessages = $db->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$totalAdmins = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();

$recentProjects = $db->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recentMessages = $db->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

$currentAdmin = getCurrentAdmin();
$initials = 'A';
if (!empty($currentAdmin['full_name'])) {
    $parts = explode(' ', $currentAdmin['full_name']);
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[count($parts)-1]) ? substr($parts[count($parts)-1], 0, 1) : ''));
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LIONTECH Admin — Tableau de bord</title>
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
        <button class="theme-toggle-btn" id="mobileMenuBtn" onclick="document.getElementById('sidebar').classList.toggle('mobile-open')" style="border-radius:6px;">
          <i class="fas fa-bars"></i>
        </button>
        <h1>Tableau de bord</h1>
        <span class="status-badge">En ligne</span>
      </div>
      <div class="topbar-right">
        <a href="messages.php" class="notif-btn">
          <i class="fas fa-bell"></i>
          <?php if ($unreadMessages > 0): ?>
            <span class="notif-dot"></span>
          <?php endif; ?>
        </a>
        <div class="topbar-admin">
          <div class="admin-avatar"><?= $initials ?></div>
          <span class="admin-name"><?= htmlspecialchars($currentAdmin['full_name'] ?? $currentAdmin['username']) ?></span>
        </div>
        <button class="theme-toggle-btn" id="themeBtn"><i class="fas fa-moon" id="themeIcon"></i></button>
      </div>
    </div>

    <div class="page-content">
      <p style="color:var(--muted);font-size:13px;margin-bottom:24px;">Gérez votre site depuis cette interface</p>

      <!-- STATS -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="fas fa-laptop-code"></i></div>
          <div class="stat-info">
            <div class="stat-label">Réalisations</div>
            <div class="stat-value"><?= $totalProjects ?></div>
            <div class="stat-sub"><a href="projects.php" style="color:var(--accent)">Voir tous →</a></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon gold"><i class="fas fa-users"></i></div>
          <div class="stat-info">
            <div class="stat-label">Membres actifs</div>
            <div class="stat-value"><?= $totalMembers ?></div>
            <div class="stat-sub"><a href="team.php" style="color:var(--accent)">Gérer →</a></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-envelope"></i></div>
          <div class="stat-info">
            <div class="stat-label">Messages reçus</div>
            <div class="stat-value"><?= $totalMessages ?></div>
            <div class="stat-sub"><?= $unreadMessages ?> non lu(s)</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon orange"><i class="fas fa-user-shield"></i></div>
          <div class="stat-info">
            <div class="stat-label">Administrateurs</div>
            <div class="stat-value"><?= $totalAdmins ?></div>
            <div class="stat-sub"><a href="admins.php" style="color:var(--accent)">Gérer →</a></div>
          </div>
        </div>
      </div>

      <!-- CONTENT GRID -->
      <div class="content-grid">
        <!-- RECENT PROJECTS -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h2><i class="fas fa-laptop-code" style="color:var(--accent);margin-right:8px;"></i>Réalisations récentes</h2>
            <a href="projects.php" class="card-link">Voir tout →</a>
          </div>
          <div class="admin-card-body" style="padding:0;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Titre</th>
                  <th>Catégorie</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentProjects)): ?>
                  <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px;">Aucun projet</td></tr>
                <?php else: ?>
                  <?php foreach ($recentProjects as $p): ?>
                    <tr>
                      <td><?= htmlspecialchars($p['title']) ?></td>
                      <td>
                        <span class="badge-cat <?= $p['category'] === 'Plateforme Web' ? 'badge-web' : 'badge-design' ?>">
                          <?= htmlspecialchars($p['category']) ?>
                        </span>
                      </td>
                      <td style="color:var(--muted);"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- RECENT MESSAGES -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h2><i class="fas fa-envelope" style="color:var(--accent);margin-right:8px;"></i>Derniers messages</h2>
            <a href="messages.php" class="card-link">Voir tout →</a>
          </div>
          <div class="admin-card-body" style="padding:0;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Sujet</th>
                  <th>Statut</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($recentMessages)): ?>
                  <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px;">Aucun message</td></tr>
                <?php else: ?>
                  <?php foreach ($recentMessages as $msg): ?>
                    <tr>
                      <td><?= htmlspecialchars($msg['name']) ?></td>
                      <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars(substr($msg['subject'] ?: 'Sans sujet', 0, 25)) ?></td>
                      <td>
                        <span class="badge-cat <?= $msg['is_read'] ? 'badge-read' : 'badge-unread' ?>">
                          <?= $msg['is_read'] ? 'Lu' : 'Non lu' ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <div class="admin-card" style="margin-top:20px;">
        <div class="admin-card-header">
          <h2><i class="fas fa-bolt" style="color:var(--gold);margin-right:8px;"></i>Actions rapides</h2>
        </div>
        <div class="admin-card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
          <a href="projects.php?action=add" class="btn-primary"><i class="fas fa-plus"></i> Nouveau projet</a>
          <a href="team.php?action=add" class="btn-primary" style="background:var(--gold);"><i class="fas fa-user-plus"></i> Nouveau membre</a>
          <a href="messages.php" class="btn-secondary"><i class="fas fa-envelope"></i> Voir les messages <?php if ($unreadMessages > 0): ?>(<?= $unreadMessages ?>)<?php endif; ?></a>
          <a href="settings.php" class="btn-secondary"><i class="fas fa-cog"></i> Paramètres</a>
          <a href="<?= BASE_PATH ?>/" target="_blank" class="btn-secondary"><i class="fas fa-external-link-alt"></i> Voir le site</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const saved = localStorage.getItem('lt-admin-theme') || 'dark';
document.documentElement.setAttribute('data-theme', saved);
const icon = document.getElementById('themeIcon');
icon.className = saved === 'dark' ? 'fas fa-sun' : 'fas fa-moon';

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
