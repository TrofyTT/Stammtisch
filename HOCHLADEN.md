# 🚀 Schnell-Anleitung: Hochladen auf Alfahosting

## ⚡ Schnell-Checkliste

### Vorbereitung
- [ ] Alle Dateien sind bereit zum Upload
- [ ] `config.local.php` wird **NICHT** hochgeladen

### Dateien hochladen (via FTP)
- [ ] FTP-Client öffnen (z.B. FileZilla)
- [ ] Verbindung zu deiner Domain herstellen
- [ ] Alle PHP-Dateien hochladen
- [ ] `assets/` Ordner komplett hochladen
- [ ] `.htaccess` hochladen
- [ ] `uploads/avatars/` Ordner anlegen (Berechtigung 755 oder 777)

### Datenbank einrichten
- [ ] In phpMyAdmin: Deine Datenbank auswählen
- [ ] Datei `database.sql` importieren
- [ ] Datenbank-Update-Dateien importieren (falls vorhanden):
  - `database_update.sql`
  - `database_update2.sql`
  - `database_update3.sql`
  - `database_update4.sql`
  - `database_update5.sql`

### Testen
- [ ] Website öffnen: Deine Domain im Browser
- [ ] Mit Admin einloggen: `admin@stammtisch.de` / `admin123`
- [ ] **Sofort Admin-Passwort ändern!**
- [ ] Avatar-Upload testen
- [ ] Alle Seiten testen (Dashboard, Spiele, Statistiken)

---

## 📋 Detaillierte Schritte

### 1. FTP-Verbindung herstellen

**FileZilla einrichten:**
1. FileZilla öffnen
2. Neue Verbindung:
   - **Host:** [DEINE_DOMAIN] (oder FTP-Adresse von Alfahosting)
   - **Benutzername:** (von Alfahosting)
   - **Passwort:** (von Alfahosting)
   - **Port:** 21 (Standard) oder 22 (SFTP)

### 2. Dateien hochladen

**Zielverzeichnis:** Meist `html/` oder `public_html/`

**Hochladen:**
- ✅ Alle `.php` Dateien aus dem Hauptverzeichnis
- ✅ `assets/` Ordner (komplett)
- ✅ `.htaccess`
- ✅ `database.sql` (zum Importieren)

**NICHT hochladen:**
- ❌ `config.local.php`
- ❌ `.git/` Ordner
- ❌ `README.md`, `DEPLOYMENT.md`, `HOCHLADEN.md` (optional)

### 3. Ordner-Berechtigungen setzen

Nach dem Upload:
- `uploads/avatars/` → **755** (oder 777 falls nötig)

### 4. Datenbank importieren

**Via phpMyAdmin:**
1. Alfahosting-Kundenpanel öffnen
2. phpMyAdmin öffnen
3. Deine Datenbank auswählen
4. **Importieren** klicken
5. Datei `database.sql` auswählen
6. **Ausführen**

**WICHTIG:** Importiere auch die Update-Dateien in dieser Reihenfolge:
- `database_update.sql`
- `database_update2.sql`
- `database_update3.sql`
- `database_update4.sql`
- `database_update5.sql`

### 5. Erste Schritte

1. **Website öffnen:** Deine Domain im Browser
2. **Admin-Login:**
   - Email: `admin@stammtisch.de`
   - Passwort: `admin123`
3. **Passwort sofort ändern:**
   - Gehe zu "Mitglieder"
   - Admin-Benutzer bearbeiten
   - Neues sicheres Passwort setzen

### 6. Testen

**Teste folgende Funktionen:**
- ✅ Login/Logout
- ✅ Dashboard anzeigen
- ✅ Mitglieder verwalten (als Admin)
- ✅ Avatar hochladen
- ✅ Neuen Termin erstellen
- ✅ Anwesenheit erfassen
- ✅ Spiel erstellen und spielen
- ✅ Statistiken anzeigen

---

## ⚠️ Wichtige Hinweise

### Konfiguration
- `config.php` ist bereits für Produktion konfiguriert
- Die Datenbank-Verbindung verwendet:
  - Host: `127.0.0.1`
  - Port: `3307`
  - DB: `[DEINE_DATENBANK]`
  - User: `[DEIN_DB_USER]`
  - Pass: `[DEIN_DB_PASSWORT]`

### Sicherheit
1. **Admin-Passwort sofort ändern!**
2. SSL/HTTPS aktivieren (empfohlen)
3. `database.sql` nach Import löschen (optional)

### Bei Problemen

**"Datenbankverbindung fehlgeschlagen"**
- Prüfe, ob `config.local.php` auf dem Server existiert (sollte entfernt werden)
- Prüfe Datenbank-Anmeldedaten

**"Bilder werden nicht angezeigt"**
- Prüfe Ordner-Berechtigungen: `uploads/avatars/` → 755 oder 777

**"403 Forbidden"**
- Prüfe Dateiberechtigungen (644 für Dateien, 755 für Ordner)

---

## 📞 Support

Falls etwas nicht funktioniert:
1. PHP-Fehlerlogs im Alfahosting-Panel prüfen
2. Browser-Konsole öffnen (F12) und Fehler prüfen
3. Temporär PHP-Fehlermeldungen aktivieren zum Debuggen

---

**Viel Erfolg! 🎉**

