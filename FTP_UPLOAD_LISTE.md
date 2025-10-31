# 📤 FTP-Upload Liste für Stammtisch App

## ✅ Dateien, die hochgeladen werden MÜSSEN:

### PHP-Dateien (Root-Verzeichnis)
```
✅ index.php
✅ dashboard.php
✅ stats.php
✅ members.php
✅ admin.php
✅ achievements.php
✅ game.php
✅ games.php
✅ game_stats.php
✅ logout.php
✅ api.php
✅ config.php
✅ upload.php
✅ install.php
```

### Konfigurationsdateien
```
✅ .htaccess
```

### Ordner (komplett hochladen)
```
✅ assets/
   ├── css/
   │   └── style.css
   ├── js/
   │   ├── auth.js
   │   ├── dashboard.js
   │   ├── stats.js
   │   ├── members.js
   │   ├── game.js
   │   ├── games.js
   │   ├── game_stats.js
   │   ├── achievements.js
   │   ├── admin.js
   │   └── nav.js
   └── img/
       └── default-avatar.svg
```

### SQL-Dateien (optional, für Backup/Referenz)
```
⚠️ database_complete.sql (optional - wird bei Installation verwendet)
```

### Ordner erstellen (auf dem Server)
```
📁 uploads/
   └── avatars/
      └── .htaccess (wird automatisch erstellt, falls nicht vorhanden)
```

---

## ❌ Dateien, die NICHT hochgeladen werden sollten:

```
❌ config.local.php (nur für lokale Entwicklung)
❌ .git/ (nicht nötig auf Server)
❌ .DS_Store (macOS System-Datei)
❌ Thumbs.db (Windows System-Datei)
❌ *.md (Dokumentation - optional, aber nicht nötig)
❌ node_modules/ (falls vorhanden)
❌ .gitignore (nicht nötig auf Server)
```

---

## 📋 Schnell-Checkliste:

### 1. Alle PHP-Dateien aus dem Root-Verzeichnis
- [ ] index.php
- [ ] dashboard.php
- [ ] stats.php
- [ ] members.php
- [ ] admin.php
- [ ] achievements.php
- [ ] game.php
- [ ] games.php
- [ ] game_stats.php
- [ ] logout.php
- [ ] api.php
- [ ] config.php
- [ ] upload.php
- [ ] install.php

### 2. Konfiguration
- [ ] .htaccess

### 3. Assets-Ordner (komplett)
- [ ] assets/css/style.css
- [ ] assets/js/*.js (alle JS-Dateien)
- [ ] assets/img/default-avatar.svg

### 4. Optional (für Referenz)
- [ ] database_complete.sql

### 5. Ordner auf Server erstellen
- [ ] uploads/ (Ordner erstellen)
- [ ] uploads/avatars/ (Ordner erstellen)
- [ ] uploads/avatars/.htaccess (Datei erstellen - siehe unten)

---

## 📝 Wichtige Hinweise:

### .htaccess für uploads/avatars/ erstellen:

Erstelle nach dem Upload eine Datei `uploads/avatars/.htaccess` mit folgendem Inhalt:

```apache
# Erlaube Bilddateien
<FilesMatch "\.(jpg|jpeg|png|gif|svg|webp)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Keine PHP-Ausführung in diesem Verzeichnis
php_flag engine off
```

### Dateiberechtigungen setzen:

Nach dem Upload die Berechtigungen setzen:
- `uploads/` → **755**
- `uploads/avatars/` → **755**

---

## 🚀 Upload-Reihenfolge:

1. **Zuerst:** Ordner-Struktur erstellen (`uploads/avatars/`)
2. **Dann:** Alle PHP-Dateien hochladen
3. **Dann:** `.htaccess` hochladen
4. **Dann:** `assets/` Ordner komplett hochladen
5. **Dann:** `database_complete.sql` hochladen (optional)
6. **Zum Schluss:** `install.php` öffnen und Installation starten

---

## ✅ Nach dem Upload:

1. Öffne `install.php` im Browser: `https://deine-domain.de/install.php`
2. Gib deine Datenbank-Credentials ein:
   - Host: `127.0.0.1`
   - Port: `3307`
   - Datenbankname: `kdph7973_sven`
   - Benutzer: `kdph7973_svenni`
   - Passwort: `#Ht0Wf*&1p8mKKK&`
3. Erstelle Admin-Account
4. Fertig! 🎉

---

**Tipp:** Nutze einen FTP-Client wie FileZilla oder Cyberduck für einfaches Hochladen.

