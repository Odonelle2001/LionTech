<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_PATH . '/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'] ?: $admin['username'];
            $db->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")
               ->execute([$admin['id']]);
            header('Location: ' . BASE_PATH . '/admin/dashboard.php');
            exit;
        } else {
            $error = 'Identifiant ou mot de passe incorrect.';
        }
    } else {
        $error = 'Veuillez remplir tous les champs.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LIONTECH — Connexion Admin</title>
  <link rel="icon" href="../liontech-logo.jpg" type="image/jpeg">
  <link rel="stylesheet" href="admin.css">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <img src="../liontech-logo.jpg" alt="LIONTECH Logo" class="login-logo-img">
      <div class="name">LIONTECH</div>
      <div class="sub">Espace Administrateur</div>
    </div>

    <h2>Connexion</h2>
    <p>Accès réservé aux administrateurs</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group" style="margin-bottom:16px;">
        <label>Identifiant</label>
        <input type="text" name="username" placeholder="Votre identifiant" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group" style="margin-bottom:24px;">
        <label>Mot de passe</label>
        <div class="pw-field">
          <input type="password" name="password" placeholder="Votre mot de passe" id="pwInput" required>
          <button type="button" class="pw-toggle" onclick="togglePw()"><i class="fas fa-eye" id="pwIcon"></i></button>
        </div>
      </div>
      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:13px;">
        <i class="fas fa-sign-in-alt"></i> Se connecter
      </button>
    </form>
  </div>
</div>

<script>
const saved = localStorage.getItem('lt-admin-theme') || 'dark';
document.documentElement.setAttribute('data-theme', saved);

function togglePw() {
    const input = document.getElementById('pwInput');
    const icon = document.getElementById('pwIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>
</body>
</html>
