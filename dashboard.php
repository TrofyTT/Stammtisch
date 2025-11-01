<?php
require_once 'config.php';
requireAuth();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stammtisch Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">STAMMTISCH</h1>
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
                    <a href="games.php" class="nav-link">Spiele</a>
                    <a href="stats.php" class="nav-link">Statistiken</a>
                    <?php if ($_SESSION['is_admin']): ?>
                        <a href="members.php" class="nav-link">Mitglieder</a>
                        <a href="admin.php" class="nav-link">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="nav-link">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <!-- 4-Spalten Dashboard Layout -->
        <div class="dashboard-grid">
            <!-- 1/4: Spiele Achievements -->
            <div class="dashboard-card" id="gameAchievements">
                <h3>Spiele Achievements</h3>
                <div class="achievements-list" id="gameAchievementsList">
                    <!-- Wird per JS befüllt -->
                </div>
            </div>
            
            <!-- 1/4: Stammtisch Achievements -->
            <div class="dashboard-card" id="stammtischAchievements">
                <h3>Stammtisch Achievements</h3>
                <div class="achievements-list" id="stammtischAchievementsList">
                    <!-- Wird per JS befüllt -->
                </div>
            </div>
            
            <!-- 1/4: Rangliste (All-Time + Letzter Gewinner) -->
            <div class="dashboard-card" id="rankingWinners">
                <h3>Rangliste</h3>
                <div id="winnerContent">
                    <!-- Wird per JS befüllt -->
                </div>
            </div>

            <!-- 1/4: Looser (All-Time + Letzter Verlierer) -->
            <div class="dashboard-card" id="rankingLosers">
                <h3>Looser</h3>
                <div id="loserContent">
                    <!-- Wird per JS befüllt -->
                </div>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="stats-grid" id="quickStats">
            <!-- Wird per JS befüllt -->
        </div>

        <!-- Termine Section (unten) -->
        <div class="dashboard-header">
            <h2>Termine</h2>
            <button class="btn btn-primary" id="newTerminBtn">+ Neuer Termin</button>
        </div>

        <!-- Termine Liste -->
        <div class="termine-container" id="termineList">
            <!-- Wird per JS befüllt -->
        </div>
    </div>

    <!-- Modal für neuen Termin -->
    <div class="modal" id="terminModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Neuer Termin</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="terminForm">
                <input type="hidden" id="termin_id" name="termin_id">
                <div class="form-group">
                    <label for="termin_name">Name (z.B. "Stammtischrunde 4")</label>
                    <input type="text" id="termin_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="termin_ort">Ort</label>
                    <input type="text" id="termin_ort" name="ort" required>
                </div>
                <div class="form-group">
                    <label for="termin_datum">Datum</label>
                    <input type="date" id="termin_datum" name="datum" required>
                </div>
                <div class="form-group">
                    <label for="termin_uhrzeit">Uhrzeit</label>
                    <input type="time" id="termin_uhrzeit" name="uhrzeit" required>
                </div>
                <div class="form-group">
                    <label for="termin_beschreibung">Beschreibung (optional)</label>
                    <textarea id="termin_beschreibung" name="beschreibung" rows="3"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary modal-cancel">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Anwesenheits-Modal -->
    <div class="modal" id="anwesenheitModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="anwesenheitTitle">Anwesenheit erfassen</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div id="anwesenheitContent">
                <!-- Wird per JS befüllt -->
            </div>
        </div>
    </div>

    <script src="assets/js/nav.js"></script>
    <script src="assets/js/dashboard.js"></script>
</body>
</html>

