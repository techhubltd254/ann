@props([
    'liveStreamId' => 0,
    'height' => '400px',
])

<div class="bg-white border border-gray-200 rounded-2xl overflow-hidden flex flex-col"
     style="height: {{ $height }}"
     x-data="liveChat({{ $liveStreamId }})"
     x-init="init()">
    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
            <span class="text-xs font-bold text-gray-900">Live Chat</span>
        </div>
        <span class="text-[10px] text-gray-400" x-text="messages.length + ' messages'"></span>
    </div>

    <div class="flex-1 overflow-y-auto p-3 space-y-2" x-ref="chatBox">
        <template x-for="msg in messages" :key="msg.id">
            <div class="flex items-start gap-2">
                <div class="w-6 h-6 rounded-full bg-[#0B1E57]/10 flex items-center justify-center text-[10px] font-bold text-[#0B1E57] shrink-0"
                     x-text="(msg.user_name || 'G')[0].toUpperCase()"></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-gray-900" x-text="msg.user_name || 'Guest'"></span>
                        <span class="text-[9px] text-gray-400" x-text="timeAgo(msg.created_at)"></span>
                    </div>
                    <p class="text-xs text-gray-600 break-words" x-text="msg.message"></p>
                </div>
            </div>
        </template>
        <div x-show="messages.length === 0" class="text-center py-8 text-gray-400 text-xs">No messages yet. Be the first!</div>
    </div>

    <div class="shrink-0 border-t border-gray-100 p-3">
        <form @submit.prevent="sendMessage()" class="flex gap-2">
            <input type="text" x-model="newMessage" placeholder="Type a message..."
                   class="flex-1 h-9 rounded-xl bg-gray-50 border border-gray-200 text-xs px-3 outline-none focus:ring-1 focus:ring-[#FFCD05]"
                   maxlength="500">
            <button type="submit" :disabled="!newMessage.trim()"
                    class="h-9 px-4 rounded-xl bg-[#0B1E57] text-white text-xs font-bold hover:bg-[#16275f] transition-all disabled:opacity-50">
                Send
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
function liveChat(streamId) {
    return {
        messages: [],
        newMessage: '',
        pollInterval: null,

        init() {
            this.fetchMessages();
            this.pollInterval = setInterval(() => this.fetchMessages(), 3000);
        },

        fetchMessages() {
            fetch('/streams/' + streamId + '/chat')
                .then(r => r.json())
                .then(data => {
                    if (data && data.length > 0) {
                        this.messages = data;
                        this.$nextTick(() => {
                            const box = this.$refs.chatBox;
                            if (box) box.scrollTop = box.scrollHeight;
                        });
                    }
                })
                .catch(() => {});
        },

        sendMessage() {
            if (!this.newMessage.trim()) return;
            fetch('/streams/' + streamId + '/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                },
                body: JSON.stringify({ message: this.newMessage })
            })
            .then(r => r.json())
            .then(() => {
                this.newMessage = '';
                this.fetchMessages();
            })
            .catch(() => {});
        },

        timeAgo(date) {
            if (!date) return '';
            var d = new Date(date);
            var s = Math.floor((new Date() - d) / 1000);
            if (s < 60) return 'now';
            if (s < 3600) return Math.floor(s / 60) + 'm';
            if (s < 86400) return Math.floor(s / 3600) + 'h';
            return Math.floor(s / 86400) + 'd';
        },

        destroy() {
            if (this.pollInterval) clearInterval(this.pollInterval);
        }
    };
}
</script>
@endpush