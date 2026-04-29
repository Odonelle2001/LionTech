<?php
/* ============================================================
   config.php — Configuration globale LionRDV
   - Détecte automatiquement la BASE_URL du projet
   - Fonctionne aussi bien sur Wampserver (htdocs/LionRDV/...)
     que sur un hébergement à la racine du domaine.
   - Définit aussi DATA_DIR et UPLOADS_DIR (chemins absolus serveur).
============================================================ */

if (!defined('LIONRDV_ROOT')) {
    define('LIONRDV_ROOT', __DIR__);
}

if (!defined('BASE_URL')) {
    /**
     * Calcule l'URL de base à partir de la position du script en cours
     * et de la position de ce config.php (qui est à la racine du projet).
     *
     * Exemples :
     *  - Wamp : http://localhost/LionRDV/RSVAdmin.php
     *           SCRIPT_NAME = /LionRDV/RSVAdmin.php  -> BASE_URL = /LionRDV
     *  - Wamp : http://localhost/LionRDV/AjouterBussiness/AjouterBussiness.php
     *           -> BASE_URL = /LionRDV
     *  - Racine : http://exemple.com/RSVAdmin.php  -> BASE_URL = '' (vide)
     */
    $base = '';
    if (!empty($_SERVER['SCRIPT_FILENAME']) && !empty($_SERVER['SCRIPT_NAME'])) {
        $scriptFile = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']) ?: $_SERVER['SCRIPT_FILENAME']);
        $root       = str_replace('\\', '/', LIONRDV_ROOT);
        $scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

        if ($scriptFile && $root && strpos($scriptFile, $root) === 0) {
            // Sous-dossier relatif du script par rapport à la racine du projet
            $relSub = trim(substr(dirname($scriptFile), strlen($root)), '/');
            // Retirer ce sous-dossier de la fin de SCRIPT_NAME pour ne garder que la base
            if ($relSub === '') {
                $base = rtrim($scriptDir, '/');
            } else {
                $suffix = '/' . $relSub;
                if (substr($scriptDir, -strlen($suffix)) === $suffix) {
                    $base = substr($scriptDir, 0, strlen($scriptDir) - strlen($suffix));
                } else {
                    $base = rtrim($scriptDir, '/');
                }
            }
        } else {
            $base = rtrim($scriptDir, '/');
        }
    }
    if ($base === '/' ) $base = '';
    define('BASE_URL', $base);
}

if (!defined('DATA_DIR')) {
    define('DATA_DIR', LIONRDV_ROOT . '/data');
}
if (!defined('UPLOADS_DIR')) {
    define('UPLOADS_DIR', LIONRDV_ROOT . '/uploads');
}

// Crée les dossiers de données si nécessaire (idempotent)
if (!is_dir(DATA_DIR))    { @mkdir(DATA_DIR, 0775, true); }
if (!is_dir(UPLOADS_DIR)) { @mkdir(UPLOADS_DIR, 0775, true); }

/**
 * Helper : retourne une URL absolue interne au site.
 *   url('AjouterBussiness/AjouterBussiness.php')
 *     -> /LionRDV/AjouterBussiness/AjouterBussiness.php   (Wamp)
 *     -> /AjouterBussiness/AjouterBussiness.php           (racine)
 */
if (!function_exists('url')) {
    function url($path = '') {
        $path = ltrim((string)$path, '/');
        return BASE_URL . '/' . $path;
    }
}

/**
 * Vérifie qu'un fichier média existe dans le dossier du projet.
 * (remplace les anciens file_exists('C:/Xampp/htdocs/LionRDV/...').)
 */
if (!function_exists('media_exists')) {
    function media_exists($relativePath) {
        if (empty($relativePath)) return false;
        $clean = ltrim(str_replace('\\', '/', $relativePath), '/');
        return is_file(LIONRDV_ROOT . '/' . $clean);
    }
}
