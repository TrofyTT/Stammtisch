# 🚀 Datenbank direkt importieren

## Option 1: Via phpMyAdmin (EINFACHSTE Methode)

1. **Alfahosting-Kundenpanel öffnen**
2. **phpMyAdmin öffnen**
3. **Deine Datenbank auswählen** (links in der Liste)
4. **Tab "Importieren" klicken**
5. **Datei auswählen:** `database_complete.sql`
6. **Format:** SQL (sollte automatisch erkannt werden)
7. **Auf "Ausführen" klicken**
8. **Fertig!** ✅

---

## Option 2: Via MySQL-Kommandozeile (falls SSH-Zugriff vorhanden)

Wenn du SSH-Zugriff auf den Alfahosting-Server hast:

```bash
# Auf dem Server (via SSH) ausführen:
mysql -u [DEIN_DB_USER] -p'[DEIN_DB_PASSWORT]' -h 127.0.0.1 -P 3307 [DEINE_DATENBANK] < database_complete.sql
```

Oder interaktiv (Passwort wird abgefragt):

```bash
mysql -u [DEIN_DB_USER] -p -h 127.0.0.1 -P 3307 [DEINE_DATENBANK] < database_complete.sql
# Passwort eingeben: [DEIN_DB_PASSWORT]
```

---

## Option 3: Via MySQL-Client (von lokal aus, falls Remote-Zugriff möglich)

**WICHTIG:** Normalerweise funktioniert das NICHT, weil MySQL auf Alfahosting nur intern erreichbar ist (127.0.0.1). 

Falls du aber einen SSH-Tunnel hast oder MySQL von außen erreichbar ist:

```bash
mysql -u kdph7973_pimmel -p'Oh?oNQ&~M428FSv5' -h franggn.de -P 3307 kdph7973_pimmel < database_complete.sql
```

**Oder mit explizitem Host:**

```bash
mysql -u kdph7973_pimmel -p'Oh?oNQ&~M428FSv5' -h [ALFAHOSTING-MYSQL-HOST] -P 3307 kdph7973_pimmel < database_complete.sql
```

---

## ✅ Was wird importiert?

Die Datei `database_complete.sql` enthält:

- ✅ Alle Tabellen (users, termine, anwesenheiten, settings, spiele, etc.)
- ✅ Alle Updates (avatar, rang, color, achievements)
- ✅ Standard-Achievements (Er frisst alles, Streber, Verlorenes Kind)
- ✅ Admin-Benutzer (admin@stammtisch.de / admin123)

**WICHTIG:** Falls die Datenbank bereits existiert, werden existierende Tabellen NICHT gelöscht, sondern nur erweitert (IF NOT EXISTS).

---

## ⚠️ Bei Problemen

**"Table already exists" Fehler:**
- Das ist OK! Die Tabellen existieren bereits.
- Die `CREATE TABLE IF NOT EXISTS` Statements verhindern Fehler.
- Falls du die Datenbank komplett neu aufsetzen willst, lösche zuerst alle Tabellen in phpMyAdmin.

**"Access denied" Fehler:**
- Prüfe die Datenbank-Anmeldedaten
- Stelle sicher, dass dein Datenbank-Benutzer existiert und die richtigen Rechte hat

**"Connection refused" Fehler:**
- Die MySQL-Verbindung ist nur intern erreichbar
- Verwende Option 1 (phpMyAdmin) oder Option 2 (SSH auf dem Server)

---

## 🎯 Empfohlene Methode

**Für die meisten Nutzer: Option 1 (phpMyAdmin)** ist am einfachsten!

1. Einmal hochladen
2. Einmal importieren
3. Fertig! ✅

