<?php
/**
 * settings.php
 * -------------
 * Paramètres du compte admin : changer son nom, son email, son mot
 * de passe. C'est ici qu'on retrouve password_hash() en action une
 * seconde fois (la première étant à la création du compte).
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getConnexionDB();
$userId = $_SESSION['user_id'];
$mariageId = $_SESSION['mariage_id'];

$erreur = null;
$succes = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierTokenCSRF($_POST['csrf_token'] ?? null)) {
        $erreur = "Votre session a expiré, merci de réessayer.";
    } else {

        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $motDePasseActuel = $_POST['mot_de_passe_actuel'] ?? '';
        $nouveauMotDePasse = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirmationMotDePasse = $_POST['confirmation_mot_de_passe'] ?? '';

        if ($nom === '' || $email === '') {
            $erreur = "Le nom et l'email sont obligatoires.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = "L'adresse email n'est pas valide.";
        } else {

            // On vérifie que cet email n'est pas déjà pris par un AUTRE compte
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
            $stmt->execute(['email' => $email, 'id' => $userId]);
            if ($stmt->fetch()) {
                $erreur = "Cet email est déjà utilisé par un autre compte.";
            }
        }

        // Si l'admin veut aussi changer son mot de passe
        $nouveauHash = null;
        if (!$erreur && $nouveauMotDePasse !== '') {

            // On exige le mot de passe ACTUEL avant d'accepter le changement :
            // ça évite qu'une personne qui aurait laissé la session ouverte
            // sur un ordinateur puisse voler définitivement le compte.
            $stmt = $pdo->prepare("SELECT mot_de_passe FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();

            if (!password_verify($motDePasseActuel, $user['mot_de_passe'])) {
                $erreur = "Le mot de passe actuel est incorrect.";
            } elseif (strlen($nouveauMotDePasse) < 8) {
                $erreur = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            } elseif ($nouveauMotDePasse !== $confirmationMotDePasse) {
                $erreur = "La confirmation ne correspond pas au nouveau mot de passe.";
            } else {
                $nouveauHash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
            }
        }

        if (!$erreur) {
            if ($nouveauHash) {
                $stmt = $pdo->prepare("UPDATE users SET nom = :nom, email = :email, mot_de_passe = :mdp WHERE id = :id");
                $stmt->execute(['nom' => $nom, 'email' => $email, 'mdp' => $nouveauHash, 'id' => $userId]);
                $succes = "Vos informations et votre mot de passe ont été mis à jour.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nom = :nom, email = :email WHERE id = :id");
                $stmt->execute(['nom' => $nom, 'email' => $email, 'id' => $userId]);
                $succes = "Vos informations ont été mises à jour.";
            }

            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_email'] = $email;
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM mariages WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $mariageId]);
$mariage = $stmt->fetch();

$csrfToken = genererTokenCSRF();
$titrePage = "Paramètres";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<h1 class="h3 mb-4">Paramètres du compte</h1>

<?php if ($erreur): ?><div class="alert alert-danger"><?= e($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<form method="POST" class="carte-formulaire" style="max-width: 500px;">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <h5 class="mb-3">Informations du compte</h5>
    <div class="mb-3">
        <label class="form-label">Nom</label>
        <input type="text" name="nom" class="form-control" required value="<?= e($user['nom']) ?>">
    </div>
    <div class="mb-4">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required value="<?= e($user['email']) ?>">
    </div>

    <h5 class="mb-3">Changer le mot de passe</h5>
    <p class="text-muted small">Laissez ces champs vides si vous ne souhaitez pas changer votre mot de passe.</p>

    <div class="mb-3">
        <label class="form-label">Mot de passe actuel</label>
        <input type="password" name="mot_de_passe_actuel" class="form-control">
    </div>
    <div class="mb-3">
        <label class="form-label">Nouveau mot de passe</label>
        <input type="password" name="nouveau_mot_de_passe" class="form-control" minlength="8">
        <div class="form-text">8 caractères minimum.</div>
    </div>
    <div class="mb-4">
        <label class="form-label">Confirmer le nouveau mot de passe</label>
        <input type="password" name="confirmation_mot_de_passe" class="form-control" minlength="8">
    </div>

    <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Enregistrer</button>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
