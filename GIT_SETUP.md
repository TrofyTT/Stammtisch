# 🚀 Git Setup für automatische Updates

## Schritt 1: Git-Repository initialisieren (lokal)

```bash
# Im Projektverzeichnis
cd /Users/tim/Documents/Trofy/Programme/Pimmelfotzen

# Git initialisieren
git init

# .gitignore ist bereits vorhanden
# Prüfe ob alles korrekt ist
git status

# Alle Dateien hinzufügen
git add .

# Erster Commit
git commit -m "Initial commit - Stammtisch App"
```

## Schritt 2: Git-Repository erstellen (GitHub/GitLab/etc.)

1. **Auf GitHub/GitLab/etc. ein neues Repository erstellen**
   - Name: z.B. `stammtisch-app` oder `pimmelfotzen`
   - **WICHTIG:** Repository sollte PRIVAT sein (wegen sensibler Daten in config.php)

2. **Remote hinzufügen:**
```bash
git remote add origin https://github.com/DEIN-USERNAME/stammtisch-app.git
# Oder SSH: git remote add origin git@github.com:DEIN-USERNAME/stammtisch-app.git
```

3. **Ersten Push:**
```bash
git branch -M main
git push -u origin main
```

## Schritt 3: Auf dem Server einrichten

### Option A: SSH-Zugriff (empfohlen)

1. **SSH auf den Server verbinden:**
```bash
ssh DEIN-USER@franggn.de
```

2. **Im Web-Verzeichnis Git klonen:**
```bash
cd html  # oder public_html
git clone https://github.com/DEIN-USERNAME/stammtisch-app.git .
# Oder SSH: git clone git@github.com:DEIN-USERNAME/stammtisch-app.git .
```

3. **Berechtigungen setzen:**
```bash
chmod 755 uploads/avatars/
```

### Option B: FTP + Git auf Server installieren

Falls Git nicht auf dem Server installiert ist:
1. Dateien wie gewohnt per FTP hochladen
2. Auf dem Server Git initialisieren:
```bash
cd html  # oder public_html
git init
git remote add origin https://github.com/DEIN-USERNAME/stammtisch-app.git
git pull origin main
```

## Schritt 4: SSH-Keys für Git (für automatischen Pull ohne Passwort)

### Auf dem Server:

1. **SSH-Key generieren (falls noch nicht vorhanden):**
```bash
ssh-keygen -t ed25519 -C "franggn.de"
# Key speichern z.B. in ~/.ssh/id_ed25519_github
```

2. **Public Key zu GitHub hinzufügen:**
```bash
cat ~/.ssh/id_ed25519_github.pub
# Inhalt kopieren und zu GitHub Settings → SSH Keys hinzufügen
```

3. **SSH-Config anpassen:**
```bash
nano ~/.ssh/config

# Folgendes hinzufügen:
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_ed25519_github
```

4. **Testen:**
```bash
ssh -T git@github.com
# Sollte "Hi USERNAME! You've successfully authenticated" ausgeben
```

## Schritt 5: Update-Button testen

1. **Admin-Panel öffnen:** `https://franggn.de/admin.php`
2. **"Update von Git laden" klicken**
3. **Bestätigen**
4. **Ergebnis prüfen**

## Schritt 6: Workflow für Updates

### Lokal entwickeln:
```bash
# Änderungen machen
git add .
git commit -m "Beschreibung der Änderungen"
git push origin main
```

### Auf Server aktualisieren:
- **Einfach im Admin-Panel den Update-Button klicken!** ✅
- Oder manuell per SSH:
```bash
cd html  # oder public_html
git pull origin main
```

## 🔒 Sicherheit

### Wichtige Hinweise:

1. **Repository sollte PRIVAT sein** (wegen `config.php` mit DB-Zugangsdaten)
2. **Oder `.gitignore` erweitern** um sensible Dateien auszuschließen:
   ```
   config.php
   config.local.php
   ```
   → Aber dann musst du `config.php` manuell auf dem Server pflegen

3. **Alternativ: Umgebungsvariablen verwenden**
   - Erstelle `config.example.php` (ohne sensible Daten)
   - Nutze auf dem Server Umgebungsvariablen oder separate `config.production.php`

## 📝 .gitignore bereits vorhanden

Die `.gitignore` enthält bereits:
- `uploads/avatars/*` (hochgeladene Bilder)
- `config.local.php` (lokale Konfiguration)

## ⚠️ Was NICHT ins Git sollte

Falls du `config.php` mit echten Zugangsdaten im Repo hast:
- **Repository sollte PRIVAT sein**
- Oder Zugangsdaten auslagern in Umgebungsvariablen
- Oder `config.php` in `.gitignore` aufnehmen

## 🎯 Empfohlene Struktur

```
config.example.php  # Template ohne echte Zugangsdaten
config.php          # Wird auf dem Server erstellt/gepflegt (NICHT im Git)
config.local.php    # Nur lokal (bereits in .gitignore)
```

---

**Viel Erfolg mit Git! 🚀**

