-- Run this in phpMyAdmin > SQL tab
CREATE DATABASE IF NOT EXISTS `vote` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vote`;

CREATE TABLE IF NOT EXISTS `etudiants` (
    `id_etudiant`  INT NOT NULL AUTO_INCREMENT,
    `nom`          VARCHAR(50) NOT NULL,
    `prenom`       VARCHAR(50) NOT NULL,
    `email`        VARCHAR(100) NOT NULL UNIQUE,
    `mot_de_passe` VARCHAR(255) NOT NULL,
    `a_vote`       TINYINT(1) NOT NULL DEFAULT 0,
    `role`         VARCHAR(10) NOT NULL DEFAULT 'user',
    PRIMARY KEY (`id_etudiant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `candidats` (
    `id_candidat` INT NOT NULL AUTO_INCREMENT,
    `nom`         VARCHAR(50) NOT NULL,
    `prenom`      VARCHAR(50) NOT NULL,
    `programme`   TEXT NOT NULL,
    `photo`       VARCHAR(255) NOT NULL DEFAULT 'images/candidats/default.png',
    PRIMARY KEY (`id_candidat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `votes` (
    `id_vote`     INT NOT NULL AUTO_INCREMENT,
    `id_etudiant` INT NOT NULL UNIQUE,
    `id_candidat` INT NOT NULL,
    `date_vote`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_vote`),
    CONSTRAINT `fk_vote_etudiant` FOREIGN KEY (`id_etudiant`) REFERENCES `etudiants`(`id_etudiant`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vote_candidat` FOREIGN KEY (`id_candidat`) REFERENCES `candidats`(`id_candidat`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `password_resets` (
    `id`         INT NOT NULL AUTO_INCREMENT,
    `email`      VARCHAR(100) NOT NULL,
    `otp_code`   VARCHAR(10) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `verified`   TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin  (password: Admin@123)
INSERT IGNORE INTO `etudiants` (`nom`,`prenom`,`email`,`mot_de_passe`,`role`)
VALUES ('Admin','System','admin@vote.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','admin');

-- Sample candidates
INSERT IGNORE INTO `candidats` (`nom`,`prenom`,`programme`,`photo`) VALUES
('Diallo','Amina',
 'I am committed to improving digital infrastructure, ensuring equitable access to educational resources, and creating an inclusive learning environment for all students.',
 'images/candidats/candidate1.jpg'),
('Ndiaye','Ibrahima',
 'My program focuses on strengthening student solidarity, building a peer mentoring network, and negotiating partnerships with top companies for internship opportunities.',
 'images/candidats/candidate2.jpg'),
('Sow','Mariama',
 'I will modernize our campus spaces, organize monthly cultural events, and fight for student rights with full transparency and integrity every step of the way.',
 'images/candidats/candidate3.jpg');
