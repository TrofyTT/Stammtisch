<?php
require_once 'config.php';

// Wenn bereits eingeloggt, zum Dashboard weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Login verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Bitte E-Mail und Passwort eingeben.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            
            // Last login aktualisieren
            $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Ungültige E-Mail oder Passwort.';
        }
    }
}

// Registrierung verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($email) || empty($name) || empty($password)) {
        $error = 'Bitte alle Felder ausfüllen.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ungültige E-Mail-Adresse.';
    } elseif ($password !== $password_confirm) {
        $error = 'Passwörter stimmen nicht überein.';
    } elseif (strlen($password) < 6) {
        $error = 'Passwort muss mindestens 6 Zeichen lang sein.';
    } else {
        $db = getDB();
        // Prüfen ob E-Mail bereits existiert
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Diese E-Mail-Adresse ist bereits registriert.';
        } else {
            // Ersten Benutzer zum Admin machen
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch()['count'];
            $is_admin = ($count === 0);
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (email, password_hash, name, is_admin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$email, $password_hash, $name, $is_admin]);
            
            $success = 'Registrierung erfolgreich! Du kannst dich jetzt einloggen.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stammtisch - Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="login-container">
            <div class="logo-area">
                <h1 class="logo">STAMMTISCH</h1>
                <p class="subtitle">Anwesenheits-Tracking System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab-btn active" data-tab="login">Login</button>
                <button class="tab-btn" data-tab="register">Registrieren</button>
            </div>
            
            <!-- Login Form -->
            <form id="loginForm" class="tab-content active" method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="login_email">E-Mail</label>
                    <input type="email" id="login_email" name="email" required autofocus>
                </div>
                <div class="form-group">
                    <label for="login_password">Passwort</label>
                    <input type="password" id="login_password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Anmelden</button>
            </form>
            
            <!-- Register Form -->
            <form id="registerForm" class="tab-content" method="POST">
                <input type="hidden" name="action" value="register">
                <div class="form-group">
                    <label for="register_name">Name</label>
                    <input type="text" id="register_name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="register_email">E-Mail</label>
                    <input type="email" id="register_email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="register_password">Passwort</label>
                    <input type="password" id="register_password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="register_password_confirm">Passwort bestätigen</label>
                    <input type="password" id="register_password_confirm" name="password_confirm" required minlength="6">
                </div>
                <button type="submit" class="btn btn-primary">Registrieren</button>
            </form>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>

