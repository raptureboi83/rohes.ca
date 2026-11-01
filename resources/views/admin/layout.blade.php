<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin') — {{ setting('site.short_name', 'ROHES') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="admin-body">
<div class="admin-shell">

    <aside class="admin-side">
        <a href="{{ route('admin.dashboard') }}" class="admin-side__brand">
            @if (setting('site.logo_path'))
                <img src="{{ setting('site.logo_path') }}" alt="">
            @endif
            <span>
                <b>{{ setting('site.short_name', 'ROHES') }}</b>
                <span class="admin-side__tag">Site admin</span>
            </span>
        </a>

        <nav class="admin-nav">
            <span class="admin-nav__label">Overview</span>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">Dashboard</a>

            <span class="admin-nav__label">Your website</span>
            <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') && ! request()->routeIs('admin.pages.sections.*') ? 'is-active' : '' }}">Website pages</a>

            <span class="admin-nav__label">Other updates</span>
            <a href="{{ route('admin.media.index') }}" class="{{ request()->routeIs('admin.media.*') ? 'is-active' : '' }}">Photos and files</a>
            <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'is-active' : '' }}">Business details</a>
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">User accounts</a>
            <a href="{{ route('admin.messages.index') }}" class="{{ request()->routeIs('admin.messages.*') ? 'is-active' : '' }}">
                Enquiries
                @if (($unreadBadge ?? 0) > 0)<span class="badge">{{ $unreadBadge }}</span>@endif
            </a>
        </nav>

        <div class="admin-side__preview">
            <a href="{{ route('home') }}" target="_blank" rel="noopener">Preview website &nearr;</a>
        </div>

        <div class="admin-side__foot">
            <p class="admin-side__identity">Signed in as <b>{{ auth()->user()->name }}</b></p>
            <div class="admin-side__account">
                <a href="{{ route('profile.edit') }}">Your account</a>
                <form method="POST" action="{{ route('logout') }}" class="inline-form">
                    @csrf
                    <button type="submit" style="background:none;border:0;color:var(--green-200);cursor:pointer;font:inherit;font-size:.78rem;padding:0;text-decoration:underline">Sign out</button>
                </form>
            </div>
        </div>
    </aside>

    <main class="admin-main">
        @include('admin.partials.flash')
        @yield('content')
    </main>

</div>
</body>
</html>
