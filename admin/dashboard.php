<?php
/**
 * dashboard.php
 * --------------
 * Tableau de bord de l'admin : statistiques globales du mariage.
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$pdo = getConnexionDB();
$mariageId = $_SESSION['mariage_id'];

$stmt = $pdo->prepare("SELECT * FROM mariages WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $mariageId]);
$mariage = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_invites,
        SUM(CASE WHEN presence = 'oui' THEN 1 ELSE 0 END) AS total_presents,
        SUM(CASE WHEN presence = 'non' THEN 1 ELSE 0 END) AS total_absents,
        SUM(CASE WHEN presence = 'en_attente' THEN 1 ELSE 0 END) AS total_attente,
        SUM(CASE WHEN presence = 'oui' THEN nombre_personnes ELSE 0 END) AS total_personnes
    FROM invites
    WHERE mariage_id = :mariage_id
");
$stmt->execute(['mariage_id' => $mariageId]);
$statsInvites = $stmt->fetch();
foreach ($statsInvites as $cle => $valeur) {
    $statsInvites[$cle] = $valeur ?? 0;
}

$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total_messages,
        SUM(CASE WHEN valide = 0 THEN 1 ELSE 0 END) AS messages_en_attente
    FROM messages_livre_or
    WHERE mariage_id = :mariage_id
");
$stmt->execute(['mariage_id' => $mariageId]);
$statsMessages = $stmt->fetch();
foreach ($statsMessages as $cle => $valeur) {
    $statsMessages[$cle] = $valeur ?? 0;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM photos WHERE mariage_id = :mariage_id");
$stmt->execute(['mariage_id' => $mariageId]);
$totalPhotos = $stmt->fetchColumn();

$titrePage = "Tableau de bord";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<h1 class="h3 mb-4">Tableau de bord</h1>
<p class="text-muted mb-4">
    Voici un aperçu de l'organisation du mariage de
    <strong><?= e($mariage['nom_marie']) ?> &amp; <?= e($mariage['nom_mariee']) ?></strong>.
</p>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="carte-stat">
            <i class="bi bi-people fs-2"></i>
            <div class="carte-stat-chiffre"><?= (int)$statsInvites['total_invites'] ?></div>
            <div class="carte-stat-libelle">Invités</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="carte-stat carte-stat-succes">
            <i class="bi bi-check-circle fs-2"></i>
            <div class="carte-stat-chiffre"><?= (int)$statsInvites['total_presents'] ?></div>
            <div class="carte-stat-libelle">Présents</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="carte-stat carte-stat-danger">
            <i class="bi bi-x-circle fs-2"></i>
            <div class="carte-stat-chiffre"><?= (int)$statsInvites['total_absents'] ?></div>
            <div class="carte-stat-libelle">Absents</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="carte-stat carte-stat-attente">
            <i class="bi bi-hourglass-split fs-2"></i>
            <div class="carte-stat-chiffre"><?= (int)$statsInvites['total_attente'] ?></div>
            <div class="carte-stat-libelle">En attente</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="carte-stat">
            <i class="bi bi-person-hearts fs-2"></i>
            <div class="carte-stat-chiffre"><?= (int)$statsInvites['total_personnes'] ?></div>
            <div class="carte-stat-libelle">Personnes attendues</div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="carte-stat">
            <i class="bi bi-chat-heart fs-2"></i>
            <div class="carte-stat-chiffre"><?= (int)$statsMessages['total_messages'] ?></div>
            <div class="carte-stat-libelle">
                Messages du livre d'or
                <?php if ($statsMessages['messages_en_attente'] > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= (int)$statsMessages['messages_en_attente'] ?> à valider</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="carte-stat">
            <i class="bi bi-images fs-2"></i>
            <div class="carte-stat-chiffre"><?= (int)$totalPhotos ?></div>
            <div class="carte-stat-libelle">Photos dans la galerie</div>
        </div>
    </div>
</div>

<a href="<?= BASE_URL ?>/mariage/<?= e($mariage['slug']) ?>" target="_blank" class="btn btn-outline-dark">
    <i class="bi bi-box-arrow-up-right"></i> Voir mon invitation en ligne
</a>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
