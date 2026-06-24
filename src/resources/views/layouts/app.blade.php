<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'SalmaDrive') }} — SalmaDrive</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-neutral-50 font-sans antialiased">

{{-- Top navigation --}}
<nav class="bg-[#1A1A24] border-b border-[#080810]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('logo/logo_white_trans.png') }}" alt="SalmaDrive" class="h-8 w-auto">
                <span class="text-white font-semibold text-lg hidden sm:block">SalmaDrive</span>
            </a>

            <div class="flex items-center gap-6">
                @auth
                    <span class="text-neutral-400 text-sm hidden md:block">
                        {{ Auth::user()->name }}
                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#F0A820]/20 text-[#F0A820]">
                            {{ str_replace('_', ' ', Auth::user()->role) }}
                        </span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-neutral-300 hover:text-white text-sm font-medium transition-colors">
                            Sign out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-neutral-300 hover:text-white text-sm font-medium transition-colors">Sign in</a>
                    <a href="{{ route('register') }}" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Register</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         class="bg-[#2EBD7A] text-white text-sm px-4 py-3 text-center">
        {{ session('status') }}
    </div>
@endif

<main>
    {{ $slot }}
</main>

@livewireScripts
</body>
</html>
