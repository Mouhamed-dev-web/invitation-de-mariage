<?php
/**
 * admin/index.php
 * -----------------
 * Point d'entrée de l'espace admin : redirige vers le dashboard
 * si connecté, ou vers login.php sinon.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';

if (estConnecte()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/admin/login.php');
}
exit;
