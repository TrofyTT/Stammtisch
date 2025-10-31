document.addEventListener('DOMContentLoaded', () => {
    // Git Pull Button
    const gitPullBtn = document.getElementById('gitPullBtn');
    if (gitPullBtn) {
        gitPullBtn.addEventListener('click', handleGitPull);
    }
    loadUsers();
    loadSettings();
    
    document.getElementById('saveSettingsBtn').addEventListener('click', saveSettings);
});

function loadUsers() {
    fetch('api.php?action=get_users')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderUsers(data.users);
            }
        })
        .catch(err => console.error('Fehler:', err));
}

function renderUsers(users) {
    const container = document.getElementById('usersList');
    
    if (users.length === 0) {
        container.innerHTML = '<div class="alert">Keine Benutzer gefunden.</div>';
        return;
    }
    
    container.innerHTML = `
        <div class="users-list">
            ${users.map(user => {
                const avatarUrl = user.avatar ? `uploads/avatars/${escapeHtml(user.avatar)}` : 'assets/img/default-avatar.svg';
                return `
                <div class="user-item">
                    <div class="user-item-left">
                        <div class="user-avatar-container">
                            <img src="${avatarUrl}" alt="${escapeHtml(user.name)}" class="user-avatar" id="avatar-${user.id}">
                            <div class="avatar-overlay">
                                <label for="avatar-upload-${user.id}" class="avatar-upload-btn">
                                    <span>📷</span>
                                    <span>Bild ändern</span>
                                </label>
                                <input type="file" id="avatar-upload-${user.id}" accept="image/*" style="display: none;" onchange="uploadAvatar(${user.id}, this)">
                            </div>
                        </div>
                        <div class="user-info">
                            <div class="user-info-name">
                                ${escapeHtml(user.name)}
                                ${user.is_admin ? '<span class="user-badge">Admin</span>' : ''}
                            </div>
                            <div class="user-info-email">${escapeHtml(user.email)}</div>
                            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 5px;">
                                Registriert: ${formatDate(user.created_at)}
                                ${user.last_login ? ` • Letzter Login: ${formatDateTime(user.last_login)}` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="user-actions">
                        ${!user.is_admin ? `
                            <button class="btn btn-small btn-secondary" onclick="toggleAdmin(${user.id}, true)">
                                Admin machen
                            </button>
                        ` : `
                            <button class="btn btn-small btn-secondary" onclick="toggleAdmin(${user.id}, false)">
                                Admin entfernen
                            </button>
                        `}
                    </div>
                </div>
            `}).join('')}
        </div>
    `;
}

function uploadAvatar(userId, input) {
    const file = input.files[0];
    if (!file) return;
    
    // Datei validieren
    if (!file.type.startsWith('image/')) {
        alert('Bitte wähle ein Bild aus!');
        return;
    }
    
    if (file.size > 5 * 1024 * 1024) {
        alert('Bild ist zu groß (max. 5 MB)');
        return;
    }
    
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('user_id', userId);
    
    // Loading anzeigen
    const avatarImg = document.getElementById(`avatar-${userId}`);
    const originalSrc = avatarImg.src;
    avatarImg.style.opacity = '0.5';
    
    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Avatar aktualisieren
                avatarImg.src = data.url + '?t=' + Date.now();
                avatarImg.style.opacity = '1';
                
                // Input zurücksetzen
                input.value = '';
            } else {
                alert('Fehler: ' + data.error);
                avatarImg.src = originalSrc;
                avatarImg.style.opacity = '1';
            }
        })
        .catch(err => {
            alert('Fehler beim Upload');
            avatarImg.src = originalSrc;
            avatarImg.style.opacity = '1';
        });
}

function toggleAdmin(userId, makeAdmin) {
    const formData = new FormData();
    formData.append('action', 'toggle_admin');
    formData.append('user_id', userId);
    formData.append('is_admin', makeAdmin ? '1' : '0');
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadUsers();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Aktualisieren');
        });
}

function loadSettings() {
    fetch('api.php?action=get_settings')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Settings laden und in Formular einfügen
                if (data.settings.app_name) {
                    document.getElementById('setting_app_name').value = data.settings.app_name;
                }
            }
        })
        .catch(err => console.error('Fehler:', err));
}

function saveSettings() {
    const formData = new FormData();
    formData.append('action', 'save_settings');
    formData.append('app_name', document.getElementById('setting_app_name').value);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Einstellungen gespeichert!');
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Speichern');
        });
}

function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return date.toLocaleDateString('de-DE', options);
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    const options = { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('de-DE', options);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function handleGitPull() {
    const btn = document.getElementById('gitPullBtn');
    const statusDiv = document.getElementById('gitUpdateStatus');
    
    if (!btn || !statusDiv) return;
    
    // Bestätigung
    if (!confirm('Möchtest du wirklich ein Update von Git durchführen?\n\nDies lädt alle Änderungen vom Repository herunter.')) {
        return;
    }
    
    // Button deaktivieren
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ Lade Update...</span>';
    statusDiv.innerHTML = '<div class="alert alert-info">Update wird durchgeführt...</div>';
    
    fetch('api.php?action=git_pull', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<span>🔄 Update von Git laden</span>';
        
        if (data.success) {
            let statusHTML = '<div class="alert alert-success">✅ Update erfolgreich!</div>';
            
            if (data.output) {
                statusHTML += '<div class="update-output"><pre>' + escapeHtml(data.output) + '</pre></div>';
            }
            
            if (data.status) {
                statusHTML += '<div class="update-status-info"><strong>Git Status:</strong><pre>' + escapeHtml(data.status) + '</pre></div>';
            }
            
            statusDiv.innerHTML = statusHTML;
            
            // Optional: Seite neu laden nach 2 Sekunden
            setTimeout(() => {
                if (confirm('Update erfolgreich! Seite neu laden?')) {
                    window.location.reload();
                }
            }, 2000);
        } else {
            let errorHTML = '<div class="alert alert-danger">❌ Update fehlgeschlagen!</div>';
            
            if (data.output) {
                errorHTML += '<div class="update-output"><strong>Fehler:</strong><pre>' + escapeHtml(data.output) + '</pre></div>';
            }
            
            if (data.status) {
                errorHTML += '<div class="update-status-info"><strong>Git Status:</strong><pre>' + escapeHtml(data.status) + '</pre></div>';
            }
            
            statusDiv.innerHTML = errorHTML;
        }
    })
    .catch(err => {
        console.error('Fehler:', err);
        btn.disabled = false;
        btn.innerHTML = '<span>🔄 Update von Git laden</span>';
        statusDiv.innerHTML = '<div class="alert alert-danger">Fehler beim Update: ' + escapeHtml(err.message) + '</div>';
    });
}

window.toggleAdmin = toggleAdmin;
window.uploadAvatar = uploadAvatar;

