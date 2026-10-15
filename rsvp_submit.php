<?php
/**
 * rsvp_submit.php
 * -----------------
 * Traite le formulaire RSVP. Ne fait aucun affichage lui-même :
 * valide, enregistre, puis redirige toujours (pattern PRG).
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

function rediriger_avec_erreur_rsvp(string $slug, string $message): never
{
    $url = BASE_URL . '/mariage/index.php?slug=' . urlencode($slug)
         . '&rsvp=erreur&message=' . urlencode($message) . '#rsvp';
    header('Location: ' . $url);
    exit;
}

if ($slug === '' || $mariageId <= 0) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

// Vérification du jeton CSRF avant tout traitement
if (!verifierTokenCSRF($_POST['csrf_token'] ?? null)) {
    rediriger_avec_erreur_rsvp($slug, "Votre session a expiré, merci de réessayer.");
}

// On vérifie que ce mariage existe vraiment (sécurité)
$stmt = $pdo->prepare("SELECT id FROM mariages WHERE id = :id AND slug = :slug LIMIT 1");
$stmt->execute(['id' => $mariageId, 'slug' => $slug]);
if (!$stmt->fetch()) {
    rediriger_avec_erreur_rsvp($slug, "Une erreur est survenue, merci de réessayer.");
}

$nomComplet = trim($_POST['nom_complet'] ?? '');
if ($nomComplet === '') {
    rediriger_avec_erreur_rsvp($slug, "Merci de renseigner votre nom complet.");
}
if (mb_strlen($nomComplet) > 150) {
    rediriger_avec_erreur_rsvp($slug, "Le nom saisi est trop long.");
}

$telephone = trim($_POST['telephone'] ?? '');
$telephone = preg_replace('/[^0-9+]/', '', $telephone);

$nombrePersonnes = (int)($_POST['nombre_personnes'] ?? 0);
if ($nombrePersonnes < 1 || $nombrePersonnes > 20) {
    rediriger_avec_erreur_rsvp($slug, "Le nombre de personnes doit être compris entre 1 et 20.");
}

$presence = $_POST['presence'] ?? '';
if (!in_array($presence, ['oui', 'non'], true)) {
    rediriger_avec_erreur_rsvp($slug, "Merci d'indiquer si vous serez présent(e).");
}

$stmt = $pdo->prepare(
    "INSERT INTO invites (mariage_id, nom_complet, telephone, nombre_personnes, presence)
     VALUES (:mariage_id, :nom_complet, :telephone, :nombre_personnes, :presence)"
);
$stmt->execute([
    'mariage_id' => $mariageId,
    'nom_complet' => $nomComplet,
    'telephone' => $telephone ?: null,
    'nombre_personnes' => $nombrePersonnes,
    'presence' => $presence,
]);

$url = BASE_URL . '/mariage/index.php?slug=' . urlencode($slug) . '&rsvp=succes#rsvp';
header('Location: ' . $url);
exit;
