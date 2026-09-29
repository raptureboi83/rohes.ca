@extends('admin.layout')

@section('title', 'User accounts')

@section('content')
<div class="admin-head">
    <div>
        <h1>User accounts</h1>
        <p>Only administrators can sign in to this site.</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.users.create') }}" class="abtn abtn--primary">+ Add user</a>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card__body admin-card__body--flush">
        <div class="table-scroll">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Last seen</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <div class="flex-between" style="gap:.75rem">
                                    <span>
                                        <span class="admin-table__title">{{ $user->name }}</span>
                                        <span class="admin-table__sub">{{ $user->email }}</span>
                                    </span>
                                    @if ($user->is_authenticated)
                                        <span class="pill pill--home">You</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($user->is_admin)
                                    <span class="pill pill--on">Administrator</span>
                                @else
                                    <span class="pill pill--off">No admin access</span>
                                @endif
                            </td>
                            <td class="muted small nowrap">
                                Added {{ $user->created_at?->format('j M Y') ?? '—' }}
                            </td>
                            <td>
                                <div class="admin-table__actions">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="abtn abtn--ghost abtn--sm">Edit</a>
                                    @unless ($user->is_authenticated)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline-form"
                                              onsubmit="return confirm('Delete the account for {{ $user->name }}?')">
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

        @if ($users->hasPages())
            <div class="pagination">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
