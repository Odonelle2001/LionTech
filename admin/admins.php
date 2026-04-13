<?php
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$success = '';
$error = '';
$myId = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete') {
        $id = (int)$_POST['id'];
        if ($id === $myId) {
            $error = 'Vous ne pouvez pas supprimer votre propre compte.';
        } else {
            $total = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
            if ($total <= 1) {
                $error = 'Impossible de supprimer le dernier administrateur.';
            } else {
                $db->prepare("DELETE FROM admins WHERE id = ?")->execute([$id]);
                $success = 'Administrateur supprimé.';
            }
        }
        $action = 'list';
    } elseif ($postAction === 'save') {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['full_name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $id       = (int)($_POST['id'] ?? 0);

        if (!$username) {
            $error = 'L\'identifiant est obligatoire.';
        } else {
            $existing = $db->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $existing->execute([$username, $id]);
            if ($existing->fetch()) {
                $error = 'Cet identifiant est déjà utilisé.';
            } else {
                if ($id > 0) {
                    if ($password) {
                        $db->prepare("UPDATE admins SET username=?,full_name=?,password_hash=? WHERE id=?")
                           ->execute([$username, $fullname, password_hash($password, PASSWORD_DEFAULT), $id]);
                    } else {
                        $db->prepare("UPDATE admins SET username=?,full_name=? WHERE id=?")
                           ->execute([$username, $fullname, $id]);
                    }
                    $success = 'Administrateur mis à jour.';
                } else {
                    if (!$password) {
                        $error = 'Le mot de passe est obligatoire pour un nouvel administrateur.';
                    } else {
                        $db->prepare("INSERT INTO admins (username, full_name, password_hash) VALUES (?,?,?)")
                           ->execute([$username, $fullname, password_hash($password, PASSWORD_DEFAULT)]);
                        $success = 'Administrateur ajouté avec succès.';
                    }
                }
                if (!$error) $action = 'list';
            }
        }
    }
}

$editAdmin = null;
if (($action === 'edit') && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
    $stmt->execute([$editId]);
    $editAdmin = $stmt->fetch();
    if (!$editAdmin) $action = 'list';
}

$admins = $db->query("SELECT id, username, full_name, last_login, created_at FROM admins ORDER BY id ASC")->fetchAll();

$currentAdmin = getCurrentAdmin();
$initials = strtoupper(substr($currentAdmin['username'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LIONTECH Admin — Administrateurs</title>
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
        <h1>Administrateurs</h1>
      </div>
      <div class="topbar-right">
        <div class="admin-avatar"><?= $initials ?></div>
        <button class="theme-toggle-btn" id="themeBtn"><i class="fas fa-moon" id="themeIcon"></i></button>
      </div>
    </div>

    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <?php if ($action === 'add' || $action === 'edit'): ?>
        <div class="admin-card">
          <div class="admin-card-header">
            <h2><?= $action === 'add' ? 'Ajouter un administrateur' : 'Modifier l\'administrateur' ?></h2>
            <a href="admins.php" class="btn-secondary" style="padding:6px 14px;font-size:12px;"><i class="fas fa-arrow-left"></i> Retour</a>
          </div>
          <div class="admin-card-body">
            <form method="POST">
              <input type="hidden" name="action" value="save">
              <input type="hidden" name="id" value="<?= $editAdmin['id'] ?? 0 ?>">
              <div class="form-grid">
                <div class="form-group">
                  <label>Nom complet</label>
                  <input type="text" name="full_name" value="<?= htmlspecialchars($editAdmin['full_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                  <label>Identifiant (login) *</label>
                  <input type="text" name="username" value="<?= htmlspecialchars($editAdmin['username'] ?? '') ?>" required>
                </div>
                <div class="form-group full">
                  <label>Mot de passe <?= $action === 'edit' ? '(laisser vide = ne pas changer)' : '*' ?></label>
                  <div class="pw-field">
                    <input type="password" name="password" id="pwInput" placeholder="<?= $action === 'add' ? 'Mot de passe' : 'Nouveau mot de passe...' ?>">
                    <button type="button" class="pw-toggle" onclick="document.getElementById('pwInput').type = document.getElementById('pwInput').type === 'password' ? 'text' : 'password'"><i class="fas fa-eye"></i></button>
                  </div>
                </div>
              </div>
              <div class="form-actions" style="margin-top:20px;">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="admins.php" class="btn-secondary">Annuler</a>
              </div>
            </form>
          </div>
        </div>

      <?php else: ?>
        <div class="page-header">
          <div>
            <h1>Administrateurs</h1>
            <p><?= count($admins) ?> admin(s) enregistré(s)</p>
          </div>
          <a href="admins.php?action=add" class="btn-primary"><i class="fas fa-user-plus"></i> Ajouter un admin</a>
        </div>

        <div class="admin-card">
          <div class="admin-card-body" style="padding:0;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Nom complet</th>
                  <th>Identifiant</th>
                  <th>Dernière connexion</th>
                  <th>Créé le</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($admins as $a): ?>
                  <tr>
                    <td>
                      <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:32px;height:32px;background:var(--accent);border-radius:50%;display:grid;place-items:center;font-size:12px;color:#fff;flex-shrink:0;"><?= strtoupper(substr($a['username'],0,1)) ?></div>
                        <span><?= htmlspecialchars($a['full_name'] ?: '—') ?></span>
                        <?php if ($a['id'] === $myId): ?><span class="badge-cat badge-read" style="font-size:10px;padding:2px 8px;">Vous</span><?php endif; ?>
                      </div>
                    </td>
                    <td style="font-family:monospace;"><?= htmlspecialchars($a['username']) ?></td>
                    <td style="color:var(--muted);font-size:12px;"><?= $a['last_login'] ? date('d/m/Y H:i', strtotime($a['last_login'])) : 'Jamais' ?></td>
                    <td style="color:var(--muted);font-size:12px;"><?= date('d/m/Y', strtotime($a['created_at'])) ?></td>
                    <td>
                      <div class="actions">
                        <a href="admins.php?action=edit&id=<?= $a['id'] ?>" class="btn-action edit"><i class="fas fa-edit"></i> Modifier</a>
                        <?php if ($a['id'] !== $myId): ?>
                          <form method="POST" onsubmit="return confirm('Supprimer cet administrateur ?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button type="submit" class="btn-action delete"><i class="fas fa-trash"></i></button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
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
