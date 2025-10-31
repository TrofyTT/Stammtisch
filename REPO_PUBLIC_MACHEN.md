# 🔓 Repository öffentlich machen

## Warum ist das jetzt sicher?

✅ **Alle sensiblen Daten wurden entfernt:**
- Keine echten Datenbank-Credentials mehr in `config.php`
- Keine echten Passwörter in Dokumentation
- Alle persönlichen Daten entfernt
- `config.local.php` ist in `.gitignore` (wird nicht committed)

## 📝 Schritt-für-Schritt Anleitung:

### 1. Auf GitHub gehen
- Öffne: https://github.com/TrofyTT/Stammtisch

### 2. Settings öffnen
- Klicke auf **"Settings"** (oben im Repository)

### 3. Zur "Danger Zone" scrollen
- Scroll ganz nach unten zur Sektion **"Danger Zone"**

### 4. Repository öffentlich machen
- Klicke auf **"Change visibility"**
- Wähle **"Make public"**
- Bestätige mit dem Repository-Namen: `TrofyTT/Stammtisch`

### 5. Fertig! ✅
- Das Repository ist jetzt öffentlich
- ZIP-Download funktioniert jetzt automatisch
- Die `install.php` kann jetzt alle Dateien herunterladen

---

## 🔐 Was ist geschützt?

Die folgenden Dateien enthalten **KEINE** sensiblen Daten mehr:

✅ `config.php` - Nur Platzhalter
✅ `database_complete.sql` - Keine echten DB-Namen
✅ Alle `.md` Dateien - Nur Platzhalter
✅ Alle anderen Dateien - Öffentlich sicher

**Geschützt:**
- `config.local.php` - Ist in `.gitignore`, wird nie committed
- `uploads/` - Enthält nur Benutzer-Avatare (wird nicht ins Git committed)

---

## ⚠️ Falls du es privat lassen willst:

Alternative: Du kannst alle Dateien manuell per FTP hochladen (siehe `FTP_UPLOAD_LISTE.md`).

Aber: Das Repository enthält jetzt keine sensiblen Daten mehr, daher ist es **sicher**, es öffentlich zu machen!

