<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'SalmaDrive') }} — SalmaDrive</title>

    {{-- SEO + social (P9). Pages may override via $metaDescription / $ogImage / $head. --}}
    @php $metaDesc = $metaDescription ?? "SalmaDrive — Zimbabwe's automotive marketplace for vehicles and parts. Find It. Buy It. Drive It."; @endphp
    <meta name="description" content="{{ $metaDesc }}">
    <meta property="og:title" content="{{ $title ?? 'SalmaDrive' }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="SalmaDrive">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('logo/logo_white_trans.png') }}">
    <meta name="twitter:card" content="summary_large_image">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>[x-cloak]{display:none!important}</style>
    {{ $head ?? '' }}
</head>
<body class="h-full bg-neutral-50 font-sans antialiased">

@inject('nav', 'App\Support\Navigation')
@inject('cart', 'App\Modules\Cart\Services\CartService')
@php
    $navUser   = auth()->user();
    $canShop   = $nav->canShop($navUser);
    $roleLinks = $navUser ? $nav->for($navUser) : [];
@endphp

{{-- Top navigation (sticky — stays put on scroll) --}}
<nav x-data="{ mobileOpen: false }" class="bg-[#1A1A24] border-b border-[#080810] sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <img src="{{ asset('logo/logo_white_trans.png') }}" alt="SalmaDrive" class="h-8 w-auto">
                <span class="text-white font-semibold text-lg hidden sm:block">SalmaDrive</span>
            </a>

            {{-- Desktop nav --}}
            <div class="hidden sm:flex items-center gap-6">
                @if ($canShop)
                    <a href="{{ route('products.index') }}" class="text-neutral-300 hover:text-white text-sm font-medium transition-colors">Shop</a>
                    <a href="{{ route('vehicles.index') }}" class="text-neutral-300 hover:text-white text-sm font-medium transition-colors">Vehicles</a>
                @endif
                @foreach ($roleLinks as $item)
                    <a href="{{ $item['url'] }}" class="text-neutral-300 hover:text-white text-sm font-medium transition-colors">{{ $item['label'] }}</a>
                @endforeach
                @if ($canShop)
                    <a href="{{ route('cart.index') }}" class="relative text-neutral-300 hover:text-white text-sm font-medium transition-colors">
                        Cart
                        @if ($cart->count() > 0)
                            <span class="absolute -top-2 -right-3 bg-[#F0A820] text-[#1A1A24] text-xs font-bold rounded-full px-1.5 py-0.5 leading-none tabular-nums">{{ $cart->count() }}</span>
                        @endif
                    </a>
                @endif

                @auth
                    <span class="text-neutral-400 text-sm hidden md:block">
                        {{ Auth::user()->name }}
                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-[#F0A820]/20 text-[#F0A820]">{{ str_replace('_', ' ', Auth::user()->role) }}</span>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-neutral-300 hover:text-white text-sm font-medium transition-colors">Sign out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-neutral-300 hover:text-white text-sm font-medium transition-colors">Sign in</a>
                    <a href="{{ route('register') }}" class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] text-sm font-semibold px-4 py-2 rounded-lg transition-colors">Register</a>
                @endauth
            </div>

            {{-- Mobile: cart + hamburger --}}
            <div class="flex items-center gap-4 sm:hidden">
                @if ($canShop)
                    <a href="{{ route('cart.index') }}" class="relative text-neutral-300 hover:text-white text-sm font-medium" aria-label="Cart">
                        Cart
                        @if ($cart->count() > 0)
                            <span class="absolute -top-2 -right-3 bg-[#F0A820] text-[#1A1A24] text-xs font-bold rounded-full px-1.5 py-0.5 leading-none tabular-nums">{{ $cart->count() }}</span>
                        @endif
                    </a>
                @endif
                <button type="button" @click="mobileOpen = !mobileOpen"
                        class="text-neutral-300 hover:text-white focus:outline-none"
                        aria-label="Toggle navigation menu" :aria-expanded="mobileOpen.toString()">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path x-show="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        <path x-show="mobileOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile drawer --}}
    <div x-show="mobileOpen" x-cloak x-transition class="sm:hidden border-t border-[#080810] px-4 py-3 space-y-1">
        @if ($canShop)
            <a href="{{ route('products.index') }}" class="block text-neutral-300 hover:text-white text-sm font-medium py-2">Shop</a>
            <a href="{{ route('vehicles.index') }}" class="block text-neutral-300 hover:text-white text-sm font-medium py-2">Vehicles</a>
        @endif
        @foreach ($roleLinks as $item)
            <a href="{{ $item['url'] }}" class="block text-neutral-300 hover:text-white text-sm font-medium py-2">{{ $item['label'] }}</a>
        @endforeach

        @auth
            <div class="pt-2 mt-2 border-t border-[#080810] flex items-center justify-between">
                <span class="text-neutral-400 text-sm">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-neutral-300 hover:text-white text-sm font-medium">Sign out</button>
                </form>
            </div>
        @else
            <div class="pt-2 mt-2 border-t border-[#080810] flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-neutral-300 hover:text-white text-sm font-medium py-2">Sign in</a>
                <a href="{{ route('register') }}" class="bg-[#F0A820] text-[#1A1A24] text-sm font-semibold px-4 py-2 rounded-lg">Register</a>
            </div>
        @endauth
    </div>
</nav>

{{-- Flash feedback (success + error) --}}
@if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" x-transition
         class="bg-[#2EBD7A] text-white text-sm px-4 py-3 text-center" role="status">
        {{ session('status') }}
    </div>
@endif
@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)" x-transition
         class="bg-red-600 text-white text-sm px-4 py-3 text-center" role="alert">
        {{ session('error') }}
    </div>
@endif
@if ($errors->any() && ! $errors->has('email') && ! $errors->has('password'))
    <div x-data="{ show: true }" x-show="show" x-transition
         class="bg-red-50 border-b border-red-200 text-red-700 text-sm px-4 py-3 text-center" role="alert">
        {{ $errors->first() }}
    </div>
@endif

<main>
    {{ $slot }}
</main>

{{-- Footer (P9) --}}
<footer class="bg-[#1A1A24] text-neutral-400 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-8">
            <div class="max-w-xs">
                <img src="{{ asset('logo/logo_white_trans.png') }}" alt="SalmaDrive" class="h-8 w-auto mb-3">
                <p class="text-sm text-neutral-500">Find It. Buy It. Drive It. Zimbabwe's automotive marketplace for vehicles and parts.</p>
            </div>
            <div class="grid grid-cols-2 gap-x-12 gap-y-2 text-sm">
                <a href="{{ route('pages.how-fbs-works') }}" class="hover:text-white transition-colors">How FBS works</a>
                <a href="{{ route('pages.rfq-guide') }}" class="hover:text-white transition-colors">Request a part</a>
                <a href="{{ route('pages.fees') }}" class="hover:text-white transition-colors">Fees</a>
                <a href="{{ route('pages.cod-policy') }}" class="hover:text-white transition-colors">COD policy</a>
                <a href="{{ route('pages.terms') }}" class="hover:text-white transition-colors">Terms</a>
                <a href="{{ route('pages.privacy') }}" class="hover:text-white transition-colors">Privacy</a>
            </div>
        </div>
        <div class="mt-8 pt-6 border-t border-[#080810] text-xs text-neutral-600">
            &copy; {{ date('Y') }} SalmaDrive · Salma Technology. All rights reserved.
        </div>
    </div>
</footer>

@livewireScripts
</body>
</html>
