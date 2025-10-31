let charts = {};

document.addEventListener('DOMContentLoaded', () => {
    loadStatsData();
    loadGameAllTimeStats();
    
    // Re-render Table bei Window-Resize für responsive Layout
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            // Re-lade Stats-Daten um Tabelle neu zu rendern
            loadStatsData();
        }, 300);
    });
});

function loadStatsData() {
    fetch('api.php?action=get_detailed_stats')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderCharts(data.data);
                renderTable(data.data);
            }
        })
        .catch(err => console.error('Fehler:', err));
}

function renderCharts(data) {
    // Chart 1: Anwesenheitsquote nach Person
    const ctxPersonen = document.getElementById('chartPersonen');
    if (ctxPersonen && data.personen_stats) {
        const labels = data.personen_stats.map(p => p.name);
        const anwesenheiten = data.personen_stats.map(p => p.anwesend);
        const total = data.personen_stats.map(p => p.total);
        const quotes = data.personen_stats.map((p, i) => 
            total[i] > 0 ? ((anwesenheiten[i] / total[i]) * 100).toFixed(1) : 0
        );
        
        // Farben für jeden Spieler
        const colors = data.personen_stats.map((p, i) => {
            return p.color || getDefaultColor(i);
        });
        
        if (charts.personen) charts.personen.destroy();
        charts.personen = new Chart(ctxPersonen, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Anwesenheitsquote (%)',
                    data: quotes,
                    backgroundColor: colors.map(c => c + 'CC'), // 80% Opacity
                    borderColor: colors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        
        // Legende mit Avataren erstellen
        setTimeout(() => {
            const legendContainer = document.getElementById('chartPersonenLegend');
            if (legendContainer && data.personen_stats) {
                legendContainer.innerHTML = data.personen_stats.map((p, i) => {
                    const avatarUrl = p.avatar 
                        ? `uploads/avatars/${escapeHtml(p.avatar)}` 
                        : 'assets/img/default-avatar.svg';
                    const playerColor = p.color || getDefaultColor(i);
                    
                    return `
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <img src="${avatarUrl}" alt="${escapeHtml(p.name)}" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover; border: 2px solid ${playerColor};">
                            <span style="font-size: 12px; color: var(--text-secondary);">${escapeHtml(p.name)}</span>
                        </div>
                    `;
                }).join('');
            }
        }, 100);
    }
    
    // Chart 2: Status-Verteilung
    const ctxStatus = document.getElementById('chartStatus');
    if (ctxStatus && data.status_stats) {
        if (charts.status) charts.status.destroy();
        charts.status = new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: ['Anwesend', 'Nicht Anwesend', 'Unentschuldigt'],
                datasets: [{
                    data: [
                        data.status_stats.anwesend || 0,
                        data.status_stats.nicht_anwesend || 0,
                        data.status_stats.unentschuldigt || 0
                    ],
                    backgroundColor: [
                        '#34C759',
                        '#FF9500',
                        '#FF3B30'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    animateRotate: true,
                    duration: 2000
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    // Chart 3: Anwesenheiten über Zeit
    const ctxZeit = document.getElementById('chartZeit');
    if (ctxZeit && data.zeit_stats) {
        const labels = data.zeit_stats.map(d => formatDateShort(d.datum));
        const anwesenheiten = data.zeit_stats.map(d => d.anwesend);
        
        if (charts.zeit) charts.zeit.destroy();
        charts.zeit = new Chart(ctxZeit, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Anwesend',
                    data: anwesenheiten,
                    borderColor: '#007AFF',
                    backgroundColor: 'rgba(0, 122, 255, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // Chart 4: Top Orte
    const ctxOrte = document.getElementById('chartOrte');
    if (ctxOrte && data.orte_stats) {
        const labels = data.orte_stats.map(o => o.ort);
        const counts = data.orte_stats.map(o => o.count);
        
        if (charts.orte) charts.orte.destroy();
        charts.orte = new Chart(ctxOrte, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Besuche',
                    data: counts,
                    backgroundColor: 'rgba(88, 86, 214, 0.6)',
                    borderColor: '#5856D6',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                animation: {
                    duration: 2000,
                    easing: 'easeOutQuart'
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
}

// 6 Nimmt! All Time Statistiken
function loadGameAllTimeStats() {
    fetch('api.php?action=get_game_alltime_stats')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderGameAllTimeChart(data.chart);
                renderGamePlayersStats(data.players);
            }
        })
        .catch(err => {
            console.error('Fehler beim Laden der Spiel-Statistiken:', err);
        });
}

function renderGameAllTimeChart(chartData) {
    const ctx = document.getElementById('chartGameAllTime');
    if (!ctx || !chartData || !chartData.labels || chartData.datasets.length === 0) {
        return;
    }
    
    // Avatare für Chart vorbereiten
    const avatarPlugin = {
        id: 'gameAllTimeAvatarPlugin',
        afterDatasetsDraw(chart, args, options) {
            const { ctx } = chart;
            
            chart.data.datasets.forEach((dataset, i) => {
                const meta = chart.getDatasetMeta(i);
                if (!meta.hidden && dataset.data.length > 0) {
                    const lastPoint = meta.data[dataset.data.length - 1];
                    if (lastPoint && dataset.data[lastPoint.index] !== null) {
                        const { x, y } = lastPoint.tooltipPosition();
                        
                        if (isNaN(x) || isNaN(y) || x < 0 || y < 0 || x > chart.width || y > chart.height) {
                            return;
                        }
                        
                        loadAvatarImage(dataset.avatar).then(img => {
                            if (img && img.naturalWidth > 0 && img.naturalHeight > 0) {
                                drawChartAvatar(ctx, x, y, img, dataset.borderColor);
                            }
                        }).catch(err => console.error('Error loading avatar:', err));
                    }
                }
            });
        }
    };
    
    function loadAvatarImage(avatarPath) {
        return new Promise((resolve) => {
            if (!avatarPath) {
                const defaultImg = new Image();
                defaultImg.src = 'assets/img/default-avatar.svg';
                defaultImg.onload = () => resolve(defaultImg);
                defaultImg.onerror = () => resolve(null);
                return;
            }
            
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => resolve(img);
            img.onerror = () => {
                const defaultImg = new Image();
                defaultImg.src = 'assets/img/default-avatar.svg';
                defaultImg.onload = () => resolve(defaultImg);
                defaultImg.onerror = () => resolve(null);
            };
            img.src = `uploads/avatars/${avatarPath}`;
        });
    }
    
    function drawChartAvatar(ctx, x, y, img, color) {
        const radius = 20;
        
        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, radius + 3, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();
        
        ctx.beginPath();
        ctx.arc(x, y, radius, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(img, x - radius, y - radius, radius * 2, radius * 2);
        ctx.restore();
        
        ctx.save();
        ctx.beginPath();
        ctx.arc(x, y, radius + 3, 0, Math.PI * 2);
        ctx.strokeStyle = color || '#007AFF';
        ctx.lineWidth = 3;
        ctx.stroke();
        ctx.restore();
    }
    
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: chartData.datasets.map(dataset => ({
                label: dataset.label,
                data: dataset.data,
                borderColor: dataset.borderColor,
                backgroundColor: dataset.backgroundColor,
                tension: 0.4,
                fill: false,
                pointRadius: 6,
                pointHoverRadius: 8,
                pointBackgroundColor: dataset.borderColor,
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                avatar: dataset.avatar,
                playerId: dataset.playerId
            }))
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
                        text: 'Spiel-Datum',
                        font: {
                            size: window.innerWidth <= 768 ? 14 : 12
                        }
                    },
                    ticks: {
                        font: {
                            size: window.innerWidth <= 768 ? 12 : 11
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Punkte',
                        font: {
                            size: window.innerWidth <= 768 ? 14 : 12
                        }
                    },
                    ticks: {
                        font: {
                            size: window.innerWidth <= 768 ? 12 : 11
                        }
                    },
                    beginAtZero: true
                }
            }
        },
        plugins: [avatarPlugin]
    });
    
    // Legende erstellen
    renderGameAllTimeLegend(chartData.datasets);
}

function renderGameAllTimeLegend(datasets) {
    const legendContainer = document.getElementById('chartGameAllTimeLegend');
    if (!legendContainer || !datasets) return;
    
    legendContainer.innerHTML = datasets.map(dataset => {
        const avatarUrl = dataset.avatar 
            ? `uploads/avatars/${escapeHtml(dataset.avatar)}` 
            : 'assets/img/default-avatar.svg';
        const playerColor = dataset.borderColor || '#007AFF';
        
        // Letzter Wert finden
        const lastValue = dataset.data.filter(v => v !== null).pop();
        
        return `
            <div class="legend-item" style="border-left-color: ${playerColor};">
                <div class="legend-color" style="background-color: ${playerColor}; border-color: ${playerColor};"></div>
                <img src="${avatarUrl}" alt="${escapeHtml(dataset.label)}" class="legend-avatar" style="border-color: ${playerColor};">
                <span class="legend-name">${escapeHtml(dataset.label)}</span>
                ${lastValue !== undefined ? `<span class="legend-points" style="color: ${playerColor};">Letzte: ${lastValue} Pkt.</span>` : ''}
            </div>
        `;
    }).join('');
}

function renderGamePlayersStats(players) {
    const container = document.getElementById('gamePlayersStatsList');
    if (!container || !players || players.length === 0) {
        container.innerHTML = '<div class="empty-state">Noch keine Spiel-Statistiken verfügbar</div>';
        return;
    }
    
    container.innerHTML = players.map((player, index) => {
        const avatarUrl = player.avatar 
            ? `uploads/avatars/${escapeHtml(player.avatar)}` 
            : 'assets/img/default-avatar.svg';
        const playerColor = player.color || '#007AFF';
        
        // Medaille bestimmen
        let medal = '';
        if (index === 0) medal = '🥇';
        else if (index === 1) medal = '🥈';
        else if (index === 2) medal = '🥉';
        
        return `
            <div class="game-player-stat-card">
                <div class="game-player-stat-header">
                    <div class="game-player-stat-avatar-container">
                        <img src="${avatarUrl}" alt="${escapeHtml(player.name)}" class="game-player-stat-avatar" style="border-color: ${playerColor};">
                        ${medal ? `<span class="game-player-stat-medal">${medal}</span>` : ''}
                    </div>
                    <div class="game-player-stat-info">
                        <h4 class="game-player-stat-name">${escapeHtml(player.name)}</h4>
                        <div class="game-player-stat-rank">Platz ${index + 1}</div>
                    </div>
                </div>
                <div class="game-player-stat-numbers">
                    <div class="game-player-stat-item">
                        <span class="game-player-stat-label">Spiele</span>
                        <span class="game-player-stat-value">${player.game_count}</span>
                    </div>
                    <div class="game-player-stat-item">
                        <span class="game-player-stat-label">Ø Punkte</span>
                        <span class="game-player-stat-value" style="color: ${playerColor};">${player.average_points}</span>
                    </div>
                    <div class="game-player-stat-item">
                        <span class="game-player-stat-label">Gesamtpunkte</span>
                        <span class="game-player-stat-value">${player.total_points}</span>
                    </div>
                    <div class="game-player-stat-item">
                        <span class="game-player-stat-label">Beste</span>
                        <span class="game-player-stat-value" style="color: #34C759;">${player.best_score !== null ? player.best_score : '-'}</span>
                    </div>
                    <div class="game-player-stat-item">
                        <span class="game-player-stat-label">Schlechteste</span>
                        <span class="game-player-stat-value" style="color: #FF3B30;">${player.worst_score !== null ? player.worst_score : '-'}</span>
                    </div>
                    <div class="game-player-stat-item">
                        <span class="game-player-stat-label">Gewonnen</span>
                        <span class="game-player-stat-value" style="color: #FFD700;">${player.wins}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, m => map[m]);
}

function renderTable(data) {
    const container = document.getElementById('statsTable');
    
    if (!data.detailed_table) {
        container.innerHTML = '<div class="alert">Keine Daten verfügbar.</div>';
        return;
    }
    
    // Prüfe ob Mobile
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Mobile: Cards statt Tabelle
        container.innerHTML = data.detailed_table.map((row, index) => {
            const total = row.anwesend + row.nicht_anwesend + row.unentschuldigt;
            const quote = total > 0 ? ((row.anwesend / total) * 100).toFixed(1) : 0;
            const avatarUrl = row.avatar 
                ? `uploads/avatars/${escapeHtml(row.avatar)}` 
                : 'assets/img/default-avatar.svg';
            const playerColor = row.color || getDefaultColor(index);
            
            return `
                <div class="stats-table-card">
                    <div class="stats-table-card-header">
                        <img src="${avatarUrl}" alt="${escapeHtml(row.name)}" class="stats-table-card-avatar" style="border-color: ${playerColor};">
                        <div class="stats-table-card-name">${escapeHtml(row.name)}</div>
                        <div class="stats-table-card-quote" style="color: ${playerColor};">${quote}%</div>
                    </div>
                    <div class="stats-table-card-stats">
                        <div class="stats-table-card-stat-item">
                            <span class="stats-table-card-stat-label">Anwesend</span>
                            <span class="stats-table-card-stat-value" style="color: var(--success);">${row.anwesend}</span>
                        </div>
                        <div class="stats-table-card-stat-item">
                            <span class="stats-table-card-stat-label">Nicht Anwesend</span>
                            <span class="stats-table-card-stat-value" style="color: var(--warning);">${row.nicht_anwesend}</span>
                        </div>
                        <div class="stats-table-card-stat-item">
                            <span class="stats-table-card-stat-label">Unentschuldigt</span>
                            <span class="stats-table-card-stat-value" style="color: var(--danger);">${row.unentschuldigt}</span>
                        </div>
                        <div class="stats-table-card-stat-item">
                            <span class="stats-table-card-stat-label">Gesamt</span>
                            <span class="stats-table-card-stat-value">${total}</span>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        // Desktop: Normale Tabelle
        let html = `
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Person</th>
                        <th>Anwesend</th>
                        <th>Nicht Anwesend</th>
                        <th>Unentschuldigt</th>
                        <th>Gesamt</th>
                        <th>Quote</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.detailed_table.forEach((row, index) => {
            const total = row.anwesend + row.nicht_anwesend + row.unentschuldigt;
            const quote = total > 0 ? ((row.anwesend / total) * 100).toFixed(1) : 0;
            const avatarUrl = row.avatar 
                ? `uploads/avatars/${escapeHtml(row.avatar)}` 
                : 'assets/img/default-avatar.svg';
            const playerColor = row.color || getDefaultColor(index);
            
            html += `
                <tr>
                    <td>
                        <div class="stats-table-person">
                            <img src="${avatarUrl}" alt="${escapeHtml(row.name)}" class="stats-table-avatar" style="border-color: ${playerColor};">
                            <span class="stats-table-name">${escapeHtml(row.name)}</span>
                        </div>
                    </td>
                    <td class="stats-table-center" style="color: var(--success);">${row.anwesend}</td>
                    <td class="stats-table-center" style="color: var(--warning);">${row.nicht_anwesend}</td>
                    <td class="stats-table-center" style="color: var(--danger);">${row.unentschuldigt}</td>
                    <td class="stats-table-center">${total}</td>
                    <td class="stats-table-center" style="font-weight: 600; color: ${playerColor};">${quote}%</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        container.innerHTML = html;
    }
}

function formatDateShort(dateString) {
    const date = new Date(dateString + 'T00:00:00');
    return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
}

function getDefaultColor(index) {
    const colors = [
        '#007AFF', '#5856D6', '#34C759', '#FF9500', '#FF3B30',
        '#AF52DE', '#FF2D55', '#5AC8FA', '#FFCC00', '#30D158'
    ];
    return colors[index % colors.length];
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

