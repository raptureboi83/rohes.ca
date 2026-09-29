<?php
// Feature test: walk every admin route as a signed-in admin and assert the
// response is a 2xx/3xx, not a 500. This catches missing views, bad route
// names and undefined-variable errors without a browser.

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$user = App\Models\User::where('email','admin@rohes.ca')->first();
if (! $user) { exit("no admin user\n"); }
Auth::login($user);

$page   = App\Models\Page::where('is_home', true)->first() ?? App\Models\Page::first();
$section = App\Models\Section::first();
$item   = App\Models\SectionItem::first();
$msg    = App\Models\Message::first();

$paths = [
  '/admin', '/admin/pages', '/admin/pages/create',
  '/admin/pages/'.$page->id.'/edit',
  '/admin/pages/'.$page->id.'/sections',
  '/admin/pages/'.$page->id.'/sections/create',
  '/admin/sections/'.$section->id.'/edit',
  '/admin/sections/'.$section->id.'/items',
  '/admin/sections/'.$section->id.'/items/create',
  '/admin/sections/'.$section->id.'/items/'.$item->id.'/edit',
  '/admin/messages', '/admin/settings', '/admin/users',
  '/admin/users/create', '/admin/users/'.$user->id.'/edit',
];
if ($msg) { $paths[] = '/admin/messages/'.$msg->id; }

$fail = 0;
foreach ($paths as $p) {
    $resp = $kernel->handle(Request::create($p, 'GET'));
    $code = $resp->getStatusCode();
    $ok   = $code >= 200 && $code < 400;
    if (! $ok) { $fail++; }
    printf("  %-52s %s%s\n", $p, $code, $ok ? '' : '   <== PROBLEM');
}

echo "\n  failures: $fail\n";
exit($fail ? 1 : 0);
