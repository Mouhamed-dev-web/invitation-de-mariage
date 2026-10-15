<?php
/**
 * config.php
 * -----------
 * Réglages généraux du site. Ne contient AUCUNE information secrète.
 */

// Mode développement (affiche les erreurs) ou production (les cache).
// IMPORTANT : mettre à false avant de mettre le site en ligne (Étape 18
// du guide de déploiement fourni séparément).
define('MODE_DEV', true);

if (MODE_DEV) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

define('SITE_NAME', 'Wedding Invitation Platform');

// En local avec XAMPP, adapter au nom exact du dossier dans htdocs.
// En production, ce sera l'URL réelle du domaine (ex: https://moncafe.sn)
define('BASE_URL', 'http://localhost/wedding-invitation');

// Dossiers d'upload
define('UPLOAD_COUPLES', __DIR__ . '/../uploads/couples/');
define('UPLOAD_GALERIE', __DIR__ . '/../uploads/galerie/');
define('UPLOAD_AVATARS', __DIR__ . '/../uploads/avatars/');

// Fuseau horaire (important pour les dates/heures affichées correctement)
date_default_timezone_set('Africa/Dakar');
