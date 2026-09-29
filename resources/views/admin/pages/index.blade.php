@extends('admin.layout')

@section('title', 'Pages')

@section('content')
<div class="admin-head">
    <div>
        <h1>Pages</h1>
        <p>Each page is a real web address, e.g. <code>/services</code>.</p>
            <p>Choose Edit content to update a page's text, links and images directly.</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.pages.create') }}" class="abtn abtn--primary">+ New page</a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card__body admin-card__body--flush">
        @if ($pages->isEmpty())
            <div class="table-empty">
                <p>No pages yet.</p>
                <a href="{{ route('admin.pages.create') }}" class="abtn abtn--primary">Create the first page</a>
            </div>
        @else
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:70px">Order</th>
                            <th>Page</th>
                            <th>Sections</th>
                            <th>Status</th>
                            <th>Menu</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td class="muted mono">{{ $page->sort_order }}</td>
                                <td>
                                    <a href="{{ route('admin.pages.edit', $page) }}" class="admin-table__title">
                                        {{ $page->title }}
                                    </a>
                                    @if ($page->is_home)<span class="pill pill--home" style="margin-left:.4rem">Home</span>@endif
                                    <div class="admin-table__sub mono">/{{ $page->slug }}</div>
                                </td>
                                <td>{{ $page->sections_count }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.pages.toggle', [$page, 'is_active']) }}" class="inline-form">
                                        @csrf @method('PATCH')
                                        <button class="pill {{ $page->is_active ? 'pill--on' : 'pill--off' }} pill--dot" style="border:0;cursor:pointer">
                                            {{ $page->is_active ? 'Live' : 'Hidden' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('admin.pages.toggle', [$page, 'show_in_nav']) }}" class="inline-form">
                                        @csrf @method('PATCH')
                                        <button class="pill {{ $page->show_in_nav ? 'pill--on' : 'pill--off' }} pill--dot" style="border:0;cursor:pointer">
                                            {{ $page->show_in_nav ? 'Shown' : 'Not in menu' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="admin-table__actions">
                                        <a href="{{ route('page', $page->slug) }}" target="_blank" rel="noopener" class="abtn abtn--ghost abtn--sm">View</a>
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="abtn abtn--primary abtn--sm">Edit content</a>
                                        <a href="{{ route('admin.pages.sections.index', $page) }}" class="abtn abtn--ghost abtn--sm">Structure</a>
                                        @unless ($page->is_home)
                                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="inline-form"
                                                  onsubmit="return confirm('Delete &quot;{{ $page->title }}&quot; and all of its sections? This cannot be undone.')">
                                                @csrf @method('DELETE')
                                                <button class="abtn abtn--danger abtn--sm">Delete</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($pages->hasPages())
                <div class="pagination">{{ $pages->links() }}</div>
            @endif
        @endif
    </div>
</div>
@endsection
