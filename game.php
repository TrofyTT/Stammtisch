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
    <title>6 Nimmt! - Stammtisch</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">6 NIMMT!</h1>
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
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
                    <a href="games.php" class="nav-link">Spiele</a>
                    <a href="stats.php" class="nav-link">Statistiken</a>
                    <a href="members.php" class="nav-link">Mitglieder</a>
                    <?php if ($user['is_admin']): ?>
                        <a href="admin.php" class="nav-link">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="nav-link">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <!-- Spiel erstellen / Spieler auswählen -->
        <div id="gameSetup" class="game-section">
            <h2>Neues Spiel</h2>
            <div class="form-group">
                <label for="gameDate">Datum</label>
                <input type="date" id="gameDate" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Spieler auswählen (Reihenfolge beachten!)</label>
                <div id="availablePlayers" class="players-grid">
                    <!-- Wird per JS befüllt -->
                </div>
            </div>
            <div id="selectedPlayersContainer" style="margin-top: 20px;">
                <label>Ausgewählte Spieler (in Reihenfolge):</label>
                <div id="selectedPlayers" class="selected-players-list">
                    <div class="empty-state">Keine Spieler ausgewählt</div>
                </div>
            </div>
            <div style="margin-top: 20px;">
                <button class="btn btn-primary" id="startGameBtn" disabled>Spiel starten</button>
            </div>
        </div>
        
        <!-- Spiel läuft -->
        <div id="gameActive" class="game-section" style="display: none;">
            <div class="game-header">
                <h2>6 Nimmt! - Runde <span id="currentRound">1</span></h2>
                <button class="btn btn-danger" id="endGameBtn">Spiel beenden</button>
            </div>
            
            <!-- Runden-Info -->
            <div id="roundInfo" class="round-info" style="display: none;">
                <div class="round-info-content">
                    <span>✓ Runde abgeschlossen</span>
                </div>
            </div>
            
            <!-- Live Chart mit Avataren -->
            <div class="game-chart-container">
                <canvas id="gameChart"></canvas>
                <div id="chartLegend" class="chart-legend">
                    <!-- Wird per JS befüllt -->
                </div>
            </div>
            
            <!-- Aktueller Spieler -->
            <div class="current-player-section">
                <div id="currentPlayerInfo" class="current-player-card">
                    <!-- Wird per JS befüllt -->
                </div>
                <div class="points-input-container">
                    <label for="pointsInput">Punkte eingeben:</label>
                    <input type="number" id="pointsInput" placeholder="z.B. 60" min="0" step="1">
                    <button class="btn btn-primary" id="submitPointsBtn">Eingeben</button>
                </div>
            </div>
            
            <!-- Spieler-Status Liste -->
            <div class="players-status-list">
                <h3>Spieler-Status</h3>
                <div id="playersStatus" class="players-status-grid">
                    <!-- Wird per JS befüllt -->
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/game.js"></script>
</body>
</html>

