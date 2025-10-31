<?php
require_once 'config.php';
requireAdmin();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Stammtisch</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">STAMMTISCH - ADMIN</h1>
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
                    <a href="logout.php" class="nav-link">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="admin-section">
            <h2>Benutzer verwalten</h2>
            <div id="usersList">
                <!-- Wird per JS befüllt -->
            </div>
        </div>
        
        <div class="admin-section">
            <h2>Achievements verwalten</h2>
            <p>Verwalte die Achievements, die Spieler automatisch erhalten können.</p>
            <a href="achievements.php" class="btn btn-primary">Achievements öffnen</a>
        </div>
        
        <div class="admin-section">
            <h2>🔄 Updates</h2>
            <div class="update-section">
                <p>Lade die neueste Version von GitHub herunter und aktualisiere die Anwendung.</p>
                <a href="update.php" class="btn btn-primary">
                    🚀 Update-System öffnen
                </a>
            </div>
        </div>
        
        <div class="admin-section">
            <h2>Einstellungen</h2>
            <div class="settings-form">
                <div class="form-group">
                    <label>Anwendung-Name</label>
                    <input type="text" id="setting_app_name" placeholder="Stammtisch">
                </div>
                <button class="btn btn-primary" id="saveSettingsBtn">Einstellungen speichern</button>
            </div>
        </div>
    </div>
    
    <script src="assets/js/nav.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>

