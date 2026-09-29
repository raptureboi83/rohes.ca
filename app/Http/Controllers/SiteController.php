<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Section;
use Illuminate\View\View;

class SiteController extends Controller
{
    /**
     * Render a page by slug.
     *
     * This is the generic renderer that the matsart.ca project lacks: it
     * looks the page up by slug, loads its visible sections, and includes
     * one Blade partial per section's `template_key`. Nothing about the
     * page structure is hardcoded, so the client can add pages, sections
     * and layouts in the admin and have them appear without a code change.
     */
    public function page(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $sections = $page->visibleSections()->with('items')->get();

        // Defensive: if a section references a template with no partial
        // (e.g. a key added to the DB by hand), skip it rather than
        // throwing a 500 on a live page.
        $sections = $sections->filter(
            fn (Section $section) => $section->has_template
        )->values();

        return view('site.page', [
            'page' => $page,
            'sections' => $sections,
        ]);
    }

    public function home(): View
    {
        $home = Page::query()->where('is_home', true)->where('is_active', true)->first();

        if (! $home) {
            abort(404, 'No home page has been set up yet.');
        }

        return $this->page($home->slug);
    }
}
