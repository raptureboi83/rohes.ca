<section id="{{ $section->anchor_id }}" class="section section--sand section-anchor">
    <div class="wrap">
        @if ($section->title || $section->intro)
            <div class="section__head">
                @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
                @if ($section->title)<h2>{{ $section->title }}</h2>@endif
                @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif
            </div>
        @endif

        <div class="news-list">
            @foreach ($section->items as $item)
                <article class="news-item {{ $item->meta('is_archived') ? 'news-item--archived' : '' }}">
                    <span class="news-item__date">{{ $item->meta('date') ?: $item->created_at?->format('j F Y') }}</span>
                    <h3>{{ $item->title }}</h3>
                    <p>{{ $item->body }}</p>

                    @if ($item->link_url)
                        <p style="margin-top:.85rem">
                            <a href="{{ link_to($item->link_url) }}" style="color:var(--green-700);font-weight:700;text-decoration:none"
                               @if ($item->meta('is_archived')) target="_blank" rel="noopener" @endif>
                                {{ $item->link_text ?: 'Read more' }} &rarr;
                            </a>
                        </p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
