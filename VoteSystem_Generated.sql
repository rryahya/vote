-- ============================================================
--  VoteSystem — SQL généré depuis PowerAMC MPD
--  SGBD    : MySQL 8.0
--  Auteur  : VoteApp
--  Date    : 2026-03-26
--  Modèle  : VoteSystem_MPD.pdm
-- ============================================================

-- ── Création de la base ─────────────────────────────────────
CREATE DATABASE IF NOT EXISTS `vote`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `vote`;

-- ── Désactiver les FK le temps de créer les tables ──────────
SET FOREIGN_KEY_CHECKS = 0;


-- ============================================================
--  TABLE : etudiants
--  Entité source MCD : ETUDIANT
-- ============================================================
CREATE TABLE IF NOT EXISTS `etudiants` (
    `id_etudiant`  INT          NOT NULL AUTO_INCREMENT  COMMENT 'PK - Auto-incrémenté',
    `nom`          VARCHAR(50)  NOT NULL                 COMMENT 'Nom de famille',
    `prenom`       VARCHAR(50)  NOT NULL                 COMMENT 'Prénom',
    `email`        VARCHAR(100) NOT NULL                 COMMENT 'Email unique - identifiant de connexion',
    `mot_de_passe` VARCHAR(255) NOT NULL                 COMMENT 'Hash bcrypt',
    `a_vote`       TINYINT(1)   NOT NULL DEFAULT 0       COMMENT '0=pas encore voté | 1=a voté',
    `role`         VARCHAR(10)  NOT NULL DEFAULT 'user'  COMMENT 'user ou admin',
    CONSTRAINT `PK_etudiants`        PRIMARY KEY (`id_etudiant`),
    CONSTRAINT `UQ_etudiants_email`  UNIQUE      (`email`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COMMENT='Table des étudiants inscrits sur la plateforme';


-- ============================================================
--  TABLE : candidats
--  Entité source MCD : CANDIDAT
-- ============================================================
CREATE TABLE IF NOT EXISTS `candidats` (
    `id_candidat` INT          NOT NULL AUTO_INCREMENT  COMMENT 'PK - Auto-incrémenté',
    `nom`         VARCHAR(50)  NOT NULL                 COMMENT 'Nom de famille',
    `prenom`      VARCHAR(50)  NOT NULL                 COMMENT 'Prénom',
    `programme`   TEXT         NOT NULL                 COMMENT 'Programme électoral',
    `photo`       VARCHAR(255) NOT NULL
                  DEFAULT 'images/candidats/default.png' COMMENT 'Chemin vers la photo',
    CONSTRAINT `PK_candidats` PRIMARY KEY (`id_candidat`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COMMENT='Table des candidats aux élections';


-- ============================================================
--  TABLE : votes
--  Issue de l'association VOTER du MCD
--  VOTER (ETUDIANT 0,1 --- 0,N CANDIDAT) + attribut date_vote
-- ============================================================
CREATE TABLE IF NOT EXISTS `votes` (
    `id_vote`     INT      NOT NULL AUTO_INCREMENT  COMMENT 'PK',
    `id_etudiant` INT      NOT NULL                 COMMENT 'FK → etudiants.id_etudiant (UNIQUE : 1 vote/étudiant)',
    `id_candidat` INT      NOT NULL                 COMMENT 'FK → candidats.id_candidat',
    `date_vote`   DATETIME NOT NULL
                  DEFAULT CURRENT_TIMESTAMP         COMMENT 'Horodatage automatique',
    CONSTRAINT `PK_votes`            PRIMARY KEY  (`id_vote`),
    CONSTRAINT `UQ_votes_etudiant`   UNIQUE       (`id_etudiant`),  -- 1 vote max par étudiant
    CONSTRAINT `FK_votes_etudiant`   FOREIGN KEY  (`id_etudiant`)
        REFERENCES `etudiants` (`id_etudiant`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    CONSTRAINT `FK_votes_candidat`   FOREIGN KEY  (`id_candidat`)
        REFERENCES `candidats` (`id_candidat`)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COMMENT='Votes - issue de l association VOTER du MCD';


-- ============================================================
--  TABLE : password_resets
--  Entité source MCD : REINITIALISATION_MDP
--  Association DEMANDER (ETUDIANT 0,N --- 1,1 REINIT)
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT          NOT NULL AUTO_INCREMENT  COMMENT 'PK',
    `email`      VARCHAR(100) NOT NULL                 COMMENT 'Email de l étudiant',
    `otp_code`   VARCHAR(10)  NOT NULL                 COMMENT 'Code OTP 4 chiffres',
    `expires_at` DATETIME     NOT NULL                 COMMENT 'Expiration (10 min)',
    `verified`   TINYINT(1)   NOT NULL DEFAULT 0       COMMENT '0=non vérifié | 1=utilisé',
    CONSTRAINT `PK_password_resets` PRIMARY KEY (`id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COMMENT='Réinitialisations MDP par OTP';


-- ── Réactiver les FK ────────────────────────────────────────
SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
--  DONNÉES INITIALES
-- ============================================================

-- Compte admin par défaut (mot de passe : Admin@123)
INSERT IGNORE INTO `etudiants`
    (`nom`, `prenom`, `email`, `mot_de_passe`, `role`)
VALUES
    ('Admin', 'System', 'admin@vote.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'admin');

-- Candidats exemples
INSERT IGNORE INTO `candidats` (`nom`, `prenom`, `programme`, `photo`) VALUES
('Diallo',  'Amina',
 'Je m''engage à améliorer les infrastructures numériques, garantir l''accès équitable aux ressources pédagogiques et créer un environnement inclusif pour tous les étudiants.',
 'images/candidats/candidate1.jpg'),
('Ndiaye',  'Ibrahima',
 'Mon programme vise à renforcer la solidarité étudiante, mettre en place un tutorat entre pairs et négocier des partenariats avec les entreprises locales pour l''insertion professionnelle.',
 'images/candidats/candidate2.jpg'),
('Sow',     'Mariama',
 'Je propose de moderniser les espaces de travail collaboratif, organiser des événements culturels mensuels et défendre les droits des étudiants avec transparence et intégrité.',
 'images/candidats/candidate3.jpg');


-- ============================================================
--  REQUÊTES UTILES (commentées)
-- ============================================================

-- Résultats des votes par candidat :
-- SELECT c.nom, c.prenom, COUNT(v.id_vote) AS total_votes
-- FROM candidats c
-- LEFT JOIN votes v ON c.id_candidat = v.id_candidat
-- GROUP BY c.id_candidat
-- ORDER BY total_votes DESC;

-- Vérifier si un étudiant a voté :
-- SELECT a_vote FROM etudiants WHERE id_etudiant = ?;

-- Lister les votants avec leur choix :
-- SELECT e.prenom, e.nom, e.email,
--        c.prenom AS cand_prenom, c.nom AS cand_nom,
--        v.date_vote
-- FROM votes v
-- JOIN etudiants e ON v.id_etudiant = e.id_etudiant
-- JOIN candidats c ON v.id_candidat = c.id_candidat
-- ORDER BY v.date_vote DESC;

-- Réinitialiser tous les votes (admin) :
-- DELETE FROM votes;
-- UPDATE etudiants SET a_vote = 0;
