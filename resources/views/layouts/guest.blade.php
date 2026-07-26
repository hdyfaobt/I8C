<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'i8c') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>

    <!-- Tailwind CSS & Alpine.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.1/dist/cdn.min.js"></script>

    <style>
        /* I8C brand colors */
        :root {
            --i8c-orange: #da532c;
            --i8c-navy:   #1e2235;
        }

        /* Decorative circles on the left panel */
        .brand-circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }
    </style>
</head>
<body class="font-sans antialiased" style="background-color: #f1f3f7;">

    {{-- ===== SPLIT-SCREEN WRAPPER ===== --}}
    <div class="min-h-screen flex">

        {{-- ===== LEFT PANEL — i8c branding (hidden on mobile) ===== --}}
        <div class="hidden lg:flex lg:w-1/2 flex-col justify-between relative overflow-hidden p-12"
             style="background-color: #1e2235;">

            {{-- Decorative background circles --}}
            <div class="brand-circle w-96 h-96" style="top: -80px; right: -80px;"></div>
            <div class="brand-circle w-64 h-64" style="bottom: 120px; left: -40px;"></div>
            <div class="brand-circle w-32 h-32" style="bottom: 60px; right: 100px; background: rgba(218,83,44,0.08);"></div>

            {{-- Logo --}}
            <div class="relative z-10">
                <a href="/" class="inline-block">
                    <span style="color: #da532c; font-size: 2.8rem; font-weight: 800; letter-spacing: -2px; line-height: 1;">
                        i<span style="color: white;">8</span>c
                    </span>
                </a>
            </div>

            {{-- Central tagline --}}
            <div class="relative z-10">
                <p class="text-xs font-semibold uppercase tracking-widest mb-4"
                   style="color: #da532c;">Welkom bij i8c</p>
                <h1 class="text-4xl font-bold text-white leading-snug mb-4">
                    Uw partner in<br>
                    <span style="color: #da532c;">systeemintegratie</span>
                </h1>
                <p class="text-gray-400 text-base leading-relaxed max-w-sm">
                    Beheer uw klanten, bestellingen en workflows op één plek — snel, veilig en betrouwbaar.
                </p>
            </div>

            {{-- Bottom footer --}}
            <div class="relative z-10">
                <p class="text-sm text-gray-600">
                    © {{ date('Y') }} i8c — Integratie experts
                </p>
            </div>
        </div>

        {{-- ===== RIGHT PANEL — auth form ===== --}}
        <div class="flex-1 flex flex-col justify-center items-center px-6 py-12 bg-white">

            {{-- Mobile logo (only shows when left panel is hidden) --}}
            <div class="lg:hidden mb-8">
                <a href="/">
                    <span style="color: #da532c; font-size: 2.5rem; font-weight: 800; letter-spacing: -2px; line-height: 1;">
                        i<span style="color: #1e2235;">8</span>c
                    </span>
                </a>
            </div>

            {{-- Form card --}}
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>

    </div>{{-- end split-screen --}}

    <script>
        // Global "Bezig..." feedback — same as layouts/app.blade.php, so the
        // login/wachtwoord-vergeten forms get it too.
        document.addEventListener('submit', (event) => {
            const form = event.target;
            const button = event.submitter || form.querySelector('button[type="submit"]');

            if (! button || button.disabled) {
                return;
            }

            button.disabled = true;

            if (button.children.length === 0) {
                button.textContent = 'Bezig...';
            }
        });
    </script>

</body>
</html>
