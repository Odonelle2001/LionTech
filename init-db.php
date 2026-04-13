<?php
/**
 * LIONTECH — Initialisation de la base de données MySQL
 * Accessible via : http://localhost/LionTechV2/init-db.php
 * À exécuter UNE SEULE FOIS pour créer les tables et données de départ.
 * Supprimez ou renommez ce fichier après utilisation.
 */
require_once __DIR__ . '/db.php';

$db = getDB();

// ─── Création des tables ─────────────────────────────────────────────────────

$db->exec("
CREATE TABLE IF NOT EXISTS projects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    category    VARCHAR(100) NOT NULL DEFAULT 'Plateforme Web',
    description TEXT,
    link        VARCHAR(500),
    tools       VARCHAR(500),
    image       VARCHAR(255),
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_members (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(255) NOT NULL,
    roles         VARCHAR(255) NOT NULL,
    description   TEXT,
    photo         VARCHAR(255),
    portfolio_url VARCHAR(500),
    linkedin      VARCHAR(255),
    github        VARCHAR(255),
    active        TINYINT(1) DEFAULT 1,
    order_num     INT DEFAULT 0,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255) NOT NULL,
    email      VARCHAR(255),
    whatsapp   VARCHAR(50),
    subject    VARCHAR(255),
    message    TEXT,
    is_read    TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name     VARCHAR(255),
    last_login    DATETIME,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// ─── Données de départ ───────────────────────────────────────────────────────

// Admin par défaut (Odonel / Odo2026)
$adminCount = $db->query("SELECT COUNT(*) FROM admins")->fetchColumn();
if ($adminCount == 0) {
    $hash = password_hash('Odo2026', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO admins (username, password_hash, full_name) VALUES (?, ?, ?)")
       ->execute(['Odonel', $hash, 'Marthe Odonelle Njoya']);
}

// Membres de l'équipe
$teamCount = $db->query("SELECT COUNT(*) FROM team_members")->fetchColumn();
if ($teamCount == 0) {
    $members = [
        ['Marthe Odonelle Njoya', 'Développeuse Fullstack', 'Développeuse Fullstack passionnée, Marthe maîtrise le développement front et back-end. Elle conçoit des applications web robustes, des interfaces soignées et des architectures solides.', 'odonel.jpg', 'https://odonelle2001.github.io/MarthePotfolio/index.html', 'marthe-odonelle-njoya', 'Odonelle2001', 0],
        ['Ben FOCH', 'Développeur Web', 'Développeur Web créatif et rigoureux, Ben transforme vos idées en sites web performants et modernes. Spécialiste du développement frontend et de l\'intégration.', 'ben.jpeg', 'https://benfochportfolio.iceiy.com/index.php#hero', '', '', 1],
        ['YONKOUE Njoya Emma', 'Designer Graphique & UI/UX', 'Designer Graphique & UI/UX, Emma donne vie à l\'identité visuelle de vos projets. De la création de logo à la conception d\'interfaces utilisateur intuitives.', 'emma.jpeg', 'https://porte-folio-4-0.vercel.app/', '', '', 2],
    ];
    $stmt = $db->prepare("INSERT INTO team_members (name, roles, description, photo, portfolio_url, linkedin, github, order_num) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($members as $m) $stmt->execute($m);
}

// Paramètres du site
$settingsCount = $db->query("SELECT COUNT(*) FROM settings")->fetchColumn();
if ($settingsCount == 0) {
    $defaults = [
        ['whatsapp_link', 'https://wa.me/237651347948'],
        ['telegram_link', 'https://t.me/liontech'],
        ['email',         'odonellenjoya83@gmail.com'],
        ['phone',         '(651) 347-9485'],
        ['facebook',      'https://facebook.com/liontech'],
        ['instagram',     'https://instagram.com/liontech'],
        ['linkedin',      'https://linkedin.com/company/liontech'],
        ['github',        'https://github.com/Odonelle2001'],
        ['address',       'Yaoundé, Cameroun'],
    ];
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)");
    foreach ($defaults as $s) $stmt->execute($s);
}

// Projets d'exemple
$projectCount = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
if ($projectCount == 0) {
    $projects = [
        ['Boutique Mode Yaoundé',      'Plateforme Web',   'Site e-commerce bilingue — optimisé mobile, +200% de ventes en ligne.', '#', 'HTML, CSS, PHP, MySQL', ''],
        ['Resto Le Palmier',           'Design Graphique', 'Campagne réseaux sociaux — abonnés Instagram × 3 en 60 jours.',          '#', 'Photoshop, Illustrator', ''],
        ['Clinique Santé Plus',        'Plateforme Web',   'Google Ads — retour sur investissement multiplié par 5 en 3 mois.',      '#', 'Google Ads, Analytics', ''],
        ['Bijouterie Elégance',        'Design Graphique', 'Identité de marque complète — logo, charte graphique et supports print.', '#', 'Illustrator, InDesign', ''],
        ['Hôtel Azur Douala',          'Plateforme Web',   'Système de réservation en ligne avec paiement intégré.',                 '#', 'PHP, JavaScript, Stripe', ''],
        ['Cabinet Juridique Dikobé',   'Plateforme Web',   'Site vitrine professionnel avec blog et formulaire de contact.',          '#', 'WordPress, CSS', ''],
    ];
    $stmt = $db->prepare("INSERT INTO projects (title, category, description, link, tools, image) VALUES (?,?,?,?,?,?)");
    foreach ($projects as $p) $stmt->execute($p);
}

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Init DB</title>
<style>body{font-family:sans-serif;max-width:600px;margin:60px auto;padding:20px}
.ok{background:#d4edda;border:1px solid #c3e6cb;padding:20px;border-radius:8px;color:#155724}
a{color:#155724}</style></head><body>
<div class='ok'>
<h2>✅ Base de données initialisée avec succès !</h2>
<p>Tables créées et données de départ insérées.</p>
<p><strong>Identifiants admin :</strong> <code>Odonel</code> / <code>Odo2026</code></p>
<p>⚠️ <strong>Supprimez ou renommez ce fichier</strong> après utilisation pour des raisons de sécurité.</p>
<p><a href=''>↩ Revenir au site</a></p>
</div>
</body></html>";
