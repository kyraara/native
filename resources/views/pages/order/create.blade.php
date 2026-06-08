@extends('layouts.app')

@section('title', 'Order Joki Tugas — NativeCuy')

@section('content')
<div class="min-h-screen bg-cream relative overflow-hidden pt-20 pb-16">

    {{-- Background decorations --}}
    <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-gold/5 rounded-full blur-3xl pointer-events-none -translate-y-1/3 translate-x-1/3"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-navy/5 rounded-full blur-3xl pointer-events-none translate-y-1/3 -translate-x-1/3"></div>
    <div class="absolute inset-0 bg-[linear-gradient(rgba(27,58,110,0.02)_1px,transparent_1px),linear-gradient(90deg,rgba(27,58,110,0.02)_1px,transparent_1px)] bg-[size:48px_48px] pointer-events-none"></div>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 relative">

        {{-- Header --}}
        <div class="text-center mb-8" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 bg-gold/10 border border-gold/25 rounded-full px-4 py-1.5 mb-4">
                <span class="w-2 h-2 rounded-full bg-gold animate-pulse"></span>
                <span class="text-gold text-xs font-bold tracking-wide uppercase">Form Order</span>
            </div>
            <h1 class="font-display text-3xl sm:text-4xl font-extrabold text-navy-dark mb-2">
                Mulai <span class="font-script text-gold">Order</span> Kamu
            </h1>
            <p class="text-gray-500 text-sm sm:text-base">Isi form di bawah dan kami akan segera menghubungi kamu via WhatsApp.</p>
        </div>

        {{-- Main Card --}}
        <div x-data="orderWizard()" class="bg-white rounded-3xl shadow-xl shadow-navy/5 border border-gray-100 overflow-hidden" data-aos="fade-up" data-aos-delay="80">

            {{-- Step Progress Header --}}
            <div class="px-6 sm:px-8 pt-6 pb-5 border-b border-gray-100">
                {{-- Step labels --}}
                <div class="flex items-center justify-between mb-4">
                    @foreach([
                        ['label' => 'Layanan',    'icon' => 'fa-th-large'],
                        ['label' => 'Detail',     'icon' => 'fa-file-alt'],
                        ['label' => 'Kontak',     'icon' => 'fa-user'],
                        ['label' => 'Konfirmasi', 'icon' => 'fa-check-circle'],
                    ] as $si => $s)
                        <div class="flex flex-col items-center gap-1.5 flex-1">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold transition-all duration-400 border-2"
                                 :class="{{ $si + 1 }} < step
                                     ? 'bg-emerald-500 border-emerald-500 text-white'
                                     : ({{ $si + 1 }} === step
                                         ? 'bg-navy-dark border-navy-dark text-gold'
                                         : 'bg-white border-gray-200 text-gray-400')">
                                <template x-if="{{ $si + 1 }} < step">
                                    <i class="fas fa-check text-xs"></i>
                                </template>
                                <template x-if="{{ $si + 1 }} >= step">
                                    <i class="fas {{ $s['icon'] }} text-xs"></i>
                                </template>
                            </div>
                            <span class="text-xs font-medium hidden sm:block transition-colors duration-300"
                                  :class="{{ $si + 1 }} === step ? 'text-navy-dark font-bold' : ({{ $si + 1 }} < step ? 'text-emerald-600' : 'text-gray-400')">{{ $s['label'] }}</span>
                        </div>
                        @if($si < 3)
                            <div class="flex-1 flex items-center justify-center pt-1 pb-5 sm:pb-6">
                                <div class="h-0.5 w-full transition-all duration-500 rounded-full"
                                     :class="{{ $si + 1 }} < step ? 'bg-emerald-400' : 'bg-gray-100'"></div>
                            </div>
                        @endif
                    @endforeach
                </div>

                {{-- Progress bar --}}
                <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-navy to-gold rounded-full transition-all duration-600 ease-out"
                         :style="`width: ${((step - 1) / 3) * 100}%`"></div>
                </div>
                <div class="flex justify-between mt-2">
                    <span class="text-xs text-gray-400">Langkah <span x-text="step"></span> dari 4</span>
                    <span class="text-xs font-semibold text-navy-dark" x-text="stepTitle"></span>
                </div>
            </div>

            <form method="POST" action="{{ route('order.store') }}" enctype="multipart/form-data" id="orderForm">
                @csrf

                <div class="p-6 sm:p-8">

                    @if($errors->any())
                        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
                            <ul class="text-red-600 text-sm space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ======= STEP 1: Pilih Layanan ======= --}}
                    <div x-show="step === 1"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-x-4"
                         x-transition:enter-end="opacity-100 translate-x-0">

                        <div class="mb-5">
                            <h2 class="font-display text-xl font-bold text-navy-dark">Pilih Layanan</h2>
                            <p class="text-gray-400 text-sm mt-1">Klik layanan yang kamu butuhkan — akan langsung lanjut ke langkah berikutnya.</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($services as $service)
                                <button type="button"
                                        @click="selectService('{{ $service->id }}')"
                                        class="text-left w-full p-4 rounded-2xl border-2 transition-all duration-200 group focus:outline-none"
                                        :class="form.service_id == '{{ $service->id }}'
                                            ? 'border-navy-dark bg-navy-dark/4 ring-2 ring-navy/10 shadow-sm'
                                            : 'border-gray-100 bg-white hover:border-gray-200 hover:shadow-sm'">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 transition-all duration-200"
                                             :class="form.service_id == '{{ $service->id }}' ? 'bg-navy-dark' : 'bg-cream'">
                                            <i class="{{ $service->icon_class }} text-sm transition-colors duration-200"
                                               :class="form.service_id == '{{ $service->id }}' ? 'text-gold' : 'text-navy'"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-navy-dark text-sm">{{ $service->name }}</div>
                                            <div class="text-gray-400 text-xs mt-0.5">Mulai Rp {{ number_format($service->base_price, 0, ',', '.') }}</div>
                                        </div>
                                        <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center flex-shrink-0 transition-all duration-200"
                                             :class="form.service_id == '{{ $service->id }}' ? 'border-navy-dark bg-navy-dark' : 'border-gray-200'">
                                            <i class="fas fa-check text-white text-[9px]"
                                               x-show="form.service_id == '{{ $service->id }}'"></i>
                                        </div>
                                    </div>
                                </button>
                                <input type="radio" name="service_id" value="{{ $service->id }}" class="hidden"
                                       :checked="form.service_id == '{{ $service->id }}'">
                            @endforeach
                        </div>

                        <p class="text-red-500 text-xs mt-3" x-show="errors.service_id" x-text="errors.service_id"></p>

                        <div class="mt-5 text-center">
                            <p class="text-gray-400 text-xs" x-show="!form.service_id">
                                <i class="fas fa-hand-pointer mr-1"></i> Klik layanan untuk langsung lanjut
                            </p>
                            <p class="text-emerald-600 text-xs font-medium" x-show="form.service_id && !autoAdvancing">
                                <i class="fas fa-check-circle mr-1"></i>
                                Dipilih: <span class="font-bold" x-text="getServiceName()"></span>
                            </p>
                            <p class="text-navy text-xs font-medium animate-pulse" x-show="autoAdvancing">
                                <i class="fas fa-arrow-right mr-1"></i> Melanjutkan ke detail tugas...
                            </p>
                        </div>
                    </div>

                    {{-- ======= STEP 2: Detail Tugas ======= --}}
                    <div x-show="step === 2"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-x-4"
                         x-transition:enter-end="opacity-100 translate-x-0">

                        <div class="flex items-center gap-3 mb-6">
                            <h2 class="font-display text-xl font-bold text-navy-dark">Detail Tugas</h2>
                            <span class="bg-navy/8 text-navy text-xs font-semibold px-2.5 py-1 rounded-full border border-navy/15" x-text="getServiceName()"></span>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-navy-dark mb-1.5">Judul Tugas <span class="text-red-400">*</span></label>
                                <input type="text" name="title" x-model="form.title"
                                       placeholder="Contoh: Laporan PKL PT Maju Jaya 2024"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition bg-white">
                                <p class="text-red-500 text-xs mt-1" x-show="errors.title" x-text="errors.title"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-navy-dark mb-1.5">Deskripsi & Instruksi <span class="text-red-400">*</span></label>
                                <textarea name="description" x-model="form.description" rows="4"
                                          placeholder="Jelaskan detail tugas, referensi yang dipakai, format yang diinginkan, dll."
                                          class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition resize-none bg-white"></textarea>
                                <p class="text-red-500 text-xs mt-1" x-show="errors.description" x-text="errors.description"></p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-navy-dark mb-1.5">Deadline <span class="text-red-400">*</span></label>
                                    <input type="date" name="deadline" x-model="form.deadline" :min="minDate"
                                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition bg-white">
                                    <p class="text-red-500 text-xs mt-1" x-show="errors.deadline" x-text="errors.deadline"></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-navy-dark mb-1.5">Budget <span class="text-gray-400 font-normal text-xs">(opsional)</span></label>
                                    <input type="text" name="budget" x-model="form.budget"
                                           placeholder="Contoh: 50rb–100rb"
                                           class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition bg-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-navy-dark mb-1.5">
                                    Upload File Referensi <span class="text-gray-400 font-normal text-xs">(opsional, maks 10MB)</span>
                                </label>
                                <label class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed rounded-2xl cursor-pointer transition-all group"
                                       :class="form.file_name ? 'border-navy/30 bg-navy/4' : 'border-gray-200 hover:border-navy/30 hover:bg-cream'">
                                    <div class="flex flex-col items-center gap-1.5" x-show="!form.file_name">
                                        <i class="fas fa-cloud-upload-alt text-gray-300 text-xl group-hover:text-navy/40 transition-colors"></i>
                                        <span class="text-gray-400 text-xs">PDF, DOC, ZIP, JPG, PNG</span>
                                    </div>
                                    <div class="flex items-center gap-2" x-show="form.file_name">
                                        <i class="fas fa-file-check text-navy"></i>
                                        <span class="text-navy-dark text-xs font-medium" x-text="form.file_name"></span>
                                        <button type="button" @click.prevent="form.file_name = ''" class="text-red-400 hover:text-red-600 text-xs ml-1">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <input type="file" name="attachment" class="hidden"
                                           @change="form.file_name = $event.target.files[0]?.name || ''"
                                           accept=".pdf,.doc,.docx,.zip,.rar,.jpg,.png">
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- ======= STEP 3: Data Kontak ======= --}}
                    <div x-show="step === 3"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-x-4"
                         x-transition:enter-end="opacity-100 translate-x-0">

                        <div class="mb-6">
                            <h2 class="font-display text-xl font-bold text-navy-dark">Data Kontak</h2>
                            <p class="text-gray-400 text-sm mt-1">Kami akan hubungi kamu via WhatsApp untuk konfirmasi order.</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-sm font-semibold text-navy-dark mb-1.5">Nama Lengkap <span class="text-red-400">*</span></label>
                                <input type="text" name="client_name" x-model="form.client_name"
                                       placeholder="Nama kamu"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition bg-white">
                                <p class="text-red-500 text-xs mt-1" x-show="errors.client_name" x-text="errors.client_name"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-navy-dark mb-1.5">Email <span class="text-red-400">*</span></label>
                                <input type="email" name="client_email" x-model="form.client_email"
                                       placeholder="email@kamu.com"
                                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-navy/20 focus:border-navy transition bg-white">
                                <p class="text-red-500 text-xs mt-1" x-show="errors.client_email" x-text="errors.client_email"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-navy-dark mb-1.5">Nomor WhatsApp <span class="text-red-400">*</span></label>
                                <div class="flex rounded-xl overflow-hidden border border-gray-200 focus-within:ring-2 focus-within:ring-navy/20 focus-within:border-navy transition-all bg-white">
                                    <span class="inline-flex items-center px-4 bg-gray-50 border-r border-gray-200 text-gray-500 text-sm font-medium flex-shrink-0">+62</span>
                                    <input type="tel" name="client_phone" x-model="form.client_phone"
                                           placeholder="8123456789"
                                           class="flex-1 px-4 py-3 text-sm focus:outline-none bg-white">
                                </div>
                                <p class="text-red-500 text-xs mt-1" x-show="errors.client_phone" x-text="errors.client_phone"></p>
                            </div>

                            <div class="flex items-start gap-3 bg-emerald-50 border border-emerald-100 rounded-2xl p-4">
                                <i class="fas fa-shield-alt text-emerald-500 mt-0.5 flex-shrink-0"></i>
                                <p class="text-emerald-700 text-xs leading-relaxed">
                                    Data kamu <strong>100% aman dan rahasia</strong>. Kami tidak akan menyebarkan informasimu ke siapapun.
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- ======= STEP 4: Konfirmasi ======= --}}
                    <div x-show="step === 4"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-x-4"
                         x-transition:enter-end="opacity-100 translate-x-0">

                        <div class="mb-6">
                            <h2 class="font-display text-xl font-bold text-navy-dark">Konfirmasi Order</h2>
                            <p class="text-gray-400 text-sm mt-1">Cek kembali sebelum dikirim.</p>
                        </div>

                        <div class="bg-cream rounded-2xl border border-gray-100 overflow-hidden mb-5">
                            @php
                                $rows = [
                                    ['label' => 'Layanan',   'icon' => 'fa-th-large',    'xtext' => "getServiceName()"],
                                    ['label' => 'Judul',     'icon' => 'fa-file-alt',    'xtext' => "form.title || '—'"],
                                    ['label' => 'Deadline',  'icon' => 'fa-calendar',    'xtext' => "form.deadline || '—'"],
                                    ['label' => 'Budget',    'icon' => 'fa-coins',       'xtext' => "form.budget || 'Diskusi'"],
                                    ['label' => 'Nama',      'icon' => 'fa-user',        'xtext' => "form.client_name || '—'"],
                                    ['label' => 'Email',     'icon' => 'fa-envelope',    'xtext' => "form.client_email || '—'"],
                                    ['label' => 'WhatsApp',  'icon' => 'fa-phone',       'xtext' => "form.client_phone ? '+62' + form.client_phone : '—'"],
                                ];
                            @endphp
                            @foreach($rows as $i => $row)
                                <div class="flex items-center gap-4 px-5 py-3.5 {{ $i < count($rows) - 1 ? 'border-b border-gray-100' : '' }}">
                                    <div class="w-7 h-7 rounded-lg bg-white border border-gray-100 flex items-center justify-center flex-shrink-0">
                                        <i class="fas {{ $row['icon'] }} text-navy/50 text-xs"></i>
                                    </div>
                                    <span class="text-gray-500 text-sm w-20 flex-shrink-0">{{ $row['label'] }}</span>
                                    <span class="font-semibold text-navy-dark text-sm flex-1 text-right" x-text="{{ $row['xtext'] }}"></span>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-start gap-3 bg-amber-50 border border-amber-100 rounded-2xl p-4">
                            <i class="fas fa-info-circle text-amber-500 mt-0.5 flex-shrink-0"></i>
                            <p class="text-amber-800 text-xs leading-relaxed">
                                Setelah dikirim, kamu akan mendapat <strong>kode tracking unik</strong> untuk memantau progress. Kami akan menghubungi kamu via WhatsApp dalam <strong>1×24 jam</strong>.
                            </p>
                        </div>
                    </div>

                </div>{{-- end p-8 --}}

                {{-- Navigation buttons --}}
                <div class="px-6 sm:px-8 pb-7 flex items-center gap-3">
                    <button type="button" @click="prevStep()"
                            x-show="step > 1"
                            class="flex items-center gap-2 px-5 py-2.5 border-2 border-gray-200 text-gray-600 font-semibold rounded-xl hover:border-gray-300 hover:bg-gray-50 transition-all text-sm">
                        <i class="fas fa-arrow-left text-xs"></i> Kembali
                    </button>

                    <button type="button" @click="nextStep()"
                            x-show="step < 4"
                            class="ml-auto flex items-center gap-2 bg-navy-dark text-white font-bold px-7 py-2.5 rounded-xl hover:bg-navy transition-colors shadow-sm shadow-navy/20 text-sm">
                        Lanjut <i class="fas fa-arrow-right text-xs"></i>
                    </button>

                    <button type="submit"
                            x-show="step === 4"
                            class="ml-auto btn-gold flex items-center gap-2 px-8 py-2.5 rounded-xl text-navy-dark font-bold shadow-sm shadow-gold/20 text-sm">
                        <i class="fas fa-paper-plane"></i> Kirim Order
                    </button>
                </div>

            </form>
        </div>

        {{-- Trust badges below card --}}
        <div class="flex flex-wrap items-center justify-center gap-4 mt-6" data-aos="fade-up" data-aos-delay="150">
            <div class="flex items-center gap-2 text-gray-400 text-xs">
                <i class="fas fa-shield-alt text-emerald-400"></i> Anti Plagiarisme
            </div>
            <div class="flex items-center gap-2 text-gray-400 text-xs">
                <i class="fas fa-sync-alt text-blue-400"></i> Revisi Gratis
            </div>
            <div class="flex items-center gap-2 text-gray-400 text-xs">
                <i class="fas fa-lock text-purple-400"></i> Data Aman
            </div>
            <div class="flex items-center gap-2 text-gray-400 text-xs">
                <i class="fas fa-clock text-gold"></i> Tepat Waktu
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    const servicesData = @json($services->keyBy('id'));

    function orderWizard() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const minDate = tomorrow.toISOString().split('T')[0];

        const urlParams = new URLSearchParams(window.location.search);
        const preService = urlParams.get('service') || '';

        return {
            step: 1,
            autoAdvancing: false,
            minDate,
            form: {
                service_id: preService,
                title: '', description: '', deadline: '', budget: '',
                file_name: '', client_name: '', client_email: '', client_phone: '',
            },
            errors: {},

            get stepTitle() {
                return ['', 'Pilih Layanan', 'Detail Tugas', 'Data Kontak', 'Konfirmasi'][this.step];
            },

            getServiceName() {
                const s = servicesData[this.form.service_id];
                return s ? s.name : '—';
            },

            selectService(id) {
                this.form.service_id = id;
                this.errors.service_id = '';
                this.autoAdvancing = true;
                setTimeout(() => {
                    this.autoAdvancing = false;
                    this.step = 2;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 400);
            },

            validate() {
                this.errors = {};
                if (this.step === 1 && !this.form.service_id) {
                    this.errors.service_id = 'Pilih layanan terlebih dahulu.';
                    return false;
                }
                if (this.step === 2) {
                    if (!this.form.title.trim())       { this.errors.title       = 'Judul wajib diisi.';       return false; }
                    if (!this.form.description.trim()) { this.errors.description = 'Deskripsi wajib diisi.';   return false; }
                    if (!this.form.deadline)           { this.errors.deadline    = 'Deadline wajib diisi.';    return false; }
                }
                if (this.step === 3) {
                    if (!this.form.client_name.trim())  { this.errors.client_name  = 'Nama wajib diisi.';          return false; }
                    if (!this.form.client_email.trim()) { this.errors.client_email = 'Email wajib diisi.';          return false; }
                    if (!this.form.client_phone.trim()) { this.errors.client_phone = 'Nomor WhatsApp wajib diisi.'; return false; }
                }
                return true;
            },

            nextStep() {
                if (this.validate()) {
                    this.step = Math.min(this.step + 1, 4);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },

            prevStep() {
                this.step = Math.max(this.step - 1, 1);
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },
        };
    }
</script>
@endpush
