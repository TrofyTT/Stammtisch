<?php
// Installationsseite für Stammtisch App

// Fehleranzeige aktivieren für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Maximale Ausführungszeit erhöhen für ZIP-Download
set_time_limit(300); // 5 Minuten
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');

// Erstelle logs-Ordner FÜR ERROR HANDLER (muss vor dem Error Handler sein!)
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
    @file_put_contents($logDir . '/.htaccess', "Deny from all\n");
}

// Log-Datei für Error Handler
$logFile = $logDir . '/install_' . date('Y-m-d') . '.log';

// ERROR HANDLER - Fängt ALLE PHP-Fehler ab (inkl. Fatal Errors vor dem eigentlichen Logging)
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $errorType = '';
    switch ($errno) {
        case E_ERROR: $errorType = 'FATAL ERROR'; break;
        case E_WARNING: $errorType = 'WARNING'; break;
        case E_PARSE: $errorType = 'PARSE ERROR'; break;
        case E_NOTICE: $errorType = 'NOTICE'; break;
        case E_CORE_ERROR: $errorType = 'CORE ERROR'; break;
        case E_CORE_WARNING: $errorType = 'CORE WARNING'; break;
        case E_COMPILE_ERROR: $errorType = 'COMPILE ERROR'; break;
        case E_COMPILE_WARNING: $errorType = 'COMPILE WARNING'; break;
        case E_USER_ERROR: $errorType = 'USER ERROR'; break;
        case E_USER_WARNING: $errorType = 'USER WARNING'; break;
        case E_USER_NOTICE: $errorType = 'USER NOTICE'; break;
        default: $errorType = "ERROR ($errno)"; break;
    }
    $logEntry = "[$timestamp] [$errorType] $errstr\n";
    $logEntry .= "[$timestamp] [$errorType] File: $errfile\n";
    $logEntry .= "[$timestamp] [$errorType] Line: $errline\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Zeige auch im Browser (falls möglich)
    if (ini_get('display_errors')) {
        echo "<div style='background: #ff0000; color: white; padding: 20px; margin: 20px; border-radius: 10px;'>";
        echo "<strong>[$errorType]</strong><br>";
        echo "$errstr<br>";
        echo "<small>File: $errfile<br>Line: $errline</small><br>";
        echo "<small>Log: logs/install_" . date('Y-m-d') . ".log</small>";
        echo "</div>";
    }
    
    // Fatal Errors nicht unterdrücken
    if ($errno === E_ERROR || $errno === E_PARSE || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR) {
        return false; // Weiterleiten an Standard PHP Error Handler
    }
    return true; // Fehler behandelt
});

// Shutdown Handler - Fängt Fatal Errors ab, die nicht vom Error Handler gefangen werden
register_shutdown_function(function() use ($logFile) {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [FATAL ERROR - SHUTDOWN] " . $error['message'] . "\n";
        $logEntry .= "[$timestamp] [FATAL ERROR - SHUTDOWN] File: " . $error['file'] . "\n";
        $logEntry .= "[$timestamp] [FATAL ERROR - SHUTDOWN] Line: " . $error['line'] . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
});

// ============================================
// LOGGING-SYSTEM
// ============================================
// Hinweis: $logDir und $logFile wurden bereits oben definiert (für Error Handler)

/**
 * Schreibt eine Nachricht ins Log-File
 */
function writeLog($message, $level = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Schreibt einen Fehler ins Log-File
 */
function writeErrorLog($message, $exception = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [ERROR] $message\n";
    if ($exception) {
        $logEntry .= "[$timestamp] [ERROR] Exception: " . $exception->getMessage() . "\n";
        $logEntry .= "[$timestamp] [ERROR] File: " . $exception->getFile() . "\n";
        $logEntry .= "[$timestamp] [ERROR] Line: " . $exception->getLine() . "\n";
        $logEntry .= "[$timestamp] [ERROR] Trace: " . $exception->getTraceAsString() . "\n";
    }
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Start-Log
writeLog('═══════════════════════════════════════════════════════');
writeLog('Installation gestartet');
writeLog('PHP Version: ' . PHP_VERSION);
writeLog('Server: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'));
writeLog('Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'Unknown'));
writeLog('Request Method: ' . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown'));
writeLog('Working Directory: ' . __DIR__);
writeLog('Script File: ' . __FILE__);
writeLog('Memory Limit: ' . ini_get('memory_limit'));
writeLog('Max Execution Time: ' . ini_get('max_execution_time'));
writeLog('Loaded Extensions: ' . implode(', ', get_loaded_extensions()));
writeLog('═══════════════════════════════════════════════════════');
writeLog('Starte Code-Ausführung...');

// Hilfsfunktion zum Löschen von Verzeichnissen (MUSS ganz am Anfang sein!)
if (!function_exists('deleteDirectory')) {
    function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        
        try {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    deleteDirectory($path);
                } else {
                    @unlink($path);
                }
            }
            @rmdir($dir);
        } catch (Exception $e) {
            writeErrorLog('Fehler beim Löschen von Verzeichnis: ' . $dir, $e);
        }
    }
}

// Prüfe ob bereits installiert
writeLog('Prüfe Installation-Status...');
$configFile = __DIR__ . '/config.php';
$installed = false;
$configExists = file_exists($configFile);
writeLog('Config-Datei existiert: ' . ($configExists ? 'Ja (' . $configFile . ')' : 'Nein'));

if ($configExists) {
    writeLog('Lade config.php...');
    try {
        require_once $configFile;
        writeLog('config.php erfolgreich geladen');
        if (function_exists('getDB')) {
            writeLog('getDB() Funktion verfügbar - teste Datenbankverbindung...');
            try {
                $db = getDB();
                writeLog('Datenbankverbindung erfolgreich');
                // Prüfe ob Tabellen existieren
                $stmt = $db->query("SHOW TABLES LIKE 'users'");
                $installed = $stmt->rowCount() > 0;
                writeLog('Tabelle "users" existiert: ' . ($installed ? 'Ja' : 'Nein'));
            } catch (PDOException $e) {
                writeErrorLog('PDO Fehler bei Datenbankverbindung', $e);
                $installed = false;
            }
        } else {
            writeLog('WARNUNG: getDB() Funktion nicht verfügbar nach require config.php');
        }
    } catch (Exception $e) {
        writeErrorLog('Fehler beim Laden von config.php', $e);
        $installed = false;
    } catch (Error $e) {
        writeErrorLog('PHP Fehler beim Laden von config.php', $e);
        $installed = false;
    }
} else {
    writeLog('Config-Datei nicht gefunden - Installation nötig');
}

writeLog('Bestimme Step...');
$step = $_GET['step'] ?? ($installed ? 'update' : 'install');
writeLog("Step: $step (installed: " . ($installed ? 'true' : 'false') . ")");
$error = null;
$success = null;
$output = [];
writeLog('Variablen initialisiert');

// Prüfe ob Dateien heruntergeladen werden müssen
$needsDownload = !$installed && (
    !file_exists(__DIR__ . '/api.php') || 
    !file_exists(__DIR__ . '/index.php') || 
    !file_exists(__DIR__ . '/assets/css/style.css')
);

// Auto-Download beim ersten Aufruf (wenn Dateien fehlen)
if ($needsDownload && $_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['skip_download']) && !isset($_GET['download'])) {
    // Prüfe PHP-Extensions
    if (!class_exists('ZipArchive')) {
        $error = 'ZipArchive ist nicht verfügbar. Bitte installiere die PHP Zip-Extension auf deinem Server.';
        $needsDownload = false;
    } elseif (!function_exists('curl_init') && !ini_get('allow_url_fopen')) {
        $error = 'Weder cURL noch allow_url_fopen sind verfügbar. Bitte kontaktiere deinen Hoster, um eine dieser Optionen zu aktivieren.';
        $needsDownload = false;
    } else {
    $output = [];
    $output[] = '═══════════════════════════════════════════════════════';
    $output[] = '📦 DOWNLOAD MODUS';
    $output[] = '═══════════════════════════════════════════════════════';
    $output[] = '';
    $output[] = 'Lade alle Dateien von GitHub herunter...';
    $output[] = '';
    
    // Logging
    writeLog('Download-Modus gestartet');
    writeLog('PHP-Version: ' . PHP_VERSION);
    writeLog('ZipArchive: ' . (class_exists('ZipArchive') ? 'Verfügbar' : 'NICHT verfügbar'));
    writeLog('cURL: ' . (function_exists('curl_init') ? 'Verfügbar' : 'NICHT verfügbar'));
    writeLog('allow_url_fopen: ' . (ini_get('allow_url_fopen') ? 'Aktiviert' : 'Deaktiviert'));
    writeLog('Memory Limit: ' . ini_get('memory_limit'));
    writeLog('Max Execution Time: ' . ini_get('max_execution_time') . ' Sekunden');
    
    $output[] = 'PHP-Version: ' . PHP_VERSION;
    $output[] = 'ZipArchive: ' . (class_exists('ZipArchive') ? '✅ Verfügbar' : '❌ Nicht verfügbar');
    $output[] = 'cURL: ' . (function_exists('curl_init') ? '✅ Verfügbar' : '❌ Nicht verfügbar');
    $output[] = 'allow_url_fopen: ' . (ini_get('allow_url_fopen') ? '✅ Aktiviert' : '❌ Deaktiviert');
    $output[] = 'Memory Limit: ' . ini_get('memory_limit');
    $output[] = 'Max Execution Time: ' . ini_get('max_execution_time') . ' Sekunden';
    $output[] = 'Log-Datei: logs/install_' . date('Y-m-d') . '.log';
    $output[] = '';
    
            // Prüfe ob ZipArchive verfügbar ist
            if (!class_exists('ZipArchive')) {
                $error = 'ZipArchive ist nicht verfügbar. Bitte installiere die PHP Zip-Extension auf deinem Server.';
                writeErrorLog('ZipArchive ist nicht verfügbar');
            } elseif (!function_exists('curl_init') && !ini_get('allow_url_fopen')) {
                $error = 'Weder cURL noch allow_url_fopen sind verfügbar. Bitte kontaktiere deinen Hoster, um eine dieser Optionen zu aktivieren.';
                writeErrorLog('Weder cURL noch allow_url_fopen verfügbar');
            } else {
                writeLog('Extensions-Prüfung erfolgreich - Download kann starten');
        try {
            $gitDir = __DIR__;
            $zipUrls = [
                'https://github.com/TrofyTT/Stammtisch/archive/main.zip',
                'https://github.com/TrofyTT/Stammtisch/archive/refs/heads/main.zip',
                'https://codeload.github.com/TrofyTT/Stammtisch/zip/refs/heads/main',
                'https://codeload.github.com/TrofyTT/Stammtisch/zip/main'
            ];
            $zipFile = $gitDir . '/install_temp.zip';
            $extractDir = $gitDir . '/install_temp';
            
            $output[] = 'Lade ZIP von GitHub...';
            $zipData = false;
            
            // Versuche jede URL
            foreach ($zipUrls as $urlIndex => $testUrl) {
                $urlNum = $urlIndex + 1;
                $output[] = "Versuche URL $urlNum/" . count($zipUrls) . "...";
                writeLog("Versuche URL $urlNum/" . count($zipUrls) . ": $testUrl");
                
                // Methode 1: cURL
                if (function_exists('curl_init')) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $testUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
                    
                    $zipData = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($zipData !== false && $httpCode === 200 && substr($zipData, 0, 2) === "PK") {
                        $size = number_format(strlen($zipData) / 1024, 2);
                        $output[] = '✅ ZIP erfolgreich heruntergeladen (' . $size . ' KB)';
                        writeLog("ZIP erfolgreich heruntergeladen via cURL: $size KB von URL $urlNum");
                        break;
                    } else {
                        writeLog("cURL fehlgeschlagen für URL $urlNum: HTTP $httpCode");
                    }
                }
                
                // Methode 2: file_get_contents
                if ($zipData === false && ini_get('allow_url_fopen')) {
                    $context = stream_context_create([
                        'http' => [
                            'method' => 'GET',
                            'header' => ['User-Agent: Mozilla/5.0'],
                            'timeout' => 120,
                            'follow_location' => true
                        ],
                        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
                    ]);
                    
                    $zipData = @file_get_contents($testUrl, false, $context);
                    if ($zipData !== false && strlen($zipData) > 1000 && substr($zipData, 0, 2) === "PK") {
                        $size = number_format(strlen($zipData) / 1024, 2);
                        $output[] = '✅ ZIP erfolgreich heruntergeladen (' . $size . ' KB)';
                        writeLog("ZIP erfolgreich heruntergeladen via file_get_contents: $size KB von URL $urlNum");
                        break;
                    } else {
                        writeLog("file_get_contents fehlgeschlagen für URL $urlNum");
                    }
                }
            }
            
            if ($zipData === false || strlen($zipData) < 1000) {
                writeErrorLog('ZIP-Download von allen URLs fehlgeschlagen');
                throw new Exception('Konnte ZIP nicht von GitHub herunterladen. Bitte kontaktiere deinen Hoster.');
            }
            
            // Speichere ZIP
            writeLog("Speichere ZIP-Datei: $zipFile");
            $bytes = @file_put_contents($zipFile, $zipData);
            if ($bytes === false) {
                writeErrorLog("Konnte ZIP-Datei nicht speichern: $zipFile");
                throw new Exception('Konnte ZIP-Datei nicht speichern. Prüfe Schreibrechte.');
            }
            writeLog("ZIP gespeichert: " . number_format($bytes / 1024, 2) . " KB");
            $output[] = 'ZIP gespeichert';
            
            // Erstelle temporären Extraktions-Ordner
            if (!is_dir($extractDir)) {
                mkdir($extractDir, 0755, true);
            }
            
            // Entpacke ZIP
            writeLog("Entpacke ZIP-Datei nach: $extractDir");
            $zip = new ZipArchive();
            $zipResult = $zip->open($zipFile);
            if ($zipResult === TRUE) {
                $zip->extractTo($extractDir);
                $zip->close();
                writeLog("ZIP erfolgreich entpackt");
                $output[] = 'ZIP entpackt';
                
                // Finde den extrahierten Ordner
                $extractedFolder = null;
                $dirs = scandir($extractDir);
                foreach ($dirs as $dir) {
                    if ($dir !== '.' && $dir !== '..' && is_dir($extractDir . '/' . $dir)) {
                        $extractedFolder = $extractDir . '/' . $dir;
                        break;
                    }
                }
                
                if ($extractedFolder) {
                    $output[] = 'Kopiere Dateien...';
                    
                    // Dateien kopieren (außer install.php, config.php, config.local.php und uploads/)
                    $excludeFiles = ['install.php', 'config.php', 'config.local.php', '.git'];
                    $excludeDirs = ['uploads', '.git'];
                    
                    $filesCopied = 0;
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($extractedFolder, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    
                    foreach ($iterator as $item) {
                        $relativePath = substr($item->getPathname(), strlen($extractedFolder) + 1);
                        $targetPath = $gitDir . '/' . $relativePath;
                        
                        // Überspringe ausgeschlossene Dateien/Ordner
                        $skip = false;
                        foreach ($excludeFiles as $exclude) {
                            if (basename($relativePath) === $exclude || strpos($relativePath, $exclude . '/') === 0) {
                                $skip = true;
                                break;
                            }
                        }
                        foreach ($excludeDirs as $excludeDir) {
                            if (strpos($relativePath, $excludeDir . '/') === 0 || basename($relativePath) === $excludeDir) {
                                $skip = true;
                                break;
                            }
                        }
                        
                        if (!$skip) {
                            if ($item->isDir()) {
                                if (!is_dir($targetPath)) {
                                    mkdir($targetPath, 0755, true);
                                }
                            } else {
                                $targetDir = dirname($targetPath);
                                if (!is_dir($targetDir)) {
                                    mkdir($targetDir, 0755, true);
                                }
                                copy($item->getPathname(), $targetPath);
                                $filesCopied++;
                            }
                        }
                    }
                    
                    $output[] = '✅ ' . $filesCopied . ' Dateien kopiert';
                    writeLog("$filesCopied Dateien erfolgreich kopiert");
                    
                    // Erstelle uploads/avatars/ Ordner
                    if (!is_dir($gitDir . '/uploads/avatars')) {
                        @mkdir($gitDir . '/uploads/avatars', 0755, true);
                        writeLog("uploads/avatars/ Ordner erstellt");
                        $output[] = '✅ uploads/avatars/ Ordner erstellt';
                    }
                    
                    // Erstelle .htaccess für uploads/avatars/
                    $htaccessPath = $gitDir . '/uploads/avatars/.htaccess';
                    $htaccessContent = "<FilesMatch \"\\.(jpg|jpeg|png|gif|svg|webp)$\">\n    Order Allow,Deny\n    Allow from all\n</FilesMatch>\nphp_flag engine off";
                    @file_put_contents($htaccessPath, $htaccessContent);
                    writeLog(".htaccess für uploads/avatars/ erstellt");
                    $output[] = '✅ .htaccess für uploads/avatars/ erstellt';
                    
                    // Aufräumen
                    writeLog("Räume temporäre Dateien auf...");
                    @unlink($zipFile);
                    deleteDirectory($extractDir);
                    writeLog("Aufräumen abgeschlossen");
                    
                    $output[] = '';
                    $output[] = '✅ Alle Dateien erfolgreich heruntergeladen und installiert!';
                    writeLog('═══════════════════════════════════════════════════════');
                    writeLog('Download erfolgreich abgeschlossen!');
                    writeLog('═══════════════════════════════════════════════════════');
                    
                    // Aktualisiere needsDownload und setze Success
                    $needsDownload = false;
                    $success = 'Alle Dateien wurden erfolgreich von GitHub heruntergeladen. Du kannst jetzt mit der Installation fortfahren.';
                    
                    writeLog('Download-Modus beendet - weiter mit Installationsformular');
                    
                } else {
                    writeErrorLog('Konnte extrahierten Ordner nicht finden');
                    throw new Exception('Konnte extrahierten Ordner nicht finden.');
                }
            } else {
                writeErrorLog("ZIP konnte nicht geöffnet werden. Fehlercode: $zipResult");
                throw new Exception("ZIP konnte nicht geöffnet werden. Fehlercode: $zipResult");
            }
            
        } catch (Exception $e) {
            writeErrorLog('Fehler beim Download', $e);
            if (isset($zipFile) && file_exists($zipFile)) @unlink($zipFile);
            if (isset($extractDir) && is_dir($extractDir)) {
                if (function_exists('deleteDirectory')) {
                    deleteDirectory($extractDir);
                }
            }
            $error = 'Fehler beim Download: ' . $e->getMessage();
            $output[] = '';
            $output[] = '❌ FEHLER: ' . $e->getMessage();
            $output[] = 'Stack Trace: ' . $e->getTraceAsString();
            $output[] = 'Log-Datei: logs/install_' . date('Y-m-d') . '.log';
        } catch (Error $e) {
            // PHP 7+ Fatal Errors abfangen
            writeErrorLog('PHP Fatal Error', $e);
            $error = 'PHP Fehler: ' . $e->getMessage() . ' in Zeile ' . $e->getLine();
            $output[] = '';
            $output[] = '❌ PHP FEHLER: ' . $e->getMessage();
            $output[] = 'Datei: ' . $e->getFile();
            $output[] = 'Zeile: ' . $e->getLine();
            $output[] = 'Log-Datei: logs/install_' . date('Y-m-d') . '.log';
        }
    }
    } // Ende der else-Klammer für Extensions-Prüfung
}

// POST-Handler
writeLog('Prüfe Request-Methode: ' . $_SERVER['REQUEST_METHOD']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    writeLog('POST-Request erkannt');
    writeLog('POST-Daten: ' . json_encode(array_keys($_POST)));
    
    if ($step === 'install') {
        writeLog('Starte Installation...');
        // Installation durchführen
        $db_host = $_POST['db_host'] ?? '127.0.0.1';
        $db_port = $_POST['db_port'] ?? '3307';
        $db_name = $_POST['db_name'] ?? '';
        $db_user = $_POST['db_user'] ?? '';
        $db_pass = $_POST['db_pass'] ?? '';
        $admin_email = $_POST['admin_email'] ?? 'admin@stammtisch.de';
        $admin_password = $_POST['admin_password'] ?? '';
        
        writeLog("DB-Host: $db_host");
        writeLog("DB-Port: $db_port");
        writeLog("DB-Name: " . (empty($db_name) ? 'LEER' : 'gesetzt'));
        writeLog("DB-User: " . (empty($db_user) ? 'LEER' : 'gesetzt'));
        writeLog("Admin-Email: $admin_email");
        
        if (empty($db_name) || empty($db_user) || empty($admin_password)) {
            writeLog('FEHLER: Nicht alle Pflichtfelder ausgefüllt');
            $error = 'Bitte fülle alle Pflichtfelder aus.';
        } else {
            writeLog('Alle Felder ausgefüllt - starte Datenbankverbindung...');
            try {
                // Teste DB-Verbindung
                $dsn = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                $testDb = new PDO($dsn, $db_user, $db_pass, $options);
                
                // Erstelle Datenbank falls nicht vorhanden
                $createDbSql = "CREATE DATABASE IF NOT EXISTS `" . str_replace('`', '``', $db_name) . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
                $testDb->exec($createDbSql);
                
                // Wechsle zur Datenbank
                $testDb->exec("USE `" . str_replace('`', '``', $db_name) . "`");
                
                // Lade SQL-Datei
                $sqlFile = __DIR__ . '/database_complete.sql';
                if (!file_exists($sqlFile)) {
                    throw new Exception('SQL-Datei nicht gefunden: database_complete.sql');
                }
                
                $sql = file_get_contents($sqlFile);
                $output[] = '📄 SQL-Datei geladen: ' . number_format(strlen($sql)) . ' Bytes';
                
                // DEBUG: Zeige alle Vorkommen von Datenbanknamen
                $dbNameMatches = [];
                preg_match_all('/\b(kdph7973_\w+)\b/i', $sql, $dbNameMatches);
                if (!empty($dbNameMatches[1])) {
                    $output[] = '⚠️ DEBUG: Gefundene Datenbanknamen im SQL: ' . implode(', ', array_unique($dbNameMatches[1]));
                }
                
                // Entferne Kommentare, die CREATE DATABASE oder USE enthalten
                $sqlBefore = $sql;
                $sql = preg_replace('/^--.*CREATE\s+DATABASE.*$/mi', '', $sql);
                $sql = preg_replace('/^--.*USE.*$/mi', '', $sql);
                $output[] = '✅ Kommentare entfernt';
                
                // Ersetze USE statement und alle Datenbanknamen
                // Ersetze Platzhalter in SQL (falls vorhanden)
                $sql = preg_replace('/USE\s+\[DEINE_DATENBANK\]\s*;/i', '', $sql); // Entferne Platzhalter USE
                $sql = preg_replace('/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+\[DEINE_DATENBANK\]/i', '', $sql); // Entferne Platzhalter CREATE
                
                // Ersetze auch alle alten Datenbanknamen, die möglicherweise noch im SQL stehen
                // (z.B. von früheren Versionen)
                $oldDbNames = ['kdph7973_pimmel', 'kdph7973_sven'];
                foreach ($oldDbNames as $oldDbName) {
                    if ($oldDbName !== $db_name && stripos($sql, $oldDbName) !== false) {
                        $output[] = '🔄 Ersetze alten DB-Namen: ' . $oldDbName . ' → ' . $db_name;
                        $sql = preg_replace('/\b' . preg_quote($oldDbName, '/') . '\b/i', $db_name, $sql);
                    }
                }
                
                // Entferne alle USE Statements (wir sind bereits in der richtigen DB)
                $useCount = preg_match_all('/^\s*USE\s+[^;]+;\s*$/mi', $sql, $useMatches);
                if ($useCount > 0) {
                    $output[] = '🔄 Entferne ' . $useCount . ' USE-Statement(s): ' . implode(', ', $useMatches[0]);
                }
                $sql = preg_replace('/^\s*USE\s+[^;]+;\s*$/mi', '', $sql);
                
                // Prüfe nochmal nach dem alten DB-Namen
                if (stripos($sql, 'kdph7973_pimmel') !== false) {
                    $output[] = '❌ WARNUNG: Alter DB-Name "kdph7973_pimmel" noch im SQL gefunden!';
                    $output[] = 'DEBUG: Erste 500 Zeichen des SQL: ' . substr($sql, 0, 500);
                }
                
                // Aufteilen in einzelne Statements und ausführen
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function($stmt) {
                        return !empty($stmt) && !preg_match('/^--/', $stmt);
                    }
                );
                
                $output[] = '📊 Gefundene SQL-Statements: ' . count($statements);
                $successCount = 0;
                $errorCount = 0;
                
                foreach ($statements as $idx => $statement) {
                    if (!empty($statement)) {
                        // Prüfe nochmal auf alten DB-Namen
                        if (stripos($statement, 'kdph7973_pimmel') !== false) {
                            $output[] = '❌ FEHLER: Statement #' . ($idx + 1) . ' enthält noch "kdph7973_pimmel"!';
                            $output[] = 'Statement: ' . substr($statement, 0, 200);
                            $statement = preg_replace('/\bkdph7973_pimmel\b/i', $db_name, $statement);
                            $output[] = 'Korrigiertes Statement: ' . substr($statement, 0, 200);
                        }
                        
                        try {
                            $testDb->exec($statement);
                            $successCount++;
                            if ($idx < 5 || $idx % 10 == 0) { // Zeige erste 5 und dann jede 10.
                                $output[] = '✅ Statement #' . ($idx + 1) . ' erfolgreich';
                            }
                        } catch (PDOException $e) {
                            $errorCount++;
                            $errorMsg = $e->getMessage();
                            $output[] = '❌ FEHLER bei Statement #' . ($idx + 1) . ': ' . $errorMsg;
                            $output[] = 'Statement Anfang: ' . substr($statement, 0, 150);
                            
                            // Ignoriere Fehler bei "table already exists" etc.
                            if (strpos($errorMsg, 'already exists') === false && 
                                strpos($errorMsg, 'Duplicate') === false &&
                                strpos($errorMsg, 'Access denied') === false) {
                                // Bei Access denied wollen wir aber stoppen!
                                if (strpos($errorMsg, 'Access denied') !== false) {
                                    $output[] = '🔴 KRITISCHER FEHLER: Access denied - prüfe Datenbankname und Berechtigungen!';
                                    $output[] = 'Verwendete DB: ' . $db_name;
                                    $output[] = 'Verwendeter User: ' . $db_user;
                                    throw $e; // Stoppe bei Access denied
                                }
                            }
                        }
                    }
                }
                
                $output[] = '✅ SQL-Import abgeschlossen: ' . $successCount . ' erfolgreich, ' . $errorCount . ' Fehler';
                
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
                writeErrorLog('Fehler bei Installation', $e);
                $error = 'Fehler: ' . $e->getMessage();
                $output[] = 'Log-Datei: logs/install_' . date('Y-m-d') . '.log';
            } catch (Error $e) {
                writeErrorLog('PHP Fatal Error bei Installation', $e);
                $error = 'PHP Fehler: ' . $e->getMessage() . ' in Zeile ' . $e->getLine();
                $output[] = 'Log-Datei: logs/install_' . date('Y-m-d') . '.log';
            }
        }
    } elseif ($step === 'update') {
        writeLog('Update-Modus gestartet');
        // Update durchführen (via Git oder ZIP-Download)
        $output = [];
        $returnCode = 0;
        $gitDir = __DIR__;
        
        // Methode 1: Versuche Git (falls verfügbar)
        exec('which git 2>&1', $gitCheck, $gitCheckCode);
        $gitAvailable = ($gitCheckCode === 0);
        
        if ($gitAvailable && is_dir($gitDir . '/.git')) {
            // Git-Repository vorhanden - versuche Pull
            exec("cd " . escapeshellarg($gitDir) . " && git pull origin main 2>&1", $output, $returnCode);
            
            if ($returnCode === 0) {
                $success = 'Update erfolgreich via Git!';
            } else {
                // Git Pull fehlgeschlagen - versuche ZIP-Methode
                $output[] = '=== Git Pull fehlgeschlagen, versuche ZIP-Download ===';
                $gitAvailable = false;
            }
        }
        
        // Methode 2: ZIP-Download (falls Git nicht verfügbar oder fehlgeschlagen)
        if (!$gitAvailable || !is_dir($gitDir . '/.git')) {
            $output[] = '=== Lade Update via ZIP-Download ===';
            
            // Prüfe ob ZipArchive verfügbar ist
            if (!class_exists('ZipArchive')) {
                $error = 'Weder Git noch ZipArchive ist verfügbar. Bitte kontaktiere deinen Hoster oder installiere Git/ZipArchive.';
            } else {
                // Lade ZIP von GitHub (verschiedene URL-Formate versuchen)
                // Repository: https://github.com/TrofyTT/Stammtisch.git
                // Hinweis: Für private Repos benötigt man einen Token, daher versuchen wir öffentliche URLs
                $zipUrls = [
                    'https://github.com/TrofyTT/Stammtisch/archive/main.zip',
                    'https://github.com/TrofyTT/Stammtisch/archive/refs/heads/main.zip',
                    'https://codeload.github.com/TrofyTT/Stammtisch/zip/refs/heads/main',
                    'https://codeload.github.com/TrofyTT/Stammtisch/zip/main'
                ];
                $zipFile = $gitDir . '/update_temp.zip';
                $extractDir = $gitDir . '/update_temp';
                
                try {
                    // Lade ZIP herunter - versuche verschiedene URLs
                    $output[] = 'Lade ZIP von GitHub...';
                    $zipData = false;
                    $usedUrl = null;
                    
                    // Versuche jede URL
                    foreach ($zipUrls as $urlIndex => $testUrl) {
                        $output[] = "Versuche URL " . ($urlIndex + 1) . "/" . count($zipUrls) . "...";
                        $zipData = false;
                        
                        // Methode 1: cURL (besser für Redirects und SSL)
                        if (function_exists('curl_init')) {
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $testUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
                            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
                            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
                            
                            $zipData = curl_exec($ch);
                            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
                            $curlError = curl_error($ch);
                            curl_close($ch);
                            
                            if ($zipData !== false && $httpCode === 200) {
                                // Prüfe ZIP-Signatur (PK = ZIP-Datei)
                                if (substr($zipData, 0, 2) === "PK") {
                                    $usedUrl = $testUrl;
                                    $output[] = '✅ cURL erfolgreich: ' . number_format(strlen($zipData) / 1024, 2) . ' KB (ZIP validiert)';
                                    break;
                                } else {
                                    $output[] = '⚠️ Antwort ist keine ZIP-Datei (HTTP ' . $httpCode . ', Type: ' . $contentType . ')';
                                    $zipData = false;
                                }
                            } else {
                                $output[] = '❌ cURL Fehler: HTTP ' . $httpCode . ($curlError ? ' - ' . $curlError : '');
                                $zipData = false;
                            }
                        }
                        
                        // Methode 2: file_get_contents (Fallback)
                        if ($zipData === false && ini_get('allow_url_fopen')) {
                            $context = stream_context_create([
                                'http' => [
                                    'method' => 'GET',
                                    'header' => [
                                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                                        'Accept: */*'
                                    ],
                                    'timeout' => 60,
                                    'follow_location' => true,
                                    'max_redirects' => 5,
                                    'ignore_errors' => false
                                ],
                                'ssl' => [
                                    'verify_peer' => false,
                                    'verify_peer_name' => false
                                ]
                            ]);
                            
                            $zipData = @file_get_contents($testUrl, false, $context);
                            
                            if ($zipData !== false && strlen($zipData) > 1000) {
                                // Prüfe ZIP-Signatur
                                if (substr($zipData, 0, 2) === "PK") {
                                    $usedUrl = $testUrl;
                                    $output[] = '✅ file_get_contents erfolgreich: ' . number_format(strlen($zipData) / 1024, 2) . ' KB (ZIP validiert)';
                                    break;
                                } else {
                                    $output[] = '⚠️ Heruntergeladene Datei ist keine ZIP (erste Bytes: ' . bin2hex(substr($zipData, 0, 4)) . ')';
                                    $zipData = false;
                                }
                            } else {
                                $output[] = '❌ file_get_contents fehlgeschlagen';
                                $zipData = false;
                            }
                        }
                    }
                    
                    if ($zipData === false || strlen($zipData) < 1000) {
                        // Prüfe ob es ein privates Repository ist (404 Fehler)
                        $isPrivateRepo = false;
                        foreach ($zipUrls as $testUrl) {
                            if (function_exists('curl_init')) {
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL, $testUrl);
                                curl_setopt($ch, CURLOPT_NOBODY, true);
                                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                curl_exec($ch);
                                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);
                                
                                if ($httpCode === 404) {
                                    $isPrivateRepo = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($isPrivateRepo) {
                            throw new Exception('Das GitHub-Repository ist wahrscheinlich privat. ZIP-Downloads funktionieren nur bei öffentlichen Repositories.<br><br><strong>Lösungen:</strong><br>1. ✅ Repository auf GitHub öffentlich machen (Settings → Danger Zone → Change visibility)<br>2. ✅ Updates per FTP hochladen<br>3. ✅ Update-Button im Admin-Panel nutzen (falls Git verfügbar)<br>4. ✅ SSH-Zugriff nutzen: <code>git pull origin main</code>');
                        } else {
                            throw new Exception('Konnte ZIP nicht von GitHub herunterladen. Alle URL-Varianten wurden versucht.<br><br>Mögliche Lösungen:<br>1. Prüfe ob der Server externe Verbindungen erlaubt<br>2. Kontaktiere deinen Hoster (allow_url_fopen oder cURL benötigt)<br>3. Lade die Dateien manuell per FTP hoch');
                        }
                    }
                    
                    // Speichere ZIP
                    $bytesWritten = file_put_contents($zipFile, $zipData);
                    if ($bytesWritten === false) {
                        throw new Exception('Konnte ZIP-Datei nicht speichern. Bitte prüfe Schreibrechte.');
                    }
                    $output[] = 'ZIP heruntergeladen und gespeichert (' . number_format(strlen($zipData) / 1024, 2) . ' KB)';
                    
                    // Erstelle temporären Extraktions-Ordner
                    if (!is_dir($extractDir)) {
                        mkdir($extractDir, 0755, true);
                    }
                    
                    // Prüfe ob es wirklich eine ZIP-Datei ist
                    $fileSignature = @file_get_contents($zipFile, false, null, 0, 4);
                    if ($fileSignature !== "PK\x03\x04") {
                        throw new Exception('Heruntergeladene Datei ist keine gültige ZIP-Datei. Möglicherweise wurde eine Fehlerseite heruntergeladen.');
                    }
                    $output[] = 'ZIP-Datei validiert';
                    
                    // Entpacke ZIP
                    $zip = new ZipArchive();
                    $zipResult = $zip->open($zipFile);
                    if ($zipResult === TRUE) {
                        $zip->extractTo($extractDir);
                        $zip->close();
                        $output[] = 'ZIP erfolgreich entpackt';
                        
                        // Finde den extrahierten Ordner (normalerweise Stammtisch-main)
                        $extractedFolder = null;
                        $dirs = scandir($extractDir);
                        foreach ($dirs as $dir) {
                            if ($dir !== '.' && $dir !== '..' && is_dir($extractDir . '/' . $dir)) {
                                $extractedFolder = $extractDir . '/' . $dir;
                                break;
                            }
                        }
                        
                        if ($extractedFolder) {
                            $output[] = 'Kopiere Dateien...';
                            
                            // Dateien kopieren (außer config.php und uploads/)
                            $excludeFiles = ['config.php', 'config.local.php', '.git', 'uploads'];
                            $excludeDirs = ['uploads'];
                            
                            $filesCopied = 0;
                            $iterator = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($extractedFolder, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::SELF_FIRST
                            );
                            
                            foreach ($iterator as $item) {
                                $relativePath = substr($item->getPathname(), strlen($extractedFolder) + 1);
                                $targetPath = $gitDir . '/' . $relativePath;
                                
                                // Überspringe ausgeschlossene Dateien/Ordner
                                $skip = false;
                                foreach ($excludeFiles as $exclude) {
                                    if (strpos($relativePath, $exclude) === 0) {
                                        $skip = true;
                                        break;
                                    }
                                }
                                foreach ($excludeDirs as $excludeDir) {
                                    if (strpos($relativePath, $excludeDir . '/') === 0 || $relativePath === $excludeDir) {
                                        $skip = true;
                                        break;
                                    }
                                }
                                
                                if (!$skip) {
                                    if ($item->isDir()) {
                                        if (!is_dir($targetPath)) {
                                            mkdir($targetPath, 0755, true);
                                        }
                                    } else {
                                        // Erstelle Zielverzeichnis falls nötig
                                        $targetDir = dirname($targetPath);
                                        if (!is_dir($targetDir)) {
                                            mkdir($targetDir, 0755, true);
                                        }
                                        
                                        copy($item->getPathname(), $targetPath);
                                        $filesCopied++;
                                    }
                                }
                            }
                            
                            $output[] = $filesCopied . ' Dateien aktualisiert';
                            
                            // Aufräumen
                            unlink($zipFile);
                            deleteDirectory($extractDir);
                            
                            $success = 'Update erfolgreich via ZIP-Download! ' . $filesCopied . ' Dateien aktualisiert.';
                        } else {
                            throw new Exception('Konnte extrahierten Ordner nicht finden.');
                        }
                    } else {
                        throw new Exception('Konnte ZIP nicht entpacken.');
                    }
                    
                } catch (Exception $e) {
                    // Aufräumen bei Fehler
                    if (file_exists($zipFile)) unlink($zipFile);
                    if (is_dir($extractDir)) deleteDirectory($extractDir);
                    
                    $error = 'Fehler beim ZIP-Download: ' . $e->getMessage();
                }
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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #1a1a1a;
            color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .install-container {
            max-width: 700px;
            width: 100%;
        }
        
        .install-step {
            background: #2a2a2a;
            border: 1px solid #404040;
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }
        
        .step-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #404040;
        }
        
        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #007AFF, #5856D6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 24px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 122, 255, 0.3);
        }
        
        .step-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #ffffff;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #ffffff;
            font-size: 15px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px 18px;
            border: 2px solid #404040;
            border-radius: 10px;
            background: #1a1a1a;
            color: #ffffff;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007AFF;
            background: #252525;
            box-shadow: 0 0 0 4px rgba(0, 122, 255, 0.1);
        }
        
        .form-group input::placeholder {
            color: #888888;
        }
        
        .form-group h2 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 20px;
            margin-top: 10px;
        }
        
        hr {
            margin: 35px 0;
            border: none;
            border-top: 2px solid #404040;
        }
        
        .btn-install {
            width: 100%;
            padding: 18px 24px;
            background: linear-gradient(135deg, #007AFF, #5856D6);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(0, 122, 255, 0.3);
            margin-top: 10px;
        }
        
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 122, 255, 0.4);
        }
        
        .btn-install:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 18px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 15px;
            line-height: 1.5;
            border: 2px solid;
        }
        
        .alert-success {
            background: rgba(52, 199, 89, 0.15);
            border-color: #34C759;
            color: #34C759;
        }
        
        .alert-error {
            background: rgba(255, 59, 48, 0.15);
            border-color: #FF3B30;
            color: #FF3B30;
        }
        
        .alert-info {
            background: rgba(0, 122, 255, 0.15);
            border-color: #007AFF;
            color: #007AFF;
        }
        
        .update-section {
            background: #1a1a1a;
            border: 2px solid #404040;
            border-radius: 12px;
            padding: 25px;
            margin-top: 25px;
        }
        
        .update-section h2 {
            font-size: 22px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 15px;
        }
        
        .update-section p {
            color: #cccccc;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .info-box {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #404040;
        }
        
        .info-box p {
            color: #aaaaaa;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .info-box a {
            color: #007AFF;
            text-decoration: none;
            font-weight: 600;
        }
        
        .info-box a:hover {
            text-decoration: underline;
        }
        
        pre {
            background: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
            border: 1px solid #404040;
            color: #cccccc;
            font-family: 'SF Mono', 'Monaco', 'Courier New', monospace;
        }
        
        @media (max-width: 768px) {
            .install-step {
                padding: 25px;
            }
            
            .step-header h1 {
                font-size: 22px;
            }
            
            .step-number {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
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
                <div class="alert alert-error" style="background: #ff4444; color: #ffffff; padding: 30px; border-radius: 12px; margin: 30px 0; font-size: 18px; line-height: 1.8; box-shadow: 0 6px 20px rgba(255, 68, 68, 0.4); border: 3px solid #ff0000; animation: pulse-error 2s infinite;">
                    <strong style="font-size: 24px; display: block; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">❌ KRITISCHER FEHLER:</strong>
                    <div style="background: rgba(0,0,0,0.3); padding: 20px; border-radius: 8px; font-family: 'Monaco', 'Menlo', 'Consolas', monospace; white-space: pre-wrap; word-wrap: break-word; font-size: 16px; border: 1px solid rgba(255,255,255,0.2);"><?= htmlspecialchars($error) ?></div>
                </div>
                <style>
                    @keyframes pulse-error {
                        0%, 100% { box-shadow: 0 6px 20px rgba(255, 68, 68, 0.4); }
                        50% { box-shadow: 0 6px 30px rgba(255, 68, 68, 0.7); }
                    }
                </style>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($needsDownload && !isset($_POST['skip_download']) && empty($error)): ?>
                <div class="alert alert-info" style="margin-bottom: 25px; padding: 25px; background: #007AFF; color: #ffffff; border-radius: 12px;">
                    <strong style="font-size: 20px; display: block; margin-bottom: 15px;">📦 Dateien werden von GitHub heruntergeladen...</strong>
                    <p style="font-size: 16px; line-height: 1.6;">Die Installation lädt automatisch alle benötigten Dateien herunter.</p>
                    <p style="font-size: 14px; margin-top: 15px; opacity: 0.9;">Dies kann einige Sekunden dauern...</p>
                </div>
                
                <?php if (!empty($output)): ?>
                    <div class="update-log" style="margin-top: 25px; padding: 30px; background: #0a0a0a; border-radius: 12px; border: 3px solid #007AFF; max-height: 600px; overflow-y: auto;">
                        <strong style="color: #007AFF; display: block; margin-bottom: 20px; font-size: 20px; font-weight: 700;">📋 Download-Log:</strong>
                        <pre style="color: #cccccc; font-size: 14px; line-height: 2; margin: 0; white-space: pre-wrap; word-wrap: break-word; font-family: 'Monaco', 'Menlo', 'Consolas', monospace;"><?= htmlspecialchars(implode("\n", $output)) ?></pre>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div style="margin-top: 20px;">
                            <a href="?step=install" class="btn-install" style="text-decoration: none; display: block; text-align: center;">
                                🔄 Erneut versuchen
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 30px;">
                            <form method="POST">
                                <input type="hidden" name="skip_download" value="1">
                                <button type="submit" class="btn-install">
                                    ✅ Weiter zur Installation
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
            <?php elseif (!$installed && $step === 'install'): ?>
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
                        <input type="text" name="db_name" placeholder="z.B. meine_datenbank" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Benutzer *</label>
                        <input type="text" name="db_user" placeholder="z.B. mein_db_user" required>
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
                
                <div class="alert alert-info" style="margin-bottom: 25px;">
                    <strong>📦 Automatischer Update</strong><br>
                    Das Update wird automatisch von GitHub geladen. Es wird versucht:
                    <ul style="margin: 10px 0 0 20px; color: #cccccc;">
                        <li>✅ Git (falls verfügbar)</li>
                        <li>✅ ZIP-Download (falls Git nicht verfügbar)</li>
                    </ul>
                    <strong>Kein SSH-Zugriff nötig!</strong>
                    <?php if (!is_dir(__DIR__ . '/.git')): ?>
                        <br><br>
                        <strong>⚠️ Hinweis:</strong> Falls das Repository privat ist, könnte der ZIP-Download fehlschlagen. 
                        In diesem Fall nutze den Update-Button im Admin-Panel oder lade die Dateien manuell per FTP hoch.
                    <?php endif; ?>
                </div>
                
                <form method="POST">
                    <button type="submit" class="btn-install">🔄 Update von GitHub laden</button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #1a1a1a; border-radius: 8px; border: 1px solid #404040;">
                    <strong style="color: #ffffff; display: block; margin-bottom: 10px;">💡 Alternative Methoden:</strong>
                    <ul style="margin: 0 0 0 20px; color: #aaaaaa; font-size: 14px; line-height: 1.8;">
                        <li><strong>Admin-Panel:</strong> Nach dem Login kannst du im Admin-Bereich ebenfalls Updates durchführen</li>
                        <li><strong>FTP Upload:</strong> Lade einfach die geänderten Dateien per FTP hoch</li>
                        <li><strong>Git (falls SSH verfügbar):</strong> <code>git pull origin main</code></li>
                    </ul>
                </div>
                
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
                <h3 style="color: #ffffff; margin-bottom: 15px; font-size: 18px;">📋 Update-Log:</h3>
                <pre><?= htmlspecialchars(implode("\n", $output)) ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

