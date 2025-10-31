let members = [];
let currentMemberId = null;
let currentAvatarMemberId = null;
let cropper = null;

document.addEventListener('DOMContentLoaded', () => {
    loadMembers();
    setupModals();
});

function setupModals() {
    const memberModal = document.getElementById('memberModal');
    const avatarModal = document.getElementById('avatarModal');
    
    // Neues Mitglied Button
    document.getElementById('newMemberBtn').addEventListener('click', () => {
        openMemberModal();
    });
    
    // Modal schließen
    document.querySelectorAll('.modal-close, .modal-cancel').forEach(btn => {
        btn.addEventListener('click', () => {
            closeModals();
        });
    });
    
    // Click outside to close
    [memberModal, avatarModal].forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModals();
            }
        });
    });
    
    // Member Form Submit
    document.getElementById('memberForm').addEventListener('submit', (e) => {
        e.preventDefault();
        saveMember();
    });
    
    // Avatar Upload
    document.getElementById('avatarFileInput').addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            handleAvatarFileSelect(file);
        }
    });
    
    // Crop Buttons
    document.getElementById('avatarCropCancel').addEventListener('click', () => {
        resetAvatarModal();
    });
    
    document.getElementById('avatarCropApply').addEventListener('click', () => {
        applyCropAndUpload();
    });
}

function closeModals() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
    currentMemberId = null;
    currentAvatarMemberId = null;
    document.getElementById('memberForm').reset();
    document.getElementById('member_id').value = '';
    resetAvatarModal();
}

function resetAvatarModal() {
    // Cropper zerstören
    if (cropper) {
        cropper.destroy();
        cropper = null;
    }
    
    // UI zurücksetzen
    document.getElementById('avatarCropContainer').style.display = 'none';
    document.getElementById('avatarPreviewContainer').style.display = 'none';
    document.getElementById('avatarCropActions').style.display = 'none';
    document.getElementById('avatarFileInput').value = '';
    document.getElementById('avatarUploadStatus').innerHTML = '';
    document.getElementById('avatarCropImage').src = '';
}

function openMemberModal(member = null) {
    const modal = document.getElementById('memberModal');
    const form = document.getElementById('memberForm');
    const title = document.getElementById('modalTitle');
    
    if (member) {
        title.textContent = 'Mitglied bearbeiten';
        document.getElementById('member_id').value = member.id;
        document.getElementById('member_name').value = member.name;
        document.getElementById('member_email').value = member.email;
        document.getElementById('member_rang').value = member.rang || '';
        const memberColor = member.color || '#007AFF';
        document.getElementById('member_color').value = memberColor;
        document.getElementById('member_color_picker').value = memberColor;
        document.getElementById('member_is_admin').checked = member.is_admin == 1;
        document.getElementById('member_password').required = false;
        document.getElementById('member_password').placeholder = 'Leer lassen, um nicht zu ändern';
        currentMemberId = member.id;
    } else {
        title.textContent = 'Neues Mitglied';
        form.reset();
        document.getElementById('member_id').value = '';
        document.getElementById('member_password').required = true;
        document.getElementById('member_password').placeholder = '';
        currentMemberId = null;
    }
    
    modal.classList.add('active');
}

function openAvatarModal(member) {
    const modal = document.getElementById('avatarModal');
    const preview = document.getElementById('avatarPreview');
    const avatarUrl = member.avatar 
        ? `uploads/avatars/${escapeHtml(member.avatar)}` 
        : 'assets/img/default-avatar.svg';
    
    preview.src = avatarUrl;
    preview.style.display = 'block';
    document.getElementById('avatarPreviewContainer').style.display = 'block';
    currentAvatarMemberId = member.id;
    resetAvatarModal();
    modal.classList.add('active');
}

function handleAvatarFileSelect(file) {
    // Validierung
    if (!file.type.startsWith('image/')) {
        showAvatarStatus('Bitte wähle ein Bild aus!', 'error');
        return;
    }
    
    if (file.size > 10 * 1024 * 1024) {
        showAvatarStatus('Bild ist zu groß (max. 10 MB)', 'error');
        return;
    }
    
    // Datei als URL laden
    const reader = new FileReader();
    reader.onload = (e) => {
        const imageUrl = e.target.result;
        initCropper(imageUrl);
    };
    reader.readAsDataURL(file);
}

function initCropper(imageUrl) {
    // Alten Cropper zerstören falls vorhanden
    if (cropper) {
        cropper.destroy();
    }
    
    const cropImage = document.getElementById('avatarCropImage');
    cropImage.src = imageUrl;
    
    // Preview verstecken, Crop Container zeigen
    document.getElementById('avatarPreviewContainer').style.display = 'none';
    document.getElementById('avatarCropContainer').style.display = 'block';
    document.getElementById('avatarCropActions').style.display = 'block';
    document.getElementById('avatarUploadStatus').innerHTML = '';
    
    // Cropper initialisieren mit runder Crop-Box
    cropper = new Cropper(cropImage, {
        aspectRatio: 1, // 1:1 für runde Avatare
        viewMode: 1, // Crop-Box innerhalb des Bildes
        dragMode: 'move',
        autoCropArea: 0.8, // 80% des Bildes automatisch auswählen
        restore: false,
        guides: true,
        center: true,
        highlight: false,
        cropBoxMovable: true,
        cropBoxResizable: true,
        toggleDragModeOnDblclick: false,
        minCropBoxWidth: 100,
        minCropBoxHeight: 100,
    });
}

function applyCropAndUpload() {
    if (!cropper) {
        showAvatarStatus('Bitte wähle zuerst ein Bild aus!', 'error');
        return;
    }
    
    showAvatarStatus('Bild wird zugeschnitten...', 'loading');
    
    // Canvas für runden Zuschnitt erstellen (512x512)
    const canvas = document.createElement('canvas');
    const size = 512;
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    
    // Rundes Clip erstellen
    ctx.beginPath();
    ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
    ctx.clip();
    
    // Gecroptes Bild auf Canvas zeichnen
    const croppedCanvas = cropper.getCroppedCanvas({
        width: size,
        height: size,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
    });
    
    ctx.drawImage(croppedCanvas, 0, 0, size, size);
    
    // Canvas als Blob konvertieren
    canvas.toBlob((blob) => {
        if (!blob) {
            showAvatarStatus('Fehler beim Zuschneiden', 'error');
            return;
        }
        
        // Als Date für Upload vorbereiten
        const file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
        
        // Upload durchführen
        uploadCroppedAvatar(file);
    }, 'image/jpeg', 0.95);
}

function uploadCroppedAvatar(file) {
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('user_id', currentAvatarMemberId);
    
    showAvatarStatus('Upload läuft...', 'loading');
    
    fetch('upload.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAvatarStatus('Profilbild erfolgreich gespeichert!', 'success');
                setTimeout(() => {
                    closeModals();
                    loadMembers();
                }, 1500);
            } else {
                showAvatarStatus('Fehler: ' + data.error, 'error');
            }
        })
        .catch(err => {
            showAvatarStatus('Fehler beim Upload', 'error');
        });
}

function showAvatarStatus(message, type) {
    const statusDiv = document.getElementById('avatarUploadStatus');
    const alertClass = type === 'error' ? 'error' : type === 'success' ? 'success' : type === 'loading' ? 'info' : 'info';
    statusDiv.innerHTML = `<div class="alert alert-${alertClass}">${escapeHtml(message)}</div>`;
}

function loadMembers() {
    fetch('api.php?action=get_members')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                members = data.members;
                renderMembers();
            }
        })
        .catch(err => console.error('Fehler:', err));
}

function renderMembers() {
    const container = document.getElementById('membersList');
    
    if (members.length === 0) {
        container.innerHTML = '<div class="alert">Noch keine Mitglieder vorhanden.</div>';
        return;
    }
    
    container.innerHTML = members.map(member => {
        const avatarUrl = member.avatar 
            ? `uploads/avatars/${escapeHtml(member.avatar)}` 
            : 'assets/img/default-avatar.svg';
        
        return `
            <div class="member-card">
                <div class="member-avatar-container">
                    <img src="${avatarUrl}" alt="${escapeHtml(member.name)}" class="member-avatar" onclick="openAvatarModal(${JSON.stringify(member).replace(/"/g, '&quot;')})">
                    <div class="member-avatar-overlay" onclick="openAvatarModal(${JSON.stringify(member).replace(/"/g, '&quot;')})">
                        <span>📷</span>
                    </div>
                </div>
                <div class="member-info">
                    <h3 class="member-name">
                        ${escapeHtml(member.name)}
                        ${member.is_admin ? '<span class="user-badge">Admin</span>' : ''}
                    </h3>
                    <div class="member-email">${escapeHtml(member.email)}</div>
                    ${member.rang ? `<div class="member-rang">🏆 ${escapeHtml(member.rang)}</div>` : ''}
                    <div class="member-meta">
                        ${member.last_login ? `Letzter Login: ${formatDateTime(member.last_login)}` : 'Noch nie eingeloggt'}
                    </div>
                </div>
                <div class="member-actions">
                    <button class="btn btn-small btn-primary" onclick="openMemberModalById(${member.id})">
                        Bearbeiten
                    </button>
                    <button class="btn btn-small btn-danger" onclick="deleteMember(${member.id})">
                        Löschen
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

function saveMember() {
    const form = document.getElementById('memberForm');
    const formData = new FormData(form);
    
    const memberId = formData.get('member_id');
    const action = memberId ? 'update_member' : 'create_member';
    
    formData.append('action', action);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModals();
                loadMembers();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Speichern');
        });
}

function deleteMember(id) {
    if (!confirm('Mitglied wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_member');
    formData.append('member_id', id);
    
    fetch('api.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadMembers();
            } else {
                alert('Fehler: ' + data.error);
            }
        })
        .catch(err => {
            alert('Fehler beim Löschen');
        });
}

function openMemberModalById(id) {
    const member = members.find(m => m.id == id);
    if (member) {
        openMemberModal(member);
    }
}

function formatDateTime(dateString) {
    if (!dateString) return '';
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

// Global functions
window.openMemberModal = openMemberModal;
window.openMemberModalById = openMemberModalById;
window.openAvatarModal = openAvatarModal;
window.deleteMember = deleteMember;

