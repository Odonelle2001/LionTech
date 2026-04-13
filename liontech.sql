-- ─────────────────────────────────────────────────────────────────────────────
-- LIONTECH — Base de données MySQL
-- À importer dans phpMyAdmin : http://localhost/phpmyadmin/
-- Créez d'abord la base "liontech" puis importez ce fichier.
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── Table : projects ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `projects` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(255) NOT NULL,
  `category`    VARCHAR(100) NOT NULL DEFAULT 'Plateforme Web',
  `description` TEXT,
  `link`        VARCHAR(500),
  `tools`       VARCHAR(500),
  `image`       VARCHAR(255),
  `created_at`  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table : team_members ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `team_members` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(255) NOT NULL,
  `roles`         VARCHAR(255) NOT NULL,
  `description`   TEXT,
  `photo`         VARCHAR(255),
  `portfolio_url` VARCHAR(500),
  `linkedin`      VARCHAR(255),
  `github`        VARCHAR(255),
  `active`        TINYINT(1) DEFAULT 1,
  `order_num`     INT DEFAULT 0,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table : messages ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `messages` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(255) NOT NULL,
  `email`      VARCHAR(255),
  `whatsapp`   VARCHAR(50),
  `subject`    VARCHAR(255),
  `message`    TEXT,
  `is_read`    TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table : admins ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name`     VARCHAR(255),
  `last_login`    DATETIME,
  `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Table : settings ────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at`    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Équipe ───────────────────────────────────────────────────────────────────
INSERT INTO `team_members` (`name`, `roles`, `description`, `photo`, `portfolio_url`, `linkedin`, `github`, `active`, `order_num`) VALUES
('Marthe Odonelle Njoya', 'Développeuse Fullstack', 'Développeuse Fullstack passionnée, Marthe maîtrise le développement front et back-end. Elle conçoit des applications web robustes, des interfaces soignées et des architectures solides.', 'odonel.jpg', 'https://odonelle2001.github.io/MarthePotfolio/index.html', 'marthe-odonelle-njoya', 'Odonelle2001', 1, 0),
('Ben FOCH', 'Développeur Web', 'Développeur Web créatif et rigoureux, Ben transforme vos idées en sites web performants et modernes. Spécialiste du développement frontend et de l''intégration.', 'ben.jpeg', 'https://benfochportfolio.iceiy.com/index.php#hero', '', '', 1, 1),
('YONKOUE Njoya Emma', 'Designer Graphique & UI/UX', 'Designer Graphique & UI/UX, Emma donne vie à l''identité visuelle de vos projets. De la création de logo à la conception d''interfaces utilisateur intuitives.', 'emma.jpeg', 'https://porte-folio-4-0.vercel.app/', '', '', 1, 2);

-- ─── Paramètres ───────────────────────────────────────────────────────────────
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('whatsapp_link', 'https://wa.me/237651347948'),
('telegram_link', 'https://t.me/liontech'),
('email', 'odonellenjoya83@gmail.com'),
('phone', '(651) 347-9485'),
('facebook', 'https://facebook.com/liontech'),
('instagram', 'https://instagram.com/liontech'),
('linkedin', 'https://linkedin.com/company/liontech'),
('github', 'https://github.com/Odonelle2001'),
('address', 'Yaoundé, Cameroun');

-- ─── Projets d'exemple ───────────────────────────────────────────────────────
INSERT INTO `projects` (`title`, `category`, `description`, `link`, `tools`, `image`) VALUES
('Boutique Mode Yaoundé', 'Plateforme Web', 'Site e-commerce bilingue — optimisé mobile, +200% de ventes en ligne.', '#', 'HTML, CSS, PHP, MySQL', ''),
('Resto Le Palmier', 'Design Graphique', 'Campagne réseaux sociaux — abonnés Instagram × 3 en 60 jours.', '#', 'Photoshop, Illustrator', ''),
('Clinique Santé Plus', 'Plateforme Web', 'Google Ads — retour sur investissement multiplié par 5 en 3 mois.', '#', 'Google Ads, Analytics', ''),
('Bijouterie Elégance', 'Design Graphique', 'Identité de marque complète — logo, charte graphique et supports print.', '#', 'Illustrator, InDesign', ''),
('Hôtel Azur Douala', 'Plateforme Web', 'Système de réservation en ligne avec paiement intégré.', '#', 'PHP, JavaScript, Stripe', ''),
('Cabinet Juridique Dikobé', 'Plateforme Web', 'Site vitrine professionnel avec blog et formulaire de contact.', '#', 'WordPress, CSS', '');

-- ─── Admin (mot de passe : Odo2026) ──────────────────────────────────────────
-- Le hash sera auto-généré si vous utilisez init-db.php
-- Pour insérer manuellement, exécutez d'abord ce script PHP dans WAMP :
--   <?php echo password_hash('Odo2026', PASSWORD_DEFAULT); ?>
-- Puis remplacez VOTRE_HASH_ICI ci-dessous :
-- INSERT INTO `admins` (`username`, `password_hash`, `full_name`) VALUES
-- ('Odonel', 'VOTRE_HASH_ICI', 'Marthe Odonelle Njoya');
-- OU utilisez simplement init-db.php (recommandé) : http://localhost/LionTechV2/init-db.php

SET FOREIGN_KEY_CHECKS = 1;
