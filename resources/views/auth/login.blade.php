<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KICC Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; }
        .role-card { transition: all 0.2s; cursor: pointer; }
        .role-card:hover { border-color: #046bd2; background: #f0f6ff; }
        .role-card.selected { border-color: #046bd2; background: #e8f0fe; box-shadow: 0 0 0 2px #046bd2; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-5">
@php $messageBag = $errors ?? new \Illuminate\Support\ViewErrorBag; @endphp
    <div class="w-full max-w-lg" x-data="{ role: 'kicc', countyId: '', step: 'role' }">
        <div class="bg-white rounded-3xl border border-gray-200 p-8 shadow-sm">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-[#046bd2]/10 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-[#046bd2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-black text-gray-900">KICC Admin Portal</h1>
                <p class="text-gray-400 text-sm mt-1">Select your role to continue</p>
            </div>

            {{-- Step 1: Role Selection --}}
            <div x-show="step === 'role'">
                <div class="space-y-3">
                    <div class="role-card border-2 border-gray-200 rounded-2xl p-5" :class="role === 'kicc' && 'selected'" @click="role='kicc'">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-[#046bd2]/10 flex items-center justify-center shrink-0">
                                <span class="text-2xl">🏛️</span>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">KICC Superadmin</div>
                                <div class="text-xs text-gray-400 mt-0.5">Full access over all counties, national government, and platform settings</div>
                            </div>
                            <div class="ml-auto" x-show="role === 'kicc'">
                                <svg class="w-6 h-6 text-[#046bd2]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="role-card border-2 border-gray-200 rounded-2xl p-5" :class="role === 'national' && 'selected'" @click="role='national'">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-[#046bd2]/10 flex items-center justify-center shrink-0">
                                <span class="text-2xl">🏢</span>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">National Government</div>
                                <div class="text-xs text-gray-400 mt-0.5">Manage ministries, agencies, and national-level content</div>
                            </div>
                            <div class="ml-auto" x-show="role === 'national'">
                                <svg class="w-6 h-6 text-[#046bd2]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            </div>
                        </div>
                    </div>

                    <div class="role-card border-2 border-gray-200 rounded-2xl p-5" :class="role === 'county' && 'selected'" @click="role='county'; step='county'">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-[#046bd2]/10 flex items-center justify-center shrink-0">
                                <span class="text-2xl">📍</span>
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">County Admin</div>
                                <div class="text-xs text-gray-400 mt-0.5">Manage your county's content, products, and services</div>
                            </div>
                            <div class="ml-auto" x-show="role === 'county'">
                                <svg class="w-6 h-6 text-[#046bd2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                @if($messageBag->any())
                <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mt-4 text-sm">{{ $messageBag->first() }}</div>
                @endif

                <div x-show="role !== 'county'" class="mt-6">
                    <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true">
                        @csrf
                        <input type="hidden" name="admin_type" :value="role">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email</label>
                                <input type="email" name="login" required autofocus
                                       class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#046bd2]/60 rounded-xl px-4 py-2.5 text-sm outline-none transition-colors">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Password</label>
                                <input type="password" name="password" required
                                       class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#046bd2]/60 rounded-xl px-4 py-2.5 text-sm outline-none transition-colors">
                            </div>
                        </div>
                        <button type="submit" :disabled="loading"
                                class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#046bd2] text-white hover:bg-[#045cb4] disabled:opacity-60">
                            <span x-text="loading ? 'Signing in…' : 'Sign In'"></span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Step 2: County Selection --}}
            <div x-show="step === 'county'">
                <button @click="step='role'" class="text-sm text-[#046bd2] font-bold mb-4 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Back to roles
                </button>
                <form method="POST" action="{{ route('login') }}" x-data="{ loading: false }" @submit="loading = true">
                    @csrf
                    <input type="hidden" name="admin_type" value="county">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Your County</label>
                            <select name="county_id" x-model="countyId" required
                                    class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#046bd2]/60 rounded-xl px-4 py-2.5 text-sm outline-none transition-colors">
                                <option value="">Select your county…</option>
                                @foreach($counties as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} County</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Email</label>
                            <input type="email" name="login" required
                                   class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#046bd2]/60 rounded-xl px-4 py-2.5 text-sm outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1.5">Password</label>
                            <input type="password" name="password" required
                                   class="w-full bg-[#F9FAFB] border border-gray-200 focus:border-[#046bd2]/60 rounded-xl px-4 py-2.5 text-sm outline-none transition-colors">
                        </div>
                    </div>
                    <button type="submit" :disabled="loading"
                            class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-[#046bd2] text-white hover:bg-[#045cb4] disabled:opacity-60">
                        <span x-text="loading ? 'Signing in…' : 'Sign In'"></span>
                    </button>
                </form>
            </div>
        </div>
        <p class="text-center text-xs text-gray-400 mt-6">KICC Admin Portal v1.0 — Authorized personnel only</p>
    </div>
</body>
</html>