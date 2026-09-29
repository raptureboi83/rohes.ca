<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InvalidatesContentCache;
use App\Http\Controllers\Admin\Concerns\ManagesContent;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    use InvalidatesContentCache;
    use ManagesContent;

    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()
                ->withCount('sections')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('admin.pages.create', [
            'page' => new Page(['is_active' => true, 'show_in_nav' => true]),
            'availableImages' => Media::query()->images()->orderBy('original_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], 'pages');
        $data['is_home'] = false; // only ever set by the seeder, never from a form
        $data['sort_order'] = $this->nextSortOrder('pages');

        $page = Page::create($data);

        $this->forgetContentCache();

        return redirect()
            ->route('admin.pages.sections.index', $page)
            ->with('success', "Page \"{$page->title}\" created. Add some sections to it below.");
    }

    /** Landing on a page in the admin goes straight to its section list. */
    public function show(Page $page): RedirectResponse
    {
        return redirect()->route('admin.pages.sections.index', $page);
    }

    public function edit(Page $page): View
    {
        $page->load('sections.items');

        return view('admin.pages.edit', [
            'page' => $page,
            'availableImages' => Media::query()->images()->orderBy('original_name')->get(),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['slug'] !== $page->slug) {
            $data['slug'] = $this->uniqueSlug($data['slug'], 'pages', 'slug', $page->id);
        }

        $page->update($data);

        $this->forgetContentCache();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', "Page \"{$page->title}\" updated.");
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->is_home) {
            return back()->with('error', 'The home page cannot be deleted.');
        }

        $title = $page->title;
        $page->delete(); // sections and items cascade

        $this->forgetContentCache();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', "Page \"{$title}\" and all of its sections were deleted.");
    }

    /**
     * Toggle a boolean flag from the index table. Kept as an explicit
     * action rather than a generic "edit anything" endpoint so a
     * mistyped request cannot change an unrelated column.
     */
    public function toggle(Request $request, Page $page, string $field): RedirectResponse
    {
        abort_unless(in_array($field, ['is_active', 'show_in_nav'], true), 404);

        if ($field === 'is_active' && $page->is_home) {
            return back()->with('error', 'The home page must stay active.');
        }

        $page->update([$field => ! $page->{$field}]);

        $this->forgetContentCache();

        return back()->with('success', 'Updated.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]*$/'],
            'nav_label' => ['nullable', 'string', 'max:255'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_image' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'show_in_nav' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'nav_label' => 'menu label',
            'meta_description' => 'meta description',
        ]);

        $data['nav_label'] = $this->blankToNull($data['nav_label'] ?? null);
        $data['meta_title'] = $this->blankToNull($data['meta_title'] ?? null);
        $data['meta_description'] = $this->blankToNull($data['meta_description'] ?? null);
        $data['meta_image'] = $this->blankToNull($data['meta_image'] ?? null);
        $data['slug'] = $this->blankToNull($data['slug'] ?? null);
        $data['is_active'] = $request->boolean('is_active');
        $data['show_in_nav'] = $request->boolean('show_in_nav');
        $data['sort_order'] = $data['sort_order'] ?? $this->nextSortOrder('pages');

        return $data;
    }
}
