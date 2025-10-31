# 🔧 Debug-Anleitung für Error 500

## Problem:
Nach erfolgreichem Download von GitHub kommt ein **Error 500** und das Installationsformular wird nicht angezeigt.

## ✅ Lösung:

Die `install.php` wurde jetzt so angepasst, dass:

1. **Nach erfolgreichem Download** wird die Seite automatisch neu geladen (`?step=install`)
2. **Das Installationsformular** wird dann angezeigt, wenn `$needsDownload === false`
3. **Verbesserte Fehlerbehandlung** fängt alle PHP-Fehler ab und schreibt sie ins Log

## 📋 Schritte zur Fehlerbehebung:

### 1. Prüfe die Log-Datei:
```bash
logs/install_YYYY-MM-DD.log
```

### 2. Häufige Fehlerquellen:

#### a) Parse Error
- **Symptom**: "syntax error, unexpected..."
- **Lösung**: PHP-Syntax-Fehler in `install.php` prüfen

#### b) Fatal Error (Function not defined)
- **Symptom**: "Call to undefined function..."
- **Lösung**: Stelle sicher, dass `deleteDirectory()` am Anfang der Datei definiert ist

#### c) Memory Limit
- **Symptom**: "Allowed memory size exhausted"
- **Lösung**: `memory_limit` in `install.php` erhöhen (aktuell: 256M)

#### d) Timeout
- **Symptom**: "Maximum execution time exceeded"
- **Lösung**: `max_execution_time` in `install.php` erhöhen (aktuell: 300 Sekunden)

### 3. Manuelle Prüfung:

1. **Lade `install.php` neu** nach dem Git-Push
2. **Prüfe Browser-Konsole** (F12) für JavaScript-Fehler
3. **Prüfe Log-Datei** für PHP-Fehler

## 🔄 Wenn es immer noch nicht geht:

1. **Lösche alle temporären Dateien:**
   - `install_temp.zip`
   - `install_temp/` (Ordner)
   - `update_temp.zip`
   - `update_temp/` (Ordner)

2. **Lade `install.php` erneut hoch** per FTP

3. **Prüfe Berechtigungen:**
   - `install.php` → 644
   - `logs/` → 755
   - `uploads/avatars/` → 755

## 📞 Nächste Schritte:

Nach dem aktuellen Fix sollte:
- ✅ Download erfolgreich sein (wie in deinem Log sichtbar)
- ✅ Automatischer Reload nach Download
- ✅ Installationsformular erscheint
- ✅ Kein Error 500 mehr

**Falls der Error 500 weiterhin besteht**, liegt der Fehler wahrscheinlich **nach** dem Download, beim Rendering des HTML-Formulars. In diesem Fall bitte die Log-Datei senden.

