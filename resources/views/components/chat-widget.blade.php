{{-- Cuy-AI Chat Widget --}}
<div
    x-data="cuyAiChat()"
    x-init="init()"
    x-cloak
    class="font-sans"
>
    {{-- Floating Button --}}
    <button
        @click="toggle()"
        x-show="!open"
        x-transition.opacity
        class="fixed bottom-4 right-4 sm:bottom-5 sm:right-5 z-[9998] group bg-gradient-to-br from-[#F5B914] to-[#d4a012] hover:from-[#1B3A6E] hover:to-[#0D1F3C] w-14 h-14 sm:w-16 sm:h-16 rounded-full shadow-2xl shadow-[#1B3A6E]/40 flex items-center justify-center transition-all duration-300 hover:scale-110 border-2 border-white overflow-visible"
        aria-label="Buka chat Cuy-AI"
    >
        {{-- Owl mascot --}}
        <img
            src="{{ asset('images/nativecuy_icon_128x128.png') }}"
            alt="Cuy-AI"
            class="w-12 h-12 sm:w-14 sm:h-14 object-contain drop-shadow-md pointer-events-none"
        />
        {{-- Online indicator --}}
        <span class="absolute top-1 right-1 flex h-3 w-3 pointer-events-none">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500 ring-2 ring-white"></span>
        </span>
        {{-- Tooltip (desktop only) --}}
        <span class="hidden sm:block absolute right-full mr-3 px-3 py-1.5 bg-[#0D1F3C] text-white text-xs font-semibold rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap pointer-events-none shadow-lg">
            Tanya Cuy-AI
        </span>
    </button>

    {{-- Backdrop (mobile only) --}}
    <div
        x-show="open"
        x-transition.opacity
        @click="toggle()"
        class="fixed inset-0 bg-black/40 z-[9998] sm:hidden"
    ></div>

    {{-- Chat Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed z-[9999] bg-white shadow-2xl shadow-black/30 flex flex-col overflow-hidden border border-gray-200
               inset-x-3 bottom-3 top-16 rounded-2xl
               sm:inset-auto sm:bottom-5 sm:right-5 sm:top-auto sm:left-auto sm:w-[24rem] sm:h-[34rem] sm:max-h-[85vh] sm:rounded-2xl"
    >
        {{-- Header --}}
        <div class="bg-gradient-to-r from-[#1B3A6E] to-[#0D1F3C] px-3 sm:px-4 py-2.5 sm:py-3 flex items-center gap-2.5 sm:gap-3 flex-shrink-0">
            <div class="relative flex-shrink-0">
                <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-full bg-[#F5B914] flex items-center justify-center overflow-hidden border-2 border-white/30">
                    <img src="{{ asset('images/nativecuy_icon_64x64.png') }}" alt="Cuy-AI" class="w-8 h-8 sm:w-9 sm:h-9 object-contain">
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 sm:w-3 sm:h-3 bg-emerald-500 rounded-full border-2 border-[#1B3A6E]"></span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-white font-bold text-sm leading-tight truncate">Cuy-AI</div>
                <div class="text-emerald-300 text-[10px] sm:text-xs flex items-center gap-1 truncate">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse flex-shrink-0"></span>
                    <span class="truncate">Online · Customer Service</span>
                </div>
            </div>
            <button
                @click="resetChat()"
                title="Mulai chat baru"
                class="text-white/70 hover:text-white p-1.5 sm:p-2 rounded-lg hover:bg-white/10 transition flex-shrink-0"
            >
                <i class="fas fa-rotate-right text-sm"></i>
            </button>
            <button
                @click="toggle()"
                title="Tutup"
                class="text-white/70 hover:text-white p-1.5 sm:p-2 rounded-lg hover:bg-white/10 transition flex-shrink-0"
            >
                <i class="fas fa-xmark text-base"></i>
            </button>
        </div>

        {{-- Messages --}}
        <div
            x-ref="scrollArea"
            class="flex-1 overflow-y-auto bg-gradient-to-b from-gray-50 to-white px-3 py-4 space-y-3"
        >
            {{-- Welcome bubble (only when empty) --}}
            <template x-if="messages.length === 0">
                <div class="flex items-start gap-2">
                    <div class="w-7 h-7 rounded-full bg-[#F5B914]/20 border border-[#F5B914]/40 flex items-center justify-center overflow-hidden flex-shrink-0">
                        <img src="{{ asset('images/nativecuy_icon_64x64.png') }}" alt="Cuy-AI" class="w-6 h-6 object-contain">
                    </div>
                    <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-3.5 py-2.5 shadow-sm max-w-[80%]">
                        <p class="text-sm text-gray-800 leading-relaxed">
                            Halo! Gw <b>Cuy-AI</b> 🦉<br>
                            Asisten NativeCuy. Mau tanya soal layanan joki, harga, atau cara order?
                        </p>
                    </div>
                </div>
            </template>

            <template x-for="(msg, idx) in messages" :key="idx">
                <div :class="msg.role === 'user' ? 'flex items-start gap-2 justify-end' : 'flex items-start gap-2'">
                    <template x-if="msg.role === 'assistant'">
                        <div class="w-7 h-7 rounded-full bg-[#F5B914]/20 border border-[#F5B914]/40 flex items-center justify-center overflow-hidden flex-shrink-0">
                            <img src="{{ asset('images/nativecuy_icon_64x64.png') }}" alt="Cuy-AI" class="w-6 h-6 object-contain">
                        </div>
                    </template>
                    <div
                        :class="msg.role === 'user'
                            ? 'bg-[#1B3A6E] text-white rounded-2xl rounded-tr-sm'
                            : 'bg-white border border-gray-200 text-gray-800 rounded-2xl rounded-tl-sm shadow-sm'"
                        class="px-3.5 py-2.5 max-w-[80%] text-sm leading-relaxed whitespace-pre-wrap break-words"
                        x-html="renderMessage(msg.content)"
                    ></div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="loading" class="flex items-start gap-2">
                <div class="w-7 h-7 rounded-full bg-[#F5B914]/20 border border-[#F5B914]/40 flex items-center justify-center overflow-hidden flex-shrink-0">
                    <img src="{{ asset('images/nativecuy_icon_64x64.png') }}" alt="Cuy-AI" class="w-6 h-6 object-contain">
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm">
                    <div class="flex items-center gap-1">
                        <span class="w-2 h-2 bg-[#1B3A6E]/40 rounded-full animate-bounce"></span>
                        <span class="w-2 h-2 bg-[#1B3A6E]/40 rounded-full animate-bounce" style="animation-delay: 0.15s"></span>
                        <span class="w-2 h-2 bg-[#1B3A6E]/40 rounded-full animate-bounce" style="animation-delay: 0.3s"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick suggestions --}}
        <template x-if="messages.length === 0">
            <div class="px-2.5 sm:px-3 pb-2 flex flex-wrap gap-1.5 flex-shrink-0">
                <template x-for="q in suggestions" :key="q">
                    <button
                        @click="sendQuick(q)"
                        :disabled="loading"
                        class="text-[11px] sm:text-xs px-2.5 sm:px-3 py-1.5 bg-[#F5B914]/10 hover:bg-[#F5B914]/20 text-[#1B3A6E] font-medium border border-[#F5B914]/30 rounded-full transition disabled:opacity-50 whitespace-nowrap"
                        x-text="q"
                    ></button>
                </template>
            </div>
        </template>

        {{-- Input --}}
        <form
            @submit.prevent="send()"
            class="border-t border-gray-200 p-2.5 sm:p-3 bg-white flex items-end gap-2 flex-shrink-0"
        >
            <textarea
                x-ref="input"
                x-model="draft"
                @keydown.enter.prevent="if (!$event.shiftKey) send()"
                rows="1"
                maxlength="1000"
                :disabled="loading"
                placeholder="Tulis pertanyaan lo..."
                class="flex-1 min-w-0 resize-none border border-gray-300 focus:border-[#F5B914] focus:ring-2 focus:ring-[#F5B914]/30 rounded-xl px-3 sm:px-3.5 py-2 sm:py-2.5 text-sm text-gray-800 placeholder:text-gray-400 outline-none transition max-h-24"
            ></textarea>
            <button
                type="submit"
                :disabled="loading || !draft.trim()"
                class="bg-[#1B3A6E] hover:bg-[#0D1F3C] disabled:bg-gray-300 disabled:cursor-not-allowed text-white w-10 h-10 rounded-xl flex items-center justify-center transition shadow-md flex-shrink-0"
                aria-label="Kirim"
            >
                <i class="fas fa-paper-plane text-sm" x-show="!loading"></i>
                <i class="fas fa-spinner fa-spin text-sm" x-show="loading"></i>
            </button>
        </form>

        {{-- Footer --}}
        <div class="bg-gray-50 border-t border-gray-200 px-3 py-1.5 text-center flex-shrink-0">
            <p class="text-[10px] text-gray-400 leading-tight">
                Cuy-AI bisa salah. Untuk hal penting, chat admin via WA.
            </p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function cuyAiChat() {
    return {
        open: false,
        loading: false,
        draft: '',
        messages: [],
        suggestions: [
            'Berapa harga joki tugas?',
            'Gimana cara order?',
            'Cek status order',
            'Layanan apa aja?',
        ],

        async init() {
            try {
                const r = await fetch('{{ route('chat.history') }}', { headers: { 'Accept': 'application/json' } });
                if (r.ok) {
                    const data = await r.json();
                    this.messages = data.history || [];
                }
            } catch (e) {}
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.$refs.input?.focus();
                });
            }
        },

        async sendQuick(text) {
            this.draft = text;
            await this.send();
        },

        async send() {
            const text = this.draft.trim();
            if (!text || this.loading) return;

            this.messages.push({ role: 'user', content: text });
            this.draft = '';
            this.loading = true;
            this.$nextTick(() => this.scrollToBottom());

            try {
                const r = await fetch('{{ route('chat.send') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ message: text }),
                });

                if (!r.ok) {
                    throw new Error('HTTP ' + r.status);
                }

                const data = await r.json();
                this.messages.push({ role: 'assistant', content: data.reply || 'Maaf, ada gangguan.' });
            } catch (e) {
                this.messages.push({
                    role: 'assistant',
                    content: 'Waduh, ada gangguan koneksi. Coba lagi sebentar ya, atau chat admin via WA langsung 🙏',
                });
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.$refs.input?.focus();
                });
            }
        },

        async resetChat() {
            if (this.loading) return;
            if (this.messages.length > 0 && !confirm('Mulai chat baru? Riwayat akan dihapus.')) return;
            this.messages = [];
            try {
                await fetch('{{ route('chat.reset') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
            } catch (e) {}
        },

        scrollToBottom() {
            const el = this.$refs.scrollArea;
            if (el) el.scrollTop = el.scrollHeight;
        },

        renderMessage(text) {
            const esc = (text ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            // wa.me/xxx → link
            let html = esc.replace(/(wa\.me\/\d+)/g, '<a href="https://$1" target="_blank" class="text-emerald-600 font-semibold underline">$1</a>');
            // /order /track → link
            html = html.replace(/(^|\s)(\/order|\/track)(\b)/g, '$1<a href="$2" class="text-[#1B3A6E] font-semibold underline">$2</a>$3');
            // **bold**
            html = html.replace(/\*\*(.+?)\*\*/g, '<b>$1</b>');
            return html;
        },
    };
}
</script>
@endpush
