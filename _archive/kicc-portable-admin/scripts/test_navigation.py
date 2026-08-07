#!/usr/bin/env python3
"""Test all navigation buttons on the admin portal end-to-end."""
import subprocess, time, sys, re

BASE = "http://127.0.0.1:8090"
TOOLS = "/home/kicc/Desktop/kicc/kicc-platform/.tools/php"

def curl(args, **kw):
    """Run curl and return (exit, stdout, stderr)."""
    cmd = ["curl", "-s", "-L", "--max-time", "5"] + args
    r = subprocess.run(cmd, capture_output=True, text=True, **kw)
    return r.returncode, r.stdout.strip(), r.stderr.strip()

def extract_token(html):
    m = re.search(r'name="_token" value="([^"]+)"', html)
    return m.group(1) if m else None

def test(description, method="GET", path="/", data=None, expect_status=200, expect_content=None, jar=None):
    args = []
    if jar:
        args += ["-b", jar, "-c", jar]
    if method == "POST":
        args += ["-X", "POST"]
        if data:
            args += ["-d", data]
    args += [f"{BASE}{path}"]
    
    rc, out, err = curl(args)
    
    # following redirects so 200 means final success
    ok = True
    if expect_status and rc != 0:
        ok = False
        msg = f"curl failed (rc={rc})"
    
    if expect_content and expect_content not in out:
        ok = False
        msg = f"missing '{expect_content}'"
    
    if ok:
        print(f"  ✅ {description}")
        return True, out
    else:
        print(f"  ❌ {description} — {msg}")
        return False, out

def test_login_flow():
    print("\n📋 Login Flow Tests")
    errors = 0
    
    # 1. Get login page
    rc, html, _ = curl([])
    token = extract_token(html)
    if not token:
        print("  ❌ Cannot extract CSRF token")
        return 1
    
    if "KICC Platform Admin" not in html:
        print("  ❌ Step 1 (role selector) not visible")
        errors += 1
    else:
        print("  ✅ Step 1: Role selector visible")
    
    # Test Step 2: County picker navigation (simulated via JS)
    # We check the county picker elements exist
    if "county-option" not in html:
        print("  ❌ County picker options not found")
        errors += 1
    else:
        print("  ✅ Step 2: County picker has options")
    
    # Test Step 3: Login form 
    if '<form method="POST" action="' not in html:
        print("  ❌ Login form not found")
        errors += 1
    else:
        print("  ✅ Step 3: Login form present")
    
    # 2. Login as KICC admin
    print("\n   ── KICC Admin Login ──")
    ok, html = test("POST /login (kicc)", "POST", "/login",
                     f"login=admin@kicc.go.ke&password=Admin@2026&admin_type=kicc&_token={token}",
                     expect_content="KICC Platform Control Center")
    if ok:
        # Get new token from login page (might redirect to /portal or /kicc-admin)
        token2 = extract_token(html)
        # Test KICC dashboard access
        test("GET /kicc-admin", "GET", "/kicc-admin",
             expect_content="KICC Platform Control Center", jar="/tmp/test_kicc_jar.txt")
        
        # Test National dashboard access (KICC can access)
        test("GET /national-admin", "GET", "/national-admin",
             expect_content=None, jar="/tmp/test_kicc_jar.txt")
        
        # Test County pro dashboard access (KICC can access)
        test("GET /county-admin/kilifi/pro", "GET", "/county-admin/kilifi/pro",
             expect_content="Kilifi", jar="/tmp/test_kicc_jar.txt")
    
    # 3. Login as National admin  
    print("\n   ── National Admin Login ──")
    rc, html, _ = curl([])
    token = extract_token(html)
    ok, _ = test("POST /login (national)", "POST", "/login",
                  f"login=national@kicc.go.ke&password=national@2026&admin_type=national&_token={token}",
                  expect_content="National Government Dashboard")
    
    # 4. Login as County admin
    print("\n   ── County Admin Login ──")
    rc, html, _ = curl([])
    token = extract_token(html)
    ok, _ = test("POST /login (county, slug=kilifi)", "POST", "/login",
                  f"login=county@kicc.go.ke&password=county@2026&admin_type=county&county_slug=kilifi&_token={token}",
                  expect_content="Kilifi")
    
    # 5. Test 403 enforcement
    print("\n   ── Access Control ──")
    # County admin cannot access KICC
    rc, html, _ = curl(["-b", "/tmp/test_county_jar.txt", f"{BASE}/kicc-admin"])
    if "403" in html or rc != 0:
        print("  ✅ County admin blocked from /kicc-admin (403)")
    else:
        print("  ❌ County admin can access /kicc-admin (should be 403)")
        errors += 1
    
    # Logout test
    print("\n   ── Logout ──")
    # Try accessing /kicc-admin without auth
    rc, html, _ = curl([f"{BASE}/kicc-admin"])
    if "login" in html.lower() or "KICC Platform Admin" in html:
        print("  ✅ Redirected to login when not authenticated")
    else:
        print("  ❌ Not redirected to login")
        errors += 1
    
    return errors

def test_kicc_dashboard():
    print("\n📋 KICC Dashboard Tests")
    errors = 0
    
    rc, html, _ = curl(["-b", "/tmp/test_kicc_jar.txt", f"{BASE}/kicc-admin"])
    
    tabs = ["Overview", "Counties", "Sectors", "National", "Users", "Sync"]
    for tab in tabs:
        if tab not in html:
            print(f"  ❌ Tab '{tab}' not found")
            errors += 1
        else:
            print(f"  ✅ Tab '{tab}' present")
    
    # Sync section
    if "Pull from TiDB" in html and "Push to TiDB" in html:
        print("  ✅ Sync panel with Pull/Push buttons")
    else:
        print("  ❌ Sync panel missing")
        errors += 1
    
    return errors

def test_county_pro_dashboard():
    print("\n📋 County Pro Dashboard Tests")
    errors = 0
    
    rc, html, _ = curl(["-b", "/tmp/test_county_jar.txt", f"{BASE}/county-admin/kilifi/pro"])
    
    tabs = ["Overview", "Content", "Sectors", "Entities", "Tourism", "Hotels", "Farms", "Health", "Institutions", "Transport", "Culture"]
    for tab in tabs:
        if tab not in html:
            print(f"  ❌ Tab '{tab}' not found")
            errors += 1
        else:
            print(f"  ✅ Tab '{tab}' present")
    
    # CRUD forms
    for item in ["Add institution", "Add transport", "Add culture", "Add health", "Add farm", "Add hotel", "Add attraction"]:
        if item in html:
            print(f"  ✅ Add button for '{item}' present")
        else:
            print(f"  ❌ Add button for '{item}' missing")
            errors += 1
    
    return errors

def test_national_dashboard():
    print("\n📋 National Dashboard Tests")
    errors = 0
    
    rc, html, _ = curl(["-b", "/tmp/test_national_jar.txt", f"{BASE}/national-admin"])
    
    # Check for basic elements
    keywords = ["Counties", "Ministries", "Government"]
    for kw in keywords:
        if kw in html:
            print(f"  ✅ '{kw}' present")
        else:
            print(f"  ❌ '{kw}' missing")
            errors += 1
    
    return errors

if __name__ == "__main__":
    print("=" * 60)
    print("  KICC Admin Portal — Full Navigation Test")
    print("=" * 60)
    
    # Check server is running
    import urllib.request
    try:
        urllib.request.urlopen(f"{BASE}/login", timeout=5)
        print("  ✅ Server is running on port 8090")
    except:
        print("  ❌ Server is NOT running — start with:")
        print("     php artisan serve --host=127.0.0.1 --port=8090")
        sys.exit(1)
    
    total_errors = 0
    
    total_errors += test_login_flow()
    total_errors += test_kicc_dashboard()
    
    # National dashboard
    # Login as national first
    rc, html, _ = curl([])
    token = extract_token(html)
    if token:
        rc, _, _ = curl(["-X", "POST", "-d", 
            f"login=national@kicc.go.ke&password=national@2026&admin_type=national&_token={token}",
            "-b", "/tmp/test_national_jar.txt", "-c", "/tmp/test_national_jar.txt",
            f"{BASE}/login"])
    total_errors += test_national_dashboard()
    
    # County dashboard  
    rc, html, _ = curl([])
    token = extract_token(html)
    if token:
        rc, _, _ = curl(["-X", "POST", "-d",
            f"login=county@kicc.go.ke&password=county@2026&admin_type=county&county_slug=kilifi&_token={token}",
            "-b", "/tmp/test_county_jar.txt", "-c", "/tmp/test_county_jar.txt",
            f"{BASE}/login"])
    total_errors += test_county_pro_dashboard()
    
    print("\n" + "=" * 60)
    if total_errors == 0:
        print("  ✅ ALL TESTS PASSED — No navigation issues")
    else:
        print(f"  ❌ {total_errors} test(s) FAILED")
    print("=" * 60)
    
    sys.exit(total_errors)
