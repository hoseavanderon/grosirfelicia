<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#09090b">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">

    <title>{{ config('app.name') }}</title>

    {{-- Tailwind --}}
    <script>
        tailwind.config = {
            darkMode: 'class'
        };
    </script>
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine --}}
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect/dist/cdn.min.js"></script>

    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>

<body x-data="appLayout" class="h-full bg-zinc-100 dark:bg-zinc-950">

    <div class="app-shell">

        @include('partials.sidebar')

        <div class="app-main">

            @include('partials.header')

            <main class="app-content">

                @yield('content')

            </main>

        </div>

    </div>

    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/pwa-register.js') }}"></script>

    @stack('scripts')

    <x-ui.toast />
</body>

</html>
