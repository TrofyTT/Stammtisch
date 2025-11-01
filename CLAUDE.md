# Claude Code Context - Stammtisch App

## Projektübersicht

**Stammtisch** ist eine moderne Web-App zum Tracken von Kartenspielen und Punkten bei Stammtisch-Treffen. Die App hat ein modernes Gaming-Dashboard-Design (Valorant-Style) und läuft komplett web-basiert.

## Tech Stack

- **Backend**: PHP 8.0+ mit MySQL 8.0
- **Frontend**: Vanilla JavaScript, CSS3 (Custom Properties)
- **Charts**: Chart.js 4.4.0
- **Hosting**: Alfahosting (franggn.de)
- **Version Control**: Git + GitHub

## Projektstruktur

```
/
├── index.php              # Login/Registrierung
├── dashboard.php          # Haupt-Dashboard
├── games.php             # Spiele-Übersicht
├── game.php              # Einzelnes Spiel spielen
├── stats.php             # Statistiken
├── members.php           # Mitgliederverwaltung (Admin)
├── admin.php             # Admin-Panel
├── api.php               # REST-ähnliche API
├── config.php            # Haupt-Config
├── config.local.php      # DB-Credentials (gitignored)
├── update.php            # Auto-Update von GitHub
├── assets/
│   ├── css/style.css     # Alle Styles (2600+ Zeilen)
│   ├── js/
│   │   ├── dashboard.js  # Dashboard-Logik
│   │   ├── nav.js        # Navigation
│   │   └── ...
│   └── img/
└── uploads/
    └── avatars/          # User-Avatare
```

## Wichtige Features

### 1. Dashboard (dashboard.php)
- **4 Cards Layout**: Spiele Achievements, Stammtisch Achievements, Rangliste, Looser
- **Gradient Backgrounds**: Jede Card hat eigene Farbe (Blau, Lila, Grün, Rot)
- **Responsive**: 4 Spalten Desktop → 2 Tablet → 1 Mobile

### 2. Spiele System
- Spiele können erstellt und gespielt werden
- Rundenbasiert: Jeder Spieler gibt Punkte pro Runde ein
- Automatische Gewinner-Ermittlung (niedrigste Punktzahl)
- Status: "aktiv" oder "beendet"

### 3. Achievements System
- **Spiel-Achievements**: z.B. "Er frisst alles" (schlechteste Runde)
- **Stammtisch-Achievements**: Langzeit-Achievements
- Werden automatisch vergeben basierend auf Spielergebnissen

### 4. Statistiken
- All-Time Rankings (Gewinner = niedrigste Gesamtpunkte)
- Letzter Stammtisch Stats
- Durchschnittliche Punkte pro Spiel
- Charts mit Chart.js

## Development Workflow

### Web-Only Development
Seit dem Redesign wird **NUR noch web-basiert** entwickelt:

1. **Änderungen machen**: Code direkt bearbeiten
2. **Committen**: `git add -A && git commit -m "message"`
3. **Pushen**: `git push origin main`
4. **Deployen**: Auf https://franggn.de/update.php gehen und "Update starten" klicken

**Keine lokale Entwicklung mehr!** Alles läuft direkt auf dem Server.

## Datenbank

### Hosting Details
- **Server**: 127.0.0.1:3307
- **Datenbank**: kdph7973_sven
- **User**: kdph7973_svenni
- **Credentials**: In `config.local.php` (nicht in Git!)

### Wichtige Tabellen
- `users` - Benutzer mit Avatar, Farbe, Admin-Flag
- `spiele` - Spiele mit Name, Datum, Status
- `spiel_teilnahme` - Welche User in welchem Spiel
- `spiel_runden` - Punkte pro Runde pro Spieler
- `achievements` - Achievement-Definitionen
- `user_achievements` - Vergebene Achievements

## Design System

### Moderne Gaming-Ästhetik
- **Dark Theme**: Schwarz/Dunkelgrau mit farbigen Akzenten
- **Gradient Cards**: Subtile Farbverläufe für verschiedene Bereiche
- **Glow Effects**: Hover-Effekte mit farbigem Leuchten
- **Shadows**: Tiefe durch `0 2px 12px rgba(0,0,0,0.3)`
- **Border Radius**: 18-20px für moderne, runde Cards

### CSS Variables
```css
--primary: #007AFF (Blau)
--secondary: #5856D6 (Lila)
--success: #34C759 (Grün)
--danger: #FF3B30 (Rot)
--bg: #000000 (Schwarz)
--bg-secondary: #1C1C1E
--bg-tertiary: #2C2C2E
```

### Mobile-First
- Container padding: **8px** (Mobile) vs 20px (Desktop)
- Kompaktes Layout, wenig Whitespace
- Touch-optimiert (44px min. Tap-Targets)

## API Endpoints (api.php)

Alle Requests: `api.php?action=<action>`

**User:**
- `get_current_user` - Aktueller User
- `login` - Login
- `register` - Registrierung

**Dashboard:**
- `get_user_achievements` - Achievements für Dashboard
- `get_last_stammtisch_game_stats` - Rangliste/Looser Daten

**Games:**
- `get_spiele` - Alle Spiele
- `create_spiel` - Neues Spiel erstellen
- `get_spiel_details` - Details eines Spiels
- `submit_round` - Runde abschließen

**Stats:**
- `get_stats` - Globale Statistiken
- `get_player_stats` - Spieler-spezifische Stats

## Typische Aufgaben

### Neues Feature hinzufügen
1. PHP-Logic in entsprechende Datei oder `api.php`
2. JavaScript in `assets/js/` (z.B. `dashboard.js`)
3. Styles in `assets/css/style.css`
4. Committen & Pushen
5. Update via `update.php`

### Bugfix
1. Logs checken: `logs/` Verzeichnis auf Server
2. Fix implementieren
3. Committen mit aussagekräftiger Message
4. Pushen & Deployen

### Design-Änderungen
- **Nur CSS bearbeiten**: `assets/css/style.css`
- Gradient-Farben für Cards: rgba() mit 0.08/0.02 Opacity
- Hover-Glow: `box-shadow: 0 8px 32px rgba(color, 0.3), 0 0 20px rgba(color, 0.1)`
- Border-radius: 18-20px für moderne Cards

## Bekannte Quirks

1. **Config-Datei**: `config.local.php` muss manuell via FTP hochgeladen werden
2. **Avatars**: Werden in `uploads/avatars/` gespeichert, `.htaccess` verhindert PHP-Execution
3. **Update-System**: `update.php` lädt ZIP von GitHub, kein `git pull`
4. **Session-Security**: Sessions sind httponly, secure (wenn HTTPS)
5. **Achievements**: Werden NICHT automatisch neu berechnet bei alten Spielen

## Testing

- **Manuell**: Auf https://franggn.de testen
- **Mobile**: Chrome DevTools oder echtes Gerät
- **Browser**: Primär Chrome/Safari (iOS-optimiert)

## Admin-Features

Nur für User mit `is_admin = 1`:
- Mitglieder verwalten (`members.php`)
- Admin-Panel (`admin.php`)
- Spiele löschen/bearbeiten
- User-Avatare hochladen

## Git Workflow

```bash
# Änderungen committen
git add -A
git commit -m "feat: neue Funktion"
# oder "fix:", "design:", "refactor:"

# Pushen
git push origin main

# Auf Server deployen
# → https://franggn.de/update.php aufrufen
```

**Branch**: `main` (Haupt-Branch, immer deploybar)

## Performance Notes

- Chart.js lazy-loaded
- Avatare werden gecacht
- API-Calls nutzen `fetch()` mit Error-Handling
- CSS Animations mit `will-change` für GPU-Acceleration
- Bilder: Max 500x500px für Avatare

## Security

- ✅ XSS-Prevention: `htmlspecialchars()` überall
- ✅ SQL-Injection: PDO mit Prepared Statements
- ✅ Session-Hijacking: httponly, secure, regenerate
- ✅ File-Upload: Whitelist (JPG, PNG), Größenlimit 5MB
- ✅ CSRF: Wird über Session-Checks gehandhabt
- ⚠️ Rate-Limiting: Nicht implementiert

## Wichtige Dateien für Claude

Wenn du am Projekt arbeitest, lies zuerst:
1. `dashboard.php` - Haupt-UI
2. `api.php` - Backend-Logik (Zeilen 920-1060: get_last_stammtisch_game_stats)
3. `assets/css/style.css` - Design-System
4. `assets/js/dashboard.js` - Frontend-Logik

## Deployment Checklist

- [ ] Code committen mit aussagekräftiger Message
- [ ] `git push origin main`
- [ ] Auf https://franggn.de/update.php gehen
- [ ] "🚀 Jetzt Update starten" klicken
- [ ] Auf Fehlermeldungen achten
- [ ] Testen auf Mobile & Desktop
- [ ] Bei Bedarf: Browser-Cache leeren (Strg+Shift+R)

## Kontakt & Access

- **Server**: franggn.de
- **FTP**: Via Alfahosting-Panel
- **GitHub**: TrofyTT/Stammtisch
- **User**: tim.tinnefeld@trofy.de

---

**Zuletzt aktualisiert**: 2025-11-01
**Claude Code Session**: Vollständiges Redesign im Gaming-Dashboard-Style
