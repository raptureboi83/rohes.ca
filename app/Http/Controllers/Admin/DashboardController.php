<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InvalidatesContentCache;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Message;
use App\Models\Page;
use App\Models\Section;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use InvalidatesContentCache;

    public function index(): View
    {
        return view('admin.dashboard', [
            'pageCount' => Page::query()->count(),
            'hiddenPageCount' => Page::query()->where('is_active', false)->count(),
            'sectionCount' => Section::query()->count(),
            'itemCount' => \App\Models\SectionItem::query()->count(),
            'mediaCount' => Media::query()->count(),
            'unreadCount' => Message::query()->inbox()->unread()->count(),
            'inboxCount' => Message::query()->inbox()->count(),
            'recentMessages' => Message::query()->inbox()->latest()->take(5)->get(),
            'adminCount' => User::query()->where('is_admin', true)->count(),
            'contactPage' => Page::query()->where('slug', 'contact')->first(),
            'missingTemplateCount' => Section::query()
                ->get()
                ->filter(fn (Section $s) => ! $s->has_template)
                ->count(),
        ]);
    }
}
