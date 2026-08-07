#!/bin/bash
# KICC — Install desktop shortcuts and launcher
DIR="$(cd "$(dirname "$0")" && pwd)"

echo "Installing KICC desktop shortcuts..."

# Copy .desktop file to desktop
cp "$DIR/KICC-Mother-Engine.desktop" "$HOME/Desktop/" 2>/dev/null && echo "  ✓ Desktop shortcut created"

# Also add to applications menu
mkdir -p "$HOME/.local/share/applications"
cp "$DIR/KICC-Mother-Engine.desktop" "$HOME/.local/share/applications/" && echo "  ✓ Application menu entry created"

# Update desktop database
update-desktop-database "$HOME/.local/share/applications/" 2>/dev/null || true

echo ""
echo "Done! You can now:"
echo "  • Double-click 'KICC Mother Engine' on your desktop"
echo "  • OR find it in your applications menu"
echo "  • OR run: cd server && ./start-kicc.sh"
echo ""
echo "Default admin: admin@kicc.go.ke / Admin@2026"
