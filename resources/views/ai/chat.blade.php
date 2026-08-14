@extends('layouts.app')
@section('title', 'AI Travel Assistant — KICC')
@section('content')
<div class="pt-24 max-w-4xl mx-auto px-5 py-10">
    <h1 class="text-2xl font-black text-gray-900 mb-2">AI Travel Assistant</h1>
    <p class="text-gray-500 text-sm mb-6">Ask about destinations, activities, hotels, or anything Kenya travel related.</p>
    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden" x-data="{ messages: [], input: '', loading: false }">
        <div class="p-4 max-h-96 overflow-y-auto space-y-3" id="chat-box">
            <template x-for="(m, i) in messages" :key="i">
                <div class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[80%] rounded-2xl px-4 py-2.5 text-sm" :class="m.role === 'user' ? 'bg-[#046bd2] text-white' : 'bg-gray-100 text-gray-900'">
                        <p x-text="m.content"></p>
                    </div>
                </div>
            </template>
            <div x-show="loading" class="text-center text-gray-400 text-sm">Thinking...</div>
        </div>
        <div class="border-t border-gray-100 p-4 flex gap-3">
            <input type="text" x-model="input" @keydown.enter="if(!input)return;loading=true;messages.push({role:'user',content:input});fetch('/ai/chat',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({message:input})}).then(r=>r.json()).then(d=>{messages.push({role:'assistant',content:d.reply});loading=false;input='';}).catch(()=>{loading=false;})" placeholder="Ask about Kenya travel..." class="flex-1 h-11 px-4 rounded-xl bg-[#F9FAFB] border border-gray-200 text-sm outline-none">
            <button @click="if(!input)return;loading=true;messages.push({role:'user',content:input});fetch('/ai/chat',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({message:input})}).then(r=>r.json()).then(d=>{messages.push({role:'assistant',content:d.reply});loading=false;input='';}).catch(()=>{loading=false;})" class="h-11 px-6 rounded-xl bg-[#046bd2] text-white font-bold text-sm hover:bg-[#045cb4]">Send</button>
        </div>
    </div>
</div>
@endsection