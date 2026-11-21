export function publicHero(services) {
    return {
        services,
        active: 0,
        ready: false,
        visible: true,
        reduced: false,
        touchStart: null,
        frame: null,
        observer: null,
        media: null,
        motionChanged: null,
        visibilityChanged: null,
        get selected() { return this.services[this.active]; },
        init() {
            this.media = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.reduced = this.media.matches;
            this.motionChanged = event => {
                this.reduced = event.matches;
                if (this.reduced) this.resetPointer();
            };
            this.visibilityChanged = () => { if (document.hidden) this.resetPointer(); };
            this.media.addEventListener('change', this.motionChanged);
            document.addEventListener('visibilitychange', this.visibilityChanged);
            if ('IntersectionObserver' in window) {
                this.observer = new IntersectionObserver(entries => {
                    this.visible = entries[0].isIntersecting;
                    if (!this.visible) this.resetPointer();
                });
                this.observer.observe(this.$el);
            }
            this.$nextTick(() => { this.ready = true; });
        },
        choose(index) {
            if (!this.services.length) return;
            this.active = (index + this.services.length) % this.services.length;
        },
        slideStyle(index) {
            const count = this.services.length;
            let distance = index - this.active;
            if (distance > count / 2) distance -= count;
            if (distance < -count / 2) distance += count;
            const position = Math.max(-2, Math.min(2, distance));
            return { '--slot': position, '--slide-scale': distance === 0 ? 1 : 0.92, '--slide-opacity': distance === 0 ? 1 : Math.abs(distance) === 1 ? 0.23 : 0 };
        },
        pointer(event) {
            if (this.reduced || !this.visible || document.hidden || event.pointerType !== 'mouse') return;
            const rect = this.$refs.stage.getBoundingClientRect();
            const x = Math.max(-1, Math.min(1, (event.clientX - rect.left) / rect.width * 2 - 1));
            const y = Math.max(-1, Math.min(1, (event.clientY - rect.top) / rect.height * 2 - 1));
            cancelAnimationFrame(this.frame);
            this.frame = requestAnimationFrame(() => {
                this.$refs.stage.style.setProperty('--pointer-x', `${x * 7}px`);
                this.$refs.stage.style.setProperty('--pointer-y', `${y * 5}px`);
                this.$refs.stage.style.setProperty('--pointer-turn', `${x * 1.5}deg`);
                this.frame = null;
            });
        },
        resetPointer() {
            cancelAnimationFrame(this.frame);
            this.frame = null;
            this.$refs.stage?.style.removeProperty('--pointer-x');
            this.$refs.stage?.style.removeProperty('--pointer-y');
            this.$refs.stage?.style.removeProperty('--pointer-turn');
        },
        touchBegin(event) {
            this.touchStart = event.touches.length === 1 ? { x: event.touches[0].clientX, y: event.touches[0].clientY } : null;
        },
        touchEnd(event) {
            if (!this.touchStart || !event.changedTouches.length) return;
            const x = event.changedTouches[0].clientX - this.touchStart.x;
            const y = event.changedTouches[0].clientY - this.touchStart.y;
            if (Math.abs(x) > 45 && Math.abs(x) > Math.abs(y) * 1.3) this.choose(this.active + (x < 0 ? 1 : -1));
            this.touchStart = null;
        },
        destroy() {
            this.resetPointer();
            this.observer?.disconnect();
            this.media?.removeEventListener('change', this.motionChanged);
            document.removeEventListener('visibilitychange', this.visibilityChanged);
        },
    };
}
