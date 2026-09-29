<?php
// Read-only inspection of the live database. Reports current contact settings
// and media counts so the deploy can be verified against the real baseline.
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (['contact.toll_free', 'contact.phone', 'contact.fax', 'contact.phone2', 'contact.mobile'] as $key) {
    $s = App\Models\Setting::where('key', $key)->first();
    echo '  '.$key.' = '.($s ? $s->value : '(row absent)').PHP_EOL;
}
echo '  media rows: '.App\Models\Media::count().PHP_EOL;
echo '  pages: '.App\Models\Page::count().'  sections: '.App\Models\Section::count()
    .'  items: '.App\Models\SectionItem::count().PHP_EOL;
echo '  messages: '.App\Models\Message::count().'  users: '.App\Models\User::count().PHP_EOL;
$default = App\Models\User::where('email', 'admin@rohes.ca')->exists() ? 'yes' : 'no';
echo '  default-password account present: '.$default.PHP_EOL;
