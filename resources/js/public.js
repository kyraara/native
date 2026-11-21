import { visibleFields, estimate, answerList, validateDetails, blankDetails, normalizeErrorKey, optionPriceLabel, unitPriceLabel, rupiah } from './service-fields.js';

export function publicNavigation() {
    return {
        open: false,
        scrolled: false,
        onScroll: null,
        onResize: null,
        init() {
            this.onScroll = () => { this.scrolled = window.scrollY > 40; };
            this.onResize = () => { if (window.innerWidth >= 960) this.open = false; };
            this.onScroll();
            window.addEventListener('scroll', this.onScroll, { passive: true });
            window.addEventListener('resize', this.onResize, { passive: true });
        },
        close(returnFocus = false) {
            if (!this.open) return;
            this.open = false;
            if (returnFocus) this.$refs.menuToggle.focus();
        },
        destroy() {
            window.removeEventListener('scroll', this.onScroll);
            window.removeEventListener('resize', this.onResize);
        },
    };
}

export function publicCatalog(initial = '') {
    return {
        major: String(initial),
        matches(ids) { return !this.major || ids.map(String).includes(this.major); },
    };
}

export function publicOrderForm(config) {
    return {
        step: config.step || 1,
        services: config.services,
        majorIds: config.majorIds.map(String),
        form: { ...config.initial, details: {} },
        detailsServiceId: '',
        minDate: config.minDate,
        errors: config.errors || {},
        submitting: false,
        submitError: '',
        fileName: '',
        fileError: '',
        init() {
            if (!this.majorIds.includes(String(this.form.major_id))) this.form.major_id = '';
            if (!this.visibleServices.some(service => String(service.id) === String(this.form.service_id))) this.form.service_id = '';
            this.form.details = blankDetails(this.selectedFields, config.initialDetails || {});
            this.detailsServiceId = String(this.form.service_id);
            window.addEventListener('pageshow', () => { this.submitting = false; });
        },
        // Service-specific fields (see resources/js/service-fields.js).
        get selectedFields() { return this.selectedService?.fields || []; },
        // Empty while switching services, so the new fields never render with the previous service's answers.
        get detailFields() { return this.detailsServiceId === String(this.form.service_id) ? visibleFields(this.selectedFields, this.form.details) : []; },
        isChosen(field, option) {
            const answer = this.form.details[field.key];
            return Array.isArray(answer) ? answer.includes(option.value) : answer === option.value;
        },
        get estimate() {
            const service = this.selectedService;
            if (!service) return null;
            // Until every visible required "harga dasar" choice is made, show the catalog starting price.
            const pendingBase = this.detailFields.some(field => field.pricing === 'base' && field.required && !this.form.details[field.key]);
            if (pendingBase) return { total: service.price, lines: [], pending: true };
            return estimate(this.selectedFields, this.form.details, service.base_price);
        },
        get detailAnswers() { return answerList(this.selectedFields, this.form.details); },
        optionPrice(field, option) { return optionPriceLabel(field, option); },
        unitPrice(field) { return unitPriceLabel(field); },
        detailError(field) { return this.errors['details.' + field.key]; },
        clearDetailError(field) { delete this.errors['details.' + field.key]; },
        get visibleServices() {
            return this.services.filter(service => !this.form.major_id || service.major_ids.map(String).includes(String(this.form.major_id)));
        },
        get selectedService() { return this.services.find(service => String(service.id) === String(this.form.service_id)); },
        get stepTitle() { return ['Layanan & detail', 'Data kontak', 'Tinjau & kirim'][this.step - 1]; },
        currency(value) { return rupiah(value); },
        dateLabel(value) {
            if (!value) return 'Belum dipilih';
            const date = new Date(value + 'T12:00:00');
            return Number.isNaN(date.getTime()) ? value : new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
        },
        changeMajor() {
            if (!this.visibleServices.some(service => String(service.id) === String(this.form.service_id))) this.selectService('');
            delete this.errors.major_id;
            delete this.errors.service_id;
        },
        selectService(id) {
            this.form.service_id = String(id);
            delete this.errors.service_id;
            // Answers belong to one service; a different service starts with a clean set of fields.
            // (Compared with detailsServiceId because x-model may already have updated service_id.)
            if (this.detailsServiceId !== String(id)) {
                this.detailsServiceId = String(id);
                this.form.details = blankDetails(this.selectedFields);
                Object.keys(this.errors).filter(key => key.startsWith('details.')).forEach(key => delete this.errors[key]);
            }
        },
        fileChanged(event) {
            const file = event.target.files[0];
            this.fileName = file?.name || '';
            this.fileError = '';
            delete this.errors.attachment;
            if (file && file.size > 10 * 1024 * 1024) this.fileError = 'Ukuran lampiran maksimal 10 MB.';
            else if (file && !/\.(pdf|docx?|jpe?g|png)$/i.test(file.name)) this.fileError = 'Gunakan PDF, DOC, DOCX, JPG, atau PNG.';
            if (this.fileError) this.errors.attachment = this.fileError;
        },
        removeFile() { this.$refs.attachment.value = ''; this.fileName = ''; this.fileError = ''; delete this.errors.attachment; },
        validateStep(step) {
            const errors = {};
            if (step === 1) {
                if (!this.visibleServices.some(service => String(service.id) === String(this.form.service_id))) errors.service_id = 'Pilih layanan yang tersedia.';
                if (!this.form.title.trim()) errors.title = 'Tuliskan judul kebutuhan kamu.';
                else if (this.form.title.length > 255) errors.title = 'Judul maksimal 255 karakter.';
                if (!this.form.description.trim()) errors.description = 'Jelaskan kebutuhan dan instruksinya.';
                else if (this.form.description.length > 5000) errors.description = 'Deskripsi maksimal 5.000 karakter.';
                if (!this.form.deadline || this.form.deadline < this.minDate) errors.deadline = 'Pilih tanggal mulai besok atau setelahnya.';
                if (this.form.budget.length > 50) errors.budget = 'Budget maksimal 50 karakter.';
                if (this.fileError) errors.attachment = this.fileError;
                Object.assign(errors, validateDetails(this.selectedFields, this.form.details));
            } else {
                if (!this.form.client_name.trim()) errors.client_name = 'Nama lengkap wajib diisi.';
                else if (this.form.client_name.length > 100) errors.client_name = 'Nama maksimal 100 karakter.';
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.form.client_email.trim())) errors.client_email = 'Masukkan alamat email yang valid.';
                let phone = this.form.client_phone.replace(/\D/g, '');
                if (phone.startsWith('0')) phone = '62' + phone.slice(1);
                else if (phone.startsWith('8')) phone = '62' + phone;
                if (!/^62\d{8,13}$/.test(phone)) errors.client_phone = 'Gunakan nomor WhatsApp aktif, contoh 081234567890.';
            }
            this.errors = errors;
            return Object.keys(errors).length === 0;
        },
        focusStep() {
            this.$nextTick(() => {
                const firstError = Object.keys(this.errors)[0];
                const name = firstError?.startsWith('details.') ? `details[${firstError.slice(8)}]` : firstError;
                const field = name && [...this.$refs.form.querySelectorAll(`[name="${name}"], [name="${name}[]"]`)].find(element => element.getClientRects().length);
                const target = field && field.type !== 'hidden' ? field : this.$refs.stepHeading;
                target?.focus({ preventScroll: true });
                this.$refs.form.scrollIntoView({ block: 'start', behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
            });
        },
        next() {
            if (this.validateStep(this.step)) this.step = Math.min(3, this.step + 1);
            this.focusStep();
        },
        back(target) { this.step = target || Math.max(1, this.step - 1); this.errors = {}; this.focusStep(); },
        async submit() {
            if (this.submitting) return;
            if (this.step < 3) { this.next(); return; }
            for (const step of [1, 2]) {
                if (!this.validateStep(step)) { this.step = step; this.focusStep(); return; }
            }
            this.submitting = true;
            this.submitError = '';
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 45000);
            try {
                const response = await fetch(this.$refs.form.action, {
                    method: 'POST', body: new FormData(this.$refs.form),
                    headers: { Accept: 'application/json' }, signal: controller.signal,
                });
                const data = await response.json().catch(() => ({}));
                if (response.status === 422) {
                    this.errors = {};
                    for (const [key, messages] of Object.entries(data.errors || {})) this.errors[normalizeErrorKey(key)] ??= messages[0];
                    const detailFields = ['major_id', 'service_id', 'title', 'description', 'deadline', 'budget', 'attachment'];
                    if (detailFields.some(key => this.errors[key]) || Object.keys(this.errors).some(key => key.startsWith('details.'))) this.step = 1;
                    else if (['client_name', 'client_email', 'client_phone'].some(key => this.errors[key])) this.step = 2;
                    this.submitError = this.errors.submission_token || 'Ada data yang perlu diperbaiki. Isian dan lampiranmu tetap tersimpan di form ini.';
                    this.focusStep();
                    return;
                }
                if (!response.ok) throw new Error(response.status === 419 ? 'Sesi formulir kedaluwarsa. Salin detail kebutuhanmu, lalu muat ulang halaman sebelum mengirim kembali.' : response.status === 429 ? 'Terlalu banyak percobaan. Tunggu satu menit lalu kirim kembali.' : 'Pengiriman belum dapat dikonfirmasi. Isianmu tetap ada; coba kirim kembali.');
                if (!data.redirect_url || new URL(data.redirect_url, location.href).origin !== location.origin) throw new Error('Halaman tracking belum tersedia. Coba kirim kembali untuk membuka order yang sama.');
                window.location.assign(data.redirect_url);
            } catch (error) {
                this.submitError = error.name === 'AbortError' || error instanceof TypeError ? 'Koneksi terputus atau terlalu lama. Isianmu tetap ada; coba kirim kembali untuk memeriksa pengiriman.' : error.message;
            } finally {
                clearTimeout(timeout);
                this.submitting = false;
            }
        },
    };
}

export function publicTracking(config) {
    return {
        order: config.order,
        endpoint: config.endpoint,
        loading: false,
        error: '',
        needsVerification: false,
        notice: '',
        copied: false,
        lastChecked: '',
        timer: null,
        controller: null,
        visibilityHandler: null,
        init() {
            this.lastChecked = this.clock();
            this.timer = window.setInterval(() => { if (!document.hidden) this.refresh(); }, 60000);
            this.visibilityHandler = () => { if (!document.hidden) this.refresh(); };
            document.addEventListener('visibilitychange', this.visibilityHandler);
        },
        destroy() {
            window.clearInterval(this.timer);
            this.controller?.abort();
            document.removeEventListener('visibilitychange', this.visibilityHandler);
        },
        clock() { return new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' }).format(new Date()) + ' WIB'; },
        get stage() { return ['pending', 'processing', 'review', 'done'].indexOf(this.order.status); },
        get statusDescription() {
            return {
                pending: 'Brief sudah diterima. Admin akan menghubungi kamu untuk menyepakati harga dan deadline.',
                processing: 'Order sedang dikerjakan sesuai kebutuhan dan kesepakatan dengan admin.',
                review: 'Order berada pada tahap review. Lihat pesan admin untuk informasi selanjutnya.',
                done: 'Pengerjaan order sudah selesai. Hubungi admin untuk informasi hasil dan revisi.',
                cancelled: 'Order ini dibatalkan. Hubungi admin jika kamu membutuhkan penjelasan.',
            }[this.order.status] || 'Lihat pesan admin untuk informasi order.';
        },
        async refresh() {
            if (this.loading || this.needsVerification) return;
            this.loading = true;
            this.error = '';
            this.notice = '';
            this.controller = new AbortController();
            const timeout = window.setTimeout(() => this.controller.abort(), 15000);
            try {
                const response = await fetch(this.endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: this.controller.signal });
                if (response.status === 403) {
                    this.needsVerification = true;
                    window.clearInterval(this.timer);
                    throw new Error('Sesi tracking berakhir. Verifikasi kembali 4 digit WhatsApp untuk melihat pembaruan.');
                }
                if (!response.ok) throw new Error(response.status === 429 ? 'Terlalu banyak permintaan. Coba lagi sebentar.' : 'Pembaruan belum berhasil. Data terakhir tetap ditampilkan.');
                const data = await response.json();
                if (!['pending', 'processing', 'review', 'done', 'cancelled'].includes(data.status)) throw new Error('Data pembaruan belum tersedia. Coba lagi sebentar.');
                this.notice = JSON.stringify(this.order) !== JSON.stringify(data) ? 'Informasi order telah diperbarui.' : '';
                this.order = data;
                this.lastChecked = this.clock();
            } catch (error) {
                this.error = error.name === 'AbortError' ? 'Koneksi membutuhkan waktu lebih lama. Data terakhir tetap ditampilkan.' : (error instanceof TypeError ? 'Koneksi terputus. Data terakhir tetap ditampilkan; coba perbarui kembali.' : error.message);
            } finally {
                window.clearTimeout(timeout);
                this.loading = false;
            }
        },
        async copy(value) {
            try {
                await navigator.clipboard.writeText(value);
                this.copied = true;
                window.setTimeout(() => this.copied = false, 2500);
            } catch {
                this.error = 'Belum bisa menyalin otomatis. Pilih kode tracking lalu salin secara manual.';
            }
        },
    };
}

export function publicMarquee() {
    return {
        paused: false,
        visible: true,
        observer: null,
        onVisibility: null,
        init() {
            this.onVisibility = () => { this.paused = document.hidden || !this.visible; };
            this.observer = new IntersectionObserver(([entry]) => { this.visible = entry.isIntersecting; this.onVisibility(); });
            this.observer.observe(this.$el);
            document.addEventListener('visibilitychange', this.onVisibility);
            this.onVisibility();
        },
        destroy() { this.observer?.disconnect(); document.removeEventListener('visibilitychange', this.onVisibility); },
    };
}
