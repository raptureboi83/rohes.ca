@php $t = fn (string $k, $d = null) => $section->setting($k, $d); @endphp
<section id="{{ $section->anchor_id }}" class="section section--sand section-anchor">
    <div class="wrap">
        <div class="section__head">
            @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
            @if ($section->title)<h2>{{ $section->title }}</h2>@endif
            @if ($section->intro)<p class="lede">{{ $section->intro }}</p>@endif
        </div>

        <div class="contact-grid">
            <ul class="contact-list">
                @if (setting('contact.address'))
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        <span><b>Office</b>{!! nl2br(e(setting('contact.address'))) !!}</span>
                    </li>
                @endif
                @if (setting('contact.toll_free'))
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
                        <span><b>Toll Free</b><a href="{{ tel_href(setting('contact.toll_free')) }}">{{ setting('contact.toll_free') }}</a></span>
                    </li>
                @endif
                @if (setting('contact.phone'))
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></svg>
                        <span><b>Local</b><a href="{{ tel_href(setting('contact.phone')) }}">{{ setting('contact.phone') }}</a></span>
                    </li>
                @endif
                @if (setting('contact.mobile'))
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
                        <span><b>Mobile</b><a href="{{ tel_href(setting('contact.mobile')) }}">{{ setting('contact.mobile') }}</a></span>
                    </li>
                @endif
                @if (setting('contact.fax'))
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z"/></svg>
                        <span><b>Fax</b>{{ setting('contact.fax') }}</span>
                    </li>
                @endif
                @if (setting('contact.email'))
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                        <span><b>Email</b><a href="mailto:{{ setting('contact.email') }}">{{ setting('contact.email') }}</a></span>
                    </li>
                @endif
                @if (setting('contact.hours'))
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        <span><b>Hours</b>{!! nl2br(e(setting('contact.hours'))) !!}</span>
                    </li>
                @endif
            </ul>

            <div>
                @if ($t('map_embed'))
                    <div style="border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--line)">
                        {!! \App\Support\HtmlSanitizer::sanitize($t('map_embed')) !!}
                    </div>
                @endif
                @if ($t('note'))
                    <div class="callout" style="margin-top:1.25rem">{!! nl2br(e($t('note'))) !!}</div>
                @endif
            </div>
        </div>
    </div>
</section>
