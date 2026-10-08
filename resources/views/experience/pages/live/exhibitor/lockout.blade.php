@extends('layouts.app')
@section('title', 'Access Denied — Studio')
@section('content')
<div class="min-h-screen flex items-center justify-center" style="background: var(--kicc-navy);">
    <div class="text-center px-8 py-12 rounded-2xl" style="background: rgba(255,255,255,0.05); max-width: 480px;">
        <div class="text-5xl mb-4">🔒</div>
        <h1 class="text-2xl font-bold text-white mb-2">Studio Access Denied</h1>
        <p class="mb-2" style="color: #B3261E;">
            @if($reason === 'unauthorized')
            This booth is not authorized. Contact Super Admin to enable streaming.
            @elseif($reason === 'terminated')
            This booth has been terminated. Contact Super Admin for re-authorization.
            @else
            You do not have permission to access this studio.
            @endif
        </p>
        <p class="text-sm mb-6" style="color: #FFFFFF;">Reason code: {{ $reason ?? 'unknown' }}</p>
        <a href="mailto:techhubltd254@gmail.com" class="inline-block px-6 py-2 rounded-lg font-medium" style="background: var(--kicc-gold); color: var(--kicc-navy);">Contact Support</a>
    </div>
</div>
@endsection