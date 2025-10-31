let allAchievements = [];

document.addEventListener('DOMContentLoaded', () => {
    loadAchievements();
    
    document.getElementById('achievementForm').addEventListener('submit', saveAchievement);
    document.querySelector('.modal-cancel').addEventListener('click', closeModal);
});

function loadAchievements() {
    fetch('api.php?action=get_achievements')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                allAchievements = data.achievements;
                renderAchievements();
            }
        })
        .catch(err => console.error('Fehler:', err));
}

function renderAchievements() {
    const container = document.getElementById('achievementsList');
    
    if (allAchievements.length === 0) {
        container.innerHTML = '<div class="empty-state">Noch keine Achievements vorhanden.</div>';
        return;
    }
    
    container.innerHTML = allAchievements.map(achievement => {
        const conditionDesc = getConditionDescription(achievement.condition_type, achievement.condition_value);
        
        // Icon sicher behandeln - Unicode-Emoji direkt verwenden
        let icon = '🏆'; // Fallback
        if (achievement.icon) {
            // Entferne eventuelle fehlerhafte Kodierung
            icon = achievement.icon.trim();
            // Wenn es wie ein Kodierungsfehler aussieht, verwende Fallback
            if (icon.includes('ð') || icon.includes('Ÿ') || icon.length > 2) {
                icon = '🏆';
            }
        }
        
        return `
            <div class="achievement-card">
                <div class="achievement-icon">${icon}</div>
                <div class="achievement-content">
                    <h3>${escapeHtml(achievement.name)}</h3>
                    <p class="achievement-description">${escapeHtml(achievement.description || '')}</p>
                    <div class="achievement-condition">
                        <span class="condition-badge">${conditionDesc}</span>
                    </div>
                </div>
                <div class="achievement-actions">
                    <button class="btn btn-small btn-secondary" onclick="editAchievement(${achievement.id})">Bearbeiten</button>
                    <button class="btn btn-small btn-danger" onclick="deleteAchievement(${achievement.id})">Löschen</button>
                </div>
            </div>
        `;
    }).join('');
}

function getConditionDescription(type, value) {
    const descriptions = {
        'game_points': `Punkte erreichen: ${value || '?'}`,
        'attendance_first': 'Erster in Anwesenheit',
        'attendance_never': 'Nie dabei gewesen',
        'attendance_count': `Anwesenheiten: ${value || '?'}`,
        'game_wins': `Spiele gewonnen: ${value || '?'}`
    };
    return descriptions[type] || type;
}

function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Neues Achievement';
    document.getElementById('achievementForm').reset();
    document.getElementById('achievement_id').value = '';
    document.getElementById('conditionValueGroup').style.display = 'none';
    document.getElementById('achievementModal').style.display = 'flex';
}

function editAchievement(id) {
    const achievement = allAchievements.find(a => a.id == id);
    if (!achievement) return;
    
    document.getElementById('modalTitle').textContent = 'Achievement bearbeiten';
    document.getElementById('achievement_id').value = achievement.id;
    document.getElementById('achievement_name').value = achievement.name;
    document.getElementById('achievement_description').value = achievement.description || '';
    document.getElementById('achievement_condition_type').value = achievement.condition_type;
    document.getElementById('achievement_condition_value').value = achievement.condition_value || '';
    document.getElementById('achievement_icon').value = achievement.icon || '🏆';
    
    updateConditionValue();
    document.getElementById('achievementModal').style.display = 'flex';
}

function updateConditionValue() {
    const conditionType = document.getElementById('achievement_condition_type').value;
    const valueGroup = document.getElementById('conditionValueGroup');
    
    const needsValue = ['game_points', 'attendance_count', 'game_wins'].includes(conditionType);
    valueGroup.style.display = needsValue ? 'block' : 'none';
}

function closeModal() {
    document.getElementById('achievementModal').style.display = 'none';
    document.getElementById('achievementForm').reset();
}

function saveAchievement(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    formData.append('action', 'save_achievement');
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModal();
                loadAchievements();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            console.error('Fehler:', err);
            alert('Fehler beim Speichern');
        });
}

function deleteAchievement(id) {
    if (!confirm('Achievement wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_achievement');
    formData.append('achievement_id', id);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadAchievements();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            console.error('Fehler:', err);
            alert('Fehler beim Löschen');
        });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

