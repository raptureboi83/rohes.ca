<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Sign in') &mdash; {{ setting('site.name', 'Region of Huronia Environmental Services') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Auth screens are a separate shell from the public site: no header, no footer,
    no navigation. They reuse the public stylesheet so the login page matches the
    rest of the brand rather than looking like a different website.
--}}
<body class="auth-body">
    <main class="auth-shell">
        <a href="{{ route('home') }}" class="auth-brand">
            @if (setting('site.logo_path'))
                <img src="{{ setting('site.logo_path') }}" alt="" class="auth-brand__logo">
            @else
                <span class="auth-brand__mark" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(setting('site.short_name', 'ROHES'), 0, 2)) }}</span>
            @endif
            <span class="auth-brand__text">
                <b>{{ setting('site.name', 'Region of Huronia Environmental Services') }}</b>
                <span>{{ setting('site.tagline', 'Site administration') }}</span>
            </span>
        </a>

        <div class="auth-panel">
            @if ($errors->any())
                <div class="flash flash--error" role="alert">
                    <span><b>There is a problem.</b> Please check the highlighted fields.</span>
                </div>
            @endif

            {{-- Error flash lives here so every auth screen shows it identically.
                 Success flash is declared per-screen, because only the password
                 reset screens have a success message. --}}
            @yield('content')
        </div>

        <p class="auth-foot">
            <a href="{{ route('home') }}">&larr; Back to the website</a>
        </p>
    </main>
</body>
</html>
