@extends('layouts.admin')

@php
    $editing = $order->exists;
    $oldDetails = old('details');
    $formConfig = [
        'services' => $services,
        'serviceId' => old('service_id', $order->service_id),
        'originalServiceId' => $order->service_id,
        'details' => is_array($oldDetails) ? $oldDetails : (object) $answers,
        'oldEstimate' => $editing && $order->price_estimate !== null ? (float) $order->price_estimate : null,
        'priceFinal' => $order->price_final !== null ? (float) $order->price_final : null,
        'errors' => collect($errors->messages())->mapWithKeys(fn ($messages, $key) => [preg_replace('/^(details\.[^.]+).*$/', '$1', $key) => $messages[0]]),
        'status' => old('status', $order->status),
        'priceInput' => old('price_final', ''),
    ];
@endphp

@section('title', $editing ? 'Edit order #'.$order->tracking_token : 'Order baru')
@section('toolbar-title', $editing ? 'Edit order' : 'Order baru')
@section('page-title', $editing ? 'Edit order' : 'Tambah order')
@section('page-subtitle', $editing ? '#'.$order->tracking_token.' · '.$order->client_name : 'Catat order yang masuk lewat WhatsApp, Instagram, atau langsung.')

@section('content')
<div class="svc-topline">
    <a href="{{ $editing ? route('admin.orders.show', $order) : route('admin.orders.index') }}" class="ui-link"><x-admin-icon name="arrow" class="svc-back-icon"/> {{ $editing ? 'Kembali ke detail order' : 'Kembali ke daftar order' }}</a>
</div>

<form method="POST" action="{{ $editing ? route('admin.orders.update', $order) : route('admin.orders.store') }}" enctype="multipart/form-data"
      x-data="adminOrderForm(@js($formConfig))" @submit="submitting = true" class="ui-grid-main">
    @csrf
    @if($editing) @method('PUT') @endif

    <div class="ui-stack">
        {{-- Service & specific fields --}}
        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Layanan &amp; detail</h2><p>Pilihan di sini menentukan estimasi harga.</p></div></div>
            <div class="ui-card-body">
                <div class="ui-field">
                    <label class="ui-label" for="order-service">Layanan <span class="ui-req">*</span></label>
                    <select id="order-service" name="service_id" class="ui-select" x-model="serviceId" @change="changeService()" required @error('service_id') aria-invalid="true" @enderror>
                        <option value="">Pilih layanan…</option>
                        @foreach($services as $service)
                            <option value="{{ $service['id'] }}">{{ $service['name'] }} — mulai @rupiah($service['price'])</option>
                        @endforeach
                    </select>
                    @error('service_id')<p class="ui-error">{{ $message }}</p>@enderror
                    @if($editing)
                        <p class="ui-help" x-show="serviceId !== originalServiceId" x-cloak>Mengganti layanan akan mengosongkan jawaban field khusus layanan sebelumnya.</p>
                    @endif
                </div>

                <div class="ord-fields" x-show="visible.length" x-cloak>
                    <template x-for="field in visible" :key="serviceId + '-' + field.key">
                        <div class="ui-field ord-field">
                            <template x-if="field.type === 'radio' || field.type === 'checkbox'">
                                <fieldset>
                                    <legend class="ui-label"><span x-text="field.label"></span> <span class="ui-req" x-show="field.required">*</span><small x-show="!field.required">opsional</small></legend>
                                    <div class="ord-choices">
                                        <template x-for="option in field.options" :key="option.value">
                                            <label class="ord-choice" :class="{ 'is-selected': isChosen(field, option) }">
                                                <input :type="field.type" :name="field.type === 'checkbox' ? 'details[' + field.key + '][]' : 'details[' + field.key + ']'" :value="option.value" x-model="details[field.key]" @change="clearError(field)">
                                                <span x-text="option.label"></span>
                                                <small x-show="optionPrice(field, option)" x-text="optionPrice(field, option)"></small>
                                            </label>
                                        </template>
                                    </div>
                                </fieldset>
                            </template>
                            <template x-if="field.type === 'select'">
                                <div class="ui-field">
                                    <label class="ui-label" :for="'d-' + field.key"><span x-text="field.label"></span> <span class="ui-req" x-show="field.required">*</span></label>
                                    <select class="ui-select" :id="'d-' + field.key" :name="'details[' + field.key + ']'" x-model="details[field.key]" @change="clearError(field)" :aria-invalid="!!error(field)">
                                        <option value="">Pilih…</option>
                                        <template x-for="option in field.options" :key="option.value"><option :value="option.value" :selected="details[field.key] === option.value" x-text="option.label + (optionPrice(field, option) ? ' · ' + optionPrice(field, option) : '')"></option></template>
                                    </select>
                                </div>
                            </template>
                            <template x-if="['text', 'textarea', 'number'].includes(field.type)">
                                <div class="ui-field">
                                    <label class="ui-label" :for="'d-' + field.key"><span x-text="field.label"></span> <span class="ui-req" x-show="field.required">*</span><small x-show="!field.required">opsional</small></label>
                                    <input class="ui-input" :id="'d-' + field.key" :name="'details[' + field.key + ']'" :type="field.type === 'number' ? 'number' : 'text'" x-model="details[field.key]" :min="field.type === 'number' ? (field.min ?? 0) : null" :max="field.type === 'number' ? field.max : null" :placeholder="field.placeholder || ''" @input="clearError(field)" :aria-invalid="!!error(field)">
                                </div>
                            </template>
                            <p class="ui-help" x-show="field.help || unitPrice(field)" x-text="[unitPrice(field), field.help].filter(Boolean).join(' · ')"></p>
                            <p class="ui-error" x-show="error(field)" x-text="error(field)"></p>
                        </div>
                    </template>
                </div>
                <p class="ui-help" style="margin-top:12px" x-show="service && !fields.length" x-cloak>Layanan ini memakai form umum tanpa field khusus.</p>
            </div>
        </section>

        {{-- Brief --}}
        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Brief</h2><p>Kebutuhan dan deadline yang diajukan klien.</p></div></div>
            <div class="ui-card-body ord-grid">
                <div class="ui-field svc-span-2">
                    <label class="ui-label" for="order-title">Judul kebutuhan <span class="ui-req">*</span></label>
                    <input id="order-title" name="title" value="{{ old('title', $order->title) }}" maxlength="255" required class="ui-input" placeholder="Contoh: Publikasi jurnal manajemen" @error('title') aria-invalid="true" @enderror>
                    @error('title')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field svc-span-2">
                    <label class="ui-label" for="order-description">Detail &amp; instruksi <span class="ui-req">*</span></label>
                    <textarea id="order-description" name="description" rows="5" maxlength="5000" required class="ui-textarea" @error('description') aria-invalid="true" @enderror>{{ old('description', $order->description) }}</textarea>
                    @error('description')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="order-deadline">Deadline <span class="ui-req">*</span></label>
                    <input id="order-deadline" type="date" name="deadline" value="{{ old('deadline', $order->deadline?->toDateString()) }}" required class="ui-input" @error('deadline') aria-invalid="true" @enderror>
                    @error('deadline')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="order-budget">Budget klien <small>opsional</small></label>
                    <input id="order-budget" name="budget" value="{{ old('budget', $order->budget) }}" maxlength="50" class="ui-input" placeholder="Contoh: Rp1–2 juta">
                    @error('budget')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field svc-span-2">
                    <label class="ui-label" for="order-attachment">Lampiran <small>{{ $order->attachment_path ? 'unggah untuk mengganti lampiran lama' : 'opsional' }}</small></label>
                    <input id="order-attachment" type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="ui-input ord-file">
                    <p class="ui-help">PDF, DOC, DOCX, JPG, PNG · maksimal 10 MB.</p>
                    @error('attachment')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- Client --}}
        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Klien</h2><p>Nomor WhatsApp dipakai klien untuk membuka halaman tracking.</p></div></div>
            <div class="ui-card-body ord-grid">
                <div class="ui-field">
                    <label class="ui-label" for="client-name">Nama <span class="ui-req">*</span></label>
                    <input id="client-name" name="client_name" value="{{ old('client_name', $order->client_name) }}" maxlength="100" required class="ui-input" @error('client_name') aria-invalid="true" @enderror>
                    @error('client_name')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="client-phone">WhatsApp <span class="ui-req">*</span></label>
                    <input id="client-phone" type="tel" name="client_phone" value="{{ old('client_phone', $order->client_phone) }}" maxlength="22" required class="ui-input" placeholder="081234567890" @error('client_phone') aria-invalid="true" @enderror>
                    @error('client_phone')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="client-email">Email <small>opsional</small></label>
                    <input id="client-email" type="email" name="client_email" value="{{ old('client_email', $order->client_email) }}" maxlength="100" class="ui-input" @error('client_email') aria-invalid="true" @enderror>
                    @error('client_email')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="order-source">Sumber order <span class="ui-req">*</span></label>
                    <select id="order-source" name="source" class="ui-select" required>
                        @foreach($sources as $key => $label)
                            <option value="{{ $key }}" @selected(old('source', $order->source ?? 'whatsapp') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('source')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>
    </div>

    <aside class="ui-stack svc-aside">
        {{-- Estimate --}}
        <section class="ui-card ui-card-navy">
            <div class="ui-card-body">
                <span class="svc-eyebrow">ESTIMASI MULAI DARI</span>
                <strong class="svc-catalog-price" x-text="estimate ? rupiah(estimate.total) : '—'"></strong>
                <div class="ord-lines" x-show="estimate && estimate.lines.length > 1">
                    <template x-for="(line, i) in (estimate ? estimate.lines : [])" :key="i">
                        <div><span x-text="line.detail || line.label"></span><b x-text="(i ? '+' : '') + rupiah(line.amount)"></b></div>
                    </template>
                </div>
                <p class="ui-muted" style="font-size:12px;margin-top:8px" x-show="estimate?.pending">Lengkapi pilihan harga dasar untuk estimasi yang akurat.</p>
                @if($editing)
                    <div class="ord-compare" x-show="estimateChanged" x-cloak>
                        <span>Sebelumnya</span><s x-text="rupiah(oldEstimate)"></s>
                    </div>
                @endif
                <p class="ui-muted" style="font-size:11px;margin-top:10px">Estimasi bukan harga final. Dihitung ulang oleh sistem saat disimpan.</p>
            </div>
        </section>

        @if($editing)
            {{-- Final price awareness while editing --}}
            @if($order->price_final !== null)
                <section class="ui-card">
                    <div class="ui-card-body">
                        <p class="ui-label">Harga final saat ini</p>
                        <p class="ord-final">@rupiah($order->price_final)</p>
                        <div class="ui-alert ui-alert-warning" x-show="estimateChanged" x-cloak style="margin-top:10px">
                            <x-admin-icon name="alert"/>
                            <div>
                                <p>Estimasi berubah. Harga final tidak ikut berubah otomatis.</p>
                                <label class="ui-check" style="margin-top:8px"><input type="checkbox" name="reset_price_final" value="1" @checked(old('reset_price_final'))> Kosongkan harga final untuk disepakati ulang</label>
                            </div>
                        </div>
                        <p class="ui-help" style="margin-top:8px">Ubah harga final dan status di halaman detail order.</p>
                    </div>
                </section>
            @endif
        @else
            {{-- Status & final price at creation --}}
            <section class="ui-card">
                <div class="ui-card-head"><div><h2>Status &amp; harga</h2><p>Isi harga final jika sudah disepakati.</p></div></div>
                <div class="ui-card-body">
                    <div class="ui-field">
                        <label class="ui-label" for="order-status">Status awal</label>
                        <select id="order-status" name="status" class="ui-select" x-model="status">
                            @foreach($statuses as $key => $meta)
                                <option value="{{ $key }}">{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="ui-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="ui-field">
                        <label class="ui-label" for="order-price">Harga final (Rp) <small>opsional</small></label>
                        <input id="order-price" type="number" name="price_final" min="0" step="0.01" class="ui-input" x-model="priceInput" placeholder="Belum disepakati" @error('price_final') aria-invalid="true" @enderror>
                        <button type="button" class="ui-link" style="justify-self:start" @click="useEstimate()" x-show="estimate && !estimate.pending">Pakai estimasi <span x-text="estimate ? rupiah(estimate.total) : ''"></span></button>
                        <p class="ui-error" x-show="needsPrice" x-cloak>Order yang langsung dikerjakan wajib punya harga final.</p>
                        @error('price_final')<p class="ui-error">{{ $message }}</p>@enderror
                    </div>
                    @if(\App\Support\NotificationSettings::whatsAppReady() && \App\Support\NotificationSettings::enabled('notify_client_created'))
                        <label class="ui-check" style="margin-top:14px"><input type="checkbox" name="notify_client" value="1" @checked(old('notify_client', true))> Kirim kode tracking ke WhatsApp klien</label>
                    @endif
                </div>
            </section>
        @endif

        <section class="ui-card">
            <div class="ui-card-body">
                <div class="ui-field">
                    <label class="ui-label" for="admin-notes">Pesan untuk klien <small>tampil di tracking</small></label>
                    <textarea id="admin-notes" name="admin_notes" rows="3" maxlength="2000" class="ui-textarea" style="min-height:80px">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    @error('admin_notes')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="svc-save">
            <button type="submit" class="ui-btn ui-btn-gold ui-btn-block" :disabled="submitting"><x-admin-icon name="check"/><span x-text="submitting ? 'Menyimpan…' : @js($editing ? 'Simpan perubahan' : 'Simpan order')"></span></button>
            @if($editing)<p class="ui-help">Semua perubahan tercatat di riwayat order.</p>@endif
        </div>
    </aside>
</form>
@endsection
