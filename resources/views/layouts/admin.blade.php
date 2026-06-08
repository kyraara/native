<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — NativeCuy</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body { font-family: 'Inter', sans-serif; }
        h1,h2,h3,h4 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .sidebar-link { transition: all 0.2s ease; border-left: 3px solid transparent; }
        .sidebar-link:hover { background: rgba(245,185,20,0.12); color: #F5B914; border-left-color: rgba(245,185,20,0.5); }
        .sidebar-link.active { background: rgba(245,185,20,0.18); color: #F5B914; border-left-color: #F5B914; }
    </style>
</head>
<body class="bg-gray-100 antialiased" x-data="adminLayout()">

{{-- Global toast --}}
<div x-show="toast.show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-3"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-3"
     class="fixed bottom-6 right-6 z-50 flex items-center gap-3 px-5 py-3 rounded-2xl shadow-2xl text-sm font-medium"
     :class="toast.type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'"
     style="display: none;">
    <i :class="toast.type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'"></i>
    <span x-text="toast.message"></span>
</div>

<div class="flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-64 bg-navy-dark flex-shrink-0 flex flex-col transition-all duration-300"
           :class="sidebarOpen ? 'flex' : 'hidden md:flex'">

        <div class="p-5 border-b border-navy/50">
            <a href="{{ route('home') }}" class="flex items-center gap-3" target="_blank">
                <img src="{{ asset('images/logo.png') }}" alt="NativeCuy" class="h-10 w-auto">
            </a>
            <div class="flex items-center justify-between mt-2">
                <p class="text-xs text-gray-500">Admin Panel</p>
                <a href="{{ route('home') }}" target="_blank" class="text-gray-600 hover:text-gold text-xs transition-colors">
                    <i class="fas fa-external-link-alt"></i> Lihat Website
                </a>
            </div>
        </div>

        <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
            <p class="text-gray-600 text-xs font-semibold uppercase tracking-wider px-4 py-2 mt-2">Menu</p>

            <a href="{{ route('admin.dashboard') }}"
               class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie w-4 text-center flex-shrink-0"></i>
                Dashboard
            </a>

            <a href="{{ route('admin.orders.index') }}"
               class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 text-sm font-medium {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list w-4 text-center flex-shrink-0"></i>
                <span class="flex-1">Kelola Order</span>
                @php $pendingCount = \App\Models\Order::where('status', 'pending')->count(); @endphp
                @if($pendingCount > 0)
                    <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[1.25rem] text-center">
                        {{ $pendingCount > 99 ? '99+' : $pendingCount }}
                    </span>
                @endif
            </a>

            <a href="{{ route('admin.settings.index') }}"
               class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 text-sm font-medium {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="fas fa-cog w-4 text-center flex-shrink-0"></i>
                <span class="flex-1">Pengaturan</span>
            </a>

            <div class="pt-3 mt-3 border-t border-navy/50">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="sidebar-link w-full flex items-center gap-3 px-4 py-3 rounded-lg text-gray-500 text-sm font-medium text-left hover:text-red-400 hover:bg-red-500/10 hover:border-red-500/30">
                        <i class="fas fa-sign-out-alt w-4 text-center flex-shrink-0"></i>
                        Logout
                    </button>
                </form>
            </div>
        </nav>

        {{-- Admin info --}}
        <div class="p-4 border-t border-navy/50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-navy rounded-full flex items-center justify-center text-gold text-xs font-bold flex-shrink-0">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-xs font-semibold truncate">{{ auth()->user()->name }}</p>
                    <p class="text-gray-500 text-xs truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main area --}}
    <div class="flex-1 flex flex-col overflow-hidden">

        {{-- Topbar --}}
        <header class="bg-white border-b border-gray-200 px-6 py-3.5 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-4">
                <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-500 hover:text-navy transition-colors p-1">
                    <i class="fas fa-bars text-lg"></i>
                </button>
                <div>
                    <h1 class="text-base font-bold text-navy-dark">@yield('page-title', 'Dashboard')</h1>
                    @hasSection('page-subtitle')
                        <p class="text-xs text-gray-400">@yield('page-subtitle')</p>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($pendingCount > 0)
                    <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}"
                       class="flex items-center gap-1.5 bg-red-50 text-red-600 border border-red-200 text-xs font-semibold px-3 py-1.5 rounded-lg hover:bg-red-100 transition-colors">
                        <i class="fas fa-bell animate-pulse"></i>
                        {{ $pendingCount }} order menunggu
                    </a>
                @endif
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="mx-6 mt-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-center gap-3 flex-shrink-0">
                <i class="fas fa-check-circle text-green-500 flex-shrink-0"></i>
                <span class="text-green-700 text-sm">{{ session('success') }}</span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        @endif
        @if(session('error'))
            <div class="mx-6 mt-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 flex items-center gap-3 flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-500 flex-shrink-0"></i>
                <span class="text-red-700 text-sm">{{ session('error') }}</span>
                <button onclick="this.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        @endif

        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
<script>
    function adminLayout() {
        return {
            sidebarOpen: false,
            toast: { show: false, message: '', type: 'success' },
            showToast(message, type = 'success') {
                this.toast = { show: true, message, type };
                setTimeout(() => this.toast.show = false, 3500);
            },
        };
    }

    // Global helper agar bisa dipanggil dari halaman manapun
    window.showAdminToast = (message, type = 'success') => {
        window.dispatchEvent(new CustomEvent('admin-toast', { detail: { message, type } }));
    };

    document.addEventListener('alpine:init', () => {
        Alpine.store('adminToast', {
            show: false, message: '', type: 'success',
            fire(message, type = 'success') {
                this.show = true;
                this.message = message;
                this.type = type;
                setTimeout(() => this.show = false, 3500);
            }
        });
    });
</script>
</body>
</html>
