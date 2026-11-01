@php
    $cols = (int) $section->setting('columns', 0);
    $style = $cols > 0 ? 'grid-template-columns:repeat('.$cols.',minmax(0,1fr))' : null;
@endphp
<section id="{{ $section->anchor_id }}" class="section {{ $section->setting('background', '') }} section-anchor">
    <div class="wrap">
        @if ($section->title || $section->intro)
            <div class="section__head {{ $section->setting('center_heading') ? 'section__head--center' : '' }}">
                @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
                @if ($section->title)<h2>{{ $section->title }}</h2>@endif
                @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif
            </div>
        @endif

        @if ($section->items->isNotEmpty())
            <div class="cards" @if ($style) style="{{ $style }}" @endif>
                @foreach ($section->items as $item)
                    <article class="card">
                        @if ($item->image_path)
                            <div class="card__media">
                                <img src="{{ media_src($item->image_path, $item->title) }}"
                                     alt="{{ $item->image_alt ?: $item->title }}"
                                     loading="lazy" width="{{ $item->meta('width') }}" height="{{ $item->meta('height') }}">
                            </div>
                        @endif
                        <div class="card__body">
                            @if ($item->subtitle)<p class="card__subtitle">{{ $item->subtitle }}</p>@endif
                            <h3 class="card__title">{{ $item->title }}</h3>
                            @if ($item->body)<p class="card__text">{{ $item->body }}</p>@endif
                            @if ($item->link_url)
                                <a href="{{ link_to($item->link_url) }}" class="card__link">
                                    {{ $item->link_text ?: 'Learn more' }} &rarr;
                                </a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
