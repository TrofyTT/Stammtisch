# 📤 FTP-Upload Paket für Stammtisch App

## 🎯 Was ist das?

Dieser Ordner enthält **ALLE** Dateien, die du per FTP hochladen musst, um die Stammtisch App zu installieren.

---

## 📦 Enthaltene Dateien:

### 1. ✅ `install.php` (PFLICHT)
**Die Hauptinstallationsdatei!**
- Lädt automatisch alle anderen Dateien von GitHub herunter
- Installiert die Datenbank
- Erstellt Admin-Account
- **DU MUSST NUR DIESE DATEI HOCHLADEN!**

### 2. ✅ `database_complete.sql` (OPTIONAL - Fallback)
**SQL-Dump der kompletten Datenbank**
- Wird automatisch von install.php verwendet
- Nur nötig wenn GitHub-Download fehlschlägt
- Enthält alle Tabellen und Strukturen

### 3. ✅ `.htaccess` (EMPFOHLEN)
**Apache-Konfiguration für Sicherheit**
- Optimiert PHP-Einstellungen
- Versteckt sensible Dateien
- Aktiviert Error-Reporting für Installation

### 4. ✅ `config.local.php.example` (OPTIONAL)
**Template für lokale Entwicklung**
- Nur für lokale Tests
- Auf dem Server NICHT benötigt (config.php wird bei Installation erstellt)

### 5. ✅ `logs/` Ordner (OPTIONAL)
**Log-Verzeichnis mit Sicherheit**
- Enthält `.htaccess` zum Blockieren von HTTP-Zugriff
- Wird automatisch von install.php erstellt wenn nicht vorhanden

---

## 🚀 Installationsanleitung:

### **Option 1: Schnellinstallation (EMPFOHLEN)**

```
SCHRITT 1: Per FTP hochladen
├── Nur install.php hochladen
└── In dein Hauptverzeichnis (z.B. /public_html/ oder /httpdocs/)

SCHRITT 2: Im Browser öffnen
└── https://deine-domain.de/install.php

SCHRITT 3: Automatischer Download
└── install.php lädt ALLE Dateien von GitHub herunter (48 Dateien)

SCHRITT 4: Installation
├── Datenbank-Zugangsdaten eingeben
├── Admin-Account einrichten
└── Fertig! ✅
```

### **Option 2: Vollständiger Upload (wenn GitHub-Download fehlschlägt)**

```
SCHRITT 1: Per FTP hochladen (ALLE Dateien aus upload/)
├── install.php
├── database_complete.sql
├── .htaccess
└── logs/ (mit .htaccess)

SCHRITT 2: Im Browser öffnen
└── https://deine-domain.de/install.php

SCHRITT 3: Installation
├── Falls GitHub-Download fehlschlägt, verwendet install.php die lokale database_complete.sql
├── Datenbank-Zugangsdaten eingeben
├── Admin-Account einrichten
└── Fertig! ✅
```

---

## 📋 FTP-Upload Checkliste:

### ✅ Minimal (Funktioniert in 99% der Fälle):
- [ ] `install.php` hochladen

### ✅ Empfohlen (Sicherer):
- [ ] `install.php` hochladen
- [ ] `.htaccess` hochladen
- [ ] `logs/` Ordner hochladen (mit `.htaccess`)

### ✅ Vollständig (Fallback wenn GitHub nicht funktioniert):
- [ ] `install.php` hochladen
- [ ] `database_complete.sql` hochladen
- [ ] `.htaccess` hochladen
- [ ] `logs/` Ordner hochladen (mit `.htaccess`)

---

## 🔐 Datei-Berechtigungen (CHMOD):

Nach dem FTP-Upload solltest du folgende Berechtigungen setzen:

| Datei/Ordner | CHMOD | Beschreibung |
|--------------|-------|--------------|
| `install.php` | **644** | Standard für PHP-Dateien |
| `.htaccess` | **644** | Standard für .htaccess |
| `logs/` | **755** | Schreibrechte für PHP |
| `logs/.htaccess` | **644** | Sicherheit |
| `uploads/` | **755** | Wird bei Installation erstellt |

**Wichtig:** Viele FTP-Programme setzen diese Berechtigungen automatisch korrekt!

---

## 🛠️ Schritt-für-Schritt mit Screenshots:

### 1. FTP-Programm öffnen (z.B. FileZilla)

```
Server: ftp.deine-domain.de
Benutzer: dein-ftp-user
Passwort: dein-ftp-passwort
```

### 2. Navigiere zu deinem Hauptverzeichnis

Typische Verzeichnisse:
- `/public_html/` (cPanel)
- `/httpdocs/` (Plesk)
- `/www/` (Allgemein)
- `/html/` (Einige Hoster)

### 3. Lade die Dateien hoch

**Drag & Drop:**
- `install.php` in das Hauptverzeichnis ziehen
- Optional: `.htaccess` hochladen
- Optional: `logs/` Ordner hochladen

### 4. Browser öffnen

Gehe zu: `https://deine-domain.de/install.php`

### 5. Folge den Anweisungen

Die Installation führt dich durch alle Schritte!

---

## ⚠️ Häufige Probleme & Lösungen:

### Problem 1: "500 Internal Server Error"
**Lösung:**
- Prüfe die Log-Datei: `logs/install_2025-XX-XX.log`
- Stelle sicher dass `logs/` Ordner Schreibrechte hat (CHMOD 755)
- Prüfe ob `.htaccess` kompatibel ist (manche Hoster erlauben keine PHP-Einstellungen in .htaccess)

### Problem 2: "GitHub-Download schlägt fehl"
**Lösung:**
- Lade auch `database_complete.sql` hoch
- install.php verwendet dann die lokale Datei
- Oder: Manuell alle Dateien vom GitHub-Repository herunterladen und hochladen

### Problem 3: "Permission Denied" beim Erstellen von logs/
**Lösung:**
- Erstelle den `logs/` Ordner manuell per FTP
- Setze Berechtigungen auf 755
- Lade die `.htaccess` aus upload/logs/ hoch

### Problem 4: "ZipArchive nicht verfügbar"
**Lösung:**
- Kontaktiere deinen Hoster und bitte um Aktivierung der PHP Zip-Extension
- Oder: Lade alle Dateien manuell hoch (ohne Auto-Download)

---

## 💡 Tipps & Tricks:

### Tipp 1: Installation in Unterordner
Wenn du die App in einem Unterordner installieren möchtest:

```
1. Erstelle Ordner: /public_html/stammtisch/
2. Lade install.php dort hoch
3. Öffne: https://deine-domain.de/stammtisch/install.php
```

### Tipp 2: Installation testen (auf Subdomain)
```
1. Erstelle Subdomain: test.deine-domain.de
2. Lade install.php in das Subdomain-Verzeichnis
3. Teste die Installation dort
4. Bei Erfolg: Auf Hauptdomain installieren
```

### Tipp 3: Logs prüfen bei Problemen
```
1. Per FTP zu logs/ navigieren
2. Datei öffnen: install_2025-XX-XX.log
3. Suche nach [ERROR] Einträgen
4. Zeige die Fehler im GitHub Issue
```

### Tipp 4: Alte Installation entfernen
```
Vor Neuinstallation:
1. Alte install.php löschen
2. Alte config.php löschen
3. uploads/ Ordner leeren
4. logs/ Ordner leeren
5. Datenbank leeren oder neu erstellen
```

---

## 📧 Support:

Bei Problemen:
1. **Log-Datei prüfen:** `logs/install_DATUM.log`
2. **GitHub Issue öffnen:** https://github.com/TrofyTT/Stammtisch/issues
3. **Log-Datei anhängen** (enthält keine Passwörter)

---

## ✅ Nach erfolgreicher Installation:

1. **Lösche install.php** (Sicherheit!)
   ```
   Per FTP: install.php löschen
   ```

2. **Oder: Schütze install.php**
   ```
   Füge in .htaccess hinzu:

   <Files "install.php">
       Order allow,deny
       Deny from all
   </Files>
   ```

3. **Login:**
   ```
   https://deine-domain.de/
   E-Mail: deine-admin-email@example.com
   Passwort: (was du bei Installation eingegeben hast)
   ```

4. **Viel Spaß mit der Stammtisch App! 🎉**

---

**Erstellt: 2025-10-31**
**Version: 1.0**
**GitHub:** https://github.com/TrofyTT/Stammtisch
