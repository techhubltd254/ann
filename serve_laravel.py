import http.server
import os
import sys

PORT = int(sys.argv[1]) if len(sys.argv) > 1 else 8080
DIR = sys.argv[2] if len(sys.argv) > 2 else os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    "kenya-3d-platform", "laravel-backend", "public"
)

os.chdir(DIR)

class Handler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=DIR, **kwargs)

    def log_message(self, format, *args):
        print(f"[{self.log_date_time_string()}] {args[0]} {args[1]} {args[2]}")

    def do_GET(self):
        if self.path == '/' or self.path == '':
            if not os.path.exists('index.php'):
                self.path = '/index-local.html'
        return super().do_GET()

print(f"\n  KICC Laravel Static Preview Server")
print(f"  {'=' * 40}")
print(f"  URL:    http://localhost:{PORT}")
print(f"  Dir:    {DIR}")
print(f"  {'=' * 40}")
print(f"\n  3D Map:       http://localhost:{PORT}/3d/kenya_3d_map.html")
print(f"  Sector:       http://localhost:{PORT}/3d/sector_map.html")
print(f"  Booth:        http://localhost:{PORT}/3d/booth_viewer.html")
print(f"  Home:         http://localhost:{PORT}/\n")

server = http.server.HTTPServer(('0.0.0.0', PORT), Handler)
server.serve_forever()
