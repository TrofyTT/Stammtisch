<?php
// Installationsseite für Stammtisch App

// Prüfe ob bereits installiert
$configFile = __DIR__ . '/config.php';
$installed = false;
$configExists = file_exists($configFile);

if ($configExists) {
    require_once $configFile;
    try {
        $db = getDB();
        // Prüfe ob Tabellen existieren
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        $installed = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        // Config existiert, aber DB nicht erreichbar
        $installed = false;
    }
}

$step = $_GET['step'] ?? ($installed ? 'update' : 'install');
$error = null;
$success = null;

// POST-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'install') {
        // Installation durchführen
        $db_host = $_POST['db_host'] ?? '127.0.0.1';
        $db_port = $_POST['db_port'] ?? '3307';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        $admin_email = $_POST['admin_email'] ?? 'admin@stammtisch.de';
        $admin_password = $_POST['admin_password'] ?? '';
        
        if (empty($db_name) || empty($db_user) || empty($admin_password)) {
            $error = 'Bitte fülle alle Pflichtfelder aus.';
        } else {
            try {
                // Teste DB-Verbindung
                $dsn = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $testDb = new PDO($dsn, $db_user, $db_pass, $options);
                
                // Erstelle Datenbank falls nicht vorhanden
                $testDb->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $testDb->exec("USE `$db_name`");
                
                // Lade SQL-Datei
                $sqlFile = __DIR__ . '/database_complete.sql';
                if (!file_exists($sqlFile)) {
                    throw new Exception('SQL-Datei nicht gefunden: database_complete.sql');
                }
                
                $sql = file_get_contents($sqlFile);
                // Ersetze USE statement
                $sql = str_replace('USE kdph7973_pimmel;', "USE `$db_name`;", $sql);
                $sql = str_replace('CREATE DATABASE IF NOT EXISTS kdph7973_pimmel', "CREATE DATABASE IF NOT EXISTS `$db_name`", $sql);
                
                // Führe SQL aus
                $testDb->exec($sql);
                
                // Erstelle config.php
                $configContent = <<<PHP
<?php
// Datenbankkonfiguration
// Automatische Erkennung: Lokal vs. Produktion
if (file_exists(__DIR__ . '/config.local.php')) {
    // Lokale Entwicklung
    require_once __DIR__ . '/config.local.php';
} else {
    // Produktion
    define('DB_HOST', '{$db_host}');
    define('DB_PORT', '{$db_port}');
    define('DB_NAME', '{$db_name}');
    define('DB_USER', '{$db_user}');
    define('DB_PASS', '{$db_pass}');
}

// Session-Einstellungen
session_start();

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Datenbankverbindung
function getDB() {
    static \$pdo = null;
    if (\$pdo === null) {
        try {
            \$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            \$options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, \$options);
            
            // Explizit UTF-8 setzen
            \$pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
            \$pdo->exec("SET CHARACTER SET utf8mb4");
            
        } catch (PDOException \$e) {
            die("Datenbankverbindung fehlgeschlagen: " . \$e->getMessage());
        }
    }
    return \$pdo;
}

// Authentifizierung prüfen
function requireAuth() {
    if (!isset(\$_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

// Admin-Rechte prüfen
function requireAdmin() {
    requireAuth();
    if (!isset(\$_SESSION['is_admin']) || !\$_SESSION['is_admin']) {
        header('Location: dashboard.php');
        exit;
    }
}

// Aktuellen Benutzer abrufen
function getCurrentUser() {
    if (!isset(\$_SESSION['user_id'])) {
        return null;
    }
    \$db = getDB();
    \$stmt = \$db->prepare("SELECT * FROM users WHERE id = ?");
    \$stmt->execute([\$_SESSION['user_id']]);
    return \$stmt->fetch();
}
PHP;
                
                file_put_contents($configFile, $configContent);
                
                // Admin-Passwort ändern
                require_once $configFile;
                $db = getDB();
                $passwordHash = password_hash($admin_password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET email = ?, password_hash = ? WHERE is_admin = 1 LIMIT 1");
                $stmt->execute([$admin_email, $passwordHash]);
                
                // Erstelle uploads-Ordner
                $uploadsDir = __DIR__ . '/uploads/avatars';
                if (!is_dir($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }
                
                $success = 'Installation erfolgreich!';
                $installed = true;
                $step = 'update';
                
            } catch (Exception $e) {
                $error = 'Fehler: ' . $e->getMessage();
            }
        }
    } elseif ($step === 'update') {
        // Git Update durchführen
        $output = [];
        $returnCode = 0;
        $gitDir = __DIR__;
        
        // Prüfe ob Git-Repository vorhanden
        if (!is_dir($gitDir . '/.git')) {
            $error = 'Git-Repository nicht gefunden. Bitte erst manuell klonen: git clone https://github.com/TrofyTT/Stammtisch.git .';
        } else {
            // Git Pull
            exec("cd " . escapeshellarg($gitDir) . " && git pull origin main 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                $success = 'Update erfolgreich!';
            } else {
                $error = 'Update fehlgeschlagen: ' . implode("\n", $output);
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Stammtisch App</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .install-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
        }
        .install-step {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--bg-primary);
            color: var(--text-primary);
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn-install {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-install:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: rgba(52, 199, 89, 0.2);
            border: 1px solid #34C759;
            color: #34C759;
        }
        .alert-error {
            background: rgba(255, 59, 48, 0.2);
            border: 1px solid #FF3B30;
            color: #FF3B30;
        }
        .alert-info {
            background: rgba(0, 122, 255, 0.2);
            border: 1px solid #007AFF;
            color: #007AFF;
        }
        .step-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-step">
            <div class="step-header">
                <div class="step-number"><?= $installed ? '✅' : '1' ?></div>
                <h1>Stammtisch App Installation</h1>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if (!$installed && $step === 'install'): ?>
                <form method="POST">
                    <h2>Datenbank-Konfiguration</h2>
                    
                    <div class="form-group">
                        <label>Datenbank-Host *</label>
                        <input type="text" name="db_host" value="127.0.0.1" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Port *</label>
                        <input type="text" name="db_port" value="3307" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Name *</label>
                        <input type="text" name="db_name" placeholder="z.B. kdph7973_pimmel" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Benutzer *</label>
                        <input type="text" name="db_user" placeholder="z.B. kdph7973_pimmel" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Passwort *</label>
                        <input type="password" name="db_pass" required>
                    </div>
                    
                    <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border);">
                    
                    <h2>Admin-Account</h2>
                    
                    <div class="form-group">
                        <label>Admin-E-Mail *</label>
                        <input type="email" name="admin_email" value="admin@stammtisch.de" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Admin-Passwort *</label>
                        <input type="password" name="admin_password" placeholder="Sicheres Passwort" required>
                    </div>
                    
                    <button type="submit" class="btn-install">🚀 Installation starten</button>
                </form>
                
            <?php elseif ($installed && $step === 'update'): ?>
                <div class="alert alert-success">
                    ✅ <strong>App ist bereits installiert!</strong>
                </div>
                
                <h2>🔄 Update von Git</h2>
                <p>Lade die neuesten Änderungen vom GitHub-Repository herunter.</p>
                
                <form method="POST">
                    <button type="submit" class="btn-install">🔄 Update von GitHub laden</button>
                </form>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border);">
                    <p style="color: var(--text-secondary); font-size: 14px;">
                        💡 <strong>Tipp:</strong> Nach dem Update kannst du auch direkt zum 
                        <a href="admin.php" style="color: var(--primary);">Admin-Panel</a> gehen und dort Updates durchführen.
                    </p>
                    <p style="margin-top: 15px;">
                        <a href="index.php" class="btn-install" style="text-decoration: none; display: block; text-align: center;">
                            → Zur Anmeldung
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($step === 'update' && isset($output) && !empty($output)): ?>
            <div class="install-step">
                <h3>Git-Output:</h3>
                <pre style="background: var(--bg-primary); padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 12px;"><?= htmlspecialchars(implode("\n", $output)) ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

