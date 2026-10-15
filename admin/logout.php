<?php
/**
 * logout.php
 * -----------
 * Déconnecte l'administrateur.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';

deconnecterUtilisateur();

header('Location: ' . BASE_URL . '/admin/login.php');
exit;
