<?php
/**
 * header.php
 * -----------
 * En-tête HTML commun aux pages publiques.
 * Attend que $mariage existe déjà (défini dans mariage/index.php).
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($mariage['nom_marie']) ?> & <?= e($mariage['nom_mariee']) ?> — Notre mariage</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

    <style>
        /* Couleurs propres à CE mariage, injectées dynamiquement */
        :root {
            --couleur-principale: <?= e($mariage['couleur_principale']) ?>;
            --couleur-secondaire: <?= e($mariage['couleur_secondaire']) ?>;
        }
    </style>
</head>
<body class="theme-<?= e($mariage['theme']) ?>">
    <?php require_once __DIR__ . '/navbar.php'; ?>
