@extends('admin.layout')

@section('title', 'Edit ' . $user->name)

@section('content')
<div class="admin-head">
    <div>
        <h1>Edit user</h1>
        <p>{{ $user->name }} &middot; {{ $user->email }}</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.users.index') }}" class="abtn abtn--ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.users.update', $user) }}" class="admin-card">
    @csrf
    @method('PUT')
    <div class="admin-card__body">
        @include('admin.users._form', ['user' => $user, 'isSelf' => auth()->id() === $user->id])
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.users.index') }}" class="abtn abtn--ghost">Cancel</a>
        <button type="submit" class="abtn abtn--primary">Save changes</button>
    </div>
</form>
@endsection
