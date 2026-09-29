<?php

use App\Models\User;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (User::all() as $u) {
    printf(
        "%d | %s | role=%s | verified_at=%s\n",
        $u->id,
        $u->email,
        $u->is_admin ? 'admin' : 'user',
        $u->email_verified_at ?? 'NULL (NOT verified)'
    );
}

echo "\n-- configured mail + verification settings --\n";
foreach ([
    'mail.default', 'mail.from.address', 'mail.from.name',
    'app.url', 'app.env',
] as $k) {
    echo str_pad($k, 22).' = '.config($k)."\n";
}
