@extends('layouts.blank')

@section('title', 'Sign In — KICC Admin')

@section('content')
<div class="min-h-screen bg-[#F9FAFB] flex items-center justify-center p-5">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 50 51' fill='%23901C1E'%3E%3Cpath d='M49.6 11.6v11l-9.2 5.3v10.5l-19.2 11-.1.1-.2.1h-.4l-.1-.1-19.2-11V7.6l9.6-5.5h.8l9.6 5.5v20.6l8-4.6V13.5l9.6-5.5h.8l9.6 5.5z'/%3E%3C/svg%3E" alt="KICC" class="h-16 w-auto mx-auto mb-4">
            <h1 class="text-2xl font-black text-gray-900">KICC Admin</h1>
            <p class="text-gray-500 text-sm mt-2">Sign in to manage the platform</p>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl p-7">
            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
                        <input type="email" name="login" value="admin@kicc.go.ke" required
                               class="w-full bg-gray-50 border border-gray-200 focus:border-blue-500/50 rounded-xl px-4 py-2.5 text-sm text-gray-900 outline-none transition-colors">
                        @error('login')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-wider mb-1.5">Password</label>
                        <input type="password" name="password" required
                               class="w-full bg-gray-50 border border-gray-200 focus:border-blue-500/50 rounded-xl px-4 py-2.5 text-sm text-gray-900 outline-none transition-colors">
                    </div>
                </div>
                <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 font-bold tracking-wide transition-all duration-200 mt-6 px-8 text-base h-14 rounded-xl bg-blue-600 text-white hover:bg-blue-700">
                    Sign In
                </button>
            </form>
        </div>

        <div class="mt-6 text-center text-xs text-gray-400">
            &copy; {{ date('Y') }} KICC — Platform Admin Panel
        </div>
    </div>
</div>
@endsection