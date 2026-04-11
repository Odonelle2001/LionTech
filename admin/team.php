<?php
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'delete') {
        $id = (int)$_POST['id'];
        $db->prepare("DELETE FROM team_members WHERE id = ?")->execute([$id]);
        $success = 'Membre supprimé avec succès.';
        $action = 'list';
    } elseif ($postAction === 'toggle') {
        $id = (int)$_POST['id'];
        $db->prepare("UPDATE team_members SET active = 1 - active WHERE id = ?")->execute([$id]);
        $success = 'Statut du membre mis à jour.';
        $action = 'list';
    } elseif ($postAction === 'save') {
        $name        = trim($_POST['name'] ?? '');
        $roles       = trim($_POST['roles'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $portfolio   = trim($_POST['portfolio_url'] ?? '');
        $linkedin    = trim($_POST['linkedin'] ?? '');
        $github      = trim($_POST['github'] ?? '');
        $order       = (int)($_POST['order_num'] ?? 0);
        $id          = (int)($_POST['id'] ?? 0);

        $photoFile = '';
        if (!empty($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $photoFile = uniqid('team_') . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../uploads/team/' . $photoFile);
            }
        }

        if (!$name || !$roles) {
            $error = 'Le nom et le rôle sont obligatoires.';
        } else {
            if ($id > 0) {
                if ($photoFile) {
                    $db->prepare("UPDATE team_members SET name=?,roles=?,description=?,photo=?,portfolio_url=?,linkedin=?,github=?,order_num=? WHERE id=?")
                       ->execute([$name,$roles,$description,$photoFile,$portfolio,$linkedin,$github,$order,$id]);
                } else {
                    $db->prepare("UPDATE team_members SET name=?,roles=?,description=?,portfolio_url=?,linkedin=?,github=?,order_num=? WHERE id=?")
                       ->execute([$name,$roles,$description,$portfolio,$linkedin,$github,$order,$id]);
                }
                $success = 'Membre mis à jour avec succès.';
            } else {
                $db->prepare("INSERT INTO team_members (name,roles,description,photo,portfolio_url,linkedin,github,order_num) VALUES (?,?,?,?,?,?,?,?)")
                   ->execute([$name,$roles,$description,$photoFile,$portfolio,$linkedin,$github,$order]);
                $success = 'Membre ajouté avec succès.';
            }
            $action = 'list';
        }
    }
}

$editMember = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$editId]);
    $editMember = $stmt->fetch();
    if (!$editMember) $action = 'list';
}

$members = $db->query("SELECT * FROM team_members ORDER BY order_num ASC, id ASC")->fetchAll();

$currentAdmin = getCurrentAdmin();
$initials = strtoupper(substr($currentAdmin['username'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LIONTECH Admin — Équipe</title>
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
        <h1>Gestion de l'Équipe</h1>
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
            <h2><?= $action === 'add' ? 'Ajouter un membre' : 'Modifier le membre' ?></h2>
            <a href="team.php" class="btn-secondary" style="padding:6px 14px;font-size:12px;"><i class="fas fa-arrow-left"></i> Retour</a>
          </div>
          <div class="admin-card-body">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save">
              <input type="hidden" name="id" value="<?= $editMember['id'] ?? 0 ?>">
              <div class="form-grid">
                <div class="form-group">
                  <label>Nom complet *</label>
                  <input type="text" name="name" value="<?= htmlspecialchars($editMember['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label>Rôle(s) *</label>
                  <input type="text" name="roles" value="<?= htmlspecialchars($editMember['roles'] ?? '') ?>" placeholder="Développeur Fullstack, Designer..." required>
                </div>
                <div class="form-group full">
                  <label>Description (biographie courte)</label>
                  <textarea name="description" rows="4" placeholder="Décrivez le membre, son expertise, ses spécialités..."><?= htmlspecialchars($editMember['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                  <label>Lien Portfolio</label>
                  <input type="url" name="portfolio_url" value="<?= htmlspecialchars($editMember['portfolio_url'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="form-group">
                  <label>Ordre d'affichage</label>
                  <input type="number" name="order_num" value="<?= $editMember['order_num'] ?? 0 ?>" min="0">
                </div>
                <div class="form-group">
                  <label>LinkedIn (nom d'utilisateur)</label>
                  <input type="text" name="linkedin" value="<?= htmlspecialchars($editMember['linkedin'] ?? '') ?>" placeholder="prenom-nom">
                </div>
                <div class="form-group">
                  <label>GitHub (nom d'utilisateur)</label>
                  <input type="text" name="github" value="<?= htmlspecialchars($editMember['github'] ?? '') ?>" placeholder="username">
                </div>
                <div class="form-group full">
                  <label>Photo</label>
                  <input type="file" name="photo" accept="image/*">
                  <?php if (!empty($editMember['photo'])): ?>
                    <small style="color:var(--muted);">Photo actuelle : <?= htmlspecialchars($editMember['photo']) ?></small>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-actions" style="margin-top:20px;">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="team.php" class="btn-secondary">Annuler</a>
              </div>
            </form>
          </div>
        </div>

      <?php else: ?>
        <div class="page-header">
          <div>
            <h1>Équipe LIONTECH</h1>
            <p><?= count($members) ?> membre(s)</p>
          </div>
          <a href="team.php?action=add" class="btn-primary"><i class="fas fa-user-plus"></i> Nouveau membre</a>
        </div>

        <div class="admin-card">
          <div class="admin-card-body" style="padding:0;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Photo</th>
                  <th>Nom</th>
                  <th>Rôle(s)</th>
                  <th>Portfolio</th>
                  <th>Statut</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($members)): ?>
                  <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted);">Aucun membre.</td></tr>
                <?php else: ?>
                  <?php foreach ($members as $m): ?>
                    <tr>
                      <td>
                        <?php
                        $photoPath = '';
                        if (!empty($m['photo'])) {
                            if (file_exists(__DIR__ . '/../uploads/team/' . $m['photo'])) {
                                $photoPath = '/uploads/team/' . $m['photo'];
                            } elseif (file_exists(__DIR__ . '/../' . $m['photo'])) {
                                $photoPath = '/' . $m['photo'];
                            }
                        }
                        ?>
                        <?php if ($photoPath): ?>
                          <img src="<?= htmlspecialchars($photoPath) ?>" class="table-avatar">
                        <?php else: ?>
                          <div style="width:36px;height:36px;background:var(--accent);border-radius:50%;display:grid;place-items:center;font-size:13px;color:#fff;"><?= strtoupper(substr($m['name'],0,1)) ?></div>
                        <?php endif; ?>
                      </td>
                      <td><strong><?= htmlspecialchars($m['name']) ?></strong></td>
                      <td style="color:var(--muted);"><?= htmlspecialchars($m['roles']) ?></td>
                      <td>
                        <?php if ($m['portfolio_url']): ?>
                          <a href="<?= htmlspecialchars($m['portfolio_url']) ?>" target="_blank" style="color:var(--accent);font-size:12px;"><i class="fas fa-external-link-alt"></i> Voir</a>
                        <?php else: ?>
                          <span style="color:var(--muted);font-size:12px;">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="action" value="toggle">
                          <input type="hidden" name="id" value="<?= $m['id'] ?>">
                          <button type="submit" class="btn-action <?= $m['active'] ? 'view' : 'delete' ?>" style="border-radius:20px;font-size:11px;">
                            <?= $m['active'] ? '<i class="fas fa-eye"></i> Actif' : '<i class="fas fa-eye-slash"></i> Inactif' ?>
                          </button>
                        </form>
                      </td>
                      <td>
                        <div class="actions">
                          <a href="team.php?action=edit&id=<?= $m['id'] ?>" class="btn-action edit"><i class="fas fa-edit"></i> Modifier</a>
                          <form method="POST" onsubmit="return confirm('Supprimer ce membre ?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn-action delete"><i class="fas fa-trash"></i> Supprimer</button>
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
