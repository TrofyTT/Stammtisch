let allGames = [];
let currentFilter = 'all';

document.addEventListener('DOMContentLoaded', () => {
    loadGames();
});

function loadGames() {
    fetch('api.php?action=get_games')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                allGames = data.games;
                renderGames();
            }
        })
        .catch(err => console.error('Fehler:', err));
}

function filterGames(status) {
    currentFilter = status;
    
    // Filter Buttons aktualisieren
    document.querySelectorAll('.games-filter .btn').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    
    if (status === 'all') {
        document.getElementById('filterAll').classList.remove('btn-secondary');
        document.getElementById('filterAll').classList.add('btn-primary');
    } else if (status === 'aktiv') {
        document.getElementById('filterActive').classList.remove('btn-secondary');
        document.getElementById('filterActive').classList.add('btn-primary');
    } else if (status === 'beendet') {
        document.getElementById('filterEnded').classList.remove('btn-secondary');
        document.getElementById('filterEnded').classList.add('btn-primary');
    }
    
    renderGames();
}

function renderGames() {
    const container = document.getElementById('gamesList');
    
    let filteredGames = allGames;
    if (currentFilter !== 'all') {
        filteredGames = allGames.filter(game => game.status === currentFilter);
    }
    
    // Nach Datum sortieren (neueste zuerst)
    filteredGames.sort((a, b) => new Date(b.datum) - new Date(a.datum));
    
    if (filteredGames.length === 0) {
        container.innerHTML = '<div class="empty-state">Keine Spiele gefunden.</div>';
        return;
    }
    
    container.innerHTML = filteredGames.map(game => {
        const statusClass = game.status === 'aktiv' ? 'active' : 'ended';
        const statusText = game.status === 'aktiv' ? 'Aktiv' : 'Beendet';
        const date = formatDate(game.datum);
        
        // Gewinner und Rangliste für beendete Spiele
        let winnerHtml = '';
        let rankingHtml = '';
        
        if (game.status === 'beendet' && game.winner && game.ranking) {
            const winnerAvatar = game.winner.avatar 
                ? `uploads/avatars/${escapeHtml(game.winner.avatar)}` 
                : 'assets/img/default-avatar.svg';
            const winnerColor = game.winner.color || getDefaultColor(0);
            
            winnerHtml = `
                <div class="game-winner-section" style="border-color: ${winnerColor};">
                    <div class="game-winner-label" style="color: ${winnerColor};">🥇 Gewinner:</div>
                    <div class="game-winner-info">
                        <img src="${winnerAvatar}" alt="${escapeHtml(game.winner.name)}" class="game-winner-avatar" style="border-color: ${winnerColor};">
                        <span class="game-winner-name">${escapeHtml(game.winner.name)}</span>
                        <span class="game-winner-points" style="color: ${winnerColor};">${game.winner.total_points} Punkte</span>
                    </div>
                </div>
            `;
            
            rankingHtml = `
                <div class="game-ranking-section">
                    <div class="game-ranking-label">Rangliste:</div>
                    <div class="game-ranking-list">
                        ${game.ranking.map((player, index) => {
                            const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : (index + 1) + '.';
                            const avatar = player.avatar 
                                ? `uploads/avatars/${escapeHtml(player.avatar)}` 
                                : 'assets/img/default-avatar.svg';
                            const playerColor = player.color || getDefaultColor(index);
                            
                            return `
                                <div class="game-ranking-item ${index === 0 ? 'winner' : ''}" style="border-left: 4px solid ${playerColor};">
                                    <span class="game-ranking-medal">${medal}</span>
                                    <img src="${avatar}" alt="${escapeHtml(player.name)}" class="game-ranking-avatar" style="border-color: ${playerColor};">
                                    <span class="game-ranking-name">${escapeHtml(player.name)}</span>
                                    <span class="game-ranking-points">${player.total_points || 0} Punkte</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>
            `;
        }
        
        return `
            <div class="game-item ${statusClass}">
                <div class="game-item-content">
                    <div class="game-item-header">
                        <h4>${escapeHtml(game.name)}</h4>
                        <span class="game-item-status ${statusClass}">${statusText}</span>
                    </div>
                    <div class="game-item-meta">
                        <span>📅 ${date}</span>
                        ${game.player_count ? `<span>👥 ${game.player_count} Spieler</span>` : ''}
                        ${game.round_count ? `<span>🎲 ${game.round_count} Runden</span>` : ''}
                    </div>
                    ${winnerHtml}
                    ${rankingHtml}
                </div>
                <div class="game-item-actions">
                    ${game.status === 'beendet' ? `
                        <a href="game_stats.php?id=${game.id}" class="btn btn-small btn-primary">Statistiken</a>
                    ` : `
                        <a href="game.php?continue=${game.id}" class="btn btn-small btn-secondary">Fortsetzen</a>
                    `}
                    <button class="btn btn-small btn-danger" onclick="deleteGame(${game.id})">Löschen</button>
                </div>
            </div>
        `;
    }).join('');
}

function deleteGame(gameId) {
    if (!confirm('Spiel wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_game');
    formData.append('game_id', gameId);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadGames();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Löschen');
        });
}

function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('de-DE', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric' 
    });
}

function getDefaultColor(index) {
    const colors = [
        '#007AFF', '#5856D6', '#34C759', '#FF9500', '#FF3B30',
        '#AF52DE', '#FF2D55', '#5AC8FA', '#FFCC00', '#30D158'
    ];
    return colors[index % colors.length];
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Global functions
window.filterGames = filterGames;
window.deleteGame = deleteGame;

// Initial Filter
document.getElementById('filterAll').classList.add('btn-primary');

