@extends('layouts.app')
@section('title','Live Chat — KICC Marketplace')
@section('content')
<div class="pt-20 max-w-4xl mx-auto px-5 py-10"><h1 class="text-2xl font-black text-gray-900 mb-6"> Live Chat</h1>
<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden" x-data="{ messages: @json($messages), newMsg: '' }">
<div class="h-80 overflow-y-auto p-4 space-y-3" x-ref="chatbox" x-init="$nextTick(()=>$refs.chatbox.scrollTop=$refs.chatbox.scrollHeight)">
<template x-for="m in messages" :key="m.id">
<div :class="'flex ' + (m.user_id === {{ auth()->id() }} ? 'justify-end' : 'justify-start')">
<div :class="'max-w-xs rounded-2xl px-4 py-2 text-sm ' + (m.user_id === {{ auth()->id() }} ? 'bg-[#046bd2] text-white' : 'bg-gray-100 text-gray-900')"><span x-text="m.body"></span><div class="text-xs opacity-60 mt-1" x-text="new Date(m.created_at).toLocaleTimeString()"></div></div></div>
</template>
</div>
<div class="border-t border-gray-200 p-4 flex gap-2">
<input x-model="newMsg" @keydown.enter="if(newMsg.trim()){fetch('{{ route('live-chat.send', $vendorId) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},body:JSON.stringify({message:newMsg})});messages.push({id:Date.now(),body:newMsg,user_id:{{ auth()->id() }},created_at:new Date().toISOString()});newMsg='';$nextTick(()=>$refs.chatbox.scrollTop=$refs.chatbox.scrollHeight)}" placeholder="Type a message..." class="flex-1 border border-gray-200 rounded-xl px-4 py-2 text-sm">
<button @click="if(newMsg.trim()){fetch('{{ route('live-chat.send', $vendorId) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},body:JSON.stringify({message:newMsg})});messages.push({id:Date.now(),body:newMsg,user_id:{{ auth()->id() }},created_at:new Date().toISOString()});newMsg='';$nextTick(()=>$refs.chatbox.scrollTop=$refs.chatbox.scrollHeight)}" class="bg-[#046bd2] text-white font-bold px-4 py-2 rounded-xl text-sm">Send</button>
</div></div></div>@endsection