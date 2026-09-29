@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="admin-head">
    <div>
        <h1>Dashboard</h1>
        <p>Everything on the public site is edited from here.</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.pages.create') }}" class="abtn abtn--primary">+ New page</a>
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="abtn abtn--ghost">View site</a>
    </div>
</div>

@if ($missingTemplateCount > 0)
    <div class="flash flash--error">
        <span><b>{{ $missingTemplateCount }} section(s) use a layout the site cannot draw.</b>
        Those sections are hidden from visitors. Open the page and change the layout, or delete the section.</span>
    </div>
@endif

<div class="admin-stats">
    <a href="{{ route('admin.pages.index') }}" class="admin-stat">
        <b>{{ $pageCount }}</b><span>Pages</span>
    </a>
                Choose Edit content to change a page's headings, paragraphs, cards, links and images in one place.
    </a>
    <a href="{{ route('admin.pages.index') }}" class="admin-stat">
                <a href="{{ route('admin.pages.index') }}" class="abtn abtn--primary">Manage page content</a>
                @if ($contactPage)
                    <a href="{{ route('admin.pages.edit', $contactPage) }}" class="abtn abtn--ghost">Edit Contact page content</a>
                @endif
                <a href="{{ route('admin.settings.index') }}" class="abtn abtn--ghost">Business contact details</a>
    <a href="{{ route('admin.messages.index') }}" class="admin-stat {{ $unreadCount > 0 ? 'admin-stat--alert' : '' }}">
        <b>{{ $unreadCount }}</b><span>Unread messages</span>
    </a>
    <a href="{{ route('admin.messages.index') }}" class="admin-stat">
        <b>{{ $inboxCount }}</b><span>Total messages</span>
    </a>
    <a href="{{ route('admin.users.index') }}" class="admin-stat">
        <b>{{ $adminCount }}</b><span>Administrators</span>
    </a>
</div>

<div class="admin-card">
    <div class="admin-card__head">
        <h2>Recent messages</h2>
        <div class="spacer"></div>
        <a href="{{ route('admin.messages.index') }}" class="abtn abtn--ghost abtn--sm">Open inbox</a>
    </div>
    <div class="admin-card__body admin-card__body--flush">
        @if ($recentMessages->isEmpty())
            <div class="table-empty">
                <p>No messages yet. Enquiries from the website contact form land here.</p>
            </div>
        @else
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr><th>From</th><th>Subject</th><th>Received</th><th></th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recentMessages as $message)
                            <tr class="{{ $message->is_read ? '' : 'msg-row--unread' }}">
                                <td>
                                    <div class="msg-row">
                                        <span class="avatar">{{ $message->initials }}</span>
                                        <span>
                                            <b>{{ $message->name }}</b><br>
                                            <span class="admin-table__sub">{{ $message->email }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    @if (! $message->is_read)<span class="dot" title="Unread"></span> @endif
                                    {{ $message->subject ?: 'No subject' }}
                                </td>
                                <td class="nowrap muted small">{{ $message->created_at->diffForHumans() }}</td>
                                <td class="text-right">
                                    <a href="{{ route('admin.messages.show', $message) }}" class="abtn abtn--ghost abtn--sm">Open</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="admin-card">
    <div class="admin-card__head"><h2>Pages</h2></div>
    <div class="admin-card__body">
        <p class="muted small" style="margin-bottom:1rem">
            A page is built from sections, and a section is built from content items.
            You can add, reorder, hide and delete all of them without touching code.
        </p>
        <div class="btn-row">
            <a href="{{ route('admin.pages.index') }}" class="abtn abtn--ghost">Manage pages</a>
            <a href="{{ route('admin.settings.index') }}" class="abtn abtn--ghost">Edit contact details</a>
        </div>
    </div>
</div>
@endsection
