<section id="{{ $section->anchor_id }}" class="section section--tight section-anchor">
    <div class="wrap">
        @if ($section->title || $section->intro)
            <div class="section__head section__head--center">
                @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
                @if ($section->title)<h2>{{ $section->title }}</h2>@endif
                @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif
            </div>
        @endif

        @if ($section->items->isNotEmpty())
            <div class="stats">
                @foreach ($section->items as $item)
                    <div class="stat">
                        <span class="stat__value">{{ $item->title ?: $item->meta('value') }}</span>
                        <span class="stat__label">{{ $item->subtitle ?: $item->body }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
