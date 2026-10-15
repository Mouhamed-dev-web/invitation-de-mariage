<?php
/**
 * admin-header.php
 * ------------------
 * En-tête commun aux pages admin. Attend que requireLogin() ait déjà
 * été appelé, et que $mariage soit défini.
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titrePage) ? e($titrePage) . ' — ' : '' ?>Espace administrateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css">
</head>
<body>

<nav class="navbar admin-topbar d-lg-none">
    <div class="container-fluid">
        <span class="navbar-brand mb-0"><?= e($mariage['nom_marie']) ?> &amp; <?= e($mariage['nom_mariee']) ?></span>
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarAdmin">
            <i class="bi bi-list fs-4"></i>
        </button>
    </div>
</nav>

<div class="d-flex admin-layout">

    <div class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="sidebarAdmin">
        <div class="offcanvas-header d-lg-none">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarAdmin"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column p-0">
            <div class="p-3 border-bottom d-none d-lg-block">
                <strong><?= e($mariage['nom_marie']) ?> &amp; <?= e($mariage['nom_mariee']) ?></strong>
            </div>

            <ul class="nav flex-column p-3 flex-grow-1">
                <?php
                $pageActuelle = basename($_SERVER['PHP_SELF']);
                $liensMenu = [
                    ['dashboard.php', 'bi-speedometer2', 'Tableau de bord'],
                    ['mariage.php', 'bi-heart', 'Mon mariage'],
                    ['galerie.php', 'bi-images', 'Galerie photos'],
                    ['programme.php', 'bi-calendar-event', 'Programme'],
                    ['invites.php', 'bi-people', 'Invités'],
                    ['livre_or.php', 'bi-chat-heart', "Livre d'or"],
                    ['settings.php', 'bi-gear', 'Paramètres'],
                ];
                foreach ($liensMenu as [$fichier, $icone, $libelle]):
                    $estActif = ($pageActuelle === $fichier);
                ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $estActif ? 'active' : '' ?>" href="<?= $fichier ?>">
                            <i class="bi <?= $icone ?>"></i> <?= e($libelle) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="p-3 border-top">
                <a href="logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i> Se déconnecter
                </a>
            </div>
        </div>
    </div>

    <main class="flex-grow-1 admin-content p-4">
