@php $t = fn (string $k, $d = null) => $section->setting($k, $d); @endphp
<section id="{{ $section->anchor_id }}" class="section section--tight section-anchor">
    <div class="wrap">
        <div class="cta">
            @if ($section->eyebrow)<span class="eyebrow" style="color:#fff;opacity:.85">{{ $section->eyebrow }}</span>@endif
            <h2>{{ $section->title }}</h2>
            <p>{{ $section->intro ?: $section->items->first()?->body }}</p>
            @if ($t('cta_label'))
                <div class="btn-row">
                    <a href="{{ link_to($t('cta_url', '/contact')) }}" class="btn btn--light">{{ $t('cta_label') }}</a>
                    @if ($t('secondary_label'))
                        <a href="{{ link_to($t('secondary_url')) }}" class="btn btn--ghost" style="border-color:rgba(255,255,255,.6);color:#fff">{{ $t('secondary_label') }}</a>
                    @endif
                </div>
            @endif
        </div>
    </div>
</section>
