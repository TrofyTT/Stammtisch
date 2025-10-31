#!/bin/bash
# Auto-Sync Script für /install/ Verzeichnis
# Hält install/ und upload/ immer synchron

echo "🔄 Synchronisiere install/ und upload/ Verzeichnisse..."

# Erstelle Verzeichnisse falls nicht vorhanden
mkdir -p install
mkdir -p upload

# Kopiere von install/ nach upload/ (install/ ist die Quelle!)
if [ -f "install/install.php" ]; then
    echo "📦 Synchronisiere install.php → upload/..."
    cp -f install/install.php upload/install.php
else
    echo "⚠️  install/install.php nicht gefunden"
fi

if [ -f "install/database_complete.sql" ]; then
    echo "📦 Synchronisiere database_complete.sql → upload/..."
    cp -f install/database_complete.sql upload/database_complete.sql
else
    echo "⚠️  install/database_complete.sql nicht gefunden"
fi

# Kopiere .htaccess und .user.ini
if [ -f "install/.htaccess" ]; then
    echo "📦 Synchronisiere .htaccess → upload/..."
    cp -f install/.htaccess upload/.htaccess
fi

if [ -f "install/.user.ini" ]; then
    echo "📦 Synchronisiere .user.ini → upload/..."
    cp -f install/.user.ini upload/.user.ini
fi

# Kopiere logs/ Ordner
if [ -d "install/logs" ]; then
    echo "📦 Synchronisiere logs/ → upload/..."
    cp -rf install/logs upload/
fi

# Kopiere README und Anleitungen
if [ -f "install/README.md" ]; then
    cp -f install/README.md upload/README.md
fi

if [ -f "install/ALFAHOSTING_ANLEITUNG.md" ]; then
    cp -f install/ALFAHOSTING_ANLEITUNG.md upload/ALFAHOSTING_ANLEITUNG.md
fi

echo ""
echo "✅ Synchronisation abgeschlossen!"
echo ""
echo "📁 Verzeichnis-Struktur:"
echo "   install/ (QUELLE)"
echo "   ├── install.php"
echo "   ├── database_complete.sql"
echo "   ├── .htaccess"
echo "   ├── .user.ini"
echo "   ├── logs/"
echo "   ├── README.md"
echo "   └── ALFAHOSTING_ANLEITUNG.md"
echo ""
echo "   upload/ (SYNCHRONISIERT)"
echo "   ├── install.php ✅"
echo "   ├── database_complete.sql ✅"
echo "   ├── .htaccess ✅"
echo "   ├── .user.ini ✅"
echo "   └── logs/ ✅"
