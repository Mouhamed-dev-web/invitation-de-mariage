<?php
/**
 * login.php
 * ----------
 * Page de connexion de l'espace admin.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (estConnecte()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$erreur = null;
$csrfToken = genererTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierTokenCSRF($_POST['csrf_token'] ?? null)) {
        $erreur = "Votre session a expiré, merci de réessayer.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $motDePasse = $_POST['mot_de_passe'] ?? '';

        if ($email === '' || $motDePasse === '') {
            $erreur = "Merci de renseigner votre email et votre mot de passe.";
        } else {
            $pdo = getConnexionDB();

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($motDePasse, $user['mot_de_passe'])) {

                $stmt = $pdo->prepare("SELECT id FROM mariages WHERE user_id = :user_id LIMIT 1");
                $stmt->execute(['user_id' => $user['id']]);
                $mariage = $stmt->fetch();

                if ($mariage) {
                    connecterUtilisateur($user, $mariage['id']);
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                    exit;
                } else {
                    $erreur = "Aucun mariage n'est associé à ce compte.";
                }
            } else {
                // Message volontairement vague (voir explication : évite
                // l'énumération d'emails valides par un attaquant)
                $erreur = "Email ou mot de passe incorrect.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Espace administrateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-login-body d-flex align-items-center justify-content-center vh-100">

    <div class="card p-4 shadow" style="max-width: 400px; width: 100%;">
        <h1 class="h4 text-center mb-4">Espace administrateur</h1>

        <?php if ($erreur): ?>
            <div class="alert alert-danger"><?= e($erreur) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['expiree'])): ?>
            <div class="alert alert-warning">Votre session a expiré, merci de vous reconnecter.</div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required autofocus
                       value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="mot_de_passe" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Se connecter</button>
        </form>
    </div>

</body>
</html>
