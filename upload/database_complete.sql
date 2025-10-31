-- ============================================
-- Stammtisch App - Komplette Datenbankstruktur
-- ============================================
-- WICHTIG: Ersetze [DEINE_DATENBANK] mit deinem echten Datenbanknamen!
-- Diese Datei sollte nicht ins Git committed werden, wenn sie echte Credentials enthält.

-- CREATE DATABASE IF NOT EXISTS [DEINE_DATENBANK] CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE [DEINE_DATENBANK];

-- ============================================
-- 1. Basis-Tabellen
-- ============================================

-- Benutzer Tabelle
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    avatar VARCHAR(255) NULL,
    rang VARCHAR(100) NULL,
    color VARCHAR(7) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Termine Tabelle
CREATE TABLE IF NOT EXISTS termine (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    ort VARCHAR(255) NOT NULL,
    datum DATE NOT NULL,
    uhrzeit TIME NOT NULL,
    beschreibung TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_datum (datum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Anwesenheiten Tabelle
CREATE TABLE IF NOT EXISTS anwesenheiten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    termin_id INT NOT NULL,
    user_id INT NOT NULL,
    status ENUM('anwesend', 'nicht_anwesend', 'unentschuldigt') NOT NULL,
    notiz TEXT,
    erfasst_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (termin_id) REFERENCES termine(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_anwesenheit (termin_id, user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Einstellungen Tabelle
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Spiel-Tabellen (6 Nimmt!)
-- ============================================

-- Spiele Tabelle
CREATE TABLE IF NOT EXISTS spiele (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    datum DATE NOT NULL,
    status ENUM('aktiv', 'beendet') DEFAULT 'aktiv',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_datum (datum),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Spieler-Teilnahme Tabelle (Reihenfolge!)
CREATE TABLE IF NOT EXISTS spiel_teilnahme (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spiel_id INT NOT NULL,
    user_id INT NOT NULL,
    reihenfolge INT NOT NULL,
    FOREIGN KEY (spiel_id) REFERENCES spiele(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_spiel_user (spiel_id, user_id),
    INDEX idx_reihenfolge (spiel_id, reihenfolge)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Spiel-Runden (Punkte pro Runde)
CREATE TABLE IF NOT EXISTS spiel_runden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    spiel_id INT NOT NULL,
    spiel_teilnahme_id INT NOT NULL,
    runde_nummer INT NOT NULL,
    punkte INT NOT NULL,
    erfasst_am TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (spiel_id) REFERENCES spiele(id) ON DELETE CASCADE,
    FOREIGN KEY (spiel_teilnahme_id) REFERENCES spiel_teilnahme(id) ON DELETE CASCADE,
    UNIQUE KEY unique_runde (spiel_teilnahme_id, runde_nummer),
    INDEX idx_spiel_runde (spiel_id, runde_nummer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. Achievement-System
-- ============================================

CREATE TABLE IF NOT EXISTS achievement_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    condition_type VARCHAR(100) NOT NULL COMMENT 'z.B. game_points, attendance_first, attendance_never',
    condition_value VARCHAR(255) NULL COMMENT 'z.B. 60 für Punkte, oder NULL für boolesche Bedingungen',
    icon VARCHAR(50) DEFAULT '🏆',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    achievement_id INT NOT NULL,
    earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (achievement_id) REFERENCES achievement_types(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. Standard-Daten einfügen
-- ============================================

-- Default Admin erstellen (Passwort: admin123)
-- E-Mail: admin@stammtisch.de
-- Passwort: admin123
INSERT INTO users (email, password_hash, name, is_admin) 
VALUES (
    'admin@stammtisch.de',
    '$2y$12$6OkdiKCi0jOEVaBjF2u8quMVu0rmu8AUgK2gWJlLGLe/IwDb.bSuy',
    'Admin',
    1
) ON DUPLICATE KEY UPDATE email=email;

-- Standard-Achievements einfügen
INSERT INTO achievement_types (name, description, condition_type, condition_value, icon) VALUES
('Er frisst alles', '60 Punkte in einem Spiel erreicht', 'game_points', '60', '🍽️'),
('Streber', 'Erster in der Anwesenheit bei Stammtischen', 'attendance_first', NULL, '📚'),
('Verlorenes Kind', 'Nie bei einem Stammtisch dabei gewesen', 'attendance_never', NULL, '👻')
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- Fertig!
-- ============================================

