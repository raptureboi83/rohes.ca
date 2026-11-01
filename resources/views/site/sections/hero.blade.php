@php
    $t = fn (string $k, $d = null) => $section->setting($k, $d);
    $backgroundImage = $section->setting('background_image');
    $backgroundOpacity = max(0, min(100, (int) $section->setting('background_opacity', 15)));
@endphp
<section id="{{ $section->anchor_id }}" class="hero section-anchor{{ $backgroundImage ? ' hero--with-image' : '' }}">
    @if ($backgroundImage)
        <div class="hero__background" aria-hidden="true" style="--hero-image-opacity: {{ $backgroundOpacity / 100 }}">
            <img src="{{ media_src($backgroundImage, $section->title) }}" alt="">
        </div>
    @endif
    <div class="wrap hero__inner">
        <div>
            @if ($section->eyebrow)
                <span class="eyebrow">{{ $section->eyebrow }}</span>
            @endif
            <h1>{{ $section->title }}</h1>
            <p class="hero__intro">{{ $section->intro ?: $section->items->firstWhere('item_type', 'text')?->body }}</p>

            @if ($t('cta_label') || $t('secondary_label'))
                <div class="btn-row">
                    @if ($t('cta_label'))
                        <a href="{{ link_to($t('cta_url', '/contact')) }}" class="btn btn--primary">{{ $t('cta_label') }}</a>
                    @endif
                    @if ($t('secondary_label'))
                        <a href="{{ link_to($t('secondary_url', '/')) }}" class="btn btn--ghost" style="border-color:rgba(255,255,255,.45);color:#fff">{{ $t('secondary_label') }}</a>
                    @endif
                </div>
            @endif
        </div>

        @if ($section->items->isNotEmpty())
            <div class="hero__panel">
                <div class="hero__stats">
                    @foreach ($section->items->take(4) as $item)
                        <div class="hero__stat">
                            <b>{{ $item->title ?: $item->meta('value') }}</b>
                            <span>{{ $item->subtitle ?: $item->body }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
