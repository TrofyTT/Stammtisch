<?php
require_once 'config.php';
requireAuth();

// Benutzerdaten aus der Session holen
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken - Stammtisch</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">STAMMTISCH - STATISTIKEN</h1>
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
        <div class="stats-header">
            <h2>Detaillierte Statistiken</h2>
        </div>
        
        <div class="stats-grid-large">
        <div class="stat-card">
            <h3>Anwesenheitsquote nach Person</h3>
            <canvas id="chartPersonen"></canvas>
            <div id="chartPersonenLegend" style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;"></div>
        </div>
            
            <div class="stat-card">
                <h3>Status-Verteilung</h3>
                <canvas id="chartStatus"></canvas>
            </div>
            
            <div class="stat-card">
                <h3>Anwesenheiten über Zeit</h3>
                <canvas id="chartZeit"></canvas>
            </div>
            
            <div class="stat-card">
                <h3>Top Orte</h3>
                <canvas id="chartOrte"></canvas>
            </div>
        </div>
        
        <!-- 6 Nimmt! All Time Statistiken -->
        <div class="game-alltime-section">
            <h2>6 Nimmt! All Time Statistiken</h2>
            
            <div class="stat-card">
                <h3>Punkte-Entwicklung über alle Spiele</h3>
                <canvas id="chartGameAllTime"></canvas>
                <div id="chartGameAllTimeLegend" style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 15px; justify-content: center;"></div>
            </div>
            
            <div class="game-players-stats">
                <h3>Spieler-Statistiken</h3>
                <div id="gamePlayersStatsList" class="game-players-stats-list">
                    <!-- Wird per JS befüllt -->
                </div>
            </div>
        </div>
        
        <div class="stat-card full-width">
            <h3>Detaillierte Tabelle</h3>
            <div id="statsTable"></div>
        </div>
    </div>
    
    <script src="assets/js/nav.js"></script>
    <script src="assets/js/stats.js"></script>
</body>
</html>

