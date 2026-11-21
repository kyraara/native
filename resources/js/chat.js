export function cuyAiChat(config) {
    return {
        open: false, loading: false, historyLoading: false, historyLoaded: false,
        draft: '', messages: [], pending: '', error: '', confirmReset: false,
        controller: null, viewportHandler: null, previousOverflow: '', inertElements: [],
        init() {
            this.viewportHandler = () => {
                const viewport = window.visualViewport;
                this.$el.style.setProperty('--chat-height', `${viewport?.height || window.innerHeight}px`);
                this.$el.style.setProperty('--chat-top', `${viewport?.offsetTop || 0}px`);
                if (this.open) document.body.style.overflow = window.innerWidth <= 600 ? 'hidden' : this.previousOverflow;
            };
            window.visualViewport?.addEventListener('resize', this.viewportHandler);
            window.visualViewport?.addEventListener('scroll', this.viewportHandler);
            window.addEventListener('resize', this.viewportHandler);
            this.viewportHandler();
        },
        async show() {
            this.open = true;
            this.previousOverflow = document.body.style.overflow;
            this.inertElements = [...document.querySelectorAll('body > header, body > main, body > footer, body > .public-skip')].map(el => ({ el, wasInert: el.inert }));
            this.inertElements.forEach(({ el }) => { el.inert = true; });
            this.viewportHandler();
            this.$nextTick(() => { this.$refs.heading.focus(); this.scrollToBottom(); });
            if (!this.historyLoaded) await this.loadHistory();
        },
        close() {
            if (!this.open) return;
            this.open = false; this.confirmReset = false;
            document.body.style.overflow = this.previousOverflow;
            this.inertElements.forEach(({ el, wasInert }) => { el.inert = wasInert; });
            this.inertElements = [];
            this.$nextTick(() => this.$refs.launcher.focus());
        },
        trap(event) {
            if (event.key !== 'Tab') return;
            const elements = [...this.$refs.panel.querySelectorAll('a[href], button:not([disabled]), textarea:not([disabled]), [tabindex="0"]')].filter(el => el.getClientRects().length);
            const first = elements[0], last = elements.at(-1);
            if (!elements.includes(document.activeElement) || (event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last)) {
                event.preventDefault(); (event.shiftKey ? last : first)?.focus();
            }
        },
        async request(url, body) {
            const controller = new AbortController(); this.controller = controller;
            const timeout = setTimeout(() => controller.abort(), 35000);
            try {
                const response = await fetch(url, {
                    method: body === undefined ? 'GET' : 'POST',
                    headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: body === undefined ? undefined : JSON.stringify(body), signal: controller.signal, cache: 'no-store',
                });
                if (!response.ok) throw new Error(response.status === 429 ? 'Tunggu sebentar, lalu coba lagi. Terlalu banyak pesan dalam satu menit.' : response.status === 419 ? 'Sesi chat berakhir. Muat ulang halaman untuk melanjutkan.' : 'Chat belum tersambung. Coba lagi atau hubungi admin lewat WhatsApp.');
                return await response.json();
            } catch (error) {
                if (error.name === 'AbortError' || error instanceof TypeError) throw new Error('Koneksi terputus atau terlalu lama. Pesanmu tetap tersimpan di kolom chat.');
                throw error;
            } finally { clearTimeout(timeout); if (this.controller === controller) this.controller = null; }
        },
        async loadHistory() {
            if (this.historyLoading) return false;
            this.historyLoading = true; this.error = '';
            try {
                const data = await this.request(config.history);
                this.messages = (data.history || []).filter(m => ['user', 'assistant'].includes(m.role) && typeof m.content === 'string').slice(-24);
                this.historyLoaded = true; return true;
            } catch (error) { this.error = error.message; return false; }
            finally { this.historyLoading = false; this.$nextTick(() => this.scrollToBottom()); }
        },
        quick(text) { this.draft = text; this.send(); },
        keydown(event) { if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) { event.preventDefault(); this.send(); } },
        resizeInput() { const el = this.$refs.input; el.style.height = 'auto'; el.style.height = `${Math.min(el.scrollHeight, 120)}px`; },
        async send() {
            const text = this.draft.trim();
            if (!text || text.length > 1000 || this.loading || this.historyLoading) return;
            this.loading = true; this.error = ''; this.confirmReset = false;
            try {
                if (!this.historyLoaded) {
                    if (!await this.loadHistory()) return;
                    const last = this.messages.slice(-2);
                    if (last[0]?.role === 'user' && last[0].content === text && last[1]?.role === 'assistant') { this.draft = ''; return; }
                }
                this.pending = text; this.$nextTick(() => this.scrollToBottom());
                const data = await this.request(config.send, { message: text });
                if (typeof data.reply !== 'string') throw new Error('Balasan belum tersedia. Silakan coba lagi.');
                this.messages.push({ role: 'user', content: text }, { role: 'assistant', content: data.reply });
                this.messages = this.messages.slice(-24); this.draft = '';
            } catch (error) { this.error = error.message; this.historyLoaded = false; }
            finally { this.pending = ''; this.loading = false; this.$nextTick(() => { this.resizeInput(); this.scrollToBottom(); }); }
        },
        async resetChat() {
            if (this.loading || this.historyLoading) return;
            this.loading = true; this.error = '';
            try { await this.request(config.reset, {}); this.messages = []; this.draft = ''; this.historyLoaded = true; this.confirmReset = false; }
            catch (error) { this.error = error.message; }
            finally { this.loading = false; }
        },
        renderMessage(text) {
            const escaped = String(text).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#39;');
            return escaped.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
                .replace(/(^|\s)(?:https:\/\/)?(wa\.me\/\d+)/g, '$1<a href="https://$2" target="_blank" rel="noopener noreferrer">$2</a>')
                .replace(/(^|\s)(\/order(?:\?service=\d+)?|\/track)(?=[\s.,!?)]|$)/g, '$1<a href="$2">$2</a>');
        },
        scrollToBottom() { const el = this.$refs.scrollArea; if (el) el.scrollTop = el.scrollHeight; },
        destroy() {
            this.controller?.abort();
            if (this.open) { document.body.style.overflow = this.previousOverflow; this.inertElements.forEach(({ el, wasInert }) => { el.inert = wasInert; }); }
            window.visualViewport?.removeEventListener('resize', this.viewportHandler);
            window.visualViewport?.removeEventListener('scroll', this.viewportHandler);
            window.removeEventListener('resize', this.viewportHandler);
        },
    };
}
