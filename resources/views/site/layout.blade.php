@php
    // Not every public view has a CMS page behind it (e.g. /contact), so
    // never assume $page exists here.
    $metaPage = $page ?? null;
    $metaTitle = $metaPage?->meta_title ?: setting('site.seo_title', 'Region of Huronia Environmental Services');
    $metaDescription = $metaPage?->meta_description ?: setting('site.seo_description', 'Biosolids management, land application, dredging and related environmental services in Simcoe County, Ontario.');
    $metaImage = $metaPage?->meta_image;
    if ($metaImage && ! \Illuminate\Support\Str::startsWith($metaImage, ['http://', 'https://'])) {
        $metaImage = url($metaImage);
    }

    $socialSettingKeys = array_map(
        fn (string $network) => 'social.'.$network,
        array_keys(\App\Models\Setting::SOCIAL_NETWORKS),
    );
    $storedSocialLinks = \App\Models\Setting::query()
        ->whereIn('key', $socialSettingKeys)
        ->pluck('value', 'key');
    $socialLinks = [];
    foreach (\App\Models\Setting::SOCIAL_NETWORKS as $network => $label) {
        $url = trim((string) ($storedSocialLinks['social.'.$network] ?? ''));
        $parts = $url !== '' ? parse_url($url) : false;

        if (is_array($parts) && strtolower($parts['scheme'] ?? '') === 'https' && ! empty($parts['host'])) {
            $socialLinks[$network] = ['label' => $label, 'url' => $url];
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">

    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
    @endif

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @if (setting('site.google_analytics_id'))
        {{-- Placed here deliberately: the client supplies their own measurement ID. --}}
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ setting('site.google_analytics_id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ setting('site.google_analytics_id') }}');
        </script>
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#main" class="skip-link">Skip to main content</a>

    @include('site.partials.header')

    <main id="main">
        @yield('content')
    </main>

    @include('site.partials.footer')
</body>
</html>
