#!/usr/bin/env python3
"""
KICC Portable Admin — Native Desktop Application
GTK3 + WebKit2. POS-style native app. Double-click to launch.
"""

import os, sys, signal, subprocess, time, socket, threading
from pathlib import Path

APP_DIR = Path(__file__).parent.resolve()
PHP_SERVER_PORT = 8090
PHP_SERVER_HOST = "127.0.0.1"
SERVER_URL = f"http://{PHP_SERVER_HOST}:{PHP_SERVER_PORT}"

import gi
gi.require_version('Gtk', '3.0')
gi.require_version('WebKit2', '4.1')
from gi.repository import Gtk, WebKit2, GLib, Gdk, Pango

# ─── PHP Server Manager ──────────────────────────────────────────────
class PHPServer:
    def __init__(self):
        self.process = None
        self.php_binary = self._find_php()

    def _find_php(self):
        import shutil
        for name in ['php', 'php8.5', 'php8.4', 'php8.3']:
            p = shutil.which(name)
            if p: return p
        for p in ['/usr/bin/php', '/usr/bin/php8.5', '/usr/bin/php8.4']:
            if os.path.exists(p): return p
        return 'php'

    def start(self, status_callback=None):
        # Check if server is already running
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(0.5)
        try:
            sock.connect((PHP_SERVER_HOST, PHP_SERVER_PORT))
            sock.close()
            if status_callback: status_callback('Server already running! Loading app...')
            return True
        except:
            sock.close()

        if status_callback: status_callback('Starting PHP server...')

        self.process = subprocess.Popen(
            [self.php_binary, 'artisan', 'serve', f'--host={PHP_SERVER_HOST}', f'--port={PHP_SERVER_PORT}'],
            cwd=str(APP_DIR), stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL,
            preexec_fn=lambda: signal.signal(signal.SIGTERM, signal.SIG_IGN)
        )

        # Wait for server to listen on port (socket check = instant)
        if status_callback: status_callback('Waiting for server...')
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(1)
        for i in range(20):
            try:
                sock.connect((PHP_SERVER_HOST, PHP_SERVER_PORT))
                sock.close()
                if status_callback: status_callback('Server ready! Loading app...')
                return True
            except:
                time.sleep(0.3)
        return False

    def stop(self):
        if self.process:
            try:
                subprocess.run(['pkill', '-f', f'artisan serve.*{PHP_SERVER_PORT}'], capture_output=True)
                self.process.terminate()
            except:
                pass
            self.process = None

# ─── Main Window ─────────────────────────────────────────────────────
class AdminApp(Gtk.Window):
    def __init__(self):
        super().__init__(title="KICC Platform Admin")
        self.set_default_size(1280, 800)
        self.set_position(Gtk.WindowPosition.CENTER)
        self.webview = None

        # Load CSS
        provider = Gtk.CssProvider()
        provider.load_from_data(b"""
            .title-bar { background: #07090F; border-bottom: 1px solid rgba(255,255,255,0.08); padding: 6px 12px; }
            .title-label { color: #fff; font-weight: bold; font-size: 13px; }
            .status-bar { background: #0D1220; border-top: 1px solid rgba(255,255,255,0.05); padding: 4px 12px; }
            .status-label { color: rgba(255,255,255,0.4); font-size: 11px; }
            .loading-box { background: #07090F; }
            .load-title { color: #fff; font-size: 20px; font-weight: bold; }
            .load-sub { color: rgba(255,255,255,0.3); font-size: 12px; }
        """)
        Gtk.StyleContext.add_provider_for_screen(
            Gdk.Screen.get_default(), provider, Gtk.STYLE_PROVIDER_PRIORITY_APPLICATION)

        vbox = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=0)
        vbox.pack_start(self._make_titlebar(), False, False, 0)

        self.stack = Gtk.Stack()
        self.stack.set_transition_type(Gtk.StackTransitionType.CROSSFADE)

        # Loading page
        loading = Gtk.Box(orientation=Gtk.Orientation.VERTICAL, spacing=12)
        loading.get_style_context().add_class("loading-box")
        loading.set_valign(Gtk.Align.CENTER)
        loading.set_halign(Gtk.Align.CENTER)
        spinner = Gtk.Spinner()
        spinner.set_size_request(36, 36)
        spinner.start()
        loading.pack_start(spinner, False, False, 0)
        loading.pack_start(Gtk.Label(label="KICC Platform Admin"), False, False, 0)
        self.load_msg = Gtk.Label(label="Initializing...")
        self.load_msg.get_style_context().add_class("load-sub")
        loading.pack_start(self.load_msg, False, False, 0)
        self.stack.add_named(loading, "loading")

        # Web page
        self.web_box = Gtk.Box(orientation=Gtk.Orientation.VERTICAL)
        self.stack.add_named(self.web_box, "app")

        self.stack.set_visible_child_name("loading")
        vbox.pack_start(self.stack, True, True, 0)

        # Status bar
        status = Gtk.Box(orientation=Gtk.Orientation.HORIZONTAL, spacing=12)
        status.get_style_context().add_class("status-bar")
        self.conn = Gtk.Label(label="● Offline Mode")
        self.conn.get_style_context().add_class("status-label")
        status.pack_start(self.conn, False, False, 0)
        status.pack_start(Gtk.Box(), True, True, 0)
        url = Gtk.Label(label=SERVER_URL)
        url.get_style_context().add_class("status-label")
        status.pack_end(url, False, False, 0)
        vbox.pack_start(status, False, False, 0)

        self.add(vbox)

        self.connect('key-press-event', self._on_key)
        self.connect('destroy', lambda w: self._cleanup())
        self.connect('delete-event', lambda w, e: self._cleanup())

        # Start server in background thread
        self.php = PHPServer()
        threading.Thread(target=self._boot, daemon=True).start()

    def _make_titlebar(self):
        box = Gtk.Box(orientation=Gtk.Orientation.HORIZONTAL, spacing=8)
        box.get_style_context().add_class("title-bar")
        lbl = Gtk.Label(label="KICC Platform Admin")
        lbl.get_style_context().add_class("title-label")
        box.pack_start(lbl, False, False, 0)
        box.pack_start(Gtk.Box(), True, True, 0)
        btn = Gtk.Button(label="✕")
        btn.connect('clicked', lambda x: self._cleanup())
        box.pack_start(btn, False, False, 0)
        return box

    def _set_status(self, msg):
        GLib.idle_add(lambda: self.load_msg.set_text(msg) or False)

    def _boot(self):
        self._set_status("Killing old server processes...")
        time.sleep(0.3)
        self._set_status("Starting PHP server...")
        if not self.php.start(self._set_status):
            self._set_status("❌ Failed to start server. Check PHP installation.")
            return
        self._set_status("Server ready! Loading application...")
        time.sleep(0.3)
        GLib.idle_add(self._load_app)

    def _load_app(self):
        settings = WebKit2.Settings()
        settings.set_enable_javascript(True)
        settings.set_allow_file_access_from_file_urls(True)
        settings.set_allow_universal_access_from_file_urls(True)
        self.webview = WebKit2.WebView.new_with_settings(settings)
        self.webview.load_uri(SERVER_URL + '/login')
        self.web_box.pack_start(self.webview, True, True, 0)
        self.webview.show()
        self.stack.set_visible_child_name("app")
        return False

    def _on_key(self, w, e):
        ctrl = e.get_state() & Gdk.ModifierType.CONTROL_MASK
        k = e.keyval
        if ctrl and k == Gdk.KEY_q: self._cleanup(); return True
        if k == Gdk.KEY_F11 or (ctrl and k == Gdk.KEY_f):
            if self.get_window().get_state() & Gdk.WindowState.FULLSCREEN:
                self.unfullscreen()
            else:
                self.fullscreen()
            return True
        if k == Gdk.KEY_F5 or (ctrl and k == Gdk.KEY_r):
            if self.webview: self.webview.reload()
            return True
        return False

    def _cleanup(self):
        self.php.stop()
        Gtk.main_quit()

# ─── Launch ──────────────────────────────────────────────────────────
if __name__ == '__main__':
    app = AdminApp()
    app.show_all()
    Gtk.main()
