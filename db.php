<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // Détection automatique de l'environnement
        // REPL_ID est présent sur Replit → SQLite
        // Sur WAMP / serveur local → MySQL
        if (getenv('REPL_ID') || getenv('REPL_SLUG')) {
            $pdo = _initSQLite();
        } else {
            $pdo = _initMySQL();
        }
    }
    return $pdo;
}

// ── SQLite (Replit) ───────────────────────────────────────────────────────────
function _initSQLite(): PDO {
    $dbPath = __DIR__ . '/data/liontech.db';
    $isNew  = !file_exists($dbPath);

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    if ($isNew) {
        _seedSQLite($pdo);
    }
    return $pdo;
}

function _seedSQLite(PDO $db): void {
    $db->exec("
    CREATE TABLE IF NOT EXISTS projects (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        category TEXT NOT NULL DEFAULT 'Plateforme Web',
        description TEXT, link TEXT, tools TEXT, image TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS team_members (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL, roles TEXT NOT NULL, description TEXT,
        photo TEXT, portfolio_url TEXT, linkedin TEXT, github TEXT,
        active INTEGER DEFAULT 1, order_num INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL, email TEXT, whatsapp TEXT, subject TEXT, message TEXT,
        is_read INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL,
        full_name TEXT, last_login DATETIME, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        setting_key TEXT NOT NULL UNIQUE, setting_value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ");

    $hash = password_hash('Odo2026', PASSWORD_DEFAULT);
    $db->prepare("INSERT INTO admins (username, password_hash, full_name) VALUES (?,?,?)")
       ->execute(['Odonel', $hash, 'Marthe Odonelle Njoya']);

    $members = [
        ['Marthe Odonelle Njoya','Développeuse Fullstack','Développeuse Fullstack passionnée, Marthe maîtrise le développement front et back-end.','odonel.jpg','https://odonelle2001.github.io/MarthePotfolio/index.html','marthe-odonelle-njoya','Odonelle2001',0],
        ['Ben FOCH','Développeur Web','Développeur Web créatif et rigoureux, Ben transforme vos idées en sites web performants.','ben.jpeg','https://benfochportfolio.iceiy.com/index.php#hero','','',1],
        ['YONKOUE Njoya Emma','Designer Graphique & UI/UX','Designer Graphique & UI/UX, Emma donne vie à l\'identité visuelle de vos projets.','emma.jpeg','https://porte-folio-4-0.vercel.app/','','',2],
    ];
    $stmt = $db->prepare("INSERT INTO team_members (name,roles,description,photo,portfolio_url,linkedin,github,order_num) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($members as $m) $stmt->execute($m);

    $settings = [
        ['whatsapp_link','https://wa.me/237651347948'],['telegram_link','https://t.me/liontech'],
        ['email','odonellenjoya83@gmail.com'],['phone','(651) 347-9485'],
        ['facebook','https://facebook.com/liontech'],['instagram','https://instagram.com/liontech'],
        ['linkedin','https://linkedin.com/company/liontech'],['github','https://github.com/Odonelle2001'],
        ['address','Yaoundé, Cameroun'],
    ];
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)");
    foreach ($settings as $s) $stmt->execute($s);

    $projects = [
        ['Boutique Mode Yaoundé','Plateforme Web','Site e-commerce bilingue — optimisé mobile, +200% de ventes en ligne.','#','HTML, CSS, PHP, MySQL',''],
        ['Resto Le Palmier','Design Graphique','Campagne réseaux sociaux — abonnés Instagram × 3 en 60 jours.','#','Photoshop, Illustrator',''],
        ['Clinique Santé Plus','Plateforme Web','Google Ads — retour sur investissement multiplié par 5 en 3 mois.','#','Google Ads, Analytics',''],
        ['Bijouterie Elégance','Design Graphique','Identité de marque complète — logo, charte graphique et supports print.','#','Illustrator, InDesign',''],
        ['Hôtel Azur Douala','Plateforme Web','Système de réservation en ligne avec paiement intégré.','#','PHP, JavaScript, Stripe',''],
        ['Cabinet Juridique Dikobé','Plateforme Web','Site vitrine professionnel avec blog et formulaire de contact.','#','WordPress, CSS',''],
    ];
    $stmt = $db->prepare("INSERT INTO projects (title,category,description,link,tools,image) VALUES (?,?,?,?,?,?)");
    foreach ($projects as $p) $stmt->execute($p);
}

// ── MySQL (WAMP / Serveur local) ──────────────────────────────────────────────
function _initMySQL(): PDO {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("
        <html><head><meta charset='UTF-8'>
        <style>body{font-family:sans-serif;padding:40px;max-width:700px;margin:auto}
        .box{background:#fff3cd;border:1px solid #ffc107;padding:24px;border-radius:8px;color:#856404}
        code{background:#f8f9fa;padding:2px 6px;border-radius:4px}</style></head><body>
        <div class='box'>
        <h2>⚠️ Erreur de connexion MySQL</h2>
        <p><strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "</p><hr>
        <p>Vérifiez dans <strong>config.php</strong> :</p>
        <ul>
          <li>La base de données <code>" . DB_NAME . "</code> existe dans phpMyAdmin ?</li>
          <li>Utilisateur : <code>" . DB_USER . "</code> — Mot de passe : <code>" . (DB_PASS ?: '(vide)') . "</code></li>
          <li>MySQL est démarré dans WAMP (icône verte) ?</li>
        </ul>
        <p>Première installation ? Accédez à <a href='init-db.php'>init-db.php</a> pour créer les tables.</p>
        </div></body></html>");
    }
}
