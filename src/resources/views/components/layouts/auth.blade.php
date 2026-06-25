<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Sign In' }} — SalmaDrive</title>

    {{-- No-FOUC theme resolve — runs before paint (design system §2.4) --}}
    <script>
        (function () {
            const stored = localStorage.getItem('theme');
            const sysDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', stored ? stored === 'dark' : sysDark);
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="min-h-full bg-[#1A1A24] flex flex-col items-center justify-center py-12 px-4 sm:px-6">

    {{-- Logo --}}
    <a href="/" class="mb-8 flex flex-col items-center gap-2">
        <img src="{{ asset('logo/logo_white_trans.png') }}" alt="SalmaDrive" class="h-12 w-auto">
        <span class="text-[#C8CDD6] text-xs tracking-[0.2em] uppercase">Find It. Buy It. Drive It.</span>
    </a>

    {{-- Card --}}
    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">
        {{-- Amber accent bar --}}
        <div class="h-1 bg-[#F0A820]"></div>

        <div class="px-8 py-8">
            {{ $slot }}
        </div>
    </div>

    {{-- Footer link --}}
    @isset($footer)
        <div class="mt-6 text-center text-sm text-neutral-500">
            {{ $footer }}
        </div>
    @endisset

</body>
</html>
