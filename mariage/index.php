<?php
/**
 * mariage/index.php
 * -------------------
 * Page publique d'une invitation de mariage.
 * URL propre : /mariage/moussa-fatou (grâce au .htaccess racine)
 * URL directe : mariage/index.php?slug=moussa-fatou
 */

// On démarre une session pour ce visiteur : elle nous sert uniquement
// à stocker le jeton CSRF des formulaires RSVP / livre d'or (aucune
// donnée personnelle n'y est placée).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getConnexionDB();

// --------------------------------------------------------
// 1. Récupération du mariage demandé
// --------------------------------------------------------
$slug = $_GET['slug'] ?? null;

if (!$slug) {
    http_response_code(404);
    die('Invitation introuvable : aucun identifiant fourni.');
}

$stmt = $pdo->prepare("SELECT * FROM mariages WHERE slug = :slug LIMIT 1");
$stmt->execute(['slug' => $slug]);
$mariage = $stmt->fetch();

if (!$mariage) {
    http_response_code(404);
    die("Cette invitation n'existe pas ou a été supprimée.");
}

$mariageId = $mariage['id'];

// --------------------------------------------------------
// 2. Données liées à ce mariage
// --------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM programme WHERE mariage_id = :id ORDER BY ordre ASC, heure ASC");
$stmt->execute(['id' => $mariageId]);
$programme = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM photos WHERE mariage_id = :id ORDER BY ordre ASC");
$stmt->execute(['id' => $mariageId]);
$photos = $stmt->fetchAll();

// On ne récupère QUE les messages déjà validés par l'admin (modération)
$stmt = $pdo->prepare("SELECT * FROM messages_livre_or WHERE mariage_id = :id AND valide = 1 ORDER BY date_ajout DESC");
$stmt->execute(['id' => $mariageId]);
$messages = $stmt->fetchAll();

// Jeton CSRF utilisé par les 2 formulaires publics de cette page
$csrfToken = genererTokenCSRF();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ============================================================ -->
<!-- SECTION A : HERO / ACCUEIL -->
<!-- ============================================================ -->
<section id="hero" class="d-flex flex-column justify-content-center align-items-center text-center vh-100">
    <p class="mb-2">Le mariage de</p>
    <h1 class="display-4">
        <?= e($mariage['nom_marie']) ?>
        <span class="mx-2">&amp;</span>
        <?= e($mariage['nom_mariee']) ?>
    </h1>
    <p class="fs-5"><?= formatDateFrancais($mariage['date_mariage']) ?></p>

    <?php if (!empty($mariage['photo_principale'])): ?>
        <img src="<?= BASE_URL ?>/uploads/couples/<?= e($mariage['photo_principale']) ?>"
             alt="<?= e($mariage['nom_marie']) ?> et <?= e($mariage['nom_mariee']) ?>"
             class="img-fluid rounded my-4" style="max-width: 400px;">
    <?php endif; ?>

    <a href="#notre-histoire" class="btn btn-lg mt-3">Découvrir notre histoire</a>
</section>

<!-- ============================================================ -->
<!-- SECTION B : COMPTE À REBOURS -->
<!-- ============================================================ -->
<section id="compte-a-rebours" class="text-center py-5"
    data-date-mariage="<?= e($mariage['date_mariage']) ?>"
    data-heure-mariage="<?= e($mariage['heure_mariage'] ?? '00:00:00') ?>">
    <div class="row justify-content-center g-3">
        <div class="col-3 col-md-2">
            <div class="p-3 border rounded">
                <div id="cr-jours" class="fs-2 fw-bold">--</div>
                <div>Jours</div>
            </div>
        </div>
        <div class="col-3 col-md-2">
            <div class="p-3 border rounded">
                <div id="cr-heures" class="fs-2 fw-bold">--</div>
                <div>Heures</div>
            </div>
        </div>
        <div class="col-3 col-md-2">
            <div class="p-3 border rounded">
                <div id="cr-minutes" class="fs-2 fw-bold">--</div>
                <div>Minutes</div>
            </div>
        </div>
        <div class="col-3 col-md-2">
            <div class="p-3 border rounded">
                <div id="cr-secondes" class="fs-2 fw-bold">--</div>
                <div>Secondes</div>
            </div>
        </div>
    </div>
    <p id="cr-message-passe" class="fs-4 mt-4 d-none">Le grand jour est arrivé, merci d'être avec nous ! 💍</p>
</section>

<!-- ============================================================ -->
<!-- SECTION C : NOTRE HISTOIRE -->
<!-- ============================================================ -->
<section id="notre-histoire" class="py-5">
    <div class="container reveal">
        <h2 class="text-center mb-4">Notre histoire</h2>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <p class="fs-5"><?= nl2br(e($mariage['description'])) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- SECTION D : CÉRÉMONIE -->
<!-- ============================================================ -->
<section id="ceremonie" class="py-5 text-center">
    <div class="container reveal">
        <h2 class="mb-4">Cérémonie</h2>
        <p><i class="bi bi-calendar-event"></i> <?= formatDateFrancais($mariage['date_mariage']) ?></p>
        <p><i class="bi bi-clock"></i> <?= formatHeureFrancais($mariage['heure_mariage']) ?></p>
        <p><i class="bi bi-geo-alt"></i> <?= e($mariage['lieu']) ?></p>
        <p><?= e($mariage['adresse']) ?></p>

        <?php if (!empty($mariage['lien_google_maps'])): ?>
            <a href="<?= e($mariage['lien_google_maps']) ?>" target="_blank" rel="noopener" class="btn btn-outline-dark mt-2">
                <i class="bi bi-map"></i> Voir l'itinéraire
            </a>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- SECTION E : PROGRAMME -->
<!-- ============================================================ -->
<section id="programme" class="py-5">
    <div class="container reveal">
        <h2 class="text-center mb-4">Programme de la journée</h2>

        <?php if (empty($programme)): ?>
            <p class="text-center text-muted">Le programme sera bientôt communiqué.</p>
        <?php else: ?>
            <div class="row justify-content-center g-4">
                <?php foreach ($programme as $etape): ?>
                    <div class="col-md-4 text-center">
                        <i class="bi <?= e($etape['icone']) ?> fs-1"></i>
                        <h5 class="mt-2"><?= formatHeureFrancais($etape['heure']) ?></h5>
                        <h6><?= e($etape['titre']) ?></h6>
                        <p class="text-muted"><?= e($etape['description']) ?></p>
                        <?php if (!empty($etape['lieu'])): ?>
                            <p class="small"><i class="bi bi-geo-alt"></i> <?= e($etape['lieu']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- SECTION F : GALERIE -->
<!-- ============================================================ -->
<section id="galerie" class="py-5">
    <div class="container reveal">
        <h2 class="text-center mb-4">Galerie</h2>

        <?php if (empty($photos)): ?>
            <p class="text-center text-muted">Aucune photo pour le moment.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($photos as $index => $photo): ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <img src="<?= BASE_URL ?>/uploads/galerie/<?= e($photo['nom_fichier']) ?>"
                             alt="Photo du mariage" class="img-fluid rounded galerie-photo"
                             loading="lazy" data-index="<?= (int)$index ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- SECTION G : RSVP -->
<!-- ============================================================ -->
<section id="rsvp" class="py-5">
    <div class="container reveal">
        <h2 class="text-center mb-4">Confirmez votre présence</h2>

        <div class="row justify-content-center">
            <div class="col-md-6">

                <?php if (isset($_GET['rsvp'])): ?>
                    <?php if ($_GET['rsvp'] === 'succes'): ?>
                        <div class="alert alert-success text-center">Merci ! Votre présence a bien été enregistrée. 🎉</div>
                    <?php elseif ($_GET['rsvp'] === 'erreur'): ?>
                        <div class="alert alert-danger text-center"><?= e($_GET['message'] ?? 'Une erreur est survenue, merci de réessayer.') ?></div>
                    <?php endif; ?>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/rsvp_submit.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="mariage_id" value="<?= (int)$mariageId ?>">
                    <input type="hidden" name="slug" value="<?= e($mariage['slug']) ?>">

                    <div class="mb-3">
                        <label class="form-label">Nom complet</label>
                        <input type="text" name="nom_complet" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" name="telephone" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nombre de personnes</label>
                        <input type="number" name="nombre_personnes" class="form-control" value="1" min="1" max="20" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-block">Serez-vous présent(e) ?</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="presence" value="oui" required>
                            <label class="form-check-label">Oui</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="presence" value="non">
                            <label class="form-check-label">Non</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-lg w-100">Confirmer ma présence</button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================ -->
<!-- SECTION H : LIVRE D'OR -->
<!-- ============================================================ -->
<section id="livre-or" class="py-5">
    <div class="container reveal">
        <h2 class="text-center mb-4">Livre d'or</h2>

        <div class="row justify-content-center mb-5">
            <div class="col-md-6">

                <?php if (isset($_GET['message'])): ?>
                    <?php if ($_GET['message'] === 'succes'): ?>
                        <div class="alert alert-success text-center">Merci pour votre message ! Il sera visible après validation. 💌</div>
                    <?php elseif ($_GET['message'] === 'erreur'): ?>
                        <div class="alert alert-danger text-center"><?= e($_GET['erreur'] ?? 'Une erreur est survenue, merci de réessayer.') ?></div>
                    <?php endif; ?>
                <?php endif; ?>

                <form action="<?= BASE_URL ?>/message_submit.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                    <input type="hidden" name="mariage_id" value="<?= (int)$mariageId ?>">
                    <input type="hidden" name="slug" value="<?= e($mariage['slug']) ?>">

                    <div class="mb-3">
                        <label class="form-label">Votre nom</label>
                        <input type="text" name="nom" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Votre message</label>
                        <textarea name="message" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100">Envoyer un message</button>
                </form>
            </div>
        </div>

        <?php if (empty($messages)): ?>
            <p class="text-center text-muted">Soyez le premier à laisser un mot !</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($messages as $msg): ?>
                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100">
                            <p class="mb-1">"<?= nl2br(e($msg['message'])) ?>"</p>
                            <p class="text-muted small mb-0">— <?= e($msg['nom']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================ -->
<!-- SECTION I : BOUTON MUSIQUE -->
<!-- ============================================================ -->
<?php if (!empty($mariage['musique_fichier'])): ?>
    <audio id="musique-fond" loop>
        <source src="<?= BASE_URL ?>/uploads/couples/<?= e($mariage['musique_fichier']) ?>" type="audio/mpeg">
    </audio>
    <button id="musique-toggle" class="btn rounded-circle position-fixed" style="bottom: 20px; right: 20px; z-index: 1000;">
        <i class="bi bi-volume-mute-fill" id="musique-icone"></i>
    </button>
<?php endif; ?>

<!-- ============================================================ -->
<!-- LIGHTBOX (galerie plein écran) -->
<!-- ============================================================ -->
<div id="lightbox" class="lightbox">
    <button id="lightbox-fermer" class="lightbox-bouton lightbox-fermer" aria-label="Fermer">&times;</button>
    <button id="lightbox-precedent" class="lightbox-bouton lightbox-nav lightbox-precedent" aria-label="Photo précédente">&lsaquo;</button>
    <img id="lightbox-image" src="" alt="Photo du mariage en grand">
    <button id="lightbox-suivant" class="lightbox-bouton lightbox-nav lightbox-suivant" aria-label="Photo suivante">&rsaquo;</button>
</div>

<script>
    const photosGalerie = <?= json_encode(array_map(
        fn($p) => BASE_URL . '/uploads/galerie/' . $p['nom_fichier'],
        $photos
    )) ?>;
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
