-- Stammtisch Datenbank Struktur

CREATE DATABASE IF NOT EXISTS kdph7973_pimmel;
USE kdph7973_pimmel;

-- Benutzer Tabelle
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
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

-- Default Admin erstellen (Passwort: admin123)
-- E-Mail: admin@stammtisch.de
-- Passwort: admin123

INSERT INTO users (email, password_hash, name, is_admin) 
VALUES (
    'admin@stammtisch.de',
    '$2y$12$6OkdiKCi0jOEVaBjF2u8quMVu0rmu8AUgK2gWJlLGLe/IwDb.bSuy', -- Password: admin123
    'Admin',
    1
) ON DUPLICATE KEY UPDATE email=email;

