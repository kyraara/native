@extends('layouts.app')
@section('title', 'Cek Tracking Order — NativeCuy')
@section('content')
@php $trackingToken = old('token', request('token', '')); @endphp
<section class="public-workspace public-search-workspace">
    <div class="public-container public-search-layout">
        <div class="public-search-copy"><h1>Order kamu <br>sudah sampai <span>mana?</span></h1><p>Dari brief pertama sampai hasilnya beres. Lihat progres, harga final, deadline terbaru, dan pesan admin di sini.</p><div class="public-search-mascot"><img src="{{ asset('images/nativecuy_icon_256x256.png') }}" alt="Maskot NativeCuy" width="160" height="160"><span><x-public-icon name="progress"/>Semua update, satu tempat.</span></div></div>
        <div class="public-card public-search-card">
            <span class="public-search-icon"><x-public-icon name="phone-lock"/></span>
            <h2>Buka tracking kamu</h2><p>Siapkan kode order dan 4 digit terakhir nomor WhatsApp yang dipakai saat memesan.</p>
            @if($errors->any())<div class="public-message public-message-error" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('order.trackSearch') }}">
                @csrf
                <div class="public-field"><label for="tracking-token">Kode tracking</label><input id="tracking-token" name="token" type="text" value="{{ is_scalar($trackingToken) ? $trackingToken : '' }}" placeholder="Tempel kode order kamu" minlength="3" maxlength="20" required autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="tracking-token-help" @if($errors->has('token')) aria-invalid="true" @endif><p id="tracking-token-help" class="public-field-help">Kode yang muncul setelah order berhasil dikirim.</p></div>
                <div class="public-field"><label for="tracking-phone">4 digit terakhir WhatsApp</label><input class="public-phone-code" id="tracking-phone" name="phone_last4" type="text" inputmode="numeric" pattern="[0-9]{4}" minlength="4" maxlength="4" placeholder="••••" required autocomplete="off" aria-describedby="tracking-phone-help" @if($errors->has('phone_last4')) aria-invalid="true" @endif><p id="tracking-phone-help" class="public-field-help">Contoh: nomor 0812 3456 <strong>7890</strong> → masukkan <strong>7890</strong>. Verifikasi berlaku 30 menit pada perangkat ini.</p></div>
                <button class="public-button public-button-gold" type="submit">Buka tracking <x-admin-icon name="arrow"/></button>
            </form>
            <div class="public-search-help"><x-public-icon name="headset"/><p>Kode atau nomornya belum ketemu?<br><a href="https://wa.me/{{ $publicContact['wa_number'] }}?text={{ urlencode('Halo NativeCuy, saya butuh bantuan membuka tracking order.') }}" target="_blank" rel="noopener">Minta bantuan admin →</a></p></div><div class="public-search-new">Belum punya order? <a href="{{ route('order.create') }}">Buat order dulu</a></div>
        </div>
    </div>
</section>
@endsection
