// Globale Variablen
let termine = [];
let currentTerminId = null;

// Init
document.addEventListener('DOMContentLoaded', () => {
    loadTermine();
    loadStats();
    loadDashboardCards();
    setupModals();
    setupEventListeners();
});

// Modals Setup
function setupModals() {
    const terminModal = document.getElementById('terminModal');
    const anwesenheitModal = document.getElementById('anwesenheitModal');
    
    // Termin Modal
    document.getElementById('newTerminBtn').addEventListener('click', () => {
        openTerminModal();
    });
    
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', () => {
            closeModals();
        });
    });
    
    document.querySelectorAll('.modal-cancel').forEach(btn => {
        btn.addEventListener('click', () => {
            closeModals();
        });
    });
    
    // Click outside to close
    [terminModal, anwesenheitModal].forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModals();
            }
        });
    });
    
    // Termin Form Submit
    document.getElementById('terminForm').addEventListener('submit', (e) => {
        e.preventDefault();
        saveTermin();
    });
}

function closeModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
    currentTerminId = null;
}

function openTerminModal(termin = null) {
    const modal = document.getElementById('terminModal');
    const form = document.getElementById('terminForm');
    const title = document.getElementById('modalTitle');
    
    if (termin) {
        title.textContent = 'Termin bearbeiten';
        document.getElementById('termin_id').value = termin.id;
        document.getElementById('termin_name').value = termin.name;
        document.getElementById('termin_ort').value = termin.ort;
        document.getElementById('termin_datum').value = termin.datum;
        document.getElementById('termin_uhrzeit').value = termin.uhrzeit;
        document.getElementById('termin_beschreibung').value = termin.beschreibung || '';
        currentTerminId = termin.id;
    } else {
        title.textContent = 'Neuer Termin';
        form.reset();
        document.getElementById('termin_id').value = '';
        currentTerminId = null;
    }
    
    modal.classList.add('active');
}

function openAnwesenheitModal(termin) {
    const modal = document.getElementById('anwesenheitModal');
    const title = document.getElementById('anwesenheitTitle');
    const content = document.getElementById('anwesenheitContent');
    
    title.textContent = `Anwesenheit: ${termin.name}`;
    currentTerminId = termin.id;
    
    // Loading
    content.innerHTML = '<div style="text-align: center; padding: 20px;">Lädt...</div>';
    modal.classList.add('active');
    
    // Daten laden
    fetch(`api.php?action=get_termin_anwesenheiten&termin_id=${termin.id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderAnwesenheitForm(data.users, data.anwesenheiten);
            } else {
                content.innerHTML = `<div class="alert alert-error">${data.error}</div>`;
            }
        })
        .catch(err => {
            content.innerHTML = `<div class="alert alert-error">Fehler beim Laden</div>`;
        });
}

function renderAnwesenheitForm(users, anwesenheiten) {
    const content = document.getElementById('anwesenheitContent');
    
    let html = '<div class="anwesenheit-form">';
    
    users.forEach(user => {
        const anwesenheit = anwesenheiten[user.id];
        const status = anwesenheit ? anwesenheit.status : '';
        
        html += `
            <div class="user-anwesenheit-item">
                <div class="user-anwesenheit-item-name">${escapeHtml(user.name)}</div>
                <div class="user-anwesenheit-status-buttons">
                    <button class="status-btn anwesend ${status === 'anwesend' ? 'active' : ''}"
                            data-user-id="${user.id}" data-status="anwesend">
                        Anwesend
                    </button>
                    <button class="status-btn nicht-anwesend ${status === 'nicht_anwesend' ? 'active' : ''}"
                            data-user-id="${user.id}" data-status="nicht_anwesend">
                        Nicht Anwesend
                    </button>
                    <button class="status-btn unentschuldigt ${status === 'unentschuldigt' ? 'active' : ''}"
                            data-user-id="${user.id}" data-status="unentschuldigt">
                        Unentschuldigt
                    </button>
                </div>
            </div>
        `;
    });
    
    html += `
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary modal-cancel">Abbrechen</button>
            <button type="button" class="btn btn-primary" id="saveAnwesenheitBtn">Speichern</button>
        </div>
    </div>`;
    
    content.innerHTML = html;
    
    // Event Listeners für Status Buttons
    content.querySelectorAll('.status-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const item = btn.closest('.user-anwesenheit-item');
            item.querySelectorAll('.status-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
    
    // Save Button
    document.getElementById('saveAnwesenheitBtn').addEventListener('click', saveAnwesenheiten);
}

function saveAnwesenheiten() {
    const items = document.querySelectorAll('.user-anwesenheit-item');
    const data = [];
    
    items.forEach(item => {
        const activeBtn = item.querySelector('.status-btn.active');
        if (activeBtn) {
            data.push({
                user_id: activeBtn.dataset.userId,
                status: activeBtn.dataset.status
            });
        }
    });
    
    // Alle Anwesenheiten speichern
    const promises = data.map(item => {
        const formData = new FormData();
        formData.append('action', 'save_anwesenheit');
        formData.append('termin_id', currentTerminId);
        formData.append('user_id', item.user_id);
        formData.append('status', item.status);
        
        return fetch('api.php', {
            method: 'POST',
            body: formData
        }).then(res => res.json());
    });
    
    Promise.all(promises).then(() => {
        closeModals();
        loadTermine();
    });
}

// Event Listeners
function setupEventListeners() {
    // Stats Link (später)
}

// API Calls
function loadTermine() {
    fetch('api.php?action=get_termine')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                termine = data.data;
                renderTermine();
            }
        })
        .catch(err => console.error('Fehler:', err));
}

function loadDashboardCards() {
    // Alle 4 Karten gleichzeitig laden
    Promise.all([
        loadGameAchievements(),
        loadStammtischAchievements(),
        loadLastGameStats()
    ]).catch(err => console.error('Fehler beim Laden der Dashboard-Karten:', err));
}

function loadGameAchievements() {
    return fetch('api.php?action=get_user_achievements')
        .then(res => res.json())
        .then(data => {
            console.log('Game Achievements Data:', data);
            if (data.success && data.game_achievements && data.game_achievements.length > 0) {
                renderGameAchievements(data.game_achievements);
            } else {
                document.getElementById('gameAchievementsList').innerHTML = '<div class="empty-state-small">Noch keine</div>';
            }
        })
        .catch(err => {
            console.error('Fehler beim Laden der Spiel-Achievements:', err);
            document.getElementById('gameAchievementsList').innerHTML = '<div class="empty-state-small">Fehler beim Laden</div>';
        });
}

function renderGameAchievements(achievements) {
    const container = document.getElementById('gameAchievementsList');
    
    if (!achievements || achievements.length === 0) {
        container.innerHTML = '<div class="empty-state-small">Noch keine</div>';
        return;
    }
    
    container.innerHTML = achievements.map(achievement => {
        const avatarUrl = achievement.user_avatar 
            ? `uploads/avatars/${escapeHtml(achievement.user_avatar)}` 
            : 'assets/img/default-avatar.svg';
        const userColor = achievement.user_color || '#007AFF';
        
        let earnedDate = 'Heute';
        try {
            if (achievement.earned_at) {
                const date = new Date(achievement.earned_at);
                earnedDate = date.toLocaleDateString('de-DE');
            }
        } catch (e) {
            console.warn('Fehler beim Parsen des Datums:', e);
        }
        
        return `
            <div class="dashboard-achievement-card">
                <img src="${avatarUrl}" alt="${escapeHtml(achievement.user_name)}" class="dashboard-achievement-avatar" style="border-color: ${userColor};">
                <div class="dashboard-achievement-info">
                    <div class="dashboard-achievement-name">${escapeHtml(achievement.name)}</div>
                    <div class="dashboard-achievement-date">${earnedDate}</div>
                </div>
                <div class="dashboard-achievement-icon">${achievement.icon || '🏆'}</div>
            </div>
        `;
    }).join('');
}

function loadStammtischAchievements() {
    return fetch('api.php?action=get_user_achievements')
        .then(res => res.json())
        .then(data => {
            console.log('Stammtisch Achievements Data:', data);
            if (data.success && data.stammtisch_achievements && data.stammtisch_achievements.length > 0) {
                renderStammtischAchievements(data.stammtisch_achievements);
            } else {
                console.log('Keine Stammtisch-Achievements gefunden. Alle Achievements:', data.achievements);
                document.getElementById('stammtischAchievementsList').innerHTML = '<div class="empty-state-small">Noch keine</div>';
            }
        })
        .catch(err => {
            console.error('Fehler beim Laden der Stammtisch-Achievements:', err);
            document.getElementById('stammtischAchievementsList').innerHTML = '<div class="empty-state-small">Fehler beim Laden</div>';
        });
}

function renderStammtischAchievements(achievements) {
    const container = document.getElementById('stammtischAchievementsList');
    
    if (!achievements || achievements.length === 0) {
        container.innerHTML = '<div class="empty-state-small">Noch keine</div>';
        return;
    }
    
    container.innerHTML = achievements.map(achievement => {
        const avatarUrl = achievement.user_avatar 
            ? `uploads/avatars/${escapeHtml(achievement.user_avatar)}` 
            : 'assets/img/default-avatar.svg';
        const userColor = achievement.user_color || '#007AFF';
        
        let earnedDate = 'Heute';
        try {
            if (achievement.earned_at) {
                const date = new Date(achievement.earned_at);
                earnedDate = date.toLocaleDateString('de-DE');
            }
        } catch (e) {
            console.warn('Fehler beim Parsen des Datums:', e);
        }
        
        return `
            <div class="dashboard-achievement-card">
                <img src="${avatarUrl}" alt="${escapeHtml(achievement.user_name)}" class="dashboard-achievement-avatar" style="border-color: ${userColor};">
                <div class="dashboard-achievement-info">
                    <div class="dashboard-achievement-name">${escapeHtml(achievement.name)}</div>
                    <div class="dashboard-achievement-date">${earnedDate}</div>
                </div>
                <div class="dashboard-achievement-icon">${achievement.icon || '🏆'}</div>
            </div>
        `;
    }).join('');
}

function loadLastGameStats() {
    return fetch('api.php?action=get_last_stammtisch_game_stats')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderWinners(data.alltime_winner, data.last_winner);
                renderLosers(data.alltime_loser, data.last_loser);
            }
        })
        .catch(err => console.error('Fehler beim Laden der Spiel-Stats:', err));
}

function renderWinners(alltimeWinner, lastWinner) {
    const container = document.getElementById('winnerContent');

    if (!alltimeWinner && !lastWinner) {
        container.innerHTML = '<div class="empty-state-small">Keine Spiele</div>';
        return;
    }

    let html = '<div class="ranking-list">';

    // All-Time Gewinner
    if (alltimeWinner) {
        const avatarUrl = alltimeWinner.avatar
            ? `uploads/avatars/${escapeHtml(alltimeWinner.avatar)}`
            : 'assets/img/default-avatar.svg';
        const winnerColor = alltimeWinner.color || '#007AFF';

        html += `
            <div class="ranking-item">
                <div class="ranking-label">All-Time</div>
                <div class="dashboard-player-card">
                    <img src="${avatarUrl}" alt="${escapeHtml(alltimeWinner.name)}" class="dashboard-player-avatar" style="border-color: ${winnerColor};">
                    <div class="dashboard-player-info">
                        <div class="dashboard-player-name">${escapeHtml(alltimeWinner.name)}</div>
                        <div class="dashboard-player-points" style="color: ${winnerColor};">
                            ${alltimeWinner.total_points || 0} Punkte
                        </div>
                    </div>
                    <div class="dashboard-player-badge winner-badge">👑</div>
                </div>
            </div>
        `;
    }

    // Letzter Gewinner
    if (lastWinner) {
        const avatarUrl = lastWinner.avatar
            ? `uploads/avatars/${escapeHtml(lastWinner.avatar)}`
            : 'assets/img/default-avatar.svg';
        const winnerColor = lastWinner.color || '#007AFF';

        html += `
            <div class="ranking-item">
                <div class="ranking-label">Letzter Stammtisch</div>
                <div class="dashboard-player-card">
                    <img src="${avatarUrl}" alt="${escapeHtml(lastWinner.name)}" class="dashboard-player-avatar" style="border-color: ${winnerColor};">
                    <div class="dashboard-player-info">
                        <div class="dashboard-player-name">${escapeHtml(lastWinner.name)}</div>
                        <div class="dashboard-player-points" style="color: ${winnerColor};">
                            ${lastWinner.total_points || 0} Punkte
                        </div>
                    </div>
                    <div class="dashboard-player-badge winner-badge">👑</div>
                </div>
            </div>
        `;
    }

    html += '</div>';
    container.innerHTML = html;
}

function renderLosers(alltimeLoser, lastLoser) {
    const container = document.getElementById('loserContent');

    if (!alltimeLoser && !lastLoser) {
        container.innerHTML = '<div class="empty-state-small">Keine Spiele</div>';
        return;
    }

    let html = '<div class="ranking-list">';

    // All-Time Loser
    if (alltimeLoser) {
        const avatarUrl = alltimeLoser.avatar
            ? `uploads/avatars/${escapeHtml(alltimeLoser.avatar)}`
            : 'assets/img/default-avatar.svg';
        const loserColor = alltimeLoser.color || '#FF3B30';

        html += `
            <div class="ranking-item">
                <div class="ranking-label">All-Time</div>
                <div class="dashboard-player-card">
                    <img src="${avatarUrl}" alt="${escapeHtml(alltimeLoser.name)}" class="dashboard-player-avatar" style="border-color: ${loserColor};">
                    <div class="dashboard-player-info">
                        <div class="dashboard-player-name">${escapeHtml(alltimeLoser.name)}</div>
                        <div class="dashboard-player-points" style="color: ${loserColor};">
                            ${alltimeLoser.total_points || 0} Punkte
                        </div>
                    </div>
                    <div class="dashboard-player-badge loser-badge">😢</div>
                </div>
            </div>
        `;
    }

    // Letzter Loser
    if (lastLoser) {
        const avatarUrl = lastLoser.avatar
            ? `uploads/avatars/${escapeHtml(lastLoser.avatar)}`
            : 'assets/img/default-avatar.svg';
        const loserColor = lastLoser.color || '#FF3B30';

        html += `
            <div class="ranking-item">
                <div class="ranking-label">Letzter Stammtisch</div>
                <div class="dashboard-player-card">
                    <img src="${avatarUrl}" alt="${escapeHtml(lastLoser.name)}" class="dashboard-player-avatar" style="border-color: ${loserColor};">
                    <div class="dashboard-player-info">
                        <div class="dashboard-player-name">${escapeHtml(lastLoser.name)}</div>
                        <div class="dashboard-player-points" style="color: ${loserColor};">
                            ${lastLoser.total_points || 0} Punkte
                        </div>
                    </div>
                    <div class="dashboard-player-badge loser-badge">😢</div>
                </div>
            </div>
        `;
    }

    html += '</div>';
    container.innerHTML = html;
}

function loadStats() {
    fetch('api.php?action=get_stats')
        .then(res => res.json())
        .then(data => {
            console.log('Stats API Response:', data);
            if (data.success && data.data) {
                renderStats(data.data);
            } else {
                console.error('Stats API Error:', data);
            }
        })
        .catch(err => {
            console.error('Fehler beim Laden der Stats:', err);
        });
}

function renderStats(stats) {
    const container = document.getElementById('quickStats');
    
    if (!container) {
        console.error('quickStats Container nicht gefunden');
        return;
    }
    
    if (!stats) {
        console.error('Keine Stats-Daten erhalten');
        container.innerHTML = '<div class="alert">Fehler beim Laden der Statistiken</div>';
        return;
    }
    
    const nextTerminText = stats.next_termin && stats.next_termin.name
        ? `${stats.next_termin.name} - ${formatDate(stats.next_termin.datum)}`
        : 'Keine geplant';
    
    container.innerHTML = `
        <div class="stat-card">
            <h3>Gesamt Termine</h3>
            <div class="stat-value">${stats.total_termine || 0}</div>
        </div>
        <div class="stat-card">
            <h3>Anwesenheiten</h3>
            <div class="stat-value">${stats.total_anwesend || 0}</div>
        </div>
        <div class="stat-card">
            <h3>Aktive Mitglieder</h3>
            <div class="stat-value">${stats.active_users || 0}</div>
        </div>
        <div class="stat-card">
            <h3>Nächster Termin</h3>
            <div class="stat-value" style="font-size: 18px;">${escapeHtml(nextTerminText)}</div>
        </div>
    `;
}

function renderTermine() {
    const container = document.getElementById('termineList');
    
    if (termine.length === 0) {
        container.innerHTML = '<div class="alert">Noch keine Termine vorhanden.</div>';
        return;
    }
    
    container.innerHTML = termine.map(termin => {
        const datum = formatDate(termin.datum);
        const uhrzeit = termin.uhrzeit.substring(0, 5);
        
        // Anwesenheiten gruppieren
        const anwesend = termin.anwesenheiten.filter(a => a.status === 'anwesend').map(a => a.user_name);
        const nicht_anwesend = termin.anwesenheiten.filter(a => a.status === 'nicht_anwesend').map(a => a.user_name);
        const unentschuldigt = termin.anwesenheiten.filter(a => a.status === 'unentschuldigt').map(a => a.user_name);
        
        return `
            <div class="termin-card" data-termin-id="${termin.id}">
                <div class="termin-header">
                    <div>
                        <div class="termin-title">${escapeHtml(termin.name)}</div>
                        <div class="termin-meta">📍 ${escapeHtml(termin.ort)} • 📅 ${datum} • 🕐 ${uhrzeit}</div>
                        ${termin.beschreibung ? `<div class="termin-meta" style="margin-top: 5px;">${escapeHtml(termin.beschreibung)}</div>` : ''}
                    </div>
                    <div class="termin-actions">
                        <button class="btn btn-small btn-primary" data-termin-id="${termin.id}" onclick="openAnwesenheitModalById(${termin.id})">
                            Anwesenheit
                        </button>
                        <button class="btn btn-small btn-secondary" onclick="openTerminModalById(${termin.id})">
                            Bearbeiten
                        </button>
                        <button class="btn btn-small btn-danger" onclick="deleteTermin(${termin.id})">
                            Löschen
                        </button>
                    </div>
                </div>
                <div class="anwesenheit-summary">
                    <h4>Anwesenheit</h4>
                    <div class="anwesenheit-groups">
                        ${anwesend.length > 0 ? `
                            <div class="anwesenheit-group anwesend">
                                <div class="anwesenheit-group-label anwesend">Anwesend: ${anwesend.length}</div>
                                <div class="anwesenheit-names">
                                    ${anwesend.map(name => `<span class="anwesenheit-name">${escapeHtml(name)}</span>`).join('')}
                                </div>
                            </div>
                        ` : ''}
                        ${nicht_anwesend.length > 0 ? `
                            <div class="anwesenheit-group nicht-anwesend">
                                <div class="anwesenheit-group-label nicht-anwesend">Nicht Anwesend: ${nicht_anwesend.length}</div>
                                <div class="anwesenheit-names">
                                    ${nicht_anwesend.map(name => `<span class="anwesenheit-name">${escapeHtml(name)}</span>`).join('')}
                                </div>
                            </div>
                        ` : ''}
                        ${unentschuldigt.length > 0 ? `
                            <div class="anwesenheit-group unentschuldigt">
                                <div class="anwesenheit-group-label unentschuldigt">Unentschuldigt: ${unentschuldigt.length}</div>
                                <div class="anwesenheit-names">
                                    ${unentschuldigt.map(name => `<span class="anwesenheit-name">${escapeHtml(name)}</span>`).join('')}
                                </div>
                            </div>
                        ` : ''}
                        ${anwesend.length === 0 && nicht_anwesend.length === 0 && unentschuldigt.length === 0 ? 
                            '<div class="anwesenheit-group"><div class="anwesenheit-group-label">Noch keine Anwesenheit erfasst</div></div>' : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Termin speichern
function saveTermin() {
    const form = document.getElementById('terminForm');
    const formData = new FormData(form);
    
    const terminId = formData.get('termin_id');
    const action = terminId ? 'update_termin' : 'create_termin';
    
    formData.set('action', action);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModals();
                loadTermine();
                loadStats();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Speichern');
        });
}

// Termin löschen
function deleteTermin(id) {
    if (!confirm('Termin wirklich löschen?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_termin');
    formData.append('termin_id', id);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadTermine();
                loadStats();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Löschen');
        });
}

// Helper Functions
function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    const options = { day: '2-digit', month: '2-digit', year: 'numeric' };
    return date.toLocaleDateString('de-DE', options);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Global functions für onclick
window.openTerminModal = openTerminModal;
window.openAnwesenheitModal = openAnwesenheitModal;
window.openTerminModalById = (id) => {
    const termin = termine.find(t => t.id == id);
    if (termin) openTerminModal(termin);
};
window.openAnwesenheitModalById = (id) => {
    const termin = termine.find(t => t.id == id);
    if (termin) openAnwesenheitModal(termin);
};
window.deleteTermin = deleteTermin;

