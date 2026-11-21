@extends('layouts.admin')

@section('title', $tab === 'majors' ? 'Jurusan' : 'Layanan & harga')
@section('page-title', 'Katalog')
@section('page-subtitle', 'Atur layanan, field order & harga tiap pilihan, serta jurusan.')
@section('page-actions')
    @if($tab === 'services')
        <a href="{{ route('admin.services.create') }}" class="ui-btn ui-btn-gold"><x-admin-icon name="plus"/>Tambah layanan</a>
    @endif
@endsection

@section('content')
<div x-data="{ createMajor: {{ $errors->any() && old('_form') === 'major-create' ? 'true' : 'false' }} }">
    <section class="ui-card">
        <div class="ui-card-head">
            <nav class="catalog-tabs" aria-label="Bagian katalog">
                <a href="{{ route('admin.catalog.index', ['tab' => 'services']) }}" @if($tab === 'services') aria-current="page" @endif>Layanan ({{ $services->count() }})</a>
                <a href="{{ route('admin.catalog.index', ['tab' => 'majors']) }}" @if($tab === 'majors') aria-current="page" @endif>Jurusan ({{ $majors->count() }})</a>
            </nav>
            @if($tab === 'majors')
                <button type="button" @click="createMajor = !createMajor" :aria-expanded="createMajor" class="ui-btn ui-btn-primary"><x-admin-icon name="plus"/>Tambah jurusan</button>
            @endif
        </div>

        @if($tab === 'services')
            <div class="svc-list">
                @forelse($services as $service)
                    @php($fieldCount = count($service->form_fields ?? []))
                    <article @class(['svc-card', 'is-inactive' => ! $service->is_active])>
                        <div class="svc-card-top">
                            <span class="svc-card-icon"><x-service-icon :icon="$service->icon_class"/></span>
                            <div style="min-width:0;flex:1">
                                <div class="ui-row" style="gap:8px"><h3>{{ $service->name }}</h3>
                                    @if($service->is_active)<span class="ui-status ui-status-done" style="padding:2px 8px;font-size:11px">Aktif</span>@else<span class="ui-status" style="padding:2px 8px;font-size:11px">Nonaktif</span>@endif
                                </div>
                                <p>{{ $service->description }}</p>
                            </div>
                        </div>
                        <div class="svc-card-stats">
                            <div class="is-price"><span>Harga katalog</span><strong>@rupiah($service->display_price)</strong></div>
                            <div><span>Field order</span><strong>{{ $fieldCount ?: 'Umum' }}</strong></div>
                            <div><span>Order</span><strong>{{ $service->orders_count }}</strong></div>
                        </div>
                        <p class="ui-help" style="margin:-4px 0 12px">{{ $service->majors_count ? $service->majors->pluck('name')->join(', ') : 'Semua jurusan' }}</p>
                        <div class="svc-card-actions">
                            <a href="{{ route('admin.services.edit', $service) }}" class="ui-btn ui-btn-primary ui-btn-sm"><x-admin-icon name="edit"/>Edit field &amp; harga</a>
                            <form method="POST" action="{{ route('admin.services.duplicate', $service) }}">@csrf<button class="ui-btn ui-btn-ghost ui-btn-sm" title="Duplikat layanan"><x-admin-icon name="copy"/>Duplikat</button></form>
                        </div>
                    </article>
                @empty
                    <div class="ui-empty" style="grid-column:1/-1"><strong>Belum ada layanan</strong>Tambahkan layanan pertama, bisa mulai dari template.</div>
                @endforelse
            </div>
        @else
            <div class="mjr-create" x-show="createMajor" x-collapse x-cloak>
                <form method="POST" action="{{ route('admin.majors.store') }}" class="ui-stack">
                    @csrf
                    <input type="hidden" name="_form" value="major-create">
                    <div class="set-fields">
                        <div class="ui-field"><label class="ui-label" for="major-name">Nama jurusan <span class="ui-req">*</span></label><input id="major-name" name="name" value="{{ old('_form') === 'major-create' ? old('name') : '' }}" required maxlength="120" class="ui-input" placeholder="Contoh: Teknik Informatika"></div>
                        <div class="ui-field"><label class="ui-label" for="major-icon">Ikon <small>Font Awesome</small></label><input id="major-icon" name="icon_class" value="fas fa-graduation-cap" maxlength="80" class="ui-input"></div>
                        <div class="ui-field set-wide"><label class="ui-label" for="major-desc">Deskripsi singkat <small>opsional</small></label><input id="major-desc" name="description" maxlength="1000" class="ui-input" placeholder="Fokus atau karakteristik jurusan ini"></div>
                        <div class="ui-field"><label class="ui-label" for="major-sort">Urutan tampil</label><input id="major-sort" type="number" name="sort_order" value="{{ $majors->count() + 1 }}" min="0" class="ui-input"></div>
                        <div class="ui-field" style="align-content:end"><label class="ui-check"><input type="checkbox" name="is_active" value="1" checked> Aktif</label></div>
                        <fieldset class="ui-field set-wide" style="border:0;padding:0;margin:0"><legend class="ui-label" style="margin-bottom:7px">Layanan untuk jurusan ini</legend>
                            <div class="svc-chip-list">@foreach($services as $service)<label class="svc-chip"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}"><span>{{ $service->name }}</span></label>@endforeach</div>
                        </fieldset>
                    </div>
                    <div class="set-save"><button type="button" class="ui-btn ui-btn-ghost" @click="createMajor = false">Batal</button><button class="ui-btn ui-btn-gold"><x-admin-icon name="check"/>Simpan jurusan</button></div>
                </form>
            </div>

            <div class="mjr-grid">
                @forelse($majors as $major)
                    <article class="mjr-card" x-data="{ editing: false }">
                        <div class="mjr-head">
                            <span class="mjr-icon"><i class="{{ $major->icon_class }}" aria-hidden="true"></i></span>
                            <div>
                                <div class="ui-row" style="gap:8px"><h3>{{ $major->name }}</h3>@if(! $major->is_active)<span class="ui-status" style="padding:2px 8px;font-size:11px">Nonaktif</span>@endif</div>
                                <p>{{ $major->description ?: 'Belum ada deskripsi.' }}</p>
                            </div>
                            <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="editing = !editing" :aria-expanded="editing" aria-label="Edit jurusan {{ $major->name }}"><x-admin-icon name="edit"/></button>
                        </div>
                        <div class="mjr-chips">@forelse($major->services as $service)<span>{{ $service->name }}</span>@empty<span style="background:#fff6e2;color:#87600A">Belum memiliki layanan</span>@endforelse</div>
                        <p class="ui-help">{{ $major->services_count }} layanan · urutan #{{ $major->sort_order }}</p>

                        <div class="mjr-form" x-show="editing" x-collapse x-cloak>
                            <form method="POST" action="{{ route('admin.majors.update', $major) }}" class="ui-stack" style="gap:12px">
                                @csrf @method('PUT')
                                <div class="ui-field"><label class="ui-label">Nama</label><input name="name" value="{{ $major->name }}" required maxlength="120" class="ui-input"></div>
                                <div class="ui-field"><label class="ui-label">Deskripsi</label><input name="description" value="{{ $major->description }}" maxlength="1000" class="ui-input"></div>
                                <div class="set-fields"><div class="ui-field"><label class="ui-label">Ikon</label><input name="icon_class" value="{{ $major->icon_class }}" class="ui-input"></div><div class="ui-field"><label class="ui-label">Urutan</label><input type="number" name="sort_order" value="{{ $major->sort_order }}" min="0" class="ui-input"></div></div>
                                <label class="ui-check"><input type="checkbox" name="is_active" value="1" @checked($major->is_active)> Aktif</label>
                                <div class="svc-chip-list">@foreach($services as $service)<label class="svc-chip"><input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked($major->services->contains($service->id))><span>{{ $service->name }}</span></label>@endforeach</div>
                                <button class="ui-btn ui-btn-primary ui-btn-block"><x-admin-icon name="check"/>Simpan perubahan</button>
                            </form>
                            <form method="POST" action="{{ route('admin.majors.destroy', $major) }}" onsubmit="return confirm(@js('Hapus jurusan '.$major->name.'?'))">@csrf @method('DELETE')<button class="ui-btn ui-btn-danger ui-btn-sm ui-btn-block"><x-admin-icon name="trash"/>Hapus jurusan</button></form>
                        </div>
                    </article>
                @empty
                    <div class="ui-empty" style="grid-column:1/-1"><strong>Belum ada jurusan</strong>Tambahkan jurusan untuk membantu klien menyaring layanan.</div>
                @endforelse
            </div>
        @endif
    </section>
</div>
@endsection
