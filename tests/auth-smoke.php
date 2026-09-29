<?php
/**
 * Auth smoke test.
 *
 * Each simulated visit gets a fresh Laravel container so no state leaks
 * between requests. The helper is deliberately NOT called request(), which
 * would shadow Laravel's global request() helper inside compiled Blade.
 */
require __DIR__.'/../vendor/autoload.php';
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

function freshApp() {
    $app = require __DIR__.'/../bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    return $app;
}

$q = freshApp();
$adminId = User::where('is_admin', true)->value('id');
$plainId = User::where('is_admin', false)->value('id');
$email    = User::find($adminId)->email;

function simulate(string $method, string $uri, ?int $asId = null, array $params = []) {
    $app = freshApp();
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    if ($asId) Auth::login(User::find($asId));
    return $kernel->handle(Request::create($uri, $method, $params));
}

$fail = 0;
function check(string $method, string $uri, int $want, string $label, ?int $asId = null, array $p = []) {
    global $fail;
    $c = simulate($method, $uri, $asId, $p)->getStatusCode();
    $ok = $c === $want;
    if (!$fail && !$ok) $fail++;
    if (!$ok) $fail++;
    printf("  %-40s %s (want %d)%s\n", $label, $c, $want, $ok ? '' : '   <== PROBLEM');
}

echo "-- guest screens --\n";
check('GET', '/login', 200, 'login renders');
check('GET', '/forgot-password', 200, 'forgot password renders');
check('GET', '/register', 404, 'self-registration disabled');
check('GET', '/dashboard', 302, 'dashboard alias (guest -> home)');
check('GET', '/admin', 302, 'CMS redirects guest to login');

echo "-- signed in as administrator --\n";
check('GET', '/profile', 200, 'profile renders', $adminId);
check('GET', '/dashboard', 302, 'dashboard alias (admin -> CMS)', $adminId);
check('GET', '/confirm-password', 200, 'confirm password renders', $adminId);
check('GET', '/verify-email', 302, 'verify-email skipped once verified', $adminId);

echo "-- CSRF is enforced --\n";
check('POST', '/logout', 419, 'logout without token rejected', $adminId);

echo "-- authorisation --\n";
if ($plainId) {
    check('GET', '/admin', 302, 'non-admin cannot enter CMS', $plainId);
    check('GET', '/admin/pages', 302, 'non-admin cannot list pages', $plainId);
} else {
    echo "  (skipped: local db has no non-admin user)\n";
}

echo "\n  failures: $fail\n";
exit($fail ? 1 : 0);
