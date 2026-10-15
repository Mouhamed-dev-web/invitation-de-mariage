<?php
/**
 * invites.php
 * ------------
 * Liste des invités RSVP : recherche, filtre par présence, pagination,
 * export CSV.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getConnexionDB();
$mariageId = $_SESSION['mariage_id'];

// --------------------------------------------------------
// Lecture des paramètres de recherche/filtre (GET, car ce sont des
// paramètres de CONSULTATION, pas une action qui modifie des données
// — c'est pour ça qu'on utilise l'URL et pas un formulaire POST)
// --------------------------------------------------------
$recherche = trim($_GET['recherche'] ?? '');
$filtre = $_GET['filtre'] ?? 'tous';
if (!in_array($filtre, ['tous', 'oui', 'non', 'en_attente'], true)) {
    $filtre = 'tous';
}

// --------------------------------------------------------
// EXPORT CSV : géré tout en haut, AVANT tout affichage HTML,
// car un fichier CSV ne doit contenir que des données CSV, rien d'autre.
// --------------------------------------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {

    // On applique les mêmes filtres que la liste affichée à l'écran,
    // pour que l'export corresponde exactement à ce que l'admin voit.
    $sql = "SELECT nom_complet, telephone, nombre_personnes, presence, date_confirmation
            FROM invites WHERE mariage_id = :mariage_id";
    $params = ['mariage_id' => $mariageId];

    if ($filtre !== 'tous') {
        $sql .= " AND presence = :presence";
        $params['presence'] = $filtre;
    }
    if ($recherche !== '') {
        $sql .= " AND (nom_complet LIKE :recherche OR telephone LIKE :recherche)";
        $params['recherche'] = '%' . $recherche . '%';
    }
    $sql .= " ORDER BY nom_complet ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tousLesInvites = $stmt->fetchAll();

    // En-têtes HTTP qui disent au navigateur "ceci est un fichier à
    // télécharger", pas une page à afficher
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="invites.csv"');

    $sortie = fopen('php://output', 'w');
    // \xEF\xBB\xBF (BOM UTF-8) : évite que les accents s'affichent mal
    // en ouvrant le fichier directement dans Excel
    fwrite($sortie, "\xEF\xBB\xBF");
    fputcsv($sortie, ['Nom complet', 'Téléphone', 'Nombre de personnes', 'Présence', 'Date de confirmation']);

    foreach ($tousLesInvites as $invite) {
        fputcsv($sortie, [
            $invite['nom_complet'],
            $invite['telephone'],
            $invite['nombre_personnes'],
            $invite['presence'],
            $invite['date_confirmation'],
        ]);
    }

    fclose($sortie);
    exit; // on arrête tout ici : pas de HTML après un fichier CSV
}

// --------------------------------------------------------
// Suppression d'un invité (action POST classique)
// --------------------------------------------------------
$erreur = null;
$succes = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifierTokenCSRF($_POST['csrf_token'] ?? null)) {
        $erreur = "Votre session a expiré, merci de réessayer.";
    } elseif (($_POST['action'] ?? '') === 'supprimer') {
        $inviteId = (int)($_POST['invite_id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM invites WHERE id = :id AND mariage_id = :mariage_id");
        $stmt->execute(['id' => $inviteId, 'mariage_id' => $mariageId]);
        $succes = "Invité supprimé.";
    }
}

// --------------------------------------------------------
// Pagination
// --------------------------------------------------------
$parPage = 15;
$pageActuelle = max(1, (int)($_GET['page'] ?? 1));
$decalage = ($pageActuelle - 1) * $parPage;

// On construit la requête WHERE une seule fois, réutilisée pour le
// COMPTAGE total (pagination) et la LISTE affichée
$conditionsSql = "mariage_id = :mariage_id";
$parametres = ['mariage_id' => $mariageId];

if ($filtre !== 'tous') {
    $conditionsSql .= " AND presence = :presence";
    $parametres['presence'] = $filtre;
}
if ($recherche !== '') {
    $conditionsSql .= " AND (nom_complet LIKE :recherche OR telephone LIKE :recherche)";
    $parametres['recherche'] = '%' . $recherche . '%';
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM invites WHERE $conditionsSql");
$stmt->execute($parametres);
$totalInvites = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalInvites / $parPage));

// LIMIT/OFFSET ne peuvent pas toujours être liés comme des paramètres
// nommés selon les configurations MySQL ; on les sécurise nous-mêmes
// en les forçant en entier avant de les insérer directement.
$sql = "SELECT * FROM invites WHERE $conditionsSql ORDER BY date_confirmation DESC LIMIT " . (int)$parPage . " OFFSET " . (int)$decalage;
$stmt = $pdo->prepare($sql);
$stmt->execute($parametres);
$invites = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM mariages WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $mariageId]);
$mariage = $stmt->fetch();

$csrfToken = genererTokenCSRF();
$titrePage = "Invités";
require_once __DIR__ . '/../includes/admin-header.php';

/**
 * Petite fonction utilitaire pour reconstruire l'URL de pagination
 * en conservant la recherche et le filtre actuels.
 */
function construireUrlPage(int $page, string $recherche, string $filtre): string
{
    return '?page=' . $page . '&recherche=' . urlencode($recherche) . '&filtre=' . urlencode($filtre);
}
?>

<h1 class="h3 mb-4">Invités</h1>

<?php if ($erreur): ?><div class="alert alert-danger"><?= e($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<!-- Barre de recherche + filtres -->
<form method="GET" class="carte-formulaire mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Rechercher (nom ou téléphone)</label>
            <input type="text" name="recherche" class="form-control" value="<?= e($recherche) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Filtrer par présence</label>
            <select name="filtre" class="form-select">
                <option value="tous" <?= $filtre === 'tous' ? 'selected' : '' ?>>Tous</option>
                <option value="oui" <?= $filtre === 'oui' ? 'selected' : '' ?>>Présents</option>
                <option value="non" <?= $filtre === 'non' ? 'selected' : '' ?>>Absents</option>
                <option value="en_attente" <?= $filtre === 'en_attente' ? 'selected' : '' ?>>En attente</option>
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-dark w-100"><i class="bi bi-search"></i> Filtrer</button>
        </div>
    </div>
</form>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted mb-0"><?= $totalInvites ?> invité(s) trouvé(s)</p>
    <a href="?export=csv&recherche=<?= urlencode($recherche) ?>&filtre=<?= urlencode($filtre) ?>" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-download"></i> Exporter en CSV
    </a>
</div>

<?php if (empty($invites)): ?>
    <p class="text-muted">Aucun invité ne correspond à cette recherche.</p>
<?php else: ?>
    <div class="table-responsive carte-formulaire">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Téléphone</th>
                    <th>Personnes</th>
                    <th>Présence</th>
                    <th>Confirmé le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invites as $invite): ?>
                    <tr>
                        <td><?= e($invite['nom_complet']) ?></td>
                        <td><?= e($invite['telephone']) ?></td>
                        <td><?= (int)$invite['nombre_personnes'] ?></td>
                        <td>
                            <?php if ($invite['presence'] === 'oui'): ?>
                                <span class="badge bg-success">Présent</span>
                            <?php elseif ($invite['presence'] === 'non'): ?>
                                <span class="badge bg-danger">Absent</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">En attente</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(date('d/m/Y H:i', strtotime($invite['date_confirmation']))) ?></td>
                        <td>
                            <form method="POST" class="formulaire-suppression">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="supprimer">
                                <input type="hidden" name="invite_id" value="<?= (int)$invite['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= $p === $pageActuelle ? 'active' : '' ?>">
                        <a class="page-link" href="<?= construireUrlPage($p, $recherche, $filtre) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
