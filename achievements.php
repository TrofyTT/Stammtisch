<?php
require_once 'config.php';
requireAuth();

if (!$_SESSION['is_admin']) {
    header('Location: dashboard.php');
    exit;
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achievements - Admin - Stammtisch</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">STAMMTISCH - ACHIEVEMENTS</h1>
            <div class="nav-right">
                <?php
                $user_avatar = $user['avatar'] ?? null;
                $avatar_url = $user_avatar ? 'uploads/avatars/' . htmlspecialchars($user_avatar) : 'assets/img/default-avatar.svg';
                ?>
                <img src="<?= $avatar_url ?>" alt="<?= htmlspecialchars($_SESSION['user_name']) ?>" class="nav-avatar">
                <button class="nav-toggle" id="navToggle" aria-label="Menü öffnen">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="nav-menu" id="navMenu">
                    <a href="admin.php" class="nav-link">← Zurück zu Admin</a>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="games.php" class="nav-link">Spiele</a>
                    <a href="stats.php" class="nav-link">Statistiken</a>
                    <a href="members.php" class="nav-link">Mitglieder</a>
                    <a href="logout.php" class="nav-link">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h2>Achievements verwalten</h2>
            <button class="btn btn-primary" onclick="openCreateModal()">+ Neues Achievement</button>
        </div>
        
        <div id="achievementsList" class="achievements-grid">
            <!-- Wird per JS befüllt -->
        </div>
    </div>
    
    <!-- Modal für Create/Edit -->
    <div id="achievementModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Neues Achievement</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form id="achievementForm">
                <input type="hidden" id="achievement_id" name="achievement_id">
                
                <div class="form-group">
                    <label for="achievement_name">Name *</label>
                    <input type="text" id="achievement_name" name="name" required placeholder="z.B. Er frisst alles">
                </div>
                
                <div class="form-group">
                    <label for="achievement_description">Beschreibung</label>
                    <textarea id="achievement_description" name="description" rows="3" placeholder="Beschreibung des Achievements"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="achievement_condition_type">Bedingungstyp *</label>
                    <select id="achievement_condition_type" name="condition_type" required onchange="updateConditionValue()">
                        <option value="">-- Bitte wählen --</option>
                        <option value="game_points">Spiel-Punkte erreicht</option>
                        <option value="attendance_first">Erster in Anwesenheit</option>
                        <option value="attendance_never">Nie dabei gewesen</option>
                        <option value="attendance_count">Anwesenheiten erreicht</option>
                        <option value="game_wins">Spiele gewonnen</option>
                    </select>
                    <small style="color: var(--text-secondary); font-size: 12px;">Die Logik wird automatisch angewendet</small>
                </div>
                
                <div class="form-group" id="conditionValueGroup" style="display: none;">
                    <label for="achievement_condition_value">Wert</label>
                    <input type="number" id="achievement_condition_value" name="condition_value" placeholder="z.B. 60 für Punkte, 10 für Anwesenheiten">
                    <small style="color: var(--text-secondary); font-size: 12px;">Für punktbasierte oder zählbasierte Achievements</small>
                </div>
                
                <div class="form-group">
                    <label for="achievement_icon">Icon (Emoji)</label>
                    <input type="text" id="achievement_icon" name="icon" placeholder="🏆" maxlength="2">
                    <small style="color: var(--text-secondary); font-size: 12px;">Ein Emoji-Symbol für das Achievement</small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary modal-cancel" onclick="closeModal()">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/nav.js"></script>
    <script src="assets/js/achievements.js"></script>
</body>
</html>

