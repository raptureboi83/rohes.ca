@extends('admin.layout')

@section('title', 'Pages')

@section('content')
<div class="admin-head">
    <div>
        <h1>Website pages</h1>
        <p>Choose a page to change its words, photos or links.</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.pages.create') }}" class="abtn abtn--primary">Add a page</a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card__body admin-card__body--flush">
        @if ($pages->isEmpty())
            <div class="table-empty">
                <p>No pages yet.</p>
                <a href="{{ route('admin.pages.create') }}" class="abtn abtn--primary">Add the first page</a>
            </div>
        @else
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Visible to visitors</th>
                            <th>In menu</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pages as $page)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.pages.edit', $page) }}" class="admin-table__title">
                                        {{ $page->title }}
                                    </a>
                                    @if ($page->is_home)<span class="pill pill--home" style="margin-left:.4rem">Home</span>@endif
                                    <div class="admin-table__sub mono">{{ $page->is_home ? '/' : '/'.$page->slug }}</div>
                                </td>
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
                                            {{ $page->show_in_nav ? 'In menu' : 'Not in menu' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <div class="admin-table__actions">
                                        <a href="{{ $page->is_home ? route('home') : route('page', $page->slug) }}" target="_blank" rel="noopener" class="abtn abtn--ghost abtn--sm">Preview</a>
                                        <a href="{{ route('admin.pages.edit', $page) }}" class="abtn abtn--primary abtn--sm">Edit page</a>
                                        <a href="{{ route('admin.pages.sections.index', $page) }}" class="abtn abtn--ghost abtn--sm">Reorder content</a>
                                        @unless ($page->is_home)
                                            <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" class="inline-form"
                                                  onsubmit="return confirm('Delete &quot;{{ $page->title }}&quot; and all of its content? This cannot be undone.')">
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
