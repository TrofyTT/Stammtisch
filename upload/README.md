# 📤 FTP-Upload Ordner

## 🎯 Was ist das?

Dieser Ordner enthält **ALLES**, was du per FTP hochladen musst.

## ✅ So funktioniert's:

1. **Lade diesen kompletten Ordner `upload/` per FTP hoch**
2. **Alle Dateien aus `upload/` in das Hauptverzeichnis verschieben**
   - Also: `upload/install.php` → Hauptverzeichnis/`install.php`
   - `upload/logs/` → Hauptverzeichnis/`logs/`
3. **Fertig!**

---

## 📁 Was ist in diesem Ordner?

### ✅ `install.php`
**Das ist die Hauptdatei!** Diese Datei:
- Lädt automatisch alle anderen Dateien von GitHub herunter
- Installiert die komplette Anwendung
- Du musst nur diese Datei (und optional den `logs/` Ordner) hochladen

### ✅ `logs/`
**Log-Ordner** (optional, wird aber automatisch erstellt):
- Enthält `.htaccess` für Sicherheit (blockiert HTTP-Zugriff)
- Wird automatisch von `install.php` erstellt, wenn nicht vorhanden
- Aber: Wenn du ihn schon hochlädst, funktioniert das Logging sofort

---

## 🚀 Schnellstart:

### Option 1: Nur install.php (EMPFOHLEN)
```
1. Lade nur install.php hoch
2. Öffne install.php im Browser
3. Die Installation lädt automatisch alle Dateien von GitHub
4. Fertig! ✅
```

### Option 2: Mit logs/ Ordner
```
1. Lade install.php UND logs/ Ordner hoch
2. Öffne install.php im Browser
3. Logging funktioniert sofort (kein "Permission Denied")
4. Die Installation lädt automatisch alle Dateien von GitHub
5. Fertig! ✅
```

---

## 📋 FTP-Upload Checkliste:

### Minimal (Nur install.php):
- [ ] `install.php` hochladen

### Empfohlen (install.php + logs/):
- [ ] `install.php` hochladen
- [ ] `logs/` Ordner hochladen (mit `.htaccess`)
- [ ] Berechtigungen setzen: `logs/` → 755

---

## 🔐 Berechtigungen:

Nach dem Upload:
- `install.php` → **644** (Standard)
- `logs/` → **755**
- `logs/.htaccess` → **644**

---

## ⚠️ WICHTIG:

**DU MUSST NICHT ALLE DATEIEN HOCHLADEN!**

Die `install.php` lädt automatisch alle anderen Dateien von GitHub herunter:
- ✅ Alle PHP-Dateien
- ✅ Alle CSS/JS-Dateien
- ✅ Alle Assets
- ✅ Alle SQL-Dateien

**Ausnahme:** Wenn das GitHub-Repository privat ist, funktioniert der Auto-Download nicht. Dann musst du alle Dateien manuell hochladen (siehe `FTP_UPLOAD_LISTE.md` im Hauptverzeichnis).

---

## 💡 Tipp:

Nach dem Upload:
1. Öffne: `https://deine-domain.de/install.php`
2. Wenn Dateien fehlen, lädt die Installation sie automatisch herunter
3. Danach: Installation durchführen (Datenbank-Credentials eingeben)

---

**Viel Erfolg! 🚀**

