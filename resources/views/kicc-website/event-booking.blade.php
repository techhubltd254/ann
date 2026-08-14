@extends('layouts.app')
@section('title', 'Event Booking — KICC')
@section('content')
<div class="pt-24 max-w-4xl mx-auto px-5 py-10">
    <div class="bg-gradient-to-r from-[#046bd2] to-[#045cb4] rounded-2xl p-6 mb-8">
        <h1 class="text-2xl font-black text-white">Book an Event at KICC</h1>
        <p class="text-white/70 text-sm mt-1">Fill in the form below and our team will get back to you within 24 hours.</p>
    </div>
    <form method="POST" action="{{ route('kicc.event-booking.store') }}" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-6" x-data="{ step: 1, totalSteps: 3, needsCatering: false, needsAv: false }">
        @csrf
        {{-- Step 1: Contact --}}
        <div x-show="step === 1"><h2 class="font-bold text-gray-900 text-lg mb-4">Contact Information</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold text-gray-500 mb-1">First Name *</label><input type="text" name="first_name" required class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Last Name *</label><input type="text" name="last_name" required class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Organization</label><input type="text" name="organization" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Email *</label><input type="email" name="email" required class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Phone *</label><input type="tel" name="phone" required class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
            </div>
        </div>
        {{-- Step 2: Event Details --}}
        <div x-show="step === 2"><h2 class="font-bold text-gray-900 text-lg mb-4">Event Details</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2"><label class="block text-xs font-bold text-gray-500 mb-1">Event Name</label><input type="text" name="event_name" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Event Type *</label><select name="event_type" required class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"><option value="conference">Conference</option><option value="seminar">Seminar</option><option value="workshop">Workshop</option><option value="other">Other</option></select></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Expected Attendees</label><input type="number" name="expected_attendees" min="1" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Event Date</label><input type="date" name="event_date" min="{{ date('Y-m-d') }}" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Duration (days)</label><input type="number" name="duration_days" min="1" value="1" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></div>
                <div class="sm:col-span-2"><label class="block text-xs font-bold text-gray-500 mb-1">Preferred Venue</label>
                    <select name="preferred_venue" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"><option value="">— Select —</option>@foreach($venues as $v)<option value="{{ $v->name }}">{{ $v->name }} ({{ number_format($v->capacity ?? 0) }} cap)</option>@endforeach</select>
                    <p class="text-xs text-gray-400 mt-1">View <a href="{{ route('venues.index') }}" class="text-[#046bd2] hover:underline">pricing guidelines</a>.</p>
                </div>
            </div>
        </div>
        {{-- Step 3: Requirements --}}
        <div x-show="step === 3"><h2 class="font-bold text-gray-900 text-lg mb-4">Additional Requirements</h2>
            <div class="space-y-4">
                <label class="flex items-center gap-3"><input type="checkbox" name="needs_catering" x-model="needsCatering" class="accent-[#046bd2]"><span class="text-sm font-medium">Catering needed?</span></label>
                <div x-show="needsCatering"><textarea name="catering_details" rows="2" class="w-full px-4 py-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm" placeholder="Describe catering requirements…"></textarea></div>
                <label class="flex items-center gap-3"><input type="checkbox" name="needs_av" x-model="needsAv" class="accent-[#046bd2]"><span class="text-sm font-medium">Audio-Visual Equipment needed?</span></label>
                <div x-show="needsAv"><textarea name="av_requirements" rows="2" class="w-full px-4 py-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm" placeholder="Specify AV requirements…"></textarea></div>
                <div><label class="block text-xs font-bold text-gray-500 mb-1">Additional Information</label><textarea name="additional_info" rows="3" class="w-full px-4 py-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm"></textarea></div>
            </div>
        </div>
        {{-- Navigation --}}
        <div class="flex items-center justify-between pt-6 border-t border-gray-100">
            <button type="button" @click="step = Math.max(1, step - 1)" x-show="step > 1" class="h-11 px-6 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50">Previous</button>
            <div></div>
            <button type="button" @click="step = Math.min(totalSteps, step + 1)" x-show="step < totalSteps" class="h-11 px-6 rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4]">Next</button>
            <button type="submit" x-show="step === totalSteps" class="h-11 px-6 rounded-xl bg-[#046bd2] text-white text-sm font-bold hover:bg-[#045cb4]">Submit Enquiry</button>
        </div>
        <div class="flex justify-center gap-2">@for($i = 1; $i <= 3; $i++)<div class="w-8 h-1.5 rounded-full" :class="step >= {{ $i }} ? 'bg-[#046bd2]' : 'bg-gray-200'"></div>@endfor</div>
    </form>
</div>
@endsection