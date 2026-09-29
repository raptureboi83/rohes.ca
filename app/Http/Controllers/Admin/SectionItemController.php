<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\InvalidatesContentCache;
use App\Http\Controllers\Admin\Concerns\ManagesContent;
use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Section;
use App\Models\SectionItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SectionItemController extends Controller
{
    use ManagesContent;

    public function index(Section $section): View
    {
        return view('admin.items.index', [
            'page' => $section->page,
            'section' => $section,
            'items' => $section->items()->get(),
        ]);
    }

    public function create(Section $section): View
    {
        return view('admin.items.create', [
            'page' => $section->page,
            'section' => $section,
            'item' => new SectionItem(['item_type' => $this->defaultTypeFor($section)]),
            'availableImages' => Media::query()->images()->orderBy('original_name')->get(),
        ]);
    }

    public function store(Request $request, Section $section): RedirectResponse
    {
        $data = $this->validated($request, $section);

        $data['sort_order'] = $this->nextSortOrder('section_items', 'section_id', $section->id);

        $item = $section->items()->create($data);

        return redirect()
            ->route('admin.sections.items.index', $section)
            ->with('success', 'Content item added.');
    }

    public function edit(Section $section, SectionItem $item): View
    {
        return view('admin.items.edit', [
            'page' => $section->page,
            'section' => $section,
            'item' => $item,
            'availableImages' => Media::query()->images()->orderBy('original_name')->get(),
        ]);
    }

    public function update(Request $request, Section $section, SectionItem $item): RedirectResponse
    {
        abort_unless($item->section_id === $section->id, 404);

        $item->update($this->validated($request, $section, $item));

        if ($request->boolean('return_to_page')) {
            return redirect()
                ->route('admin.pages.edit', $section->page)
                ->with('success', 'Item content saved.');
        }

        return redirect()
            ->route('admin.sections.items.index', $section)
            ->with('success', 'Content item updated.');
    }

    public function destroy(Section $section, SectionItem $item): RedirectResponse
    {
        abort_unless($item->section_id === $section->id, 404);

        $item->delete();

        return back()->with('success', 'Content item deleted.');
    }

    /**
     * Nudge an item up or down the list without needing drag-and-drop.
     * Swaps sort_order with the neighbour.
     */
    public function move(Section $section, SectionItem $item, string $direction): RedirectResponse
    {
        abort_unless($item->section_id === $section->id, 404);
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $neighbour = $section->items()
            ->where($direction === 'up' ? '<' : '>', $item->sort_order)
            ->orderBy('sort_order', $direction === 'up' ? 'desc' : 'asc')
            ->orderBy('id', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($neighbour) {
            $current = $item->sort_order;
            $item->update(['sort_order' => $neighbour->sort_order]);
            $neighbour->update(['sort_order' => $current]);
        }

        return back();
    }

    /**
     * A sensible default item type based on which template the section uses,
     * so the client rarely has to change the dropdown at all.
     */
    protected function defaultTypeFor(Section $section): string
    {
        return match ($section->template_key) {
            'stat_grid' => 'stat',
            'checklist' => 'checklist',
            'image_tiles' => 'feature',
            'news_list' => 'news',
            'download_list' => 'document',
            'card_grid' => 'service',
            default => 'default',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, Section $section, ?SectionItem $existingItem = null): array
    {
        $itemType = (string) $request->input('item_type', '');
        $dataFields = SectionItem::DATA_FIELDS[$itemType] ?? [];
        $rules = [
            'item_type' => ['required', 'string', 'in:'.implode(',', array_keys(SectionItem::ITEM_TYPES))],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:20000'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'link_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'data' => ['nullable', 'array'],
        ];

        foreach ($dataFields as $key => $field) {
            $rules['data.'.$key] = $field['type'] === 'checkbox'
                ? ['nullable', 'boolean']
                : ['nullable', 'string', 'max:255'];
        }

        $data = $request->validate($rules, [], [
            'item_type' => 'item type',
            'image_path' => 'image',
            'image_alt' => 'image description',
            'link_url' => 'link',
        ]);

        // Not every template uses every field, so an item with a blank
        // title is legitimate for `checklist` and `rich_text`.
        foreach (['title', 'subtitle', 'body', 'image_path', 'image_alt', 'link_url', 'link_text'] as $field) {
            $data[$field] = $this->blankToNull($data[$field] ?? null);
        }

        $metadata = $existingItem?->data ?? [];
        foreach ($dataFields as $key => $field) {
            if (! $request->exists('data.'.$key)) {
                continue;
            }

            if ($field['type'] === 'checkbox') {
                $metadata[$key] = $request->boolean('data.'.$key);

                continue;
            }

            $value = trim((string) data_get($data, 'data.'.$key, ''));
            if ($value === '') {
                unset($metadata[$key]);
            } else {
                $metadata[$key] = $value;
            }
        }

        $data['data'] = $metadata ?: null;

        return $data;
    }
}
