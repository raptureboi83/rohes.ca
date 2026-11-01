@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="admin-head dashboard-head">
    <div>
        <h1>Website dashboard</h1>
        <p>Choose a page to change its words, photos or links.</p>
    </div>
    <div class="admin-head__actions">
        @if ($homePage && $homePage->is_active)
            <a href="{{ route('admin.pages.edit', $homePage) }}" class="abtn abtn--primary">Edit home page</a>
        @else
            <a href="{{ route('admin.pages.index') }}" class="abtn abtn--primary">Choose home page</a>
        @endif
    </div>
</div>

{{-- Only rendered when something is actually wrong. See DashboardController::attentionItems. --}}
@if (count($attention))
    <div class="admin-card admin-card--attention">
        <div class="admin-card__head">
            <h2>Things to check</h2>
            <div class="spacer"></div>
            <span class="muted small">{{ count($attention) }} item{{ count($attention) === 1 ? '' : 's' }}</span>
        </div>
        <ul class="attention-list">
            @foreach ($attention as $item)
                <li class="attention attention--{{ $item['severity'] }}">
                    <span>{{ $item['message'] }}</span>
                    @if ($item['url'])
                        <a href="{{ $item['url'] }}" class="abtn abtn--ghost abtn--sm">{{ $item['action'] }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Unread enquiries are the only thing on this screen that can be time-sensitive,
     so they get a full-width slot and disappear once they are all read. --}}
@if ($unreadCount > 0)
    <div class="admin-card admin-card--unread">
        <div class="admin-card__head">
            <h2>Messages to read</h2>
            <span class="pill pill--home">{{ $unreadCount }}</span>
            <div class="spacer"></div>
            <a href="{{ route('admin.messages.index') }}" class="abtn abtn--primary abtn--sm">Open messages</a>
        </div>
        @if ($recentMessages->isNotEmpty())
            <ul class="attention-list">
                @foreach ($recentMessages as $message)
                    <li class="attention attention--error">
                        <span>
                            <b>{{ $message->name }}</b>
                            &mdash; {{ $message->subject ?: 'No subject' }}
                            <span class="muted small">{{ $message->created_at->diffForHumans() }}</span>
                        </span>
                        <a href="{{ route('admin.messages.show', $message) }}" class="abtn abtn--ghost abtn--sm">Open</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

<div class="dash-cols">
    <div class="admin-card">
        <div class="admin-card__head">
            <h2>Website pages</h2>
            <div class="spacer"></div>
            <a href="{{ route('admin.pages.create') }}" class="abtn abtn--ghost abtn--sm">Add a page</a>
        </div>
        <ul class="page-list">
            @forelse ($pages as $page)
                <li class="page-list__row">
                    <span>
                        <b>{{ $page->display_label }}</b>
                        <span class="page-list__meta">
                            @if ($page->is_home)Home page @else /{{ $page->slug }}@endif
                            @if ($page->is_active)<span class="pill pill--on">Live</span>@else<span class="pill pill--warn">Hidden</span>@endif
                        </span>
                    </span>
                    <a href="{{ route('admin.pages.edit', $page) }}" class="abtn abtn--ghost abtn--sm">Edit page</a>
                </li>
            @empty
                <li class="table-empty"><p>No pages yet.</p></li>
            @endforelse
        </ul>
    </div>

    <div class="admin-card">
        <div class="admin-card__head">
            <h2>Recently updated</h2>
        </div>
        <ul class="page-list">
            @forelse ($recentlyUpdated as $section)
                <li class="page-list__row">
                    <span>
                        <b>{{ $section->title ?: 'Untitled section' }}</b>
                        <span class="page-list__meta">
                            {{ $section->page?->display_label }} &middot; {{ $section->updated_at->diffForHumans() }}
                        </span>
                    </span>
                    <a href="{{ route('admin.pages.edit', $section->page) }}" class="abtn abtn--ghost abtn--sm">Open page</a>
                </li>
            @empty
                <li class="table-empty"><p>Nothing has been edited yet.</p></li>
            @endforelse
        </ul>
    </div>
</div>

@endsection