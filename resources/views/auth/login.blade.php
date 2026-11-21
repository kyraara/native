@extends('layouts.auth')
@section('title', 'Masuk')

@section('content')
    <h1>Selamat datang kembali</h1>
    <p>Masuk untuk mengelola order, katalog, dan laporan NativeCuy.</p>

    @if(session('status'))
        <div class="ui-alert ui-alert-success" role="status"><x-admin-icon name="check"/><span>{{ session('status') }}</span></div>
    @endif

    <form method="POST" action="{{ route('login') }}" x-data="{ show: false, submitting: false }" @submit="submitting = true" novalidate>
        @csrf

        <div class="ui-field">
            <label for="email" class="ui-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="ui-input" required autofocus autocomplete="username" placeholder="admin@nativecuy.id" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="ui-error">{{ $message }}</p>@enderror
        </div>

        <div class="ui-field">
            <label for="password" class="ui-label">Password</label>
            <div class="ui-input-group">
                <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" class="ui-input" required autocomplete="current-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" @click="show = !show" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'" :aria-pressed="show.toString()" aria-label="Tampilkan password">
                    <span x-show="!show"><x-admin-icon name="eye"/></span>
                    <span x-show="show" x-cloak><x-admin-icon name="eye-off"/></span>
                </button>
            </div>
            @error('password')<p id="password-error" class="ui-error">{{ $message }}</p>@enderror
        </div>

        <div class="auth-row">
            <label class="ui-check" for="remember_me"><input id="remember_me" type="checkbox" name="remember" @checked(old('remember'))> Ingat saya</label>
            <a class="ui-link" href="{{ route('password.request') }}">Lupa password?</a>
        </div>

        <button type="submit" class="ui-btn ui-btn-primary ui-btn-block" :disabled="submitting">
            <span x-text="submitting ? 'Memeriksa…' : 'Masuk'">Masuk</span><x-admin-icon name="arrow"/>
        </button>
    </form>
@endsection
