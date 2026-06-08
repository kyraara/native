@extends('layouts.app')

@section('title', 'Cek Status Order — NativeCuy')

@section('content')
<section class="min-h-screen bg-cream relative overflow-hidden flex items-center pt-20 pb-16">

    {{-- Background --}}
    <div class="absolute top-20 right-0 w-80 h-80 bg-gold/8 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-64 h-64 bg-navy/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 bg-[linear-gradient(rgba(27,58,110,0.03)_1px,transparent_1px),linear-gradient(90deg,rgba(27,58,110,0.03)_1px,transparent_1px)] bg-[size:40px_40px] pointer-events-none"></div>

    <div class="max-w-lg mx-auto px-4 sm:px-6 w-full">

        {{-- Header --}}
        <div class="text-center mb-8 sm:mb-10" data-aos="fade-up">
            <div class="inline-flex items-center justify-center w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-navy/10 border border-navy/20 mb-5">
                <i class="fas fa-search text-navy text-xl sm:text-2xl"></i>
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-extrabold text-navy-dark mb-3">
                Tracking <span class="font-script text-gold">Order</span>
            </h1>
            <p class="text-gray-500 text-sm sm:text-base max-w-sm mx-auto">
                Masukkan kode tracking yang kamu terima setelah order untuk melihat status terkini.
            </p>
        </div>

        {{-- Card form --}}
        <div class="bg-white rounded-3xl shadow-xl border border-gray-100/80 p-6 sm:p-8" data-aos="fade-up" data-aos-delay="80">

            @if($errors->has('token') || session('error'))
                <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-5 flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 flex-shrink-0"></i>
                    <p class="text-red-600 text-sm">{{ $errors->first('token') ?: session('error') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('order.trackSearch') }}" x-data="{ token: '{{ old('token') }}' }">
                @csrf

                <div class="mb-6">
                    <label class="block text-sm font-semibold text-navy-dark mb-2">Kode Tracking</label>
                    <input type="text" name="token"
                           x-model="token"
                           @input="token = token.toUpperCase()"
                           value="{{ old('token') }}"
                           placeholder="Contoh: ABC123DEF"
                           maxlength="20"
                           autocomplete="off"
                           autofocus
                           class="w-full border-2 rounded-2xl px-4 py-4 text-center text-xl font-mono font-bold tracking-widest uppercase text-navy-dark focus:outline-none focus:ring-2 focus:ring-gold/40 focus:border-gold transition-all placeholder:text-gray-300 placeholder:text-base placeholder:tracking-normal placeholder:font-normal"
                           :class="token.length > 0 ? 'border-gold/50 bg-gold/3' : 'border-gray-200'">
                    <p class="text-gray-400 text-xs mt-2 text-center">
                        Contoh format: <span class="font-mono font-bold text-navy">ABC123DEF</span>
                    </p>
                </div>

                <button type="submit"
                        class="btn-gold w-full py-3.5 sm:py-4 rounded-2xl text-navy-dark font-extrabold text-base flex items-center justify-center gap-2 hover:scale-105 transition-transform shadow-lg shadow-gold/20">
                    <i class="fas fa-search"></i>
                    Cek Status Order
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm">
                <p class="text-gray-400 text-xs">Belum punya order?</p>
                <a href="{{ route('order.create') }}"
                   class="inline-flex items-center gap-1.5 text-navy hover:text-gold font-semibold text-sm transition-colors">
                    <i class="fas fa-plus-circle text-gold text-xs"></i>
                    Order Sekarang
                </a>
            </div>
        </div>

        {{-- Help link --}}
        <div class="mt-5 text-center" data-aos="fade-up" data-aos-delay="160">
            <p class="text-gray-400 text-xs">
                Tidak menemukan kode kamu?
                <a href="https://wa.me/{{ \App\Models\Setting::get('wa_number', '6281234567890') }}?text={{ urlencode('Halo NativeCuy, saya tidak menemukan kode tracking order saya') }}"
                   target="_blank"
                   class="text-emerald-600 hover:text-emerald-500 font-semibold transition-colors ml-1">
                    <i class="fab fa-whatsapp"></i> Hubungi Admin
                </a>
            </p>
        </div>

    </div>
</section>
@endsection
