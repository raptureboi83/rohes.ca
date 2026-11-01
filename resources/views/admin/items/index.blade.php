@extends('admin.layout')

@section('title', 'Items in ' . $section->title)

@section('content')
<div class="admin-head">
    <div>
        <h1>Content items</h1>
        <p>{{ $section->title }} &middot; on <a href="{{ route('admin.pages.sections.index', $page) }}">{{ $page->title }}</a></p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.sections.edit', $section) }}" class="abtn abtn--ghost">Section settings</a>
        <a href="{{ route('admin.sections.items.create', $section) }}" class="abtn abtn--primary">+ Add item</a>
    </div>
</div>

<div class="callout">
    <b>Layout: {{ App\Models\Section::TEMPLATE_OPTIONS[$section->template_key] ?? $section->template_key }}.</b>
    {{ App\Models\Section::TEMPLATE_HELP[$section->template_key] ?? '' }}
</div>

<div class="admin-card" style="margin-top:1.25rem">
    <div class="admin-card__body admin-card__body--flush">
        @if ($items->isEmpty())
            <div class="table-empty">
                <p>No content items yet. This section will render empty on the website.</p>
                <a href="{{ route('admin.sections.items.create', $section) }}" class="abtn abtn--primary">Add the first item</a>
            </div>
        @else
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:110px">Order</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Image</th>
                            <th>Link</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>
                                    <div class="order-buttons">
                                        <form method="POST" action="{{ route('admin.sections.items.move', [$section, $item, 'up']) }}" class="inline-form">
                                            @csrf @method('PATCH')
                                            <button title="Move up" @disabled($loop->first)>&uarr;</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.sections.items.move', [$section, $item, 'down']) }}" class="inline-form">
                                            @csrf @method('PATCH')
                                            <button title="Move down" @disabled($loop->last)>&darr;</button>
                                        </form>
                                    </div>
                                </td>
                                <td>
                                    <a href="{{ route('admin.sections.items.edit', [$section, $item]) }}" class="admin-table__title">
                                        {{ $item->title ?: '(no title)' }}
                                    </a>
                                    @if ($item->subtitle)
                                        <div class="admin-table__sub">{{ $item->subtitle }}</div>
                                    @endif
                                    @if ($item->body)
                                        <div class="admin-table__sub">{{ \Illuminate\Support\Str::limit(strip_tags($item->body), 90) }}</div>
                                    @endif
                                </td>
                                <td><span class="pill">{{ $item->type_label }}</span></td>
                                <td>
                                    @if ($item->image_path)
                                        <img class="admin-image-thumb" src="{{ media_src($item->image_path) }}" alt="">
                                    @else
                                        <span class="muted small">none</span>
                                    @endif
                                </td>
                                <td class="mono small">{{ $item->link_url ?: '—' }}</td>
                                <td>
                                    <div class="admin-table__actions">
                                        <a href="{{ route('admin.sections.items.edit', [$section, $item]) }}" class="abtn abtn--ghost abtn--sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.sections.items.destroy', [$section, $item]) }}" class="inline-form"
                                              onsubmit="return confirm('Delete this item?')">
                                            @csrf @method('DELETE')
                                            <button class="abtn abtn--danger abtn--sm">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
