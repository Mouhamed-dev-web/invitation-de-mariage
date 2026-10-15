<?php
/**
 * auth.php
 * ---------
 * Authentification de l'espace admin : session sécurisée, connexion,
 * déconnexion, protection des pages, expiration par inactivité.
 * À inclure tout en haut de chaque page admin.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,   // empêche JavaScript de lire ce cookie
        'samesite' => 'Lax',  // limite l'envoi du cookie depuis un autre site
        'secure' => isset($_SERVER['HTTPS']), // HTTPS uniquement en production
    ]);
    session_start();
}

const DUREE_MAX_INACTIVITE = 30 * 60; // 30 minutes

function verifierExpirationSession(): void
{
    if (isset($_SESSION['derniere_activite'])) {
        $tempsEcoule = time() - $_SESSION['derniere_activite'];
        if ($tempsEcoule > DUREE_MAX_INACTIVITE) {
            deconnecterUtilisateur();
            header('Location: ' . BASE_URL . '/admin/login.php?expiree=1');
            exit;
        }
    }
    $_SESSION['derniere_activite'] = time();
}

function estConnecte(): bool
{
    return isset($_SESSION['user_id']);
}

function connecterUtilisateur(array $user, int $mariageId): void
{
    // Protection contre la fixation de session : nouvel identifiant
    // après une connexion réussie.
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nom'] = $user['nom'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['mariage_id'] = $mariageId;
    $_SESSION['derniere_activite'] = time();
}

function deconnecterUtilisateur(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

/**
 * À appeler en haut de chaque page admin protégée.
 */
function requireLogin(): void
{
    if (!estConnecte()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    verifierExpirationSession();
}
