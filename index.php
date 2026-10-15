<?php
/**
 * index.php (racine)
 * --------------------
 * Page d'accueil du service : présente la plateforme et pointe vers
 * l'invitation de démonstration et l'espace admin.
 */

require_once __DIR__ . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #0e0e0e; color: #f2ede3; }
        .accent { color: #D4AF37; }
    </style>
</head>
<body class="d-flex flex-column align-items-center justify-content-center vh-100 text-center px-3">

    <h1 class="mb-3">💍 <?= SITE_NAME ?></h1>
    <p class="mb-4 fs-5">Créez et gérez de magnifiques invitations de mariage numériques.</p>

    <div class="d-flex gap-3 flex-wrap justify-content-center">
        <a href="<?= BASE_URL ?>/mariage/moussa-fatou" class="btn btn-lg btn-outline-light">
            <i class="bi bi-eye"></i> Voir une invitation de démo
        </a>
        <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-lg" style="background:#D4AF37; color:#0e0e0e;">
            <i class="bi bi-speedometer2"></i> Espace administrateur
        </a>
    </div>

</body>
</html>
