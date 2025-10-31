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
    <title>Spiele - Stammtisch</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">SPIELE</h1>
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
        <div class="games-header">
            <h2>Spiele</h2>
            <div class="games-filter">
                <button class="btn btn-small btn-secondary" id="filterAll" onclick="filterGames('all')">Alle</button>
                <button class="btn btn-small btn-secondary" id="filterActive" onclick="filterGames('aktiv')">Aktiv</button>
                <button class="btn btn-small btn-secondary" id="filterEnded" onclick="filterGames('beendet')">Beendet</button>
            </div>
        </div>
        
        <!-- Spiel-Kategorien -->
        <div class="game-categories">
            <div class="game-category-card" onclick="window.location.href='game.php'">
                <div class="game-category-icon">🎲</div>
                <h3>6 Nimmt!</h3>
                <p>Neues Spiel starten</p>
            </div>
            <!-- Hier können weitere Spiele hinzugefügt werden -->
        </div>
        
        <!-- Spiele-Liste -->
        <div class="games-list-section">
            <h3>Spiel-Historie</h3>
            <div id="gamesList" class="games-list">
                <!-- Wird per JS befüllt -->
            </div>
        </div>
    </div>
    
    <script src="assets/js/nav.js"></script>
    <script src="assets/js/games.js"></script>
</body>
</html>

