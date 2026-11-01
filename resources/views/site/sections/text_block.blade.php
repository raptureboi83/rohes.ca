<section id="{{ $section->anchor_id }}" class="section section-anchor">
    <div class="wrap">
        <div class="prose">
            @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
            @if ($section->title)<h2>{{ $section->title }}</h2>@endif
            @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif

            {{-- Items are optional here: a text_block can just be the
                 title, intro and any paragraphs entered as items. --}}
            @foreach ($section->items as $item)
                @if ($item->title)<h3>{{ $item->title }}</h3>@endif
                @if ($item->body){!! \App\Support\HtmlSanitizer::sanitizeItemBody($item->body) !!}@endif
            @endforeach
        </div>
    </div>
</section>
