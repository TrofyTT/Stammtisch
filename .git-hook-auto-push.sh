#!/bin/bash
# Automatischer Git-Push nach Änderungen

# Zeige was geändert wurde
git status --short

# Prüfe ob es Änderungen gibt
if [ -n "$(git status --porcelain)" ]; then
    echo "📦 Änderungen gefunden, pushe zu GitHub..."
    
    # Alle Änderungen hinzufügen
    git add .
    
    # Commit mit Timestamp
    TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
    git commit -m "Auto-Update: $TIMESTAMP" --no-verify
    
    # Push zu GitHub
    git push origin main
    
    echo "✅ Push erfolgreich!"
else
    echo "✅ Keine Änderungen zu pushen"
fi

