<?php
/**
 * database.php
 * -------------
 * Connexion PDO entre PHP et MySQL.
 */

// En local (XAMPP) : utilisateur root, sans mot de passe.
// En production (Étape 18 du guide de déploiement) : valeurs fournies
// par ton hébergeur cPanel, jamais un mot de passe vide.
define('DB_HOST', 'localhost');
define('DB_NAME', 'wedding_invitation');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Crée et retourne une connexion PDO à la base de données.
 */
function getConnexionDB(): PDO
{
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        if (MODE_DEV) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        } else {
            die("Le site est temporairement indisponible. Merci de réessayer plus tard.");
        }
    }
}
