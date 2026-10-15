<!--
    navbar.php
    -----------
    Menu de navigation fixe en haut de la page publique.
    Devient un menu "hamburger" sur mobile grâce à Bootstrap.
-->
<nav class="navbar navbar-expand-lg fixed-top navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="#hero">
            <?= e($mariage['nom_marie']) ?> &amp; <?= e($mariage['nom_mariee']) ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#notre-histoire">Notre histoire</a></li>
                <li class="nav-item"><a class="nav-link" href="#ceremonie">Cérémonie</a></li>
                <li class="nav-item"><a class="nav-link" href="#programme">Programme</a></li>
                <li class="nav-item"><a class="nav-link" href="#galerie">Galerie</a></li>
                <li class="nav-item"><a class="nav-link" href="#rsvp">RSVP</a></li>
                <li class="nav-item"><a class="nav-link" href="#livre-or">Livre d'or</a></li>
            </ul>
        </div>
    </div>
</nav>
