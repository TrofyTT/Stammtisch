let gameStats = null;
let gameChart = null;

document.addEventListener('DOMContentLoaded', () => {
    loadGameStats();
});

function loadGameStats() {
    // Prüfe ob gameId vorhanden ist
    if (!gameId || gameId === 0) {
        alert('Keine Spiel-ID gefunden');
        window.location.href = 'games.php';
        return;
    }
    
    fetch(`api.php?action=get_game_stats&game_id=${gameId}`)
        .then(res => {
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            return res.json();
        })
        .then(data => {
            if (data.success) {
                gameStats = data.stats;
                renderGameStats();
                renderChart();
                renderRanking();
            } else {
                alert('Fehler: ' + (data.error || 'Unbekannter Fehler'));
                window.location.href = 'games.php';
            }
        })
        .catch(err => {
            console.error('Fehler beim Laden der Statistiken:', err);
            alert('Fehler beim Laden der Statistiken. Bitte Seite neu laden.');
        });
}

function renderGameStats() {
    const header = document.getElementById('gameStatsHeader');
    const stats = gameStats.game_info;
    
    if (!stats) {
        console.error('Keine Spiel-Info gefunden');
        return;
    }
    
    header.innerHTML = `
        <div class="game-stats-title">
            <h2>${escapeHtml(stats.name || 'Unbekanntes Spiel')}</h2>
            <div class="game-stats-meta">
                <span>📅 ${formatDate(stats.datum)}</span>
                <span>🎲 ${stats.total_runden || 0} Runden</span>
                <span>👥 ${stats.player_count || 0} Spieler</span>
            </div>
        </div>
    `;
    
    const grid = document.getElementById('gameStatsGrid');
    
    if (!gameStats.players || gameStats.players.length === 0) {
        grid.innerHTML = '<div class="alert">Keine Spieler-Daten verfügbar.</div>';
        return;
    }
    
    grid.innerHTML = gameStats.players.map((player, index) => {
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        
        const totalPoints = player.total_points || 0;
        const avgPoints = player.average_points || 0;
        const maxRound = player.max_round || 0;
        const minRound = player.min_round || 0;
        const bestRound = player.best_round_points || 0;
        const worstRound = player.worst_round_points || 0;
        
        return `
            <div class="player-stats-card">
                <div class="player-stats-header">
                    <img src="${avatarUrl}" alt="${escapeHtml(player.name)}" class="player-stats-avatar">
                    <div class="player-stats-name-section">
                        <h3>${escapeHtml(player.name)}</h3>
                        <div class="player-stats-position">Platz ${index + 1}</div>
                    </div>
                </div>
                <div class="player-stats-numbers">
                    <div class="stat-item">
                        <div class="stat-label">Gesamtpunkte</div>
                        <div class="stat-value">${totalPoints}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Durchschnitt</div>
                        <div class="stat-value">${avgPoints.toFixed(1)}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Höchste Runde</div>
                        <div class="stat-value">${maxRound}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Niedrigste Runde</div>
                        <div class="stat-value">${minRound}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Beste Runde</div>
                        <div class="stat-value">${bestRound}</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-label">Schlechteste Runde</div>
                        <div class="stat-value">${worstRound}</div>
                    </div>
                </div>
                <div class="player-stats-chart">
                    <canvas id="playerChart${player.user_id}" style="height: 150px;"></canvas>
                </div>
            </div>
        `;
    }).join('');
    
    // Mini-Charts für jeden Spieler
    gameStats.players.forEach((player) => {
        if (player && player.user_id) {
            renderPlayerMiniChart(player);
        }
    });
}

function renderPlayerMiniChart(player) {
    if (!player || !player.user_id) return;
    
    const canvas = document.getElementById(`playerChart${player.user_id}`);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    
    const rounds = player.rounds || [];
    const labels = rounds.map((r, i) => 'R' + (i + 1));
    const points = rounds.map(r => parseInt(r.punkte) || 0);
    const cumulative = [];
    let sum = 0;
    points.forEach(p => {
        sum += p;
        cumulative.push(sum);
    });
    
    // Spieler-Farbe verwenden oder Fallback
    const playerIndex = gameStats.players.findIndex(p => p.user_id === player.user_id);
    const playerColor = player.color || getColorForPlayer(playerIndex >= 0 ? playerIndex : 0);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels.length > 0 ? labels : ['Start'],
            datasets: [{
                label: 'Kumuliert',
                data: cumulative.length > 0 ? cumulative : [0],
                borderColor: playerColor,
                backgroundColor: playerColor + '1A', // 10% Opacity
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: playerColor
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

function renderChart() {
    const ctx = document.getElementById('gameStatsChart');
    if (!ctx || !gameStats || !gameStats.game_info) return;
    
    const totalRunden = gameStats.game_info.total_runden || 0;
    const labels = [];
    for (let i = 1; i <= totalRunden; i++) {
        labels.push('Runde ' + i);
    }
    
    if (labels.length === 0) {
        labels.push('Start');
    }
    
    const datasets = (gameStats.players || []).map((player, index) => {
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        
        // Spieler-Farbe verwenden oder Fallback
        const playerColor = player.color || getColorForPlayer(index);
        
        // Kumulierte Punkte
        const rounds = player.rounds || [];
        const cumulative = [];
        let sum = 0;
        rounds.forEach(round => {
            sum += parseInt(round.punkte) || 0;
            cumulative.push(sum);
        });
        
        // Wenn keine Runden, zumindest einen Startpunkt
        if (cumulative.length === 0) {
            cumulative.push(0);
        }
        
        return {
            label: player.name || 'Unbekannt',
            data: cumulative,
            borderColor: playerColor,
            backgroundColor: playerColor + '1A', // 10% Opacity
            tension: 0.4,
            fill: false,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: playerColor,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            avatarUrl: avatarUrl,
            playerId: player.user_id,
            playerColor: playerColor
        };
    });
    
    // Kein Avatar-Plugin für den Haupt-Chart - nur Linien ohne Gesichter
    // Avatare bleiben in den einzelnen Spieler-Karten (unten)
    
    // Chart erstellen - ohne auf Avatare zu warten
    gameChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            animation: {
                duration: 800,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + context.parsed.y + ' Punkte';
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Runde'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Punkte (kumuliert)'
                    },
                    beginAtZero: true
                }
            }
        }
    });
    
    // Avatar-Plugin NICHT registrieren - keine Gesichter im Haupt-Chart
    // Avatare bleiben in den einzelnen Spieler-Karten unten
}

function renderRanking() {
    const container = document.getElementById('playersRanking');
    
    if (!gameStats.players || gameStats.players.length === 0) {
        container.innerHTML = '<div class="alert">Keine Spieler-Daten verfügbar.</div>';
        return;
    }
    
    container.innerHTML = gameStats.players.map((player, index) => {
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        
        const medal = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : (index + 1) + '.';
        const totalPoints = player.total_points || 0;
        const avgPoints = player.average_points || 0;
        
        return `
            <div class="ranking-item ${index < 3 ? 'top' : ''}">
                <div class="ranking-medal">${medal}</div>
                <img src="${avatarUrl}" alt="${escapeHtml(player.name)}" class="ranking-avatar">
                <div class="ranking-info">
                    <div class="ranking-name">${escapeHtml(player.name)}</div>
                    <div class="ranking-details">
                        <span>${totalPoints} Punkte</span>
                        <span>Ø ${avgPoints.toFixed(1)}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getColorForPlayer(index) {
    const colors = [
        '#007AFF',
        '#5856D6',
        '#34C759',
        '#FF9500',
        '#FF3B30',
        '#AF52DE',
        '#FF2D55',
        '#5AC8FA'
    ];
    return colors[index % colors.length];
}

function formatDate(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('de-DE', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric' 
    });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

