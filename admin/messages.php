<?php
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$success = '';
$viewMsg = null;
$viewId = (int)($_GET['view'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($postAction === 'delete' && $id) {
        $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$id]);
        $success = 'Message supprimé.';
    } elseif ($postAction === 'mark_read' && $id) {
        $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$id]);
        $success = 'Message marqué comme lu.';
    } elseif ($postAction === 'mark_unread' && $id) {
        $db->prepare("UPDATE messages SET is_read = 0 WHERE id = ?")->execute([$id]);
        $success = 'Message marqué comme non lu.';
    } elseif ($postAction === 'delete_all') {
        $db->exec("DELETE FROM messages");
        $success = 'Tous les messages ont été supprimés.';
    }
}

if ($viewId > 0) {
    $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
    $stmt->execute([$viewId]);
    $viewMsg = $stmt->fetch();
    if ($viewMsg && !$viewMsg['is_read']) {
        $db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$viewId]);
        $viewMsg['is_read'] = 1;
    }
}

$messages = $db->query("SELECT * FROM messages ORDER BY is_read ASC, created_at DESC")->fetchAll();
$unreadCount = $db->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();

$currentAdmin = getCurrentAdmin();
$initials = strtoupper(substr($currentAdmin['username'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LIONTECH Admin — Messages</title>
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
        <h1>Messages reçus</h1>
        <?php if ($unreadCount > 0): ?>
          <span class="status-badge" style="background:rgba(231,76,60,0.15);border-color:rgba(231,76,60,0.3);color:var(--danger);"><?= $unreadCount ?> non lu(s)</span>
        <?php endif; ?>
      </div>
      <div class="topbar-right">
        <div class="admin-avatar"><?= $initials ?></div>
        <button class="theme-toggle-btn" id="themeBtn"><i class="fas fa-moon" id="themeIcon"></i></button>
      </div>
    </div>

    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>

      <?php if ($viewMsg): ?>
        <!-- MESSAGE DETAIL -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h2><i class="fas fa-envelope-open" style="color:var(--accent);margin-right:8px;"></i> Message de <?= htmlspecialchars($viewMsg['name']) ?></h2>
            <a href="messages.php" class="btn-secondary" style="padding:6px 14px;font-size:12px;"><i class="fas fa-arrow-left"></i> Retour</a>
          </div>
          <div class="admin-card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
              <div>
                <small style="color:var(--muted);">NOM</small>
                <p style="font-weight:600;"><?= htmlspecialchars($viewMsg['name']) ?></p>
              </div>
              <div>
                <small style="color:var(--muted);">DATE</small>
                <p><?= date('d/m/Y à H:i', strtotime($viewMsg['created_at'])) ?></p>
              </div>
              <?php if ($viewMsg['email']): ?>
              <div>
                <small style="color:var(--muted);">EMAIL</small>
                <p><a href="mailto:<?= htmlspecialchars($viewMsg['email']) ?>" style="color:var(--accent);"><?= htmlspecialchars($viewMsg['email']) ?></a></p>
              </div>
              <?php endif; ?>
              <?php if ($viewMsg['whatsapp']): ?>
              <div>
                <small style="color:var(--muted);">WHATSAPP</small>
                <p><?= htmlspecialchars($viewMsg['whatsapp']) ?></p>
              </div>
              <?php endif; ?>
              <?php if ($viewMsg['subject']): ?>
              <div class="full">
                <small style="color:var(--muted);">SUJET</small>
                <p><?= htmlspecialchars($viewMsg['subject']) ?></p>
              </div>
              <?php endif; ?>
            </div>
            <div>
              <small style="color:var(--muted);">MESSAGE</small>
              <div class="msg-preview" style="margin-top:8px;"><?= nl2br(htmlspecialchars($viewMsg['message'])) ?></div>
            </div>
            <div style="display:flex;gap:12px;margin-top:20px;flex-wrap:wrap;">
              <?php if ($viewMsg['email']): ?>
                <a href="mailto:<?= htmlspecialchars($viewMsg['email']) ?>" class="btn-primary"><i class="fas fa-reply"></i> Répondre par email</a>
              <?php endif; ?>
              <?php if ($viewMsg['whatsapp']): ?>
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $viewMsg['whatsapp']) ?>" target="_blank" class="btn-primary" style="background:#25D366;"><i class="fab fa-whatsapp"></i> WhatsApp</a>
              <?php endif; ?>
              <form method="POST" onsubmit="return confirm('Supprimer ce message ?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $viewMsg['id'] ?>">
                <button type="submit" class="btn-danger"><i class="fas fa-trash"></i> Supprimer</button>
              </form>
            </div>
          </div>
        </div>

      <?php else: ?>
        <!-- MESSAGE LIST -->
        <div class="page-header">
          <div>
            <h1>Messages</h1>
            <p><?= count($messages) ?> message(s) au total</p>
          </div>
          <?php if (!empty($messages)): ?>
          <form method="POST" onsubmit="return confirm('Supprimer TOUS les messages ?');">
            <input type="hidden" name="action" value="delete_all">
            <button type="submit" class="btn-danger" style="font-size:12px;"><i class="fas fa-trash-alt"></i> Tout supprimer</button>
          </form>
          <?php endif; ?>
        </div>

        <div class="admin-card">
          <div class="admin-card-body" style="padding:0;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nom</th>
                  <th>Email / WhatsApp</th>
                  <th>Sujet</th>
                  <th>Date</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($messages)): ?>
                  <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted);">Aucun message reçu.</td></tr>
                <?php else: ?>
                  <?php foreach ($messages as $msg): ?>
                    <tr style="<?= !$msg['is_read'] ? 'font-weight:600;' : '' ?>">
                      <td><?= htmlspecialchars($msg['name']) ?></td>
                      <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($msg['email'] ?: $msg['whatsapp'] ?: '—') ?></td>
                      <td style="font-size:12px;"><?= htmlspecialchars(substr($msg['subject'] ?: 'Sans sujet', 0, 30)) ?></td>
                      <td style="color:var(--muted);font-size:12px;"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></td>
                      <td><span class="badge-cat <?= $msg['is_read'] ? 'badge-read' : 'badge-unread' ?>"><?= $msg['is_read'] ? 'Lu' : 'Non lu' ?></span></td>
                      <td>
                        <div class="actions">
                          <a href="messages.php?view=<?= $msg['id'] ?>" class="btn-action view"><i class="fas fa-eye"></i> Voir</a>
                          <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="<?= $msg['is_read'] ? 'mark_unread' : 'mark_read' ?>">
                            <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                            <button type="submit" class="btn-action"><?= $msg['is_read'] ? '<i class="fas fa-envelope"></i>' : '<i class="fas fa-envelope-open"></i>' ?></button>
                          </form>
                          <form method="POST" onsubmit="return confirm('Supprimer ?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $msg['id'] ?>">
                            <button type="submit" class="btn-action delete"><i class="fas fa-trash"></i></button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>
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
