export function adminWorkspace() {
    return {
        menuOpen: false,
        searchOpen: false,
        mobile: window.innerWidth < 1024,
        media: null,
        resizeHandler: null,
        notificationTimer: null,
        previousOverflow: '',
        returnTarget: null,
        init() {
            this.media = window.matchMedia('(max-width: 1023px)');
            this.mobile = this.media.matches;
            this.resizeHandler = event => {
                this.mobile = event.matches;
                if (!this.mobile) { this.closeMenu(false); this.searchOpen = false; }
            };
            this.media.addEventListener('change', this.resizeHandler);
            this.notificationTimer = window.setInterval(() => {
                if (!document.hidden) this.$store.adminNotifications.refresh();
            }, 60000);
        },
        openMenu() {
            if (!this.mobile) return;
            this.returnTarget = document.activeElement;
            this.previousOverflow = document.body.style.overflow;
            this.menuOpen = true;
            this.searchOpen = false;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => this.$refs.menuClose.focus());
        },
        closeMenu(restoreFocus = true) {
            if (!this.menuOpen) return;
            this.menuOpen = false;
            document.body.style.overflow = this.previousOverflow;
            if (restoreFocus) this.$nextTick(() => this.returnTarget?.focus());
        },
        trapMenu(event) {
            if (!this.mobile || !this.menuOpen) return;
            const targets = [...this.$refs.menu.querySelectorAll('a[href],button:not([disabled]),input:not([disabled])')].filter(element => element.getClientRects().length);
            const first = targets[0], last = targets[targets.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        },
        toggleSearch() {
            this.searchOpen = !this.searchOpen;
            if (this.searchOpen) this.$nextTick(() => this.$refs.adminSearch.focus());
        },
        destroy() {
            window.clearInterval(this.notificationTimer);
            this.media?.removeEventListener('change', this.resizeHandler);
            if (this.menuOpen) document.body.style.overflow = this.previousOverflow;
        },
    };
}
