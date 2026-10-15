<?php
/**
 * functions.php
 * --------------
 * Boîte à outils de fonctions réutilisées dans tout le site.
 */

/**
 * Affiche une chaîne de texte de façon sécurisée contre les attaques XSS.
 * À utiliser à chaque fois qu'on affiche une donnée venant de la base
 * de données ou d'un utilisateur. Exemple : <?= e($mariage['nom_marie']) ?>
 */
function e(?string $chaine): string
{
    return htmlspecialchars($chaine ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Transforme une date MySQL (2026-12-25) en date lisible en français
 * (25 décembre 2026).
 */
function formatDateFrancais(?string $dateMysql): string
{
    if (!$dateMysql) {
        return '';
    }

    $moisFrancais = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    $date = new DateTime($dateMysql);
    $jour = $date->format('j');
    $mois = $moisFrancais[(int)$date->format('n')];
    $annee = $date->format('Y');

    return "$jour $mois $annee";
}

/**
 * Transforme une heure MySQL (16:00:00) en affichage court (16h00).
 */
function formatHeureFrancais(?string $heureMysql): string
{
    if (!$heureMysql) {
        return '';
    }
    $heure = new DateTime($heureMysql);
    return $heure->format('H\hi');
}

/**
 * Traite un upload d'image de façon sécurisée : vérifie l'extension,
 * le type MIME réel, la taille, puis déplace le fichier avec un
 * nouveau nom généré automatiquement (jamais le nom d'origine).
 *
 * @param array  $fichier  Un élément de $_FILES
 * @param string $dossier  Dossier de destination absolu (voir config.php)
 * @return array ['succes' => bool, 'nom_fichier' => string|null, 'erreur' => string|null]
 */
function traiterUploadImage(array $fichier, string $dossier): array
{
    if (!isset($fichier['error']) || $fichier['error'] === UPLOAD_ERR_NO_FILE) {
        return ['succes' => false, 'nom_fichier' => null, 'erreur' => "Aucun fichier n'a été envoyé."];
    }
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        return ['succes' => false, 'nom_fichier' => null, 'erreur' => "Erreur lors de l'envoi du fichier."];
    }

    $tailleMaxOctets = 5 * 1024 * 1024; // 5 Mo
    if ($fichier['size'] > $tailleMaxOctets) {
        return ['succes' => false, 'nom_fichier' => null, 'erreur' => "L'image est trop lourde (maximum 5 Mo)."];
    }

    $extensionsAutorisees = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $extensionsAutorisees, true)) {
        return ['succes' => false, 'nom_fichier' => null, 'erreur' => "Format non autorisé. Utilisez JPG, PNG ou WEBP."];
    }

    $typesAutorises = ['image/jpeg', 'image/png', 'image/webp'];
    $infoFichier = finfo_open(FILEINFO_MIME_TYPE);
    $typeMimeReel = finfo_file($infoFichier, $fichier['tmp_name']);
    finfo_close($infoFichier);

    if (!in_array($typeMimeReel, $typesAutorises, true)) {
        return ['succes' => false, 'nom_fichier' => null, 'erreur' => "Le fichier envoyé n'est pas une image valide."];
    }

    $nouveauNom = uniqid('photo_', true) . '.' . $extension;
    $cheminDestination = $dossier . $nouveauNom;

    if (!move_uploaded_file($fichier['tmp_name'], $cheminDestination)) {
        return ['succes' => false, 'nom_fichier' => null, 'erreur' => "Impossible d'enregistrer l'image."];
    }

    return ['succes' => true, 'nom_fichier' => $nouveauNom, 'erreur' => null];
}

/* ============================================================
   PROTECTION CSRF (Cross-Site Request Forgery)
   ------------------------------------------------------------
   Le CSRF, c'est quand un site malveillant piège un visiteur pour
   lui faire envoyer, à son insu, un formulaire vers NOTRE site
   (par exemple en le connectant à une action destructrice).
   Comparaison : c'est un jeton d'entrée à usage unique. Notre
   propre formulaire reçoit ce jeton caché ; quand il revient, on
   vérifie qu'il correspond exactement à celui qu'on a délivré.
   Un site extérieur ne peut pas connaître ce jeton à l'avance,
   donc une requête forgée depuis l'extérieur est rejetée.
   ============================================================ */

/**
 * Génère (ou réutilise) un jeton CSRF stocké en session, et le renvoie.
 * À utiliser dans chaque formulaire : <input type="hidden" name="csrf_token" value="...">
 */
function genererTokenCSRF(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes() génère des octets véritablement aléatoires,
        // impossibles à deviner — bin2hex() les transforme en texte lisible
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie qu'un jeton reçu correspond bien à celui de la session.
 * hash_equals() compare deux chaînes en temps constant : ça évite
 * qu'un attaquant devine le jeton petit à petit en mesurant le temps
 * de réponse du serveur (attaque dite "par canal auxiliaire").
 */
function verifierTokenCSRF(?string $tokenRecu): bool
{
    if (empty($_SESSION['csrf_token']) || empty($tokenRecu)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $tokenRecu);
}
