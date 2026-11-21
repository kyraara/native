@extends('layouts.auth')
@section('title', 'Lupa password')

@section('content')
    <h1>Lupa password?</h1>
    <p>Masukkan email admin. Kami kirimkan tautan untuk membuat password baru.</p>

    @if(session('status'))
        <div class="ui-alert ui-alert-success" role="status"><x-admin-icon name="mail"/><span>{{ session('status') }}</span></div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" x-data="{ submitting: false }" @submit="submitting = true" novalidate>
        @csrf
        <div class="ui-field">
            <label for="email" class="ui-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" class="ui-input" required autofocus autocomplete="username" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="ui-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="ui-btn ui-btn-primary ui-btn-block" style="margin-top:22px" :disabled="submitting">
            <span x-text="submitting ? 'Mengirim…' : 'Kirim tautan reset'">Kirim tautan reset</span><x-admin-icon name="mail"/>
        </button>
    </form>
@endsection

@section('foot')
    <a class="auth-back" href="{{ route('login') }}"><x-admin-icon name="arrow"/> Kembali ke halaman masuk</a>
@endsection
