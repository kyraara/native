<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — NativeCuy</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-hero-pattern flex items-center justify-center p-4">
    <div class="w-full max-w-md">

        {{-- Logo --}}
        <div class="text-center mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="NativeCuy" class="h-16 mx-auto mb-3">
            <p class="text-gray-400 text-sm">Admin Panel Login</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <h1 class="font-display text-2xl font-extrabold text-navy-dark mb-6 text-center">Selamat Datang</h1>

            @if(session('status'))
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-xl p-3 mb-5 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-semibold text-navy-dark mb-1.5">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username"
                           class="w-full border {{ $errors->has('email') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold transition">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-navy-dark mb-1.5">Password</label>
                    <input id="password" type="password" name="password"
                           required autocomplete="current-password"
                           class="w-full border {{ $errors->has('password') ? 'border-red-400' : 'border-gray-200' }} rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-gold/50 focus:border-gold transition">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="w-4 h-4 rounded border-gray-300 text-navy focus:ring-gold">
                    <label for="remember_me" class="text-sm text-gray-600">Ingat saya</label>
                </div>

                <button type="submit"
                        class="btn-gold w-full py-3.5 rounded-xl text-navy-dark font-extrabold text-sm">
                    <i class="fas fa-sign-in-alt mr-2"></i> Masuk
                </button>
            </form>
        </div>

        <div class="text-center mt-6">
            <a href="{{ route('home') }}" class="text-gray-400 hover:text-white text-sm transition-colors">
                <i class="fas fa-arrow-left mr-1"></i> Kembali ke Website
            </a>
        </div>
    </div>
</body>
</html>
