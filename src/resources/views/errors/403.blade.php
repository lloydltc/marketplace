<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — Access Denied — SalmaDrive</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-[#1A1A24] flex flex-col items-center justify-center px-4 text-center">

    <img src="{{ asset('logo/logo_white_trans.png') }}" alt="SalmaDrive" class="h-10 w-auto mb-8 opacity-80">

    <div class="text-[#F0A820] text-7xl font-bold mb-4 leading-none">403</div>
    <h1 class="text-2xl font-semibold text-white mb-2">Access Denied</h1>
    <p class="text-neutral-400 text-sm max-w-sm mb-8">
        {{ $exception->getMessage() ?: "You don't have permission to access this area. Contact your administrator if you believe this is a mistake." }}
    </p>

    <div class="flex items-center gap-4">
        <a href="{{ url('/') }}"
           class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-5 py-2.5 rounded-lg text-sm transition-colors">
            Go to homepage
        </a>
        @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="border border-neutral-600 hover:border-neutral-400 text-neutral-400 hover:text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-colors">
                    Sign out
                </button>
            </form>
        @endauth
    </div>

</body>
</html>
