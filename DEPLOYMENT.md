# Deployment-Anleitung für Alfahosting

## Schritt 1: Dateien vorbereiten

### 1.1 Lokale Konfigurationsdateien entfernen
- Die Datei `config.local.php` soll **NICHT** hochgeladen werden (nur für lokale Entwicklung)
- Prüfe, ob eine `.gitignore` Datei existiert - wenn nicht, wird empfohlen, eine zu erstellen

### 1.2 Wichtige Dateien prüfen
Stelle sicher, dass folgende Dateien vorhanden sind:
- `config.php` (Produktions-Konfiguration ist bereits enthalten)
- `database.sql` (Datenbank-Schema)
- Alle PHP-Dateien
- `assets/` Ordner (CSS, JS, Bilder)
- `uploads/avatars/` Ordner (wird automatisch erstellt, aber besser vorher anlegen)

## Schritt 2: Datenbank exportieren (falls bereits Daten vorhanden)

### 2.1 Lokale Datenbank exportieren (optional)
Falls du bereits Daten in der lokalen Datenbank hast und diese mit hochladen willst:

```bash
# Lokale Datenbank exportieren
mysqldump -u root kdph7973_pimmel > database_export.sql
```

**WICHTIG:** Prüfe `database_export.sql` und entferne lokale Test-Daten, falls gewünscht.

## Schritt 3: FTP-Zugangsdaten prüfen

Du benötigst:
- **FTP-Host:** [DEINE_DOMAIN] (oder die von Alfahosting bereitgestellte FTP-Adresse)
- **FTP-Port:** 21 (Standard) oder 22 (SFTP)
- **FTP-Benutzer:** (wird dir von Alfahosting mitgeteilt)
- **FTP-Passwort:** (wird dir von Alfahosting mitgeteilt)
- **Zielverzeichnis:** Meist `html/`, `public_html/` oder `/`

## Schritt 4: Dateien hochladen

### 4.1 FTP-Client verwenden (empfohlen)
Verwende einen FTP-Client wie:
- **FileZilla** (kostenlos, für Windows/Mac)
- **WinSCP** (Windows)
- **Cyberduck** (Mac)
- **VS Code Extension "SFTP"** (wenn du VS Code nutzt)

### 4.2 Hochladen der Dateien
1. Verbinde dich mit dem FTP-Server
2. Navigiere zum Web-Root-Verzeichnis (meist `html/` oder `public_html/`)
3. Erstelle folgenden Ordner, falls noch nicht vorhanden: `uploads/avatars/`
4. Setze die Berechtigungen für `uploads/avatars/` auf **755** (oder 777, falls 755 nicht funktioniert)

5. Lade folgende Dateien/Ordner hoch:
   - Alle `.php` Dateien im Hauptverzeichnis
   - Den gesamten `assets/` Ordner
   - Den `uploads/` Ordner (mit Unterordnern)
   - `.htaccess` Datei
   - `database.sql` (optional, nur zum Importieren)

   **NICHT hochladen:**
   - `config.local.php` ❌
   - `README.md` (optional)
   - `DEPLOYMENT.md` (optional)
   - `.git/` Ordner (falls vorhanden)
   - Lokale Test-Dateien

### 4.3 Dateiberechtigungen setzen
Nach dem Hochladen, setze folgende Berechtigungen:
- `uploads/avatars/` → **755** oder **777**
- Alle PHP-Dateien → **644**
- Alle Ordner → **755**

## Schritt 5: Datenbank importieren

### 5.1 Über phpMyAdmin (empfohlen)
1. Logge dich in das Alfahosting-Kundenpanel ein
2. Gehe zu **phpMyAdmin**
3. Wähle deine Datenbank aus
4. Klicke auf **Importieren**
5. Wähle die Datei `database.sql`
6. Klicke auf **Ausführen**

### 5.2 Über MySQL-Kommandozeile (alternativ)
Falls du SSH-Zugang hast:

```bash
mysql -u [DEIN_DB_USER] -p -h 127.0.0.1 -P 3307 [DEINE_DATENBANK] < database.sql
# Passwort eingeben: [DEIN_DB_PASSWORT]
```

## Schritt 6: Konfiguration prüfen

### 6.1 `config.php` prüfen
Die `config.php` sollte bereits die richtigen Produktions-Einstellungen haben:
```php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', '[DEINE_DATENBANK]');
define('DB_USER', '[DEIN_DB_USER]');
define('DB_PASS', '[DEIN_DB_PASSWORT]');
```

**WICHTIG:** Stelle sicher, dass `config.local.php` **NICHT** auf dem Server liegt!

### 6.2 Domain-Verbindung prüfen
- Die Anwendung sollte über deine Domain erreichbar sein
- Prüfe, ob SSL/HTTPS aktiviert ist (empfohlen!)

## Schritt 7: Erste Schritte nach dem Deployment

### 7.1 Login testen
1. Öffne deine Domain im Browser
2. Teste die Registrierung eines neuen Benutzers (falls nötig)
3. Oder logge dich mit dem Admin-Account ein:
   - **Email:** admin@stammtisch.de
   - **Passwort:** admin123
   - ⚠️ **WICHTIG:** Ändere das Admin-Passwort sofort nach dem ersten Login!

### 7.2 Avatar-Upload testen
1. Gehe zu "Mitglieder" (als Admin)
2. Bearbeite ein Mitglied und lade ein Avatar-Bild hoch
3. Prüfe, ob das Bild korrekt gespeichert wird in `uploads/avatars/`

### 7.3 Berechtigungen korrigieren (falls nötig)
Falls Bilder nicht hochgeladen werden können:
```bash
# Per FTP: Ordner-Berechtigung auf 777 setzen
chmod 777 uploads/avatars/
```

## Schritt 8: Sicherheit prüfen

### 8.1 Admin-Passwort ändern
1. Logge dich als Admin ein
2. Gehe zu "Mitglieder"
3. Bearbeite den Admin-Benutzer
4. Setze ein sicheres Passwort

### 8.2 `.htaccess` prüfen
Die `.htaccess` sollte vorhanden sein und folgendes enthalten:
- URL-Rewriting (falls verwendet)
- Sicherheitsregeln

### 8.3 Sensible Dateien schützen
Stelle sicher, dass folgende Dateien nicht öffentlich zugänglich sind:
- `config.php` (sollte nicht direkt aufrufbar sein)
- `config.local.php` (sollte nicht existieren)
- `database.sql` (optional: entfernen oder schützen)

## Schritt 9: Troubleshooting

### 9.1 Datenbankverbindung fehlgeschlagen
**Fehler:** "Datenbankverbindung fehlgeschlagen"
**Lösung:**
- Prüfe `config.php` - sind die Zugangsdaten korrekt?
- Prüfe, ob `config.local.php` auf dem Server existiert (sollte entfernt werden)
- Prüfe die Datenbank-Anmeldedaten im Alfahosting-Panel

### 9.2 Bilder werden nicht angezeigt
**Fehler:** Avatare werden nicht angezeigt
**Lösung:**
- Prüfe Ordner-Berechtigungen: `uploads/avatars/` sollte 755 oder 777 sein
- Prüfe, ob der Ordner existiert und beschreibbar ist
- Prüfe die Dateipfade in den PHP-Dateien

### 9.3 "403 Forbidden" oder "404 Not Found"
**Fehler:** Seite nicht erreichbar
**Lösung:**
- Prüfe, ob die Dateien im richtigen Verzeichnis liegen (meist `html/` oder `public_html/`)
- Prüfe `.htaccess` - eventuell deaktivieren zum Testen
- Prüfe Dateiberechtigungen (sollten 644 für Dateien, 755 für Ordner sein)

### 9.4 Session-Probleme
**Fehler:** Logout nach kurzer Zeit oder Login-Probleme
**Lösung:**
- Prüfe `config.php` - Session-Einstellungen sollten korrekt sein
- Prüfe PHP-Version (sollte 7.4+ sein)

## Schritt 10: Backup erstellen

Nach erfolgreichem Deployment:
1. **Datenbank-Backup erstellen** (über phpMyAdmin: Exportieren)
2. **Dateien-Backup erstellen** (kompletten Upload-Ordner kopieren)
3. **Regelmäßige Backups planen** (z.B. wöchentlich)

## Checkliste vor dem Go-Live

- [ ] Alle Dateien hochgeladen
- [ ] `config.local.php` **NICHT** auf dem Server
- [ ] Datenbank importiert
- [ ] Berechtigungen für `uploads/avatars/` gesetzt (755 oder 777)
- [ ] Login funktioniert
- [ ] Admin-Passwort geändert
- [ ] Avatar-Upload getestet
- [ ] Alle Seiten funktionieren (Dashboard, Spiele, Statistiken, etc.)
- [ ] Mobile-Ansicht getestet
- [ ] SSL/HTTPS aktiviert (empfohlen)
- [ ] Backup erstellt

## Support

Bei Problemen:
1. Prüfe die PHP-Fehlerlogs im Alfahosting-Panel
2. Prüfe die Browser-Konsole (F12) auf JavaScript-Fehler
3. Aktiviere PHP-Fehlermeldungen temporär zum Debuggen

---

**Viel Erfolg beim Deployment! 🚀**

