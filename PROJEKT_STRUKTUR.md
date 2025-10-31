# 📁 Stammtisch App - Projekt-Struktur

## 🎯 Übersicht

Die Stammtisch App ist jetzt **sauber strukturiert** mit getrennten Verzeichnissen:

```
Stammtisch/
├── install/          → Installation (nur beim ersten Mal)
├── upload/           → FTP-Upload-Paket (für manuellen Upload)
├── assets/           → CSS, JS, Bilder
├── config.php        → Haupt-Konfiguration
├── index.php         → Login-Seite
├── dashboard.php     → Haupt-Dashboard
├── api.php           → API-Endpoints
├── sync-install.sh   → Auto-Sync Script
└── ...
```

---

## 📦 Verzeichnis-Beschreibungen:

### 1. `/install/` - Installations-Verzeichnis
**Zweck:** Enthält alles für die erste Installation

```
install/
├── install.php              → Haupt-Installer
├── database_complete.sql    → DB-Schema
├── .htaccess                → Alfahosting-kompatibel
├── .user.ini                → PHP-FPM Einstellungen
├── logs/                    → Installation-Logs
├── README.md                → Anleitung
└── ALFAHOSTING_ANLEITUNG.md → Alfahosting-Guide
```

**Verwendung:**
```
1. Lade install/ Ordner per FTP hoch
2. Öffne: https://deine-domain.de/install/
3. Folge den Anweisungen
4. WICHTIG: Lösche install/ nach erfolgreicher Installation!
```

---

### 2. `/upload/` - FTP-Upload-Paket
**Zweck:** Synchronisierte Kopie von install/ für einfachen FTP-Upload

```
upload/
├── install.php              → Synchronisiert mit install/
├── database_complete.sql    → Synchronisiert mit install/
├── .htaccess                → Synchronisiert mit install/
├── .user.ini                → Synchronisiert mit install/
├── logs/                    → Synchronisiert mit install/
└── README.md                → Anleitungen
```

**Automatische Synchronisation:**
- Wird automatisch mit `install/` synchronisiert
- Bei jedem Git-Commit (via Pre-Commit-Hook)
- Manuell via `./sync-install.sh`

**Verwendung:**
```bash
# Lade den kompletten upload/ Ordner per FTP hoch
# Alle Dateien sind bereits synchronisiert
```

---

### 3. Haupt-Verzeichnis (Root)
**Zweck:** Produktiv-Code - keine Installations-Dateien!

**Was IST im Root:**
```
✅ config.php           → Konfiguration
✅ index.php            → Login
✅ dashboard.php        → Dashboard
✅ api.php              → API
✅ assets/              → CSS, JS
✅ uploads/             → User-Uploads
✅ .htaccess            → Apache-Einstellungen
✅ .user.ini            → PHP-FPM Einstellungen
```

**Was ist NICHT im Root:**
```
❌ install.php          → Jetzt in /install/
❌ database*.sql        → Jetzt in /install/
❌ Installation-Logs    → Jetzt in /install/logs/
```

---

## 🔄 Auto-Sync System

### sync-install.sh
Synchronisiert automatisch `install/` → `upload/`

**Verwendung:**
```bash
# Manuell ausführen
./sync-install.sh

# Wird automatisch ausgeführt:
# - Bei jedem Git-Commit (Pre-Commit-Hook)
# - Kopiert alle Änderungen von install/ nach upload/
```

**Was wird synchronisiert:**
- ✅ install.php
- ✅ database_complete.sql
- ✅ .htaccess
- ✅ .user.ini
- ✅ logs/ Ordner
- ✅ README.md
- ✅ ALFAHOSTING_ANLEITUNG.md

---

## 🚀 Workflow für Entwicklung:

### 1. Änderungen an install.php:
```bash
1. Ändere install/install.php
2. Git Commit
3. Pre-Commit-Hook synchronisiert automatisch → upload/
4. Push zu GitHub
```

### 2. Manuell synchronisieren:
```bash
./sync-install.sh
```

### 3. Neue Installation testen:
```bash
1. Lade install/ Ordner auf Test-Server
2. Öffne: https://test.deine-domain.de/install/
3. Teste Installation
```

---

## 📋 Git-Hooks:

### Pre-Commit Hook
Automatisch aktiviert in `.git/hooks/pre-commit`

**Was passiert:**
1. Bei `git commit` wird automatisch `sync-install.sh` ausgeführt
2. Alle Änderungen werden von `install/` → `upload/` kopiert
3. `install/` und `upload/` werden automatisch zu Git hinzugefügt
4. Commit wird normal fortgesetzt

**Deaktivieren:**
```bash
rm .git/hooks/pre-commit
```

**Reaktivieren:**
```bash
chmod +x .git/hooks/pre-commit
```

---

## 🔐 .gitignore

Folgende Dateien werden NICHT in Git getrackt:

```gitignore
# Installations-Dateien im Root (sind jetzt in /install/)
/install.php
/database*.sql
/logs/

# Uploads
uploads/avatars/*
!uploads/avatars/.htaccess

# Lokale Konfiguration
config.local.php

# System
.DS_Store
```

---

## 📊 Vergleich Alt vs. Neu:

### ❌ VORHER (Unordentlich):
```
Root/
├── install.php           ← Installation im Root
├── database.sql          ← SQL-Dateien überall
├── database_update.sql
├── database_update2.sql
├── ...
├── index.php
├── dashboard.php
└── ...
```

### ✅ NACHHER (Sauber):
```
Root/
├── install/              ← Installation getrennt
│   ├── install.php
│   └── database_complete.sql
├── upload/               ← FTP-Paket
│   └── (synchronisiert)
├── index.php
├── dashboard.php
└── ...
```

---

## 🎯 Vorteile der neuen Struktur:

### 1. Sauberkeit
- ✅ Keine Installations-Dateien im Produktiv-Code
- ✅ Klare Trennung: Installation vs. Produktion

### 2. Sicherheit
- ✅ `/install/` kann nach Installation gelöscht werden
- ✅ Keine sensiblen SQL-Dateien im Root

### 3. Wartbarkeit
- ✅ Änderungen an install.php → automatisch synchronisiert
- ✅ Immer aktuelle Version in `install/` UND `upload/`

### 4. Deployment
- ✅ `install/` für Server-Installation
- ✅ `upload/` für FTP-Upload
- ✅ Beide immer synchron

---

## 🛠️ Häufige Aufgaben:

### Installation aktualisieren:
```bash
1. Ändere install/install.php
2. git commit -m "Update Installation"
   → Automatische Sync via Pre-Commit-Hook
3. git push
```

### Manuell synchronisieren:
```bash
./sync-install.sh
```

### Neue Installation deployen:
```bash
# Option 1: install/ Ordner
Upload install/ → Server

# Option 2: upload/ Ordner
Upload upload/ → Server
```

### Nach Installation:
```bash
# Sicherheit: Lösche install/ auf dem Server
rm -rf /pfad/zum/server/install/
```

---

## 📖 Weitere Dokumentation:

- **Installation:** `install/README.md`
- **Alfahosting:** `install/ALFAHOSTING_ANLEITUNG.md`
- **FTP-Upload:** `upload/README.md`
- **Schnellstart:** `upload/SCHNELLSTART.txt`

---

**Version:** 2.0 - Neue saubere Struktur
**Erstellt:** 2025-10-31
**GitHub:** https://github.com/TrofyTT/Stammtisch
