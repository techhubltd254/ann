import sys, os, json
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

from http.server import HTTPServer, BaseHTTPRequestHandler
from kicc_algorithms import KiccPlatform
from decimal import Decimal

_kicc = None
def get():
    global _kicc
    if _kicc is None:
        _kicc = KiccPlatform(json.loads(os.environ.get("KICC_CONFIG_OVERRIDES", "{}")))
    return _kicc

class Handler(BaseHTTPRequestHandler):
    def _send(self, code, data):
        body = json.dumps(data, default=str).encode()
        self.send_response(code)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", len(body))
        self.end_headers()
        self.wfile.write(body)

    def do_GET(self):
        if self.path == "/health":
            return self._send(200, {"status": "ok", "algorithms": 20})
        self._send(404, {"error": "not found"})

    def do_POST(self):
        length = int(self.headers.get("Content-Length", 0))
        raw = self.rfile.read(length)
        try:
            body = json.loads(raw) if raw else {}
        except Exception:
            return self._send(400, {"error": "invalid json"})
        try:
            if self.path == "/quality":
                r = get().quality_of(**body)
            elif self.path == "/kyb":
                r = get().onboard(**body)
            elif self.path == "/screen":
                from kicc_algorithms.algorithms.screening import Screener
                r = Screener(get().config).screen(body["name"])
            elif self.path == "/classify":
                r = get().classify_counties([body])[0]
            elif self.path == "/pool/distribute":
                r = get().run_pool_distribution(body.get("contributions", []), body.get("qualities", {}))
            elif self.path == "/anonymize":
                a = get().anonymizer
                a.k = body.get("k", 5)
                r = a.anonymize(body.get("rows", []), body.get("group_by_key", "county_id"))
            else:
                return self._send(404, {"error": "not found"})
            self._send(200, r)
        except Exception as e:
            self._send(500, {"error": str(e)})

if __name__ == "__main__":
    port = int(os.environ.get("KICC_API_PORT", "8400"))
    HTTPServer(("0.0.0.0", port), Handler).serve_forever()
