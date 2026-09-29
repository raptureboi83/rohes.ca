<?php
/**
 * Proves the admin guard actually blocks non-administrators.
 * The fixture user is created and deleted within this run.
 */
require __DIR__.'/../vendor/autoload.php';
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

function freshApp() {
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    return $app;
}

$app = freshApp();
$id = User::create([
    'name' => 'Smoke Non Admin',
    'email' => 'smoke-nonadmin@example.test',
    'password' => Hash::make('temporary-probe-password'),
    'is_admin' => false,
])->id;

$fail = 0;
// 403 = signed in but not an administrator. Guest access is covered by
// auth-smoke.php, which expects a redirect to /login.
$checks = [
    ['/admin', 403, 'CMS dashboard'],
    ['/admin/pages', 403, 'pages'],
    ['/admin/messages', 403, 'messages'],
    ['/admin/users', 403, 'user accounts'],
    ['/admin/settings', 403, 'settings'],
    ['/admin/media', 403, 'media library'],
];
foreach ($checks as [$uri, $want, $label]) {
    $a = freshApp();
    $k = $a->make(Illuminate\Contracts\Http\Kernel::class);
    Auth::login(User::find($id));
    $c = $k->handle(Request::create($uri, 'GET'))->getStatusCode();
    $ok = $c === $want;
    if (!$ok) $fail++;
    printf("  %-26s %s (want %d)%s\n", $label, $c, $want, $ok ? '' : '   <== PROBLEM');
}

User::find($id)->forceDelete();
printf("  fixture removed: %s\n", User::find($id) ? 'NO' : 'yes');
echo "\n  failures: $fail\n";
exit($fail ? 1 : 0);
