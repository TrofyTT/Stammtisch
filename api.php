<?php
require_once 'config.php';
requireAuth();

header('Content-Type: application/json; charset=utf-8');

// Funktion zur Generierung eindeutiger Farben
function generateUniqueColor($db, $exclude_user_id = null) {
    $defaultColors = [
        '#007AFF', '#5856D6', '#34C759', '#FF9500', '#FF3B30',
        '#AF52DE', '#FF2D55', '#5AC8FA', '#FFCC00', '#30D158'
    ];
    
    // Bereits verwendete Farben holen
    $query = "SELECT color FROM users WHERE color IS NOT NULL AND color != ''";
    if ($exclude_user_id) {
        $query .= " AND id != " . intval($exclude_user_id);
    }
    $stmt = $db->query($query);
    $usedColors = array_map(function($row) { return strtoupper($row['color']); }, $stmt->fetchAll());
    
    // Erste verfügbare Standardfarbe finden
    foreach ($defaultColors as $color) {
        if (!in_array(strtoupper($color), $usedColors)) {
            return $color;
        }
    }
    
    // Falls alle verwendet werden, zufällige Farbe generieren
    return sprintf('#%06X', mt_rand(0, 0xFFFFFF));
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $db = getDB();
    
    switch ($action) {
        case 'get_termine':
            $stmt = $db->query("
                SELECT t.*, u.name as creator_name,
                       COUNT(DISTINCT CASE WHEN a.status = 'anwesend' THEN a.id END) as anwesend_count,
                       COUNT(DISTINCT CASE WHEN a.status = 'nicht_anwesend' THEN a.id END) as nicht_anwesend_count,
                       COUNT(DISTINCT CASE WHEN a.status = 'unentschuldigt' THEN a.id END) as unentschuldigt_count
                FROM termine t
                LEFT JOIN users u ON t.created_by = u.id
                LEFT JOIN anwesenheiten a ON t.id = a.termin_id
                GROUP BY t.id
                ORDER BY t.datum DESC, t.uhrzeit DESC
                LIMIT 50
            ");
            $termine = $stmt->fetchAll();
            
            // Für jeden Termin die Anwesenheiten holen
            foreach ($termine as &$termin) {
                $stmt = $db->prepare("
                    SELECT a.*, u.name as user_name, u.email as user_email
                    FROM anwesenheiten a
                    JOIN users u ON a.user_id = u.id
                    WHERE a.termin_id = ?
                    ORDER BY a.status, u.name
                ");
                $stmt->execute([$termin['id']]);
                $termin['anwesenheiten'] = $stmt->fetchAll();
            }
            
            echo json_encode(['success' => true, 'data' => $termine]);
            break;
            
        case 'create_termin':
            $name = trim($_POST['name'] ?? '');
            $ort = trim($_POST['ort'] ?? '');
            $datum = $_POST['datum'] ?? '';
            $uhrzeit = $_POST['uhrzeit'] ?? '';
            $beschreibung = trim($_POST['beschreibung'] ?? '');
            
            if (empty($name) || empty($ort) || empty($datum) || empty($uhrzeit)) {
                throw new Exception('Alle Pflichtfelder müssen ausgefüllt sein.');
            }
            
            $stmt = $db->prepare("INSERT INTO termine (name, ort, datum, uhrzeit, beschreibung, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $ort, $datum, $uhrzeit, $beschreibung, $_SESSION['user_id']]);
            
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            break;
            
        case 'update_termin':
            $id = intval($_POST['termin_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $ort = trim($_POST['ort'] ?? '');
            $datum = $_POST['datum'] ?? '';
            $uhrzeit = $_POST['uhrzeit'] ?? '';
            $beschreibung = trim($_POST['beschreibung'] ?? '');
            
            if (empty($name) || empty($ort) || empty($datum) || empty($uhrzeit)) {
                throw new Exception('Alle Pflichtfelder müssen ausgefüllt sein.');
            }
            
            // Prüfen ob User Admin ist oder der Ersteller
            $stmt = $db->prepare("SELECT created_by FROM termine WHERE id = ?");
            $stmt->execute([$id]);
            $termin = $stmt->fetch();
            
            if (!$termin) {
                throw new Exception('Termin nicht gefunden.');
            }
            
            if (!$stmt && !$_SESSION['is_admin'] && $termin['created_by'] != $_SESSION['user_id']) {
                throw new Exception('Keine Berechtigung.');
            }
            
            $stmt = $db->prepare("UPDATE termine SET name = ?, ort = ?, datum = ?, uhrzeit = ?, beschreibung = ? WHERE id = ?");
            $stmt->execute([$name, $ort, $datum, $uhrzeit, $beschreibung, $id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_termin':
            $id = intval($_POST['termin_id'] ?? 0);
            
            // Prüfen ob User Admin ist oder der Ersteller
            $stmt = $db->prepare("SELECT created_by FROM termine WHERE id = ?");
            $stmt->execute([$id]);
            $termin = $stmt->fetch();
            
            if (!$termin) {
                throw new Exception('Termin nicht gefunden.');
            }
            
            if (!$_SESSION['is_admin'] && $termin['created_by'] != $_SESSION['user_id']) {
                throw new Exception('Keine Berechtigung.');
            }
            
            $stmt = $db->prepare("DELETE FROM termine WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_termin_anwesenheiten':
            $termin_id = intval($_GET['termin_id'] ?? 0);
            
            // Alle Benutzer holen
            $stmt = $db->query("SELECT id, name, email FROM users ORDER BY name");
            $users = $stmt->fetchAll();
            
            // Anwesenheiten für diesen Termin holen
            $stmt = $db->prepare("
                SELECT a.*, u.name as user_name
                FROM anwesenheiten a
                JOIN users u ON a.user_id = u.id
                WHERE a.termin_id = ?
            ");
            $stmt->execute([$termin_id]);
            $anwesenheiten = $stmt->fetchAll();
            
            // Anwesenheiten nach User-ID indexieren
            $anwesenheiten_map = [];
            foreach ($anwesenheiten as $anw) {
                $anwesenheiten_map[$anw['user_id']] = $anw;
            }
            
            echo json_encode(['success' => true, 'users' => $users, 'anwesenheiten' => $anwesenheiten_map]);
            break;
            
        case 'save_anwesenheit':
            $termin_id = intval($_POST['termin_id'] ?? 0);
            $user_id = intval($_POST['user_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            $notiz = trim($_POST['notiz'] ?? '');
            
            if (!in_array($status, ['anwesend', 'nicht_anwesend', 'unentschuldigt'])) {
                throw new Exception('Ungültiger Status.');
            }
            
            // Prüfen ob User existiert
            $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Benutzer nicht gefunden.');
            }
            
            // Prüfen ob Termin existiert
            $stmt = $db->prepare("SELECT id FROM termine WHERE id = ?");
            $stmt->execute([$termin_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Termin nicht gefunden.');
            }
            
            // Anwesenheit speichern oder aktualisieren
            $stmt = $db->prepare("
                INSERT INTO anwesenheiten (termin_id, user_id, status, notiz)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = ?, notiz = ?, erfasst_am = NOW()
            ");
            $stmt->execute([$termin_id, $user_id, $status, $notiz, $status, $notiz]);
            
            // Achievement-Prüfung nach Anwesenheit
            checkAchievements($db, $user_id);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_stats':
            // Quick Stats
            $stats = [];
            
            // Gesamt Termine
            $stmt = $db->query("SELECT COUNT(*) as count FROM termine");
            $result = $stmt->fetch();
            $stats['total_termine'] = (int)($result['count'] ?? 0);
            
            // Gesamt Anwesenheiten (nur anwesend)
            $stmt = $db->query("SELECT COUNT(*) as count FROM anwesenheiten WHERE status = 'anwesend'");
            $result = $stmt->fetch();
            $stats['total_anwesend'] = (int)($result['count'] ?? 0);
            
            // Aktive Benutzer (die mindestens einmal anwesend waren)
            $stmt = $db->query("SELECT COUNT(DISTINCT user_id) as count FROM anwesenheiten WHERE status = 'anwesend'");
            $result = $stmt->fetch();
            $stats['active_users'] = (int)($result['count'] ?? 0);
            
            // Nächster Termin
            $stmt = $db->query("
                SELECT datum, uhrzeit, name 
                FROM termine 
                WHERE CONCAT(datum, ' ', uhrzeit) >= NOW() 
                ORDER BY datum ASC, uhrzeit ASC 
                LIMIT 1
            ");
            $nextTermin = $stmt->fetch();
            if ($nextTermin) {
                $stats['next_termin'] = [
                    'name' => $nextTermin['name'],
                    'datum' => $nextTermin['datum'],
                    'uhrzeit' => $nextTermin['uhrzeit']
                ];
            } else {
                $stats['next_termin'] = null;
            }
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'get_detailed_stats':
            $data = [];
            
            // Personen Statistiken
            $stmt = $db->query("
                SELECT 
                    u.id,
                    u.name,
                    u.avatar,
                    u.color,
                    COUNT(CASE WHEN a.status = 'anwesend' THEN 1 END) as anwesend,
                    COUNT(CASE WHEN a.status = 'nicht_anwesend' THEN 1 END) as nicht_anwesend,
                    COUNT(CASE WHEN a.status = 'unentschuldigt' THEN 1 END) as unentschuldigt,
                    COUNT(a.id) as total
                FROM users u
                LEFT JOIN anwesenheiten a ON u.id = a.user_id
                GROUP BY u.id, u.name, u.avatar, u.color
                ORDER BY u.name
            ");
            $data['personen_stats'] = $stmt->fetchAll();
            
            // Status Statistiken
            $stmt = $db->query("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM anwesenheiten
                GROUP BY status
            ");
            $status_stats = [];
            while ($row = $stmt->fetch()) {
                $status_stats[$row['status']] = $row['count'];
            }
            $data['status_stats'] = [
                'anwesend' => $status_stats['anwesend'] ?? 0,
                'nicht_anwesend' => $status_stats['nicht_anwesend'] ?? 0,
                'unentschuldigt' => $status_stats['unentschuldigt'] ?? 0
            ];
            
            // Zeitliche Entwicklung
            $stmt = $db->query("
                SELECT 
                    t.datum,
                    COUNT(CASE WHEN a.status = 'anwesend' THEN 1 END) as anwesend
                FROM termine t
                LEFT JOIN anwesenheiten a ON t.id = a.termin_id
                GROUP BY t.datum
                ORDER BY t.datum ASC
            ");
            $data['zeit_stats'] = $stmt->fetchAll();
            
            // Top Orte
            $stmt = $db->query("
                SELECT 
                    ort,
                    COUNT(DISTINCT t.id) as count
                FROM termine t
                GROUP BY ort
                ORDER BY count DESC
                LIMIT 10
            ");
            $data['orte_stats'] = $stmt->fetchAll();
            
            // Detaillierte Tabelle
            $stmt = $db->query("
                SELECT 
                    u.name,
                    u.avatar,
                    u.color,
                    COUNT(CASE WHEN a.status = 'anwesend' THEN 1 END) as anwesend,
                    COUNT(CASE WHEN a.status = 'nicht_anwesend' THEN 1 END) as nicht_anwesend,
                    COUNT(CASE WHEN a.status = 'unentschuldigt' THEN 1 END) as unentschuldigt
                FROM users u
                LEFT JOIN anwesenheiten a ON u.id = a.user_id
                GROUP BY u.id, u.name, u.avatar, u.color
                ORDER BY u.name
            ");
            $data['detailed_table'] = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'get_users':
        case 'get_members':
            $stmt = $db->query("SELECT id, name, email, is_admin, created_at, last_login, avatar, rang, color FROM users ORDER BY name");
            $users = $stmt->fetchAll();
            echo json_encode(['success' => true, 'users' => $users, 'members' => $users]);
            break;
            
        case 'create_member':
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rang = trim($_POST['rang'] ?? '');
            $color = trim($_POST['color'] ?? '');
            $password = $_POST['password'] ?? '';
            $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'on' ? 1 : 0;
            
            // Validiere Hex-Farbe
            if (!empty($color) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                throw new Exception('Ungültige Hex-Farbe. Format: #RRGGBB');
            }
            
            // Wenn keine Farbe angegeben, automatisch eine zuweisen
            if (empty($color)) {
                $color = generateUniqueColor($db);
            }
            
            if (empty($name) || empty($email)) {
                throw new Exception('Name und E-Mail sind erforderlich.');
            }
            
            if (empty($password)) {
                throw new Exception('Passwort ist beim Erstellen erforderlich.');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ungültige E-Mail-Adresse.');
            }
            
            // Prüfen ob E-Mail bereits existiert
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Diese E-Mail-Adresse ist bereits registriert.');
            }
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, password_hash, rang, color, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $password_hash, $rang, $color, $is_admin]);
            
            echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);
            break;
            
        case 'update_member':
            $id = intval($_POST['member_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $rang = trim($_POST['rang'] ?? '');
            $color = trim($_POST['color'] ?? '');
            $password = $_POST['password'] ?? '';
            $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'on' ? 1 : 0;
            
            // Validiere Hex-Farbe
            if (!empty($color) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                throw new Exception('Ungültige Hex-Farbe. Format: #RRGGBB');
            }
            
            // Wenn keine Farbe angegeben, automatisch eine zuweisen
            if (empty($color)) {
                $color = generateUniqueColor($db, $id);
            }
            
            if (empty($name) || empty($email)) {
                throw new Exception('Name und E-Mail sind erforderlich.');
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Ungültige E-Mail-Adresse.');
            }
            
            // Prüfen ob E-Mail bereits von anderem Benutzer verwendet wird
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                throw new Exception('Diese E-Mail-Adresse wird bereits verwendet.');
            }
            
            // Verhindern dass sich der letzte Admin selbst entfernt
            if (!$is_admin) {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE is_admin = 1 AND id != ?");
                $stmt->execute([$id]);
                $admin_count = $stmt->fetch()['count'];
                if ($admin_count < 1) {
                    throw new Exception('Es muss mindestens ein Admin vorhanden sein.');
                }
            }
            
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, password_hash = ?, rang = ?, color = ?, is_admin = ? WHERE id = ?");
                $stmt->execute([$name, $email, $password_hash, $rang, $color, $is_admin, $id]);
            } else {
                $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, rang = ?, color = ?, is_admin = ? WHERE id = ?");
                $stmt->execute([$name, $email, $rang, $color, $is_admin, $id]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_member':
            $id = intval($_POST['member_id'] ?? 0);
            
            if ($id == $_SESSION['user_id']) {
                throw new Exception('Du kannst dich nicht selbst löschen.');
            }
            
            // Prüfen ob es der letzte Admin ist
            $stmt = $db->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $member = $stmt->fetch();
            
            if ($member && $member['is_admin']) {
                $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
                $admin_count = $stmt->fetch()['count'];
                if ($admin_count <= 1) {
                    throw new Exception('Der letzte Admin kann nicht gelöscht werden.');
                }
            }
            
            // Avatar löschen falls vorhanden
            $stmt = $db->prepare("SELECT avatar FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $avatar = $stmt->fetchColumn();
            if ($avatar && file_exists(__DIR__ . '/uploads/avatars/' . $avatar)) {
                unlink(__DIR__ . '/uploads/avatars/' . $avatar);
            }
            
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'toggle_admin':
            $user_id = intval($_POST['user_id'] ?? 0);
            $is_admin = intval($_POST['is_admin'] ?? 0) === 1;
            
            if (!$_SESSION['is_admin']) {
                throw new Exception('Keine Berechtigung.');
            }
            
            // Verhindern dass sich der letzte Admin selbst entfernt
            if (!$is_admin) {
                $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 1");
                $admin_count = $stmt->fetch()['count'];
                if ($admin_count <= 1) {
                    throw new Exception('Es muss mindestens ein Admin vorhanden sein.');
                }
            }
            
            $stmt = $db->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
            $stmt->execute([$is_admin, $user_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_settings':
            $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            echo json_encode(['success' => true, 'settings' => $settings]);
            break;
            
        case 'git_pull':
            // Git Pull ausführen (nur für Admins)
            if (!$_SESSION['is_admin']) {
                throw new Exception('Keine Berechtigung.');
            }
            
            $output = [];
            $returnCode = 0;
            
            // Sicherstellen, dass wir im richtigen Verzeichnis sind
            $gitDir = __DIR__;
            
            // Git Pull ausführen
            $command = "cd " . escapeshellarg($gitDir) . " && git pull 2>&1";
            exec($command, $output, $returnCode);
            
            $result = [
                'success' => $returnCode === 0,
                'output' => implode("\n", $output),
                'return_code' => $returnCode,
                'command' => $command
            ];
            
            // Git Status prüfen für zusätzliche Infos
            $statusOutput = [];
            exec("cd " . escapeshellarg($gitDir) . " && git status 2>&1", $statusOutput);
            $result['status'] = implode("\n", $statusOutput);
            
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            break;
            
        case 'save_settings':
            if (!$_SESSION['is_admin']) {
                throw new Exception('Keine Berechtigung.');
            }
            
            $app_name = trim($_POST['app_name'] ?? '');
            
            $stmt = $db->prepare("
                INSERT INTO settings (setting_key, setting_value) 
                VALUES ('app_name', ?)
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([$app_name, $app_name]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'create_game':
            $datum = $_POST['datum'] ?? date('Y-m-d');
            $players = json_decode($_POST['players'] ?? '[]', true);
            
            if (empty($players) || count($players) < 2) {
                throw new Exception('Mindestens 2 Spieler erforderlich.');
            }
            
            // Spiel erstellen
            $stmt = $db->prepare("INSERT INTO spiele (name, datum, status, created_by) VALUES (?, ?, 'aktiv', ?)");
            $spiel_name = '6 Nimmt - ' . date('d.m.Y', strtotime($datum));
            $stmt->execute([$spiel_name, $datum, $_SESSION['user_id']]);
            $spiel_id = $db->lastInsertId();
            
            // Spieler in Reihenfolge eintragen
            foreach ($players as $index => $player_id) {
                $stmt = $db->prepare("INSERT INTO spiel_teilnahme (spiel_id, user_id, reihenfolge) VALUES (?, ?, ?)");
                $stmt->execute([$spiel_id, $player_id, $index + 1]);
            }
            
            echo json_encode(['success' => true, 'game_id' => $spiel_id]);
            break;
            
        case 'add_game_points':
            $spiel_id = intval($_POST['game_id'] ?? 0);
            $player_id = intval($_POST['player_id'] ?? 0);
            $runde = intval($_POST['round'] ?? 0);
            $punkte = intval($_POST['points'] ?? 0);
            
            // Spiel-Teilnahme ID holen
            $stmt = $db->prepare("SELECT id FROM spiel_teilnahme WHERE spiel_id = ? AND user_id = ?");
            $stmt->execute([$spiel_id, $player_id]);
            $teilnahme = $stmt->fetch();
            
            if (!$teilnahme) {
                throw new Exception('Spieler nicht gefunden.');
            }
            
            // Punkte eintragen
            $stmt = $db->prepare("
                INSERT INTO spiel_runden (spiel_id, spiel_teilnahme_id, runde_nummer, punkte)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE punkte = ?
            ");
            $stmt->execute([$spiel_id, $teilnahme['id'], $runde, $punkte, $punkte]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'end_game':
            $spiel_id = intval($_POST['game_id'] ?? 0);
            
            // Prüfen ob User berechtigt ist
            $stmt = $db->prepare("SELECT created_by FROM spiele WHERE id = ?");
            $stmt->execute([$spiel_id]);
            $spiel = $stmt->fetch();
            
            if (!$spiel) {
                throw new Exception('Spiel nicht gefunden.');
            }
            
            if ($spiel['created_by'] != $_SESSION['user_id'] && !$_SESSION['is_admin']) {
                throw new Exception('Keine Berechtigung.');
            }
            
            // Spiel als beendet markieren
            $stmt = $db->prepare("UPDATE spiele SET status = 'beendet' WHERE id = ?");
            $stmt->execute([$spiel_id]);
            
            // Achievement-Prüfung für alle Spieler
            $stmt = $db->prepare("
                SELECT st.user_id, SUM(sr.punkte) as total_points
                FROM spiel_teilnahme st
                LEFT JOIN spiel_runden sr ON st.id = sr.spiel_teilnahme_id
                WHERE st.spiel_id = ?
                GROUP BY st.user_id
            ");
            $stmt->execute([$spiel_id]);
            $players = $stmt->fetchAll();
            
            $newAchievements = [];
            foreach ($players as $player) {
                $achievements = checkAchievements($db, $player['user_id'], [
                    'game_id' => $spiel_id,
                    'total_points' => $player['total_points']
                ]);
                $newAchievements = array_merge($newAchievements, $achievements);
            }
            
            echo json_encode([
                'success' => true,
                'new_achievements' => array_map(function($a) {
                    return ['name' => $a['name'], 'icon' => $a['icon']];
                }, $newAchievements)
            ]);
            break;
            
        case 'get_games':
            $stmt = $db->query("
                SELECT 
                    s.*,
                    COUNT(DISTINCT st.user_id) as player_count,
                    COUNT(DISTINCT sr.runde_nummer) as round_count
                FROM spiele s
                LEFT JOIN spiel_teilnahme st ON s.id = st.spiel_id
                LEFT JOIN spiel_runden sr ON s.id = sr.spiel_id
                GROUP BY s.id
                ORDER BY s.datum DESC, s.created_at DESC
            ");
            $games = $stmt->fetchAll();
            
            // Für jedes Spiel die Rangliste und den Gewinner holen
            foreach ($games as &$game) {
                if ($game['status'] === 'beendet') {
                    // Rangliste für beendete Spiele
                    $stmt = $db->prepare("
                        SELECT 
                            u.id,
                            u.name,
                            u.avatar,
                            SUM(sr.punkte) as total_points
                        FROM spiel_teilnahme st
                        JOIN users u ON st.user_id = u.id
                        LEFT JOIN spiel_runden sr ON st.id = sr.spiel_teilnahme_id
                        WHERE st.spiel_id = ?
                        GROUP BY st.id, u.id, u.name, u.avatar
                        ORDER BY total_points ASC, st.reihenfolge ASC
                    ");
                    $stmt->execute([$game['id']]);
                    $ranking = $stmt->fetchAll();
                    
                    $game['ranking'] = $ranking;
                    $game['winner'] = !empty($ranking) ? $ranking[0] : null; // Erster = niedrigste Punkte
                }
            }
            
            echo json_encode(['success' => true, 'games' => $games]);
            break;
            
        case 'get_game_stats':
            $spiel_id = intval($_GET['game_id'] ?? 0);
            
            // Spiel-Info
            $stmt = $db->prepare("
                SELECT 
                    s.*,
                    COUNT(DISTINCT sr.runde_nummer) as total_runden
                FROM spiele s
                LEFT JOIN spiel_runden sr ON s.id = sr.spiel_id
                WHERE s.id = ?
                GROUP BY s.id
            ");
            $stmt->execute([$spiel_id]);
            $game_info = $stmt->fetch();
            
            if (!$game_info) {
                throw new Exception('Spiel nicht gefunden.');
            }
            
            // Spieler mit Statistiken
            $stmt = $db->prepare("
                SELECT 
                    u.id as user_id,
                    u.name,
                    u.avatar,
                    u.color,
                    st.reihenfolge,
                    COALESCE(SUM(sr.punkte), 0) as total_points,
                    COUNT(sr.id) as rounds_played,
                    COALESCE(AVG(sr.punkte), 0) as average_points,
                    COALESCE(MAX(sr.punkte), 0) as best_round_points,
                    COALESCE(MIN(sr.punkte), 0) as worst_round_points,
                    COALESCE(MAX(sr.runde_nummer), 0) as max_round,
                    COALESCE(MIN(sr.runde_nummer), 0) as min_round
                FROM spiel_teilnahme st
                JOIN users u ON st.user_id = u.id
                LEFT JOIN spiel_runden sr ON st.id = sr.spiel_teilnahme_id
                WHERE st.spiel_id = ?
                GROUP BY st.id, u.id, u.name, u.avatar, u.color, st.reihenfolge
                ORDER BY total_points ASC, st.reihenfolge ASC
            ");
            $stmt->execute([$spiel_id]);
            $players = $stmt->fetchAll();
            
            // Runden-Daten für jeden Spieler
            foreach ($players as &$player) {
                $stmt = $db->prepare("
                    SELECT runde_nummer, punkte
                    FROM spiel_runden sr
                    JOIN spiel_teilnahme st ON sr.spiel_teilnahme_id = st.id
                    WHERE st.spiel_id = ? AND st.user_id = ?
                    ORDER BY runde_nummer ASC
                ");
                $stmt->execute([$spiel_id, $player['user_id']]);
                $player['rounds'] = $stmt->fetchAll();
                
                // Fallback-Werte für Spieler ohne Runden sicherstellen
                if (empty($player['rounds'])) {
                    $player['total_points'] = 0;
                    $player['average_points'] = 0;
                    $player['best_round_points'] = 0;
                    $player['worst_round_points'] = 0;
                    $player['max_round'] = 0;
                    $player['min_round'] = 0;
                } else {
                    // Sicherstellen, dass alle Werte numerisch sind
                    $player['total_points'] = (float)$player['total_points'];
                    $player['average_points'] = (float)$player['average_points'];
                    $player['best_round_points'] = (int)$player['best_round_points'];
                    $player['worst_round_points'] = (int)$player['worst_round_points'];
                    $player['max_round'] = (int)$player['max_round'];
                    $player['min_round'] = (int)$player['min_round'];
                }
            }
            
            // Sortieren nach Gesamtpunkten (niedrigste zuerst = beste bei 6 Nimmt)
            usort($players, function($a, $b) {
                return $a['total_points'] <=> $b['total_points'];
            });
            
            // Player Count
            $game_info['player_count'] = count($players);
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'game_info' => $game_info,
                    'players' => $players
                ]
            ]);
            break;
            
        case 'delete_game':
            $spiel_id = intval($_POST['game_id'] ?? 0);
            
            // Prüfen ob User berechtigt ist
            $stmt = $db->prepare("SELECT created_by FROM spiele WHERE id = ?");
            $stmt->execute([$spiel_id]);
            $spiel = $stmt->fetch();
            
            if (!$spiel) {
                throw new Exception('Spiel nicht gefunden.');
            }
            
            if ($spiel['created_by'] != $_SESSION['user_id'] && !$_SESSION['is_admin']) {
                throw new Exception('Keine Berechtigung.');
            }
            
            // Spiel löschen (CASCADE löscht auch Teilnahme und Runden)
            $stmt = $db->prepare("DELETE FROM spiele WHERE id = ?");
            $stmt->execute([$spiel_id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_achievements':
            $stmt = $db->query("SELECT * FROM achievement_types ORDER BY name");
            $achievements = $stmt->fetchAll();
            
            // Icons bereinigen - fehlerhafte UTF-8 Kodierung entfernen
            foreach ($achievements as &$achievement) {
                if (!empty($achievement['icon'])) {
                    // Entferne fehlerhafte Kodierung
                    $icon = $achievement['icon'];
                    
                    // Prüfe auf bekannte fehlerhafte Muster
                    if (preg_match('/[ðŸ]/u', $icon) || mb_strlen($icon, 'UTF-8') > 2) {
                        // Setze korrektes Icon basierend auf ID
                        $correctIcons = [
                            1 => '🍽️',
                            2 => '📚',
                            3 => '👻'
                        ];
                        $achievement['icon'] = $correctIcons[$achievement['id']] ?? '🏆';
                    }
                } else {
                    // Fallback wenn leer
                    $achievement['icon'] = '🏆';
                }
            }
            
            echo json_encode(['success' => true, 'achievements' => $achievements], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'save_achievement':
            $id = intval($_POST['achievement_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $condition_type = trim($_POST['condition_type'] ?? '');
            $condition_value = trim($_POST['condition_value'] ?? '');
            $icon = trim($_POST['icon'] ?? '🏆');
            
            if (empty($name) || empty($condition_type)) {
                throw new Exception('Name und Bedingungstyp sind erforderlich.');
            }
            
            if ($id > 0) {
                // Update
                $stmt = $db->prepare("
                    UPDATE achievement_types 
                    SET name = ?, description = ?, condition_type = ?, condition_value = ?, icon = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $condition_type, $condition_value ?: null, $icon, $id]);
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO achievement_types (name, description, condition_type, condition_value, icon)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $condition_type, $condition_value ?: null, $icon]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_achievement':
            $id = intval($_POST['achievement_id'] ?? 0);
            
            // Prüfen ob Achievement existiert
            $stmt = $db->prepare("SELECT id FROM achievement_types WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('Achievement nicht gefunden.');
            }
            
            // Achievement löschen (CASCADE löscht auch user_achievements)
            $stmt = $db->prepare("DELETE FROM achievement_types WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true]);
            break;
            
        case 'get_user_achievements':
            $user_id = intval($_GET['user_id'] ?? $_SESSION['user_id']);
            
            // Hole User-Daten für Avatar und Name
            $stmt = $db->prepare("SELECT id, name, avatar, color FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            // Prüfe zuerst, ob neue Achievements verliehen werden sollten
            checkAchievements($db, $user_id);
            
            $stmt = $db->prepare("
                SELECT 
                    at.id,
                    at.name,
                    at.description,
                    at.icon,
                    at.condition_type,
                    ua.earned_at
                FROM user_achievements ua
                JOIN achievement_types at ON ua.achievement_id = at.id
                WHERE ua.user_id = ?
                ORDER BY ua.earned_at DESC
            ");
            $stmt->execute([$user_id]);
            $achievements = $stmt->fetchAll();
            
            // Kategorisiere Achievements
            $gameAchievements = [];
            $stammtischAchievements = [];
            
            // Icons bereinigen und User-Daten hinzufügen
            foreach ($achievements as &$achievement) {
                if (!empty($achievement['icon'])) {
                    $icon = $achievement['icon'];
                    if (preg_match('/[ðŸ]/u', $icon) || mb_strlen($icon, 'UTF-8') > 2) {
                        $achievement['icon'] = '🏆';
                    }
                } else {
                    $achievement['icon'] = '🏆';
                }
                
                // Datum formatieren
                if ($achievement['earned_at']) {
                    $achievement['earned_at'] = date('Y-m-d\TH:i:s', strtotime($achievement['earned_at']));
                }
                
                // User-Daten hinzufügen
                $achievement['user_name'] = $user['name'] ?? 'Unbekannt';
                $achievement['user_avatar'] = $user['avatar'] ?? null;
                $achievement['user_color'] = $user['color'] ?? '#007AFF';
                
                // Kategorisieren: Spiel-bezogen vs. Anwesenheits-bezogen
                $conditionType = $achievement['condition_type'];
                if (in_array($conditionType, ['game_points', 'game_wins'])) {
                    $gameAchievements[] = $achievement;
                } else {
                    // Alle anderen sind Stammtisch-Achievements (attendance_first, attendance_never, attendance_count, etc.)
                    $stammtischAchievements[] = $achievement;
                }
            }
            
            
            echo json_encode([
                'success' => true, 
                'achievements' => $achievements,
                'game_achievements' => $gameAchievements,
                'stammtisch_achievements' => $stammtischAchievements
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get_last_stammtisch_game_stats':
            // Finde letzten Stammtisch-Termin
            $stmt = $db->query("
                SELECT id, name, datum
                FROM termine
                ORDER BY datum DESC, uhrzeit DESC
                LIMIT 1
            ");
            $lastTermin = $stmt->fetch();

            $lastWinner = null;
            $lastLoser = null;

            if ($lastTermin) {
                // Finde alle Spiele von diesem Datum
                $stmt = $db->prepare("
                    SELECT s.id, s.name, s.status
                    FROM spiele s
                    WHERE DATE(s.datum) = DATE(?)
                    AND s.status = 'beendet'
                    ORDER BY s.created_at DESC
                ");
                $stmt->execute([$lastTermin['datum']]);
                $spiele = $stmt->fetchAll();

                if (!empty($spiele)) {
                    // Nimm das letzte Spiel
                    $spiel = $spiele[0];

                    // Finde alle Spieler mit Punkten
                    $stmt = $db->prepare("
                        SELECT
                            u.id,
                            u.name,
                            u.avatar,
                            u.color,
                            SUM(sr.punkte) as total_points
                        FROM spiel_teilnahme st
                        JOIN users u ON st.user_id = u.id
                        LEFT JOIN spiel_runden sr ON st.id = sr.spiel_teilnahme_id
                        WHERE st.spiel_id = ?
                        GROUP BY st.id, u.id, u.name, u.avatar, u.color
                        ORDER BY total_points ASC
                    ");
                    $stmt->execute([$spiel['id']]);
                    $players = $stmt->fetchAll();

                    if (!empty($players)) {
                        // Gewinner = niedrigste Punkte (bei 6 Nimmt)
                        $lastWinner = $players[0];

                        // Verlierer = höchste Punkte
                        if (count($players) > 1) {
                            $lastLoser = $players[count($players) - 1];
                        }
                    }
                }
            }

            // All-Time Gewinner (niedrigste Gesamtpunkte)
            $stmt = $db->query("
                SELECT
                    u.id,
                    u.name,
                    u.avatar,
                    u.color,
                    SUM(sr.punkte) as total_points
                FROM users u
                JOIN spiel_teilnahme st ON u.id = st.user_id
                JOIN spiele s ON st.spiel_id = s.id
                LEFT JOIN spiel_runden sr ON st.id = sr.spiel_teilnahme_id
                WHERE s.status = 'beendet'
                GROUP BY u.id, u.name, u.avatar, u.color
                HAVING total_points IS NOT NULL
                ORDER BY total_points ASC
                LIMIT 1
            ");
            $allTimeWinner = $stmt->fetch();

            // All-Time Loser (höchste Gesamtpunkte)
            $stmt = $db->query("
                SELECT
                    u.id,
                    u.name,
                    u.avatar,
                    u.color,
                    SUM(sr.punkte) as total_points
                FROM users u
                JOIN spiel_teilnahme st ON u.id = st.user_id
                JOIN spiele s ON st.spiel_id = s.id
                LEFT JOIN spiel_runden sr ON st.id = sr.spiel_teilnahme_id
                WHERE s.status = 'beendet'
                GROUP BY u.id, u.name, u.avatar, u.color
                HAVING total_points IS NOT NULL
                ORDER BY total_points DESC
                LIMIT 1
            ");
            $allTimeLoser = $stmt->fetch();

            echo json_encode([
                'success' => true,
                'last_winner' => $lastWinner,
                'last_loser' => $lastLoser,
                'alltime_winner' => $allTimeWinner,
                'alltime_loser' => $allTimeLoser
            ]);
            break;
            
        case 'get_latest_achievements':
            // Hole neueste Achievements (alle User, limitiert auf letzte 10)
            $stmt = $db->query("
                SELECT 
                    ua.id,
                    ua.earned_at,
                    at.name as achievement_name,
                    at.icon as achievement_icon,
                    u.id as user_id,
                    u.name as user_name,
                    u.avatar as user_avatar,
                    u.color as user_color
                FROM user_achievements ua
                JOIN achievement_types at ON ua.achievement_id = at.id
                JOIN users u ON ua.user_id = u.id
                ORDER BY ua.earned_at DESC
                LIMIT 10
            ");
            $achievements = $stmt->fetchAll();
            
            // Icons bereinigen
            foreach ($achievements as &$achievement) {
                if (!empty($achievement['achievement_icon'])) {
                    $icon = $achievement['achievement_icon'];
                    if (preg_match('/[ðŸ]/u', $icon) || mb_strlen($icon, 'UTF-8') > 2) {
                        $achievement['achievement_icon'] = '🏆';
                    }
                } else {
                    $achievement['achievement_icon'] = '🏆';
                }
                
                // Datum formatieren
                if ($achievement['earned_at']) {
                    $achievement['earned_at'] = date('Y-m-d\TH:i:s', strtotime($achievement['earned_at']));
                }
            }
            
            echo json_encode(['success' => true, 'achievements' => $achievements], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'get_game_alltime_stats':
            // Alle beendeten Spiele holen
            $stmt = $db->prepare("
                SELECT 
                    s.id as spiel_id,
                    s.name as spiel_name,
                    s.datum,
                    u.id as user_id,
                    u.name as user_name,
                    u.avatar,
                    u.color,
                    COALESCE(SUM(sr.punkte), 0) as total_points,
                    COUNT(sr.id) as runden_count
                FROM spiele s
                JOIN spiel_teilnahme st ON s.id = st.spiel_id
                JOIN users u ON st.user_id = u.id
                LEFT JOIN spiel_runden sr ON st.id = sr.spiel_teilnahme_id
                WHERE s.status = 'beendet'
                GROUP BY s.id, u.id, u.name, u.avatar, u.color, s.name, s.datum
                ORDER BY s.datum ASC, s.id ASC
            ");
            $stmt->execute();
            $gameResults = $stmt->fetchAll();
            
            // Spiele gruppieren
            $games = [];
            $players = [];
            
            foreach ($gameResults as $result) {
                $gameId = $result['spiel_id'];
                $userId = $result['user_id'];
                
                // Spiel sammeln
                if (!isset($games[$gameId])) {
                    $games[$gameId] = [
                        'id' => $gameId,
                        'name' => $result['spiel_name'],
                        'datum' => $result['datum'],
                        'players' => []
                    ];
                }
                
                // Spieler-Ergebnis zum Spiel hinzufügen
                $games[$gameId]['players'][] = [
                    'user_id' => $userId,
                    'name' => $result['user_name'],
                    'avatar' => $result['avatar'],
                    'color' => $result['color'],
                    'points' => (int)$result['total_points'],
                    'rounds' => (int)$result['runden_count']
                ];
                
                // Spieler sammeln
                if (!isset($players[$userId])) {
                    $players[$userId] = [
                        'id' => $userId,
                        'name' => $result['user_name'],
                        'avatar' => $result['avatar'],
                        'color' => $result['color'],
                        'games' => [],
                        'total_points' => 0,
                        'game_count' => 0,
                        'best_score' => null,
                        'worst_score' => null,
                        'wins' => 0
                    ];
                }
                
                $points = (int)$result['total_points'];
                $players[$userId]['games'][] = [
                    'game_id' => $gameId,
                    'game_name' => $result['spiel_name'],
                    'datum' => $result['datum'],
                    'points' => $points,
                    'rounds' => (int)$result['runden_count']
                ];
                
                $players[$userId]['total_points'] += $points;
                $players[$userId]['game_count']++;
                
                if ($players[$userId]['best_score'] === null || $points < $players[$userId]['best_score']) {
                    $players[$userId]['best_score'] = $points;
                }
                
                if ($players[$userId]['worst_score'] === null || $points > $players[$userId]['worst_score']) {
                    $players[$userId]['worst_score'] = $points;
                }
            }
            
            // Gewinner für jedes Spiel bestimmen (niedrigste Punkte = Gewinner bei 6 Nimmt!)
            // WICHTIG: Hier müssen wir die sortierten players-Arrays verwenden, nicht die ursprünglichen
            foreach ($games as $gameId => &$game) {
                if (count($game['players']) > 0) {
                    // Spieler nach Punkten sortieren (niedrigste zuerst)
                    usort($game['players'], function($a, $b) {
                        return $a['points'] <=> $b['points'];
                    });
                    
                    // Gewinner ist der Spieler mit den niedrigsten Punkten
                    $winnerId = $game['players'][0]['user_id'];
                    if (isset($players[$winnerId])) {
                        $players[$winnerId]['wins']++;
                    }
                }
            }
            unset($game); // Referenz löschen
            
            // Durchschnittspunkte berechnen
            foreach ($players as &$player) {
                $player['average_points'] = $player['game_count'] > 0 
                    ? round($player['total_points'] / $player['game_count'], 1) 
                    : 0;
            }
            
            // Spieler nach Durchschnitt sortieren (beste zuerst = niedrigste Punkte)
            // Zuerst nach Durchschnitt, dann nach wins als Tiebreaker
            usort($players, function($a, $b) {
                // Zuerst nach Durchschnitt
                $avgCompare = $a['average_points'] <=> $b['average_points'];
                if ($avgCompare !== 0) {
                    return $avgCompare;
                }
                // Bei gleichem Durchschnitt: mehr Wins ist besser
                return $b['wins'] <=> $a['wins'];
            });
            
            // Sicherstellen, dass wir keine Duplikate haben (basierend auf user_id)
            $uniquePlayers = [];
            foreach ($players as $player) {
                $playerId = $player['id'];
                if (!isset($uniquePlayers[$playerId])) {
                    $uniquePlayers[$playerId] = $player;
                } else {
                    // Falls Duplikat: Werte zusammenführen
                    $uniquePlayers[$playerId]['game_count'] += $player['game_count'];
                    $uniquePlayers[$playerId]['total_points'] += $player['total_points'];
                    $uniquePlayers[$playerId]['wins'] += $player['wins'];
                    if ($player['best_score'] !== null && ($uniquePlayers[$playerId]['best_score'] === null || $player['best_score'] < $uniquePlayers[$playerId]['best_score'])) {
                        $uniquePlayers[$playerId]['best_score'] = $player['best_score'];
                    }
                    if ($player['worst_score'] !== null && ($uniquePlayers[$playerId]['worst_score'] === null || $player['worst_score'] > $uniquePlayers[$playerId]['worst_score'])) {
                        $uniquePlayers[$playerId]['worst_score'] = $player['worst_score'];
                    }
                    // Games-Array zusammenführen
                    $uniquePlayers[$playerId]['games'] = array_merge($uniquePlayers[$playerId]['games'], $player['games']);
                }
            }
            
            // Durchschnitt neu berechnen nach Zusammenführung
            foreach ($uniquePlayers as &$player) {
                $player['average_points'] = $player['game_count'] > 0 
                    ? round($player['total_points'] / $player['game_count'], 1) 
                    : 0;
            }
            unset($player);
            
            // Nochmal sortieren nach der Zusammenführung
            usort($uniquePlayers, function($a, $b) {
                $avgCompare = $a['average_points'] <=> $b['average_points'];
                if ($avgCompare !== 0) {
                    return $avgCompare;
                }
                return $b['wins'] <=> $a['wins'];
            });
            
            // Für Chart: Datenpunkte pro Spieler pro Spiel (nach der Deduplizierung)
            $chartData = [];
            $chartLabels = [];
            $gameList = array_values($games);
            
            foreach ($gameList as $game) {
                $gameDate = date('d.m.Y', strtotime($game['datum']));
                $chartLabels[] = $gameDate;
            }
            
            // Für jeden unique Spieler eine Datenreihe erstellen
            foreach ($uniquePlayers as $player) {
                $dataPoints = [];
                foreach ($gameList as $game) {
                    $found = false;
                    foreach ($game['players'] as $gamePlayer) {
                        if ($gamePlayer['user_id'] == $player['id']) {
                            $dataPoints[] = $gamePlayer['points'];
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $dataPoints[] = null; // Spieler war nicht in diesem Spiel
                    }
                }
                
                $chartData[] = [
                    'label' => $player['name'],
                    'data' => $dataPoints,
                    'borderColor' => $player['color'] ?: '#007AFF',
                    'backgroundColor' => ($player['color'] ?: '#007AFF') . '1A',
                    'avatar' => $player['avatar'],
                    'playerId' => $player['id']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'games' => array_values($games),
                'players' => array_values($uniquePlayers),
                'chart' => [
                    'labels' => $chartLabels,
                    'datasets' => $chartData
                ]
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception('Unbekannte Aktion.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

// Achievement-Prüfung Funktionen
function checkAchievements($db, $user_id, $context = []) {
    // Hole alle Achievement-Typen
    $stmt = $db->query("SELECT * FROM achievement_types");
    $allAchievements = $stmt->fetchAll();
    
    // Hole bereits erhaltene Achievements
    $stmt = $db->prepare("SELECT achievement_id FROM user_achievements WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $earnedIds = array_column($stmt->fetchAll(), 'achievement_id');
    
    $newAchievements = [];
    
    foreach ($allAchievements as $achievement) {
        // Überspringe wenn bereits erhalten
        if (in_array($achievement['id'], $earnedIds)) {
            continue;
        }
        
        $earned = false;
        
        switch ($achievement['condition_type']) {
            case 'game_points':
                // Prüfe ob Spieler in einem Spiel X Punkte erreicht hat
                if (isset($context['game_id']) && isset($context['total_points'])) {
                    $targetPoints = intval($achievement['condition_value']);
                    if ($context['total_points'] >= $targetPoints) {
                        $earned = true;
                    }
                }
                break;
                
            case 'attendance_first':
                // Vereinfachte und sichere Logik: Nutzer hat die meisten Anwesenheiten (ohne Gleichstand)
                // Eigene Anwesenheiten zählen
                $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM anwesenheiten WHERE user_id = ? AND status = 'anwesend'");
                $stmt->execute([$user_id]);
                $userCount = (int)($stmt->fetch()['cnt'] ?? 0);
                
                if ($userCount > 0) {
                    // Prüfe aggregiert, ob jemand mehr/gleich viele Anwesenheiten hat
                    $stmt = $db->prepare(
                        "SELECT
                            SUM(CASE WHEN cnt > ? THEN 1 ELSE 0 END) AS with_more,
                            SUM(CASE WHEN cnt = ? THEN 1 ELSE 0 END) AS with_same
                        FROM (
                            SELECT user_id, COUNT(*) AS cnt
                            FROM anwesenheiten
                            WHERE status = 'anwesend' AND user_id != ?
                            GROUP BY user_id
                        ) agg"
                    );
                    $stmt->execute([$userCount, $userCount, $user_id]);
                    $row = $stmt->fetch();
                    $withMore = (int)($row['with_more'] ?? 0);
                    $withSame = (int)($row['with_same'] ?? 0);
                    if ($withMore === 0 && $withSame === 0) {
                        $earned = true;
                    }
                }
                break;
                
            case 'attendance_never':
                // Prüfe ob Spieler nie anwesend war
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count
                    FROM anwesenheiten
                    WHERE user_id = ? AND status = 'anwesend'
                ");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch();
                
                if ($result['count'] == 0) {
                    $earned = true;
                }
                break;
                
            case 'attendance_count':
                // Prüfe ob Spieler X Anwesenheiten erreicht hat
                $targetCount = intval($achievement['condition_value']);
                $stmt = $db->prepare("
                    SELECT COUNT(*) as count
                    FROM anwesenheiten
                    WHERE user_id = ? AND status = 'anwesend'
                ");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch();
                
                if ($result['count'] >= $targetCount) {
                    $earned = true;
                }
                break;
                
            case 'game_wins':
                // Prüfe ob Spieler X Spiele gewonnen hat
                $targetWins = intval($achievement['condition_value']);
                $stmt = $db->prepare("
                    SELECT COUNT(*) as wins
                    FROM (
                        SELECT st.spiel_id, st.user_id, SUM(sr.punkte) as total_points
                        FROM spiel_teilnahme st
                        LEFT JOIN spiel_runden sr ON st.id = sr.spiel_teilnahme_id
                        JOIN spiele s ON st.spiel_id = s.id
                        WHERE s.status = 'beendet' AND st.user_id = ?
                        GROUP BY st.spiel_id, st.user_id
                    ) as player_scores
                    WHERE player_scores.total_points = (
                        SELECT MIN(SUM(sr2.punkte))
                        FROM spiel_teilnahme st2
                        LEFT JOIN spiel_runden sr2 ON st2.id = sr2.spiel_teilnahme_id
                        WHERE st2.spiel_id = player_scores.spiel_id
                        GROUP BY st2.user_id
                    )
                ");
                $stmt->execute([$user_id]);
                $result = $stmt->fetch();
                
                if ($result['wins'] >= $targetWins) {
                    $earned = true;
                }
                break;
        }
        
        if ($earned) {
            // Achievement verleihen
            $stmt = $db->prepare("
                INSERT INTO user_achievements (user_id, achievement_id)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE earned_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$user_id, $achievement['id']]);
            $newAchievements[] = $achievement;
        }
    }
    
    return $newAchievements;
}

