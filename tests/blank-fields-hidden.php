<?php
/**
 * Proves that a blank setting is hidden everywhere on the public site.
 *
 * Rather than trusting a regex over the Blade source, this blanks every
 * optional setting, renders each public page, and inspects the HTML for
 * orphaned labels - a heading or "Fax:" with no number after it, an empty
 * list item, or a tel: link with no target.
 *
 * Runs inside a transaction that is always rolled back.
 */
require __DIR__.'/../vendor/autoload.php';
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$optional = [
    'contact.toll_free', 'contact.phone', 'contact.mobile', 'contact.fax',
    'contact.email', 'contact.address', 'contact.hours',
    'site.logo_path', 'site.google_analytics_id', 'legal.license_number',
    'site.footer_blurb', 'site.tagline',
];

$before = Setting::whereIn('key', $optional)->pluck('value', 'key')->all();

DB::beginTransaction();
try {
    foreach ($optional as $key) {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => '', 'group' => explode('.', $key)[0]]);
    }
    // Bypass the settings cache so the blanks are what actually render.
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    cache()->forget('site_settings');
    cache()->forget('nav_pages');

    $urls = ['/', '/contact', '/services', '/about', '/news', '/resources', '/biosolids'];
    $fail = 0;

    foreach ($urls as $url) {
        $html = $kernel->handle(Request::create($url, 'GET'))->getContent();
        $problems = [];

        // A visible label with nothing after it.
        if (preg_match_all('/<b>\s*(Toll Free|Local|Mobile|Fax|Email|Office|Phone)\s*<\/b>\s*(?:<\/b>|<[^>]*>\s*<\/a>)?\s*$/m', $html, $m)) {
            $problems[] = 'orphan label: '.implode(', ', array_unique($m[1]));
        }
        if (preg_match_all('/>\s*(Toll Free|Local|Mobile|Fax)\s*:\s*</', $html, $m)) {
            $problems[] = 'orphan footer label: '.implode(', ', array_unique($m[1]));
        }
        // An empty list item or an anchor with no visible text.
        if (preg_match('/<li>\s*<\/li>/', $html)) {
            $problems[] = 'empty <li>';
        }
        if (preg_match_all('/<a[^>]*>\s*<\/a>/', $html)) {
            $problems[] = count($m[0]).' empty anchor(s)';
        }
        // A tel: link with no number.
        if (preg_match('/href="tel:\s*"/', $html)) {
            $problems[] = 'empty tel: link';
        }
        // nl2br of nothing.
        if (preg_match('/<br\s*\/?>\s*<\/span>/', $html)) {
            $problems[] = 'empty <br> block';
        }

        $ok = $problems === [];
        if (! $ok) {
            $fail++;
        }
        printf("  %-14s %s%s\n", $url, $ok ? 'clean' : 'PROBLEM', $ok ? '' : ' -> '.implode('; ', $problems));
    }

    echo "\n  pages with leaked blank fields: $fail\n";
} finally {
    DB::rollBack();
}

// Prove the rollback actually restored the real values.
$after = Setting::whereIn('key', $optional)->pluck('value', 'key')->all();
$restored = $after == $before ? 'yes' : 'NO - '.json_encode($after);
echo "  settings restored by rollback: $restored\n";
echo '  live values still: '
    .Setting::where('key', 'contact.toll_free')->value('value') . ' / '
    .Setting::where('key', 'contact.phone')->value('value') . "\n";

exit($fail ? 1 : 0);
