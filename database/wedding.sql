-- ============================================================
-- WEDDING INVITATION PLATFORM - Structure de la base de données
-- ============================================================

CREATE DATABASE IF NOT EXISTS wedding_invitation
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE wedding_invitation;

DROP TABLE IF EXISTS messages_livre_or;
DROP TABLE IF EXISTS programme;
DROP TABLE IF EXISTS photos;
DROP TABLE IF EXISTS invites;
DROP TABLE IF EXISTS mariages;
DROP TABLE IF EXISTS users;

-- ============================================================
-- TABLE 1 : users (comptes administrateurs)
-- ============================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE 2 : mariages
-- ============================================================
CREATE TABLE mariages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    nom_marie VARCHAR(100) NOT NULL,
    nom_mariee VARCHAR(100) NOT NULL,
    date_mariage DATE NOT NULL,
    heure_mariage TIME,
    lieu VARCHAR(200),
    adresse VARCHAR(255),
    description TEXT,
    photo_principale VARCHAR(255),
    lien_google_maps VARCHAR(500),
    couleur_principale VARCHAR(20) DEFAULT '#000000',
    couleur_secondaire VARCHAR(20) DEFAULT '#D4AF37',
    theme VARCHAR(50) DEFAULT 'noir-dore',
    musique_fichier VARCHAR(255),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TABLE 3 : invites
-- ============================================================
CREATE TABLE invites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mariage_id INT NOT NULL,
    nom_complet VARCHAR(150) NOT NULL,
    telephone VARCHAR(20),
    nombre_personnes INT NOT NULL DEFAULT 1,
    presence ENUM('oui', 'non', 'en_attente') NOT NULL DEFAULT 'en_attente',
    date_confirmation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mariage_id) REFERENCES mariages(id) ON DELETE CASCADE,
    INDEX idx_mariage_presence (mariage_id, presence)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE 4 : photos
-- ============================================================
CREATE TABLE photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mariage_id INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    est_principale BOOLEAN DEFAULT FALSE,
    ordre INT DEFAULT 0,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mariage_id) REFERENCES mariages(id) ON DELETE CASCADE,
    INDEX idx_mariage (mariage_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE 5 : programme
-- ============================================================
CREATE TABLE programme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mariage_id INT NOT NULL,
    titre VARCHAR(100) NOT NULL,
    description TEXT,
    heure TIME NOT NULL,
    lieu VARCHAR(200),
    icone VARCHAR(50) DEFAULT 'bi-calendar-event',
    ordre INT DEFAULT 0,
    FOREIGN KEY (mariage_id) REFERENCES mariages(id) ON DELETE CASCADE,
    INDEX idx_mariage (mariage_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE 6 : messages_livre_or
-- ============================================================
CREATE TABLE messages_livre_or (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mariage_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    valide BOOLEAN DEFAULT FALSE,
    date_ajout DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mariage_id) REFERENCES mariages(id) ON DELETE CASCADE,
    INDEX idx_mariage_valide (mariage_id, valide)
) ENGINE=InnoDB;

-- ============================================================
-- DONNÉES DE DÉMONSTRATION
-- ============================================================

-- Compte admin de démo : admin@demo.com / mot de passe : password123
INSERT INTO users (nom, email, mot_de_passe) VALUES
('Moussa Diop', 'admin@demo.com', '$2b$10$EoZ30/DY5NQ5U9hR6gLsDeZeA1kB9b6PBGGXaFKVnQMTSi5BpTUru');

INSERT INTO mariages
(user_id, slug, nom_marie, nom_mariee, date_mariage, heure_mariage, lieu, adresse, description, lien_google_maps, couleur_principale, couleur_secondaire, theme)
VALUES
(1, 'moussa-fatou', 'Moussa', 'Fatou', '2026-12-25', '16:00:00',
 'Salle des Fêtes de Dakar', 'Route de Ouakam, Dakar, Sénégal',
 'Après 5 belles années ensemble, Moussa et Fatou ont dit oui ! Rejoignez-nous pour célébrer leur union dans la joie et la bonne humeur.',
 'https://maps.google.com/?q=Salle+des+Fêtes+Dakar',
 '#000000', '#D4AF37', 'noir-dore');

INSERT INTO invites (mariage_id, nom_complet, telephone, nombre_personnes, presence) VALUES
(1, 'Ibrahima Ndiaye', '221771234567', 2, 'oui'),
(1, 'Aïssatou Ba', '221772345678', 1, 'oui'),
(1, 'Cheikh Fall', '221773456789', 4, 'en_attente'),
(1, 'Khadija Sow', '221774567890', 1, 'non');

INSERT INTO programme (mariage_id, titre, description, heure, lieu, icone, ordre) VALUES
(1, 'Cérémonie religieuse', 'Bénédiction du mariage', '16:00:00', 'Mosquée de Ouakam', 'bi-book', 1),
(1, 'Cocktail', 'Moment convivial de retrouvailles', '18:00:00', 'Jardin de la salle', 'bi-cup-straw', 2),
(1, 'Dîner', 'Repas de fête', '20:00:00', 'Salle des Fêtes de Dakar', 'bi-egg-fried', 3);

INSERT INTO messages_livre_or (mariage_id, nom, message, valide) VALUES
(1, 'Awa Diallo', 'Tous mes vœux de bonheur à ce magnifique couple !', TRUE);
