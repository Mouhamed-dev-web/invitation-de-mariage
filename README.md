# Wedding Invitation Platform

Plateforme SaaS multi-mariages d'invitations de mariage numériques,
construite en PHP natif (PDO) + MySQL + Bootstrap 5.

Chaque mariage dispose de sa propre invitation publique accessible via
une URL du type `/mariage/moussa-fatou`, et son propriétaire dispose
d'un espace d'administration séparé pour la gérer.

## Stack technique
- PHP 8+ (natif, PDO, sans framework)
- MySQL 5.7+ / MariaDB
- HTML5 / CSS3 / Bootstrap 5 / Bootstrap Icons
- JavaScript natif (aucune librairie JS externe)

## Installation locale (XAMPP)

1. Copier le dossier `wedding-invitation/` dans `htdocs/` de XAMPP.
2. Démarrer Apache et MySQL depuis le panneau XAMPP.
3. Ouvrir `http://localhost/phpmyadmin`, importer `database/wedding.sql`.
4. Vérifier `config/database.php` (par défaut : hôte `localhost`,
   utilisateur `root`, mot de passe vide — valeurs par défaut de XAMPP).
5. Vérifier `BASE_URL` dans `config/config.php` (doit correspondre au
   nom du dossier dans `htdocs`).
6. Ouvrir `http://localhost/wedding-invitation/`.

## Compte de démonstration
- Espace admin : `http://localhost/wedding-invitation/admin/login.php`
- Email : `admin@demo.com`
- Mot de passe : `password123`
- Invitation publique de démo : `http://localhost/wedding-invitation/mariage/moussa-fatou`
  (fonctionne grâce au `.htaccess` — sans URL propre, utiliser
  `mariage/index.php?slug=moussa-fatou`)

## Structure du projet

```
wedding-invitation/
├── index.php              Page d'accueil / vitrine du service
├── rsvp_submit.php         Traitement du formulaire RSVP (public)
├── message_submit.php      Traitement du formulaire livre d'or (public)
├── .htaccess                URLs propres + sécurité de base
├── config/                  Connexion DB + réglages généraux
├── admin/                   Espace d'administration (protégé)
├── includes/                 Fichiers PHP réutilisés partout
├── mariage/                  Page publique d'une invitation
├── assets/                   CSS, JS, images fixes
├── uploads/                  Fichiers envoyés par les admins (photos)
└── database/wedding.sql      Structure complète + données de démo
```

## Sécurité mise en œuvre
- Mots de passe hachés avec `password_hash()` / vérifiés avec `password_verify()`
- Toutes les requêtes SQL utilisent des requêtes préparées PDO (aucune concaténation de données utilisateur)
- Protection XSS : toute donnée affichée passe par `htmlspecialchars()` (fonction `e()`)
- Protection CSRF sur tous les formulaires (public + admin) via jeton de session
- Sessions sécurisées (cookie httponly, samesite, régénération d'ID à la connexion, expiration après inactivité)
- Uploads d'images vérifiés à 3 niveaux (extension, type MIME réel, taille) puis renommés automatiquement
- Isolation stricte multi-mariages : chaque requête admin filtre systématiquement par `mariage_id` de la session
- `.htaccess` : exécution de PHP désactivée dans `/uploads`, accès direct à `/config` bloqué

## Déploiement en production
Voir la section correspondante fournie séparément (nom de domaine,
hébergement cPanel, import de la base, configuration HTTPS, etc.).
