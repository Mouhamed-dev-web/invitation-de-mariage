/**
 * admin.js
 * ---------
 * JavaScript de l'espace admin.
 */
document.addEventListener('DOMContentLoaded', function () {
    initConfirmationSuppression();
});

/**
 * Ajoute une boîte de confirmation avant l'envoi de tout formulaire
 * ayant la classe "formulaire-suppression" (photos, invités, messages...).
 */
function initConfirmationSuppression() {
    const formulaires = document.querySelectorAll('.formulaire-suppression');

    formulaires.forEach(function (formulaire) {
        formulaire.addEventListener('submit', function (evenement) {
            const confirmation = confirm("Voulez-vous vraiment supprimer cet élément ? Cette action est irréversible.");
            if (!confirmation) {
                evenement.preventDefault();
            }
        });
    });
}
