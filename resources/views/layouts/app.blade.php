<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="NativeCuy — Jasa joki tugas, makalah, laporan, PPT, dan web profesional. Cepat, terpercaya, hasil memuaskan!">
    <title>@yield('title', 'NativeCuy — Jasa Joki Tugas Profesional')</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&family=Inter:wght@300;400;500;600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- AOS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('head')

    <style>
        body { font-family: 'Inter', sans-serif; }
        h1, h2, h3, h4 { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-script { font-family: 'Dancing Script', cursive; }

        .clip-diagonal { clip-path: polygon(0 0, 100% 0, 100% 88%, 0 100%); }
        .clip-diagonal-rev { clip-path: polygon(0 12%, 100% 0, 100% 100%, 0 100%); }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0D1F3C; }
        ::-webkit-scrollbar-thumb { background: #F5B914; border-radius: 3px; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-15px) rotate(2deg); }
            66% { transform: translateY(-8px) rotate(-1deg); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delay { animation: float 6s ease-in-out 2s infinite; }
        .animate-float-delay2 { animation: float 6s ease-in-out 4s infinite; }

        .btn-gold {
            background: linear-gradient(135deg, #F5B914, #FDE68A, #F5B914);
            background-size: 200% auto;
            transition: all 0.4s ease;
        }
        .btn-gold:hover { background-position: right center; transform: translateY(-2px); box-shadow: 0 8px 25px rgba(245,185,20,0.4); }

        .btn-navy {
            transition: all 0.3s ease;
        }
        .btn-navy:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(27,58,110,0.4); }

        .card-service {
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-service:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(27,58,110,0.2); }
        .card-service:hover .service-icon { background: #1B3A6E; color: #F5B914; }
    </style>
</head>
<body class="bg-cream text-navy-dark antialiased">

    @include('components.navbar')

    <main>
        @yield('content')
    </main>

    @include('components.footer')

    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 700, once: true, easing: 'ease-out-cubic' });
    </script>

    @stack('scripts')
</body>
</html>
