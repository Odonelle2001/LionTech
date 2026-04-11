<?php
// ─── CONFIGURATION LIONTECH ─────────────────────────────────────────────────
// Modifiez uniquement les paramètres MySQL si nécessaire.
// Le chemin de base (BASE_PATH) est détecté automatiquement.

// Détection automatique du chemin de base (compatible Replit et WAMP)
$_docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$_projDir = rtrim(str_replace('\\', '/', __DIR__), '/');
define('BASE_PATH', str_replace($_docRoot, '', $_projDir));

// ─── Paramètres MySQL ────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'liontech');
define('DB_USER',    'root');       // Utilisateur MySQL (root par défaut sur WAMP)
define('DB_PASS',    '');           // Mot de passe vide par défaut sur WAMP
define('DB_CHARSET', 'utf8mb4');
