/**
 * script.js
 * ----------
 * Interactivité de la page publique de l'invitation.
 */
document.addEventListener('DOMContentLoaded', function () {
    initCompteARebours();
    initRevealAuScroll();
    initLightbox();
    initMusique();
});

/* ============================================================
   1. COMPTE À REBOURS
   ============================================================ */
function initCompteARebours() {
    const section = document.getElementById('compte-a-rebours');
    if (!section) return;

    const dateMariage = section.dataset.dateMariage;
    const heureMariage = section.dataset.heureMariage;
    const dateCible = new Date(`${dateMariage}T${heureMariage}`);

    const elJours = document.getElementById('cr-jours');
    const elHeures = document.getElementById('cr-heures');
    const elMinutes = document.getElementById('cr-minutes');
    const elSecondes = document.getElementById('cr-secondes');
    const messagePasse = document.getElementById('cr-message-passe');

    function mettreAJour() {
        const maintenant = new Date();
        const difference = dateCible - maintenant;

        if (difference <= 0) {
            section.querySelector('.row').classList.add('d-none');
            messagePasse.classList.remove('d-none');
            clearInterval(intervalle);
            return;
        }

        const jours = Math.floor(difference / (1000 * 60 * 60 * 24));
        const heures = Math.floor((difference / (1000 * 60 * 60)) % 24);
        const minutes = Math.floor((difference / (1000 * 60)) % 60);
        const secondes = Math.floor((difference / 1000) % 60);

        elJours.textContent = jours;
        elHeures.textContent = String(heures).padStart(2, '0');
        elMinutes.textContent = String(minutes).padStart(2, '0');
        elSecondes.textContent = String(secondes).padStart(2, '0');
    }

    mettreAJour();
    const intervalle = setInterval(mettreAJour, 1000);
}

/* ============================================================
   2. APPARITION EN DOUCEUR AU SCROLL
   ============================================================ */
function initRevealAuScroll() {
    const elements = document.querySelectorAll('.reveal');
    if (elements.length === 0) return;

    const observateur = new IntersectionObserver(function (entrees) {
        entrees.forEach(function (entree) {
            if (entree.isIntersecting) {
                entree.target.classList.add('visible');
                observateur.unobserve(entree.target);
            }
        });
    }, { threshold: 0.15 });

    elements.forEach(function (element) {
        observateur.observe(element);
    });
}

/* ============================================================
   3. LIGHTBOX (galerie photo)
   ============================================================ */
function initLightbox() {
    if (typeof photosGalerie === 'undefined' || photosGalerie.length === 0) {
        return;
    }

    const lightbox = document.getElementById('lightbox');
    const imageLightbox = document.getElementById('lightbox-image');
    const boutonFermer = document.getElementById('lightbox-fermer');
    const boutonPrecedent = document.getElementById('lightbox-precedent');
    const boutonSuivant = document.getElementById('lightbox-suivant');
    const miniatures = document.querySelectorAll('.galerie-photo');

    let indexActuel = 0;

    function ouvrir(index) {
        indexActuel = index;
        imageLightbox.src = photosGalerie[indexActuel];
        lightbox.classList.add('ouverte');
        document.body.style.overflow = 'hidden';
    }

    function fermer() {
        lightbox.classList.remove('ouverte');
        document.body.style.overflow = '';
    }

    function photoSuivante() {
        indexActuel = (indexActuel + 1) % photosGalerie.length;
        imageLightbox.src = photosGalerie[indexActuel];
    }

    function photoPrecedente() {
        indexActuel = (indexActuel - 1 + photosGalerie.length) % photosGalerie.length;
        imageLightbox.src = photosGalerie[indexActuel];
    }

    miniatures.forEach(function (miniature) {
        miniature.addEventListener('click', function () {
            const index = parseInt(miniature.dataset.index, 10);
            ouvrir(index);
        });
    });

    boutonFermer.addEventListener('click', fermer);
    boutonSuivant.addEventListener('click', photoSuivante);
    boutonPrecedent.addEventListener('click', photoPrecedente);

    lightbox.addEventListener('click', function (evenement) {
        if (evenement.target === lightbox) fermer();
    });

    document.addEventListener('keydown', function (evenement) {
        if (!lightbox.classList.contains('ouverte')) return;
        if (evenement.key === 'Escape') fermer();
        if (evenement.key === 'ArrowRight') photoSuivante();
        if (evenement.key === 'ArrowLeft') photoPrecedente();
    });
}

/* ============================================================
   4. MUSIQUE D'AMBIANCE
   ============================================================ */
function initMusique() {
    const audio = document.getElementById('musique-fond');
    const bouton = document.getElementById('musique-toggle');
    const icone = document.getElementById('musique-icone');

    if (!audio || !bouton) return;

    let enLecture = false;

    bouton.addEventListener('click', function () {
        if (enLecture) {
            audio.pause();
            icone.classList.remove('bi-volume-up-fill');
            icone.classList.add('bi-volume-mute-fill');
            bouton.classList.remove('en-lecture');
        } else {
            audio.play();
            icone.classList.remove('bi-volume-mute-fill');
            icone.classList.add('bi-volume-up-fill');
            bouton.classList.add('en-lecture');
        }
        enLecture = !enLecture;
    });
}
