@extends('layouts.admin')

@php
    $editing = $service->exists;
@endphp
@section('title', $editing ? 'Edit '.$service->name : 'Layanan baru')
@section('toolbar-title', $editing ? 'Edit layanan' : 'Layanan baru')
@section('page-title', $editing ? $service->name : 'Layanan baru')
@section('page-subtitle', $editing ? 'Atur info layanan, field order, dan harga tiap pilihan.' : ($source ? 'Disalin dari '.$source->name.'. Sesuaikan lalu simpan.' : 'Isi info layanan, lalu susun field dan harganya.'))

@section('content')
@php
    $selectedMajors = collect(old('major_ids', $service->relationLoaded('majors') ? $service->majors->pluck('id')->all() : []))->map(fn ($id) => (int) $id);
    $builderConfig = [
        'fields' => $fields,
        'templates' => $templates,
        'otherServices' => $otherServices,
        'basePrice' => (float) old('base_price', $service->base_price ?? 0),
    ];
@endphp

<div class="svc-topline">
    <a href="{{ route('admin.catalog.index', ['tab' => 'services']) }}" class="ui-link"><x-admin-icon name="arrow" class="svc-back-icon"/> Kembali ke katalog</a>
    @if($editing)
        <div class="ui-row">
            @if($service->is_active)<span class="ui-status ui-status-done"><span class="ui-status-dot"></span>Aktif di website</span>@else<span class="ui-status"><span class="ui-status-dot"></span>Nonaktif</span>@endif
            <span class="ui-muted" style="font-size:12px">{{ $ordersCount }} order memakai layanan ini</span>
        </div>
    @endif
</div>

<form method="POST" action="{{ $editing ? route('admin.services.update', $service) : route('admin.services.store') }}"
      x-data="serviceFormBuilder(@js($builderConfig))" @submit="beforeSubmit()" class="ui-grid-main svc-layout">
    @csrf
    @if($editing) @method('PUT') @endif
    <input type="hidden" name="form_fields" :value="json">

    <div class="ui-stack">
        {{-- Service info --}}
        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Info layanan</h2><p>Tampil di katalog website dan form order.</p></div></div>
            <div class="ui-card-body svc-info-grid">
                <div class="ui-field svc-span-2">
                    <label class="ui-label" for="svc-name">Nama layanan <span class="ui-req">*</span></label>
                    <input id="svc-name" name="name" value="{{ old('name', $service->name) }}" required maxlength="120" class="ui-input" placeholder="Contoh: Publish Jurnal" @error('name') aria-invalid="true" @enderror>
                    @error('name')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field svc-span-2">
                    <label class="ui-label" for="svc-desc">Deskripsi <span class="ui-req">*</span></label>
                    <textarea id="svc-desc" name="description" rows="2" required maxlength="2000" class="ui-textarea" style="min-height:70px" @error('description') aria-invalid="true" @enderror>{{ old('description', $service->description) }}</textarea>
                    @error('description')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="svc-base">Harga cadangan (Rp) <span class="ui-req">*</span></label>
                    <input id="svc-base" type="number" name="base_price" min="0" step="1000" required class="ui-input" x-model.number="basePrice" value="{{ old('base_price', (int) $service->base_price) }}" @error('base_price') aria-invalid="true" @enderror>
                    <p class="ui-help">Dipakai bila tidak ada pilihan "harga dasar" yang terisi.</p>
                    @error('base_price')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="svc-icon">Ikon <small>Font Awesome</small></label>
                    <input id="svc-icon" name="icon_class" value="{{ old('icon_class', $service->icon_class) }}" maxlength="80" class="ui-input" placeholder="fas fa-book-open">
                    @error('icon_class')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="svc-sort">Urutan tampil</label>
                    <input id="svc-sort" type="number" name="sort_order" min="0" max="255" required value="{{ old('sort_order', $service->sort_order ?? 0) }}" class="ui-input">
                </div>
                <div class="ui-field svc-active">
                    <label class="ui-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $service->is_active))> Tampilkan di website</label>
                    <p class="ui-help">{{ $editing ? 'Matikan untuk menyembunyikan tanpa menghapus.' : 'Biarkan nonaktif sampai pratinjau sesuai.' }}</p>
                </div>
                @if($majors->isNotEmpty())
                    <fieldset class="ui-field svc-span-2">
                        <legend class="ui-label" style="margin-bottom:7px">Tersedia untuk jurusan <small>kosongkan = semua jurusan</small></legend>
                        <div class="svc-chip-list">
                            @foreach($majors as $major)
                                <label class="svc-chip"><input type="checkbox" name="major_ids[]" value="{{ $major->id }}" @checked($selectedMajors->contains($major->id))><span>{{ $major->name }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif
            </div>
        </section>

        {{-- Builder --}}
        <section class="ui-card">
            <div class="ui-card-head svc-builder-head">
                <div><h2>Field order &amp; harga</h2><p>Pertanyaan khusus layanan ini dan harga tiap pilihannya.</p></div>
                <div class="ui-row" x-show="fields.length" x-cloak>
                    <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="expandAll(true)">Buka semua</button>
                    <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="expandAll(false)">Tutup semua</button>
                </div>
            </div>

            @error('form_fields')
                <div class="ui-card-body" style="padding-bottom:0">
                    <div class="ui-alert ui-alert-danger" role="alert"><x-admin-icon name="alert"/><div><strong>Field belum bisa disimpan:</strong><ul class="svc-error-list">@foreach($errors->get('form_fields') as $message)<li>{{ $message }}</li>@endforeach</ul></div></div>
                </div>
            @enderror

            <div class="ui-card-body svc-start" x-show="!fields.length">
                <p class="svc-start-title">Mulai dari mana?</p>
                <p class="ui-help" style="margin-bottom:14px">Tanpa field khusus, layanan memakai form umum (judul, detail, deadline, lampiran) dan harga cadangan.</p>
                <div class="svc-template-grid">
                    <template x-for="template in templates" :key="template.key">
                        <button type="button" class="svc-template" @click="useTemplate(template)"><strong x-text="template.name"></strong><span x-text="template.fields.length + ' field'"></span></button>
                    </template>
                    <button type="button" class="svc-template svc-template-blank" @click="addField('radio')"><strong>Kosong</strong><span>Susun sendiri</span></button>
                </div>
            </div>

            <div class="svc-fields" x-show="fields.length" x-cloak>
                <template x-for="(field, index) in fields" :key="field.uid">
                    <article class="svc-field" :class="{ 'is-open': field.open }">
                        <header class="svc-field-head">
                            <button type="button" class="svc-field-toggle" @click="field.open = !field.open" :aria-expanded="field.open">
                                <span class="svc-field-num" x-text="index + 1"></span>
                                <span class="svc-field-title">
                                    <strong x-text="field.label || 'Field tanpa nama'"></strong>
                                    <small x-text="summary(field)"></small>
                                </span>
                                <span class="svc-field-req" x-show="field.required">Wajib</span>
                            </button>
                            <div class="svc-field-actions">
                                <button type="button" @click="move(index, -1)" :disabled="index === 0" aria-label="Pindah ke atas" title="Pindah ke atas">↑</button>
                                <button type="button" @click="move(index, 1)" :disabled="index === fields.length - 1" aria-label="Pindah ke bawah" title="Pindah ke bawah">↓</button>
                                <button type="button" @click="duplicateField(index)" aria-label="Duplikat field" title="Duplikat field"><x-admin-icon name="copy"/></button>
                                <button type="button" class="is-danger" @click="removeField(index)" aria-label="Hapus field" title="Hapus field"><x-admin-icon name="trash"/></button>
                            </div>
                        </header>

                        <div class="svc-field-body" x-show="field.open" x-collapse>
                            <div class="svc-field-grid">
                                <div class="ui-field svc-span-2">
                                    <label class="ui-label" :for="'label-' + field.uid">Pertanyaan / label <span class="ui-req">*</span></label>
                                    <input class="ui-input" :id="'label-' + field.uid" x-model="field.label" @input="labelChanged(field)" maxlength="120" placeholder="Contoh: Target indeksasi">
                                </div>
                                <div class="ui-field">
                                    <label class="ui-label" :for="'type-' + field.uid">Jenis isian</label>
                                    <select class="ui-select" :id="'type-' + field.uid" x-model="field.type" @change="typeChanged(field)">
                                        <template x-for="(label, type) in typeLabels" :key="type"><option :value="type" x-text="label" :selected="field.type === type"></option></template>
                                    </select>
                                </div>
                                <div class="ui-field">
                                    <label class="ui-label" :for="'pricing-' + field.uid">Pengaruh ke harga</label>
                                    <select class="ui-select" :id="'pricing-' + field.uid" x-model="field.pricing" :disabled="pricingFor(field).length === 1">
                                        <template x-for="mode in pricingFor(field)" :key="mode"><option :value="mode" x-text="pricingLabels[mode]" :selected="field.pricing === mode"></option></template>
                                    </select>
                                </div>
                                <div class="ui-field svc-span-2">
                                    <label class="ui-check"><input type="checkbox" x-model="field.required"> Wajib diisi klien</label>
                                </div>
                            </div>

                            {{-- Options --}}
                            <div class="svc-options" x-show="['select', 'radio', 'checkbox'].includes(field.type)">
                                <div class="svc-options-head"><span>Pilihan</span><span x-show="field.pricing !== 'none'">Harga (Rp)</span></div>
                                <template x-for="(option, optionIndex) in field.options" :key="option.uid">
                                    <div class="svc-option">
                                        <input class="ui-input" x-model="option.label" @input="optionLabelChanged(field, option)" maxlength="120" :placeholder="'Pilihan ' + (optionIndex + 1)" :aria-label="'Label pilihan ' + (optionIndex + 1)">
                                        <input class="ui-input svc-price" type="number" min="0" step="1000" x-model="option.price" x-show="field.pricing !== 'none'" :placeholder="field.pricing === 'base' ? 'ikut field lain' : '0'" :aria-label="'Harga pilihan ' + (optionIndex + 1)">
                                        <div class="svc-option-actions">
                                            <button type="button" @click="moveOption(field, optionIndex, -1)" :disabled="optionIndex === 0" aria-label="Naikkan pilihan">↑</button>
                                            <button type="button" @click="moveOption(field, optionIndex, 1)" :disabled="optionIndex === field.options.length - 1" aria-label="Turunkan pilihan">↓</button>
                                            <button type="button" class="is-danger" @click="removeOption(field, optionIndex)" aria-label="Hapus pilihan"><x-admin-icon name="close"/></button>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="addOption(field)"><x-admin-icon name="plus"/>Tambah pilihan</button>
                                <p class="ui-help" x-show="priceHint(field)" x-text="priceHint(field)"></p>
                            </div>

                            {{-- Number settings --}}
                            <div class="svc-field-grid svc-number" x-show="field.type === 'number'">
                                <div class="ui-field" x-show="field.pricing === 'per_unit'"><label class="ui-label">Harga per unit (Rp)</label><input class="ui-input" type="number" min="0" step="500" x-model="field.unit_price" placeholder="5000"></div>
                                <div class="ui-field"><label class="ui-label">Nama unit</label><input class="ui-input" x-model="field.unit_label" maxlength="30" placeholder="slide, halaman, soal"></div>
                                <div class="ui-field"><label class="ui-label">Minimal</label><input class="ui-input" type="number" min="0" x-model="field.min" placeholder="1"></div>
                                <div class="ui-field"><label class="ui-label">Maksimal</label><input class="ui-input" type="number" min="0" x-model="field.max" placeholder="100"></div>
                                <p class="ui-help svc-span-2" x-show="priceHint(field)" x-text="priceHint(field)"></p>
                            </div>

                            {{-- Conditional visibility --}}
                            <div class="svc-condition">
                                <div class="ui-field">
                                    <label class="ui-label" :for="'cond-' + field.uid">Tampilkan field ini</label>
                                    <select class="ui-select" :id="'cond-' + field.uid" :value="field.showIf?.uid || ''" @change="setCondition(field, $event.target.value)">
                                        <option value="">Selalu</option>
                                        <template x-for="source in conditionSources(index)" :key="source.uid"><option :value="source.uid" x-text="'Hanya jika ' + (source.label || 'field tanpa nama') + ' ='" :selected="field.showIf?.uid === source.uid"></option></template>
                                    </select>
                                </div>
                                <div class="svc-chip-list" x-show="sourceOf(field)">
                                    <template x-for="option in (sourceOf(field)?.options || [])" :key="option.uid">
                                        <label class="svc-chip"><input type="checkbox" :checked="field.showIf?.values.includes(option.value)" @change="toggleConditionValue(field, option.value)"><span x-text="option.label || option.value"></span></label>
                                    </template>
                                </div>
                                <p class="ui-error" x-show="conditionProblem(field, index)" x-text="conditionProblem(field, index)"></p>
                            </div>

                            <details class="svc-advanced">
                                <summary>Teks bantuan &amp; pengaturan lanjutan</summary>
                                <div class="svc-field-grid">
                                    <div class="ui-field svc-span-2"><label class="ui-label">Teks bantuan <small>opsional</small></label><input class="ui-input" x-model="field.help" maxlength="200" placeholder="Muncul di bawah field"></div>
                                    <div class="ui-field" x-show="['text', 'textarea', 'number'].includes(field.type)"><label class="ui-label">Placeholder</label><input class="ui-input" x-model="field.placeholder" maxlength="120"></div>
                                    <div class="ui-field"><label class="ui-label">Kode field</label><input class="ui-input ui-mono" :value="field.key" @change="field.key = $event.target.value; keyEdited(field)" maxlength="50"><p class="ui-help">Huruf kecil, angka, garis bawah. Dipakai sistem, tidak tampil ke klien.</p></div>
                                </div>
                            </details>
                        </div>
                    </article>
                </template>
            </div>

            <div class="ui-card-foot svc-builder-foot">
                <div class="ui-row">
                    <span class="ui-muted" style="font-weight:600">Tambah field:</span>
                    <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="addField('radio')"><x-admin-icon name="plus"/>Pilihan</button>
                    <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="addField('checkbox')"><x-admin-icon name="plus"/>Centang</button>
                    <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="addField('number')"><x-admin-icon name="plus"/>Angka</button>
                    <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="addField('text')"><x-admin-icon name="plus"/>Teks</button>
                </div>
                <div class="ui-row">
                    <select class="ui-select svc-copy-select" x-model="copyFrom" aria-label="Salin field dari layanan lain">
                        <option value="">Salin field dari layanan…</option>
                        <template x-for="item in otherServices" :key="item.id"><option :value="item.id" x-text="item.name + ' (' + item.fields.length + ' field)'"></option></template>
                    </select>
                    <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="copyFromService()" :disabled="!copyFrom">Salin</button>
                    <details class="svc-template-menu" x-show="fields.length">
                        <summary class="ui-btn ui-btn-ghost ui-btn-sm">Template</summary>
                        <div>
                            <template x-for="template in templates" :key="template.key"><button type="button" @click="useTemplate(template); $el.closest('details').open = false" x-text="template.name"></button></template>
                            <button type="button" class="is-danger" @click="clearFields(); $el.closest('details').open = false">Kosongkan semua field</button>
                        </div>
                    </details>
                </div>
            </div>
        </section>
    </div>

    {{-- Live preview --}}
    <aside class="ui-stack svc-aside">
        <section class="ui-card ui-card-navy svc-price-card">
            <div class="ui-card-body">
                <span class="svc-eyebrow">HARGA DI KATALOG</span>
                <strong class="svc-catalog-price" x-text="'Mulai ' + rupiah(catalogPrice)"></strong>
                <p class="ui-muted" style="font-size:12px">Otomatis dari kombinasi pilihan termurah.</p>
            </div>
        </section>

        <section class="ui-card svc-preview">
            <div class="ui-card-head"><div><h2>Pratinjau form klien</h2><p>Coba pilih untuk mengecek estimasi.</p></div><button type="button" class="ui-link" @click="resetPreview()">Reset</button></div>
            <div class="ui-card-body" x-effect="syncPreview()">
                <p class="ui-help" x-show="!previewFields.length">Belum ada field khusus. Klien hanya mengisi form umum.</p>
                <template x-for="field in previewFields" :key="field.key">
                    <div class="ui-field svc-preview-field">
                        <span class="ui-label"><span x-text="field.label || 'Field tanpa nama'"></span> <span class="ui-req" x-show="field.required">*</span></span>
                        <template x-if="field.type === 'radio' || field.type === 'checkbox'">
                            <div class="svc-preview-options">
                                <template x-for="option in field.options" :key="option.value">
                                    <label class="ui-check"><input :type="field.type" :name="'preview-' + field.key" :value="option.value" x-model="preview[field.key]"><span x-text="option.label || '—'"></span><small class="svc-preview-price" x-text="optionPrice(field, option)"></small></label>
                                </template>
                            </div>
                        </template>
                        <template x-if="field.type === 'select'">
                            <select class="ui-select" x-model="preview[field.key]"><option value="">Pilih…</option><template x-for="option in field.options" :key="option.value"><option :value="option.value" x-text="option.label + (optionPrice(field, option) ? ' · ' + optionPrice(field, option) : '')"></option></template></select>
                        </template>
                        <template x-if="field.type === 'number'">
                            <input class="ui-input" type="number" x-model="preview[field.key]" :min="field.min ?? 0" :max="field.max" :placeholder="field.min ? 'Minimal ' + field.min : ''">
                        </template>
                        <template x-if="field.type === 'text' || field.type === 'textarea'">
                            <input class="ui-input" type="text" x-model="preview[field.key]" :placeholder="field.placeholder || ''">
                        </template>
                        <p class="ui-help" x-show="field.help || unitPrice(field)" x-text="[unitPrice(field), field.help].filter(Boolean).join(' · ')"></p>
                    </div>
                </template>
            </div>
            <div class="svc-estimate">
                <div class="svc-estimate-lines">
                    <template x-for="(line, i) in previewEstimate.lines" :key="i">
                        <div><span x-text="line.detail ? line.detail : line.label"></span><b x-text="(i ? '+' : '') + rupiah(line.amount)"></b></div>
                    </template>
                </div>
                <div class="svc-estimate-total"><span>Estimasi mulai dari</span><strong x-text="rupiah(previewEstimate.total)"></strong></div>
            </div>
        </section>

        <div class="svc-save">
            <button type="submit" class="ui-btn ui-btn-gold ui-btn-block" :disabled="submitting"><x-admin-icon name="check"/><span x-text="submitting ? 'Menyimpan…' : '{{ $editing ? 'Simpan perubahan' : 'Simpan layanan' }}'"></span></button>
            <p class="ui-help" x-show="dirty" x-cloak>Ada perubahan field yang belum disimpan.</p>
        </div>
    </aside>
</form>

@if($editing)
    <section class="ui-card svc-danger">
        <div class="ui-card-body ui-row">
            <div class="ui-spacer"><strong>Duplikat atau hapus layanan</strong><p class="ui-help">Salinan dibuat nonaktif. Layanan yang sudah punya order tidak bisa dihapus — nonaktifkan saja.</p></div>
            <form method="POST" action="{{ route('admin.services.duplicate', $service) }}">@csrf<button class="ui-btn ui-btn-ghost"><x-admin-icon name="copy"/>Duplikat</button></form>
            <form method="POST" action="{{ route('admin.services.destroy', $service) }}" onsubmit="return confirm(@js('Hapus layanan '.$service->name.'?'))">@csrf @method('DELETE')<button class="ui-btn ui-btn-danger" @disabled($ordersCount > 0)><x-admin-icon name="trash"/>Hapus</button></form>
        </div>
    </section>
@endif
@endsection
