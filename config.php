<?php
// Datenbankkonfiguration
// Automatische Erkennung: Lokal vs. Produktion
if (file_exists(__DIR__ . '/config.local.php')) {
    // Lokale Entwicklung
    require_once __DIR__ . '/config.local.php';
} else {
    // Produktion - Credentials werden bei der Installation gesetzt
    // Falls keine config.local.php existiert, werden die Werte aus install.php verwendet
    // Oder du erstellst manuell eine config.local.php mit den echten Credentials
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3307');
    define('DB_NAME', ''); // Wird bei Installation gesetzt
    define('DB_USER', ''); // Wird bei Installation gesetzt
    define('DB_PASS', ''); // Wird bei Installation gesetzt
}

// Session-Einstellungen
session_start();

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Datenbankverbindung
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Explizit UTF-8 setzen
            $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            $pdo->exec("SET CHARACTER SET utf8mb4");
            
        } catch (PDOException $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Authentifizierung prüfen
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Admin-Rechte prüfen
function requireAdmin() {
    requireAuth();
    if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
        header('Location: dashboard.php');
        exit;
    }
}

// Aktuellen Benutzer abrufen
function getCurrentUser() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

