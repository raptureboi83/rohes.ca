@extends('admin.layout')

@section('title', 'Sections of ' . $page->title)

@section('content')
<div class="admin-head">
    <div>
        <h1>Sections</h1>
        <p>Page: <a href="{{ route('admin.pages.sections.index', $page) }}">{{ $page->title }}</a>
            &middot; <a href="{{ route('page', $page->slug) }}" target="_blank" rel="noopener">view live</a></p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.pages.index') }}" class="abtn abtn--ghost">All pages</a>
        <a href="{{ route('admin.pages.sections.create', $page) }}" class="abtn abtn--primary">+ Add section</a>
    </div>
</div>

@if ($page->template_key)
    <div class="callout">
        <b>This page uses a custom layout.</b> Sections may not appear the way you expect.
    </div>
@endif

<div class="admin-card">
    <div class="admin-card__body admin-card__body--flush">
        @if ($sections->isEmpty())
            <div class="table-empty">
                <p>This page has no sections, so it is currently empty on the website.</p>
                <a href="{{ route('admin.pages.sections.create', $page) }}" class="abtn abtn--primary">Add the first section</a>
            </div>
        @else
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:70px">Order</th>
                            <th>Section</th>
                            <th>Layout</th>
                            <th style="width:90px">Items</th>
                            <th>Visible</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sections as $section)
                            <tr>
                                <td class="muted mono">{{ $section->sort_order }}</td>
                                <td>
                                    <a href="{{ route('admin.sections.items.index', $section) }}" class="admin-table__title">
                                        {{ $section->title }}
                                    </a>
                                    @if ($section->anchor_id)
                                        <div class="admin-table__sub mono">#{{ $section->anchor_id }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="pill pill--on">{{ App\Models\Section::TEMPLATE_OPTIONS[$section->template_key] ?? $section->template_key }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.sections.items.index', $section) }}">{{ $section->items_count }}</a>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.sections.toggle', [$section, 'is_visible']) }}" class="inline-form">
                                        @csrf @method('PATCH')
                                        <button class="pill {{ $section->is_visible ? 'pill--on' : 'pill--off' }} pill--dot" style="border:0;cursor:pointer">
                                            {{ $section->is_visible ? 'Visible' : 'Hidden' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="admin-table__actions">
                                        <a href="{{ route('admin.sections.items.index', $section) }}" class="abtn abtn--primary abtn--sm">Items</a>
                                        <a href="{{ route('admin.sections.edit', $section) }}" class="abtn abtn--ghost abtn--sm">Edit</a>
                                        <form method="POST" action="{{ route('admin.sections.destroy', $section) }}" class="inline-form"
                                              onsubmit="return confirm('Delete the section &quot;{{ $section->title }}&quot; and its {{ $section->items_count }} content items?')">
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
