@extends('layouts.app')

@section('title', 'Register as Agent — KICC Tourism Marketplace')
@section('description', 'Register your tourism business on the KICC platform.')

@section('content')
<div class="pt-28 pb-16">
    <div class="max-w-3xl mx-auto px-5">
        <div class="bg-gradient-to-r from-[#046bd2] to-[#045cb4] rounded-2xl p-6 mb-8">
            <h1 class="text-2xl font-black text-white">Register Your Tourism Business</h1>
            <p class="text-white/70 text-sm mt-1">Become a verified agent on Kenya's national tourism marketplace. Reach tourists from across the world.</p>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ $errors->first() }}</div>
        @endif
        @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-600 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('agent.store') }}" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-2xl p-6 space-y-6">
            @csrf
            <div>
                <h2 class="font-bold text-gray-900 text-lg mb-4">Business Information</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Business Name *</label>
                        <input type="text" name="business_name" required value="{{ old('business_name') }}" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Registration Number</label>
                        <input type="text" name="registration_number" value="{{ old('registration_number') }}" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">License Number</label>
                        <input type="text" name="license_number" value="{{ old('license_number') }}" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Tax ID / KRA PIN</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">County</label>
                        <select name="county_id" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                            <option value="">— Select county —</option>
                            @foreach($counties as $c)<option value="{{ $c->id }}" {{ old('county_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Agent Type *</label>
                        <select name="agent_type" required class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
                            <option value="local" {{ old('agent_type') === 'local' ? 'selected' : '' }}>Local (Kenyan)</option>
                            <option value="international" {{ old('agent_type') === 'international' ? 'selected' : '' }}>International</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="font-bold text-gray-900 text-lg mb-4">Contact Information</h2>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Contact Email *</label>
                        <input type="email" name="contact_email" required value="{{ old('contact_email') }}" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Contact Phone *</label>
                        <input type="tel" name="contact_phone" required value="{{ old('contact_phone') }}" placeholder="+254 7XX XXX XXX" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Website</label>
                        <input type="url" name="website" value="{{ old('website') }}" placeholder="https://" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Address</label>
                        <input type="text" name="address" value="{{ old('address') }}" class="w-full h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40">
                    </div>
                </div>
            </div>

            <div>
                <h2 class="font-bold text-gray-900 text-lg mb-4">Services</h2>
                <p class="text-xs text-gray-400 mb-3">Select the types of tourism services you offer.</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach(['tour_operator'=>'Tour Operator / Packages','accommodation'=>'Accommodation / Hotels','transport'=>'Transport / Car Rental','guide'=>'Tour Guide','restaurant'=>'Restaurant / Dining','events'=>'Events / MICE'] as $val => $label)
                    <label class="flex items-center gap-3 border border-gray-200 rounded-xl p-3 cursor-pointer hover:border-[#046bd2]/40 transition-all">
                        <input type="checkbox" name="service_types[]" value="{{ $val }}" {{ in_array($val, old('service_types', [])) ? 'checked' : '' }} class="accent-[#046bd2]">
                        <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div>
                <h2 class="font-bold text-gray-900 text-lg mb-4">Supporting Documents</h2>
                <p class="text-xs text-gray-400 mb-3">Upload business license, tax registration, KYC documents, certifications (PDF or images, max 10MB each).</p>
                <input type="file" name="documents[]" multiple accept=".pdf,.jpg,.jpeg,.png" class="w-full">
            </div>

            <div>
                <h2 class="font-bold text-gray-900 text-lg mb-4">Business Description</h2>
                <textarea name="description" rows="4" class="w-full px-4 py-3 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none focus:ring-2 focus:ring-[#046bd2]/40" placeholder="Tell us about your business, experience, and what makes you unique…">{{ old('description') }}</textarea>
            </div>

            <button type="submit" class="w-full h-12 rounded-xl bg-[#046bd2] text-white font-black text-sm hover:bg-[#045cb4] transition-all">Submit Application</button>
            <p class="text-gray-400 text-[11px] text-center">A KICC trade officer will review your application within 48 hours.</p>
        </form>
    </div>
</div>
@endsection