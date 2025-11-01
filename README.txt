================================================================================
STAMMTISCH - Web-App für Spiel-Tracking
================================================================================

Eine moderne Web-Anwendung zum Tracken von Kartenspielen und Punkten bei
Stammtisch-Treffen. Mit Gaming-Dashboard-Design und Echtzeit-Statistiken.

Version: 2.0 (Gaming Dashboard Edition)
Datum: November 2025

================================================================================
FEATURES
================================================================================

✅ Dashboard
   - Spiele Achievements & Stammtisch Achievements
   - All-Time Rangliste (niedrigste Punkte = Gewinner)
   - All-Time Looser (höchste Punkte)
   - Quick Stats

✅ Spiele-System
   - Spiele erstellen und spielen
   - Rundenbasierte Punkteingabe
   - Automatische Gewinner-Ermittlung
   - Spiel-Historie mit Status (aktiv/beendet)

✅ Achievement-System
   - Automatische Vergabe von Achievements
   - Spiel-Achievements (pro Spiel)
   - Stammtisch-Achievements (dauerhaft)

✅ Statistiken
   - Detaillierte Spieler-Statistiken
   - Charts und Grafiken (Chart.js)
   - Durchschnittswerte pro Spiel
   - Gewinner/Verlierer-Übersichten

✅ Mitgliederverwaltung (Admin)
   - User hinzufügen/bearbeiten/löschen
   - Avatar-Upload mit Cropper
   - Farb-Zuweisung für Spieler

✅ Modernes Design
   - Gaming-Dashboard-Ästhetik (Valorant-Style)
   - Dark Theme mit farbigen Akzenten
   - Gradient Cards & Glow-Effekte
   - Komplett Responsive (Mobile First)

================================================================================
TECHNISCHE DETAILS
================================================================================

Backend:
- PHP 8.0+
- MySQL 8.0
- Session-basierte Authentifizierung
- RESTful-ähnliche API (api.php)

Frontend:
- Vanilla JavaScript (ES6+)
- CSS3 mit Custom Properties
- Chart.js 4.4.0 für Statistiken
- Responsive Design (Mobile First)

Hosting:
- Alfahosting Server (franggn.de)
- MySQL Datenbank (Port 3307)
- HTTPS/SSL aktiviert

================================================================================
INSTALLATION
================================================================================

Die App ist bereits live auf: https://franggn.de

Für manuelle Installation:

1. Alle Dateien auf Server hochladen (via FTP)
2. config.local.php erstellen mit DB-Credentials:

   <?php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3307');
   define('DB_NAME', 'kdph7973_sven');
   define('DB_USER', 'kdph7973_svenni');
   define('DB_PASS', 'IHR_PASSWORT');

3. Datenbank importieren (falls nicht vorhanden)
4. uploads/avatars/ Ordner beschreibbar machen (chmod 755)
5. Im Browser aufrufen und registrieren

================================================================================
VERWENDUNG
================================================================================

ERSTER LOGIN:
1. Auf https://franggn.de gehen
2. Registrieren mit Name, E-Mail, Passwort
3. Nach Login kommst du zum Dashboard

SPIEL ERSTELLEN:
1. Auf "Spiele" klicken
2. "+ Neues Spiel" Button
3. Name eingeben, Spieler auswählen
4. "Spiel starten"

SPIEL SPIELEN:
1. Im Spiel auf "Spielen" klicken
2. Jeder Spieler gibt seine Punkte ein
3. "Runde abschließen" klicken
4. Wiederholen bis Spiel fertig
5. "Spiel beenden" wenn fertig

STATISTIKEN ANSEHEN:
1. Auf "Statistiken" klicken
2. Verschiedene Stats & Charts ansehen
3. Filter nutzen für spezifische Zeiträume

MITGLIEDER VERWALTEN (nur Admin):
1. Auf "Mitglieder" klicken
2. "+ Neues Mitglied" für neue User
3. Avatar hochladen (optional)
4. Farbe zuweisen für bessere Erkennung

================================================================================
UPDATES
================================================================================

Die App nutzt ein automatisches Update-System:

1. Auf https://franggn.de/update.php gehen
2. "🚀 Jetzt Update starten" klicken
3. System lädt neueste Version von GitHub
4. Automatisches Backup wird erstellt
5. Fertig!

WICHTIG: config.local.php wird NICHT überschrieben!

================================================================================
DATENBANK-STRUKTUR
================================================================================

Wichtigste Tabellen:

users
- Benutzer-Accounts
- Avatar, Farbe, Admin-Flag

spiele
- Spiele mit Name, Datum, Status
- Status: 'aktiv' oder 'beendet'

spiel_teilnahme
- Verknüpfung User ↔ Spiel
- Speichert welcher User in welchem Spiel dabei ist

spiel_runden
- Punkte pro Runde pro Spieler
- Runden-Nummer, Punkte

achievements
- Achievement-Definitionen
- Name, Beschreibung, Icon, Typ

user_achievements
- Vergebene Achievements
- User + Achievement + Datum

================================================================================
ADMIN-FUNKTIONEN
================================================================================

Als Admin (is_admin = 1 in DB) kannst du:

✓ Alle Mitglieder verwalten
✓ User bearbeiten/löschen
✓ Spiele löschen
✓ Admin-Panel nutzen
✓ Statistiken für alle User sehen

Ersten Admin manuell in DB setzen:
UPDATE users SET is_admin = 1 WHERE id = 1;

================================================================================
DESIGN-SYSTEM
================================================================================

Farben:
- Primary (Blau):     #007AFF
- Secondary (Lila):   #5856D6
- Success (Grün):     #34C759
- Danger (Rot):       #FF3B30
- Background:         #000000
- Cards:              #1C1C1E / #2C2C2E

Card-Styles:
- Winner Cards:       Grüner Gradient
- Loser Cards:        Roter Gradient
- Game Achievements:  Blauer Gradient
- Stammtisch Achieve: Lila Gradient

Border Radius: 18-20px (modern & rund)
Shadows: 0 2px 12px rgba(0,0,0,0.3)
Hover: Glow-Effekte in passenden Farben

================================================================================
ENTWICKLUNG
================================================================================

Web-Only Development (kein lokales Setup):

1. Code ändern
2. git add -A
3. git commit -m "Beschreibung"
4. git push origin main
5. Auf update.php deployen

Git Repository:
https://github.com/TrofyTT/Stammtisch

================================================================================
TROUBLESHOOTING
================================================================================

Problem: "Datenbankverbindung fehlgeschlagen"
Lösung: config.local.php prüfen, DB-Credentials korrekt?

Problem: "Access denied"
Lösung: Nicht eingeloggt? Session abgelaufen? Neu einloggen.

Problem: "Seite lädt nicht"
Lösung: Browser-Cache leeren (Strg+Shift+R)

Problem: "Avatar wird nicht angezeigt"
Lösung: uploads/avatars/ Ordner beschreibbar? (chmod 755)

Problem: "Update schlägt fehl"
Lösung: Logs in logs/ Ordner checken, ggf. manuell via FTP

Logs:
- logs/error.log (auf Server)
- Browser DevTools Console (F12)

================================================================================
SICHERHEIT
================================================================================

✓ XSS-Prevention (htmlspecialchars)
✓ SQL-Injection Prevention (PDO Prepared Statements)
✓ Session-Security (httponly, secure flags)
✓ File-Upload Whitelist (nur JPG/PNG)
✓ .htaccess schützt uploads/ vor PHP-Execution
✓ CSRF-Protection über Session-Checks

Passwörter:
- Werden mit password_hash() gehashed (bcrypt)
- Mindestlänge: 6 Zeichen
- Nie im Klartext gespeichert

================================================================================
PERFORMANCE
================================================================================

- Lazy-Loading für Chart.js
- Avatar-Caching
- CSS Animations mit GPU-Acceleration
- Optimierte Datenbank-Queries
- Minimale API-Calls

Empfohlene Browser:
- Chrome 90+
- Safari 14+
- Firefox 88+
- Edge 90+

Optimiert für:
- iPhone/iPad (iOS Safari)
- Android (Chrome)
- Desktop (alle modernen Browser)

================================================================================
LIZENZ & CREDITS
================================================================================

Projekt: Stammtisch App
Entwickelt: 2025
Framework: Vanilla PHP/JS
Design: Gaming Dashboard Style (Valorant-inspiriert)

Abhängigkeiten:
- Chart.js 4.4.0 (MIT License)
- Cropper.js 1.6.2 (MIT License)

================================================================================
KONTAKT
================================================================================

Bei Fragen oder Problemen:

E-Mail: tim.tinnefeld@trofy.de
GitHub: https://github.com/TrofyTT/Stammtisch
Server: franggn.de

================================================================================
CHANGELOG
================================================================================

Version 2.0 (November 2025)
- Komplett neues Gaming-Dashboard-Design
- Mobile-First Optimierung (8px padding)
- Gradient Cards mit Glow-Effekten
- Alle Achievements Cards mit Farben
- Rangliste/Looser mit Gradients
- Border-Radius überall erhöht (18-20px)
- Stärkere Shadows für mehr Tiefe
- Web-Only Development Workflow

Version 1.0 (Oktober 2025)
- Initiales Release
- Basis-Features implementiert
- Dashboard, Games, Stats, Members
- Achievement-System
- Admin-Panel

================================================================================

Viel Spaß beim Spielen! 🎮🍻
