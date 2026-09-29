@extends('admin.layout')

@section('title', 'Edit ' . $page->title)

@section('content')
<div class="admin-head">
    <div>
        <h1>Edit {{ $page->title }}</h1>
        <p>Edit the words and pictures that appear on this page below.</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.pages.sections.index', $page) }}" class="abtn abtn--ghost">Section list</a>
        <a href="{{ route('page', $page->slug) }}" target="_blank" rel="noopener" class="abtn abtn--ghost">View</a>
    </div>
</div>

<div class="admin-card" style="margin-top:1.5rem">
    <div class="admin-card__head">
        <div>
            <h2>Page content</h2>
            <p class="hint" style="margin:.25rem 0 0">Expand a section or item to edit its text, links, settings or image. Save each part independently.</p>
        </div>
        <a href="{{ route('admin.pages.sections.create', $page) }}" class="abtn abtn--primary abtn--sm">+ Add section</a>
    </div>
    <div class="admin-card__body admin-card__body--flush">
        @if ($page->sections->isEmpty())
            <div class="table-empty">
                <p>This page has no sections yet, so nothing will show on it.</p>
                <a href="{{ route('admin.pages.sections.create', $page) }}" class="abtn abtn--primary">Add the first section</a>
            </div>
        @else
            <div style="padding:0 1.25rem">
                @foreach ($page->sections as $section)
                    <section style="padding:1.25rem 0;border-bottom:1px solid var(--line)">
                        <div style="display:flex;align-items:center;gap:.65rem;flex-wrap:wrap;margin-bottom:.8rem">
                            <h3 style="font-size:1.05rem;margin:0">{{ $section->title }}</h3>
                            <span class="pill">{{ App\Models\Section::TEMPLATE_OPTIONS[$section->template_key] ?? $section->template_key }}</span>
                            <span class="muted small">{{ $section->items->count() }} item{{ $section->items->count() === 1 ? '' : 's' }}</span>
                        </div>

                        <details style="margin-bottom:.75rem">
                            <summary style="cursor:pointer;font-weight:600">Edit section text and layout</summary>
                            <form method="POST" action="{{ route('admin.sections.update', $section) }}" style="margin-top:1rem">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="return_to_page" value="1">
                                @include('admin.sections._form', ['section' => $section, 'fieldPrefix' => 'section-'.$section->id.'-'])
                                <div class="form-actions">
                                    <button type="submit" class="abtn abtn--primary">Save section</button>
                                </div>
                            </form>
                        </details>

                        @foreach ($section->items as $item)
                            <details style="margin:.5rem 0 .5rem 1rem">
                                <summary style="cursor:pointer">Edit item: <b>{{ $item->title ?: 'Untitled item' }}</b>
                                    @if ($item->image_path)<span class="muted small">&middot; has image</span>@endif
                                </summary>
                                <form method="POST" action="{{ route('admin.sections.items.update', [$section, $item]) }}" style="margin-top:1rem">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="return_to_page" value="1">
                                    @include('admin.items._form', [
                                        'item' => $item,
                                        'fieldPrefix' => 'item-'.$item->id.'-',
                                        'availableImages' => $availableImages,
                                    ])
                                    <div class="form-actions">
                                        <button type="submit" class="abtn abtn--primary">Save item</button>
                                    </div>
                                </form>
                            </details>
                        @endforeach

                        <a href="{{ route('admin.sections.items.create', $section) }}" class="abtn abtn--ghost abtn--sm" style="margin: .75rem 0 0 1rem">+ Add item</a>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
</div>

<details class="admin-card" style="margin-top:1.25rem">
    <summary class="admin-card__head" style="cursor:pointer">More page options (menu, web address and search details)</summary>
    <form method="POST" action="{{ route('admin.pages.update', $page) }}">
        @csrf
        @method('PUT')
        <div class="admin-card__body">
            @include('admin.pages._form', ['page' => $page])
        </div>
        <div class="form-actions">
            <button type="submit" class="abtn abtn--primary">Save page options</button>
        </div>
    </form>
</details>
@endsection
