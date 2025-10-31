<?php
require_once 'config.php';
requireAuth();

$game_id = intval($_GET['id'] ?? 0);
if (!$game_id) {
    header('Location: games.php');
    exit;
}

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spiel-Statistiken - Stammtisch</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">SPIEL-STATISTIKEN</h1>
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
                    <a href="games.php" class="nav-link">← Zurück zu Spiele</a>
                    <a href="dashboard.php" class="nav-link">Dashboard</a>
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
        <div class="game-stats-header" id="gameStatsHeader">
            <!-- Wird per JS befüllt -->
        </div>
        
        <!-- Chart -->
        <div class="game-chart-container">
            <canvas id="gameStatsChart"></canvas>
        </div>
        
        <!-- Detaillierte Statistiken -->
        <div class="game-stats-grid" id="gameStatsGrid">
            <!-- Wird per JS befüllt -->
        </div>
        
        <!-- Spieler-Rankings -->
        <div class="players-ranking-section">
            <h3>Spieler-Rangliste</h3>
            <div id="playersRanking" class="players-ranking-list">
                <!-- Wird per JS befüllt -->
            </div>
        </div>
    </div>
    
    <script>
        const gameId = <?= $game_id ?>;
    </script>
    <script src="assets/js/nav.js"></script>
    <script src="assets/js/game_stats.js"></script>
</body>
</html>

