# LIONTECH — Site Vitrine + Espace Admin

## Description
Site vitrine professionnel pour l'agence web & digitale LIONTECH (Cameroun), avec espace d'administration sécurisé.

## Stack technique
- **Backend** : PHP 8.2 (serveur intégré)
- **Base de données** : SQLite (via PDO) — adaptable en MySQL pour WAMP
- **Frontend** : HTML5, CSS3, JavaScript vanilla, Font Awesome 6
- **Typographie** : Cormorant Garamond + Inter (Google Fonts)

## Structure du projet
```
index.php          — Page publique principale (dynamique)
index.css          — Styles du site public (thème clair/sombre)
db.php             — Connexion PDO SQLite
init-db.php        — Initialisation de la base de données
contact-handler.php — API du formulaire de contact
liontech.sql       — Export MySQL (compatible WAMP)

admin/
  login.php        — Page de connexion admin
  logout.php       — Déconnexion
  dashboard.php    — Tableau de bord
  projects.php     — CRUD réalisations
  team.php         — CRUD membres équipe
  admins.php       — CRUD administrateurs
  messages.php     — Gestion des messages de contact
  settings.php     — Paramètres du site
  admin.css        — Styles du panneau admin
  includes/
    auth.php       — Middleware d'authentification
    sidebar.php    — Barre latérale admin

uploads/
  projects/        — Images des projets
  team/            — Photos des membres (via admin)

data/
  liontech.db      — Base SQLite (auto-générée)
```

## Accès par défaut
- **Site public** : `/`
- **Admin** : `/admin/login.php`
  - Identifiant : `Odonel`
  - Mot de passe : `Odo2026`

## Fonctionnalités
### Site public
- Thème clair/sombre (persistant via localStorage)
- Bilingue FR/EN (switch en un clic, persistant)
- Navbar responsive avec hamburger mobile
- Sections : Hero, Services (6 cartes), Réalisations (filtre par catégorie), Équipe, Process, Contact
- Formulaire de contact avec envoi en base de données
- Photos des membres : odonel.jpg, ben.jpeg, emma.jpeg (racine du projet)

### Espace admin
- Authentification sécurisée (session PHP + password_hash)
- Dashboard avec statistiques
- CRUD complet : projets, membres, administrateurs
- Gestion des messages avec marquage lu/non lu
- Paramètres configurables : contacts, liens réseaux sociaux

## Déploiement
- **Workflow** : `php -S 0.0.0.0:5000`
- **Port** : 5000
- **Type** : VM (persistant pour la base SQLite)

## Migration vers WAMP/MySQL
1. Importer `liontech.sql` dans phpMyAdmin
2. Modifier `db.php` : remplacer SQLite par MySQL PDO
3. Placer dans `C:/wamp64/www/liontech/`
