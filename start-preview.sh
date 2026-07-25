#!/bin/bash
# Start the KICC Laravel static preview server
# Access at http://localhost:8081

PORT=${1:-8081}
DIR="/home/kicc/Desktop/kicc/kenya-3d-platform/laravel-backend/public"

echo "Starting KICC preview server on port $PORT..."
echo "URL: http://localhost:$PORT"
echo ""

# Kill any existing server on this port
kill $(lsof -ti :$PORT) 2>/dev/null

cd "$DIR"
python3 -m http.server $PORT > /dev/null 2>&1 &
disown

sleep 1
echo "Server running (PID $!)"
echo ""
echo "Available endpoints:"
echo "  Home page:    http://localhost:$PORT/"
echo "  3D Map:       http://localhost:$PORT/3d/kenya_3d_map.html"
echo "  Sector:       http://localhost:$PORT/3d/sector_map.html"
echo "  Booth:        http://localhost:$PORT/3d/booth_viewer.html"
echo "  Videos:       http://localhost:$PORT/storage/screens/"
echo ""
echo "To stop: kill $(lsof -ti :$PORT)"
