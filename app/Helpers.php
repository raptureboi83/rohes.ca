<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

if (! function_exists('setting')) {
    /**
     * Read a site-wide setting with a fallback.
     *
     * The whole settings table is cached in one array because it is small
     * and read on every page render. SettingController flushes this cache
     * whenever anything is saved.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        $settings = Cache::rememberForever('site_settings', function () {
            return \App\Models\Setting::query()->pluck('value', 'key')->toArray();
        });

        $value = $settings[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }
}

if (! function_exists('setting_bool')) {
    function setting_bool(string $key, bool $default = false): bool
    {
        $value = setting($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}

if (! function_exists('setting_url')) {
    /**
     * Normalise a stored URL for output. Internal paths are left alone;
     * external links are forced to https where possible so the site does
     * not leak referrer data or trip mixed-content warnings.
     */
    function setting_url(string $key, ?string $default = null): ?string
    {
        $value = trim((string) setting($key, $default));

        if ($value === '') {
            return $default;
        }

        if (Str::startsWith($value, ['http://', 'https://', 'mailto:', 'tel:', '#', '/'])) {
            return $value;
        }

        return 'https://'.$value;
    }
}

if (! function_exists('nav_pages')) {
    /**
     * Site navigation.
     *
     * Read on every page render and only changes when the client edits the
     * admin, so it is cached. Returned as a plain array of
     * `['slug' => …, 'label' => …]` rather than a Collection of Eloquent
     * models: serialising models into the cache couples the cached value to
     * the model class, and a change to that class later makes every entry
     * unserialise as `__PHP_Incomplete_Class` and break rendering.
     *
     * Cleared by Admin\Concerns\InvalidatesContentCache.
     */
    function nav_pages(): array
    {
        return Cache::rememberForever('nav_pages', function () {
            return \App\Models\Page::query()
                ->where('is_active', true)
                ->where('show_in_nav', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['title', 'slug', 'nav_label'])
                ->map(fn ($page) => [
                    'slug' => $page->slug,
                    'label' => $page->display_label,
                ])
                ->all();
        });
    }
}

if (! function_exists('link_to')) {
    /**
     * Resolve a content-authored link for output.
     *
     * The admin accepts either a full external URL (https://…, mailto:…,
     * tel:…) or a bare path such as "/services" meaning an internal page.
     * Internal paths get run through url() so they stay correct on any
     * host; external ones are passed through untouched.
     */
    function link_to(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://', 'mailto:', 'tel:', '#'])) {
            return $url;
        }

        return url('/'.ltrim($url, '/'));
    }
}

if (! function_exists('media_src')) {
    /**
     * Resolve a content image path, falling back to a generated
     * placeholder so card grids never collapse to uneven heights.
     */
    function media_src(?string $path, string $label = 'ROHES'): string
    {
        $path = trim((string) $path);

        return $path !== '' ? url($path) : placeholder_image_url($label);
    }
}

if (! function_exists('tel_href')) {
    /**
     * Build a tel: URL from a display number, stripping the punctuation that
     * is shown to people but must not appear in the link target.
     * Returns an empty string when there is no number.
     */
    function tel_href(?string $number): string
    {
        $number = trim((string) $number);
        $digits = preg_replace('/[^0-9+]/', '', $number) ?? '';

        return $digits === '' ? '' : 'tel:'.$digits;
    }
}

if (! function_exists('excerpt')) {
    /** Trim to a word boundary and append an ellipsis. */
    function excerpt(?string $text, int $limit = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? '');

        if ($text === '' || Str::length($text) <= $limit) {
            return $text;
        }

        return Str::limit($text, $limit, '…');
    }
}

if (! function_exists('placeholder_image_url')) {
    /**
     * Render a neutral inline placeholder when a section or item has no
     * image, so card grids never collapse to uneven heights.
     */
    function placeholder_image_url(string $label = ''): string
    {
        $label = trim($label) ?: 'ROHES';
        $initials = collect(explode(' ', $label))
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300">'
            .'<rect width="400" height="300" fill="#e2efe8"/>'
            .'<text x="50%" y="50%" text-anchor="middle" dy=".35em" '
            .'font-family="system-ui,sans-serif" font-size="64" font-weight="700" '
            .'fill="#86bda1">'.htmlspecialchars($initials).'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
