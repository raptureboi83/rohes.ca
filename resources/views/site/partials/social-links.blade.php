@if ($socialLinks)
    <div class="social-links social-links--{{ $placement ?? 'footer' }}" aria-label="Social media">
        @foreach ($socialLinks as $network => $social)
            <a class="social-link" href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
               aria-label="ROHES on {{ $social['label'] }}" title="{{ $social['label'] }}">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    @switch($network)
                        @case('facebook')
                            <path d="M13.6 21v-8h2.7l.4-3.1h-3.1V8c0-.9.3-1.5 1.6-1.5h1.7V3.7c-.3 0-1.4-.1-2.6-.1-2.6 0-4.3 1.6-4.3 4.5v1.8H7.1V13H10v8z"/>
                            @break
                        @case('instagram')
                            <rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="17.5" cy="6.5" r="1.25"/>
                            @break
                        @case('linkedin')
                            <path d="M5 8.5a1.8 1.8 0 1 0 0-3.6 1.8 1.8 0 0 0 0 3.6ZM3.4 10h3.2v10H3.4zM9 10h3.1v1.4h.1a3.4 3.4 0 0 1 3-1.7c3.2 0 3.8 2.1 3.8 4.8V20h-3.2v-4.9c0-1.2 0-2.7-1.7-2.7s-1.9 1.3-1.9 2.6V20H9z"/>
                            @break
                        @case('youtube')
                            <path d="M23 7.2a3 3 0 0 0-2.1-2.1C19 4.6 12 4.6 12 4.6s-7 0-8.9.5A3 3 0 0 0 1 7.2 31 31 0 0 0 .5 12a31 31 0 0 0 .5 4.8 3 3 0 0 0 2.1 2.1c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.8 31 31 0 0 0-.5-4.8ZM9.7 15.3V8.7l5.7 3.3z"/>
                            @break
                        @case('x')
                            <path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3l-5-7.3L5.5 22H2.3l7.3-8.4L1.8 2h6.5l4.5 6.7zM17.8 19.8h1.7L7.3 4.1H5.5z"/>
                            @break
                    @endswitch
                </svg>
            </a>
        @endforeach
    </div>
@endif