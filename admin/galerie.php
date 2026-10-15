<?php
/**
 * galerie.php
 * ------------
 * Gestion de la galerie photo : ajouter, supprimer, définir principale.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getConnexionDB();
$mariageId = $_SESSION['mariage_id'];

$erreur = null;
$succes = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierTokenCSRF($_POST['csrf_token'] ?? null)) {
        $erreur = "Votre session a expiré, merci de réessayer.";
    } else {

        $action = $_POST['action'] ?? '';

        switch ($action) {

            case 'ajouter':
                if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
                    $erreur = "Merci de choisir une image à ajouter.";
                    break;
                }

                $resultat = traiterUploadImage($_FILES['photo'], UPLOAD_GALERIE);
                if (!$resultat['succes']) {
                    $erreur = $resultat['erreur'];
                    break;
                }

                $stmt = $pdo->prepare("SELECT COALESCE(MAX(ordre), 0) + 1 FROM photos WHERE mariage_id = :id");
                $stmt->execute(['id' => $mariageId]);
                $prochainOrdre = $stmt->fetchColumn();

                $stmt = $pdo->prepare("INSERT INTO photos (mariage_id, nom_fichier, ordre) VALUES (:mariage_id, :nom_fichier, :ordre)");
                $stmt->execute([
                    'mariage_id' => $mariageId,
                    'nom_fichier' => $resultat['nom_fichier'],
                    'ordre' => $prochainOrdre,
                ]);

                $succes = "Photo ajoutée à la galerie.";
                break;

            case 'supprimer':
                $photoId = (int)($_POST['photo_id'] ?? 0);

                $stmt = $pdo->prepare("SELECT * FROM photos WHERE id = :id AND mariage_id = :mariage_id LIMIT 1");
                $stmt->execute(['id' => $photoId, 'mariage_id' => $mariageId]);
                $photo = $stmt->fetch();

                if (!$photo) {
                    $erreur = "Photo introuvable.";
                    break;
                }

                $stmt = $pdo->prepare("DELETE FROM photos WHERE id = :id");
                $stmt->execute(['id' => $photoId]);

                $chemin = UPLOAD_GALERIE . $photo['nom_fichier'];
                if (file_exists($chemin)) {
                    unlink($chemin);
                }

                $succes = "Photo supprimée.";
                break;

            case 'definir_principale':
                $photoId = (int)($_POST['photo_id'] ?? 0);

                $stmt = $pdo->prepare("SELECT id FROM photos WHERE id = :id AND mariage_id = :mariage_id LIMIT 1");
                $stmt->execute(['id' => $photoId, 'mariage_id' => $mariageId]);

                if (!$stmt->fetch()) {
                    $erreur = "Photo introuvable.";
                    break;
                }

                $stmt = $pdo->prepare("UPDATE photos SET est_principale = 0 WHERE mariage_id = :mariage_id");
                $stmt->execute(['mariage_id' => $mariageId]);

                $stmt = $pdo->prepare("UPDATE photos SET est_principale = 1 WHERE id = :id");
                $stmt->execute(['id' => $photoId]);

                $succes = "Photo principale mise à jour.";
                break;

            default:
                $erreur = "Action inconnue.";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM photos WHERE mariage_id = :id ORDER BY ordre ASC");
$stmt->execute(['id' => $mariageId]);
$photos = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM mariages WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $mariageId]);
$mariage = $stmt->fetch();

$csrfToken = genererTokenCSRF();
$titrePage = "Galerie photos";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<h1 class="h3 mb-4">Galerie photos</h1>

<?php if ($erreur): ?><div class="alert alert-danger"><?= e($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="carte-formulaire mb-4">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="action" value="ajouter">

    <h5 class="mb-3">Ajouter une photo</h5>
    <div class="row g-2 align-items-end">
        <div class="col-md-8">
            <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png,.webp" required>
            <div class="form-text">Formats acceptés : JPG, PNG, WEBP. Taille maximum : 5 Mo.</div>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn btn-dark w-100"><i class="bi bi-plus-lg"></i> Ajouter à la galerie</button>
        </div>
    </div>
</form>

<?php if (empty($photos)): ?>
    <p class="text-muted">Aucune photo pour le moment. Ajoutez la première ci-dessus.</p>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($photos as $photo): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <div class="carte-photo <?= $photo['est_principale'] ? 'carte-photo-principale' : '' ?>">
                    <img src="<?= BASE_URL ?>/uploads/galerie/<?= e($photo['nom_fichier']) ?>" alt="Photo de la galerie" class="img-fluid rounded">

                    <?php if ($photo['est_principale']): ?>
                        <span class="badge bg-warning text-dark carte-photo-badge"><i class="bi bi-star-fill"></i> Principale</span>
                    <?php endif; ?>

                    <div class="carte-photo-actions mt-2 d-flex gap-2">
                        <?php if (!$photo['est_principale']): ?>
                            <form method="POST" class="flex-grow-1">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="definir_principale">
                                <input type="hidden" name="photo_id" value="<?= (int)$photo['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100" title="Définir comme principale"><i class="bi bi-star"></i></button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" class="flex-grow-1 formulaire-suppression">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="photo_id" value="<?= (int)$photo['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100" title="Supprimer"><i class="bi bi-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
