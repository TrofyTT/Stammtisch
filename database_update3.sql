-- Update für 6 Nimmt Spiel
USE kdph7973_pimmel;

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

