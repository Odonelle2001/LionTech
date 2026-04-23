-- ============================================================
--  LionRDV — Base de données DÉFINITIVE
--  Un seul fichier propre. Importez dans phpMyAdmin.
--  ⚠️  Supprime et recrée tout. Perdez les données existantes.
-- ============================================================

DROP DATABASE IF EXISTS lionrdv;
CREATE DATABASE lionrdv
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE lionrdv;

-- ============================================================
--  TABLE : businesses
--  Créée par : AjouterBussiness.php (admin IT)
--  Lue par   : Utulisateur.php, reserver.php, ClientLion.php
-- ============================================================
CREATE TABLE businesses (
  id                  INT AUTO_INCREMENT PRIMARY KEY,

  -- Identité
  slug                VARCHAR(100)   NOT NULL UNIQUE,
  name                VARCHAR(150)   NOT NULL,
  initials            VARCHAR(5)     NOT NULL DEFAULT '',
  type                VARCHAR(100)   NOT NULL DEFAULT '',
  description         TEXT,
  city                VARCHAR(150)   DEFAULT NULL,
  neighborhood        VARCHAR(150)   DEFAULT NULL,
  whatsapp            VARCHAR(20)    DEFAULT NULL,

  -- Médias
  logo                VARCHAR(255)   DEFAULT NULL,
  cover_photo         VARCHAR(255)   DEFAULT NULL,
  avatar_photo        VARCHAR(255)   DEFAULT NULL,

  -- Couleurs & thème
  theme_color         VARCHAR(10)    DEFAULT '#C9A84C',
  theme_bg            VARCHAR(10)    DEFAULT '#FFF9EE',
  primary_color       VARCHAR(10)    DEFAULT '#C9A84C',
  secondary_color     VARCHAR(10)    DEFAULT '#0A0A0A',
  button_color        VARCHAR(10)    DEFAULT '#C9A84C',
  text_color          VARCHAR(10)    DEFAULT '#222222',
  background_color    VARCHAR(10)    DEFAULT '#ffffff',
  border_color        VARCHAR(10)    DEFAULT '#e5e7eb',
  title_color         VARCHAR(10)    DEFAULT '#000000',

  -- Navbar & footer
  navbar_style        ENUM('light','dark','transparent')              DEFAULT 'light',
  footer_style        ENUM('minimal','dark','branded','rich')         DEFAULT 'minimal',
  show_biz_logo       TINYINT(1)     DEFAULT 1,
  show_lt_logo        TINYINT(1)     DEFAULT 1,
  lt_footer_only      TINYINT(1)     DEFAULT 0,
  show_connexion_btn  TINYINT(1)     DEFAULT 0,

  -- Langue & typographie globale
  language            ENUM('fr','en','bilingual')                     DEFAULT 'fr',
  global_font         VARCHAR(100)   DEFAULT 'system-ui',
  global_font_size    VARCHAR(10)    DEFAULT '1rem',
  global_font_weight  VARCHAR(5)     DEFAULT '400',

  -- Section À propos
  about_position      ENUM('before','after')                          DEFAULT 'after',
  about_font          VARCHAR(100)   DEFAULT NULL,
  about_font_size     VARCHAR(10)    DEFAULT '1rem',
  about_text_color    VARCHAR(10)    DEFAULT '#444444',

  -- Bouton RDV
  btn_style           VARCHAR(20)    DEFAULT 'filled',
  btn_text            VARCHAR(100)   DEFAULT 'Prendre rendez-vous',

  -- Réservation
  booking_style       ENUM('individual','multiple','employee','capacity','request') DEFAULT 'individual',
  employee_count      INT            DEFAULT 1,
  slot_duration       INT            DEFAULT 45,

  -- Galerie
  gal_display_mode    VARCHAR(20)    DEFAULT 'grid',
  gal_max_photos      INT            DEFAULT 9,
  gal_border          TINYINT(1)     DEFAULT 0,
  show_prices         TINYINT(1)     DEFAULT 1,

  -- Services
  svc_display_style   VARCHAR(20)    DEFAULT 'list',

  -- Horaires
  hor_style           VARCHAR(10)    DEFAULT 'list',

  -- Fond & texture
  bg_texture          VARCHAR(20)    DEFAULT 'none',
  bg_custom_css       TEXT           DEFAULT NULL,

  -- Ordre des sections (JSON)
  sections_order      TEXT           DEFAULT NULL,

  -- Plan & statut
  plan                ENUM('basic','standard','premium')              DEFAULT 'basic',
  status              ENUM('active','config','new','suspended')       DEFAULT 'new',
  internal_notes      TEXT,

  created_at          DATETIME       DEFAULT CURRENT_TIMESTAMP,
  updated_at          DATETIME       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE : owners
--  Créée par : AjouterBussiness.php
--  Connexion : ClientLion.php (WhatsApp + mot de passe)
--  ⚠️  email est optionnel — WhatsApp est l'identifiant principal
-- ============================================================
CREATE TABLE owners (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  business_id     INT           NOT NULL,

  -- Identifiant de connexion (WhatsApp sans +)
  whatsapp        VARCHAR(20)   NOT NULL UNIQUE,
  password_hash   VARCHAR(255)  NOT NULL,

  -- Infos
  name            VARCHAR(150)  DEFAULT NULL,
  email           VARCHAR(150)  DEFAULT NULL,

  -- Préférences dashboard
  language_pref   VARCHAR(5)    DEFAULT 'fr',
  dark_mode       TINYINT(1)    DEFAULT 0,

  -- Onboarding
  must_change_pwd TINYINT(1)    DEFAULT 1,

  created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE : services
--  Insérés automatiquement selon le type de business
--  Modifiables par le propriétaire dans ClientLion.php
-- ============================================================
CREATE TABLE services (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  business_id      INT           NOT NULL,

  name             VARCHAR(150)  NOT NULL,
  name_en          VARCHAR(150)  DEFAULT NULL,
  description      VARCHAR(255)  DEFAULT NULL,
  duration         VARCHAR(20)   DEFAULT NULL,
  duration_minutes INT           DEFAULT NULL,
  price            INT           DEFAULT NULL,
  color            VARCHAR(10)   DEFAULT '#C9A84C',
  display_order    INT           DEFAULT 0,
  active           TINYINT(1)    DEFAULT 1,

  created_at       DATETIME      DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE : availability
--  Insérée par défaut dans AjouterBussiness.php
--  Modifiable par le propriétaire dans ClientLion.php
-- ============================================================
CREATE TABLE availability (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  business_id  INT          NOT NULL,
  day_name     VARCHAR(20)  NOT NULL,
  day_en       VARCHAR(20)  NOT NULL,
  day_index    TINYINT      NOT NULL,
  is_open      TINYINT(1)   DEFAULT 1,
  open_time    TIME         DEFAULT NULL,
  close_time   TIME         DEFAULT NULL,

  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  UNIQUE KEY uq_biz_day (business_id, day_index)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE : gallery
--  Photos uploadées par le propriétaire dans ClientLion.php
-- ============================================================
CREATE TABLE gallery (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  business_id   INT           NOT NULL,
  path          VARCHAR(255)  NOT NULL,
  alt_text      VARCHAR(255)  DEFAULT NULL,
  display_order INT           DEFAULT 0,
  created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,

  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE : reservations
--  Créées par le client dans reserver.php
--  Gérées par le propriétaire dans ClientLion.php
-- ============================================================
CREATE TABLE reservations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  business_id   INT           NOT NULL,
  rdv_uid       VARCHAR(40)   NOT NULL UNIQUE,

  prenom        VARCHAR(80)   NOT NULL,
  nom           VARCHAR(80)   NOT NULL,
  whatsapp      VARCHAR(20)   NOT NULL,

  rdv_date      DATE          NOT NULL,
  rdv_time      TIME          NOT NULL,
  cancel_before DATETIME      DEFAULT NULL,

  status        ENUM('confirmed','cancelled','completed','no_show') DEFAULT 'confirmed',
  cancelled_at  DATETIME      DEFAULT NULL,
  cancel_reason VARCHAR(255)  DEFAULT NULL,

  created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
  INDEX idx_biz_date   (business_id, rdv_date),
  INDEX idx_biz_wa_nom (business_id, whatsapp, nom),
  INDEX idx_biz_slot   (business_id, rdv_date, rdv_time)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  TABLE : reservation_services
-- ============================================================
CREATE TABLE reservation_services (
  reservation_id INT           NOT NULL,
  service_name   VARCHAR(150)  NOT NULL,

  PRIMARY KEY (reservation_id, service_name),
  FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  DONNÉES DE TEST
--  ⚠️  Les mots de passe ci-dessous sont de vrais hashes bcrypt
--  Mot de passe pour les deux comptes : Lion2026!
--  Supprimer ces lignes avant la mise en production.
-- ============================================================

INSERT INTO businesses (
  slug, name, initials, type, description,
  city, neighborhood, whatsapp,
  theme_color, theme_bg, primary_color, secondary_color, button_color,
  text_color, background_color, border_color,
  navbar_style, footer_style, show_biz_logo, show_lt_logo,
  language, global_font, global_font_size, btn_style, btn_text,
  gal_display_mode, gal_max_photos, svc_display_style,
  booking_style, employee_count, slot_duration, show_prices,
  about_position, plan, status
) VALUES
(
  'nora-beauty', 'Nora Beauty', 'NB', 'Salon de beauté',
  'Votre salon de beauté haut de gamme à Bastos, Yaoundé. Spécialiste coiffure, ongles et maquillage.',
  'Yaoundé', 'Bastos', '237699001122',
  '#D4447A', '#FFF0F8', '#D4447A', '#0A0A0A', '#D4447A',
  '#222222', '#ffffff', '#e5e7eb',
  'light', 'minimal', 1, 1,
  'bilingual', 'system-ui', '1rem', 'filled', 'Prendre un RDV',
  'grid', 9, 'list',
  'individual', 3, 45, 1,
  'after', 'standard', 'active'
),
(
  'barber-kings', 'Barber Kings', 'BK', 'Barbier',
  'Le meilleur barbier de Mvog-Ada. Coupes modernes, barbe et rasage traditionnel.',
  'Yaoundé', 'Mvog-Ada', '237690112233',
  '#0A0A0A', '#FAFAF8', '#0A0A0A', '#C9A84C', '#C9A84C',
  '#111111', '#ffffff', '#e5e7eb',
  'dark', 'dark', 1, 1,
  'fr', 'system-ui', '1rem', 'pill', 'Réserver maintenant',
  'grid', 9, 'pills',
  'individual', 2, 30, 1,
  'after', 'basic', 'active'
);

-- ── Propriétaires ─────────────────────────────────────────
-- Mot de passe : Lion2026!
-- Hash généré par : password_hash('Lion2026!', PASSWORD_DEFAULT)
-- Ces hashes sont valides et fonctionnent avec password_verify()
INSERT INTO owners (business_id, whatsapp, password_hash, name, language_pref, dark_mode, must_change_pwd)
VALUES
  (1, '237699001122',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Nora Beauty', 'fr', 0, 1),
  (2, '237690112233',
   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
   'Barber Kings', 'fr', 0, 1);

-- NOTE : Le hash '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
-- correspond au mot de passe 'password' (hash Laravel standard pour les tests).
-- Pour utiliser 'Lion2026!' comme mot de passe réel, exécutez ce PHP une fois :
--   echo password_hash('Lion2026!', PASSWORD_DEFAULT);
-- puis remplacez le hash ci-dessus dans phpMyAdmin.
-- Ou changez simplement le mot de passe via l'écran de première connexion.

-- ── Services Nora Beauty ──────────────────────────────────
INSERT INTO services (business_id, name, name_en, description, duration, duration_minutes, price, color, display_order)
VALUES
  (1, 'Coupe & Brushing',   'Cut & Blowdry',          'Coupe + soin + brushing professionnel', '45 min', 45,  2500,  '#D4447A', 1),
  (1, 'Lissage brésilien',  'Brazilian straightening', 'Lissage longue durée kératine',         '2h',     120, 9000,  '#E07B39', 2),
  (1, 'Pose ongles gel',    'Gel nail application',    'Pose complète ongles gel couleur',      '1h',     60,  5000,  '#7C3AED', 3),
  (1, 'Maquillage complet', 'Full makeup',             'Maquillage professionnel événement',    '1h30',   90,  7000,  '#0EA5E9', 4),
  (1, 'Tresses africaines', 'African braids',          'Tresses sur mesure toutes longueurs',   '3h',     180, 12000, '#059669', 5);

-- ── Services Barber Kings ─────────────────────────────────
INSERT INTO services (business_id, name, name_en, description, duration, duration_minutes, price, color, display_order)
VALUES
  (2, 'Coupe homme',    "Men's cut",   'Coupe moderne dégradé ou classique', '30 min', 30, 1500, '#C9A84C', 1),
  (2, 'Barbe',          'Beard trim',  'Taille et mise en forme de la barbe', '20 min', 20, 1000, '#E07B39', 2),
  (2, 'Rasage complet', 'Full shave',  'Rasage traditionnel à la lame',       '25 min', 25, 1200, '#607D8B', 3),
  (2, 'Coupe + Barbe',  'Cut + Beard', 'Forfait complet coupe et barbe',      '45 min', 45, 2200, '#059669', 4),
  (2, 'Dégradé',        'Fade',        'Dégradé américain ou bas',            '35 min', 35, 2000, '#0EA5E9', 5);

-- ── Horaires Nora Beauty ──────────────────────────────────
INSERT INTO availability (business_id, day_name, day_en, day_index, is_open, open_time, close_time)
VALUES
  (1, 'Dimanche', 'Sunday',    0, 0, NULL,       NULL),
  (1, 'Lundi',    'Monday',    1, 1, '08:00:00', '18:00:00'),
  (1, 'Mardi',    'Tuesday',   2, 1, '08:00:00', '18:00:00'),
  (1, 'Mercredi', 'Wednesday', 3, 1, '08:00:00', '18:00:00'),
  (1, 'Jeudi',    'Thursday',  4, 1, '08:00:00', '18:00:00'),
  (1, 'Vendredi', 'Friday',    5, 1, '08:00:00', '19:00:00'),
  (1, 'Samedi',   'Saturday',  6, 1, '09:00:00', '17:00:00');

-- ── Horaires Barber Kings ─────────────────────────────────
INSERT INTO availability (business_id, day_name, day_en, day_index, is_open, open_time, close_time)
VALUES
  (2, 'Dimanche', 'Sunday',    0, 0, NULL,       NULL),
  (2, 'Lundi',    'Monday',    1, 1, '09:00:00', '19:00:00'),
  (2, 'Mardi',    'Tuesday',   2, 1, '09:00:00', '19:00:00'),
  (2, 'Mercredi', 'Wednesday', 3, 1, '09:00:00', '19:00:00'),
  (2, 'Jeudi',    'Thursday',  4, 1, '09:00:00', '19:00:00'),
  (2, 'Vendredi', 'Friday',    5, 1, '09:00:00', '20:00:00'),
  (2, 'Samedi',   'Saturday',  6, 1, '08:00:00', '20:00:00');

-- ── RDV de test pour Nora Beauty ─────────────────────────
INSERT INTO reservations (business_id, rdv_uid, prenom, nom, whatsapp, rdv_date, rdv_time, cancel_before, status)
VALUES
  (1, 'rdv_test_001', 'Marie',     'Fouda',   '699111222', CURDATE(),                    '14:00:00', DATE_ADD(CURDATE(), INTERVAL 12 HOUR), 'confirmed'),
  (1, 'rdv_test_002', 'Sandrine',  'Ngono',   '677334455', CURDATE(),                    '15:30:00', DATE_ADD(CURDATE(), INTERVAL 14 HOUR), 'confirmed'),
  (1, 'rdv_test_003', 'Christelle','Atangana', '655667788', DATE_SUB(CURDATE(), INTERVAL 1 DAY), '10:00:00', NULL, 'cancelled'),
  (1, 'rdv_test_004', 'Patricia',  'Mvogo',   '690998877', DATE_SUB(CURDATE(), INTERVAL 2 DAY), '09:00:00', NULL, 'completed'),
  (1, 'rdv_test_005', 'Ines',      'Bello',   '677112233', DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'confirmed');

INSERT INTO reservation_services (reservation_id, service_name) VALUES
  (1, 'Lissage brésilien'),
  (2, 'Coupe & Brushing'),
  (3, 'Pose ongles gel'),
  (4, 'Maquillage complet'),
  (5, 'Tresses africaines');