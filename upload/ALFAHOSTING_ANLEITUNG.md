# 🚀 Installation auf Alfahosting

## ⚠️ WICHTIG: Alfahosting-spezifische Besonderheiten!

Alfahosting verwendet **PHP-FPM** (FastCGI), daher gelten spezielle Regeln:

---

## 📦 Dateien die du hochladen musst:

### ✅ PFLICHT (Minimal):
```
install.php     → Hauptinstaller (lädt alles von GitHub)
.user.ini       → PHP-Einstellungen (WICHTIG für PHP-FPM!)
```

### ✅ EMPFOHLEN:
```
install.php     → Hauptinstaller
.user.ini       → PHP-Einstellungen
.htaccess       → Apache-Einstellungen (ohne php_value!)
logs/           → Log-Ordner mit .htaccess
```

### ✅ VOLLSTÄNDIG (Fallback):
```
Alle Dateien aus upload/ hochladen
```

---

## 🔴 KRITISCH: .htaccess vs .user.ini

### ❌ FALSCH (funktioniert NICHT bei Alfahosting):
```apache
# .htaccess mit php_value → 500 ERROR!
php_value upload_max_filesize 10M
php_flag display_errors on
```
**→ Verursacht 500 Internal Server Error!**

### ✅ RICHTIG (funktioniert bei Alfahosting):
```ini
; .user.ini (für PHP-FPM)
upload_max_filesize = 10M
display_errors = On
```

---

## 📋 Schritt-für-Schritt Anleitung:

### 1️⃣ FTP-Verbindung herstellen

**FTP-Zugangsdaten aus Alfahosting-Kundencenter:**
- Server: `ftp.deine-domain.de`
- Benutzer: `dein-ftp-user`
- Passwort: `dein-ftp-passwort`
- Port: `21`

**Navigiere zu:**
```
/httpdocs/     (Hauptverzeichnis bei Alfahosting mit Plesk)
```

### 2️⃣ Dateien hochladen

**Minimal (empfohlen):**
```
✅ install.php hochladen
✅ .user.ini hochladen
```

**Mit Sicherheit:**
```
✅ install.php hochladen
✅ .user.ini hochladen
✅ .htaccess hochladen
✅ logs/ Ordner hochladen
```

### 3️⃣ Berechtigungen setzen (CHMOD)

| Datei/Ordner | CHMOD | Warum |
|--------------|-------|-------|
| `install.php` | **644** | Standard |
| `.user.ini` | **644** | Wird von PHP gelesen |
| `.htaccess` | **644** | Wird von Apache gelesen |
| `logs/` | **755** | PHP muss schreiben können |

**In FileZilla:**
1. Rechtsklick auf Datei → "Dateiberechtigungen..."
2. Trage CHMOD ein (z.B. 644)
3. OK klicken

### 4️⃣ Installation starten

**Im Browser öffnen:**
```
https://deine-domain.de/install.php
```

**Was passiert:**
1. ✅ Download von GitHub (52 Dateien)
2. ✅ Schöne Erfolgsmeldung mit Spinner
3. ✅ Automatischer Redirect zur Installation
4. ✅ Installationsformular erscheint

### 5️⃣ Datenbank-Zugangsdaten eingeben

**Aus Alfahosting-Kundencenter:**
- Host: `localhost` (meistens)
- Port: `3306` (Standard MySQL)
- Datenbank-Name: `deine_db`
- Benutzer: `deine_db_user`
- Passwort: `dein_db_passwort`

**Admin-Account:**
- E-Mail: `deine@email.de`
- Passwort: `sicheres-passwort`

### 6️⃣ Installation abschließen

Nach erfolgreicher Installation:
1. ✅ Weiterleitung zu `index.php` (Login)
2. ✅ Mit Admin-Zugangsdaten anmelden
3. ✅ Fertig!

---

## ⚠️ Häufige Fehler bei Alfahosting:

### Problem 1: 500 Internal Server Error

**Ursachen:**
- ❌ Alte .htaccess mit `php_value` Direktiven
- ❌ Fehlende .user.ini
- ❌ Falsche Berechtigungen auf logs/

**Lösung:**
```bash
1. Lösche die alte .htaccess
2. Lade die NEUE .htaccess hoch (ohne php_value!)
3. Lade .user.ini hoch
4. Setze logs/ auf CHMOD 755
```

### Problem 2: PHP-Einstellungen werden ignoriert

**Ursache:**
- ❌ PHP-Einstellungen in .htaccess statt .user.ini

**Lösung:**
```bash
1. Erstelle .user.ini mit allen PHP-Einstellungen
2. Entferne php_value/php_flag aus .htaccess
3. Warte 5 Minuten (PHP-FPM Cache)
```

### Problem 3: "Cannot create directory logs/"

**Ursache:**
- ❌ Keine Schreibrechte

**Lösung:**
```bash
1. Erstelle logs/ Ordner manuell per FTP
2. Setze CHMOD auf 755
3. Lade logs/.htaccess hoch
4. Erneut versuchen
```

### Problem 4: GitHub-Download schlägt fehl

**Ursache:**
- ❌ Firewall blockiert GitHub
- ❌ cURL nicht verfügbar

**Lösung:**
```bash
1. Lade database_complete.sql hoch
2. install.php verwendet dann die lokale Datei
3. Oder: Alle Dateien manuell hochladen
```

---

## 🔧 Alfahosting-spezifische Einstellungen:

### .user.ini (PFLICHT für PHP-FPM):
```ini
; Upload & POST Limits
upload_max_filesize = 10M
post_max_size = 10M
max_input_time = 300

; Execution Time & Memory
max_execution_time = 300
memory_limit = 256M

; Error Reporting
display_errors = On
log_errors = On
error_reporting = E_ALL
```

### .htaccess (OHNE php_value!):
```apache
# Zeichenkodierung
AddDefaultCharset UTF-8

# Verzeichnis-Listing deaktivieren
Options -Indexes

# DirectoryIndex
DirectoryIndex index.php index.html
```

---

## 📊 Was ist anders bei Alfahosting?

| Feature | Andere Hoster | Alfahosting |
|---------|--------------|-------------|
| PHP-Modus | mod_php | **PHP-FPM** |
| PHP-Einstellungen | .htaccess | **.user.ini** |
| Hauptverzeichnis | /public_html/ | **/httpdocs/** |
| MySQL Host | localhost | localhost |
| MySQL Port | 3306 | 3306 |

---

## ✅ Checkliste für erfolgreiche Installation:

- [ ] FTP-Zugangsdaten aus Kundencenter kopiert
- [ ] Zu /httpdocs/ navigiert
- [ ] install.php hochgeladen
- [ ] .user.ini hochgeladen (WICHTIG!)
- [ ] .htaccess hochgeladen (optional)
- [ ] logs/ Ordner erstellt mit CHMOD 755
- [ ] Browser: https://deine-domain.de/install.php geöffnet
- [ ] Download erfolgreich (52 Dateien)
- [ ] Datenbank-Zugangsdaten aus Kundencenter bereit
- [ ] Installation durchgeführt
- [ ] Login erfolgreich
- [ ] install.php gelöscht (Sicherheit!)

---

## 🎯 Nach erfolgreicher Installation:

### Sicherheit:
```bash
1. install.php löschen (per FTP)
2. .user.ini: display_errors = Off setzen
3. In .htaccess: Zugriff auf install.php blockieren
```

### Performance:
```bash
1. In .user.ini: memory_limit erhöhen falls nötig
2. OPcache ist bei Alfahosting aktiv (gut!)
```

---

## 📞 Support:

### Alfahosting-Support:
- **Telefon:** 06181 9911-0
- **E-Mail:** support@alfahosting.de
- **Kundencenter:** https://www.alfahosting.de

### Stammtisch-App Support:
- **GitHub Issues:** https://github.com/TrofyTT/Stammtisch/issues
- **Log-Datei:** `logs/install_DATUM.log` anhängen

---

## 💡 Tipps für Alfahosting:

1. ✅ **PHP-Version prüfen:** Mindestens PHP 8.0
2. ✅ **MySQL-Datenbank:** Im Kundencenter anlegen
3. ✅ **Subdomain:** Für Tests verwenden
4. ✅ **Backup:** Vor Installation Backup erstellen
5. ✅ **SSL:** Let's Encrypt kostenlos aktivieren

---

**Viel Erfolg mit der Installation auf Alfahosting! 🚀**

**Erstellt: 2025-10-31**
**Version: 1.0 - Alfahosting-optimiert**
