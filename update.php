<?php
require_once 'config.php';
requireAdmin();

// Fehleranzeige aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Maximale Ausführungszeit erhöhen
set_time_limit(300);
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Logging
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/update_' . date('Y-m-d') . '.log';

function writeLog($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

$error = null;
$success = null;
$output = [];

// Update durchführen (POST-Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_update'])) {
    writeLog('═══════════════════════════════════════════════════════');
    writeLog('Update gestartet');
    writeLog('User: ' . $_SESSION['user_email']);

    try {
        $gitDir = __DIR__;
        $zipUrls = [
            'https://github.com/TrofyTT/Stammtisch/archive/main.zip',
            'https://github.com/TrofyTT/Stammtisch/archive/refs/heads/main.zip',
        ];
        $zipFile = $gitDir . '/update_temp.zip';
        $extractDir = $gitDir . '/update_temp';

        writeLog('Lade ZIP von GitHub...');
        $output[] = '📦 Lade neueste Version von GitHub...';

        $zipData = false;
        foreach ($zipUrls as $urlIndex => $testUrl) {
            $urlNum = $urlIndex + 1;
            writeLog("Versuche URL $urlNum: $testUrl");

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $testUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);

                $zipData = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($zipData !== false && $httpCode == 200 && strlen($zipData) > 1000) {
                    writeLog("ZIP erfolgreich heruntergeladen via cURL von URL $urlNum");
                    $output[] = "✅ Download erfolgreich (" . round(strlen($zipData) / 1024, 2) . " KB)";
                    break;
                }
            }
        }

        if ($zipData === false || strlen($zipData) < 1000) {
            throw new Exception('Download fehlgeschlagen. Bitte versuche es später erneut.');
        }

        // ZIP speichern
        writeLog('Speichere ZIP-Datei...');
        file_put_contents($zipFile, $zipData);
        $output[] = '💾 ZIP-Datei gespeichert';

        // ZIP entpacken
        writeLog('Entpacke ZIP...');
        $zip = new ZipArchive;
        $zipResult = $zip->open($zipFile);

        if ($zipResult === TRUE) {
            $zip->extractTo($extractDir);
            $zip->close();
            writeLog('ZIP erfolgreich entpackt');
            $output[] = '📂 Dateien entpackt';

            // Finde den Stammtisch-Ordner
            $sourceDir = null;
            $items = scandir($extractDir);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($extractDir . '/' . $item)) {
                    $sourceDir = $extractDir . '/' . $item;
                    break;
                }
            }

            if ($sourceDir) {
                writeLog("Gefunden: $sourceDir");

                // Erstelle Backup
                $backupDir = __DIR__ . '/backup_' . date('Y-m-d_H-i-s');
                writeLog("Erstelle Backup: $backupDir");
                $output[] = '💾 Erstelle Backup...';

                // Backup wichtiger Dateien
                $filesToBackup = ['config.php', 'config.local.php', '.htaccess', '.user.ini'];
                @mkdir($backupDir, 0755, true);

                foreach ($filesToBackup as $file) {
                    if (file_exists(__DIR__ . '/' . $file)) {
                        @copy(__DIR__ . '/' . $file, $backupDir . '/' . $file);
                        writeLog("Backup: $file");
                    }
                }

                // Backup uploads/ Ordner
                if (is_dir(__DIR__ . '/uploads')) {
                    @mkdir($backupDir . '/uploads', 0755, true);
                    // Kopiere nur Struktur, nicht die Dateien (zu groß)
                    writeLog("Backup: uploads/ Struktur");
                }

                $output[] = '✅ Backup erstellt';

                // Dateien kopieren (OHNE config.php, uploads/, logs/)
                $skipDirs = ['uploads', 'logs', 'backup_', 'install_temp', 'update_temp'];
                $skipFiles = ['config.php', 'config.local.php'];

                $updatedCount = 0;
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );

                foreach ($iterator as $item) {
                    $sourcePath = $item->getPathname();
                    $relativePath = substr($sourcePath, strlen($sourceDir) + 1);

                    // Skip bestimmte Verzeichnisse und Dateien
                    $skip = false;
                    foreach ($skipDirs as $skipDir) {
                        if (strpos($relativePath, $skipDir) === 0) {
                            $skip = true;
                            break;
                        }
                    }

                    if ($skip) continue;

                    foreach ($skipFiles as $skipFile) {
                        if ($relativePath === $skipFile || basename($relativePath) === $skipFile) {
                            $skip = true;
                            break;
                        }
                    }

                    if ($skip) continue;

                    $targetPath = $gitDir . '/' . $relativePath;

                    if ($item->isDir()) {
                        if (!is_dir($targetPath)) {
                            @mkdir($targetPath, 0755, true);
                        }
                    } else {
                        @copy($sourcePath, $targetPath);
                        $updatedCount++;
                    }
                }

                writeLog("$updatedCount Dateien aktualisiert");
                $output[] = "✅ $updatedCount Dateien aktualisiert";

                // Aufräumen
                writeLog('Räume temporäre Dateien auf...');
                @unlink($zipFile);

                // Lösche Temp-Verzeichnis rekursiv
                function deleteDirectory($dir) {
                    if (!is_dir($dir)) return;
                    $items = scandir($dir);
                    foreach ($items as $item) {
                        if ($item === '.' || $item === '..') continue;
                        $path = $dir . '/' . $item;
                        if (is_dir($path)) {
                            deleteDirectory($path);
                        } else {
                            @unlink($path);
                        }
                    }
                    @rmdir($dir);
                }

                deleteDirectory($extractDir);
                writeLog('Aufräumen abgeschlossen');
                $output[] = '🧹 Temporäre Dateien gelöscht';

                writeLog('═══════════════════════════════════════════════════════');
                writeLog('Update erfolgreich abgeschlossen!');
                writeLog("Backup: $backupDir");
                writeLog('═══════════════════════════════════════════════════════');

                $success = 'Update erfolgreich! ' . $updatedCount . ' Dateien wurden aktualisiert.';
                $output[] = '';
                $output[] = '✅ Update abgeschlossen!';
                $output[] = "📦 Backup: $backupDir";

            } else {
                throw new Exception('Konnte extrahierten Ordner nicht finden.');
            }
        } else {
            throw new Exception('Konnte ZIP nicht öffnen.');
        }

    } catch (Exception $e) {
        writeLog('FEHLER: ' . $e->getMessage());
        $error = 'Update fehlgeschlagen: ' . $e->getMessage();
        $output[] = '❌ Fehler: ' . $e->getMessage();

        // Aufräumen bei Fehler
        if (isset($zipFile) && file_exists($zipFile)) @unlink($zipFile);
        if (isset($extractDir) && is_dir($extractDir)) {
            function deleteDirectory($dir) {
                if (!is_dir($dir)) return;
                $items = scandir($dir);
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $path = $dir . '/' . $item;
                    if (is_dir($path)) {
                        deleteDirectory($path);
                    } else {
                        @unlink($path);
                    }
                }
                @rmdir($dir);
            }
            deleteDirectory($extractDir);
        }
    }
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update - Stammtisch</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .update-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: #2a2a2a;
            border-radius: 16px;
        }
        .update-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .update-header h1 {
            color: #007AFF;
            margin-bottom: 10px;
        }
        .update-warning {
            background: #ff9500;
            color: #000;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .update-warning h3 {
            margin-top: 0;
        }
        .update-info {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }
        .update-info h3 {
            color: #007AFF;
            margin-top: 0;
        }
        .update-info ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        .update-info li {
            margin: 8px 0;
            color: #cccccc;
        }
        .update-output {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .update-output pre {
            margin: 0;
            color: #cccccc;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .btn-update {
            background: #007AFF;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
        }
        .btn-update:hover {
            background: #0051D5;
        }
        .btn-back {
            background: #666;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-back:hover {
            background: #888;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">STAMMTISCH - UPDATE</h1>
            <div class="nav-right">
                <?php
                $user_avatar = $user['avatar'] ?? null;
                $avatar_url = $user_avatar ? 'uploads/avatars/' . htmlspecialchars($user_avatar) : 'assets/img/default-avatar.svg';
                ?>
                <img src="<?= $avatar_url ?>" alt="<?= htmlspecialchars($_SESSION['user_name']) ?>" class="nav-avatar">
            </div>
        </div>
    </nav>

    <div class="update-container">
        <div class="update-header">
            <h1>🔄 System Update</h1>
            <p>Aktualisiere die Stammtisch App auf die neueste Version</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>❌ Update fehlgeschlagen!</strong><br>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <strong>✅ <?= htmlspecialchars($success) ?></strong>
            </div>

            <script>
                // Nach erfolreichem Update Seite neu laden
                setTimeout(() => {
                    if (confirm('Update erfolgreich! Seite neu laden?')) {
                        window.location.href = 'admin.php';
                    }
                }, 2000);
            </script>
        <?php endif; ?>

        <?php if (!empty($output)): ?>
            <div class="update-output">
                <h3>📋 Update-Log:</h3>
                <pre><?= htmlspecialchars(implode("\n", $output)) ?></pre>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <div class="update-warning">
                <h3>⚠️ Wichtige Hinweise:</h3>
                <ul>
                    <li>Das Update lädt die neueste Version von GitHub</li>
                    <li>Es wird automatisch ein Backup erstellt</li>
                    <li>Deine config.php bleibt unverändert</li>
                    <li>Uploads bleiben erhalten</li>
                    <li>Der Update-Vorgang kann 30-60 Sekunden dauern</li>
                </ul>
            </div>

            <div class="update-info">
                <h3>📦 Was wird aktualisiert:</h3>
                <ul>
                    <li>✅ Alle PHP-Dateien</li>
                    <li>✅ CSS & JavaScript</li>
                    <li>✅ HTML-Templates</li>
                    <li>✅ .htaccess & .user.ini</li>
                </ul>

                <h3 style="margin-top: 20px;">🔒 Was bleibt unverändert:</h3>
                <ul>
                    <li>✅ config.php (Datenbank-Zugangsdaten)</li>
                    <li>✅ uploads/ (Avatare & Dateien)</li>
                    <li>✅ logs/ (Log-Dateien)</li>
                </ul>
            </div>

            <form method="POST" onsubmit="return confirm('Wirklich Update durchführen?');">
                <input type="hidden" name="confirm_update" value="1">
                <button type="submit" class="btn-update">
                    🚀 Jetzt Update starten
                </button>
            </form>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="admin.php" class="btn-back">← Zurück zum Admin</a>
        </div>
    </div>

    <script src="assets/js/nav.js"></script>
</body>
</html>
