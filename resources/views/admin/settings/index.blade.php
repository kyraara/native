@extends('layouts.admin')
@section('title', 'Pengaturan')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Kontak, statistik publik, notifikasi, dan testimoni pelanggan.')
@section('content')
@include('admin.settings.nav')
@php($safeOld = fn ($key, $default = '') => is_scalar(old($key, $default)) ? old($key, $default) : '')

<form action="{{ route('admin.settings.update') }}" method="POST" class="ui-stack">
    @csrf @method('PUT')
    <section class="ui-card">
        <div class="ui-card-head"><div><h2>Kontak &amp; tautan</h2><p>Dipakai calon customer untuk menghubungi NativeCuy.</p></div></div>
        <div class="ui-card-body set-fields">
            @foreach([
                ['wa_number', 'Nomor WhatsApp', 'text', '6281234567890', 20, 'Gunakan kode negara, contoh 6281234567890.'],
                ['email', 'Email', 'email', 'order@nativecuy.id', 100, 'Alamat email untuk pertanyaan pelanggan.'],
                ['instagram', 'Username Instagram', 'text', '@nativecuy.id', 100, 'Tulis username, tanpa tautan instagram.com.'],
                ['location', 'Lokasi', 'text', 'Palembang, Sumatera Selatan', 100, 'Ditampilkan pada bagian kontak di footer.'],
            ] as [$key, $label, $type, $placeholder, $max, $help])
                <div class="ui-field">
                    <label for="setting-{{ $key }}" class="ui-label">{{ $label }} @if($key !== 'location')<span class="ui-req">*</span>@else<small>opsional</small>@endif</label>
                    <input id="setting-{{ $key }}" type="{{ $type }}" name="{{ $key }}" class="ui-input" value="{{ $safeOld($key, $settings[$key] ?? '') }}" placeholder="{{ $placeholder }}" maxlength="{{ $max }}" @required($key !== 'location') @error($key) aria-invalid="true" @enderror>
                    <p class="ui-help">{{ $help }}</p>
                    @error($key)<p class="ui-error">{{ $message }}</p>@enderror
                </div>
            @endforeach
        </div>
    </section>

    <section class="ui-card">
        <div class="ui-card-head"><div><h2>Statistik publik</h2><p>Angka yang ditampilkan di halaman utama.</p></div></div>
        <div class="ui-card-body">
            <div class="set-fields">
                @foreach([['total_orders', 'Order selesai', '500+', 20], ['happy_clients', 'Klien puas', '350+', 20], ['subjects', 'Mata kuliah', '50+', 20], ['rating', 'Rating layanan', '4.9', 10]] as [$key, $label, $default, $max])
                    <div class="ui-field">
                        <label for="setting-{{ $key }}" class="ui-label">{{ $label }} <span class="ui-req">*</span></label>
                        <input id="setting-{{ $key }}" type="text" name="{{ $key }}" class="ui-input" value="{{ $safeOld($key, $settings[$key] ?? $default) }}" maxlength="{{ $max }}" required @error($key) aria-invalid="true" @enderror>
                        @error($key)<p class="ui-error">{{ $message }}</p>@enderror
                    </div>
                @endforeach
            </div>
            <div class="ui-alert ui-alert-info" style="margin-top:16px"><x-admin-icon name="book"/><p>Ditampilkan persis seperti yang kamu isi, termasuk desimal dan tanda +. Statistik ini diatur manual, terpisah dari laporan penghasilan dan rating testimoni.</p></div>
        </div>
    </section>

    <div class="set-save">
        <a class="ui-btn ui-btn-ghost" href="{{ route('home') }}" target="_blank" rel="noopener">Lihat website <x-admin-icon name="external"/></a>
        <button type="submit" class="ui-btn ui-btn-gold"><x-admin-icon name="check"/>Simpan pengaturan</button>
    </div>
</form>
@endsection
