@extends('layouts.admin')
@section('title', $testimonial->exists ? 'Edit testimoni' : 'Tambah testimoni')
@section('page-title', $testimonial->exists ? 'Edit testimoni' : 'Tambah testimoni')
@section('page-subtitle', 'Tulis ulasan sesuai pengalaman yang disampaikan pelanggan.')
@section('content')
@php($safeOld = fn ($key, $default = '') => is_scalar(old($key, $default)) ? old($key, $default) : '')
@include('admin.settings.nav')

<div class="svc-topline"><a class="ui-link" href="{{ route('admin.testimonials.index') }}"><x-admin-icon name="arrow" class="svc-back-icon"/> Kembali ke testimoni</a></div>

<form method="POST" action="{{ $testimonial->exists ? route('admin.testimonials.update', $testimonial) : route('admin.testimonials.store') }}" class="ui-stack" style="max-width:860px">
    @csrf @if($testimonial->exists) @method('PUT') @endif
    <section class="ui-card">
        <div class="ui-card-head"><div><h2>Detail ulasan</h2><p>Nama dan ulasan terlihat di halaman publik saat testimoni ditampilkan.</p></div></div>
        <div class="ui-card-body set-fields">
            <div class="ui-field">
                <label for="testimonial-name" class="ui-label">Nama pelanggan <span class="ui-req">*</span></label>
                <input id="testimonial-name" name="client_name" class="ui-input" value="{{ $safeOld('client_name', $testimonial->client_name) }}" maxlength="100" required autocomplete="name">
                @error('client_name')<p class="ui-error">{{ $message }}</p>@enderror
            </div>
            <div class="ui-field">
                <label for="testimonial-service" class="ui-label">Layanan <small>opsional</small></label>
                <select id="testimonial-service" name="service_id" class="ui-select">
                    <option value="">Tanpa layanan tertentu</option>
                    @foreach($services as $service)<option value="{{ $service->id }}" @selected(old('service_id', $testimonial->service_id) == $service->id)>{{ $service->name }}{{ $service->is_active ? '' : ' (nonaktif)' }}</option>@endforeach
                </select>
                @error('service_id')<p class="ui-error">{{ $message }}</p>@enderror
            </div>
            <fieldset class="ui-field set-wide" style="border:0;padding:0;margin:0">
                <legend class="ui-label" style="margin-bottom:7px">Rating <span class="ui-req">*</span></legend>
                <div class="tst-rating">@for($rating = 1; $rating <= 5; $rating++)<label><input type="radio" name="rating" value="{{ $rating }}" @checked(old('rating', $testimonial->rating) == $rating) required><span>{{ $rating }} <x-admin-icon name="star"/><span class="sr-only">bintang</span></span></label>@endfor</div>
                @error('rating')<p class="ui-error">{{ $message }}</p>@enderror
            </fieldset>
            <div class="ui-field set-wide">
                <label for="testimonial-comment" class="ui-label">Ulasan pelanggan <span class="ui-req">*</span></label>
                <textarea id="testimonial-comment" name="comment" rows="6" class="ui-textarea" maxlength="1500" required placeholder="Tuliskan ulasan pelanggan di sini.">{{ $safeOld('comment', $testimonial->comment) }}</textarea>
                <p class="ui-help">Maksimal 1.500 karakter. Inisial avatar dibuat otomatis dari nama pelanggan.</p>
                @error('comment')<p class="ui-error">{{ $message }}</p>@enderror
            </div>
            <div class="set-wide">
                <input type="hidden" name="is_featured" value="0">
                <label class="ntf-toggle"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $testimonial->is_featured))><span class="ntf-switch" aria-hidden="true"></span><span><strong>Tampilkan di website</strong><small>Matikan untuk menyimpan testimoni tanpa menampilkannya ke calon customer.</small></span></label>
                @error('is_featured')<p class="ui-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </section>
    <div class="set-save">
        <a class="ui-btn ui-btn-ghost" href="{{ route('admin.testimonials.index') }}">Batal</a>
        <button class="ui-btn ui-btn-gold" type="submit"><x-admin-icon name="check"/>{{ $testimonial->exists ? 'Simpan perubahan' : 'Simpan testimoni' }}</button>
    </div>
</form>
@endsection
