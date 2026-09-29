<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InvalidatesContentCache;
use App\Http\Controllers\Admin\Concerns\ManagesContent;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\Section;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SectionController extends Controller
{
    use InvalidatesContentCache;
    use ManagesContent;

    public function index(Page $page): View
    {
        return view('admin.sections.index', [
            'page' => $page,
            'sections' => $page->sections()->withCount('items')->get(),
        ]);
    }

    public function create(Page $page): View
    {
        return view('admin.sections.create', [
            'page' => $page,
            'section' => new Section([
                'template_key' => 'card_grid',
                'is_visible' => true,
            ]),
            'availableImages' => Media::query()->images()->orderBy('original_name')->get(),
        ]);
    }

    public function store(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request);
        $sharedSettings = $data['_shared_settings'];
        unset($data['_shared_settings']);

        $data['slug'] = $this->uniqueSectionSlug($page->id, $data['slug'] ?: $data['title']);
        $data['sort_order'] = $this->nextSortOrder('sections', 'page_id', $page->id);
        $data['page_id'] = $page->id;

        $section = $page->sections()->create($data);
        $this->saveSharedSettings($sharedSettings);

        return redirect()
            ->route('admin.sections.items.index', $section)
            ->with('success', "Section \"{$section->title}\" created. Add the content items to it below.");
    }

    public function edit(Section $section): View
    {
        return view('admin.sections.edit', [
            'page' => $section->page,
            'section' => $section,
            'availableImages' => Media::query()->images()->orderBy('original_name')->get(),
        ]);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $data = $this->validated($request);
        $sharedSettings = $data['_shared_settings'];
        unset($data['_shared_settings']);

        if ($data['slug'] !== $section->slug) {
            $data['slug'] = $this->uniqueSectionSlug(
                $section->page_id,
                $data['slug'],
                $section->id
            );
        }

        $section->update($data);
        $this->saveSharedSettings($sharedSettings);

        if ($request->boolean('return_to_page')) {
            return redirect()
                ->route('admin.pages.edit', $section->page)
                ->with('success', 'Section content saved.');
        }

        return redirect()
            ->route('admin.pages.sections.index', $section->page)
            ->with('success', "Section \"{$section->title}\" updated.");
    }

    public function destroy(Section $section): RedirectResponse
    {
        $page = $section->page;
        $title = $section->title;

        $section->delete(); // items cascade

        return redirect()
            ->route('admin.pages.sections.index', $page)
            ->with('success', "Section \"{$title}\" deleted.");
    }

    public function toggle(Section $section, string $field): RedirectResponse
    {
        abort_unless(in_array($field, ['is_visible'], true), 404);

        $section->update([$field => ! $section->{$field}]);

        return back()->with('success', $section->is_visible
            ? 'Section is now visible on the website.'
            : 'Section is now hidden from the website.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        $templateKey = (string) $request->input('template_key', '');
        $templateSettings = Section::TEMPLATE_SETTINGS[$templateKey] ?? [];
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9\-]*$/'],
            'template_key' => ['required', 'string', 'in:'.implode(',', array_keys(Section::TEMPLATE_OPTIONS))],
            'anchor_id' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]*$/'],
            'eyebrow' => ['nullable', 'string', 'max:255'],
            'intro' => ['nullable', 'string', 'max:1000'],
            'is_visible' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'settings' => ['nullable', 'array'],
        ];

        foreach ($templateSettings as $key => $field) {
            $rules['settings.'.$key] = match ($field['type']) {
                'checkbox' => ['nullable', 'boolean'],
                'select' => ['nullable', 'string', Rule::in(array_map('strval', array_keys($field['options'])))],
                'image' => ['nullable', 'string', 'max:255'],
                'textarea' => ['nullable', 'string', 'max:2000'],
                default => $key === 'contact_email'
                    ? ['nullable', 'email', 'max:255']
                    : ['nullable', 'string', 'max:255'],
            };
        }

        $data = $request->validate($rules, [], [
            'template_key' => 'layout',
            'eyebrow' => 'small heading',
            'anchor_id' => 'anchor',
        ]);

        $data['slug'] = $this->blankToNull($data['slug'] ?? null);
        $data['anchor_id'] = $this->blankToNull($data['anchor_id'] ?? null);
        $data['eyebrow'] = $this->blankToNull($data['eyebrow'] ?? null);
        $data['intro'] = $this->blankToNull($data['intro'] ?? null);
        $data['is_visible'] = $request->boolean('is_visible');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        // Drop empty strings from the settings blob so templates see
        // "absent" rather than an empty value they then have to test for.
        $settings = [];
        $sharedSettings = [];
        foreach ($templateSettings as $key => $field) {
            if ($field['type'] === 'checkbox') {
                $settings[$key] = $request->boolean('settings.'.$key);

                continue;
            }

            $value = trim((string) data_get($data, 'settings.'.$key, ''));
            if ($key === 'interests' && $value !== '') {
                $value = implode("\n", array_filter(array_map('trim', preg_split('/[\r\n,]+/', $value) ?: [])));
            }

            if (isset(Section::SHARED_SETTINGS[$key])) {
                $sharedSettings[$key] = $value;
            } elseif ($value !== '') {
                $settings[$key] = $value;
            }
        }

        $data['settings'] = $settings ?: null;
        $data['_shared_settings'] = $sharedSettings;

        return $data;
    }

    /** @param array<string, string> $sharedSettings */
    protected function saveSharedSettings(array $sharedSettings): void
    {
        foreach ($sharedSettings as $key => $value) {
            $settingKey = Section::SHARED_SETTINGS[$key] ?? null;
            if ($settingKey === null) {
                continue;
            }

            if ($value === '') {
                Setting::query()->where('key', $settingKey)->delete();

                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $settingKey],
                ['value' => $value, 'group' => 'contact'],
            );
        }

        if ($sharedSettings !== []) {
            $this->forgetContentCache();
        }
    }
}
