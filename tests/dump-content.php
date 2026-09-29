<?php

use App\Models\SectionItem;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PAGES / SECTIONS / ITEMS ===\n";
foreach (Page::orderBy('id')->get() as $p) {
    printf("PAGE %d | %s | /%s | published=%s\n", $p->id, $p->title, $p->slug, $p->is_published ? '1' : '0');

    foreach (Section::where('page_id', $p->id)->orderBy('sort_order')->get() as $s) {
        printf("   SEC %d | %s | order=%d | %s\n", $s->id, $s->template_key, $s->sort_order, mb_substr(strip_tags((string) $s->heading), 0, 60));

        foreach (SectionItem::where('section_id', $s->id)->orderBy('sort_order')->get() as $i) {
            printf(
                "      ITM %d | %s | %s | textlen=%d | url=%s\n",
                $i->id,
                $i->item_type,
                mb_substr((string) ($i->title ?: $i->label), 0, 50),
                mb_strlen(strip_tags((string) $i->body)),
                $i->url ?? ''
            );
        }
    }
}
echo "\n=== SETTINGS ===\n";
foreach (Setting::orderBy('group')->orderBy('key')->get() as $st) {
    printf("%s | %s | %s\n", $st->group, $st->key, mb_substr((string) $st->value, 0, 90));
}

