@extends('layouts.app')
@section('title', 'FAQ — KICC')
@section('content')
<div class="pt-20 max-w-3xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">Frequently Asked Questions</h1>
    @if($faqs->isEmpty())
    <p class="text-gray-400">No FAQs yet.</p>
    @else
    <div class="space-y-3" id="kicc-faq-list">@foreach($faqs as $i => $f)
        <div class="bg-white border border-gray-200 rounded-2xl">
            <button onclick="var p=this.parentElement; var a=p.querySelector('.faq-answer'); var ic=p.querySelector('.faq-icon'); var vis=a.style.display; a.style.display=vis==='block'?'none':'block'; ic.style.transform=vis==='block'?'':'rotate(180deg)';"
                    class="w-full flex items-center justify-between p-5 text-left">
                <span class="font-bold text-gray-900 text-sm">{{ $f->question }}</span>
                <svg class="faq-icon w-4 h-4 shrink-0 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div class="faq-answer px-5 pb-5 text-sm text-gray-600 leading-relaxed" style="display:none">{{ $f->answer }}</div>
        </div>
    @endforeach</div>
    @endif
</div>
@endsection