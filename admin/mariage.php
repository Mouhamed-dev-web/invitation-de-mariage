<?php
/**
 * mariage.php
 * ------------
 * Modification des informations du mariage : noms, date, lieu,
 * description, photo principale, couleurs, thème.
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

        $nomMarie = trim($_POST['nom_marie'] ?? '');
        $nomMariee = trim($_POST['nom_mariee'] ?? '');
        $dateMariage = trim($_POST['date_mariage'] ?? '');
        $heureMariage = trim($_POST['heure_mariage'] ?? '');
        $lieu = trim($_POST['lieu'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $lienGoogleMaps = trim($_POST['lien_google_maps'] ?? '');
        $couleurPrincipale = trim($_POST['couleur_principale'] ?? '#000000');
        $couleurSecondaire = trim($_POST['couleur_secondaire'] ?? '#D4AF37');
        $theme = trim($_POST['theme'] ?? 'noir-dore');

        if ($nomMarie === '' || $nomMariee === '') {
            $erreur = "Les noms du marié et de la mariée sont obligatoires.";
        } elseif ($dateMariage === '') {
            $erreur = "La date du mariage est obligatoire.";
        } elseif (!in_array($theme, ['noir-dore', 'blanc-beige', 'rose-poudre', 'vert-dore'], true)) {
            $erreur = "Thème invalide.";
        } elseif (!preg_match('/^#[0-9A-Fa-f]{6}$/', $couleurPrincipale)
               || !preg_match('/^#[0-9A-Fa-f]{6}$/', $couleurSecondaire)) {
            $erreur = "Format de couleur invalide.";
        }

        $nouvelleNomPhoto = null;
        if (!$erreur && isset($_FILES['photo_principale']) && $_FILES['photo_principale']['error'] !== UPLOAD_ERR_NO_FILE) {
            $resultatUpload = traiterUploadImage($_FILES['photo_principale'], UPLOAD_COUPLES);
            if (!$resultatUpload['succes']) {
                $erreur = $resultatUpload['erreur'];
            } else {
                $nouvelleNomPhoto = $resultatUpload['nom_fichier'];
            }
        }

        if (!$erreur) {
            $stmt = $pdo->prepare("SELECT photo_principale FROM mariages WHERE id = :id");
            $stmt->execute(['id' => $mariageId]);
            $ancienneMariage = $stmt->fetch();

            $sql = "UPDATE mariages SET
                        nom_marie = :nom_marie,
                        nom_mariee = :nom_mariee,
                        date_mariage = :date_mariage,
                        heure_mariage = :heure_mariage,
                        lieu = :lieu,
                        adresse = :adresse,
                        description = :description,
                        lien_google_maps = :lien_google_maps,
                        couleur_principale = :couleur_principale,
                        couleur_secondaire = :couleur_secondaire,
                        theme = :theme";

            $parametres = [
                'nom_marie' => $nomMarie,
                'nom_mariee' => $nomMariee,
                'date_mariage' => $dateMariage,
                'heure_mariage' => $heureMariage ?: null,
                'lieu' => $lieu,
                'adresse' => $adresse,
                'description' => $description,
                'lien_google_maps' => $lienGoogleMaps,
                'couleur_principale' => $couleurPrincipale,
                'couleur_secondaire' => $couleurSecondaire,
                'theme' => $theme,
                'id' => $mariageId,
            ];

            if ($nouvelleNomPhoto) {
                $sql .= ", photo_principale = :photo_principale";
                $parametres['photo_principale'] = $nouvelleNomPhoto;
            }

            $sql .= " WHERE id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($parametres);

            if ($nouvelleNomPhoto && !empty($ancienneMariage['photo_principale'])) {
                $ancienChemin = UPLOAD_COUPLES . $ancienneMariage['photo_principale'];
                if (file_exists($ancienChemin)) {
                    unlink($ancienChemin);
                }
            }

            $succes = "Les informations du mariage ont bien été mises à jour.";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM mariages WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $mariageId]);
$mariage = $stmt->fetch();

$csrfToken = genererTokenCSRF();
$titrePage = "Mon mariage";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<h1 class="h3 mb-4">Mon mariage</h1>

<?php if ($erreur): ?><div class="alert alert-danger"><?= e($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="carte-formulaire">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">

    <h5 class="mb-3">Les mariés</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label">Nom du marié</label>
            <input type="text" name="nom_marie" class="form-control" required value="<?= e($mariage['nom_marie']) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Nom de la mariée</label>
            <input type="text" name="nom_mariee" class="form-control" required value="<?= e($mariage['nom_mariee']) ?>">
        </div>
    </div>

    <h5 class="mb-3">Date et lieu</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Date du mariage</label>
            <input type="date" name="date_mariage" class="form-control" required value="<?= e($mariage['date_mariage']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">Heure</label>
            <input type="time" name="heure_mariage" class="form-control" value="<?= e(substr($mariage['heure_mariage'] ?? '', 0, 5)) ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Lieu</label>
            <input type="text" name="lieu" class="form-control" value="<?= e($mariage['lieu']) ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Adresse complète</label>
            <input type="text" name="adresse" class="form-control" value="<?= e($mariage['adresse']) ?>">
        </div>
        <div class="col-12">
            <label class="form-label">Lien Google Maps</label>
            <input type="url" name="lien_google_maps" class="form-control" placeholder="https://maps.google.com/..." value="<?= e($mariage['lien_google_maps']) ?>">
        </div>
    </div>

    <h5 class="mb-3">Notre histoire</h5>
    <div class="mb-4">
        <textarea name="description" class="form-control" rows="5"><?= e($mariage['description']) ?></textarea>
    </div>

    <h5 class="mb-3">Photo principale</h5>
    <div class="mb-4">
        <?php if (!empty($mariage['photo_principale'])): ?>
            <img src="<?= BASE_URL ?>/uploads/couples/<?= e($mariage['photo_principale']) ?>" alt="Photo actuelle" class="img-thumbnail mb-2" style="max-width: 200px;">
            <p class="text-muted small">Photo actuelle. Choisissez un fichier ci-dessous pour la remplacer.</p>
        <?php endif; ?>
        <input type="file" name="photo_principale" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        <div class="form-text">Formats acceptés : JPG, PNG, WEBP. Taille maximum : 5 Mo.</div>
    </div>

    <h5 class="mb-3">Apparence</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Couleur principale</label>
            <input type="color" name="couleur_principale" class="form-control form-control-color" value="<?= e($mariage['couleur_principale']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Couleur secondaire (accent)</label>
            <input type="color" name="couleur_secondaire" class="form-control form-control-color" value="<?= e($mariage['couleur_secondaire']) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Thème</label>
            <select name="theme" class="form-select">
                <?php
                $themesDisponibles = [
                    'noir-dore' => 'Noir + Doré',
                    'blanc-beige' => 'Blanc + Beige',
                    'rose-poudre' => 'Rose poudré',
                    'vert-dore' => 'Vert profond + Doré',
                ];
                foreach ($themesDisponibles as $valeur => $libelle):
                ?>
                    <option value="<?= $valeur ?>" <?= $mariage['theme'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg"></i> Enregistrer les modifications</button>
</form>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
