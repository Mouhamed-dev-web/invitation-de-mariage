<?php
/**
 * footer.php
 * -----------
 * Pied de page commun, avec les scripts JS en bas de page (performance).
 */
?>
    <footer class="text-center py-4">
        <p class="mb-1"><?= e($mariage['nom_marie']) ?> &amp; <?= e($mariage['nom_mariee']) ?></p>
        <p class="mb-1"><?= formatDateFrancais($mariage['date_mariage']) ?></p>
        <p class="mb-0">&copy; <?= date('Y') ?> — Tous droits réservés</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/script.js"></script>
</body>
</html>
