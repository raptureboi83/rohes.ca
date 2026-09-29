<?php

namespace App\Providers;

use App\Models\Message;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Every public page needs the navigation, including ones rendered by
        // their own controller (e.g. /contact). Shared by a view composer so
        // no controller has to remember to pass it.
        View::composer('site.*', function ($view) {
            $view->with('navPages', nav_pages());
        });

        // The admin sidebar shows an unread-messages badge on every admin
        // screen. Shared via a view composer rather than passed around by
        // hand, and cached for a minute so a busy inbox does not mean a
        // database query on every single admin page load.
        View::composer('admin.*', function ($view) {
            $view->with('unreadBadge', Cache::remember(
                'admin_unread_badge',
                now()->addMinute(),
                fn () => Message::query()->inbox()->unread()->count()
            ));
        });
    }
}
