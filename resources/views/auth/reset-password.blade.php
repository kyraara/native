@extends('layouts.auth')
@section('title', 'Buat password baru')

@section('content')
    <h1>Buat password baru</h1>
    <p>Gunakan password yang kuat dan belum pernah dipakai di layanan lain.</p>

    <form method="POST" action="{{ route('password.store') }}" x-data="{ show: false, submitting: false }" @submit="submitting = true" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="ui-field">
            <label for="email" class="ui-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" class="ui-input" required autocomplete="username" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="ui-error">{{ $message }}</p>@enderror
        </div>

        <div class="ui-field">
            <label for="password" class="ui-label">Password baru</label>
            <div class="ui-input-group">
                <input id="password" :type="show ? 'text' : 'password'" type="password" name="password" class="ui-input" required autofocus autocomplete="new-password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" @click="show = !show" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'" aria-label="Tampilkan password">
                    <span x-show="!show"><x-admin-icon name="eye"/></span>
                    <span x-show="show" x-cloak><x-admin-icon name="eye-off"/></span>
                </button>
            </div>
            @error('password')<p id="password-error" class="ui-error">{{ $message }}</p>@enderror
        </div>

        <div class="ui-field">
            <label for="password_confirmation" class="ui-label">Ulangi password baru</label>
            <input id="password_confirmation" :type="show ? 'text' : 'password'" type="password" name="password_confirmation" class="ui-input" required autocomplete="new-password" @error('password_confirmation') aria-invalid="true" @enderror>
            @error('password_confirmation')<p class="ui-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="ui-btn ui-btn-primary ui-btn-block" style="margin-top:22px" :disabled="submitting">
            <span x-text="submitting ? 'Menyimpan…' : 'Simpan password'">Simpan password</span><x-admin-icon name="lock"/>
        </button>
    </form>
@endsection

@section('foot')
    <a class="auth-back" href="{{ route('login') }}"><x-admin-icon name="arrow"/> Kembali ke halaman masuk</a>
@endsection
