<section id="{{ $section->anchor_id }}" class="section section-anchor">
    <div class="wrap">
        @if ($section->title || $section->intro)
            <div class="section__head section__head--center">
                @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
                @if ($section->title)<h2>{{ $section->title }}</h2>@endif
                @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif
            </div>
        @endif

        <div class="tiles">
            @foreach ($section->items as $item)
                <figure class="tile">
                    <img src="{{ media_src($item->image_path, $item->title) }}"
                         alt="{{ $item->image_alt ?: $item->title }}" loading="lazy">
                    @if ($item->title || $item->subtitle)
                        <figcaption>
                            @if ($item->title)<b>{{ $item->title }}</b>@endif
                            @if ($item->subtitle)<br><span class="muted small">{{ $item->subtitle }}</span>@endif
                        </figcaption>
                    @endif
                </figure>
            @endforeach
        </div>
    </div>
</section>
