@extends('admin.layout')

@section('title', 'Add user')

@section('content')
<div class="admin-head">
    <div>
        <h1>Add a user</h1>
        <p>They will be able to sign in immediately with the password you set.</p>
    </div>
    <div class="admin-head__actions">
        <a href="{{ route('admin.users.index') }}" class="abtn abtn--ghost">Cancel</a>
    </div>
</div>

<form method="POST" action="{{ route('admin.users.store') }}" class="admin-card">
    @csrf
    <div class="admin-card__body">
        @include('admin.users._form', ['user' => $user, 'isSelf' => false])
    </div>
    <div class="form-actions">
        <a href="{{ route('admin.users.index') }}" class="abtn abtn--ghost">Cancel</a>
        <button type="submit" class="abtn abtn--primary">Create user</button>
    </div>
</form>
@endsection
