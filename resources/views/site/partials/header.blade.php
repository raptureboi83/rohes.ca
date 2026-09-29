<header class="site-header">
    <div class="wrap site-header__inner">
        <a href="{{ route('home') }}" class="brand">
            @if (setting('site.logo_path'))
                <img src="{{ setting('site.logo_path') }}" alt="{{ setting('site.name', 'Region of Huronia Environmental Services') }}" class="brand__logo">
            @endif
            <span class="brand__text">
                <span class="brand__name">{{ setting('site.short_name', 'ROHES') }}</span>
                <span class="brand__tag">{{ setting('site.tagline', 'Environmental Services') }}</span>
            </span>
        </a>

        <nav class="nav" id="siteNav" aria-label="Main">
            <ul>
                @foreach ($navPages as $navPage)
                    <li>
                        <a href="{{ route('page', $navPage['slug']) }}"
                           @if (($page?->slug ?? null) === $navPage['slug']) aria-current="page" @endif>
                            {{ $navPage['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="site-header__actions">
            @include('site.partials.social-links', ['placement' => 'header'])
            <button type="button" class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteNav" aria-label="Toggle navigation">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>
        </div>
    </div>
</header>

<script>
    // Progressive enhancement: without JS the nav stays visible on desktop
    // and the mobile toggle simply does nothing, rather than hiding the menu.
    (function () {
        var toggle = document.getElementById('navToggle');
        var nav = document.getElementById('siteNav');
        if (!toggle || !nav) return;
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    })();
</script>
