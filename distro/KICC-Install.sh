#!/bin/bash
# KICC Platform — GUI Installer for Linux
# Double-click to install — no terminal needed

DIR="$(cd "$(dirname "$0")" && pwd)"
APP_DIR="$HOME/.kicc-platform"
JAVA="$DIR/server/java-runtime/bin/java"

# If bundled Java doesn't exist, try system Java
if [ ! -f "$JAVA" ]; then
  JAVA=$(command -v java 2>/dev/null || echo "")
fi

show_dialog() {
  if command -v zenity &>/dev/null; then
    zenity "$@"
  elif command -v kdialog &>/dev/null; then
    kdialog "$@"
  elif command -v yad &>/dev/null; then
    yad "$@"
  else
    echo "$@"
  fi
}

show_progress() {
  if command -v zenity &>/dev/null; then
    zenity --progress --pulsate --auto-close --no-cancel --text="$1" 2>/dev/null &
    PID=$!
  fi
}

stop_progress() {
  kill $PID 2>/dev/null || true
}

# Check for Java
if [ -z "$JAVA" ]; then
  show_dialog --error --title="KICC Platform" --text="Java not found.\n\nPlease install Java 21+ from:\nhttps://adoptium.net" --width=400
  exit 1
fi

# Welcome dialog
show_dialog --question --title="KICC Platform" \
  --text="KICC Digital Economy Platform\n\nThis will install the KICC Mother Engine to:\n$APP_DIR\n\nIt includes the admin console, public site,\nand all county data management tools." \
  --ok-label="Install" --cancel-label="Cancel" --width=450 2>/dev/null

if [ $? -ne 0 ]; then
  exit 0
fi

# Install
show_progress "Installing KICC Platform..."

mkdir -p "$APP_DIR"
cp "$DIR/server/kicc-engine.jar" "$APP_DIR/"
cp -r "$DIR/web" "$APP_DIR/"
cp "$DIR/server/start-kicc.sh" "$APP_DIR/"

# Create desktop shortcut
mkdir -p "$HOME/Desktop"
cat > "$HOME/Desktop/KICC-Mother-Engine.desktop" << DESKTOP
[Desktop Entry]
Name=KICC Mother Engine
Comment=KICC Digital Economy Platform
Exec=$APP_DIR/start-kicc.sh
Icon=applications-internet
Terminal=false
Type=Application
Categories=Office;Development;
DESKTOP

chmod +x "$HOME/Desktop/KICC-Mother-Engine.desktop"
chmod +x export KICC_WEB_DIR="$APP_DIR/web"
"$APP_DIR/start-kicc.sh"

# Fix Java path in launcher
if [ -f "$JAVA" ]; then
  sed -i "s|^JAVA=.*|JAVA=\"$JAVA\"|" export KICC_WEB_DIR="$APP_DIR/web"
"$APP_DIR/start-kicc.sh"
fi

# App menu entry
mkdir -p "$HOME/.local/share/applications"
cp "$HOME/Desktop/KICC-Mother-Engine.desktop" "$HOME/.local/share/applications/"

mark_trusted() {
  gio set "$1" metadata::trusted true 2>/dev/null
  gio set "$1" metadata::trusted yes 2>/dev/null
}
mark_trusted "$HOME/Desktop/KICC-Mother-Engine.desktop"
mark_trusted "$HOME/.local/share/applications/KICC-Mother-Engine.desktop"

stop_progress

# Success
show_dialog --info --title="KICC Platform" \
  --text="✅ Installation complete!\n\nKICC Mother Engine is ready.\n\nDouble-click 'KICC Mother Engine' on your desktop\nto start the server and open the admin console.\n\nAdmin login:\nadmin@kicc.go.ke / Admin@2026" \
  --width=450

# Ask to launch now
show_dialog --question --title="KICC Platform" \
  --text="Launch KICC Mother Engine now?" \
  --ok-label="Launch" --cancel-label="Close" --width=350 2>/dev/null

if [ $? -eq 0 ]; then
  export KICC_WEB_DIR="$APP_DIR/web"
"$APP_DIR/start-kicc.sh" &
fi
