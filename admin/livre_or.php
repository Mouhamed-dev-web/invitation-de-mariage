<?php
/**
 * livre_or.php
 * -------------
 * Modération du livre d'or : valider, masquer, supprimer un message.
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
        $messageId = (int)($_POST['message_id'] ?? 0);

        // Vérification d'isolation multi-mariages, comme partout ailleurs
        $stmt = $pdo->prepare("SELECT id FROM messages_livre_or WHERE id = :id AND mariage_id = :mariage_id");
        $stmt->execute(['id' => $messageId, 'mariage_id' => $mariageId]);

        if (!$stmt->fetch()) {
            $erreur = "Message introuvable.";
        } else {
            switch ($action) {
                case 'valider':
                    $stmt = $pdo->prepare("UPDATE messages_livre_or SET valide = 1 WHERE id = :id");
                    $stmt->execute(['id' => $messageId]);
                    $succes = "Message validé, il est maintenant visible sur l'invitation.";
                    break;

                case 'masquer':
                    $stmt = $pdo->prepare("UPDATE messages_livre_or SET valide = 0 WHERE id = :id");
                    $stmt->execute(['id' => $messageId]);
                    $succes = "Message masqué.";
                    break;

                case 'supprimer':
                    $stmt = $pdo->prepare("DELETE FROM messages_livre_or WHERE id = :id");
                    $stmt->execute(['id' => $messageId]);
                    $succes = "Message supprimé définitivement.";
                    break;

                default:
                    $erreur = "Action inconnue.";
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM messages_livre_or WHERE mariage_id = :id ORDER BY date_ajout DESC");
$stmt->execute(['id' => $mariageId]);
$messages = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM mariages WHERE id = :id LIMIT 1");
$stmt->execute(['id' => $mariageId]);
$mariage = $stmt->fetch();

$csrfToken = genererTokenCSRF();
$titrePage = "Livre d'or";
require_once __DIR__ . '/../includes/admin-header.php';
?>

<h1 class="h3 mb-4">Livre d'or</h1>
<p class="text-muted">Les messages ne sont visibles publiquement qu'après validation.</p>

<?php if ($erreur): ?><div class="alert alert-danger"><?= e($erreur) ?></div><?php endif; ?>
<?php if ($succes): ?><div class="alert alert-success"><?= e($succes) ?></div><?php endif; ?>

<?php if (empty($messages)): ?>
    <p class="text-muted">Aucun message pour le moment.</p>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($messages as $msg): ?>
            <div class="col-md-6 col-lg-4">
                <div class="carte-formulaire h-100 d-flex flex-column">
                    <div class="mb-2">
                        <?php if ($msg['valide']): ?>
                            <span class="badge bg-success">Visible</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">En attente</span>
                        <?php endif; ?>
                    </div>
                    <p class="flex-grow-1">"<?= nl2br(e($msg['message'])) ?>"</p>
                    <p class="text-muted small mb-3">
                        — <?= e($msg['nom']) ?> · <?= e(date('d/m/Y H:i', strtotime($msg['date_ajout']))) ?>
                    </p>

                    <div class="d-flex gap-2">
                        <?php if (!$msg['valide']): ?>
                            <form method="POST" class="flex-grow-1">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="valider">
                                <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-success w-100"><i class="bi bi-check-lg"></i> Valider</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="flex-grow-1">
                                <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                                <input type="hidden" name="action" value="masquer">
                                <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-eye-slash"></i> Masquer</button>
                            </form>
                        <?php endif; ?>

                        <form method="POST" class="flex-grow-1 formulaire-suppression">
                            <input type="hidden" name="csrf_token" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash"></i> Supprimer</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>
