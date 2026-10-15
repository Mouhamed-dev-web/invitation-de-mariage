<?php
/**
 * message_submit.php
 * ---------------------
 * Traite le formulaire du livre d'or. Les messages sont enregistrés
 * avec valide = 0 : ils n'apparaîtront publiquement qu'après validation
 * par l'admin (voir admin/livre_or.php).
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getConnexionDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$slug = trim($_POST['slug'] ?? '');
$mariageId = (int)($_POST['mariage_id'] ?? 0);

function rediriger_avec_erreur_message(string $slug, string $erreur): never
{
    $url = BASE_URL . '/mariage/index.php?slug=' . urlencode($slug)
         . '&message=erreur&erreur=' . urlencode($erreur) . '#livre-or';
    header('Location: ' . $url);
    exit;
}

if ($slug === '' || $mariageId <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

if (!verifierTokenCSRF($_POST['csrf_token'] ?? null)) {
    rediriger_avec_erreur_message($slug, "Votre session a expiré, merci de réessayer.");
}

$stmt = $pdo->prepare("SELECT id FROM mariages WHERE id = :id AND slug = :slug LIMIT 1");
$stmt->execute(['id' => $mariageId, 'slug' => $slug]);
if (!$stmt->fetch()) {
    rediriger_avec_erreur_message($slug, "Une erreur est survenue, merci de réessayer.");
}

$nom = trim($_POST['nom'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($nom === '' || $message === '') {
    rediriger_avec_erreur_message($slug, "Merci de renseigner votre nom et votre message.");
}
if (mb_strlen($nom) > 100) {
    rediriger_avec_erreur_message($slug, "Le nom saisi est trop long.");
}
if (mb_strlen($message) > 1000) {
    rediriger_avec_erreur_message($slug, "Le message est trop long (1000 caractères maximum).");
}

$stmt = $pdo->prepare(
    "INSERT INTO messages_livre_or (mariage_id, nom, message, valide)
     VALUES (:mariage_id, :nom, :message, 0)"
);
$stmt->execute([
    'mariage_id' => $mariageId,
    'nom' => $nom,
    'message' => $message,
]);

$url = BASE_URL . '/mariage/index.php?slug=' . urlencode($slug) . '&message=succes#livre-or';
header('Location: ' . $url);
exit;
