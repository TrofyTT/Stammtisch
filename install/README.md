# 📦 Stammtisch App - Installer

## 🎯 Was ist das?

Dies ist das **Installations-Verzeichnis** für die Stammtisch App.

---

## 🚀 Installation starten:

### Im Browser öffnen:
```
https://deine-domain.de/install/
```

Das war's! Der Installer führt dich durch alle Schritte.

---

## 📁 Enthaltene Dateien:

| Datei | Beschreibung |
|-------|--------------|
| `install.php` | Haupt-Installer (lädt alles von GitHub) |
| `database_complete.sql` | Datenbank-Schema (Fallback) |
| `.htaccess` | Apache-Konfiguration (Alfahosting-kompatibel) |
| `.user.ini` | PHP-FPM Einstellungen (für Alfahosting) |
| `logs/` | Log-Verzeichnis mit Sicherheit |
| `README.md` | Diese Datei |
| `ALFAHOSTING_ANLEITUNG.md` | Ausführliche Alfahosting-Anleitung |

---

## 🔒 Sicherheit:

Nach erfolgreicher Installation:

### Option 1: Verzeichnis löschen (EMPFOHLEN)
```bash
# Per FTP: Lösche kompletten /install/ Ordner
```

### Option 2: Zugriff sperren
Füge in die Haupt-.htaccess (im Root) hinzu:
```apache
<Directory "install">
    Order allow,deny
    Deny from all
</Directory>
```

### Option 3: Passwort-Schutz
```apache
# In install/.htaccess:
AuthType Basic
AuthName "Installation geschützt"
AuthUserFile /pfad/zur/.htpasswd
Require valid-user
```

---

## 📋 Installations-Schritte:

1. **Download:** Lädt alle Dateien von GitHub (automatisch)
2. **Datenbank:** Konfiguration der Datenbank-Verbindung
3. **Admin:** Erstellt Admin-Account
4. **Fertig:** Weiterleitung zum Login

---

## ⚠️ Wichtig:

- ✅ Nur für die **erste Installation** verwenden
- ✅ Nach Installation **löschen oder schützen**
- ✅ Enthält **sensible Informationen** (database_complete.sql)
- ✅ Sollte **nicht öffentlich zugänglich** bleiben

---

## 🔧 Bei Problemen:

### Log-Datei prüfen:
```
install/logs/install_DATUM.log
```

### Support:
- **GitHub Issues:** https://github.com/TrofyTT/Stammtisch/issues
- **Alfahosting-Anleitung:** `ALFAHOSTING_ANLEITUNG.md`

---

**Version:** 1.0
**Erstellt:** 2025-10-31
**GitHub:** https://github.com/TrofyTT/Stammtisch
