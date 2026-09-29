<?php

namespace App\Http\Controllers\Admin\Concerns;

use Illuminate\Support\Facades\Cache;

trait InvalidatesContentCache
{
    /**
     * The public site caches site settings and the navigation. Any admin
     * write that affects either must clear them, otherwise the client saves
     * a change and sees no effect — the single most confusing bug an editor
     * can hit. Centralised here so no controller can forget.
     */
    protected function forgetContentCache(): void
    {
        Cache::forget('site_settings');
        Cache::forget('nav_pages');
        Cache::forget('admin_unread_badge');
    }
}
