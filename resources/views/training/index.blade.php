@extends('layouts.app')
@section('title', 'Training & Documentation')
@section('content')
<div class="pt-24 max-w-7xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-6">Training & Documentation</h1>
    <div class="grid sm:grid-cols-3 gap-6">
        <a href="{{ route('training.admin') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover">
            <div class="text-3xl mb-3">📘</div>
            <h3 class="font-bold text-gray-900">Admin Manual</h3>
            <p class="text-gray-500 text-sm mt-1">Platform administration, user management, config, and maintenance.</p>
        </a>
        <a href="{{ route('training.api') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover">
            <div class="text-3xl mb-3">📡</div>
            <h3 class="font-bold text-gray-900">API Documentation</h3>
            <p class="text-gray-500 text-sm mt-1">RESTful API reference for developers integrating with the platform.</p>
        </a>
        <a href="{{ route('lms.index') }}" class="bg-white border border-gray-200 rounded-2xl p-6 hover:border-[#046bd2]/40 transition-all card-hover">
            <div class="text-3xl mb-3">🎓</div>
            <h3 class="font-bold text-gray-900">E-Learning Platform</h3>
            <p class="text-gray-500 text-sm mt-1">Courses, certifications, and training materials for all users.</p>
        </a>
    </div>
</div>
@endsection