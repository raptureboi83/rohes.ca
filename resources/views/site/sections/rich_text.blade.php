{{--
    Rich-text content is passed through an allowlist sanitizer before output.
    Formatting and selected embeds are retained; scripts, styles and unsafe
    attributes or URLs are removed.
--}}
<section id="{{ $section->anchor_id }}" class="section section-anchor">
    <div class="wrap">
        <div class="rich">
            @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
            @if ($section->title)<h2>{{ $section->title }}</h2>@endif
            @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif
            {!! \App\Support\HtmlSanitizer::sanitize($section->setting('html')) !!}
            @foreach ($section->items as $item)
                {!! \App\Support\HtmlSanitizer::sanitizeItemBody($item->body) !!}
            @endforeach
        </div>
    </div>
</section>
