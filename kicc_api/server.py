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
                # Calculate only; never drain the orchestrator's pending ledger contributions.
                qualities = {int(k): float(v) for k, v in body.get("qualities", {}).items()}
                r = get().pool.distribute(body.get("contributions", []), qualities)
            elif self.path == "/anonymize":
                a = get().anonymizer
                # A string is one quasi-identifier, never an iterable of letters.
                key = body.get("group_by_key", ["county_id", "sector_id"])
                quasi = (key,) if isinstance(key, str) else tuple(key)
                if not quasi or any(q not in ("county_id", "sector_id") for q in quasi):
                    return self._send(400, {"error": "unsupported quasi-identifier"})
                a.k = max(5, int(body.get("k", 5)))
                r = a.anonymize(body.get("rows", []), quasi)
            elif self.path == "/recommend":
                plat = get()
                r = plat.recommender.recommend(
                    history=body.get("history", []),
                    catalog=body.get("catalog", []),
                    season_tag=body.get("season_tag", ""),
                    limit=body.get("limit", 6),
                )
                r = {"items": r, "count": len(r)}
            elif self.path == "/pricing":
                plat = get()
                try:
                    m = plat.pricing.multiplier(
                        occupancy=float(body.get("occupancy", 0)),
                        days_to_event=int(body.get("days_to_event", 30)),
                        season_tag=str(body.get("season_tag", "low")),
                    )
                    p = plat.pricing.price(
                        base_price=float(body.get("base_price", 0)),
                        occupancy=float(body.get("occupancy", 0)),
                        days_to_event=int(body.get("days_to_event", 30)),
                        season_tag=str(body.get("season_tag", "low")),
                    )
                except TypeError as te:
                    # Fallback: use price() directly if multiplier() signature mismatches
                    p = plat.pricing.price(
                        base_price=float(body.get("base_price", 0)),
                        occupancy=float(body.get("occupancy", 0)),
                        days_to_event=int(body.get("days_to_event", 30)),
                        season_tag=str(body.get("season_tag", "low")),
                    )
                    m = p.get("multiplier", 1.0)
                r = {"multiplier": m, "adjusted_price": p.get("final_price", 0)}
            elif self.path == "/trust":
                import asyncio
                plat = get()
                seller_id = body.get("seller_id", 0)
                try:
                    grade = asyncio.run(plat.trust.grade(seller_id))
                    dispute = asyncio.run(plat.trust.dispute_score(seller_id))
                except Exception:
                    grade = "?"
                    dispute = 0
                r = {"seller_id": seller_id, "trust_grade": grade, "dispute_score": dispute}
            else:
                return self._send(404, {"error": "not found"})
            self._send(200, r)
        except Exception as e:
            self._send(500, {"error": str(e)})

if __name__ == "__main__":
    port = int(os.environ.get("KICC_API_PORT", "8400"))
    HTTPServer(("0.0.0.0", port), Handler).serve_forever()
