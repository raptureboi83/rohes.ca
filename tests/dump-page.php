<?php

use App\Models\Section;
use App\Models\SectionItem;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$want = array_slice($argv, 1);
if (! $want) {
    $want = ['biosolids', 'news', 'services', 'about', 'resources'];
}

foreach ($want as $slug) {
    $page = App\Models\Page::where('slug', $slug)->first();

    if (! $page) {
        echo "!! no page: $slug\n";

        continue;
    }

    echo "\n############ /".$page->slug." ############\n";

    foreach (Section::where('page_id', $page->id)->orderBy('id')->get() as $s) {
        echo "\n== SEC {$s->id} [{$s->template_key}] heading=".mb_substr((string) $s->heading, 0, 50)."\n";

        foreach (SectionItem::where('section_id', $s->id)->orderBy('sort_order')->get() as $i) {
            $label = $i->title ?: $i->subtitle ?: '';
            echo "  - [{$i->item_type}] ".mb_substr((string) $label, 0, 60)."\n";
            $b = trim(preg_replace('/\s+/', ' ', strip_tags((string) $i->body)));
            if ($b !== '') {
                echo "      $b\n";
            }
            if ($i->link_url) {
                echo "      LINK {$i->link_url} (text=".($i->link_text ?: '-').")\n";
            }
            if ($i->image_path) {
                echo "      IMG {$i->image_path}\n";
            }
        }
    }
}
