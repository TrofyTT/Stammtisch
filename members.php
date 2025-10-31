<?php
require_once 'config.php';
requireAdmin(); // Nur Admins können Mitglieder verwalten

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mitglieder - Stammtisch</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <h1 class="nav-logo">STAMMTISCH - MITGLIEDER</h1>
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
                    <?php if ($_SESSION['is_admin']): ?>
                        <a href="admin.php" class="nav-link">Admin</a>
                    <?php endif; ?>
                    <a href="logout.php" class="nav-link">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <div class="members-header">
            <h2>Mitglieder verwalten</h2>
            <button class="btn btn-primary" id="newMemberBtn">+ Neues Mitglied</button>
        </div>
        
        <div class="members-grid" id="membersList">
            <!-- Wird per JS befüllt -->
        </div>
    </div>
    
    <!-- Modal für Mitglied -->
    <div class="modal" id="memberModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Neues Mitglied</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="memberForm">
                <input type="hidden" id="member_id" name="member_id">
                
                <div class="form-group">
                    <label for="member_name">Name *</label>
                    <input type="text" id="member_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="member_email">E-Mail *</label>
                    <input type="email" id="member_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="member_rang">Rang</label>
                    <input type="text" id="member_rang" name="rang" placeholder="z.B. Stammgast, Neuling, VIP...">
                </div>
                
                <div class="form-group">
                    <label for="member_color">Farbe (Hex)</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input type="color" id="member_color_picker" value="#007AFF" style="width: 60px; height: 40px; border: none; border-radius: 8px; cursor: pointer;">
                        <input type="text" id="member_color" name="color" placeholder="#007AFF" pattern="^#[0-9A-Fa-f]{6}$" style="flex: 1;">
                    </div>
                    <small style="color: var(--text-secondary); font-size: 12px;">Eindeutige Farbe für Charts und Visualisierungen</small>
                </div>
                
                <div class="form-group">
                    <label for="member_password">Passwort</label>
                    <input type="password" id="member_password" name="password" placeholder="Nur beim Erstellen erforderlich">
                    <small style="color: var(--text-secondary); font-size: 12px;">Leer lassen, um nicht zu ändern</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="member_is_admin" name="is_admin">
                        Admin-Rechte
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary modal-cancel">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Avatar Upload Modal -->
    <div class="modal" id="avatarModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h3>Profilbild ändern</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div style="padding: 20px;">
                <div id="avatarCropContainer" style="display: none; margin-bottom: 20px;">
                    <div style="text-align: center; background: var(--bg); padding: 20px; border-radius: 10px; border: 1px solid var(--border);">
                        <img id="avatarCropImage" style="max-width: 100%; max-height: 400px; display: block;">
                    </div>
                    <div style="text-align: center; margin-top: 15px; color: var(--text-secondary); font-size: 13px;">
                        Ziehe den Rahmen, um den Bereich auszuwählen. Das Bild wird auf 512x512 Pixel rund zugeschnitten.
                    </div>
                </div>
                <div id="avatarPreviewContainer" style="text-align: center; margin-bottom: 20px;">
                    <img id="avatarPreview" src="" alt="Avatar Preview" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border); margin: 0 auto 20px; display: none;">
                </div>
                <div style="text-align: center; margin-bottom: 20px;">
                    <label for="avatarFileInput" class="btn btn-primary">
                        📷 Bild auswählen
                    </label>
                    <input type="file" id="avatarFileInput" accept="image/*" style="display: none;">
                </div>
                <div id="avatarUploadStatus" style="margin-top: 15px;"></div>
                <div id="avatarCropActions" style="display: none; text-align: center; margin-top: 20px;">
                    <button class="btn btn-secondary" id="avatarCropCancel">Abbrechen</button>
                    <button class="btn btn-primary" id="avatarCropApply">Zuschneiden & Speichern</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
    <script>
        // Color Picker Synchronisation
        document.addEventListener('DOMContentLoaded', () => {
            const colorPicker = document.getElementById('member_color_picker');
            const colorInput = document.getElementById('member_color');
            
            if (colorPicker && colorInput) {
                colorPicker.addEventListener('input', (e) => {
                    colorInput.value = e.target.value.toUpperCase();
                });
                
                colorInput.addEventListener('input', (e) => {
                    const value = e.target.value;
                    if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                        colorPicker.value = value;
                    }
                });
            }
        });
    </script>
    <script src="assets/js/nav.js"></script>
    <script src="assets/js/members.js"></script>
</body>
</html>

