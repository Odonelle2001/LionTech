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
        $proj = $db->prepare("SELECT image FROM projects WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$id]);
        $success = 'Projet supprimé avec succès.';
        $action = 'list';
    } elseif ($postAction === 'save') {
        $title       = trim($_POST['title'] ?? '');
        $category    = trim($_POST['category'] ?? 'Plateforme Web');
        $description = trim($_POST['description'] ?? '');
        $link        = trim($_POST['link'] ?? '');
        $tools       = trim($_POST['tools'] ?? '');
        $id          = (int)($_POST['id'] ?? 0);

        $imageFile = '';
        if (!empty($_FILES['image']['name'])) {
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $imageFile = uniqid('proj_') . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/projects/' . $imageFile);
            }
        }

        if (!$title) {
            $error = 'Le titre est obligatoire.';
        } else {
            if ($id > 0) {
                if ($imageFile) {
                    $db->prepare("UPDATE projects SET title=?,category=?,description=?,link=?,tools=?,image=? WHERE id=?")
                       ->execute([$title,$category,$description,$link,$tools,$imageFile,$id]);
                } else {
                    $db->prepare("UPDATE projects SET title=?,category=?,description=?,link=?,tools=? WHERE id=?")
                       ->execute([$title,$category,$description,$link,$tools,$id]);
                }
                $success = 'Projet mis à jour avec succès.';
            } else {
                $db->prepare("INSERT INTO projects (title,category,description,link,tools,image) VALUES (?,?,?,?,?,?)")
                   ->execute([$title,$category,$description,$link,$tools,$imageFile]);
                $success = 'Projet ajouté avec succès.';
            }
            $action = 'list';
        }
    }
}

$editProject = null;
if ($action === 'edit' && $editId > 0) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$editId]);
    $editProject = $stmt->fetch();
    if (!$editProject) { $action = 'list'; }
}

$projects = $db->query("SELECT * FROM projects ORDER BY created_at DESC")->fetchAll();

$currentAdmin = getCurrentAdmin();
$initials = strtoupper(substr($currentAdmin['username'] ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LIONTECH Admin — Réalisations</title>
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
        <h1>Gestion des Réalisations</h1>
      </div>
      <div class="topbar-right">
        <div class="topbar-admin">
          <div class="admin-avatar"><?= $initials ?></div>
        </div>
        <button class="theme-toggle-btn" id="themeBtn"><i class="fas fa-moon" id="themeIcon"></i></button>
      </div>
    </div>

    <div class="page-content">
      <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

      <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- FORM -->
        <div class="admin-card">
          <div class="admin-card-header">
            <h2><?= $action === 'add' ? 'Ajouter un projet' : 'Modifier le projet' ?></h2>
            <a href="projects.php" class="btn-secondary" style="padding:6px 14px;font-size:12px;"><i class="fas fa-arrow-left"></i> Retour</a>
          </div>
          <div class="admin-card-body">
            <form method="POST" enctype="multipart/form-data">
              <input type="hidden" name="action" value="save">
              <input type="hidden" name="id" value="<?= $editProject['id'] ?? 0 ?>">
              <div class="form-grid">
                <div class="form-group">
                  <label>Titre du projet *</label>
                  <input type="text" name="title" value="<?= htmlspecialchars($editProject['title'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label>Catégorie</label>
                  <select name="category">
                    <option value="Plateforme Web" <?= ($editProject['category'] ?? '') === 'Plateforme Web' ? 'selected' : '' ?>>Plateforme Web</option>
                    <option value="Design Graphique" <?= ($editProject['category'] ?? '') === 'Design Graphique' ? 'selected' : '' ?>>Design Graphique</option>
                  </select>
                </div>
                <div class="form-group full">
                  <label>Description</label>
                  <textarea name="description"><?= htmlspecialchars($editProject['description'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                  <label>Lien vers le projet</label>
                  <input type="url" name="link" value="<?= htmlspecialchars($editProject['link'] ?? '') ?>" placeholder="https://...">
                </div>
                <div class="form-group">
                  <label>Langages / Outils (séparés par des virgules)</label>
                  <input type="text" name="tools" value="<?= htmlspecialchars($editProject['tools'] ?? '') ?>" placeholder="PHP, MySQL, Bootstrap...">
                </div>
                <div class="form-group full">
                  <label>Image du projet</label>
                  <input type="file" name="image" accept="image/*">
                  <?php if (!empty($editProject['image'])): ?>
                    <small style="color:var(--muted);">Image actuelle : <?= htmlspecialchars($editProject['image']) ?></small>
                  <?php endif; ?>
                </div>
              </div>
              <div class="form-actions" style="margin-top:20px;">
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="projects.php" class="btn-secondary">Annuler</a>
              </div>
            </form>
          </div>
        </div>

      <?php else: ?>
        <!-- LIST -->
        <div class="page-header">
          <div>
            <h1>Réalisations</h1>
            <p><?= count($projects) ?> projet(s) au total</p>
          </div>
          <a href="projects.php?action=add" class="btn-primary"><i class="fas fa-plus"></i> Nouveau projet</a>
        </div>

        <div class="admin-card">
          <div class="admin-card-body" style="padding:0;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Titre</th>
                  <th>Catégorie</th>
                  <th>Outils</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($projects)): ?>
                  <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted);">Aucun projet. <a href="projects.php?action=add" style="color:var(--accent);">Ajouter le premier →</a></td></tr>
                <?php else: ?>
                  <?php foreach ($projects as $p): ?>
                    <tr>
                      <td>
                        <?php if (!empty($p['image'])): ?>
                          <img src="<?= BASE_PATH ?>/uploads/projects/<?= htmlspecialchars($p['image']) ?>" class="table-avatar" style="border-radius:6px;width:48px;height:36px;object-fit:cover;">
                        <?php else: ?>
                          <div style="width:48px;height:36px;background:var(--bg);border:1px solid var(--border);border-radius:6px;display:grid;place-items:center;color:var(--muted);font-size:12px;"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                      </td>
                      <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
                      <td><span class="badge-cat <?= $p['category'] === 'Plateforme Web' ? 'badge-web' : 'badge-design' ?>"><?= htmlspecialchars($p['category']) ?></span></td>
                      <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars(substr($p['tools'] ?? '', 0, 40)) ?></td>
                      <td style="color:var(--muted);font-size:12px;"><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                      <td>
                        <div class="actions">
                          <a href="projects.php?action=edit&id=<?= $p['id'] ?>" class="btn-action edit"><i class="fas fa-edit"></i> Modifier</a>
                          <form method="POST" onsubmit="return confirm('Supprimer ce projet ?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
