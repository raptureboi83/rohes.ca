<section id="{{ $section->anchor_id }}" class="section section--sand section-anchor">
    <div class="wrap">
        @if ($section->eyebrow || $section->title)
            <div class="section__head">
                @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
                @if ($section->title)<h2>{{ $section->title }}</h2>@endif
            </div>
        @endif

        @forelse ($section->items as $i => $item)
            @php $flip = $section->setting('flip') ? $i % 2 === 0 : $i % 2 === 1; @endphp
            <div class="split {{ $flip ? 'split--flip' : '' }}">
                <div>
                    @if ($item->image_path)
                        <div class="split__media">
                            <img src="{{ media_src($item->image_path, $item->title) }}"
                                 alt="{{ $item->image_alt ?: $item->title }}" loading="lazy">
                        </div>
                    @endif
                </div>
                <div>
                    @if ($item->subtitle)<span class="eyebrow">{{ $item->subtitle }}</span>@endif
                    <h3>{{ $item->title }}</h3>
                    <div class="prose">{!! \App\Support\HtmlSanitizer::sanitizeItemBody($item->body) !!}</div>
                    @if ($item->link_url)
                        <div class="btn-row" style="margin-top:1.25rem">
                            <a href="{{ link_to($item->link_url) }}" class="btn btn--green btn--sm">
                                {{ $item->link_text ?: 'Learn more' }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            @if ($section->intro)<p class="prose">{!! nl2br(e($section->intro)) !!}</p>@endif
        @endforelse
    </div>
</section>
