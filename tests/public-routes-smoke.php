<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Http\Request;

$fail = 0;
foreach (['/','/about','/services','/biosolids','/news','/resources','/contact','/no-such-page'] as $p) {
    $r = $kernel->handle(Request::create($p,'GET'));
    $c = $r->getStatusCode();
    $expect = $p === '/no-such-page' ? 404 : 200;
    $h1 = preg_match_all('~<h1\b~i', $r->getContent());
    $ok = $c === $expect && $h1 === 1;
    if (! $ok) $fail++;
    printf("  %-16s %s (want %d)  h1=%d %s\n", $p, $c, $expect, $h1, $ok ? '' : '<== PROBLEM');
}
echo "\n  failures: $fail\n";
exit($fail ? 1 : 0);
