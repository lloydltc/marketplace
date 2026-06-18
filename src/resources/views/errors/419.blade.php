<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>419 — Page Expired — SalmaDrive</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-full bg-[#1A1A24] flex flex-col items-center justify-center px-4 text-center">

    <img src="{{ asset('logo/logo_white_trans.png') }}" alt="SalmaDrive" class="h-10 w-auto mb-8 opacity-80">

    <div class="text-[#F0A820] text-7xl font-bold mb-4 leading-none">419</div>
    <h1 class="text-2xl font-semibold text-white mb-2">Page expired</h1>
    <p class="text-neutral-400 text-sm max-w-sm mb-8">
        Your session timed out for security. Please go back and try again — your sign-in is still safe.
    </p>

    <a href="{{ url()->previous() }}"
       class="bg-[#F0A820] hover:bg-[#F0A820]/90 text-[#1A1A24] font-semibold px-5 py-2.5 rounded-lg text-sm transition-colors">
        Go back and retry
    </a>

</body>
</html>
