<footer class="site-footer">
    <div class="wrap">
        <div class="footer-grid">
            <div>
                <h4>{{ setting('site.short_name', 'ROHES') }}</h4>
                <p style="color: var(--green-300); font-size: .93rem; max-width: 26rem;">
                    {{ setting('site.footer_blurb', 'Biosolids management, treatment, storage, transport and land application across Simcoe County, Ontario.') }}
                </p>
                @include('site.partials.social-links', ['placement' => 'footer'])
            </div>

            <div>
                <h4>Explore</h4>
                <ul>
                    @foreach ($navPages as $navPage)
                        <li><a href="{{ route('page', $navPage['slug']) }}">{{ $navPage['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div>
                <h4>Get in touch</h4>
                <ul>
                    @if (setting('contact.toll_free'))
                        <li><b>Toll Free:</b> <a href="{{ tel_href(setting('contact.toll_free')) }}">{{ setting('contact.toll_free') }}</a></li>
                    @endif
                    @if (setting('contact.phone'))
                        <li><b>Local:</b> <a href="{{ tel_href(setting('contact.phone')) }}">{{ setting('contact.phone') }}</a></li>
                    @endif
                    @if (setting('contact.mobile'))
                        <li><b>Mobile:</b> <a href="{{ tel_href(setting('contact.mobile')) }}">{{ setting('contact.mobile') }}</a></li>
                    @endif
                    @if (setting('contact.fax'))
                        <li><b>Fax:</b> {{ setting('contact.fax') }}</li>
                    @endif
                    @if (setting('contact.email'))
                        <li><a href="mailto:{{ setting('contact.email') }}">{{ setting('contact.email') }}</a></li>
                    @endif
                    @if (setting('contact.address'))
                        <li>{{ setting('contact.address') }}</li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <span>&copy; {{ date('Y') }} {{ setting('site.name', 'Region of Huronia Environmental Services') }}. All rights reserved.</span>
            @if (setting('legal.license_number'))
                <span>{{ setting('legal.license_number') }}</span>
            @endif
        </div>
    </div>
</footer>
