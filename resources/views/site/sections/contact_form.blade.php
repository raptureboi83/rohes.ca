@php
    $t = fn (string $k, $d = null) => $section->setting($k, $d);
    // h2 when this section is one of several on a page; h1 when the form is
    // the whole page. A page must have exactly one h1.
    $h = $section->setting('heading_level') === 'h1' ? 'h1' : 'h2';
@endphp
<section id="{{ $section->anchor_id }}" class="section section-anchor">
    <div class="wrap">
        <div class="contact-grid">
            <div class="section__head" style="margin-bottom:0">
                @if ($section->eyebrow)<span class="eyebrow">{{ $section->eyebrow }}</span>@endif
                <{{ $h }}>{{ $section->title }}</{{ $h }}>
                <p class="lede">{{ $section->intro }}</p>

                <ul class="contact-list" style="margin-top:2rem">
                    {{-- Address first, then the lines. Mirrors the
                         contact_details template: these two drifted apart
                         at some point and the standalone contact page was
                         left without the postal address or opening hours. --}}
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
            </div>

            <div class="form-card">
                @if (session('success'))
                    <div class="flash flash--success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="flash flash--error">
                        <span>Please correct the {{ $errors->count() }} highlighted {{ Str::plural('field', $errors->count()) }} below.</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" novalidate>
                    @csrf

                    {{-- Honeypot. Hidden from people, irresistible to bots. --}}
                    <div class="hp-field" aria-hidden="true">
                        <label for="website">Leave this field empty</label>
                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="field-row">
                        <div class="field {{ $errors->has('name') ? 'field--error' : '' }}">
                            <label for="name">Your name <span aria-hidden="true">*</span></label>
                            <input class="input" type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="name">
                            @error('name')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('email') ? 'field--error' : '' }}">
                            <label for="email">Email <span aria-hidden="true">*</span></label>
                            <input class="input" type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                            @error('email')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="field-row">
                        <div class="field {{ $errors->has('phone') ? 'field--error' : '' }}">
                            <label for="phone">Phone</label>
                            <input class="input" type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel">
                            @error('phone')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                        <div class="field {{ $errors->has('interest') ? 'field--error' : '' }}">
                            <label for="interest">What is this about?</label>
                            <select class="select" id="interest" name="interest">
                                <option value="">Please choose…</option>
                                @foreach (($t('interests') ? preg_split('/[\r\n,]+/', $t('interests')) : []) as $opt)
                                    @if (trim($opt) !== '')
                                        <option value="{{ trim($opt) }}" @selected(old('interest') === trim($opt))>{{ trim($opt) }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @error('interest')<p class="error-text">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="field {{ $errors->has('subject') ? 'field--error' : '' }}">
                        <label for="subject">Subject</label>
                        <input class="input" type="text" id="subject" name="subject" value="{{ old('subject') }}">
                        @error('subject')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <div class="field {{ $errors->has('message') ? 'field--error' : '' }}">
                        <label for="message">Message <span aria-hidden="true">*</span></label>
                        <textarea class="textarea" id="message" name="message" required>{{ old('message') }}</textarea>
                        <p class="hint">Please include your municipality, the type of material, and rough quantities if you know them.</p>
                        @error('message')<p class="error-text">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn btn--primary">Send message</button>
                </form>
            </div>
        </div>
    </div>
</section>
