let availablePlayers = [];
let selectedPlayers = [];
let currentGameId = null;
let currentRound = 1;
let currentPlayerIndex = 0;
let gameChart = null;
let gameData = {};

document.addEventListener('DOMContentLoaded', () => {
    loadPlayers();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('startGameBtn').addEventListener('click', startGame);
    document.getElementById('submitPointsBtn').addEventListener('click', submitPoints);
    document.getElementById('pointsInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            submitPoints();
        }
    });
    document.getElementById('endGameBtn').addEventListener('click', endGame);
}

function loadPlayers() {
    fetch('api.php?action=get_members')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                availablePlayers = data.members;
                renderAvailablePlayers();
            }
        })
        .catch(err => console.error('Fehler:', err));
}

function renderAvailablePlayers() {
    const container = document.getElementById('availablePlayers');
    
    if (availablePlayers.length === 0) {
        container.innerHTML = '<div class="alert">Keine Mitglieder vorhanden.</div>';
        return;
    }
    
    container.innerHTML = availablePlayers.map(player => {
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        
        const isSelected = selectedPlayers.some(p => p.id === player.id);
        
        return `
            <div class="player-select-card ${isSelected ? 'selected' : ''}" 
                 data-player-id="${player.id}"
                 onclick="togglePlayer(${player.id})">
                <img src="${avatarUrl}" alt="${escapeHtml(player.name)}" class="player-select-avatar">
                <div class="player-select-name">${escapeHtml(player.name)}</div>
                ${isSelected ? `<div class="player-select-order">${selectedPlayers.findIndex(p => p.id === player.id) + 1}</div>` : ''}
            </div>
        `;
    }).join('');
}

function togglePlayer(playerId) {
    const player = availablePlayers.find(p => p.id == playerId);
    if (!player) return;
    
    const index = selectedPlayers.findIndex(p => p.id === playerId);
    
    if (index > -1) {
        // Spieler entfernen
        selectedPlayers.splice(index, 1);
    } else {
        // Spieler hinzufügen (am Ende)
        selectedPlayers.push(player);
    }
    
    renderAvailablePlayers();
    renderSelectedPlayers();
    updateStartButton();
}

function renderSelectedPlayers() {
    const container = document.getElementById('selectedPlayers');
    
    if (selectedPlayers.length === 0) {
        container.innerHTML = '<div class="empty-state">Keine Spieler ausgewählt</div>';
        return;
    }
    
    container.innerHTML = selectedPlayers.map((player, index) => {
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        
        return `
            <div class="selected-player-item">
                <span class="selected-player-order">${index + 1}</span>
                <img src="${avatarUrl}" alt="${escapeHtml(player.name)}" class="selected-player-avatar">
                <span class="selected-player-name">${escapeHtml(player.name)}</span>
                <button class="btn btn-small btn-danger" onclick="removePlayer(${index})">×</button>
            </div>
        `;
    }).join('');
}

function removePlayer(index) {
    selectedPlayers.splice(index, 1);
    renderAvailablePlayers();
    renderSelectedPlayers();
    updateStartButton();
}

function updateStartButton() {
    const btn = document.getElementById('startGameBtn');
    btn.disabled = selectedPlayers.length < 2;
}

function startGame() {
    if (selectedPlayers.length < 2) {
        alert('Mindestens 2 Spieler erforderlich!');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_game');
    formData.append('datum', document.getElementById('gameDate').value);
    formData.append('players', JSON.stringify(selectedPlayers.map(p => p.id)));
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                currentGameId = data.game_id;
                currentRound = 1;
                currentPlayerIndex = 0;
                gameData = {};
                
                // Setup für Spiel
                selectedPlayers.forEach(player => {
                    gameData[player.id] = {
                        player: player,
                        rounds: [],
                        total: 0
                    };
                });
                
                document.getElementById('gameSetup').style.display = 'none';
                document.getElementById('gameActive').style.display = 'block';
                
                initChart();
                showCurrentPlayer();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Starten des Spiels');
        });
}

function initChart() {
    const ctx = document.getElementById('gameChart');
    
    // Start mit Runde 0 (Startpunkt)
    const labels = ['Start'];
    const datasets = selectedPlayers.map((player, index) => {
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        
        const playerColor = player.color || getColorForPlayer(index);
        
        return {
            label: player.name,
            data: [0],
            borderColor: playerColor,
            backgroundColor: playerColor + '1A', // 10% Opacity
            tension: 0.4,
            fill: false,
            pointRadius: 8,
            pointHoverRadius: 10,
            pointBackgroundColor: playerColor,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            avatarUrl: avatarUrl,
            playerId: player.id,
            playerColor: playerColor
        };
    });
    
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
                    display: false
                },
                tooltip: {
                    enabled: true,
                    titleFont: {
                        size: window.innerWidth <= 768 ? 14 : 12
                    },
                    bodyFont: {
                        size: window.innerWidth <= 768 ? 13 : 11
                    },
                    callbacks: {
                        title: function(context) {
                            const index = context[0].dataIndex;
                            return index === 0 ? 'Start' : 'Runde ' + index;
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
                        text: 'Runde',
                        font: {
                            size: window.innerWidth <= 768 ? 14 : 12
                        }
                    },
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: window.innerWidth <= 768 ? 13 : 11
                        }
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Punkte (kumuliert)',
                        font: {
                            size: window.innerWidth <= 768 ? 14 : 12
                        }
                    },
                    ticks: {
                        font: {
                            size: window.innerWidth <= 768 ? 13 : 11
                        }
                    },
                    beginAtZero: true
                }
            },
            onHover: (event, activeElements) => {
                // Avatare bei Hover zeigen könnte hier implementiert werden
            }
        }
    });
    
    // Avatar-Image Cache
    const avatarImageCache = {};
    
    function loadAvatarImage(url) {
        if (avatarImageCache[url]) {
            return Promise.resolve(avatarImageCache[url]);
        }
        
        return new Promise((resolve) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            avatarImageCache[url] = img;
            img.onload = () => resolve(img);
            img.src = url;
        });
    }
    
    // Custom Plugin für Avatare an den letzten Punkten
    const avatarPlugin = {
        id: 'avatarPlugin',
        afterDatasetsDraw: (chart) => {
            const ctx = chart.ctx;
            
            chart.data.datasets.forEach((dataset, datasetIndex) => {
                const meta = chart.getDatasetMeta(datasetIndex);
                if (meta.data.length === 0) return;
                
                // Nur letzten Punkt mit Avatar (nicht Start-Punkt)
                const lastPointIndex = meta.data.length - 1;
                if (lastPointIndex === 0) return; // Überspringe Start-Punkt
                
                const lastPoint = meta.data[lastPointIndex];
                if (!lastPoint) return;
                
                const avatarUrl = dataset.avatarUrl;
                const img = avatarImageCache[avatarUrl];
                
                if (img && img.complete) {
                    drawAvatar(ctx, lastPoint.x, lastPoint.y, img, datasetIndex);
                } else {
                    loadAvatarImage(avatarUrl).then((img) => {
                        drawAvatar(ctx, lastPoint.x, lastPoint.y, img, datasetIndex);
                    });
                }
            });
        }
    };
    
    function drawAvatar(ctx, x, y, img, colorIndex) {
        const radius = 22;
        
        ctx.save();
        
        // Weißer Ring
        ctx.beginPath();
        ctx.arc(x, y, radius + 3, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        
        // Avatar als Kreis
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(img, x - radius, y - radius, radius * 2, radius * 2);
        ctx.restore();
        
        // Farbiger Ring - verwende Spieler-Farbe
        const dataset = (gameChart && gameChart.data && gameChart.data.datasets)
            ? gameChart.data.datasets[colorIndex]
            : null;
        const playerColor = dataset && dataset.playerColor 
            ? dataset.playerColor 
            : getColorForPlayer(colorIndex);
        
        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, radius + 3, 0, Math.PI * 2);
        ctx.strokeStyle = playerColor;
        ctx.lineWidth = 3;
        ctx.stroke();
        ctx.restore();
    }
    
    Chart.register(avatarPlugin);
    
    // Legende mit Spielernamen und Farben erstellen
    renderChartLegend();
}

function renderChartLegend() {
    const legendContainer = document.getElementById('chartLegend');
    if (!legendContainer || !selectedPlayers || !gameChart) return;
    
    legendContainer.innerHTML = gameChart.data.datasets.map((dataset, index) => {
        const player = selectedPlayers.find(p => p.id == dataset.playerId);
        if (!player) return '';
        
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        const playerColor = dataset.playerColor || getColorForPlayer(index);
        const totalPoints = gameData[player.id] ? gameData[player.id].total : 0;
        
        return `
            <div class="legend-item">
                <div class="legend-color" style="background-color: ${playerColor}; border-color: ${playerColor};"></div>
                <img src="${avatarUrl}" alt="${escapeHtml(player.name)}" class="legend-avatar" style="border-color: ${playerColor};">
                <span class="legend-name">${escapeHtml(player.name)}</span>
                <span class="legend-points" style="color: ${playerColor};">${totalPoints} Pkt.</span>
            </div>
        `;
    }).join('');
}

function showCurrentPlayer() {
    // Diese Funktion wird immer aufgerufen, wenn ein Spieler dran ist
    // currentPlayerIndex sollte immer < selectedPlayers.length sein
    
    const player = selectedPlayers[currentPlayerIndex];
    const container = document.getElementById('currentPlayerInfo');
    const avatarUrl = player.avatar 
        ? `uploads/avatars/${escapeHtml(player.avatar)}` 
        : 'assets/img/default-avatar.svg';
    
    container.innerHTML = `
        <div class="current-player-avatar-container">
            <img src="${avatarUrl}" alt="${escapeHtml(player.name)}" class="current-player-avatar">
            <div class="current-player-badge">${currentPlayerIndex + 1}/${selectedPlayers.length}</div>
        </div>
        <div class="current-player-details">
            <h3>${escapeHtml(player.name)}</h3>
            <div class="current-player-total">Gesamt: <strong>${gameData[player.id].total}</strong> Punkte</div>
        </div>
    `;
    
    document.getElementById('pointsInput').value = '';
    document.getElementById('pointsInput').focus();
    updatePlayersStatus();
}

function updatePlayersStatus() {
    const container = document.getElementById('playersStatus');
    
    container.innerHTML = selectedPlayers.map((player, index) => {
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        
        const playerData = gameData[player.id];
        const isCurrent = index === currentPlayerIndex;
        const isDone = index < currentPlayerIndex;
        
        return `
            <div class="player-status-card ${isCurrent ? 'current' : ''} ${isDone ? 'done' : ''}">
                <img src="${avatarUrl}" alt="${escapeHtml(player.name)}" class="player-status-avatar">
                <div class="player-status-info">
                    <div class="player-status-name">${escapeHtml(player.name)}</div>
                    <div class="player-status-points">${playerData.total} Punkte</div>
                    ${isCurrent ? '<div class="player-status-badge">Dran</div>' : ''}
                    ${isDone ? '<div class="player-status-badge done">✓</div>' : ''}
                </div>
            </div>
        `;
    }).join('');
}

function submitPoints() {
    const pointsInput = document.getElementById('pointsInput');
    const points = parseInt(pointsInput.value);
    
    if (isNaN(points) || points < 0) {
        alert('Bitte gültige Punktzahl eingeben!');
        return;
    }
    
    const player = selectedPlayers[currentPlayerIndex];
    const playerData = gameData[player.id];
    
    // Punkte für aktuelle Runde speichern
    playerData.rounds.push({
        round: currentRound,
        points: points
    });
    
    // Gesamtpunkte aktualisieren
    playerData.total += points;
    
    // In Datenbank speichern
    const formData = new FormData();
    formData.append('action', 'add_game_points');
    formData.append('game_id', currentGameId);
    formData.append('player_id', player.id);
    formData.append('round', currentRound);
    formData.append('points', points);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Nächster Spieler
                currentPlayerIndex++;
                
                // Prüfen ob Runde beendet ist
                if (currentPlayerIndex >= selectedPlayers.length) {
                    // Runde beendet - Chart aktualisieren (nur einmal pro Runde)
                    updateChart();
                    
                    // Kurze Info anzeigen, dass Runde beendet ist
                    showRoundCompletedInfo();
                    
                    // Automatisch zur nächsten Runde nach kurzer Pause
                    setTimeout(() => {
                        currentRound++;
                        currentPlayerIndex = 0;
                        
                        // Runden-Anzeige aktualisieren
                        document.getElementById('currentRound').textContent = currentRound;
                        
                        // Info verstecken
                        document.getElementById('roundInfo').style.display = 'none';
                        
                        // Direkt nächsten Spieler zeigen (erster Spieler der neuen Runde)
                        showCurrentPlayer();
                    }, 1500); // 1.5 Sekunden Pause
                } else {
                    // Noch Spieler in dieser Runde - einfach nächsten Spieler zeigen
                    showCurrentPlayer();
                }
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Speichern');
        });
}

function updateChart() {
    // Diese Funktion wird nur aufgerufen, wenn eine Runde komplett beendet ist
    // currentRound wurde bereits erhöht, also verwenden wir currentRound - 1 für die abgeschlossene Runde
    const completedRound = currentRound - 1;
    const expectedLabel = 'Runde ' + completedRound;
    
    // Prüfen ob Label bereits existiert
    const labelExists = gameChart.data.labels.includes(expectedLabel);
    
    if (!labelExists) {
        // Neues Label hinzufügen
        gameChart.data.labels.push(expectedLabel);
    }
    
    // Daten für alle Spieler aktualisieren
    gameChart.data.datasets.forEach((dataset) => {
        const playerId = dataset.playerId;
        const playerData = gameData[playerId];
        
        // Neuen Wert hinzufügen (kumuliert) - immer am Ende
        dataset.data.push(playerData.total);
    });
    
    // Chart mit Animation aktualisieren - Avatare bewegen sich zum neuen Punkt
    gameChart.update({
        duration: 800,
        easing: 'easeOutQuart',
        lazy: false
    });
    
    // Legende aktualisieren
    renderChartLegend();
}

function showEndRoundOption() {
    // Diese Funktion wird jetzt nicht mehr automatisch aufgerufen
    // Sie bleibt für manuelles Beenden verfügbar
    const container = document.getElementById('currentPlayerInfo');
    container.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <h3>Spiel beenden?</h3>
            <p>Möchtest du das Spiel wirklich beenden?</p>
            <div style="margin-top: 30px;">
                <button class="btn btn-danger" onclick="endGame()">Spiel beenden</button>
                <button class="btn btn-secondary" onclick="closeEndGameModal()" style="margin-left: 10px;">Weiterspielen</button>
            </div>
        </div>
    `;
}

function showRoundCompletedInfo() {
    const roundInfo = document.getElementById('roundInfo');
    if (roundInfo) {
        roundInfo.style.display = 'block';
        roundInfo.querySelector('.round-info-content').innerHTML = `
            <span>✓ Runde ${currentRound} abgeschlossen</span>
        `;
    }
}

function closeEndGameModal() {
    // Einfach zurück zum aktuellen Spieler
    showCurrentPlayer();
}

function endGame() {
    if (!confirm('Spiel wirklich beenden?')) return;
    
    const formData = new FormData();
    formData.append('action', 'end_game');
    formData.append('game_id', currentGameId);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Spiel beendet!');
                window.location.href = 'dashboard.php';
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Beenden des Spiels');
        });
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

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Global functions
window.togglePlayer = togglePlayer;
window.removePlayer = removePlayer;
window.endGame = endGame;

// Fallback: startNextRound existiert ggf. in alten Buttons – hier sicher definieren
function startNextRound() {
    const roundInfo = document.getElementById('roundInfo');
    if (roundInfo) roundInfo.style.display = 'none';
    // Setze auf den ersten Spieler der aktuellen Runde zurück
    currentPlayerIndex = 0;
    document.getElementById('pointsInput').value = '';
    document.getElementById('pointsInput').focus();
    showCurrentPlayer();
}
window.startNextRound = startNextRound;

