# Stammtisch App

Web-basierte Verwaltung für Stammtisch-Treffen, Spiele und Achievements.

## Technologie

- PHP 8.0+ (Alfahosting)
- MySQL 8.0
- Vanilla JavaScript
- Apache 2.4

## Server-Zugriff

- **URL:** https://franggn.de
- **Admin:** https://franggn.de/admin.php
- **Updates:** https://franggn.de/update.php

## Update-System

Das Projekt nutzt ein ZIP-basiertes Update-System (kein Git auf Server):

1. Gehe zu: https://franggn.de/update.php
2. Klicke auf "🚀 Jetzt Update starten"
3. System lädt automatisch neueste Version von GitHub
4. Erstellt Backup und aktualisiert alle Dateien

**Geschützte Dateien (werden NICHT überschrieben):**
- `config.local.php` - Datenbank-Credentials
- `uploads/` - Avatar-Bilder
- `logs/` - Log-Dateien

## Entwicklung

**Web-Only:** Alle Änderungen direkt auf dem Server via FTP/SFTP.

**GitHub Sync:** Nach Änderungen committen und pushen, dann via update.php auf Server aktualisieren.

## Features

- ✅ Termin-Verwaltung
- ✅ Anwesenheits-Tracking
- ✅ Spiele (6 Nimmt!)
- ✅ Achievements-System
- ✅ Ranglisten (All-Time & Letzte)
- ✅ Avatar-Upload
- ✅ Admin-Panel

## Sicherheit

- Session-basierte Authentifizierung
- Admin-Rechte-System
- Geschützte Uploads (keine PHP-Ausführung)
- .htaccess Zugriffskontrolle
