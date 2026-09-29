@extends('admin.layout')

@section('title', 'Message')

@section('content')
<div class="admin-head">
    <div>
        <h1>{{ $message->subject ?: 'Enquiry' }}</h1>
        <p>
            From {{ $message->name }} &middot;
            {{ $message->created_at?->format('j F Y \a\t g:i a') }}
            @if ($message->is_read)
                <span class="pill pill--off">Read</span>
            @else
                <span class="pill pill--warn">Unread</span>
            @endif
        </p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.messages.index') }}" class="abtn abtn--ghost">Back</a>
        <a href="{{ $message->replyMailto() }}" class="abtn abtn--primary">Reply by email</a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card__body">
        <dl class="detail">
            <dt>Name</dt>
            <dd>{{ $message->name }}</dd>

            <dt>Email</dt>
            <dd><a href="mailto:{{ $message->email }}">{{ $message->email }}</a></dd>

            <dt>Phone</dt>
            <dd>
                @if ($message->phone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $message->phone) }}">{{ $message->phone }}</a>
                @else
                    <span class="muted">not given</span>
                @endif
            </dd>

            @if ($message->interest)
                <dt>Interested in</dt>
                <dd>{{ $message->interest }}</dd>
            @endif

            <dt>Received</dt>
            <dd>{{ $message->created_at?->format('j F Y \a\t g:i a') }}</dd>
        </dl>

        <h2 style="font-size:1rem;margin:1.5rem 0 .6rem">Message</h2>
        <div class="msg-body">{{ $message->message }}</div>
    </div>

    <div class="form-actions">
        <form method="POST" action="{{ route('admin.messages.read', $message) }}" class="inline-form">
            @csrf @method('PATCH')
            <button class="abtn abtn--ghost">{{ $message->is_read ? 'Mark as unread' : 'Mark as read' }}</button>
        </form>

        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" class="inline-form"
              onsubmit="return confirm('Delete this message permanently? This cannot be undone.')">
            @csrf @method('DELETE')
            <button class="abtn abtn--danger">Delete</button>
        </form>
    </div>
</div>
@endsection
