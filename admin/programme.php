<?php
/**
 * programme.php
 * --------------
 * Gestion du programme de la journée : ajouter, modifier, supprimer
 * les étapes (cérémonie, cocktail, dîner...).
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

// Liste d'icônes Bootstrap Icons proposées pour rester cohérent visuellement
$iconesDisponibles = [
    'bi-book' => 'Cérémonie religieuse',
    'bi-cup-straw' => 'Cocktail',
    'bi-egg-fried' => 'Dîner',
    'bi-music-note-beamed' => 'Musique / Bal',
    'bi-camera' => 'Séance photo',
    'bi-car-front' => 'Cortège',
    'bi-gift' => 'Cadeaux',
    'bi-calendar-event' => 'Autre évènement',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifierTokenCSRF($_POST['csrf_token'] ?? null)) {
        $erreur = "Votre session a expiré, merci de réessayer.";
    } else {

        $action = $_POST['action'] ?? '';

        switch ($action) {

            // ----------------------------------------------------
            // AJOUTER ou MODIFIER une étape (même formulaire pour les 2,
            // on distingue grâce à la présence d'un "etape_id")
            // ----------------------------------------------------
            case 'enregistrer':
                $etapeId = (int)($_POST['etape_id'] ?? 0);
                $titre = trim($_POST['titre'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $heure = trim($_POST['heure'] ?? '');
                $lieu = trim($_POST['lieu'] ?? '');
                $icone = trim($_POST['icone'] ?? 'bi-calendar-event');

                if ($titre === '' || $heure === '') {
                    $erreur = "Le titre et l'heure sont obligatoires.";
                    break;
                }
                if (!array_key_exists($icone, $iconesDisponibles)) {
                    $icone = 'bi-calendar-event';
                }

                if ($etapeId > 0) {
                    // Modification : on vérifie d'abord que l'étape appartient
                    // bien à ce mariage (isolation multi-mariages)
                    $stmt = $pdo->prepare("SELECT id FROM programme WHERE id = :id AND mariage_id = :mariage_id");
                    $stmt->execute(['id' => $etapeId, 'mariage_id' => $mariageId]);
                    if (!$stmt->fetch()) {
                        $erreur = "Étape introuvable.";
                        break;
                    }

                    $stmt = $pdo->prepare(
                        "UPDATE programme SET titre = :titre, description = :description,
                         heure = :heure, lieu = :lieu, icone = :icone WHERE id = :id"
                    );
                    $stmt->execute([
                        'titre' => $titre, 'description' => $description,
                        'heure' => $heure, 'lieu' => $lieu, 'icone' => $icone,
                        'id' => $etapeId,
                    ]);
                    $succes = "Étape du programme mise à jour.";
                } else {
                    // Création : on calcule le prochain ordre disponible
                    $stmt = $pdo->prepare("SELECT COALESCE(MAX(ordre), 0) + 1 FROM programme WHERE mariage_id = :id");
                    $stmt->execute(['id' => $mariageId]);
                    $prochainOrdre = $stmt->fetchColumn();

                    $stmt = $pdo->prepare(
                        "INSERT INTO programme (mariage_id, titre, description, heure, lieu, icone, ordre)
                         VALUES (:mariage_id, :titre, :description, :heure, :lieu, :icone, :ordre)"
                    );
                    $stmt->execute([
                        'mariage_id' => $mariageId, 'titre' => $titre, 'description' => $description,
                        'heure' => $heure, 'lieu' => $lieu, 'icone' => $icone, 'ordre' => $prochainOrdre,
                    ]);
                    $succes = "Étape ajoutée au programme.";
                }
                break;

            // ----------------------------------------------------
            // SUPPRIMER une étape
            // ----------------------------------------------------
            case 'supprimer':
                $etapeId = (int)($_POST['etape_id'] ?? 0);

                $stmt = $pdo->prepare("DELETE FROM programme WHERE id = :id AND mariage_id = :mariage_id");
                $stmt->execute(['id' => $etapeId, 'mariage_id' => $mariageId]);

                $succes = "Étape supprimée.";
                break;

            default:
                $erreur = "Action inconnue.";
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM programme WHERE mariage_id = :id ORDER BY ordre ASC, heure ASC");
$stmt->execute(['id' => $mariageId]);
$etapes = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM mariages WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $mariageId]);
$mariage = $stmt->fetch();

$csrfToken = genererTokenCSRF();
$titrePage = "Programme";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<h1 class="h3 mb-4">Programme de la journée</h1>

<?php if ($erreur): ?><div class="alert alert-danger"><?= e($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<!-- Formulaire d'ajout -->
<form method="POST" class="carte-formulaire mb-4">
    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
    <input type="hidden" name="action" value="enregistrer">
    <input type="hidden" name="etape_id" value="0">

    <h5 class="mb-3">Ajouter une étape</h5>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Titre</label>
            <input type="text" name="titre" class="form-control" placeholder="Ex : Cocktail" required>
        </div>
        <div class="col-md-2">
            <label class="form-label">Heure</label>
            <input type="time" name="heure" class="form-control" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Lieu</label>
            <input type="text" name="lieu" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label">Icône</label>
            <select name="icone" class="form-select">
                <?php foreach ($iconesDisponibles as $valeur => $libelle): ?>
                    <option value="<?= $valeur ?>"><?= e($libelle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="2"></textarea>
        </div>
    </div>
    <button type="submit" class="btn btn-dark mt-3"><i class="bi bi-plus-lg"></i> Ajouter au programme</button>
</form>

<!-- Liste des étapes existantes, modifiables directement en ligne -->
<?php if (empty($etapes)): ?>
    <p class="text-muted">Aucune étape pour le moment.</p>
<?php else: ?>
    <?php foreach ($etapes as $etape): ?>
        <form method="POST" class="carte-formulaire mb-3">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="action" value="enregistrer">
            <input type="hidden" name="etape_id" value="<?= (int)$etape['id'] ?>">

            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Titre</label>
                    <input type="text" name="titre" class="form-control" value="<?= e($etape['titre']) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Heure</label>
                    <input type="time" name="heure" class="form-control" value="<?= e(substr($etape['heure'], 0, 5)) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Lieu</label>
                    <input type="text" name="lieu" class="form-control" value="<?= e($etape['lieu']) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Icône</label>
                    <select name="icone" class="form-select">
                        <?php foreach ($iconesDisponibles as $valeur => $libelle): ?>
                            <option value="<?= $valeur ?>" <?= $etape['icone'] === $valeur ? 'selected' : '' ?>><?= e($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-outline-dark flex-grow-1"><i class="bi bi-check-lg"></i> Enregistrer</button>
                </div>
            </div>
            <div class="mt-2">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="1"><?= e($etape['description']) ?></textarea>
            </div>
        </form>
    <?php endforeach; ?>

    <!-- Formulaires de suppression séparés, un par étape -->
    <div class="mb-4">
        <?php foreach ($etapes as $etape): ?>
            <form method="POST" class="d-inline formulaire-suppression">
                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="etape_id" value="<?= (int)$etape['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger mb-2">
                    <i class="bi bi-trash"></i> Supprimer « <?= e($etape['titre']) ?> »
                </button>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
