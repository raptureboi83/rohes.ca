@extends('admin.layout')

@section('title', 'Messages')

@section('content')
<div class="admin-head">
    <div>
        <h1>Messages</h1>
        <p>Enquiries sent through the contact form on the website.</p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card__head">
        <div class="flex-between" style="flex-wrap:wrap;gap:.75rem">
            <nav class="pill-nav">
                <a href="{{ route('admin.messages.index') }}" class="{{ $filter === 'inbox' ? 'is-active' : '' }}">
                    Inbox <span class="pill pill--on">{{ $inboxCount }}</span>
                </a>
                <a href="{{ route('admin.messages.index', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'is-active' : '' }}">
                    Unread <span class="pill {{ $unreadCount ? 'pill--warn' : 'pill--off' }}">{{ $unreadCount }}</span>
                </a>
                <a href="{{ route('admin.messages.index', ['filter' => 'archived']) }}" class="{{ $filter === 'archived' ? 'is-active' : '' }}">
                    Archived <span class="pill pill--off">{{ $archivedCount }}</span>
                </a>
            </nav>
        </div>
    </div>

    <div class="admin-card__body admin-card__body--flush">
        @if ($messages->isEmpty())
            <div class="table-empty">
                <p>No messages{{ $filter !== 'inbox' ? ' in this view' : '' }}.</p>
            </div>
        @else
            <div>
                @foreach ($messages as $message)
                    <a href="{{ route('admin.messages.show', $message) }}"
                       class="msg-row {{ $message->is_read ? '' : 'msg-row--unread' }}">
                        <span class="avatar">{{ $message->initials }}</span>
                        <span style="flex:1;min-width:0">
                            <span class="msg-subject">{{ $message->subject ?: '(no subject)' }}</span>
                            <span class="muted small">
                                {{ $message->name }} &middot; {{ $message->email }}
                            </span>
                        </span>
                        <span class="nowrap muted small">{{ $message->created_at?->diffForHumans() }}</span>
                        @unless ($message->is_read)<span class="dot" title="Unread"></span>@endunless
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    @if ($messages->hasPages())
        <div class="pagination">{{ $messages->links() }}</div>
    @endif
</div>
@endsection
