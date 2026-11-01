<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InvalidatesContentCache;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Page;
use App\Models\Section;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use InvalidatesContentCache;

    public function index(): View
    {
        $pages = Page::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $homePage = $pages->firstWhere('is_home', true);
        $unreadCount = Message::query()->inbox()->unread()->count();

        return view('admin.dashboard', [
            'pages' => $pages,
            'homePage' => $homePage,
            'unreadCount' => $unreadCount,
            'recentMessages' => Message::query()->inbox()->unread()->latest()->take(3)->get(),
            'recentlyUpdated' => Section::query()
                ->with('page')
                ->whereHas('page', fn ($q) => $q->where('is_active', true))
                ->orderByDesc('updated_at')
                ->take(5)
                ->get(),
            'attention' => $this->attentionItems($pages),
        ]);
    }

    /**
     * Problems worth telling the site editor about, each with somewhere to
     * go and fix it.
     *
     * Deliberately only reports things that are actually wrong right now.
     * A dashboard that always shows the same advisory list stops being read,
     * which is the same failure mode as the vanity counters this replaced.
     *
     * @return array<int, array{severity: string, message: string, action: string|null, url: string|null}>
     */
    protected function attentionItems($pages): array
    {
        $items = [];

        $undrawable = Section::query()->get()->filter(fn (Section $s) => ! $s->has_template);
        if ($undrawable->isNotEmpty()) {
            $items[] = [
                'severity' => 'error',
                'message' => $undrawable->count().' part(s) of the website use a display style that is no longer available, so visitors cannot see that content.',
                'action' => 'Review page',
                'url' => $undrawable->first()->page
                    ? route('admin.pages.sections.index', $undrawable->first()->page)
                    : route('admin.pages.index'),
            ];
        }

        if (! $pages->contains(fn (Page $p) => $p->is_home && $p->is_active)) {
            $items[] = [
                'severity' => 'error',
                'message' => 'The homepage is not set, so visitors cannot open the main page.',
                'action' => 'Choose homepage',
                'url' => route('admin.pages.index'),
            ];
        }

        $hidden = $pages->where('is_active', false);
        if ($hidden->isNotEmpty()) {
            $items[] = [
                'severity' => 'info',
                'message' => $hidden->count().' page(s) are hidden from visitors: '
                    .$hidden->pluck('title')->join(', ').'.',
                'action' => 'Review pages',
                'url' => route('admin.pages.index'),
            ];
        }

        $missingAddress = empty(trim((string) setting('contact.address')));
        if ($missingAddress) {
            $items[] = [
                'severity' => 'info',
                'message' => 'No business address is set, so the website does not show a street address.',
                'action' => 'Add address',
                'url' => route('admin.settings.index'),
            ];
        }

        return $items;
    }
}