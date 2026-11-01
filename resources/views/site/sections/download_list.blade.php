<section id="{{ $section->anchor_id }}" class="section section-anchor">
    <div class="wrap">
        @if ($section->title || $section->intro)
            <div class="section__head">
                @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
                @if ($section->title)<h2>{{ $section->title }}</h2>@endif
                @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif
            </div>
        @endif

        <div class="downloads">
            @foreach ($section->items as $item)
                @php $url = link_to($item->link_url); @endphp
                @if ($url)
                    <a href="{{ $url }}" class="download" @if (! str_starts_with($url, url('/'))) target="_blank" rel="noopener" @endif>
                        <span class="download__icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="m7 10 5 5 5-5"/><path d="M12 15V3"/>
                            </svg>
                        </span>
                        <div style="flex:1">
                            <b>{{ $item->title }}</b>
                            @if ($item->body)<div class="download__description">{!! \App\Support\HtmlSanitizer::sanitizeItemBody($item->body) !!}</div>@endif
                        </div>
                        <span class="pill pill--on">Download</span>
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</section>
