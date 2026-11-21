@extends('layouts.app')
@section('title', 'Buat Order — NativeCuy')
@section('content')
@php
    $oldValue = fn ($key, $default = '') => is_scalar(old($key, $default)) ? (string) old($key, $default) : '';
    $formConfig = [
        'step' => $errors->hasAny(['client_name', 'client_email', 'client_phone']) && !$errors->hasAny(['service_id', 'major_id', 'title', 'description', 'deadline', 'budget', 'attachment']) && !collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'details.')) ? 2 : 1,
        'services' => $services->map(fn ($service) => ['id' => $service->id, 'name' => $service->name, 'price' => $service->display_price, 'base_price' => (float) $service->base_price, 'fields' => $service->formSchema(), 'major_ids' => $service->majors->pluck('id')])->values(),
        'initialDetails' => is_array(old('details')) ? old('details') : (object) [],
        'majorIds' => $majors->pluck('id'),
        'minDate' => now('Asia/Jakarta')->addDay()->toDateString(),
        'errors' => collect($errors->messages())->map(fn ($messages) => $messages[0]),
        'initial' => collect(['major_id', 'service_id', 'title', 'description', 'deadline', 'budget', 'client_name', 'client_email', 'client_phone'])->mapWithKeys(fn ($key) => [$key => $oldValue($key, in_array($key, ['major_id', 'service_id']) ? request($key === 'major_id' ? 'major' : 'service', '') : '')]),
    ];
@endphp
<section class="public-workspace">
    <div class="public-container">
        <nav class="public-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Beranda</a><x-admin-icon name="chevron"/><span>Buat order</span></nav>
        <div class="public-page-heading"><h1>Ada yang perlu <span>dibantu?</span></h1><p>Ceritakan kebutuhanmu. Harga dan deadline kita sepakati setelah konsultasi.</p></div>
        <div class="public-order-layout" x-data="publicOrderForm(@js($formConfig))">
            <form class="public-card public-order-form" x-ref="form" method="POST" action="{{ route('order.store') }}" enctype="multipart/form-data" @submit.prevent="submit()" novalidate>
                @csrf
                <input type="hidden" name="submission_token" value="{{ $submissionToken }}">
                <div class="public-honeypot" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" type="text" tabindex="-1" autocomplete="off"></div>
                <ol class="public-stepper" aria-label="Langkah pengisian order">
                    @foreach(['Layanan & detail', 'Data kontak', 'Tinjau & kirim'] as $label)
                        <li :class="{ 'is-active': step === {{ $loop->iteration }}, 'is-complete': step > {{ $loop->iteration }} }" :aria-current="step === {{ $loop->iteration }} ? 'step' : null"><span>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><strong>{{ $label }}</strong></li>
                    @endforeach
                </ol>
                <div class="public-form-body">
                    <div class="public-form-section-heading"><div><h2 tabindex="-1" x-ref="stepHeading" x-text="stepTitle">Layanan & detail</h2></div><span class="public-required-note">* Wajib diisi</span></div>
                    @if($errors->any())<div class="public-message public-message-error" role="alert"><strong>Order belum terkirim.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><p>Jika sebelumnya menambahkan lampiran, pilih ulang berkasnya setelah memperbaiki data.</p></div>@endif
                    <div class="public-message public-message-error" role="alert" x-show="submitError" x-cloak><strong>Order belum bisa dilanjutkan.</strong><p x-text="submitError"></p></div>
                    <div x-show="step === 1">
                        @if($services->isEmpty())
                            <div class="public-message"><strong>Belum ada layanan yang tersedia.</strong><p>Hubungi admin untuk mendiskusikan kebutuhanmu.</p><a href="https://wa.me/{{ $publicContact['wa_number'] }}" target="_blank" rel="noopener">Konsultasi via WhatsApp →</a></div>
                        @endif
                        <div class="public-field"><label for="major">Jurusan <small>opsional · membantu menyaring layanan</small></label><select id="major" name="major_id" x-model="form.major_id" @change="changeMajor()"><option value="">Semua jurusan</option>@foreach($majors as $major)<option value="{{ $major->id }}">{{ $major->name }}</option>@endforeach</select><p class="public-field-error" x-show="errors.major_id" x-text="errors.major_id" x-cloak></p></div>
                        <fieldset class="public-service-fieldset"><legend>Layanan yang dibutuhkan <span>*</span></legend><div class="public-service-options">
                            @foreach($services as $service)
                                <label class="public-service-option" :class="{ 'is-selected': form.service_id == '{{ $service->id }}' }" x-show="!form.major_id || @js($service->majors->pluck('id')->map(fn ($id) => (string) $id)->all()).includes(form.major_id)">
                                    <input type="radio" name="service_id" value="{{ $service->id }}" x-model="form.service_id" @change="selectService('{{ $service->id }}')" required>
                                    <span class="public-service-choice-icon"><x-service-icon :icon="$service->icon_class"/></span><span><strong>{{ $service->name }}</strong><small>Mulai Rp {{ number_format($service->display_price, 0, ',', '.') }}</small></span>
                                </label>
                            @endforeach
                        </div><p class="public-field-error" x-show="errors.service_id" x-text="errors.service_id" x-cloak></p><p class="public-field-help">Harga awal adalah acuan. Harga final mengikuti brief dan kesepakatan konsultasi.</p></fieldset>
                        <section class="public-detail-fields" x-show="detailFields.length" x-cloak aria-labelledby="detail-fields-heading">
                            <div class="public-detail-fields-heading"><h3 id="detail-fields-heading">Detail <span x-text="selectedService?.name"></span></h3><p>Pilihanmu menentukan estimasi harga di ringkasan.</p></div>
                            <template x-for="field in detailFields" :key="selectedService?.id + '-' + field.key">
                                <div class="public-field">
                                    <template x-if="['radio', 'checkbox'].includes(field.type)">
                                        <fieldset class="public-choice-group" :aria-describedby="'error-detail-' + field.key">
                                            <legend><span class="public-label-text" x-text="field.label"></span> <span class="public-required" x-show="field.required">*</span><small x-show="!field.required">opsional</small></legend>
                                            <div class="public-choice-options">
                                                <template x-for="option in field.options" :key="option.value">
                                                    <label class="public-choice" :class="{ 'is-selected': isChosen(field, option) }">
                                                        <input :type="field.type" :name="field.type === 'checkbox' ? 'details[' + field.key + '][]' : 'details[' + field.key + ']'" :value="option.value" x-model="form.details[field.key]" @change="clearDetailError(field)">
                                                        <span class="public-choice-text" x-text="option.label"></span>
                                                        <small x-show="optionPrice(field, option)" x-text="optionPrice(field, option)"></small>
                                                    </label>
                                                </template>
                                            </div>
                                        </fieldset>
                                    </template>
                                    <template x-if="!['radio', 'checkbox'].includes(field.type)">
                                        <div>
                                            <label :for="'detail-' + field.key"><span class="public-label-text" x-text="field.label"></span> <span x-show="field.required">*</span><small x-show="!field.required">opsional</small></label>
                                            <template x-if="field.type === 'select'">
                                                <select :id="'detail-' + field.key" :name="'details[' + field.key + ']'" x-model="form.details[field.key]" @change="clearDetailError(field)" :aria-invalid="!!detailError(field)" :aria-describedby="'error-detail-' + field.key">
                                                    <option value="">Pilih…</option>
                                                    <template x-for="option in field.options" :key="option.value"><option :value="option.value" :selected="form.details[field.key] === option.value" x-text="option.label + (optionPrice(field, option) ? ' · ' + optionPrice(field, option) : '')"></option></template>
                                                </select>
                                            </template>
                                            <template x-if="field.type === 'textarea'">
                                                <textarea :id="'detail-' + field.key" :name="'details[' + field.key + ']'" x-model="form.details[field.key]" rows="3" maxlength="2000" :placeholder="field.placeholder || ''" @input="clearDetailError(field)" :aria-invalid="!!detailError(field)" :aria-describedby="'error-detail-' + field.key"></textarea>
                                            </template>
                                            <template x-if="field.type === 'text' || field.type === 'number'">
                                                <input :id="'detail-' + field.key" :name="'details[' + field.key + ']'" :type="field.type" x-model="form.details[field.key]" :min="field.type === 'number' ? (field.min ?? 0) : null" :max="field.type === 'number' ? field.max : null" :maxlength="field.type === 'text' ? 255 : null" :inputmode="field.type === 'number' ? 'numeric' : null" :placeholder="field.placeholder || (field.type === 'number' && field.min ? 'Minimal ' + field.min : '')" @input="clearDetailError(field)" :aria-invalid="!!detailError(field)" :aria-describedby="'error-detail-' + field.key">
                                            </template>
                                        </div>
                                    </template>
                                    <p class="public-field-help" x-show="field.help || unitPrice(field)" x-text="[unitPrice(field), field.help].filter(Boolean).join(' · ')"></p>
                                    <p class="public-field-error" :id="'error-detail-' + field.key" x-show="detailError(field)" x-text="detailError(field)"></p>
                                </div>
                            </template>
                        </section>
                        <div class="public-form-divider"></div>
                        <div class="public-field"><label for="order-title">Judul kebutuhan <span>*</span></label><input id="order-title" name="title" x-model="form.title" type="text" maxlength="255" required placeholder="Contoh: Presentasi seminar proposal" :aria-invalid="!!errors.title" aria-describedby="error-title"><p id="error-title" class="public-field-error" x-show="errors.title" x-text="errors.title" x-cloak></p></div>
                        <div class="public-field"><label for="order-description">Detail & instruksi <span>*</span></label><textarea id="order-description" name="description" x-model="form.description" rows="5" maxlength="5000" required placeholder="Ceritakan kebutuhan, format, jumlah halaman, serta referensi yang perlu digunakan." :aria-invalid="!!errors.description" aria-describedby="error-description"></textarea><div class="public-field-meta"><p id="error-description" class="public-field-error" x-show="errors.description" x-text="errors.description" x-cloak></p><span x-text="form.description.length + ' / 5.000'"></span></div></div>
                        <div class="public-field-row"><div class="public-field"><label for="order-deadline">Deadline yang diajukan <span>*</span></label><input id="order-deadline" name="deadline" type="date" x-model="form.deadline" :min="minDate" required :aria-invalid="!!errors.deadline" aria-describedby="error-deadline"><p id="error-deadline" class="public-field-error" x-show="errors.deadline" x-text="errors.deadline" x-cloak></p><p class="public-field-help">Bisa disesuaikan setelah konsultasi.</p></div><div class="public-field"><label for="order-budget">Budget perkiraan <small>opsional</small></label><input id="order-budget" name="budget" type="text" x-model="form.budget" maxlength="50" placeholder="Contoh: Rp100.000–Rp200.000"><p class="public-field-error" x-show="errors.budget" x-text="errors.budget" x-cloak></p><p class="public-field-help">Kosongkan jika ingin berdiskusi dulu.</p></div></div>
                        <div class="public-field"><label for="order-attachment">Lampiran referensi <small>opsional</small></label><div class="public-upload"><x-admin-icon name="download"/><div><strong x-text="fileName || 'Tambahkan brief atau referensi'"></strong><span>PDF, DOC, DOCX, JPG, PNG · maksimal 10 MB</span></div><input id="order-attachment" name="attachment" type="file" x-ref="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" @change="fileChanged($event)" aria-describedby="error-attachment"></div><button class="public-text-button" type="button" x-show="fileName" @click="removeFile()" x-cloak>Hapus lampiran</button><p id="error-attachment" class="public-field-error" x-show="errors.attachment" x-text="errors.attachment" x-cloak></p></div>
                    </div>
                    <div x-show="step === 2" x-cloak>
                        <p class="public-section-intro">Pastikan kontaknya aktif agar admin bisa menghubungi kamu untuk konsultasi.</p>
                        <div class="public-field"><label for="client-name">Nama lengkap <span>*</span></label><input id="client-name" name="client_name" type="text" x-model="form.client_name" maxlength="100" autocomplete="name" required placeholder="Nama kamu" :aria-invalid="!!errors.client_name" aria-describedby="error-name"><p id="error-name" class="public-field-error" x-show="errors.client_name" x-text="errors.client_name" x-cloak></p></div>
                        <div class="public-field"><label for="client-email">Email <span>*</span></label><input id="client-email" name="client_email" type="email" x-model="form.client_email" maxlength="100" autocomplete="email" required placeholder="nama@email.com" :aria-invalid="!!errors.client_email" aria-describedby="error-email"><p id="error-email" class="public-field-error" x-show="errors.client_email" x-text="errors.client_email" x-cloak></p></div>
                        <div class="public-field"><label for="client-phone">Nomor WhatsApp <span>*</span></label><input id="client-phone" name="client_phone" type="tel" x-model="form.client_phone" maxlength="22" autocomplete="tel" required placeholder="081234567890" :aria-invalid="!!errors.client_phone" aria-describedby="error-phone"><p class="public-field-help">Boleh menggunakan awalan 08, 628, atau +628.</p><p id="error-phone" class="public-field-error" x-show="errors.client_phone" x-text="errors.client_phone" x-cloak></p></div>
                        <div class="public-note"><x-admin-icon name="check"/><p>Kontak ini digunakan untuk membahas order dan mengirim informasi pengerjaan.</p></div>
                    </div>
                    <div x-show="step === 3" x-cloak>
                        <p class="public-section-intro">Cek kembali brief dan kontakmu. Setelah dikirim, kamu akan mendapatkan kode tracking.</p>
                        <div class="public-review-heading"><h3>Kebutuhan kamu</h3><button type="button" @click="back(1)">Ubah detail</button></div>
                        <dl class="public-review"><div><dt>Layanan</dt><dd x-text="selectedService?.name || '—'"></dd></div><template x-for="answer in detailAnswers" :key="answer.key"><div><dt x-text="answer.label"></dt><dd x-text="answer.display"></dd></div></template><div x-show="estimate"><dt>Estimasi mulai dari</dt><dd><strong x-text="estimate ? currency(estimate.total) : ''"></strong> <small class="public-muted">· belum harga final</small></dd></div><div><dt>Judul</dt><dd x-text="form.title"></dd></div><div><dt>Detail</dt><dd class="public-preline" x-text="form.description"></dd></div><div><dt>Deadline diajukan</dt><dd x-text="dateLabel(form.deadline)"></dd></div><div><dt>Budget perkiraan</dt><dd x-text="form.budget || 'Diskusikan dengan admin'"></dd></div><div><dt>Lampiran</dt><dd x-text="fileName || 'Tidak ada lampiran'"></dd></div></dl>
                        <div class="public-review-heading"><h3>Kontak kamu</h3><button type="button" @click="back(2)">Ubah kontak</button></div>
                        <dl class="public-review"><div><dt>Nama</dt><dd x-text="form.client_name"></dd></div><div><dt>Email</dt><dd x-text="form.client_email"></dd></div><div><dt>WhatsApp</dt><dd x-text="form.client_phone"></dd></div></dl>
                        <div class="public-note"><x-admin-icon name="clock"/><p>Order masuk dengan status <strong>Menunggu konfirmasi</strong>. Admin akan meninjau brief dan menyepakati harga final serta deadline bersama kamu.</p></div>
                    </div>
                </div>
                <div class="public-form-actions"><button type="button" class="public-button public-button-outline" x-show="step > 1" @click="back()" x-cloak>Kembali</button><span class="public-form-action-note" x-show="step === 1">Langkah kecil untuk mulai beres.</span><button type="button" class="public-button public-button-navy" x-show="step < 3" @click="next()" :disabled="!services.length">Lanjut <x-admin-icon name="arrow"/></button><button type="submit" class="public-button public-button-gold" x-show="step === 3" :disabled="submitting || !services.length" x-cloak><span x-text="submitting ? 'Mengirim order…' : 'Kirim order'"></span><x-admin-icon name="arrow"/></button></div>
            </form>
            <aside class="public-order-sidebar"><section class="public-summary"><div class="public-summary-top"><span class="public-eyebrow">RINGKASAN ORDER</span><x-admin-icon name="orders"/></div><h2 x-text="selectedService?.name || 'Mulai dari pilih layanan'"></h2><div class="public-summary-price" x-show="selectedService"><span>Estimasi mulai dari</span><strong x-text="estimate ? currency(estimate.total) : ''" aria-live="polite"></strong><ul class="public-estimate-lines" x-show="estimate && estimate.lines.length > 1"><template x-for="(line, index) in estimate ? estimate.lines : []" :key="index"><li><span><span x-text="line.detail || line.label"></span><small x-show="line.detail" x-text="line.label"></small></span><b x-text="(index ? '+' : '') + currency(line.amount)"></b></li></template></ul></div><dl><div><dt>Deadline diajukan</dt><dd x-text="dateLabel(form.deadline)"></dd></div><div><dt>Budget perkiraan</dt><dd x-text="form.budget || 'Diskusikan dulu'"></dd></div></dl><div class="public-summary-note"><x-admin-icon name="check"/><p>Ini estimasi, belum harga final. Harga final dan deadline disepakati bersama admin saat konsultasi.</p></div></section><section class="public-help-card"><img src="{{ asset('images/nativecuy_icon_128x128.png') }}" alt="Maskot NativeCuy" width="56" height="56"><h3>Masih bingung pilih layanan?</h3><p>Ceritakan dulu kebutuhanmu. Kami bantu arahkan.</p><a href="https://wa.me/{{ $publicContact['wa_number'] }}?text={{ urlencode('Halo NativeCuy, saya ingin konsultasi sebelum membuat order.') }}" target="_blank" rel="noopener">Konsultasi gratis <x-admin-icon name="arrow"/></a></section><a class="public-existing-order" href="{{ route('order.trackSearch') }}">Sudah punya order? <strong>Cek tracking →</strong></a></aside>
        </div>
    </div>
</section>
@endsection
