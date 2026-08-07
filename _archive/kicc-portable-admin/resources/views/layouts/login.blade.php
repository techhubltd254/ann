<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KICC Admin</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:system-ui,-apple-system,sans-serif}
        body{background:#07090F;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .card{background:#0D1220;border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:32px;width:100%;max-width:420px}
        h1{font-size:22px;font-weight:900;text-align:center;margin-bottom:4px}
        .sub{color:rgba(255,255,255,0.35);font-size:13px;text-align:center;margin-bottom:28px}
        .role-btn{width:100%;background:transparent;border:2px solid rgba(255,255,255,0.1);border-radius:12px;padding:16px;margin-bottom:10px;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:14px;text-align:left}
        .role-btn:hover,.role-btn.sel{border-color:#FFCD05;background:rgba(255,205,5,0.1)}
        .role-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
        .role-name{font-weight:700;font-size:14px;color:#fff}
        .role-desc{font-size:11px;color:rgba(255,255,255,0.4);margin-top:1px}
        .form-group{margin-bottom:16px}
        .sel-group{margin-bottom:16px}
        label{display:block;font-size:10px;font-weight:700;color:rgba(255,255,255,0.3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px}
        input,select{width:100%;background:#141B2E;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px 14px;color:#fff;font-size:14px;outline:none}
        input:focus,select:focus{border-color:rgba(255,205,5,0.5)}
        select option{background:#141B2E;color:#fff}
        .btn{width:100%;background:#901C1E;color:#fff;border:none;border-radius:12px;padding:12px;font-size:14px;font-weight:700;cursor:pointer;transition:background .2s}
        .btn:hover{background:#7b1618}
        .btn-back{background:transparent;color:rgba(255,255,255,0.4);padding:8px 0;font-size:12px;cursor:pointer;border:none;transition:color .2s}
        .btn-back:hover{color:#fff}
        .error{color:#901C1E;font-size:12px;margin-top:-8px;margin-bottom:12px}
        .footer{text-align:center;font-size:11px;color:rgba(255,255,255,0.2);margin-top:24px}
        .hidden{display:none}
        .sel-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;margin:0 auto 12px}
    </style>
</head>
<body>
<div class="card" id="app">
    {{-- STEP 1: Choose Role --}}
    <div id="step-select">
        <div style="width:56px;height:56px;background:#901C1E;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:24px;font-weight:900">K</div>
        <h1>KICC Platform Admin</h1>
        <p class="sub">Choose your access level</p>

        <button onclick="chooseRole('kicc')" class="role-btn">
            <div style="background:rgba(144,28,30,0.15);" class="role-icon">🔐</div>
            <div><div class="role-name">KICC Administrator</div><div class="role-desc">Full platform control — all sections</div></div>
        </button>
        <button onclick="chooseRole('national')" class="role-btn">
            <div style="background:rgba(11,30,87,0.2);" class="role-icon">🏛️</div>
            <div><div class="role-name">National Government</div><div class="role-desc">Ministries, agencies &amp; county oversight</div></div>
        </button>
        <button onclick="chooseRole('county')" class="role-btn">
            <div style="background:rgba(255,205,5,0.15);" class="role-icon">📍</div>
            <div><div class="role-name">County Administration</div><div class="role-desc">Your county's content &amp; data</div></div>
        </button>
    </div>

    {{-- STEP 2: Pick County (searchable) --}}
    <div id="step-pick-county" class="hidden">
        <button onclick="goTo('step-select')" class="btn-back">← Back</button>
        <div style="text-align:center;margin:12px 0">
            <div class="sel-icon" style="background:rgba(255,205,5,0.15)">📍</div>
            <div style="font-size:13px;font-weight:700;color:#fff">County Administration</div>
            <p class="sub">Search and select your county</p>
        </div>
        <div class="sel-group" style="position:relative">
            <label>Search County</label>
            <input id="county-search" type="text" placeholder="Type county name…" autocomplete="off"
                   style="width:100%;background:#141B2E;border:1px solid rgba(255,255,255,0.1);border-radius:12px;padding:10px 14px;color:#fff;font-size:14px;outline:none"
                   onfocus="this.style.borderColor='rgba(255,205,5,0.5)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"
                   oninput="filterCounties(this.value)">
            <input type="hidden" id="county-select" value="">
            <div id="county-list" style="margin-top:6px;max-height:220px;overflow-y:auto;border-radius:10px;background:#141B2E;border:1px solid rgba(255,255,255,0.08)">
                @foreach(\App\Models\County::orderBy('name')->get() as $c)
                <div class="county-option" data-slug="{{ $c->slug }}" data-name="{{ strtolower($c->name) }}"
                     onclick="selectCounty('{{ $c->slug }}','{{ $c->name }} County')"
                     style="padding:10px 14px;cursor:pointer;border-bottom:1px solid rgba(255,255,255,0.05);color:#fff;font-size:13px;transition:background 0.15s"
                     onmouseover="this.style.background='rgba(255,205,5,0.1)'" onmouseout="this.style.background='transparent'">
                    <span style="color:rgba(255,255,255,0.5)">📍</span> {{ $c->name }} County
                    <span style="color:rgba(255,255,255,0.25);font-size:11px;float:right">{{ $c->region ?? '' }}</span>
                </div>
                @endforeach
            </div>
            <div id="county-no-results" class="hidden" style="padding:20px;text-align:center;color:rgba(255,255,255,0.3);font-size:13px">No counties match your search</div>
        </div>
        <div id="selected-county-display" class="hidden" style="background:rgba(255,205,5,0.1);border:1px solid rgba(255,205,5,0.3);border-radius:10px;padding:12px;margin-bottom:16px;text-align:center">
            <span style="color:rgba(255,255,255,0.5)">📍</span>
            <span id="selected-county-name" style="color:#fff;font-weight:600;font-size:14px"></span>
        </div>
        <button onclick="proceedToLogin()" class="btn" id="county-next-btn" style="opacity:0.5;pointer-events:none">Next →</button>
        <script>
        var countyOptions = document.querySelectorAll('.county-option');
        function filterCounties(query) {
            var q = query.toLowerCase().trim();
            var visible = 0;
            countyOptions.forEach(function(opt) {
                if (!q || opt.dataset.name.includes(q)) {
                    opt.style.display = 'block';
                    visible++;
                } else {
                    opt.style.display = 'none';
                }
            });
            document.getElementById('county-no-results').classList.toggle('hidden', visible > 0);
        }
        function selectCounty(slug, name) {
            document.getElementById('county-select').value = slug;
            document.getElementById('county-search').value = name;
            document.getElementById('selected-county-name').textContent = name;
            document.getElementById('selected-county-display').classList.remove('hidden');
            var btn = document.getElementById('county-next-btn');
            btn.style.opacity = '1';
            btn.style.pointerEvents = 'auto';
            document.getElementById('county-list').style.display = 'none';
        }
        // Show all on focus
        document.getElementById('county-search').addEventListener('focus', function() {
            document.getElementById('county-list').style.display = 'block';
            if (this.value) filterCounties(this.value);
        });
        // Hide on blur (delayed for click)
        document.getElementById('county-search').addEventListener('blur', function() {
            setTimeout(function() { document.getElementById('county-list').style.display = 'none'; }, 200);
        });
        // Clear selection if search is cleared
        document.getElementById('county-search').addEventListener('input', function() {
            if (!this.value) {
                document.getElementById('county-select').value = '';
                document.getElementById('selected-county-display').classList.add('hidden');
                var btn = document.getElementById('county-next-btn');
                btn.style.opacity = '0.5';
                btn.style.pointerEvents = 'none';
            }
        });
        </script>
    </div>

    {{-- STEP 3: Login --}}
    <div id="step-login" class="hidden">
        <button onclick="goBackFromLogin()" class="btn-back">← Back</button>
        <div style="text-align:center;margin:12px 0">
            <div style="font-size:13px;font-weight:700;color:#fff" id="role-title">KICC Administrator</div>
            <div style="font-size:11px;color:rgba(255,255,255,0.3)" id="county-label"></div>
            <p class="sub" style="margin-bottom:16px">Enter your credentials</p>
        </div>
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <input type="hidden" name="admin_type" id="admin-type-input" value="kicc">
            <input type="hidden" name="county_slug" id="county-slug-input" value="">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="login" id="login-email" placeholder="Enter your email" required>
                @error('login')<div class="error">{{ $message }}</div>@enderror
            </div>
            <div class="form-group" style="position:relative">
                <label>Password</label>
                <div style="position:relative">
                    <input type="password" name="password" id="login-password" required style="padding-right:40px">
                    <button type="button" id="toggle-password" onclick="togglePassword()" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,0.3);cursor:pointer;padding:4px;font-size:16px;line-height:1" aria-label="Toggle password visibility">👁️</button>
                </div>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
    </div>
</div>

<script>
var currentRole = 'kicc';
var countyName = '';

function chooseRole(role) {
    currentRole = role;
    if (role === 'county') {
        goTo('step-pick-county');
    } else {
        showLogin(role, '');
    }
}

function proceedToLogin() {
    var sel = document.getElementById('county-select');
    if (sel.value) {
        countyName = document.getElementById('selected-county-name').textContent;
        showLogin('county', sel.value, countyName);
    }
}

function showLogin(role, countySlug, cName) {
    var titles = { kicc: 'KICC Administrator', national: 'National Government', county: 'County Administration' };
    document.getElementById('role-title').textContent = titles[role];
    document.getElementById('admin-type-input').value = role;
    document.getElementById('county-slug-input').value = countySlug || '';
    document.getElementById('county-label').textContent = cName || '';
    goTo('step-login');

    // Pre-fill email based on role
    var emailInput = document.querySelector('input[name="login"]');
    if (role === 'kicc') emailInput.value = 'admin@kicc.go.ke';
    else if (role === 'national') emailInput.value = 'national@kicc.go.ke';
    else if (role === 'county') emailInput.value = 'county@kicc.go.ke';
}

function goTo(stepId) {
    ['step-select', 'step-pick-county', 'step-login'].forEach(function(id) {
        document.getElementById(id).classList.add('hidden');
    });
    document.getElementById(stepId).classList.remove('hidden');
}

function goBackFromLogin() {
    if (currentRole === 'county') {
        goTo('step-pick-county');
    } else {
        goTo('step-select');
    }
}

// Handle back from county picker
function backToSelect() { goTo('step-select'); }

function togglePassword() {
    var pwd = document.getElementById('login-password');
    var btn = document.getElementById('toggle-password');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        btn.textContent = '🙈';
    } else {
        pwd.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>
</body>
</html>