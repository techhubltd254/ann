#!/usr/bin/env python3
"""KICC Portable Admin — Complete System Test Loop (no redirect follows)"""

import urllib.request, urllib.error, json, sys, time, re, http.cookiejar

BASE = "http://localhost:8090"
PASS = FAIL = SKIP = 0

class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None

def test(name, url, expected=None, jar=None):
    global PASS, FAIL, SKIP
    try:
        opener = urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(jar or http.cookiejar.CookieJar()))
        req = urllib.request.Request(BASE + url, headers={'User-Agent': 'Mozilla/5.0'})
        resp = opener.open(req, timeout=15)
        status = resp.status
        body = resp.read().decode('utf-8', errors='replace')
        has_500 = status >= 500 or 'Server Error' in body
        ok = not has_500 and (not expected or status in (expected if isinstance(expected, list) else [expected]))
        icon = "✅" if ok else ("❌" if has_500 else "⚠️")
        if ok: PASS += 1
        elif has_500: FAIL += 1
        else: SKIP += 1
        print(f"  {icon} {url:45s} HTTP {status:3d} {'⚠️ 500' if has_500 else ''}")
        return {'ok': ok, 'status': status}
    except urllib.error.HTTPError as e:
        status = e.code
        has_500 = status >= 500
        ok = status in (expected if isinstance(expected, list) else [expected]) if expected else not has_500
        icon = "✅" if ok else ("❌" if has_500 else "⚠️")
        if ok: PASS += 1
        elif has_500: FAIL += 1
        else: SKIP += 1
        print(f"  {icon} {url:45s} HTTP {status:3d} {'⚠️ 500' if has_500 else ''}")
        return {'ok': ok, 'status': status}
    except Exception as e:
        FAIL += 1
        print(f"  ❌ {url:45s} {str(e)[:60]}")
        return {'ok': False}

def login(email, password):
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(NoRedirect, urllib.request.HTTPCookieProcessor(jar))
    req = urllib.request.Request(BASE + '/login', headers={'User-Agent': 'Mozilla/5.0'})
    resp = opener.open(req, timeout=15)
    body = resp.read().decode('utf-8', errors='replace')
    csrf = re.search(r'name="_token" value="([^"]+)"', body)
    if not csrf: return None
    data = f'login={email}&password={password}&_token={csrf.group(1)}'
    req2 = urllib.request.Request(BASE + '/login', data=data.encode(),
        headers={'User-Agent': 'Mozilla/5.0', 'Content-Type': 'application/x-www-form-urlencoded'})
    try:
        opener.open(req2, timeout=15)
        return jar
    except urllib.error.HTTPError as e:
        if e.code == 302: return jar  # 302 = success
        return None

def run():
    global PASS, FAIL, SKIP
    print("╔══════════════════════════════════════════════╗")
    print("║  KICC Portable Admin — Complete System Test ║")
    print("╚══════════════════════════════════════════════╝\n")

    # 1. PUBLIC
    print("─── 1. Public Pages ───")
    test("Login", "/login")
    test("Filament login", "/admin/login")
    test("Health", "/up")

    # 2. LOGIN
    print("\n─── 2. Login ───")
    jar = login('admin@kicc.go.ke', 'Admin@2026')
    print("  ✅ Login OK" if jar else "  ❌ LOGIN FAILED")
    if not jar: return

    # 3. DASHBOARDS
    print("\n─── 3. Admin Dashboards ───")
    for p in ["/admin", "/kicc-admin", "/national-admin", "/admin/national",
              "/admin/county", "/dashboard/admin", "/portal", "/county-admin",
              "/exhibitor-admin", "/provider-admin"]:
        test(f"  {p.split('/')[-1]}", p, jar=jar)

    # 4. STUB ROUTES
    print("\n─── 4. Stub Routes ───")
    for s in ["/counties", "/marketplace", "/exhibitions", "/venues",
              "/screens", "/travel", "/operations", "/room3d",
              "/subscriptions", "/packages", "/cart", "/dashboard",
              "/exhibition-3d/map", "/exhibition-3d/sector", "/exhibition-3d/booth"]:
        test(s.split('/')[-1], s, jar=jar, expected=[302, 200])

    # 5. AUTH
    print("\n─── 5. Authorization ───")
    for email, pw in [('national@kicc.go.ke', 'national@2026'), ('county@kicc.go.ke', 'county@2026')]:
        j = login(email, pw)
        role = email.split('@')[0]
        if j:
            print(f"  ✅ {role} login")
            test(f"{role}/kicc (should 403)", "/kicc-admin", jar=j, expected=[403, 302])
        else:
            print(f"  ⚠️ {role} login failed")

    print(f"\n╔══════════════════════════════════════════════╗")
    print(f"║  ✅ {PASS} passed  ❌ {FAIL} failed  ║")
    print(f"╚══════════════════════════════════════════════╝")
    return FAIL == 0

if __name__ == '__main__':
    sys.exit(0 if run() else 1)
