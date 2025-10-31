-- Update für Achievements
USE kdph7973_pimmel;

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

-- Standard-Achievements einfügen
INSERT INTO achievement_types (name, description, condition_type, condition_value, icon) VALUES
('Er frisst alles', '60 Punkte in einem Spiel erreicht', 'game_points', '60', '🍽️'),
('Streber', 'Erster in der Anwesenheit bei Stammtischen', 'attendance_first', NULL, '📚'),
('Verlorenes Kind', 'Nie bei einem Stammtisch dabei gewesen', 'attendance_never', NULL, '👻');

