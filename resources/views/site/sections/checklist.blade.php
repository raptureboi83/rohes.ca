<section id="{{ $section->anchor_id }}" class="section section--sand section-anchor">
    <div class="wrap">
        @if ($section->title || $section->intro)
            <div class="section__head section__head--center">
                @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
                @if ($section->title)<h2>{{ $section->title }}</h2>@endif
                @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif
            </div>
        @endif

        <ul class="checklist">
            @foreach ($section->items as $item)
                <li>{{ $item->title ?: $item->body }}</li>
            @endforeach
        </ul>
    </div>
</section>
